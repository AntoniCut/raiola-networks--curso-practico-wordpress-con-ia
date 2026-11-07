<?php
/**
 * Exportación de la base de datos con mysqldump.
 * Las credenciales salen de wp-config.php y viajan en un archivo .my.cnf
 * temporal con chmod 600, nunca en la línea de comandos.
 */

// Impide el acceso directo al archivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase CRB_DB_Dump: genera el volcado SQL comprimido de la base de datos.
 */
class CRB_DB_Dump {

	/**
	 * Devuelve el binario de volcado disponible en el servidor.
	 *
	 * @return string Nombre del binario o cadena vacía.
	 */
	public static function binary() {
		if ( CRB_Shell::command_exists( 'mysqldump' ) ) {
			return 'mysqldump';
		}

		if ( CRB_Shell::command_exists( 'mariadb-dump' ) ) {
			return 'mariadb-dump';
		}

		return '';
	}

	/**
	 * Indica si el volcado de base de datos es posible en este servidor.
	 *
	 * @return bool
	 */
	public static function available() {
		return '' !== self::binary() && CRB_Shell::command_exists( 'gzip' );
	}

	/**
	 * Crea el archivo temporal de credenciales para mysqldump.
	 *
	 * @return string|WP_Error Ruta del archivo creado o error.
	 */
	private static function config_file() {
		if ( ! defined( 'DB_NAME' ) || ! defined( 'DB_USER' ) || ! defined( 'DB_PASSWORD' ) || ! defined( 'DB_HOST' ) ) {
			return new WP_Error( 'crb', 'No se pudieron leer las constantes de la base de datos desde wp-config.php.' );
		}

		$lines = array( '[client]' );
		$host  = DB_HOST;

		// DB_HOST puede ser un socket Unix (formato :/ruta/al/socket).
		if ( $host && ':' === $host[0] ) {
			$lines[] = 'socket="' . addcslashes( substr( $host, 1 ), "\\\"" ) . '"';
		} elseif ( $host && ! in_array( $host, array( 'localhost', '127.0.0.1' ), true ) ) {
			// DB_HOST también puede traer puerto (formato host:puerto).
			$parts   = explode( ':', $host, 2 );
			$lines[] = 'host="' . addcslashes( $parts[0], "\\\"" ) . '"';

			if ( isset( $parts[1] ) && is_numeric( $parts[1] ) ) {
				$lines[] = 'port=' . intval( $parts[1] );
			}
		}

		$lines[] = 'user="' . addcslashes( DB_USER, "\\\"" ) . '"';
		$lines[] = 'password="' . addcslashes( DB_PASSWORD, "\\\"" ) . '"';

		$path = tempnam( sys_get_temp_dir(), 'crb-my-' );

		if ( false === $path ) {
			return new WP_Error( 'crb', 'No se pudo crear el archivo temporal de credenciales.' );
		}

		if ( false === file_put_contents( $path, implode( "\n", $lines ) . "\n" ) ) {
			return new WP_Error( 'crb', 'No se pudo escribir el archivo temporal de credenciales.' );
		}

		// Protege las credenciales frente a otros usuarios del sistema.
		chmod( $path, 0600 );

		return $path;
	}

	/**
	 * Crea el volcado SQL comprimido de la base de datos.
	 * Usa --single-transaction para no bloquear el sitio durante la exportación.
	 *
	 * @param string $output_file Ruta de destino (db_STAMP.sql.gz).
	 * @return true|WP_Error
	 */
	public static function dump( $output_file ) {
		$binary = self::binary();

		if ( '' === $binary ) {
			return new WP_Error( 'crb', 'No se encontró mysqldump ni mariadb-dump en el servidor.' );
		}

		if ( ! CRB_Shell::command_exists( 'gzip' ) ) {
			return new WP_Error( 'crb', 'No se encontró gzip en el servidor.' );
		}

		$config = self::config_file();

		if ( is_wp_error( $config ) ) {
			return $config;
		}

		$command = $binary
			. ' --defaults-extra-file=' . escapeshellarg( $config )
			. ' --single-transaction --quick --skip-lock-tables --default-character-set=utf8mb4 --no-tablespaces'
			. ' ' . escapeshellarg( DB_NAME )
			. ' | gzip -c > ' . escapeshellarg( $output_file );

		$result = CRB_Shell::run( $command );

		// El archivo de credenciales se borra siempre, haya salido bien o mal.
		@unlink( $config );

		if ( 0 !== $result['code'] || ! is_file( $output_file ) || 0 === filesize( $output_file ) ) {
			$message = 'Falló la exportación de la base de datos';

			if ( ! empty( $result['output'] ) ) {
				$message .= ': ' . substr( $result['output'], 0, 500 );
			}

			return new WP_Error( 'crb', $message );
		}

		return true;
	}
}
