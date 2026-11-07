# Diario de cambios — cafeteria-raiola.antonydev.tech

Documento vivo que registra todos los cambios que hacemos sobre la instalación WordPress del sitio de la cafetería. Es la fuente de contexto real y actualizada del proyecto.

## Reglas de mantenimiento

1. **Antes de tocar el sitio** (contenido, plugins, temas, ajustes, archivos, base de datos), registrar aquí la entrada del cambio.
2. Cada entrada incluye: fecha, qué se cambió, cómo se hizo (MCP/SSH/escritorio), estado antes → después, y verificación del resultado.
3. Mantener actualizada la tabla **Estado actual** cuando cambien versiones, plugins, contenido estructural o configuración.
4. Entradas en orden cronológico **inverso**: la más reciente arriba.
5. La sección **Estado actual** se verifica consultando el sitio por MCP, no de memoria.

## Estado actual (verificado por MCP: 2026-11-07)

### Sitio

| Elemento | Valor |
|---|---|
| Dominio | https://cafeteria-raiola.antonydev.tech |
| WordPress | 7.1.4 |
| PHP | 8.3.36 |
| Locale | es_ES |
| Entorno | production |
| Tema activo | Cafetería Raiola Child (hijo de Twenty Twenty-Five) |
| Conexión IA | Novamira v1.12.15 por MCP (OAuth), configurada en `opencode.json` |

### Plugins

| Plugin | Versión | Estado |
|---|---|---|
| Elementor | 4.3.6 | Activo |
| Novamira | 1.12.15 | Activo |
| AI Provider for Anthropic | 1.0.5 | Activo |
| Cafetería Raiola Backup | 1.1.1 | Activo |

> Nota: los plugins empaquetados de ejemplo (Hello Dolly, Akismet) se eliminaron el 2026-11-06.

### Contenido

| Tipo | Título | Slug | Estado |
|---|---|---|---|
| Página | Inicio | inicio | Publicada (portada estática, vacía) |
| Página | Política de privacidad | politica-privacidad | Publicada (plantilla, enlazada como página de privacidad) |
| Página | Carta | carta | Publicada (vacía, pendiente de diseño) |
| Página | Nuestro café | nuestro-cafe | Publicada (vacía, pendiente de diseño) |
| Página | Nosotros | nosotros | Publicada (vacía, pendiente de diseño) |
| Página | Equipo | equipo | Publicada (vacía, pendiente de diseño) |
| Página | Visítanos | visitanos | Publicada (vacía, pendiente de diseño) |
| Página | Reseñas | resenas | Publicada (vacía, pendiente de diseño) |
| Página | Contacto | contacto | Publicada (vacía, pendiente de diseño) |
| Página | Preguntas frecuentes | preguntas-frecuentes | Publicada (vacía, pendiente de diseño) |
| Página | Newsletter | newsletter | Publicada (vacía, pendiente de diseño) |
| Página | Confirmación de suscripción | newsletter/confirmacion | Publicada (vacía, hija de Newsletter) |
| Página | Baja de suscripción | newsletter/baja | Publicada (vacía, hija de Newsletter) |
| Página | Blog | blog | Publicada (vacía, pendiente de diseño) |
| Página | Aprende | aprende | Publicada (vacía, pendiente de diseño) |
| Página | Origen y sostenibilidad | sostenibilidad | Publicada (vacía, pendiente de diseño) |
| Página | Alérgenos | alergenos | Publicada (vacía, pendiente de diseño) |
| Página | Política editorial | politica-editorial | Publicada (vacía, pendiente de diseño) |
| Página | Prensa | prensa | Publicada (vacía, pendiente de diseño) |
| Página | Accesibilidad | accesibilidad | Publicada (vacía, pendiente de diseño) |
| Página | Aviso legal | aviso-legal | Publicada (vacía, pendiente de diseño) |
| Página | Política de cookies | politica-cookies | Publicada (vacía, pendiente de diseño) |
| Página | Catas y talleres | talleres | Publicada (vacía, pendiente de diseño) |
| Página | Eventos privados | eventos-privados | Publicada (vacía, pendiente de diseño) |
| Página | Café para casa | cafe-para-casa | Publicada (vacía, pendiente de diseño) |
| Página | Tarjetas regalo | tarjetas-regalo | Publicada (vacía, pendiente de diseño) |
| Página | Trabaja con nosotros | empleo | Publicada (vacía, pendiente de diseño) |
| Página | Recetas | recetas | Publicada (vacía, pendiente de diseño) |
| Página | Orígenes | origenes | Publicada (vacía, pendiente de diseño) |
| Página | Glosario | glosario | Publicada (vacía, pendiente de diseño) |
| Página | Mapa del sitio | mapa-del-sitio | Publicada (vacía, pendiente de diseño) |

