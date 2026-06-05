# 🧭 Guía para colaborar en *Ruta Nómada*

¡Bienvenido al equipo! Esta guía es para quienes **nunca han usado GitHub**.
Está escrita paso a paso, sin dar nada por sentado. Léela con calma y, si algo no
sale, avísale al equipo. 🙂

---

## 📌 ¿Qué es esto y por qué lo usamos?

- **Git** es un programa que guarda el historial de cambios de un proyecto. Es como
  un "control de versiones": puedes regresar a cómo estaba el código ayer si algo
  se rompe.
- **GitHub** es una página web donde guardamos ese proyecto en internet para que
  **todo el equipo trabaje sobre la misma copia** sin pasarse archivos por WhatsApp.
- **Repositorio (o "repo")** es la carpeta del proyecto que vive en GitHub. El nuestro
  se llama **`ruta-nomada`**.

La idea es simple:

> 1. Bajas la versión más reciente del proyecto. → 2. Haces tus cambios.
> 3. Subes tus cambios para que los demás los vean.

---

## ✅ Paso 0: Lo que necesitas instalar (una sola vez)

### 1. Crear una cuenta de GitHub
- Entra a **https://github.com** y regístrate (es gratis).
- Dile tu **nombre de usuario** a quien administra el proyecto para que te **invite**
  como colaborador. Sin esa invitación no podrás subir cambios.
- Revisa tu correo y **acepta la invitación**.

### 2. Instalar Git
- Descárgalo de **https://git-scm.com/downloads** e instálalo.
- Acepta todas las opciones que vienen por defecto (dale siempre "Next").

### 3. Tener un editor de código
Usa el que el equipo haya decidido. Cualquiera de estos sirve y trae Git integrado:
- **Google Antigravity**
- **Visual Studio Code** (https://code.visualstudio.com)

### 4. Decirle a Git quién eres (una sola vez)
Abre la terminal (en Windows: *PowerShell*; en Mac: *Terminal*) y escribe estos dos
comandos, cambiando los datos por los tuyos:

```bash
git config --global user.name "Tu Nombre"
git config --global user.email "tu-correo@ejemplo.com"
```

> 💡 Usa el **mismo correo** con el que te registraste en GitHub.

---

## ⬇️ Paso 1: Descargar el proyecto (clonar)

"Clonar" = bajar una copia del proyecto desde GitHub a tu computadora.
**Esto se hace solo la primera vez.**

1. Entra al repo en GitHub: `https://github.com/USUARIO-DEL-EQUIPO/ruta-nomada`
   (te pasarán el enlace exacto).
2. Haz clic en el botón verde **`< > Code`** y copia la URL que aparece (la que
   empieza con `https://`).
3. Abre la terminal, colócate donde quieras guardar el proyecto (por ejemplo el
   Escritorio) y escribe:

```bash
git clone https://github.com/USUARIO-DEL-EQUIPO/ruta-nomada.git
```

Esto crea una carpeta `ruta-nomada` con todo el proyecto. ¡Listo!

> **Desde el editor:** en Antigravity o VS Code también puedes usar la opción
> **"Clone Repository"** y pegar esa misma URL, sin usar la terminal.

---

## 🔄 Paso 2: La rutina de cada día (¡el corazón de todo!)

Cada vez que te sientes a trabajar, sigue **siempre** estos 4 pasos en orden:

### 1) Antes de empezar — baja lo último
```bash
git pull
```
Esto trae los cambios que tus compañeros subieron. **Hazlo siempre primero** para
no trabajar sobre una versión vieja.

### 2) Haz tus cambios
Edita los archivos en tu editor como lo harías normalmente. Guarda con `Ctrl + S`.

### 3) Prepara y guarda tus cambios (commit)
```bash
git add .
git commit -m "Describe brevemente lo que hiciste"
```
- `git add .` selecciona **todos** tus cambios.
- `git commit -m "..."` los guarda con una nota. Escribe un mensaje claro, por ejemplo:
  `"Agregué la pantalla de comunidad"` o `"Corregí los colores del botón de login"`.

### 4) Sube tus cambios a GitHub
```bash
git push
```
Listo: ahora tus compañeros pueden bajar tu trabajo con `git pull`.

> 🟢 **Resumen para pegar en la pared:**
> `git pull` → trabajar → `git add .` → `git commit -m "..."` → `git push`

---

## 🖱️ ¿Prefieres no usar la terminal?

Tanto **Antigravity** como **VS Code** tienen un panel de **Source Control**
(icono de tres puntos conectados con líneas, a la izquierda). Desde ahí puedes:

- **Pull** (bajar cambios): icono de sincronizar / flecha hacia abajo.
- **Escribir el mensaje** del commit en la cajita de texto y dar clic en **Commit**.
- **Push** (subir cambios): botón "Sync Changes" o la flecha hacia arriba.

Hace exactamente lo mismo que los comandos, pero con botones.

---

## ⚠️ Reglas de oro para no romper nada

1. **Siempre `git pull` antes de empezar.** Evita el 90% de los problemas.
2. **No editen el mismo archivo dos personas a la vez.** Coordínense por el chat
   quién toca qué.
3. **Haz commits pequeños y seguidos** con mensajes claros, en vez de uno gigante
   al final del día.
4. **Nunca subas contraseñas ni datos personales** al repositorio.
5. Si tienes dudas, **pregunta antes de hacer `push`**. Es más fácil prevenir que
   arreglar.

---

## 🆘 Problemas comunes

**"Me pide usuario y contraseña al hacer push."**
Inicia sesión en la ventana del navegador que se abre. Si te pide una contraseña en
la terminal, normalmente necesitas un *token* o iniciar sesión con el editor; pídele
ayuda al equipo.

**"Me apareció la palabra `CONFLICT` (conflicto).”**
Significa que tú y un compañero editaron lo mismo. **No entres en pánico ni borres
nada.** Avisa al equipo para resolverlo juntos.

**"Hice cambios pero no quiero subirlos / quiero deshacerlos."**
Avisa antes de usar comandos de borrado. Para descartar cambios no guardados de un
archivo: pregunta primero, porque esto **sí borra** tu trabajo.

**"No me deja hacer push: rechazado (rejected)."**
Casi siempre es porque alguien subió algo nuevo. Haz `git pull` y luego `git push`
otra vez.

---

## 🚀 Para ejecutar el proyecto en tu computadora

Necesitas **PHP** instalado. Dentro de la carpeta del proyecto, en la terminal:

```bash
php -S localhost:8000
```

Luego abre **http://localhost:8000** en tu navegador.

---

¿Dudas? Escríbele al equipo. Todos empezamos sin saber usar GitHub. 💪
