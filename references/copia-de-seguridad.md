# Copia de seguridad del sitio (WordPress en VPS)

Guía para crear, verificar y restaurar copias de seguridad del WordPress de la cafetería alojado en el VPS.

> Regla de oro: un backup no está terminado hasta que existe **fuera del servidor**. Si solo vive en el mismo disco del VPS, no te salva de una caída, un ransomware ni un borrado accidental.

## Datos del sitio

Datos verificados en la instalación:

| Elemento | Valor |
|---|---|
| Dominio | `https://cafeteria-raiola.antonydev.tech` |
| WordPress | 7.1.4 |
| PHP | 8.3.36 |
| Ruta de WordPress | `/var/www/cafeteria-raiola` |
| Base de datos | `cafeteria_raiola` |
| Prefijo de tablas | `wp_` |
| Servidor MySQL | `localhost` (socket local) |
| Tamaño del sitio | ~140 MB |
| Tamaño de la base de datos | ~6 MB |
| WP-CLI | no instalado |

Con estos tamaños, un backup manual completo tarda segundos.

## 1. Qué hay que respaldar

Un backup de WordPress son **dos partes** que siempre van juntas:

1. **Los archivos**: todo `/var/www/cafeteria-raiola`. Incluye `wp-config.php` (tiene las credenciales de la base de datos), `wp-content/uploads` (medios), temas y plugins.
2. **La base de datos**: contiene posts, páginas, usuarios, opciones, menús y la configuración del sitio.

No forman parte de este backup la configuración del servidor (nginx, PHP-FPM, certificados SSL) ni las cuentas de correo. Eso se respalda aparte.

## 2. Opción A: backup manual por SSH (recomendada)

### 2.1 Preparar las credenciales (una sola vez)

Lee el usuario y la contraseña de la base de datos desde `wp-config.php`:

```bash
grep -E "DB_USER|DB_PASSWORD" /var/www/cafeteria-raiola/wp-config.php
```

Crea un archivo de credenciales para que la contraseña no quede en el historial de comandos:

```bash
sudo tee /root/.backup-my.cnf > /dev/null <<'EOF'
[client]
user=USUARIO
password=CONTRASENA
EOF
sudo chmod 600 /root/.backup-my.cnf
```

Sustituye `USUARIO` y `CONTRASENA` por los valores reales.

### 2.2 Crear el backup

```bash
sudo bash -c '
set -e
STAMP=$(date +%F_%H%M)
BACKUP_DIR=/root/backups
WP_DIR=/var/www/cafeteria-raiola
DB_NAME=cafeteria_raiola

mkdir -p "$BACKUP_DIR"
cd "$BACKUP_DIR"

# Base de datos (consistente sin bloquear el sitio)
mysqldump --defaults-extra-file=/root/.backup-my.cnf \
  --single-transaction --quick --default-character-set=utf8mb4 \
  "$DB_NAME" | gzip > "db_$STAMP.sql.gz"

# Archivos del sitio
tar -czf "files_$STAMP.tar.gz" -C /var/www cafeteria-raiola

chmod 600 "db_$STAMP.sql.gz" "files_$STAMP.tar.gz"
echo "Backup creado: $BACKUP_DIR/db_$STAMP.sql.gz y files_$STAMP.tar.gz"
'
```

Notas:

- Los backups van a `/root/backups`, **nunca** dentro de `/var/www` (serían accesibles desde internet).
- `--single-transaction` evita bloquear la base de datos mientras se exporta.
- Si el sitio crece y aparecen cachés, se pueden excluir del `tar` con `--exclude='cafeteria-raiola/wp-content/cache'`.

### 2.3 Verificar que el backup es válido

```bash
sudo gzip -t /root/backups/db_*.sql.gz && echo "SQL OK"
sudo gzip -t /root/backups/files_*.tar.gz && echo "Archivos OK"
sudo tar -tzf /root/backups/files_*.tar.gz | head -5
sudo zcat /root/backups/db_*.sql.gz | head -20
```

En el SQL deben verse las tablas con el prefijo `wp_` y la sentencia `CREATE TABLE`.

### 2.4 Descargar el backup a tu equipo

Desde la terminal de tu máquina local (no del VPS):

```bash
scp root@TU_VPS:/root/backups/db_*.sql.gz ~/Descargas/
scp root@TU_VPS:/root/backups/files_*.tar.gz ~/Descargas/
```

Borra del servidor los backups viejos cuando ya los tengas descargados.

## 3. Opción B: WP-CLI (opcional)

WP-CLI no está instalado en este VPS, pero es cómodo si vas a hacer backups con frecuencia:

```bash
curl -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x /usr/local/bin/wp
```

Uso:

```bash
# Exportar base de datos
sudo -u www-data wp db export /root/backups/db_$(date +%F).sql --path=/var/www/cafeteria-raiola

# Comprimir archivos
sudo tar -czf /root/backups/files_$(date +%F).tar.gz -C /var/www cafeteria-raiola

# Restaurar la base de datos
sudo -u www-data wp db import /root/backups/db_2026-11-05.sql --path=/var/www/cafeteria-raiola
```

Si se ejecuta como `root`, hay que añadir `--allow-root`.

## 4. Opción C: plugin UpdraftPlus (backup programado)

Para no depender de acordarse, el plugin **UpdraftPlus** automatiza todo:

1. Instalar y activar UpdraftPlus desde el repositorio de plugins.
2. Ir a **Ajustes > UpdraftPlus Backups**.
3. Pestaña **Ajustes**: elegir archivos y base de datos, frecuencia (por ejemplo, semanal) y retención (por ejemplo, 4 copias).
4. En **Destinos remotos**, conectar Google Drive, Dropbox o similar. Sin destino remoto, el backup queda en el servidor y no cuenta como backup real.
5. Pulsar **Backup Now** para crear el primero y **comprobar que se subió** al destino remoto.

## 5. Opción D: plugin propio «Cafetería Raiola Backup» (recomendada para el día a día)

Plugin desarrollado a medida para este sitio (repo del curso: `plugins/cafeteria-raiola-backup/`) que automatiza la guía desde el panel de WordPress:

- **Backup en un clic** («Copias de seguridad» en el menú): genera las dos partes juntas — `mysqldump --single-transaction` + `tar -czf` — con las credenciales leídas de `wp-config.php` a través de un `.my.cnf` temporal con permisos 600.
- **Verificación automática** después de cada backup: integridad de gzip, lista completa del tar y presencia de las tablas `CREATE TABLE \`wp_` en el SQL. El resultado queda en un manifiesto JSON por backup.
- **Descarga al equipo** con un botón por backup (cumple la regla de oro: la copia fuera del servidor). El plugin avisa si pasás muchos días sin descargar (regla 3-2-1).
- **Programación**: WP-Cron diario a las 03:00 (configurable: diaria, semanal o desactivada) con retención por días (14 por defecto). Si el sitio recibe poco tráfico, apuntá un cron real del sistema a `wp-cron.php` (el comando está en la página del plugin).
- **Ubicación**: `/var/backups/cafeteria-raiola`, fuera del directorio web. Se crea una sola vez con `sudo mkdir -p /var/backups/cafeteria-raiola && sudo chown www-data:www-data /var/backups/cafeteria-raiola`.

Requisitos del servidor: `exec()` habilitado en PHP y binarios `mysqldump`, `tar`, `gzip` y PHP CLI. La restauración se sigue haciendo por SSH con la sección [Restaurar un backup](#6-restaurar-un-backup).

Límite: no sube copias a la nube (Google Drive, Dropbox…). Para destinos remotos automáticos, UpdraftPlus (Opción C) o `rclone` en el servidor.

## 6. Restaurar un backup

> Antes de restaurar, hacé un backup del estado actual: siempre se rompe algo cuando no hay marcha atrás.

### 6.1 Restaurar archivos

```bash
sudo tar -xzf /root/backups/files_AAAA-MM-DD_HHMM.tar.gz -C /var/www
sudo chown -R www-data:www-data /var/www/cafeteria-raiola
```

### 6.2 Restaurar la base de datos

```bash
sudo zcat /root/backups/db_AAAA-MM-DD_HHMM.sql.gz | \
  mysql --defaults-extra-file=/root/.backup-my.cnf cafeteria_raiola
```

### 6.3 Después de restaurar

- Abrir el sitio y comprobar que carga.
- Ir a **Ajustes > Enlaces permanentes** y guardar (regenera las reglas de reescritura).
- Si hay caché (LiteSpeed, Redis, etc.), vaciarla.
- Verificar que usuarios y contraseñas funcionan.

## 7. Automatizar con cron (alternativa al plugin)

Script `/root/backups/backup.sh`:

```bash
#!/bin/bash
set -e
STAMP=$(date +%F_%H%M)
BACKUP_DIR=/root/backups
DB_NAME=cafeteria_raiola

mkdir -p "$BACKUP_DIR"
mysqldump --defaults-extra-file=/root/.backup-my.cnf \
  --single-transaction --quick --default-character-set=utf8mb4 \
  "$DB_NAME" | gzip > "$BACKUP_DIR/db_$STAMP.sql.gz"
tar -czf "$BACKUP_DIR/files_$STAMP.tar.gz" -C /var/www cafeteria-raiola
chmod 600 "$BACKUP_DIR/db_$STAMP.sql.gz" "$BACKUP_DIR/files_$STAMP.tar.gz"

# Retención: borrar backups de más de 14 días
find "$BACKUP_DIR" -name "db_*.sql.gz" -mtime +14 -delete
find "$BACKUP_DIR" -name "files_*.tar.gz" -mtime +14 -delete
```

Programarlo todos los días a las 03:00:

```bash
sudo chmod +x /root/backups/backup.sh
sudo crontab -e
```

```cron
0 3 * * * /root/backups/backup.sh >> /root/backups/backup.log 2>&1
```

Y descargar los backups periódicamente a tu equipo (o sincronizarlos con `rclone` a la nube).

## 8. Buenas prácticas

- **3-2-1**: tres copias, en dos medios distintos, una fuera del servidor.
- **Antes de cualquier cambio riesgoso** (actualizaciones, activar AI Abilities, tocar el tema, migraciones) crear un backup y bajarlo.
- **Nunca** guardar backups dentro de `/var/www`: podrían descargarse desde internet.
- Proteger los archivos con `chmod 600`: contienen datos de usuarios y credenciales.
- **Probar la restauración** alguna vez: un backup que nunca se restauró es una esperanza, no un backup.
- Los backups de base de datos y de archivos se restauran **juntos** y del mismo momento.

## 9. Relación con Novamira

El agente conectado por Novamira puede consultar datos del sitio (tamaños, versiones, plugins) y ejecutar PHP, pero el backup debe hacerse a nivel de servidor por SSH o con UpdraftPlus. Novamira **no reemplaza** el proceso de backup: úsalo antes de darle acceso al agente o de pedirle cambios de contenido.