- Entradas publicadas: 0. Comentarios: 0.
- Papelera: 1 entrada y 2 páginas de ejemplo (recuperables, se vacían solas a los 30 días).

### Configuración

- Nombre del sitio: **Cafetería Raiola** · Descripción: **Cafetería de especialidad**.
- Portada: página estática **Inicio** (ID 17), publicada y vacía, lista para diseñar con Elementor.
- Enlaces permanentes: `/%postname%/` (nombre de la entrada), reglas regeneradas.
- Comentarios: habilitados con **moderación previa** (todo comentario requiere aprobación).
- Registro de visitantes: desactivado.
- Política de privacidad: plantilla publicada y enlazada como página de privacidad (pendiente de personalizar con datos reales).
- Autor público: **Antony** (antes mostraba el login `cafeteria_wp_raiola`; el usuario de login no cambió).
- Menús: ninguno creado todavía.
- Usuarios: 1 administrador.
- Zona horaria: Europe/Madrid · Idioma: es_ES · Semana empieza: lunes.
- WP-CLI: no instalado en el VPS.
- Backups: plugin propio «Cafetería Raiola Backup» activo (v1.1.0), carpeta `/var/backups/cafeteria-raiola` (dueño www-data, fuera del docroot) con una subcarpeta por backup nombrada con el sello, programación diaria a las 03:00, retención 14 días. Último backup verificado: `2026-11-07_1359`.

### Infraestructura (VPS)

- Ubuntu 24.04, Nginx, PHP 8.3-FPM, MySQL.
- Archivos: `/var/www/cafeteria-raiola`.
- Base de datos: `cafeteria_raiola`, usuario `cafeteria_wp`, prefijo `wp_`.
- Backups: plugin propio en `/var/backups/cafeteria-raiola` (diario 03:00); guías manuales en `references/copia-de-seguridad.md`.

## Bitácora

### 2026-11-07

- **Corregido bug crítico de JavaScript en «Cafetería Raiola Backup» (v1.1.1).** El JS del admin nunca se ejecutó: WordPress inyecta `var CRB_JS = {...}` (wp_localize_script) y `admin.js` declaraba `const CRB_JS` en el ámbito global, lo que lanza `SyntaxError: Identifier 'CRB_JS' has already been declared` y aborta el script completo. Por eso ningún botón respondía (crear backup, borrar, descargar). Corregido usando un alias local `datosServidor` (sin tocar el global que inyecta WordPress). Versión subida a 1.1.1 para romper la caché del navegador (`admin.js?ver=1.1.1`). **Desplegado y verificado por MCP:** JS desplegado sin `const CRB_JS`, con 5 referencias a `datosServidor`, plugin 1.1.1 activo y página renderizando. Pendiente confirmación visual del usuario con recarga forzada (Ctrl+Shift+R).

- **Borrados los backups planos antiguos (v1.0.0).** Con autorización del usuario, se borraron por MCP los dos juegos sueltos en la raíz de `/var/backups/cafeteria-raiola` (`2026-11-06_1241` y `2026-11-07_0411`, 6 archivos, ~104 MB) usando la misma lógica del botón «Borrar» del plugin, con entrada en `backup.log`. La carpeta queda con el backup vigente en formato carpeta (`2026-11-07_1359/`), `status.json` y `backup.log`.

