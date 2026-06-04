# Ruta Nómada

Aplicación web para planificar viajes: descubrir destinos, cotizar rutas y repartir gastos en grupo.
Proyecto del curso **Proyecto Integrador II**.

## Tecnologías

- **PHP** (vistas en `includes/`, punto de entrada `index.php`)
- **JavaScript / JSX** (`js/`)
- **CSS** con sistema de tokens de diseño (`css/`)

## Estructura

```
index.php              Punto de entrada
includes/              Vistas y componentes en PHP (pantallas, auth, sidebar, etc.)
js/                    Lógica de la app (app.js, *.jsx)
css/                   Estilos y tokens de diseño
uploads/               Recursos de diseño (PDFs, design system)
screens_ref/           Capturas de referencia de pantallas
DOCUMENTACION.md       Documentación del proyecto
```

## Cómo ejecutar en local

Necesitas PHP instalado. Desde la carpeta del proyecto:

```bash
php -S localhost:8000
```

Luego abre http://localhost:8000 en el navegador.

## Trabajo en equipo

Antes de empezar a trabajar, **siempre** actualiza tu copia:

```bash
git pull
```

Al terminar un cambio:

```bash
git add .
git commit -m "Describe tu cambio"
git push
```

Para evitar conflictos, coordínense para no editar el mismo archivo al mismo tiempo
y hagan `pull` con frecuencia.
