<?php
/**
 * Retención de backups: borra los más antiguos según los días configurados.
 * Solo toca carpetas con el sello del plugin y archivos con los patrones
 * db_, files_ y manifest_ (estos últimos por compatibilidad con la v1.0.0).
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
	 * @return array Nombres de las carpetas o archivos borrados.
	 */
	public static function cleanup( $backup_dir, $days ) {
		$deleted = array();
		$cutoff  = time() - ( intval( $days ) * DAY_IN_SECONDS );

		// Formato nuevo: una carpeta por backup (nombre = sello).
		$folders = glob( $backup_dir . '/*', GLOB_ONLYDIR );

		if ( is_array( $folders ) ) {
			foreach ( $folders as $folder ) {
				$name = basename( $folder );

				// Solo se tocan carpetas reales (nunca enlaces) con forma de sello.
				if ( is_link( $folder ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}_\d{4}$/D', $name ) ) {
					continue;
				}

				if ( filemtime( $folder ) < $cutoff && self::remove_tree( $folder ) ) {
					$deleted[] = $name;
				}
			}
		}

		// Compatibilidad: archivos planos de la v1.0.0 en la raíz.
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

	/**
	 * Borra una carpeta y todo su contenido de forma recursiva.
	 * Solo debe usarse con carpetas propias del plugin.
	 *
	 * @param string $dir Carpeta a borrar.
	 * @return bool
	 */
	public static function remove_tree( $dir ) {
		$dir = untrailingslashit( $dir );

		// Nunca se siguen enlaces simbólicos: solo se borran carpetas reales.
		if ( is_link( $dir ) || ! is_dir( $dir ) ) {
			return false;
		}

		$items = scandir( $dir );

		if ( false === $items ) {
			return false;
		}

		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}

			$path = $dir . '/' . $item;

			if ( is_dir( $path ) && ! is_link( $path ) ) {
				self::remove_tree( $path );
			} else {
				@unlink( $path );
			}
		}

		return @rmdir( $dir );
	}
}
