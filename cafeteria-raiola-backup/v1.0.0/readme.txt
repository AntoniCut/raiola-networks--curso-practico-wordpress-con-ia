=== Cafetería Raiola Backup ===
Contributors: antony
Tags: backup, seguridad, base de datos, archivos
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later

Copias de seguridad completas del sitio: base de datos + archivos, verificación de integridad, descarga al equipo y programación con retención.

== Description ==

Plugin propio de copias de seguridad para la cafetería, basado en la guía del proyecto (references/copia-de-seguridad.md).

Qué hace:

* Crea en un clic el backup completo: volcado SQL comprimido (mysqldump con --single-transaction) y los archivos del sitio (tar.gz).
* Verifica cada backup: integridad de gzip, lista completa del tar y presencia de las tablas con el prefijo del sitio.
* Descarga los backups a tu equipo (regla de oro: una copia fuera del servidor).
* Programa backups automáticos (diarios o semanales) con retención por días.
* Guarda todo fuera del directorio del sitio, con permisos 600, y registra la actividad en un log.
* Avisa si hace mucho que no descargás un backup (regla 3-2-1).

Requisitos del servidor:

* PHP con exec() habilitado.
* Binarios del sistema: mysqldump, tar, gzip y PHP en línea de comandos.
* Una carpeta de backups fuera del directorio web, con permiso de escritura para www-data. El plugin muestra el comando sudo exacto si falta.

== Installation ==

1. Subí la carpeta `cafeteria-raiola-backup` a `wp-content/plugins/` o instalá el zip desde el administrador.
2. Activá el plugin.
3. Abrí «Copias de seguridad» en el menú y seguí el comando sudo que aparece si la carpeta de backups no existe.
4. Creá el primer backup y comprobá la verificación.

== Frequently Asked Questions ==

= ¿Dónde se guardan los backups? =

Por defecto en /var/backups/cafeteria-raiola, fuera del directorio del sitio (nginx no sirve archivos de ahí). La carpeta es configurable.

= ¿Cómo llega la copia fuera del servidor? =

Con el botón «Descargar» de cada backup. La regla 3-2-1 exige al menos una copia fuera del VPS.

= ¿Cómo se restauran? =

Por SSH, con los comandos de la sección «Cómo restaurar un backup» de la propia página del plugin.

= ¿El cron de WordPress es fiable? =

Se ejecuta con el tráfico del sitio. Si el sitio recibe pocas visitas, apuntá un cron real del sistema a wp-cron.php (el comando aparece en la página del plugin).

== Changelog ==

= 1.0.0 =
* Primera versión: backup manual y programado, verificación, descarga, retención y log.
