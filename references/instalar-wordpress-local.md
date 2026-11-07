# Instalar WordPress en local (nginx + XAMPP)

Guía para instalar WordPress en local en el ordenador de desarrollo, usando el nginx del sistema junto con PHP 8.3-FPM y el MariaDB portable de XAMPP (sin Apache).

> Esta guía está adaptada al dominio local `cafeteria-raiola.test` y a la ruta `/var/www/cafeteria-raiola`.

## 1. Requisitos

Necesitas:

- Nginx instalado y funcionando como servicio del sistema.
- PHP 8.3-FPM instalado y funcionando.
- XAMPP portable instalado en `/opt/lampp` (solo se usa su MySQL/MariaDB).
- El dominio `.test` apuntando a `127.0.0.1` en `/etc/hosts`.

Comprueba los servicios:

```bash
systemctl status nginx
systemctl status php8.3-fpm
/opt/lampp/lampp status
```

En este ordenador, nginx carga la configuración desde `/etc/nginx/conf.d/*.conf`, no desde `sites-enabled`.

## 2. Arrancar MySQL de XAMPP

```bash
sudo /opt/lampp/lampp start
```

No importa si Apache falla porque el puerto `80` está ocupado por nginx; solo se necesita MySQL.

Comprueba que responde por TCP:

```bash
/opt/lampp/bin/mysql -u root -h 127.0.0.1 -e "SELECT VERSION();"
```

## 3. Crear la carpeta de WordPress

```bash
sudo mkdir -p /var/www/cafeteria-raiola
```

## 4. Descargar WordPress

Usa `/tmp` únicamente como ubicación temporal para descargar y descomprimir WordPress:

```bash
cd /tmp
curl -O https://wordpress.org/latest.tar.gz
tar -xzf latest.tar.gz
sudo cp -a wordpress/. /var/www/cafeteria-raiola/
```

La instalación definitiva queda en:

```text
/var/www/cafeteria-raiola
```

## 5. Configurar permisos

```bash
sudo chown -R www-data:www-data /var/www/cafeteria-raiola
sudo find /var/www/cafeteria-raiola -type d -exec chmod 755 {} \;
sudo find /var/www/cafeteria-raiola -type f -exec chmod 644 {} \;
```

## 6. Crear la configuración de Nginx

Crea el archivo de configuración:

```bash
sudo nano /etc/nginx/conf.d/cafeteria-raiola.test.conf
```

Pega este contenido:

```nginx
server {
    listen 80;
    listen [::]:80;

    server_name cafeteria-raiola.test;

    root /var/www/cafeteria-raiola;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

## 7. Añadir el dominio local a /etc/hosts

Añade esta línea a `/etc/hosts` si no existe:

```bash
sudo sh -c 'echo "127.0.0.1 cafeteria-raiola.test" >> /etc/hosts'
```

`.test` es un dominio reservado para desarrollo local, nunca existirá en internet real.

## 8. Activar el sitio

Comprueba la configuración:

```bash
sudo nginx -t
```

Solo si la prueba es correcta, recarga Nginx:

```bash
sudo systemctl reload nginx
```

No recargues Nginx si `nginx -t` muestra errores.

## 9. Crear la base de datos MySQL

Ejecuta estas instrucciones SQL con el cliente de XAMPP:

```bash
/opt/lampp/bin/mysql -u root -h 127.0.0.1 -e "CREATE DATABASE cafeteria_raiola CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Guarda estos datos porque se utilizarán durante la instalación web:

- Base de datos: `cafeteria_raiola`
- Usuario: `root`
- Contraseña: vacía (por defecto en XAMPP)
- Servidor: `127.0.0.1`

## 10. Completar la instalación desde el navegador

Abre:

```text
http://cafeteria-raiola.test
```

En el instalador de WordPress introduce:

- Base de datos: `cafeteria_raiola`
- Usuario: `root`
- Contraseña: vacía
- Servidor de la base de datos: `127.0.0.1`
- Prefijo de tablas: `wp_`

Después crea el usuario administrador de WordPress.

## 11. Limpiar los archivos temporales

```bash
rm -rf /tmp/wordpress /tmp/latest.tar.gz
```

No borres esta carpeta:

```text
/var/www/cafeteria-raiola
```

Esa es la instalación activa de WordPress.

## Errores frecuentes

### Apache de XAMPP no arranca: puerto 80 en uso

El nginx del sistema ya usa el puerto `80`. No hace falta Apache: esta guía usa nginx + PHP-FPM.

### Se muestra "Welcome to nginx" en lugar de WordPress

Comprueba que el archivo de configuración esté en `conf.d` con extensión `.conf`:

```bash
sudo nginx -T | grep cafeteria-raiola
```

### Error 502 Bad Gateway

Comprueba que PHP-FPM esté activo y que exista el socket configurado:

```bash
sudo systemctl status php8.3-fpm
ls -l /run/php/
```

Si el socket tiene otro nombre, actualiza `fastcgi_pass` en la configuración de Nginx.

### Error de conexión con la base de datos

Usa `127.0.0.1` como servidor, no `localhost`: `localhost` usa el socket del sistema y no el de XAMPP.

Comprueba que MySQL de XAMPP esté activo:

```bash
/opt/lampp/lampp status
```

## Resumen de rutas

| Elemento | Ubicación o valor |
|---|---|
| Dominio local | `cafeteria-raiola.test` |
| Archivos de WordPress | `/var/www/cafeteria-raiola` |
| Configuración Nginx | `/etc/nginx/conf.d/cafeteria-raiola.test.conf` |
| Entrada en hosts | `/etc/hosts` |
| Base de datos | `cafeteria_raiola` |
| Usuario MySQL | `root` (sin contraseña) |
| Servidor MySQL | `127.0.0.1` |
| PHP-FPM | `/run/php/php8.3-fpm.sock` |
