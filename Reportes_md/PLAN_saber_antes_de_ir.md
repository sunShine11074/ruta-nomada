# Plan — «Saber antes de ir» en la ficha del sitio

Cuatro consejos prácticos sobre el lugar, con un icono de bombilla, entre los
datos de contacto y «Abrir en:». Frame: `screens_ref/F_ site (Acerca de).png`.

Lo que sigue es la investigación y una recomendación. **Hay un hallazgo que
cambia el diseño y que conviene leer antes que nada** (apartado 3).

---

## 1. Lo que pide el frame

Cuatro líneas, cada una con bombilla amarilla, con consejos **de ese sitio
concreto** — no genéricos. Los del frame:

> Visita Manzanilla para una excursión relajada pero deliciosa que incluye catas
> de vino en el camino.
> Prueba sus platos estrella como el chuletón de cerdo con salsa mole…
> Explora tanto las áreas de comedor interiores como exteriores…
> Prepárate para un servicio más lento durante las horas pico…

Eso es lenguaje de **reseñas resumidas**: mezcla ambiente, platos concretos y
avisos operativos. No sale de ningún campo estructurado.

> Nota: el texto del frame tiene erratas («com o», «sals mole», «las área»). Es
> texto de maqueta, no copia final.

---

## 2. Lo que ya hay montado, y que juega a favor

| Pieza | Dónde | Sirve para |
|---|---|---|
| **5 reseñas por sitio** | `js/plan_logic.js:2293` | la materia prima, y **ya vienen** en el mismo `getDetails` |
| Cascada de 3 niveles | `js/plan_logic.js:2306` | el patrón exacto a imitar |
| Interruptor de coste | `PLAN_ACERCA_GOOGLE` en `plan.php:92` | precedente para poder apagarlo |
| Gemini compartido | `aiGenerar()` en `includes/ai_lib.php` | la llamada ya existe |
| Caché en disco | `api/lugar_wiki.php:95` | patrón de caché ya escrito |

Las reseñas **no cuestan una llamada extra**: ya se piden y el propio código lo
dice en `plan_logic.js:2275`.

---

## 3. ⚠️ El hallazgo que cambia el diseño

### Google ya genera estos resúmenes… pero no nos sirven

Places API (New) tiene `generativeSummary` y `reviewSummary`, resúmenes hechos
con Gemini por el propio Google. Sería la solución ideal: sin coste de IA
nuestro, sin riesgo de inventar y con la atribución resuelta.

**Pero están limitados a inglés, y sólo en India y Estados Unidos.** Los sitios
de este proyecto están en México y la interfaz es en español. No sirven hoy.
Conviene volver a mirarlo dentro de unos meses: cuando lleguen a México y a
español, esta sección se resuelve con un campo más en `fetchFields`.

### Y mandar reseñas de Google a Gemini choca con los términos

Aquí está el problema serio, y afecta también a lo que ya está en marcha:

- Los términos de Maps **prohíben usar contenido de Google Maps para entrenar,
  probar, validar o afinar modelos de IA**.
- La capa **gratuita** de Gemini **entrena con los prompts** — está ya apuntado
  en este proyecto como riesgo conocido.

Juntando las dos: **mandar el texto de las reseñas a la clave gratuita de Gemini
es entregar contenido de Maps para entrenamiento.** No es una interpretación
retorcida; es la lectura directa de las dos cláusulas.

**Y esto no es sólo del futuro.** `api/plan_ai.php:48` ya manda el destino y
`aiPlanContexto($boot)` el itinerario entero —nombres de lugares que salieron de
Places— a esa misma clave gratuita. Si la lectura es correcta, **el asistente
que ya funciona está en la misma situación**. Merece una decisión aparte de esta
sección; aquí sólo se deja constancia de que se encontró.

Las salidas posibles:

1. **Pasar Gemini a capa de pago**, donde los prompts no se usan para entrenar.
   Resuelve el problema de raíz, también el del asistente.
2. **No mandar contenido de Google**: generar los consejos sólo con lo que
   escribe el usuario o con datos que no vengan de Places.
3. **Aceptar el riesgo** y dejarlo escrito. Es un proyecto de escuela sin
   usuarios reales; la decisión es tuya, pero que sea una decisión y no un
   descuido.

