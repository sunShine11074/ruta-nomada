# Guía de Git — Ruta Nómada

Para dejar de bajar archivos `.zip` cada vez.

Con Git basta con escribir **un comando** para tener la última versión.
Sólo hay que hacer la instalación una vez; después son diez segundos.

**No hace falta cuenta de GitHub ni contraseña.** El repositorio es
público, así que bajar cambios no pide nada.

---

## Antes de empezar

Abre **Git Bash** (el buscador de Windows → escribe «Git Bash») y pega:

```bash
git --version
```

Si responde algo como `git version 2.x.x`, todo bien. Si dice que no
reconoce el comando, instala Git desde <https://git-scm.com/download/win>
y vuelve a abrir la ventana.

> **Un consejo:** usa siempre **Git Bash**, no el CMD normal. Los
> comandos de esta guía están escritos para él.

### ⚠ Si usas la terminal de VS Code, lee esto

La terminal de VS Code abre **PowerShell**, no Git Bash, y ahí **la
mitad de los comandos de esta guía no funcionan**. Dos motivos:

| En Git Bash | En PowerShell |
|---|---|
| `git pull && php algo.php` | `&&` **no existe** → un comando por línea |
| `/c/xampp/mysql/bin/mysql.exe` | `& "C:\xampp\mysql\bin\mysql.exe"` |
| `php herramientas/diagnostico.php` | `& "C:\xampp\php\php.exe" herramientas\diagnostico.php` |

Si pegas un comando con `&&` en PowerShell verás esto, y **no se ejecuta
nada**, ni siquiera la primera parte:

```
El token '&&' no es un separador de instrucciones válido en esta versión.
```

Tienes dos salidas. La cómoda:

**Cambia la terminal de VS Code a Git Bash.** Pulsa el `∨` que hay al
lado del `+` en la esquina de la terminal → *Git Bash*. A partir de ahí
todos los comandos de esta guía funcionan tal cual.

O si prefieres quedarte en PowerShell, usa la columna de la derecha de
la tabla y pega **una línea cada vez**.

### O ni terminal ni nada: doble clic

Para lo que hay que hacer después de cada `git pull` —poner la base de
datos al día y comprobar que no falta nada— hay un atajo:

> En la carpeta del proyecto, entra en **`herramientas`** y haz **doble
> clic en `actualizar.bat`**.

Se coloca solo en la carpeta correcta, usa las rutas completas de XAMPP,
actualiza la base sin borrar nada y termina enseñando el diagnóstico. Da
igual qué terminal tengas configurada. Si XAMPP no está en `C:\xampp`,
ábrelo con el Bloc de notas y corrige las dos primeras rutas.

**Ojo con una confusión fácil:** `cd` sirve para *entrar en una carpeta*.
`mysql.exe` no es una carpeta, es un programa, así que `cd mysql.exe`
siempre va a dar «No se encuentra la ruta de acceso». Y los comandos de
base de datos hay que lanzarlos **desde la carpeta del proyecto**, no
desde `C:\xampp\mysql\bin`, porque la ruta `basedatos/actualizar_bd.sql`
se busca a partir de donde estés.

---

## Parte 1 — Instalación (esto se hace UNA sola vez)

### 1. Guarda tus claves

En tu carpeta actual del proyecto, busca la carpeta `includes` y copia
estos archivos a algún sitio seguro (el Escritorio, por ejemplo):

- `includes/maps_config.php`
- `includes/ai_config.php`
- `includes/geo_config.php`
- `includes/mail_config.php`

Puede que no tengas los cuatro; copia los que existan.

**Por qué:** esos archivos llevan las claves de Google y a propósito
*nunca* viajan por GitHub, para que no queden publicadas. Los vas a
volver a poner en el paso 4.

### 2. Aparta tu carpeta vieja

Ve a `C:\xampp\htdocs`, y a la carpeta del proyecto que ya tienes.
**Renómbrala** (por ejemplo, añádele `-viejo` al final).

No la borres todavía. Cuando compruebes que la nueva funciona, la
borras.

### 3. Descarga el proyecto con Git

En Git Bash, pega estas dos líneas, una detrás de otra:

```bash
cd /c/xampp/htdocs
git clone https://github.com/sunShine11074/ruta-nomada.git
```

