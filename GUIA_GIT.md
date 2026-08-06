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

Si `php` no se reconoce, usa la ruta completa:

```bash
/c/xampp/php/php.exe herramientas/diagnostico.php
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

Añade lo que falta y **no borra nada**. Se puede ejecutar dos veces sin
problema. Al terminar imprime `5` y `1`: son las columnas y la tabla
que añadió.

**b) Empiezas de cero (o el diagnóstico dice que faltan tablas):**

```bash
/c/xampp/mysql/bin/mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS ruta_nomada CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
/c/xampp/mysql/bin/mysql.exe -u root ruta_nomada -e "source basedatos/instalar.sql"
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