- **Plugin «Cafetería Raiola Backup» v1.1.0: carpetas por día/hora y descarga con selector de carpeta.** Cambio pedido: (1) cada backup se guarda en su propia subcarpeta nombrada con el sello (`/var/backups/cafeteria-raiola/2026-11-07_1359/` con `db_`, `files_` y `manifest_` dentro; `status.json` y `backup.log` quedan en la raíz), y (2) nuevo botón «Descargar a carpeta» que en Chrome/Edge abre el selector de carpetas nativo (File System Access API) y guarda ahí la copia completa en una subcarpeta con el sello; en Firefox/Safari baja un único `cafeteria-raiola_backup_STAMP.tar.gz` generado al vuelo con tar (la extensión zip de PHP no está cargada en el VPS; se usa `passthru` con `tar` directamente). Retención, listado, borrado y descarga mantienen compatibilidad con los backups planos de la v1.0.0 (`2026-11-06_1241` y `2026-11-07_0411` siguen visibles y descargables). **Desplegado por MCP** (subida del zip por upload link de Novamira + extracción con PclZip + activado, v1.1.0 verificada por `get_plugins()`). **Verificado por MCP en el servidor:** backup real `2026-11-07_1359` creado y verificado OK (DB 45 KB, archivos 52 MB) dentro de su carpeta con permisos 0700/0600; retención borró una carpeta falsa vieja de prueba sin tocar las reales; resolución de descarga y empaquetado tar probados para ambos formatos; página del plugin renderiza los 3 backups (1 nuevo + 2 planos) con 3 botones «Descargar a carpeta». **Verificador independiente + corrección:** la revisión encontró que `remove_tree()` seguía enlaces simbólicos en su entrada raíz (un enlace con nombre de sello habría borrado el destino fuera de la carpeta). Corregido: `remove_tree()` rechaza enlaces (`is_link` antes de `is_dir`), `cleanup()` se salta enlaces, el empaquetado usa nombres explícitos (no `.`), y los regex de sello llevan modificador `D`. Se añadió `tests/test-retention.php` (regresión: enlace raíz, carpeta real y salto de línea). Verificado de nuevo en producción con un enlace real: víctima intacta, enlace saltado, carpeta vieja real borrada y backups reales intactos; tests 3/3 en verde en el servidor. Pendiente solo la prueba manual del selector de carpetas en Chrome/Edge (no automatizable por MCP).

- **Creación de la estructura de páginas (29 páginas nuevas).** Tras la investigación de arquitectura de marca + EEAT (alcance: cafetería física con newsletter de agenda y novedades), se crean por MCP (`wp_insert_post`) 29 páginas publicadas y vacías, pendientes de diseño con Elementor: Carta, Nuestro café, Nosotros, Equipo, Visítanos, Reseñas, Contacto, Preguntas frecuentes, Newsletter (+ hijas Confirmación y Baja), Blog, Aprende, Origen y sostenibilidad, Alérgenos, Política editorial, Prensa, Accesibilidad, Aviso legal, Política de cookies, Catas y talleres, Eventos privados, Café para casa, Tarjetas regalo, Trabaja con nosotros, Recetas, Orígenes, Glosario, Mapa del sitio. No se crean menús todavía. Se excluyen `guia-[barrio]` (falta el barrio real) y `equipo/[nombre]` (falta el equipo real). **Verificado por MCP:** 31 páginas publicadas en total (29 nuevas + Inicio ID 17 + Política de privacidad ID 3), slugs correctos, `confirmacion` (ID 46) y `baja` (ID 47) hijas de `newsletter` (ID 27), Inicio sigue como portada y Privacidad sigue enlazada como página de privacidad, 0 errores en `wp_insert_post`, papelera intacta (2).

### 2026-11-06

- **Corregido bug de descarga en «Cafetería Raiola Backup» (v1.0.0).** El botón «Descargar BD» devolvía «Archivo no encontrado.» porque `class-crb-download.php` armaba la ruta con la extensión `gz` en vez de `sql.gz` (y `gz` en vez de `tar.gz` para los archivos), así que `realpath()` fallaba. Corregido con un mapa de extensiones por tipo. Verificado con peticiones autenticadas reales: DB (423.778 bytes, gzip válido), archivos (54,4 MB, gzip válido) y manifiesto (JSON) descargan con HTTP 200. Plugin redeployado por MCP.

- **Creación e instalación del plugin «Cafetería Raiola Backup» v1.0.0.** Se desarrolló un plugin propio de copias de seguridad basado en `references/copia-de-seguridad.md`, con alcance elegido por el usuario: backup manual en un clic, descarga al equipo, verificación automática, programación WP-Cron (diaria 03:00) con retención de 14 días y log. Sin restauración desde la UI ni destinos en la nube. Instalado por MCP (subida del zip por Novamira + extracción con PclZip + activación), con smoke test previo del pipeline completo en `/tmp`. Primer backup real creado y verificado OK (`2026-11-06_1241`: DB 414 KB gz, archivos 52 MB gz, 12 s). Se creó `/var/backups/cafeteria-raiola` con sudo (dueño www-data). Código en `plugins/cafeteria-raiola-backup/` del repo. Bugs encontrados y corregidos durante las pruebas: ruta de wp-load.php en el worker (son 4 niveles de dirname, no 3), doble redirección que mandaba la salida del worker a /dev/null, y patrón de verificación SQL con backtick final que nunca coincidía.

