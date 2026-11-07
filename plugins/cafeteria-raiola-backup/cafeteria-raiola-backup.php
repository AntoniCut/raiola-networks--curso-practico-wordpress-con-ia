<?php
/**
 * Plugin Name: Cafetería Raiola Backup
 * Description: Copias de seguridad del sitio según la guía del proyecto: archivos + base de datos, verificación de integridad, descarga al equipo y programación con retención.
 * Version:     1.1.0
 * Author:      Antony
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

// Impide el acceso directo al archivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Constantes generales del plugin.
define( 'CRB_VERSION', '1.1.0' );
define( 'CRB_PLUGIN_FILE', __FILE__ );
define( 'CRB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CRB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Carga todas las clases del plugin.
require_once CRB_PLUGIN_DIR . 'includes/load.php';

// Registra ganchos y páginas de administración.
CRB_Plugin::init();

// Ganchos de activación y desactivación.
register_activation_hook( __FILE__, array( 'CRB_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'CRB_Plugin', 'deactivate' ) );
