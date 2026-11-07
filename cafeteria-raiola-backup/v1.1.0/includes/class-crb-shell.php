<?php
/**
 * Capa de acceso a la línea de comandos del plugin Cafetería Raiola Backup.
 * Todo el respaldo usa herramientas del sistema: mysqldump, tar, gzip, etc.
 */

// Impide el acceso directo al archivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase CRB_Shell: comprobaciones y ejecución de comandos del sistema.
 */
class CRB_Shell {

	/**
	 * Indica si PHP puede ejecutar comandos con exec().
	 *
	 * @return bool
	 */
	public static function has_exec() {
		$disabled = array_map( 'trim', explode( ',', (string) ini_get( 'disable_functions' ) ) );

		return function_exists( 'exec' ) && ! in_array( 'exec', $disabled, true );
	}

	/**
	 * Comprueba si un binario existe en el PATH del servidor.
	 *
	 * @param string $command Nombre del binario.
	 * @return bool
	 */
	public static function command_exists( $command ) {
		if ( ! self::has_exec() ) {
			return false;
		}

		$output = array();
		$code   = 1;

		exec( 'command -v ' . escapeshellarg( $command ) . ' 2>/dev/null', $output, $code );

		return 0 === $code && ! empty( $output[0] );
	}

	/**
	 * Localiza el binario de PHP en línea de comandos.
	 * PHP_BINARY apunta a php-fpm en este servidor, así que hay que buscar el CLI.
	 *
	 * @return string Nombre del binario o cadena vacía si no existe.
	 */
	public static function php_binary() {
		$candidates = array( 'php', 'php8.3', 'php8.2', 'php8.1', 'php8.0' );

		foreach ( $candidates as $candidate ) {
			if ( self::command_exists( $candidate ) ) {
				return $candidate;
			}
		}

		return '';
	}

	/**
	 * Ejecuta un comando y devuelve código de salida y salida combinada.
	 *
	 * @param string $command Comando completo a ejecutar.
	 * @return array{code:int, output:string}
	 */
	public static function run( $command ) {
		$output = array();
		$code   = 1;

		exec( $command . ' 2>&1', $output, $code );

		return array(
			'code'   => $code,
			'output' => implode( "\n", $output ),
		);
	}

	/**
	 * Lanza un comando en segundo plano y devuelve su PID.
	 *
	 * @param string $command  Comando completo a desacoplar.
	 * @param string $log_file Archivo opcional al que redirigir la salida.
	 * @return int PID del proceso lanzado (0 si falló).
	 */
	public static function run_background( $command, $log_file = '' ) {
		$output = array();

		$redirect = '' !== $log_file
			? ' >> ' . escapeshellarg( $log_file ) . ' 2>&1'
			: ' >/dev/null 2>&1';

		exec( 'nohup ' . $command . $redirect . ' & echo $!', $output );

		return ! empty( $output[0] ) ? intval( $output[0] ) : 0;
	}
}
