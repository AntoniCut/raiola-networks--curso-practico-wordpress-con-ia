<?php
/**
 * Punto de entrada del plugin: ganchos, cron y administración.
 */

// Impide el acceso directo al archivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase CRB_Plugin: arranque del plugin y gestión de la programación.
 */
class CRB_Plugin {

	/**
	 * Registra los ganchos del plugin.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'crb_run_backup', array( 'CRB_Job', 'cron_backup' ) );
		CRB_Admin::init();
	}

	/**
	 * Al activar el plugin se programa la primera cita del cron.
	 *
	 * @return void
	 */
	public static function activate() {
		self::reschedule();
		CRB_Log::log( 'Plugin activado (v' . CRB_VERSION . ').' );
	}

	/**
	 * Al desactivar el plugin se limpia la cita del cron.
	 *
	 * @return void
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'crb_run_backup' );
	}

	/**
	 * Reprograma la cita del cron según los ajustes actuales.
	 *
	 * @return void
	 */
	public static function reschedule() {
		wp_clear_scheduled_hook( 'crb_run_backup' );

		$settings = CRB_Settings::get();

		if ( 'disabled' === $settings['schedule'] ) {
			return;
		}

		$next = self::next_run( $settings );

		if ( $next ) {
			wp_schedule_event( $next, $settings['schedule'], 'crb_run_backup' );
		}
	}

	/**
	 * Calcula la próxima cita del cron en la zona horaria del sitio.
	 * Diaria: hoy a la hora elegida (o mañana si ya pasó).
	 * Semanal: el día de la semana elegido (1 = lunes … 7 = domingo).
	 *
	 * @param array $settings Ajustes actuales.
	 * @return int Timestamp de la próxima ejecución.
	 */
	public static function next_run( $settings ) {
		$parts = explode( ':', $settings['schedule_time'], 2 );
		$hour  = intval( $parts[0] );
		$min   = isset( $parts[1] ) ? intval( $parts[1] ) : 0;

		$date = new DateTime( 'now', wp_timezone() );
		$date->setTime( $hour, $min );

		// Si la hora ya pasó hoy, se pasa a mañana.
		if ( $date->getTimestamp() <= time() ) {
			$date->modify( '+1 day' );
		}

		if ( 'weekly' === $settings['schedule'] ) {
			$target  = max( 1, min( 7, intval( $settings['weekday'] ) ) );
			$current = intval( $date->format( 'N' ) );
			$diff    = ( $target - $current + 7 ) % 7;

			if ( $diff > 0 ) {
				$date->modify( sprintf( '+%d days', $diff ) );
			}

			if ( $date->getTimestamp() <= time() ) {
				$date->modify( '+7 days' );
			}
		}

		return $date->getTimestamp();
	}
}
