<?php
/**
 * Desinstalación del plugin Cafetería Raiola Backup.
 * Los backups NUNCA se borran automáticamente: son la red de seguridad.
 */

// Solo se ejecuta durante una desinstalación iniciada por WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Borra los ajustes y el estado del plugin.
delete_option( 'crb_settings' );
delete_option( 'crb_last_download' );

// Limpia la cita del cron.
wp_clear_scheduled_hook( 'crb_run_backup' );
