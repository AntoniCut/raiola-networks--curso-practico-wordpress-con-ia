<?php
/**
 * Proceso de trabajo del plugin Cafetería Raiola Backup.
 * Se ejecuta desde la línea de comandos, fuera de una petición HTTP,
 * para no chocar con los límites de tiempo de PHP-FPM.
 */

// Solo puede ejecutarse desde la línea de comandos.
if ( 'cli' !== PHP_SAPI ) {
	exit;
}

// Token de autorización pasado como primer argumento.
$crb_token = isset( $argv[1] ) ? (string) $argv[1] : '';

// Carga WordPress desde la raíz de la instalación.
$crb_wp_load = dirname( __DIR__, 4 ) . '/wp-load.php';

if ( ! is_file( $crb_wp_load ) ) {
	fwrite( STDERR, "No se encontró wp-load.php\n" );
	exit( 1 );
}

require_once $crb_wp_load;

// Carga las clases del plugin (el bootstrap normal no corre en CLI).
require_once CRB_PLUGIN_DIR . 'includes/load.php';

// Ejecuta el trabajo real.
CRB_Job::run_worker( $crb_token );
