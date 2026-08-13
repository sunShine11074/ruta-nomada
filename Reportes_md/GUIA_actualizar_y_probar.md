# Guía para ponerte al día y probar lo nuevo

**Para:** quien ya tiene el proyecto clonado y funcionando.
**Tiempo:** unos 10 minutos de puesta al día y otros 15 de pruebas.

Si vas a instalar desde cero, esta guía **no** es la tuya: usa el `README.md`,
que importa `instalar.sql` y ya lo trae todo. Esto es para no perder tus
usuarios ni tus planes.

---

## Parte 1 · Ponerte al día (4 pasos)

### Paso 1 · Traer los cambios

Abre la terminal **dentro de la carpeta del proyecto** y:

```bash
git pull
```

**Si te dice `You have not concluded your merge (MERGE_HEAD exists)`**, es que
un `pull` anterior se quedó a medias. Termínalo y vuelve a intentar:

```bash
git commit --no-edit
```

**Si te dice `Please commit your changes or stash them`**, tienes cambios sin
guardar que chocan. Guárdalos primero:

```bash
git add -A && git commit -m "mis cambios"
```

**Si salen conflictos** (`CONFLICT (content): ...`), no los resuelvas a ciegas:
avísale a quien tocó ese archivo. Un conflicto mal resuelto borra el trabajo de
alguien sin que se note.

### Paso 2 · La clave de Pexels

Es la que da las fotos de portada de los viajes. Ramón te la pasa por privado.

```bash
copy includes\pexels_config.sample.php includes\pexels_config.php
```

Abre el archivo **nuevo** —el que NO dice `.sample`— y cambia esta línea:

```php
'api_key' => 'PON_AQUI_TU_CLAVE_DE_PEXELS',
```

por la clave que te pasaron, entre comillas.

> ⚠️ **Los dos errores de siempre.**
>
> 1. **Pegar la clave dentro del `.sample`.** La aplicación **nunca** lee los
>    `.sample`: son sólo plantillas. Tiene que existir el archivo sin esa
>    palabra en el nombre.
> 2. **Copiar la clave con un espacio o un salto de línea al final.** Pexels
>    responde `401` y parece que la clave está mal cuando en realidad sobra un
>    carácter invisible.

> 🔒 **Esta clave nunca se sube al repositorio.** `includes/pexels_config.php`
> está en `.gitignore` a propósito, junto con las otras cuatro. Y **nunca
> mandes el proyecto en un `.zip`**: el `.gitignore` mira nombres de archivo,
> **no mira dentro de un comprimido**, así que un zip del proyecto se lleva las
> cinco claves aunque estén ignoradas sueltas. Para compartir el proyecto se
> clona el repositorio.
>
> Si alguna vez la clave se te escapa (un zip, una captura, un mensaje en un
> grupo), dilo. Se puede regenerar en pexels.com/api en dos minutos; lo que no
> se puede es des-publicarla.

**¿Te faltan otras claves?** El paso 4 te dirá cuáles. Las cinco son:
`maps_config.php`, `ai_config.php`, `pexels_config.php`, `geo_config.php` y
`mail_config.php`. Cada `.sample` explica de dónde sale la suya.

### Paso 3 · Actualizar la base de datos

**No importes `instalar.sql`**: ese archivo borra y vuelve a crear las tablas, y
te llevarías por delante tus usuarios y tus planes. El que suma sin borrar es
otro:

```bash
mysql -u root ruta_nomada -e "source basedatos/actualizar_bd.sql"
```

Si `mysql` no se reconoce como comando, usa la ruta completa de XAMPP:

```bash
& "C:\xampp\mysql\bin\mysql.exe" -u root ruta_nomada -e "source basedatos/actualizar_bd.sql"
```

*(Se usa `-e "source ..."` y no `< archivo` porque PowerShell **no admite** el
operador `<`. Así funciona igual en PowerShell, en CMD y en Git Bash.)*

Al terminar imprime una línea de comprobación. Tiene que decir:

```
columnas_nuevas_en_plan_items (deben ser 5)      5
tablas_nuevas (deben ser 3)                      3
columnas_nuevas_en_plan_invitaciones (deben ser 3)  3
```

**Es seguro ejecutarlo dos veces.** Todo va con `IF NOT EXISTS`: lo que ya esté
se queda como está, y no vacía ninguna tabla.

Prefieres phpMyAdmin? Base `ruta_nomada` → pestaña *Importar* → elige
`basedatos/actualizar_bd.sql` → *Continuar*.

### Paso 4 · Comprobar que quedó todo

```bash
php herramientas/diagnostico.php
```

