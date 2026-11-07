# AGENTS.md — Instrucciones para agentes de IA

Proyecto: sitio WordPress de Cafetería Raiola (https://cafeteria-raiola.antonydev.tech) y su plugin propio de copias de seguridad. Este archivo es la ley del repositorio; `diario.md` es la bitácora y la fuente de contexto.

## Primer paso, siempre

1. Leer `diario.md` completo: la sección «Estado actual» y la bitácora. Es la fuente de verdad del sitio (contenido, versiones, configuración); no se sustituye por memoria.
2. Antes de tocar el sitio (contenido, plugins, tema, ajustes, archivos o base de datos), añadir la entrada correspondiente en la bitácora del diario.
3. Después de cada cambio: verificar por MCP contra el sitio real, actualizar la entrada con el resultado y mantener la tabla «Estado actual».

## Entorno (verificado)

- WordPress 7.1.4 · PHP 8.3 · tema hijo «Cafetería Raiola Child» (padre Twenty Twenty-Five) · Elementor 4.3.6.
- Plugins activos: Novamira 1.12.15, AI Provider for Anthropic 1.0.5, Cafetería Raiola Backup 1.1.1.
- VPS: Ubuntu 24.04, Nginx, MySQL, sin WP-CLI. La extensión `zip` de PHP no está cargada: descomprimir con PclZip.
- MCP: Novamira por OAuth (`opencode.json`). Habilidades disponibles: `execute-php`, `create-upload-link`, `read-file`, `list-directory`, `edit-file`, entre otras.

## Reglas de trabajo

### Sitio

- Los cambios se aplican por MCP y se verifican contra el sitio real. No afirmar un resultado sin comprobarlo.
- No tocar el plugin Novamira (romperías la conexión MCP) ni revocar sus credenciales u OAuth.
- Operaciones destructivas (borrar contenido, backups, usuarios) requieren autorización explícita.
- Zona horaria del sitio: Europe/Madrid. Formato de los sellos de backup: `YYYY-MM-DD_HHMM`.

### Plugin de backups (`plugins/cafeteria-raiola-backup/`)

- Es la fuente viva del plugin; los snapshots por versión viven en `cafeteria-raiola-backup/vX.Y.Z/`.
- Estructura: `class-crb-job` (orquestación y estado), `class-crb-db-dump` (mysqldump), `class-crb-archiver` (tar), `class-crb-verifier` (integridad), `class-crb-retention` (borrado por antigüedad y `remove_tree`), `class-crb-download` (descargas), `class-crb-admin` (UI), `class-crb-settings`, `class-crb-log`, `class-crb-shell` (comandos) y `worker.php` (proceso CLI).
- Cada backup vive en `/var/backups/cafeteria-raiola/<sello>/` con `db_`, `files_` y `manifest_`; `status.json` y `backup.log` quedan en la raíz. Mantener compatibilidad con los backups planos de la v1.0.0.
- Al publicar una versión: bump en el header del plugin y en `CRB_VERSION`, `readme.txt` (Stable tag + changelog), snapshot nuevo en `cafeteria-raiola-backup/vX.Y.Z/` y zip de despliegue solo temporal (ignorado por git).
- Verificación mínima antes de dar por buena una versión: `php -l` de los PHP tocados, `node --check` del JS, `php tests/test-retention.php` (3/3) y prueba funcional real por MCP (backup, estructura, retención y descargas).

### Despliegue por MCP (procedimiento probado)

1. Construir el zip desde `plugins/` (no commitearlo; `.gitignore` lo excluye).
2. `novamira/create-upload-link` apuntando a `wp-content/uploads/` y subir con `curl -X PUT` usando el token.
3. Extraer con PclZip (`wp-admin/includes/class-pclzip.php`) sobre `WP_PLUGIN_DIR`; la extensión zip nativa no existe en el VPS.
4. Verificar la versión activa con `get_plugins()` y el contenido desplegado (petición nueva o CLI: las clases PHP quedan cacheadas por petición).
5. Registrar y verificar en el diario.

### Código

- PHP: estilo WordPress (tabs, docblocks en español, guardas `ABSPATH`), como el código existente del plugin.
- JS del plugin: seguir las convenciones del proyecto (JSDoc, comentario en cada instrucción con `//  -----  …  -----`, funciones flecha, sin `var`/`innerHTML`/`alert`, banner de archivo, comentarios en español). No declarar `const X` con el mismo nombre que un global inyectado por `wp_localize_script`: usar un alias local.
- Nunca commitear dumps, `wp-config.php`, backups ni credenciales. `backup/`, `db_*.sql.gz`, `files_*.tar.gz`, `manifest_*.json` y los zips del plugin están ignorados por git.
- No commitear artefactos de despliegue ni copias de backups.

### Commits

- Commits convencionales (`feat(backup): …`, `fix(…): …`, `chore(repo): …`, `docs(…): …`), en español y sin atribución de IA.
- Un commit por unidad de trabajo coherente; informar el hash y el mensaje al terminar.

### Referencias

- `references/` contiene las guías operativas (instalación local, instalación en el VPS, Novamira, copias de seguridad). `workflow/` guarda el historial de prompts y está ignorado por git.