### Y no se puede guardar

Solo el `place_id` se puede almacenar indefinidamente; las coordenadas, 30 días.
Un consejo derivado de reseñas es contenido derivado de Maps, así que **no puede
ir a la base de datos**. Eso descarta una tabla de consejos y obliga a caché de
sesión.

---

## 4. Las cuatro vías, comparadas

| | Calidad | Coste | Términos | Trabajo |
|---|---|---|---|---|
| **A. Gemini sobre las 5 reseñas** | alta, concreta | 1 llamada por ficha abierta | ⚠️ ver apartado 3 | medio |
| **B. Gemini sólo con datos estructurados** | media, algo genérica | igual | ⚠️ sigue siendo contenido de Maps | medio |
| **C. Plantilla determinista** | baja pero honesta | **cero** | ✅ sin problema | bajo |
| **D. Campo de Google** | alta | incluido | ✅ resuelto | bajo… pero **no existe en español/México** |

---

## 5. Recomendación

**Una cascada de dos niveles, calcada de la que ya existe para «Acerca de»:**

```
1. Gemini sobre las 5 reseñas   → consejos concretos
2. Plantilla determinista        → el piso, siempre produce algo
```

Con tres condiciones:

- **Interruptor propio**, `PLAN_TIPS_IA`, igual que `PLAN_ACERCA_GOOGLE`. Con él
  apagado, el nivel 2 sostiene la sección entera y no se manda nada a Gemini.
  Eso permite **entregar la sección funcionando sin tocar los términos** mientras
  se decide lo del apartado 3.
- **Caché sólo en sesión**, por `place_id`, nunca en la base.
- **Procedencia visible**, como ya se hace con el «Acerca de»: si los consejos
  los escribió una IA, hay que decirlo. El frame no lo contempla y hay que
  añadirlo.

### Qué puede dar el nivel 2 sin IA

Más de lo que parece, con lo que ya trae `getDetails`:

- «Suele estar concurrido; la gente pasa unos 45 minutos aquí» ← el campo de
  duración típica que ya se pinta en la ficha
- «Cierra los lunes» ← `opening_hours`
- «Precio medio-alto» ← `price_level`
- «4,6 sobre 5.874 reseñas» ← ya se pinta arriba

Son consejos reales, no rellenos, y no dependen de nada externo.

---

## 6. El icono

El frame usa una bombilla amarilla. El que descargaste (`idea (1).png`,
Flaticon) **no llegó a `screens_ref/`** — sólo está en tu carpeta de descargas.

Dos problemas si se usa:

1. **Es PNG.** Todo el proyecto usa SVG en línea, con la caja de tinta medida
   (`includes/iconos_planes.php`). Un PNG a 16 px se ve borroso en pantallas
   normales y obliga a un `@2x`.
2. **Flaticon gratuito exige atribución** visible. El proyecto ya documenta con
   cuidado la licencia de Font Awesome (CC BY 4.0) y la de Wikipedia (CC BY-SA);
   añadir una tercera obligación por un icono de 16 px no compensa.

**Propuesta:** usar `lightbulb` de Font Awesome Free 7.3.1, que ya es la familia
del proyecto, teñido del amarillo del frame. Si prefieres el de Flaticon, hay
que pasarlo a SVG y añadir la atribución donde toque.

---

## 7. Lo que haría falta tocar

| Archivo | Qué |
|---|---|
| `js/plan_logic.js` | `_tipsDe()` con la cascada, junto a `_acercaDe()` (2306) |
| `plan_template.html` | la sección entre el contacto y «Abrir en:» |
| `api/plan_tips.php` (nuevo) | proxy a `aiGenerar()` con el prompt de los 4 consejos |
| `includes/iconos_planes.php` | la bombilla |
| `plan.php` | el interruptor `PLAN_TIPS_IA` |

Sin migración de base de datos — y no por ahorrar, sino porque **no se puede**
guardar (apartado 3).

---

## 8. Lo que necesito de ti

1. **La decisión del apartado 3.** Es la que ordena todo lo demás, y afecta al
   asistente que ya está funcionando.
2. Si quieres el icono de Flaticon o el de Font Awesome.
3. Si el nivel 2 solo (sin IA) te vale para entregar, que es la vía sin riesgos.
