<?php
/**
 * Ajustes del plugin Cafetería Raiola Backup.
 * Se guardan en una única opción (crb_settings) de la tabla wp_options.
 */

// Impide el acceso directo al archivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase CRB_Settings: lectura, escritura y saneamiento de los ajustes.
 */
class CRB_Settings {

	/**
	 * Nombre de la opción en la tabla wp_options.
	 */
	const OPTION = 'crb_settings';

	/**
	 * Valores por defecto de todos los ajustes.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'backup_dir'            => '/var/backups/cafeteria-raiola',
			'schedule'              => 'daily',
			'schedule_time'         => '03:00',
			'weekday'               => 1,
			'retention_days'        => 14,
			'download_warning_days' => 14,
			'exclude'               => array(
				'wp-content/cache',
				'wp-content/upgrade',
				'wp-content/novamira-sandbox',
			),
		);
	}

	/**
	 * Devuelve los ajustes actuales mezclados con los valores por defecto.
	 *
	 * @return array
	 */
	public static function get() {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		return wp_parse_args( $stored, self::defaults() );
	}

	/**
	 * Guarda los ajustes.
	 *
	 * @param array $values Valores a guardar.
	 * @return void
	 */
	public static function update( $values ) {
		update_option( self::OPTION, wp_parse_args( $values, self::defaults() ), false );
	}

	/**
	 * Sanea el envío del formulario de ajustes antes de guardarlo.
	 *
	 * @param array $input Datos crudos del formulario.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$clean = self::defaults();

		if ( ! is_array( $input ) ) {
			return $clean;
		}

		if ( ! empty( $input['backup_dir'] ) ) {
			$clean['backup_dir'] = untrailingslashit( sanitize_text_field( wp_unslash( $input['backup_dir'] ) ) );
		}

		if ( isset( $input['schedule'] ) && in_array( $input['schedule'], array( 'daily', 'weekly', 'disabled' ), true ) ) {
			$clean['schedule'] = $input['schedule'];
		}

		if ( ! empty( $input['schedule_time'] ) && preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $input['schedule_time'] ) ) {
			$clean['schedule_time'] = $input['schedule_time'];
		}

		if ( isset( $input['weekday'] ) ) {
			$clean['weekday'] = max( 1, min( 7, intval( $input['weekday'] ) ) );
		}

		if ( isset( $input['retention_days'] ) ) {
			$clean['retention_days'] = max( 1, min( 365, intval( $input['retention_days'] ) ) );
		}

		if ( isset( $input['download_warning_days'] ) ) {
			$clean['download_warning_days'] = max( 1, min( 365, intval( $input['download_warning_days'] ) ) );
		}

		if ( isset( $input['exclude'] ) && is_array( $input['exclude'] ) ) {
			$clean['exclude'] = self::sanitize_excludes( $input['exclude'] );
		}

		return $clean;
	}

	/**
	 * Sanea la lista de rutas a excluir del tar.
	 * Solo admite rutas relativas a la instalación, sin barras iniciales ni segmentos padre.
	 *
	 * @param array $excludes Rutas crudas del formulario.
	 * @return array
	 */
	private static function sanitize_excludes( $excludes ) {
		$clean = array();

		foreach ( $excludes as $raw ) {
			$path = trim( sanitize_text_field( wp_unslash( $raw ) ) );

			if ( '' === $path || '/' === $path[0] ) {
				continue;
			}

			$segments = array_filter( explode( '/', $path ), 'strlen' );

			if ( empty( $segments ) || in_array( '..', $segments, true ) ) {
				continue;
			}

			$clean[] = implode( '/', $segments );
		}

		return array_values( array_unique( $clean ) );
	}
}
