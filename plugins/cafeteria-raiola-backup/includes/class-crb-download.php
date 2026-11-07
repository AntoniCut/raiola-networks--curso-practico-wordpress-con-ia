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

		if ( ! in_array( $type, $allowed, true ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}_\d{4}$/D', $stamp ) ) {
			wp_die( 'Solicitud de descarga inválida.' );
		}

		$extensions = array(
			'db'       => 'sql.gz',
			'files'    => 'tar.gz',
			'manifest' => 'json',
		);
		$extension = $extensions[ $type ];
		$dir       = CRB_Job::dir();

		// Formato nuevo: el archivo vive dentro de la carpeta del sello.
		$file = $dir . '/' . $stamp . '/' . $type . '_' . $stamp . '.' . $extension;

		// Compatibilidad: archivos planos de la v1.0.0 en la raíz.
		if ( ! is_file( $file ) ) {
			$file = $dir . '/' . $type . '_' . $stamp . '.' . $extension;
		}

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

	/**
	 * Descarga el backup completo (BD + archivos + manifiesto) como un único
	 * tar.gz generado al vuelo. Es el fallback para navegadores sin selector
	 * de carpetas (File System Access API).
	 *
	 * @return void
	 */
	public static function package() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No tienes permisos para descargar backups.' );
		}

		check_admin_referer( 'crb_download' );

		$stamp = isset( $_GET['stamp'] ) ? sanitize_text_field( wp_unslash( $_GET['stamp'] ) ) : '';

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}_\d{4}$/D', $stamp ) ) {
			wp_die( 'Solicitud de descarga inválida.' );
		}

		$dir = CRB_Job::dir();

		// Los tres archivos que forman el backup.
		$names = array(
			'db_' . $stamp . '.sql.gz',
			'files_' . $stamp . '.tar.gz',
			'manifest_' . $stamp . '.json',
		);

		// Formato nuevo: la carpeta del sello. Si no existe, raíz (v1.0.0).
		$folder = $dir . '/' . $stamp;

		// Solo se empaqueta desde una carpeta real, nunca desde un enlace.
		$source_dir = ( is_dir( $folder ) && ! is_link( $folder ) ) ? $folder : $dir;

		// Se empaquetan solo los tres archivos conocidos del backup.
		$tar_args = array( '-C', escapeshellarg( $source_dir ) );

		foreach ( $names as $name ) {
			$tar_args[] = escapeshellarg( $name );
		}

		// El backup debe estar completo y quedar dentro de la carpeta de backups.
		$real_dir = realpath( $dir );

		foreach ( $names as $name ) {
			$real = realpath( $source_dir . '/' . $name );

			if ( false === $real || false === $real_dir || 0 !== strpos( $real, $real_dir . DIRECTORY_SEPARATOR ) || ! is_readable( $real ) ) {
				wp_die( 'El backup está incompleto o no se puede leer.' );
			}
		}

		// Registra la última descarga para el aviso 3-2-1.
		update_option( 'crb_last_download', time(), false );

		$filename = 'cafeteria-raiola_backup_' . $stamp . '.tar.gz';

		nocache_headers();
		header( 'Content-Type: application/gzip' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		// Con passthru disponible, tar envía los bytes directo al navegador.
		$disabled = array_map( 'trim', explode( ',', (string) ini_get( 'disable_functions' ) ) );

		if ( function_exists( 'passthru' ) && ! in_array( 'passthru', $disabled, true ) && CRB_Shell::command_exists( 'tar' ) ) {
			passthru( 'tar -czf - ' . implode( ' ', $tar_args ) );
			exit;
		}

		// Sin passthru: se arma en un archivo temporal y se sirve con readfile.
		$temp = tempnam( sys_get_temp_dir(), 'crb_package_' );

		if ( false === $temp ) {
			wp_die( 'No se pudo crear el archivo temporal.' );
		}

		$result = CRB_Shell::run( 'tar -czf ' . escapeshellarg( $temp ) . ' ' . implode( ' ', $tar_args ) );

		if ( 0 !== $result['code'] || ! is_file( $temp ) || 0 === filesize( $temp ) ) {
			@unlink( $temp );
			wp_die( 'Falló el empaquetado del backup.' );
		}

		header( 'Content-Length: ' . filesize( $temp ) );

		readfile( $temp );

		@unlink( $temp );
		exit;
	}
}
