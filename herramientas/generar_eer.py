# Genera Reportes_md/DIAGRAMA_EER.md a partir del esquema REAL de ruta_nomada.
import subprocess, io, collections, os

M = r"C:\xampp\mysql\bin\mysql.exe"
NL = "\n"

def q(sql):
    o = subprocess.run([M, '-u', 'root', 'ruta_nomada', '-N', '-e', sql],
                       capture_output=True, text=True, encoding='utf-8')
    if o.returncode:
        raise SystemExit(o.stderr)
    return [l.split('\t') for l in o.stdout.splitlines() if l.strip()]

cols = q("SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, COLUMN_KEY, IS_NULLABLE "
         "FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='ruta_nomada' "
         "ORDER BY TABLE_NAME, ORDINAL_POSITION")
fks = q("SELECT k.TABLE_NAME, k.COLUMN_NAME, k.REFERENCED_TABLE_NAME, r.DELETE_RULE "
        "FROM information_schema.KEY_COLUMN_USAGE k "
        "JOIN information_schema.REFERENTIAL_CONSTRAINTS r "
        "  ON r.CONSTRAINT_NAME=k.CONSTRAINT_NAME AND r.CONSTRAINT_SCHEMA=k.TABLE_SCHEMA "
        "WHERE k.TABLE_SCHEMA='ruta_nomada' AND k.REFERENCED_TABLE_NAME IS NOT NULL "
        "ORDER BY k.REFERENCED_TABLE_NAME, k.TABLE_NAME")
uniq = q("SELECT TABLE_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) "
         "FROM information_schema.STATISTICS "
         "WHERE TABLE_SCHEMA='ruta_nomada' AND NON_UNIQUE=0 AND INDEX_NAME<>'PRIMARY' "
         "GROUP BY TABLE_NAME, INDEX_NAME")

fkcols = set((r[0], r[1]) for r in fks)
uk = collections.defaultdict(set)
for r in uniq:
    for c in r[1].split(','):
        uk[r[0]].add(c)

tablas = collections.OrderedDict()
nulable = {}
for r in cols:
    t, c, typ, key, nul = r[0], r[1], r[2], r[3], r[4]
    tablas.setdefault(t, []).append((c, typ, key, nul))
    nulable[(t, c)] = (nul == 'YES')

verbo = {
    ('usuarios', 'planes'): 'crea',
    ('usuarios', 'password_resets'): 'solicita',
    ('usuarios', 'favoritos'): 'marca',
    ('usuarios', 'viajes_usuario'): 'guarda',
    ('usuarios', 'plan_miembros'): 'participa-en',
    ('usuarios', 'plan_item_gasto'): 'debe',
    ('usuarios', 'plan_item_reacciones'): 'reacciona',
    ('usuarios', 'ai_uso'): 'consulta',
    ('destinos', 'favoritos'): 'es-marcado',
    ('destinos', 'viajes_usuario'): 'es-guardado',
    ('destinos', 'plan_destinos'): 'se-asocia',
    ('planes', 'plan_items'): 'contiene',
    ('planes', 'plan_gastos'): 'registra',
    ('planes', 'plan_listas'): 'organiza',
    ('planes', 'plan_miembros'): 'comparte',
    ('planes', 'plan_invitaciones'): 'invita',
    ('planes', 'plan_destinos'): 'apunta-a',
    ('planes', 'viajes_usuario'): 'nace-de',
    ('planes', 'ai_uso'): 'genera',
    ('plan_items', 'plan_item_gasto'): 'divide',
    ('plan_items', 'plan_item_reacciones'): 'recibe',
    ('plan_listas', 'plan_lista_items'): 'agrupa',
}

rel, filas = [], []
for r in fks:
    hijo, col, padre, dr = r[0], r[1], r[2], r[3]
    izq = '|o' if nulable[(hijo, col)] else '||'
    v = verbo.get((padre, hijo), 'tiene')
    rel.append('    %s %s--o{ %s : "%s"' % (padre, izq, hijo, v))
    opt = 'si' if nulable[(hijo, col)] else 'no'
    filas.append('| `%s` | `%s.%s` | 1 : 0..N | `%s` | %s |' % (padre, hijo, col, dr, opt))

