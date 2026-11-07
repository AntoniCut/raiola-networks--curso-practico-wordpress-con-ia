# Instalar WordPress en `cafeteria-raiola.antonydev.tech`

Guía completa para instalar una instalación independiente de WordPress en un subdominio de un VPS con Ubuntu 24.04, Nginx, PHP 8.3-FPM y MySQL.

> Esta guía está adaptada al subdominio `cafeteria-raiola.antonydev.tech` y a la ruta `/var/www/cafeteria-raiola`.

## 1. Requisitos

Necesitas:

- Un VPS con Ubuntu 24.04.
- Acceso SSH con un usuario administrador o `root`.
- Nginx instalado y funcionando.
- PHP 8.3-FPM instalado y funcionando.
- MySQL instalado y funcionando.
- El subdominio apuntando a la IP pública del VPS.

Comprueba el DNS desde tu ordenador:

```bash
dig +short cafeteria-raiola.antonydev.tech
```

El resultado debe ser la IP pública de tu VPS.

## 2. Conectarse al VPS

Desde tu ordenador, sustituye `IP_DE_TU_VPS` por la IP real:

```bash
ssh root@IP_DE_TU_VPS
```

Si utilizas otro usuario:

```bash
ssh usuario@IP_DE_TU_VPS
```

## 3. Comprobar los servicios existentes

No reinstales servicios que ya estén funcionando. Comprueba su estado:

```bash
sudo systemctl status nginx
sudo systemctl status mysql
sudo systemctl status php8.3-fpm
```

También puedes comprobar las versiones:

```bash
nginx -v
mysql --version
php -v
```

En este servidor se utiliza `mysql.service`, no `mariadb.service`.

## 4. Crear la carpeta de WordPress

```bash
sudo mkdir -p /var/www/cafeteria-raiola
```

## 5. Descargar WordPress

Usa `/tmp` únicamente como ubicación temporal para descargar y descomprimir WordPress:

```bash
cd /tmp
curl -O https://wordpress.org/latest.tar.gz
sudo tar -xzf latest.tar.gz
sudo cp -a wordpress/. /var/www/cafeteria-raiola/
```

La instalación definitiva queda en:

```text
/var/www/cafeteria-raiola
```

## 6. Configurar permisos

```bash
sudo chown -R www-data:www-data /var/www/cafeteria-raiola
sudo find /var/www/cafeteria-raiola -type d -exec chmod 755 {} \;
sudo find /var/www/cafeteria-raiola -type f -exec chmod 644 {} \;
```

## 7. Crear la base de datos MySQL

Entra en MySQL:

```bash
sudo mysql
```

Ejecuta estas instrucciones SQL. Cambia `CONTRASENA_SEGURA` por una contraseña larga y única:

```sql
CREATE DATABASE cafeteria_raiola
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER 'cafeteria_wp'@'localhost'
  IDENTIFIED BY 'CONTRASENA_SEGURA';

GRANT ALL PRIVILEGES ON cafeteria_raiola.*
  TO 'cafeteria_wp'@'localhost';

FLUSH PRIVILEGES;
EXIT;
```

Guarda estos datos porque se utilizarán durante la instalación web:

- Base de datos: `cafeteria_raiola`
- Usuario: `cafeteria_wp`
- Contraseña: la que hayas elegido
- Servidor: `localhost`

## 8. Crear la configuración de Nginx

Crea un archivo para el subdominio:

```bash
sudo nano /etc/nginx/sites-available/cafeteria-raiola.antonydev.tech
```

Pega este contenido:

```nginx
server {
    listen 80;
    listen [::]:80;

    server_name cafeteria-raiola.antonydev.tech;

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

Guarda el archivo y sal de `nano`:

- `Ctrl + O` para guardar.
- `Enter` para confirmar.
- `Ctrl + X` para salir.

## 9. Activar el sitio

```bash
sudo ln -s /etc/nginx/sites-available/cafeteria-raiola.antonydev.tech \
  /etc/nginx/sites-enabled/cafeteria-raiola.antonydev.tech
```

Si el enlace ya existe, no lo vuelvas a crear.

Comprueba la configuración:

```bash
sudo nginx -t
```

Solo si la prueba es correcta, recarga Nginx:

```bash
sudo systemctl reload nginx
```

No recargues Nginx si `nginx -t` muestra errores.

## 10. Comprobar el firewall

Si utilizas UFW, permite SSH y tráfico web:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw reload
```

