<?php
/**
 * Registro de actividad del plugin Cafetería Raiola Backup.
 * El log vive en la carpeta de backups, fuera del directorio web.
 */

// Impide el acceso directo al archivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase CRB_Log: escribe y lee el archivo backup.log.
 */
class CRB_Log {

	/**
	 * Nombre del archivo de log dentro de la carpeta de backups.
	 */
	const FILE = 'backup.log';

	/**
	 * Tamaño máximo del log antes de reiniciarlo (1 MB).
	 */
	const MAX_BYTES = 1048576;

	/**
	 * Añade una línea al log.
	 *
	 * @param string $message Mensaje a registrar.
	 * @param string $level   Nivel: INFO, WARN o ERROR.
	 * @return void
	 */
	public static function log( $message, $level = 'INFO' ) {
		$dir = CRB_Job::dir();

		if ( ! is_dir( $dir ) || ! is_writable( $dir ) ) {
			return;
		}

		$file = $dir . '/' . self::FILE;

		// Si el log creció demasiado, se reinicia para no crecer sin límite.
		if ( is_file( $file ) && filesize( $file ) > self::MAX_BYTES ) {
			@unlink( $file );
		}

		$line = sprintf( '[%s] %s: %s%s', wp_date( 'Y-m-d H:i:s' ), $level, $message, PHP_EOL );

		file_put_contents( $file, $line, FILE_APPEND | LOCK_EX );
	}

	/**
	 * Devuelve las últimas líneas del log (la más reciente al final).
	 *
	 * @param int $limit Número máximo de líneas.
	 * @return array
	 */
	public static function recent( $limit = 100 ) {
		$dir  = CRB_Job::dir();
		$file = $dir . '/' . self::FILE;

		if ( ! is_file( $file ) ) {
			return array();
		}

		$lines = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

		if ( false === $lines ) {
			return array();
		}

		return array_slice( $lines, -intval( $limit ) );
	}
}
