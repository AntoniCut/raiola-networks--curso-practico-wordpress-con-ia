# Cafetería Raiola — curso práctico de WordPress con IA

Sitio WordPress de una cafetería de especialidad, desarrollado como proyecto práctico de WordPress + IA. La instalación vive en un VPS y se administra por el panel de WordPress y por MCP (Novamira) desde este repositorio.

## El sitio

| Elemento | Valor |
|---|---|
| URL | https://cafeteria-raiola.antonydev.tech |
| WordPress | 7.1.4 |
| PHP | 8.3 |
| Entorno | Producción en VPS (Ubuntu 24.04, Nginx, MySQL) |
| Tema | Cafetería Raiola Child (hijo de Twenty Twenty-Five) |
| Page builder | Elementor 4.3.6 |
| Conexión IA | Novamira 1.12.15 por MCP (OAuth), configurada en `opencode.json` |

El estado real y actualizado del sitio (contenido, configuración, versiones) se consulta siempre en [`diario.md`](diario.md); este README no reemplaza esa fuente.

## Estructura del proyecto

| Ruta | Contenido |
|---|---|
| `diario.md` | Bitácora del sitio: cambios fechados y estado actual verificado por MCP |
| `plugins/cafeteria-raiola-backup/` | Código fuente del plugin propio de copias de seguridad (código vivo) |
| `plugins/novamira-1.12.14/` | Copia de referencia del plugin Novamira (terceros) |
| `cafeteria-raiola-backup/` | Snapshots versionados del plugin por release (`v1.0.0`, `v1.1.0`, `v1.1.1`) |
| `backup/` | Copias de seguridad descargadas desde el sitio. **Ignorada por git: contiene credenciales** |
| `references/` | Guías: instalación local, instalación en el VPS, Novamira, copias de seguridad |
| `workflow/` | Historial de prompts por día. Ignorada por git |
| `opencode.json` | Conexión MCP al sitio (Novamira por OAuth) |

## Plugin de copias de seguridad

Plugin propio («Cafetería Raiola Backup») que resuelve la regla de oro de los backups: una copia fuera del servidor.

- Crea el backup completo en un clic: base de datos (`mysqldump`) + archivos (`tar.gz`), con verificación de integridad (gzip, tar y tablas del SQL).
- Organiza cada copia en su propia carpeta con fecha y hora dentro de `/var/backups/cafeteria-raiola/`.
- Programa copias diarias o semanales (WP-Cron) con retención configurable y log de actividad.
- Descarga al equipo con «Descargar a carpeta»: selector nativo de carpetas en Chrome/Edge y archivo combinado como fallback en el resto de navegadores.
- Mantiene compatibilidad con las copias del formato anterior (archivos sueltos).
- Tests de regresión: `php tests/test-retention.php`.

Detalle funcional completo en `plugins/cafeteria-raiola-backup/readme.txt`.

## Cómo se trabaja este proyecto

1. **Antes de tocar el sitio**, registrar la entrada en `diario.md` (regla del proyecto).
2. Aplicar los cambios por MCP y verificar el resultado consultando el sitio real (no de memoria).
3. Actualizar la tabla de estado actual y la bitácora del diario con lo verificado.
4. Los cambios de código van con commits convencionales; el despliegue del plugin se hace por MCP.

Las reglas completas para agentes de IA están en [`AGENTS.md`](AGENTS.md).

## Seguridad

- Las copias de seguridad contienen `wp-config.php` (contraseña de MySQL y claves de autenticación) y la base de datos completa. **Nunca deben commitearse**; `backup/` y los patrones de backup están en `.gitignore`.
- Las credenciales de la base de datos se leen en tiempo de ejecución desde `wp-config.php`; el plugin no guarda secretos en el código.

## Requisitos para trabajar localmente

- PHP 8+ con CLI (lint y tests), `zip`/`unzip` para empaquetar el plugin y Node.js para comprobar el JS (`node --check`).
- Acceso MCP al sitio configurado en `opencode.json` (OAuth de Novamira).
