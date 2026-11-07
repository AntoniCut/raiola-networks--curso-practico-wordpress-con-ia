<?php
/**
 * Descarga de backups al equipo local.
 * Cumple la regla de oro: una copia fuera del servidor.
 */

// Impide el acceso directo al archivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase CRB_Download: sirve un archivo de backup con autenticación y nonce.
 */
class CRB_Download {

	/**
	 * Procesa la descarga solicitada vía admin-post.php.
	 *
	 * @return void
	 */
	public static function handle() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No tienes permisos para descargar backups.' );
		}

		check_admin_referer( 'crb_download' );

		$type  = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '';
		$stamp = isset( $_GET['stamp'] ) ? sanitize_text_field( wp_unslash( $_GET['stamp'] ) ) : '';

		$allowed = array( 'db', 'files', 'manifest' );

		if ( ! in_array( $type, $allowed, true ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}_\d{4}$/', $stamp ) ) {
			wp_die( 'Solicitud de descarga inválida.' );
		}

		$extensions = array(
			'db'       => 'sql.gz',
			'files'    => 'tar.gz',
			'manifest' => 'json',
		);
		$extension = $extensions[ $type ];
		$dir       = CRB_Job::dir();
		$file      = $dir . '/' . $type . '_' . $stamp . '.' . $extension;

		// El archivo debe existir y quedar dentro de la carpeta de backups.
		$real     = realpath( $file );
		$real_dir = realpath( $dir );

		if ( false === $real || false === $real_dir || 0 !== strpos( $real, $real_dir . DIRECTORY_SEPARATOR ) ) {
			wp_die( 'Archivo no encontrado.' );
		}

		if ( ! is_readable( $real ) ) {
			wp_die( 'No se puede leer el archivo.' );
		}

		// Registra la última descarga para el aviso 3-2-1.
		update_option( 'crb_last_download', time(), false );

		$filename = 'cafeteria-raiola_' . $type . '_' . $stamp . '.' . $extension;

		nocache_headers();
		header( 'Content-Type: ' . ( 'json' === $extension ? 'application/json' : 'application/gzip' ) );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . filesize( $real ) );

		readfile( $real );
		exit;
	}
}
