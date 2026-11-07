# Instalar Novamira en WordPress local

Guía para instalar Novamira, el plugin gratuito y open source de Dynamic.ooo que conecta tu agente de IA con WordPress mediante MCP, en el sitio local `cafeteria-raiola.test`.

> Novamira está pensado para entornos de desarrollo y staging. Siempre con backups.

## 1. Requisitos

Novamira necesita:

- WordPress 6.9 o superior (por la Abilities API de core).
- PHP 8.0 o superior.
- Node.js 18 o superior (solo si se usa el proxy remoto de MCP; con WP-CLI local se puede usar el transporte STDIO sin Node).

Este proyecto ya cumple todo:

| Componente | Valor | Estado |
|---|---|---|
| WordPress | 7.1.4 | OK |
| PHP | 8.3-FPM | OK |
| Node.js | v24.13.0 | OK |

HTTPS: WordPress exige HTTPS para las Application Passwords, pero en local se puede omitir definiendo `WP_ENVIRONMENT_TYPE` como `local` en `wp-config.php` (ver paso 4).

## 2. Comprobar que WordPress responde

Antes de instalar el plugin, el sitio debe funcionar:

```bash
curl -s -o /dev/null -w "%{http_code}" http://cafeteria-raiola.test/
```

Si devuelve `500` con "Database Error", arranca el MySQL de XAMPP:

```bash
sudo /opt/lampp/lampp start
```

## 3. Descargar e instalar el plugin

1. Descarga el ZIP desde la página oficial: https://novamira.ai/download
2. En el escritorio de WordPress: **Plugins > Añadir nuevo plugin > Subir plugin**.
3. Selecciona el ZIP y pulsa **Instalar ahora**.
4. Pulsa **Activar plugin**.

## 4. Permitir Application Passwords en local (sin HTTPS)

Edita `/var/www/cafeteria-raiola/wp-config.php` y añade antes de la línea de `ABSPATH`:

```php
define( 'WP_ENVIRONMENT_TYPE', 'local' );
```

Esto indica a WordPress que es un entorno local y permite usar Application Passwords sobre HTTP.

## 5. Activar las AI Abilities

1. Ve a **Novamira > Configuration** en el menú lateral del escritorio.
2. Activa **AI Abilities**.

Por defecto están desactivadas: hasta que no las actives, no se expone ninguna herramienta MCP. Novamira registra nueve habilidades: ejecutar PHP, leer, escribir, editar, borrar y listar archivos, habilitar/deshabilitar archivos y crear enlaces de subida.

## 6. Conectar tu cliente de IA (OpenCode)

En **Novamira > Configuration**:

1. Pulsa **Copy prompt**.
2. Pega el prompt en OpenCode: el agente lee los datos de conexión, escribe la configuración MCP y reinicia la sesión.
3. Si el prompt automático no funciona, copia la configuración manual para OpenCode desde la misma página.

Si usas WP-CLI en local, existe también el transporte STDIO, que no requiere Node.js ni proxy remoto.

## Errores frecuentes

### "413 Request Entity Too Large" al subir el ZIP

Nginx rechaza la subida porque su límite por defecto es de 1 MB. Añade `client_max_body_size 64M;` dentro del bloque `server` de `/etc/nginx/conf.d/cafeteria-raiola.test.conf` y recarga:

```bash
sudo nginx -t && sudo systemctl reload nginx
```

Además, sube los límites de PHP-FPM (por defecto son 2M / 8M):

```bash
sudo sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 64M/; s/^post_max_size = .*/post_max_size = 64M/' /etc/php/8.3/fpm/php.ini
sudo systemctl restart php8.3-fpm
```

### "Application Passwords missing"

Un plugin de seguridad puede estar desactivando las Application Passwords (Patchstack, Solid Security, Wordfence). Busca la opción de Application Passwords en el plugin de seguridad y vuelve a activarla.

### El agente no puede conectar

Comprueba que:

- Las AI Abilities están activadas en **Novamira > Configuration**.
- La contraseña de aplicación se creó en **Usuarios > Tu perfil > Application Passwords**.
- `WP_ENVIRONMENT_TYPE` está definido como `local` en `wp-config.php`.

## Notas

- Al activarse, Novamira detecta si ya está instalado el plugin independiente MCP Adapter y sugiere desactivarlo para evitar conflictos.
- Desactivar y borrar Novamira no elimina el directorio sandbox `wp-content/novamira-sandbox/`; bórralo manualmente si quieres limpiarlo.
- Las actualizaciones llegan por el mecanismo normal de actualización de plugins de WordPress.