Revisa PHP y sus extensiones, los cinco archivos de configuración, la base con
todas sus tablas, columnas y rutinas, la salida a internet y una llamada de
prueba a cada servicio. **No toca nada: sólo lee.**

Lo único que importa de la última línea es que **los fallos sean 0**:

```
  24 correctos · 0 avisos · 0 fallos
```

Los **avisos** `[!]` no son fallos: significan «esto no lo puedo probar porque
falta una clave». Si decidiste no poner la de correo, verás un aviso ahí y la
app funciona igual —sólo que las invitaciones no salen por correo y hay que
pasar el enlace a mano—. Cada `[X]`, en cambio, sí hay que atenderlo, y trae
debajo el comando que lo arregla.

Números clave, por si quieres compararlos a ojo: **21 tablas** y **16 rutinas**
(5 funciones, 6 procedimientos y 5 disparadores).

---

## Parte 2 · Probar lo nuevo

Ordenadas de menos a más lío. Las dos últimas necesitan una segunda cuenta.

> **Abre siempre por `http://localhost/...`**, no por la IP de la red
> (`http://192.168...`). Copiar al portapapeles sólo funciona en «contexto
> seguro», y `localhost` lo es; una IP de red, no.

### 1 · Un viaje nuevo nace con foto y con nombre decente

1. Barra de arriba → **Crear plan de viaje**.
2. Pon **Oaxaca** como destino y unas fechas cualesquiera. Crea.

| Qué mirar | Qué tiene que pasar |
|---|---|
| El nombre del viaje | **«Viaje a Oaxaca»** — no «Nuestro viaje a Oaxaca» |
| La imagen de cabecera | Una **foto real de Oaxaca**, no un paisaje al azar |

Si sale una imagen genérica o gris, es la clave de Pexels. Vuelve al paso 4.

> Curiosidad útil: la búsqueda no manda el destino tal cual. «Guanajuato, Gto.»
> devolvía la catedral de **León**, y «La Paz» a secas devolvía gente haciendo
> el signo de la paz. Se corta lo que va después de la coma y se añade la
> palabra «ciudad». Si pruebas con un destino raro y sale algo absurdo, ese es
> el motivo.

### 2 · Cambiar la foto desde la web

1. Dentro del viaje, pasa el ratón por la imagen de cabecera → **lápiz**.
2. Pestaña **Buscar en la web** → escribe «playa» → lupa.

| Qué mirar | Qué tiene que pasar |
|---|---|
| La rejilla | 12 fotos en 3 columnas |
| Pulsar una foto | Se marca con un **borde azul** y aparece el botón **Selecciona**. La portada **todavía no cambia** |
| Pulsar **Selecciona** | Ahora sí cambia la portada, y la ventana se cierra |
| El iconito de arriba a la izquierda de cada foto | Abre el original en Pexels (lo exige su licencia) |
| **Esc** o la **✕** | Cierran |
| Pulsar **fuera** de la ventana | **No** cierra — a propósito, para no perder lo que estabas eligiendo |

### 3 · Subir una foto tuya

Misma ventana, pestaña **Tus fotos** → **Sube tus fotos**.

| Qué mirar | Qué tiene que pasar |
|---|---|
| Una foto grande de móvil (3–8 MB) | Sube **rápido**: el navegador la reduce a 1600 px antes de mandarla. Una de 4000×3000 llega pesando unos 66 KB |
| La galería | Aparece la foto; el botón pasa a decir «Subir más fotos» |
| La ✕ de la esquina de una foto | La quita de la galería |
| Renombrar un `.txt` a `.jpg` y subirlo | **Rechazado.** Se mira el contenido del archivo, no su extensión |

Si falla con un error al subir, mira que exista `img/portadas/`. El paso 4 lo
comprueba.

### 4 · Invitar compañeros de viaje

El botón es el **círculo de línea discontinua** con la personita y el `+`, junto
a los avatares de la cabecera del viaje.

> **Sólo lo ve quien creó el viaje.** Si entraste a un viaje ajeno como editor,
> el botón no aparece: es lo correcto, no un fallo.

| Qué mirar | Qué tiene que pasar |
|---|---|
| Al abrirse | Sale un enlace ya listo, sin tener que pulsar nada |
| **Cerrar y volver a abrir 5 veces** | **El mismo enlace las cinco veces** (no se crea uno nuevo cada vez) |
| **Copiar enlace** | Pasa a **«Copiado»** con fondo azul claro, **sin cambiar de tamaño**, y vuelve solo a los 2 s |
| Pegar en el bloc de notas | Sale la dirección completa, terminada en `plan_invitacion.php?token=...` |
| Escribir un correo y pulsar **Intro** | Sale un aviso debajo. Si dice *«la invitación quedó creada, pero el correo no salió»*, es que te falta `mail_config.php` — **es normal y el enlace sigue sirviendo** |
| Un correo con dominio inventado (`x@dominioquenoexiste123.com`) | Lo rechaza antes de intentar mandarlo |
| **Gestiona tus compañeros de viaje** | Segunda pantalla, con la lista |
| La **flecha ←** o **Esc** | Vuelven a la primera pantalla; el segundo **Esc** cierra |

