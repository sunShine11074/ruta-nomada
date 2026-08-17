# Créditos de los iconos

Aquí van los iconos de **Flaticon** y de cualquier otra fuente que pida
atribución. Los de Font Awesome NO van aquí: viven en línea dentro del HTML y su
licencia está documentada en `includes/iconos_planes.php`.

---

## Por qué existe este archivo

La licencia gratuita de Flaticon **obliga a dar crédito al autor**. No es
opcional y no basta con nombrarlo en el código: tiene que verse en algún sitio al
que el usuario pueda llegar. En Ruta Nómada ese sitio es
[`creditos.php`](../../creditos.php), que lee esta misma tabla.

Si no se atribuye, el uso deja de estar cubierto por la licencia gratuita.

---

## Cómo añadir uno nuevo

1. Descarga el PNG de Flaticon y **apunta la URL de la ficha del icono**, no la
   de descarga. Es la que lleva el nombre del autor.
2. Redimensiona a los tres tamaños que usa el proyecto y déjalos aquí:

   ```bash
   python -c "from PIL import Image; im=Image.open('descarga.png').convert('RGBA'); [im.resize((n,n), Image.LANCZOS).save('img/iconos/nombre%s.png'%s, optimize=True) for n,s in ((18,''),(36,'@2x'),(54,'@3x'))]"
   ```

   Los tres hacen falta: a 18 px un PNG sin versión `@2x` se ve borroso en
   cualquier portátil moderno. El original de 512 pesa 30 veces más y no aporta.

3. Añade la fila a la tabla de abajo. `creditos.php` la lee de aquí, así que con
   eso queda publicado.

---

## Iconos usados

| Archivo | Qué es | Autor | Fuente | Dónde se usa |
|---|---|---|---|---|
| `idea.png` | bombilla con cerebro | *(pendiente: ver nota)* | [Flaticon](https://www.flaticon.com/) | «Saber antes de ir», en la ficha del sitio |

> ⚠️ **Falta el nombre del autor de `idea.png`.** Se descargó de Flaticon pero no
> se guardó la URL de la ficha, y el nombre del autor no se puede deducir del
> archivo. **Hay que rellenarlo antes de publicar el proyecto**: búscalo en tu
> historial de descargas de Flaticon, abre la ficha del icono y copia el «Icon
> made by …» junto con el enlace.
>
> Mientras esté sin rellenar, la atribución está incompleta y el uso no cumple la
> licencia gratuita.
