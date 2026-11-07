<?php
/**
 * Retención de backups: borra los más antiguos según los días configurados.
 * Solo toca archivos con los patrones db_, files_ y manifest_ del plugin.
 */

// Impide el acceso directo al archivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase CRB_Retention: limpieza por antigüedad de la carpeta de backups.
 */
class CRB_Retention {

	/**
	 * Borra los backups más antiguos que el límite de días.
	 *
	 * @param string $backup_dir Carpeta de backups.
	 * @param int    $days       Días de retención.
	 * @return array Nombres de los archivos borrados.
	 */
	public static function cleanup( $backup_dir, $days ) {
		$deleted = array();
		$cutoff  = time() - ( intval( $days ) * DAY_IN_SECONDS );

		$patterns = array( 'db_*.sql.gz', 'files_*.tar.gz', 'manifest_*.json' );

		foreach ( $patterns as $pattern ) {
			$files = glob( $backup_dir . '/' . $pattern );

			if ( false === $files ) {
				continue;
			}

			foreach ( $files as $file ) {
				if ( is_file( $file ) && filemtime( $file ) < $cutoff ) {
					if ( @unlink( $file ) ) {
						$deleted[] = basename( $file );
					}
				}
			}
		}

		return $deleted;
	}
}
