# Tipografías del proyecto

## Mona Sans

- **Archivo:** `MonaSans.woff2`
- **Versión:** 2.0.27
- **Origen:** <https://github.com/github/mona-sans/releases/tag/v2.0.27>
- **Licencia:** SIL Open Font License 1.1
- **Copyright:** © GitHub, Inc.

Es la variante **variable** del paquete oficial, que allí se llama
`fonts/webfonts/variable/MonaSansVF[opsz,wght].woff2`. Se renombró a
`MonaSans.woff2` porque los corchetes hay que escaparlos en una URL y no
aportan nada.

Trae dos ejes:

| eje    | rango     | para qué |
|--------|-----------|----------|
| `wght` | 200 – 900 | todos los pesos, de ExtraLight a Black |
| `opsz` | óptico    | ajusta el trazo según el cuerpo del texto |

Se eligió frente a las cuatro estáticas (Regular, Medium, SemiBold,
Bold) porque pesa **137 KB en una petición** en lugar de 242 KB en
cuatro, y encima da todos los pesos en vez de sólo esos cuatro.

Se declara en `mis_planes.css` con `font-weight: 200 900`, que es lo que
le dice al navegador que el peso es un rango continuo y no un valor
suelto.

### Licencia

El `.zip` de la *release* trae sólo la carpeta `fonts/`, sin el texto de
la licencia, y la OFL 1.1 exige que ese texto **acompañe a la fuente**
allí donde se distribuya. Como este repositorio es público, el archivo
se añadió a mano:

- **`OFL.txt`** — copia literal del `LICENSE` de
  <https://github.com/github/mona-sans/blob/main/LICENSE>

No lo borres ni lo muevas de esta carpeta: mientras `MonaSans.woff2`
viaje en el repositorio, ese texto tiene que viajar a su lado.

## Poppins

No vive aquí: se carga desde Google Fonts en el `<head>` de cada página.
En `mis_planes.php` se usa **sólo** en los dos botones del panel derecho
(«Editar plan» y «Eliminar plan»), tal como pide el diseño.