ent = []
for t, cs in tablas.items():
    ent.append('    %s {' % t)
    for c, typ, key, nul in cs:
        m = 'PK' if key == 'PRI' else ('FK' if (t, c) in fkcols else ('UK' if c in uk[t] else ''))
        s = '        %s %s' % (typ.split('(')[0].split(' ')[0], c)
        if m:
            s += ' ' + m
        if nul == 'YES':
            s += ' "opcional"'
        ent.append(s)
    ent.append('    }')

P = []
A = P.append
A('# Diagrama EER - base de datos `ruta_nomada`')
A('')
A('Modelo entidad-relacion extendido de Ruta Nomada, generado **a partir')
A('del esquema real** consultando `information_schema`, no dibujado a mano.')
A('')
A('- **%d entidades** - **%d atributos** - **%d relaciones**' % (len(tablas), len(cols), len(rel)))
A('- Motor InnoDB - MariaDB 10.4.32')
A('- Script de creacion: `basedatos/instalar.sql`')
A('')
A('> **Como verlo.** Los diagramas estan en formato Mermaid y se dibujan')
A('> solos en GitHub, en la vista previa de VS Code (extension *Markdown')
A('> Preview Mermaid*) y en <https://mermaid.live>. Para el diagrama nativo')
A('> de MySQL Workbench, ver el apartado 6.')
A('')
A('---')
A('')
A('## 1. Mapa de relaciones')
A('')
A('Vista de conjunto sin atributos: como se conectan las 19 tablas.')
A('')
A('```mermaid')
A('erDiagram')
P.extend(rel)
A('```')
A('')
A('**Como leer la notacion (pata de gallo):**')
A('')
A('| Simbolo | Significado |')
A('|---------|-------------|')
# Las barras hay que escaparlas: dentro de una tabla Markdown parten la
# celda aunque vayan entre comillas invertidas.
A(r'| `\|\|` | exactamente uno |')
A(r'| `\|o` | cero o uno *(la clave foranea admite NULL)* |')
A('| `o{` | cero o muchos |')
A('')
A('Ejemplo: `usuarios ||--o{ planes` se lee **"un usuario crea de cero a')
A('muchos planes, y todo plan pertenece exactamente a un usuario"**.')
A('')
A('---')
A('')
A('## 2. Diagrama completo con atributos')
A('')
A('Todas las entidades con sus columnas, tipos y claves.')
A('`PK` = clave primaria - `FK` = clave foranea - `UK` = clave unica.')
A('')
A('```mermaid')
A('erDiagram')
P.extend(rel)
A('')
P.extend(ent)
A('```')
A('')
A('---')
A('')
A('## 3. Las %d relaciones en detalle' % len(rel))
A('')
A('Que ocurre con las filas hijas cuando se borra la fila padre:')
A('')
A('| Entidad padre | Clave foranea | Cardinalidad | Al borrar el padre | Admite NULL |')
A('|---------------|---------------|--------------|--------------------|--------------|')
P.extend(filas)
A('')
A('**Las excepciones al `CASCADE`** estan puestas a proposito:')
A('')
A('- **`ai_uso.plan_id` -> `SET NULL`.** El contador de consumo de la IA no')
A('  debe borrarse al borrar un viaje: sirve para controlar el gasto del')
A('  usuario a lo largo del tiempo.')
A('- **`viajes_usuario.plan_id` -> `SET NULL`.** Es el historial de destinos')
A('  que el usuario guardo. Al borrar un plan se rompe el vinculo, pero el')
A('  recuerdo del destino se conserva.')
A('- **`viajes_usuario.usuario_id` y `.destino_id` -> `RESTRICT`.** Impiden')
A('  borrar un usuario o un destino que todavia tenga historial asociado.')
A('')
A('Y una tabla **sin ninguna clave foranea, tambien a proposito**:')
A('`planes_borrados` es el archivo de auditoria del borrado, y su fila debe')
A('**sobrevivir** al plan que registra. Una clave foranea la borraria en')
A('cascada, justo lo contrario de lo que se busca.')
A('')
A('---')
A('')
A('## 4. Las entidades por familia')
A('')
A('| Familia | Entidades | Para que |')
A('|---------|-----------|----------|')
A('| Personas | `usuarios`, `password_resets` | Quien entra al sistema |')
A('| Catalogo | `destinos`, `favoritos`, `viajes_usuario` | Destinos que ofrece la app |')
A('| El viaje | `planes`, `plan_items`, `plan_gastos` | El corazon del sistema |')
A('| Compartir | `plan_miembros`, `plan_invitaciones`, `plan_item_reacciones`, `plan_item_gasto` | Viajar acompanado |')
A('| Organizar | `plan_listas`, `plan_lista_items`, `plan_destinos` | Notas y pendientes |')
A('| Servicio | `tramo_cache`, `ruta_uso`, `ai_uso`, `planes_borrados` | Ahorro, control y auditoria |')
A('')
A('El proposito de cada tabla esta desarrollado en `REPORTE_base_de_datos.md`.')
A('')
A('---')
A('')
A('## 5. Las cuatro tablas puente')
A('')
A('Las relaciones de muchos a muchos no se pueden representar directamente')
A('entre dos tablas: necesitan una tabla intermedia. En este modelo hay')
A('cuatro, y conviene senalarlas porque son la parte del diseno que mas')
A('suele evaluarse:')
A('')
A('| Tabla puente | Conecta | Dato extra que aporta |')
A('|--------------|---------|------------------------|')
A('| `favoritos` | `usuarios` <-> `destinos` | ninguno (clave primaria compuesta) |')
A('| `plan_destinos` | `planes` <-> `destinos` | ninguno (clave primaria compuesta) |')
A('| `plan_miembros` | `usuarios` <-> `planes` | el **rol** (propietario / editor / lector) |')
A('| `plan_item_gasto` | `usuarios` <-> `plan_items` | el **importe** que le toca a cada uno |')
A('')
A('Las dos primeras son puentes puros: solo unen. Las dos ultimas llevan un')
A('atributo propio en la relacion -el rol y el importe-, que es justo lo que')
A('obliga a que existan como entidad y no como un simple par de columnas.')
A('')
A('---')
A('')
A('## 6. Generar el diagrama nativo de MySQL Workbench')
A('')
A('Este documento es el modelo en Mermaid, versionable junto al codigo. Si')
A('se necesita el diagrama EER **de Workbench** -el de las cajas con las')
A('lineas de patas de gallo-, se obtiene en cuatro pasos:')
A('')
A('1. Abrir MySQL Workbench y conectar a `127.0.0.1:3306`, usuario `root`,')
A('   contrasena vacia.')
A('2. Menu **Database -> Reverse Engineer...** (`Ctrl+R`).')
A('3. Pulsar *Next* hasta la lista de esquemas, marcar **`ruta_nomada`** y')
A('   seguir hasta *Execute*.')
A('4. Al terminar aparece la pestana **EER Diagram** con las 19 tablas y sus')
A('   relaciones dibujadas.')
A('')
A('Un par de avisos:')
A('')
A('- Workbench mostrara *"Incompatible/nonstandard server version detected')
A('  (10.4.32-MariaDB)"*. Se puede continuar: la ingenieria inversa')
A('  funciona con normalidad.')
A('- El diagrama sale con las tablas amontonadas. Conviene arrastrarlas y')
A('  agruparlas por familias (apartado 4) antes de exportarlo con')
A('  **File -> Export -> Export as PNG/SVG**.')
A('')
A('---')
A('')
A('*Generado a partir del estado real de la base `ruta_nomada`. Si el')
A('esquema cambia, este archivo hay que regenerarlo para que no mienta.*')

destino = os.path.join(r"C:\xampp\htdocs\Ruta Nómada (v1)", 'Reportes_md', 'DIAGRAMA_EER.md')
io.open(destino, 'w', encoding='utf-8').write(NL.join(P) + NL)
print('escrito:', destino)
print('entidades %d | atributos %d | relaciones %d' % (len(tablas), len(cols), len(rel)))