- **Configuración inicial del sitio (por MCP, en 8 preguntas dinámicas).** Cambios aplicados y verificados: nombre del sitio «Cafetería Raiola» (con tilde), descripción «Cafetería de especialidad», portada estática con página «Inicio» creada (ID 17), enlaces permanentes `/%postname%/` con reglas regeneradas, comentarios con moderación previa obligatoria, registro de visitantes desactivado (ya estaba), política de privacidad publicada y enlazada, y autor público cambiado de `cafeteria_wp_raiola` a «Antony» (el login no cambió). Ya estaban correctos: zona horaria Europe/Madrid, idioma es_ES, semana empieza lunes.
- **Limpieza del contenido de ejemplo.** Se eliminaron: el comentario de ejemplo (borrado permanente), la entrada «¡Hola, mundo!» y las páginas «Página de ejemplo» y «Landing de ejemplo» (movidas a la papelera, recuperables 30 días). Se conservó el borrador «Política de privacidad» como plantilla útil para el sitio real. Verificado por MCP: 0 entradas, 0 páginas publicadas, 0 comentarios.
- **Limpieza de plugins de ejemplo.** Se eliminaron Hello Dolly 1.7.2 (activo) y Akismet 5.7.2 (inactivo), los dos plugins empaquetados de la instalación. Quedan activos: Novamira 1.12.14, AI Provider for Anthropic 1.0.5 y Elementor 4.3.6.
- **Instalación de Elementor 4.3.6.** Se descargó desde `downloads.wordpress.org` (23,4 MB) y se activó. Nota técnica: la extensión `zip` de PHP no está cargada en el VPS, así que el instalador estándar de WordPress (`Plugin_Upgrader`) no sirve; se usó PclZip (PHP puro, incluido en WordPress) para descomprimir. Dato a recordar para futuras instalaciones de plugins por MCP.
- **Themes predeterminados.** Verificado: la instalación solo trae dos themes, el hijo activo `cafeteria-raiola-child` y su padre `twentytwentyfive`. No había otros themes predeterminados que borrar. **Twenty Twenty-Five no se borra**: es el tema padre del hijo activo; eliminarlo rompería el sitio (el hijo no puede funcionar sin su padre).
- **Creación de este diario.** Se crea `diario.md` para registrar todos los cambios del sitio y se establece la rutina: actualizar el diario antes/después de cada cambio y mantener la tabla de estado verificada por MCP. Se toma una foto inicial del estado real del sitio (versiones, plugins, contenido, configuración) consultándolo por MCP.

### 2026-11-05

- **Instalación y conexión de Novamira v1.12.14.** Se instaló el plugin en el sitio y se activaron las AI Abilities. Se configuró la conexión MCP con OpenCode mediante OAuth en `opencode.json` (endpoint `https://cafeteria-raiola.antonydev.tech/wp-json/mcp/novamira-oauth`). Guía: `references/instalar-novamira.md`.
- **Documentación de copias de seguridad.** Se escribió la guía `references/copia-de-seguridad.md` (backup manual por SSH, opcional WP-CLI o UpdraftPlus, restauración y cron).

### 2026-11-04

- **Investigación sobre Novamira.** Se resolvieron dudas sobre capacidades de Novamira, compatibilidad con Elementor, alternativas gratuitas, descarga y qué son las AI Abilities. Sin cambios en el sitio.

### 2026-11-01

- **Instalación de WordPress en el VPS.** Instalación independiente de WordPress en el subdominio `cafeteria-raiola.antonydev.tech` sobre Ubuntu 24.04, Nginx, PHP 8.3-FPM y MySQL. Se creó la base de datos `cafeteria_raiola` y el usuario `cafeteria_wp`. Guía completa: `references/instalar-wordpress-cafeteria-raiola.md`.
- **Creación del tema hijo.** Se creó el tema hijo "Cafetería Raiola Child" basado en Twenty Twenty-Five (tema activo del sitio).

## Referencias del proyecto

| Archivo | Contenido |
|---|---|
| `references/instalar-wordpress-cafeteria-raiola.md` | Guía de instalación del WordPress en el VPS |
| `references/instalar-wordpress-local.md` | Guía de instalación de WordPress local |
| `references/instalar-novamira.md` | Guía de instalación y conexión de Novamira |
| `references/copia-de-seguridad.md` | Guía de backups y restauración |
| `plugins/cafeteria-raiola-backup/` | Plugin propio de copias de seguridad (Opción D de la guía) |
| `workflow/` | Historial de prompts por día |
| `opencode.json` | Configuración MCP de OpenCode (conexión Novamira) |