Tarda un momento. Al terminar tendrás una carpeta nueva llamada
`ruta-nomada` con todo el proyecto dentro.

> El nombre de la carpeta da igual: la aplicación se adapta sola. Si
> prefieres otro, ponlo al final del comando:
> `git clone https://github.com/sunShine11074/ruta-nomada.git mi-carpeta`

### 4. Devuelve tus claves a su sitio

Copia los archivos que guardaste en el paso 1 dentro de la carpeta
`includes` de la **carpeta nueva**.

Si no tenías alguno, créalo a partir de su plantilla. En Git Bash:

```bash
cd /c/xampp/htdocs/ruta-nomada
cp includes/maps_config.sample.php includes/maps_config.php
cp includes/ai_config.sample.php  includes/ai_config.php
cp includes/geo_config.sample.php includes/geo_config.php
cp includes/mail_config.sample.php includes/mail_config.php
```

Después ábrelos con el Bloc de notas y pega la clave donde dice
`PON_AQUI_TU_KEY`.

### 5. Comprueba que no falta nada

```bash
php herramientas/diagnostico.php
```

Si `php` no se reconoce —y en XAMPP normalmente **no** se reconoce,
porque no está en el PATH— usa la ruta completa:

```bash
/c/xampp/php/php.exe herramientas/diagnostico.php
```

En PowerShell:

```powershell
& "C:\xampp\php\php.exe" herramientas\diagnostico.php
```

Te va a listar, una por una, las claves, la base de datos y las tablas,
diciendo qué falta y qué comando escribir. Repite hasta que salga
**0 fallos**.

### 6. La base de datos

Hay dos casos. Elige el tuyo:

**a) Ya tienes la base `ruta_nomada` de antes y quieres conservar tus
usuarios y tus planes.** Ponla al día con un solo comando:

```bash
/c/xampp/mysql/bin/mysql.exe -u root ruta_nomada -e "source basedatos/actualizar_bd.sql"
```

En PowerShell, el mismo comando se escribe así:

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root ruta_nomada -e "source basedatos/actualizar_bd.sql"
```

Añade lo que falta y **no borra nada**. Se puede ejecutar dos veces sin
problema. Al terminar imprime `5` y `3`: son las columnas y las tablas
que añadió.

**b) Empiezas de cero (o el diagnóstico dice que faltan tablas):**

```bash
/c/xampp/mysql/bin/mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS ruta_nomada CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
/c/xampp/mysql/bin/mysql.exe -u root ruta_nomada -e "source basedatos/instalar.sql"
```

En PowerShell:

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS ruta_nomada CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
& "C:\xampp\mysql\bin\mysql.exe" -u root ruta_nomada -e "source basedatos/instalar.sql"
```

`instalar.sql` crea **todo**: no necesitas ningún `migrate_*.sql` ni
`actualizar_bd.sql` (ésos son sólo para bases que ya existían).

> **Por qué `-e "source ..."` y no `< archivo`:** la terminal de VS Code
> en Windows suele ser PowerShell, y PowerShell no admite el operador
> `<`. Con `source` funciona igual en PowerShell, en CMD y en Git Bash.

Listo. Abre <http://localhost/ruta-nomada/>

---

## Parte 2 — El día a día

Cada vez que quieras la última versión, **un solo comando**:

```bash
cd /c/xampp/htdocs/ruta-nomada
git pull
```

Eso es todo. Se acabaron los `.zip`.

Si quieres ver antes qué va a cambiar, sin bajar nada todavía:

```bash
git fetch
git log --oneline HEAD..origin/main
```

### Dos cosas que conviene recordar

**Tus claves no se tocan nunca.** Git tiene orden de ignorar los
`includes/*_config.php`, así que ningún `git pull` los va a pisar. Los
configuras una vez y te olvidas.

**La base de datos no viaja con Git.** Si un cambio añade columnas o
tablas nuevas, hay que aplicarlo aparte. Para saberlo, después de cada
`git pull` ejecuta:

```bash
php herramientas/diagnostico.php
```

Si dice que faltan columnas, te dirá exactamente qué comando escribir.

---

## Si algo sale mal

### «Your local changes would be overwritten by merge»

