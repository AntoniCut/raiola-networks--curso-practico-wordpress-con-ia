<?php
/**
 * Verificación de integridad de los backups.
 * Revisa gzip, la lista completa del tar y que el SQL contenga las
 * tablas con el prefijo del sitio (CREATE TABLE `wp_`).
 */

// Impide el acceso directo al archivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase CRB_Verifier: comprueba que un backup es válido después de crearlo.
 */
class CRB_Verifier {

	/**
	 * Verifica un backup completo y devuelve el resultado de cada chequeo.
	 *
	 * @param string $backup_dir Carpeta de backups.
	 * @param string $stamp      Marca de tiempo del backup (YYYY-MM-DD_HHMM).
	 * @param string $prefix     Prefijo de tablas del sitio.
	 * @return array
	 */
	public static function verify( $backup_dir, $stamp, $prefix = 'wp_' ) {
		$db_file    = $backup_dir . '/db_' . $stamp . '.sql.gz';
		$files_file = $backup_dir . '/files_' . $stamp . '.tar.gz';

		$results = array(
			'db_gzip_ok'    => self::gzip_test( $db_file ),
			'files_gzip_ok' => self::gzip_test( $files_file ),
			'tar_list_ok'   => self::tar_list( $files_file ),
			'sql_tables_ok' => self::sql_tables( $db_file, $prefix ),
		);

		$results['db_sha256']    = is_file( $db_file ) ? hash_file( 'sha256', $db_file ) : '';
		$results['files_sha256'] = is_file( $files_file ) ? hash_file( 'sha256', $files_file ) : '';

		$results['ok'] = $results['db_gzip_ok'] && $results['files_gzip_ok'] && $results['tar_list_ok'] && $results['sql_tables_ok'];

		return $results;
	}

	/**
	 * Comprueba que un archivo .gz no está corrupto.
	 *
	 * @param string $file Ruta del archivo.
	 * @return bool
	 */
	private static function gzip_test( $file ) {
		if ( ! is_file( $file ) ) {
			return false;
		}

		$result = CRB_Shell::run( 'gzip -t ' . escapeshellarg( $file ) );

		return 0 === $result['code'];
	}

	/**
	 * Recorre la lista completa del tar; un error aquí indica archivo corrupto.
	 *
	 * @param string $file Ruta del archivo.
	 * @return bool
	 */
	private static function tar_list( $file ) {
		if ( ! is_file( $file ) ) {
			return false;
		}

		$result = CRB_Shell::run( 'tar -tzf ' . escapeshellarg( $file ) . ' > /dev/null' );

		return 0 === $result['code'];
	}

	/**
	 * Comprueba que el volcado SQL contiene tablas con el prefijo del sitio.
	 *
	 * @param string $file   Ruta del archivo .sql.gz.
	 * @param string $prefix Prefijo de tablas.
	 * @return bool
	 */
	private static function sql_tables( $file, $prefix ) {
		if ( ! is_file( $file ) ) {
			return false;
		}

		$pattern = 'CREATE TABLE `' . preg_quote( $prefix, '/' );

		// grep -m 1 corta en la primera coincidencia; su código de salida indica si apareció.
		$result = CRB_Shell::run( 'gzip -dc ' . escapeshellarg( $file ) . ' 2>/dev/null | grep -m 1 ' . escapeshellarg( $pattern ) . ' > /dev/null' );

		return 0 === $result['code'];
	}
}