### 5 · El enlace sirve para VARIAS personas ⭐

**Esta es la prueba importante**, porque es justo lo que fallaba antes: el
enlace se gastaba con la primera persona y la segunda recibía «inválido, ya fue
usado».

Necesitas dos cuentas. Lo más rápido:

1. Cuenta A: crea un viaje, copia el enlace.
2. Abre una **ventana de incógnito** (Ctrl+Shift+N) y pega el enlace.
3. Regístrate ahí con otro correo → entras al viaje como editor.
4. **Cierra el incógnito, abre otro** y repite con un **tercer** correo.

| Qué mirar | Qué tiene que pasar |
|---|---|
| La segunda persona | Entra |
| **La tercera** | **También entra** ← esto es lo que se arregló |
| Volver a A y recargar | Salen los avatares de las tres en la cabecera |
| Que alguien ya dentro reabra el enlace | Va al viaje y **no gasta** un uso de nadie |

### 6 · Quitar a alguien

Con A, en **Gestiona tus compañeros de viaje**, pulsa la ✕ de una fila.

| Qué mirar | Qué tiene que pasar |
|---|---|
| La lista | La persona desaparece **al momento**, sin recargar |
| Los avatares de la cabecera | Bajan uno, también al momento |
| La fila de quien creó el viaje | **No tiene ✕** — dejaría el viaje sin dueño |
| Esa persona, al abrir el viaje | La manda a «Mis planes» |
| Si le habías repartido un gasto | **Su parte sigue ahí**, etiquetada como «Alguien que ya no está». Borrarla cambiaría en silencio el saldo de todos |

### 7 · Lo de la sesión (rápido)

| Qué probar | Qué tiene que pasar |
|---|---|
| Fallar la contraseña 5 veces seguidas | Te bloquea un rato. Espera o borra tu fila en la tabla `intentos_login` |
| Registrarte con `@dominioinventado123.com` | Lo rechaza: se comprueba en el DNS que el dominio pueda recibir correo |

> **Ojo con una cosa que cambió hace poco.** El *inicio de sesión* ahora sólo
> acepta correos de **Gmail, Outlook y Yahoo**. Si tu cuenta de prueba es de
> otro dominio —Hotmail, iCloud, el de la escuela— **no vas a poder entrar**,
> aunque el registro sí te haya dejado crearla. No es un fallo tuyo: las dos
> puertas piden cosas distintas y está pendiente de decidir cuál se queda.

---

## Si algo no sale

| Lo que ves | Qué pasa | Qué hacer |
|---|---|---|
| La ventana del plan sale **en blanco** | Falta `libs/dc/support.js` o el `pull` quedó a medias | `git status` y repite el paso 1 |
| «Buscar en la web» no devuelve nada | La clave de Pexels | Paso 2, y comprueba con el paso 4 |
| Los viajes nuevos nacen **sin foto** | Lo mismo | Paso 2 |
| **«Sesión inválida. Recarga la página»** | El token de la página caducó | F5. Si sigue, cierra sesión y vuelve a entrar |
| Al invitar: **«No se pudo preparar el enlace»** | Tu base no tiene las columnas nuevas | Paso 3 |
| La **✕** de invitar no aparece | No eres quien creó el viaje | Es lo correcto |
| «Copiar enlace» no copia | Abriste por IP en vez de `localhost` | Abre por `http://localhost/...` |
| El mapa sale gris | La clave de Maps | F12 → *Console*; el paso 4 explica cada error |
| Cualquier otra cosa | — | `php herramientas/diagnostico.php` y manda la salida entera |

---

## Lo que hay detrás, por si te toca tocarlo

| Función | Dónde vive |
|---|---|
| Fotos de portada (Pexels) | `includes/pexels_lib.php`, `api/imagenes.php` |
| Subir fotos propias | `api/fotos.php`, tabla `usuario_fotos` |
| Invitar y el enlace | `includes/plan_invite_lib.php`, `api/plan_invitar.php`, `plan_invitacion.php` |
| Quitar compañeros | `api/plan_miembros.php` |
| Las dos ventanas | `plan_template.html` + `js/plan_logic.js` |

Los porqués, con las medidas sacadas de los frames y las trampas que costaron
tiempo, están en `Reportes_md/PLAN_cambiar_foto.md` y
`Reportes_md/PLAN_invitar.md`.