Has modificado un archivo que también cambió en el repositorio. Git no
quiere borrar tu trabajo sin permiso.

Para ver qué has tocado:

```bash
git status
```

Si **no** te importa perder tus cambios locales (lo más habitual: los
tocaste sin querer):

```bash
git checkout -- .
git pull
```

Si **sí** quieres conservarlos, guárdalos aparte primero:

```bash
git stash
git pull
git stash pop
```

### «CONFLICT (content): Merge conflict in ...»

Los dos habéis cambiado las mismas líneas. Lo más rápido, si tus
cambios no importan, es quedarte con la versión del repositorio:

```bash
git checkout --theirs .
git add -A
git commit -m "resolver conflicto"
```

Si te lías, el botón de emergencia está más abajo.

### «fatal: not a git repository»

No estás dentro de la carpeta. Vuelve a entrar:

```bash
cd /c/xampp/htdocs/ruta-nomada
```

### La página sale en blanco después de un `git pull`

Casi siempre es la base de datos, que se quedó atrás:

```bash
php herramientas/diagnostico.php
```

### Las rutas del mapa salen punteadas

El punteado es la línea de reserva: significa que la aplicación no pudo
conseguir la ruta real por carretera y unió los lugares con una recta.
Las tres causas, en orden de probabilidad:

1. **Faltan las tablas `tramo_cache` y `ruta_uso`.** Estuvieron sueltas
   en `migrate_rutas.sql` un tiempo y no entraban en la instalación.
   Se arregla sin perder datos, y lo más fácil es **doble clic en
   `herramientas/actualizar.bat`**. Si prefieres la terminal:

   ```bash
   /c/xampp/mysql/bin/mysql.exe -u root ruta_nomada -e "source basedatos/actualizar_bd.sql"
   ```

   En PowerShell:

   ```powershell
   & "C:\xampp\mysql\bin\mysql.exe" -u root ruta_nomada -e "source basedatos/actualizar_bd.sql"
   ```

2. **La Routes API no está habilitada** en el proyecto de Google Cloud
   de esa clave.

3. **PHP no puede salir a internet**, casi siempre porque a XAMPP recién
   instalado le faltan los certificados raíz de cURL.

El diagnóstico distingue las tres y dice cuál es:

```bash
php herramientas/diagnostico.php
```

### No me deja elegir un lugar / el buscador de destino no sugiere nada

Eso es la **Maps JavaScript API + Places API**, que corren en el
navegador, así que el fallo no aparece en la terminal. Abre la página,
pulsa **F12 → Console** y mira si sale algo en rojo terminado en
`MapError`. El diagnóstico explica qué significa cada uno en su punto 7.

Lo más habitual: pegar la clave dentro de `includes/maps_config.sample.php`
en lugar de crear `includes/maps_config.php`. La aplicación **nunca** lee
los `.sample`: son sólo plantillas. El diagnóstico también detecta ese
despiste.

### Botón de emergencia

Tirar TODO lo local y quedarte exactamente con lo que hay publicado.
**Borra cualquier cambio tuyo** (tus claves no, ésas están ignoradas):

```bash
git fetch origin
git reset --hard origin/main
```

---

## Chuleta

| Qué quieres | Qué escribes |
|---|---|
| Bajar la última versión | `git pull` |
| Ver si hay algo nuevo | `git fetch` y luego `git log --oneline HEAD..origin/main` |
| Ver si has tocado algo | `git status` |
| Ver los últimos cambios | `git log --oneline -10` |
| Comprobar la instalación | `php herramientas/diagnostico.php` |
| Empezar de cero | `git reset --hard origin/main` |

---

## Preguntas rápidas

**¿Necesito cuenta de GitHub?**
No. El repositorio es público y `git pull` sólo lee.

**¿Puedo seguir usando la carpeta vieja?**
No: no es un repositorio de Git, es un `.zip` descomprimido. Por eso
hace falta el `git clone` una vez.

**¿Se me borran mis planes de viaje al hacer `git pull`?**
No. Están en la base de datos MySQL, que Git ni toca.

**¿Y si rompo algo?**
Nada que no arregle el botón de emergencia. El código está a salvo en
GitHub; en tu máquina no hay nada irrecuperable salvo tus claves, y
ésas ya las tienes guardadas.
