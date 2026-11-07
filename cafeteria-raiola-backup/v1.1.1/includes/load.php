<?php
/**
 * Lista central de archivos del plugin.
 * La usa el bootstrap del plugin y el proceso de trabajo (worker.php).
 */

// Impide el acceso directo al archivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once CRB_PLUGIN_DIR . 'includes/class-crb-settings.php';
require_once CRB_PLUGIN_DIR . 'includes/class-crb-log.php';
require_once CRB_PLUGIN_DIR . 'includes/class-crb-shell.php';
require_once CRB_PLUGIN_DIR . 'includes/class-crb-db-dump.php';
require_once CRB_PLUGIN_DIR . 'includes/class-crb-archiver.php';
require_once CRB_PLUGIN_DIR . 'includes/class-crb-verifier.php';
require_once CRB_PLUGIN_DIR . 'includes/class-crb-retention.php';
require_once CRB_PLUGIN_DIR . 'includes/class-crb-job.php';
require_once CRB_PLUGIN_DIR . 'includes/class-crb-download.php';
require_once CRB_PLUGIN_DIR . 'includes/class-crb-admin.php';
require_once CRB_PLUGIN_DIR . 'includes/class-crb-plugin.php';