Los puertos necesarios son:

- TCP `22`, para SSH.
- TCP `80`, para HTTP.
- TCP `443`, para HTTPS.

## 11. Completar la instalación desde el navegador

Abre:

```text
http://cafeteria-raiola.antonydev.tech
```

En el instalador de WordPress introduce:

- Base de datos: `cafeteria_raiola`
- Usuario: `cafeteria_wp`
- Contraseña: la contraseña creada en MySQL
- Servidor de la base de datos: `localhost`
- Prefijo de tablas: `wp_`

Después crea el usuario administrador de WordPress.

## 12. Configurar la página de inicio

Si la portada muestra “Page not found” después de entrar en WordPress:

1. Ve a **Páginas → Añadir nueva**.
2. Crea una página llamada `Inicio`.
3. Publícala.
4. Ve a **Ajustes → Lectura**.
5. Selecciona **Una página estática**.
6. Selecciona `Inicio` como página de inicio.
7. Guarda los cambios.

También puedes ir a **Ajustes → Enlaces permanentes**, seleccionar **Nombre de la entrada** y pulsar **Guardar cambios** para regenerar la configuración de enlaces.

## 13. Activar HTTPS con Let’s Encrypt

Instala Certbot:

```bash
sudo apt update
sudo apt install -y certbot python3-certbot-nginx
```

Solicita el certificado:

```bash
sudo certbot --nginx -d cafeteria-raiola.antonydev.tech
```

Cuando Certbot lo pregunte, selecciona la opción para redirigir HTTP a HTTPS.

Comprueba la renovación automática:

```bash
sudo certbot renew --dry-run
```

Después, la web estará disponible en:

```text
https://cafeteria-raiola.antonydev.tech
```

## 14. Limpiar los archivos temporales

Los archivos descargados en `/tmp` ya no son necesarios después de copiar WordPress a `/var/www/cafeteria-raiola`.

Puedes eliminarlos con:

```bash
sudo rm -rf /tmp/wordpress /tmp/latest.tar.gz
```

No borres esta carpeta:

```text
/var/www/cafeteria-raiola
```

Esa es la instalación activa de WordPress.

## 15. Comprobaciones finales

Comprueba que WordPress responde:

```bash
curl -I https://cafeteria-raiola.antonydev.tech
```

Comprueba el estado de los servicios:

```bash
sudo systemctl status nginx
sudo systemctl status mysql
sudo systemctl status php8.3-fpm
```

Comprueba la configuración de Nginx:

```bash
sudo nginx -t
```

## Errores frecuentes

### Nginx muestra el sitio equivocado

Comprueba que no haya varios bloques configurados para el mismo subdominio:

```bash
sudo nginx -T | grep -A25 -B3 cafeteria-raiola
```

### Error 502 Bad Gateway

Comprueba que PHP-FPM esté activo y que exista el socket configurado:

```bash
sudo systemctl status php8.3-fpm
ls -l /run/php/
```

Si el socket tiene otro nombre, actualiza `fastcgi_pass` en la configuración de Nginx.

### Error de conexión con la base de datos

Comprueba que MySQL esté activo:

```bash
sudo systemctl status mysql
```

Revisa que WordPress utilice exactamente:

- Base de datos: `cafeteria_raiola`
- Usuario: `cafeteria_wp`
- Servidor: `localhost`

### La portada muestra “Page not found”

Regenera los enlaces permanentes desde **Ajustes → Enlaces permanentes → Guardar cambios** y revisa la página configurada en **Ajustes → Lectura**.

## Resumen de rutas

| Elemento | Ubicación o valor |
|---|---|
| Dominio | `cafeteria-raiola.antonydev.tech` |
| Archivos de WordPress | `/var/www/cafeteria-raiola` |
| Configuración Nginx | `/etc/nginx/sites-available/cafeteria-raiola.antonydev.tech` |
| Enlace Nginx activo | `/etc/nginx/sites-enabled/cafeteria-raiola.antonydev.tech` |
| Base de datos | `cafeteria_raiola` |
| Usuario MySQL | `cafeteria_wp` |
| PHP-FPM | `/run/php/php8.3-fpm.sock` |