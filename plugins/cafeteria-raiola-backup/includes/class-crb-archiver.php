<?php
/**
 * Compresión de los archivos del sitio con tar + gzip.
 * Incluye todo el directorio de WordPress salvo las rutas excluidas.
 */

// Impide el acceso directo al archivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase CRB_Archiver: crea files_STAMP.tar.gz con los archivos del sitio.
 */
class CRB_Archiver {

	/**
	 * Indica si el archivado es posible en este servidor.
	 *
	 * @return bool
	 */
	public static function available() {
		return CRB_Shell::command_exists( 'tar' ) && CRB_Shell::command_exists( 'gzip' );
	}

	/**
	 * Crea el archivo comprimido con todo el sitio.
	 * Las rutas excluidas van con prefijo ./ porque así las guarda tar
	 * cuando se archiva el directorio actual con " -C ruta .".
	 *
	 * @param string $output_file Ruta de destino (files_STAMP.tar.gz).
	 * @param array  $excludes    Rutas relativas a excluir del archivo.
	 * @return true|WP_Error
	 */
	public static function create( $output_file, $excludes = array() ) {
		$root = untrailingslashit( ABSPATH );
		$args = array();

		foreach ( (array) $excludes as $exclude ) {
			$exclude = ltrim( trim( (string) $exclude ), '/' );

			if ( '' === $exclude ) {
				continue;
			}

			$args[] = '--exclude=' . escapeshellarg( './' . $exclude );
		}

		$command = 'tar -czf ' . escapeshellarg( $output_file );

		if ( ! empty( $args ) ) {
			$command .= ' ' . implode( ' ', $args );
		}

		$command .= ' -C ' . escapeshellarg( $root ) . ' .';

		$result = CRB_Shell::run( $command );

		if ( 0 !== $result['code'] || ! is_file( $output_file ) || 0 === filesize( $output_file ) ) {
			$message = 'Falló la compresión de los archivos del sitio';

			if ( ! empty( $result['output'] ) ) {
				$message .= ': ' . substr( $result['output'], 0, 500 );
			}

			return new WP_Error( 'crb', $message );
		}

		return true;
	}
}
