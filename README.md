# Ruta Nómada

Aplicación web para planear viajes: buscas un destino, creas un plan, repartes
los lugares por días, llevas el presupuesto e invitas a quien te acompaña.
Inspirada en Wanderlog.

Proyecto Integrador II — Universidad Tecnológica.

**Stack:** PHP 8.2 · MariaDB/MySQL · JavaScript sin framework · Google Maps
Platform · Google Gemini.

---

## Instalación en 5 pasos

Necesitas **XAMPP** (o cualquier Apache + PHP 8.2 + MySQL).

### 1. Clona el repositorio dentro de `htdocs`

```bash
cd C:/xampp/htdocs
git clone https://github.com/sunShine11074/ruta-nomada.git ruta-nomada
```

Actualizar después es un `git pull`, ya no hace falta pasarse un .zip.

### 2. Crea la base de datos

En **phpMyAdmin** → pestaña *SQL*:

```sql
CREATE DATABASE ruta_nomada
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Importa el esquema

phpMyAdmin → base `ruta_nomada` → *Importar* → elige
**`basedatos/instalar.sql`** → *Continuar*.

Ese archivo crea las 15 tablas, el procedimiento almacenado y unos destinos de
ejemplo. **Es el único que necesitas**: los `migrate_*.sql` son el historial de
cómo fue creciendo el esquema y no hay que ejecutarlos por separado.

### 4. Configura tus claves

Cada servicio externo tiene su archivo de configuración. Copia los `.sample`,
quítales `.sample` al nombre y pon tus propias claves:

| Copia este archivo | y guárdalo como | ¿para qué sirve? |
|---|---|---|
| `includes/maps_config.sample.php` | `includes/maps_config.php` | El mapa y la búsqueda de lugares |
| `includes/ai_config.sample.php` | `includes/ai_config.php` | El asistente de IA |

Cada `.sample` explica dónde sacar la clave. **La app arranca sin ellas**: sin
la de Maps no verás el mapa, y sin la de Gemini el asistente responde en modo
demostración. Todo lo demás funciona igual.

> Los archivos de configuración están en `.gitignore` **a propósito**. Nunca
> subas tus claves al repositorio, y nunca las mandes dentro de un .zip.

### 5. Abre la app

```
http://localhost/ruta-nomada/login.php
```

Regístrate y listo. La base viene sin usuarios: cada quien crea el suyo.

### ¿Algo no jala?

```bash
php herramientas/ai_test.php
```

Comprueba la clave de Gemini, te dice qué modelos acepta y hace una llamada de
prueba.

---

## Estructura

| Carpeta / archivo | Qué es |
|---|---|
| `login.php`, `register.php`, `profile.php` | Cuentas y sesión |
| `inicio.php`, `resultados.php`, `destino.php`, `guias.php` | Descubrir destinos |
| `crear_plan.php`, `mis_planes.php` | Crear y listar viajes |
| `plan.php` + `plan_template.html` + `js/plan_logic.js` | **La vista del plan**: itinerario, presupuesto, mapa y explorador |
| `api/` | Endpoints JSON (guardado del plan, asistente, invitaciones, reacciones) |
| `includes/` | Guardián de sesión, acceso a datos y librerías propias |
| `libs/` | Dependencias copiadas a mano (no usamos Composer) |
| `basedatos/` | `instalar.sql` y el historial de migraciones |
| `herramientas/` | Diagnóstico desde la terminal |

### Dos cosas que parecen menores y no lo son

**`libs/` sí se versiona.** `plan.php` carga `libs/dc/support.js` desde el
navegador; sin ese archivo la vista del plan sale en blanco. Y `libs/PHPMailer`
es la librería de correo. Como no hay `composer install` que las reponga,
tienen que viajar dentro del repo.

**La columna `emoji` va en `utf8mb4_bin`.** Con la colación normal, MySQL
considera iguales a todos los emojis y `'🐙' = '🌮'` devuelve verdadero, así que
las reacciones se fundirían en una sola. Ya viene configurada en `instalar.sql`.

---

## Requisitos del servidor

- PHP **8.2+** con `pdo_mysql`, `curl` y `mbstring`
- MySQL 5.7+ / MariaDB 10.4+
- Permiso de escritura en `cache/`
- **Salida HTTPS** hacia `generativelanguage.googleapis.com`,
  `es.wikipedia.org` y `api.countrystatecity.in`

Ese último punto es el que suelen bloquear los hostings gratuitos: sin él, el
asistente de IA, las descripciones de los lugares y los selectores de
país/estado dejan de funcionar.

---

## Créditos y avisos

Los datos de lugares, calificaciones, reseñas, horarios y fotos provienen de
**Google Maps Platform** y se muestran en vivo; el proyecto no los almacena.
Las descripciones que vienen de **Wikipedia** están bajo CC BY-SA y enlazan al
artículo original.
