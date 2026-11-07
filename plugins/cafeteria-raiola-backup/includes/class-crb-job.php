<?php
/**
 * Núcleo del plugin: lanza y ejecuta el trabajo de backup.
 * El backup corre en segundo plano (PHP CLI) para no depender del
 * límite de 30 segundos de las peticiones HTTP. El estado queda en
 * status.json dentro de la carpeta de backups.
 */

// Impide el acceso directo al archivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase CRB_Job: orquesta el ciclo completo de un backup.
 */
class CRB_Job {

	/**
	 * Nombre del archivo de estado dentro de la carpeta de backups.
	 */
	const STATUS_FILE = 'status.json';

	/**
	 * Carpeta de backups configurada.
	 *
	 * @return string
	 */
	public static function dir() {
		$settings = CRB_Settings::get();

		return untrailingslashit( $settings['backup_dir'] );
	}

	/**
	 * Marca de tiempo del backup (fecha y hora local del sitio).
	 *
	 * @return string Formato YYYY-MM-DD_HHMM.
	 */
	public static function stamp() {
		return wp_date( 'Y-m-d_Hi' );
	}

	/**
	 * Lee el estado actual del último trabajo.
	 *
	 * @return array
	 */
	public static function status() {
		$file = self::dir() . '/' . self::STATUS_FILE;

		if ( ! is_file( $file ) ) {
			return array( 'state' => 'idle' );
		}

		$json = file_get_contents( $file );

		if ( false === $json ) {
			return array( 'state' => 'idle' );
		}

		$data = json_decode( $json, true );

		return is_array( $data ) ? $data : array( 'state' => 'idle' );
	}

	/**
	 * Escribe el archivo de estado en la carpeta de backups.
	 *
	 * @param array $data Datos a guardar.
	 * @return void
	 */
	private static function write_status( $data ) {
		$dir = self::dir();

		if ( ! is_dir( $dir ) || ! is_writable( $dir ) ) {
			return;
		}

		file_put_contents( $dir . '/' . self::STATUS_FILE, wp_json_encode( $data ) );

		// El estado también queda protegido de otros usuarios del sistema.
		@chmod( $dir . '/' . self::STATUS_FILE, 0600 );
	}

	/**
	 * Lanza un backup en segundo plano si no hay otro en curso.
	 *
	 * @return array|WP_Error Datos del trabajo lanzado o error.
	 */
	public static function start_backup() {
		$dir = self::dir();

		if ( ! is_dir( $dir ) || ! is_writable( $dir ) ) {
			return new WP_Error( 'crb', 'La carpeta de backups no existe o no es escribible.' );
		}

		if ( ! CRB_Shell::has_exec() ) {
			return new WP_Error( 'crb', 'Este servidor no permite ejecutar comandos del sistema (exec deshabilitado).' );
		}

		$current = self::status();

		// Si el proceso anterior sigue vivo, no se pisa el trabajo en curso.
		if ( isset( $current['state'] ) && 'running' === $current['state'] && self::pid_alive( $current['pid'] ) ) {
			return new WP_Error( 'crb', 'Ya hay un backup en curso.' );
		}

		$stamp = self::stamp();
		$token = wp_generate_password( 24, false );

		// Restos de un intento anterior con la misma marca se borran antes de empezar.
		self::remove_stamp_leftovers( $dir, $stamp );

		$status = array(
			'state'      => 'running',
			'step'       => 'starting',
			'message'    => 'Iniciando el backup…',
			'stamp'      => $stamp,
			'pid'        => 0,
			'token'      => $token,
			'started_at' => current_time( 'mysql' ),
			'error'      => '',
		);

		self::write_status( $status );

		// El proceso de trabajo se ejecuta con PHP CLI, fuera del ciclo HTTP.
		$php_binary = CRB_Shell::php_binary();

		if ( '' === $php_binary ) {
			self::write_status( array_merge( $status, array( 'state' => 'failed', 'error' => 'No se encontró PHP en línea de comandos en el servidor.' ) ) );
			return new WP_Error( 'crb', 'No se encontró PHP en línea de comandos en el servidor.' );
		}

		$worker  = CRB_PLUGIN_DIR . 'includes/worker.php';
		$log     = $dir . '/' . CRB_Log::FILE;
		$command = escapeshellarg( $php_binary ) . ' ' . escapeshellarg( $worker ) . ' ' . escapeshellarg( $token );

		$pid = CRB_Shell::run_background( $command, $log );

		$status['pid'] = $pid;
		self::write_status( $status );

		CRB_Log::log( 'Backup iniciado (pid ' . $pid . '): ' . $stamp );

		return array( 'stamp' => $stamp, 'pid' => $pid );
	}

	/**
	 * Ejecuta el trabajo completo de backup (solo dentro del proceso worker).
	 *
	 * @return void
	 */
	public static function run_backup() {
		$status = self::status();
		$stamp  = isset( $status['stamp'] ) ? $status['stamp'] : '';
		$dir    = self::dir();

		if ( '' === $stamp ) {
			return;
		}

		// La carpeta propia del backup: el sello (fecha y hora) da nombre a la carpeta.
		$backup_dir    = $dir . '/' . $stamp;
		$db_file       = $backup_dir . '/db_' . $stamp . '.sql.gz';
		$files_file    = $backup_dir . '/files_' . $stamp . '.tar.gz';
		$manifest_file = $backup_dir . '/manifest_' . $stamp . '.json';
		$settings      = CRB_Settings::get();

		try {
			// Paso 0: crear la carpeta propia del backup.
			self::step( 'folder', 'Creando la carpeta del backup…' );

			if ( ! wp_mkdir_p( $backup_dir ) ) {
				throw new Exception( 'No se pudo crear la carpeta del backup: ' . $backup_dir );
			}

			chmod( $backup_dir, 0700 );

			// Paso 1: exportar la base de datos.
			self::step( 'database', 'Exportando la base de datos…' );
			$result = CRB_DB_Dump::dump( $db_file );

			if ( is_wp_error( $result ) ) {
				throw new Exception( $result->get_error_message() );
			}

			chmod( $db_file, 0600 );

			// Paso 2: comprimir los archivos del sitio.
			self::step( 'files', 'Comprimiendo los archivos del sitio…' );

			$excludes = isset( $settings['exclude'] ) ? $settings['exclude'] : array();

			// Si la carpeta de backups estuviera dentro del sitio, se excluye sola del tar.
			if ( self::inside_abspath( $dir ) ) {
				$excludes[] = ltrim( str_replace( untrailingslashit( ABSPATH ), '', $dir ), '/' );
			}

			$result = CRB_Archiver::create( $files_file, $excludes );

			if ( is_wp_error( $result ) ) {
				throw new Exception( $result->get_error_message() );
			}

			chmod( $files_file, 0600 );

			// Paso 3: verificar la integridad de lo creado.
			self::step( 'verifying', 'Verificando la integridad…' );
			global $wpdb;
			$verification = CRB_Verifier::verify( $backup_dir, $stamp, $wpdb->prefix );

			// Paso 4: escribir el manifiesto con los datos del backup.
			self::step( 'manifest', 'Guardando el manifiesto…' );

			$manifest = array(
				'plugin_version'    => CRB_VERSION,
				'stamp'             => $stamp,
				'created_at'        => gmdate( 'c' ),
				'site_url'          => home_url(),
				'wordpress_version' => get_bloginfo( 'version' ),
				'php_version'       => PHP_VERSION,
				'db_name'           => defined( 'DB_NAME' ) ? DB_NAME : '',
				'db_prefix'         => $wpdb->prefix,
				'files'             => array(
					'db'    => array(
						'name'   => basename( $db_file ),
						'size'   => filesize( $db_file ),
						'sha256' => $verification['db_sha256'],
					),
					'files' => array(
						'name'   => basename( $files_file ),
						'size'   => filesize( $files_file ),
						'sha256' => $verification['files_sha256'],
					),
				),
				'verification'      => array(
					'db_gzip_ok'    => $verification['db_gzip_ok'],
					'files_gzip_ok' => $verification['files_gzip_ok'],
					'tar_list_ok'   => $verification['tar_list_ok'],
					'sql_tables_ok' => $verification['sql_tables_ok'],
				),
				'verification_ok'   => $verification['ok'],
			);

			file_put_contents( $manifest_file, wp_json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
			chmod( $manifest_file, 0600 );

			// Paso 5: aplicar la retención.
			self::step( 'retention', 'Aplicando la retención…' );
			$deleted = CRB_Retention::cleanup( $dir, intval( $settings['retention_days'] ) );

			// Paso 6: marcar el trabajo como terminado.
			$status                      = self::status();
			$status['state']             = 'success';
			$status['step']              = 'done';
			$status['message']           = 'Backup completado.';
			$status['finished_at']       = current_time( 'mysql' );
			$status['verification_ok']   = $verification['ok'];
			$status['sizes']             = array(
				'db'    => $manifest['files']['db']['size'],
				'files' => $manifest['files']['files']['size'],
			);
			$status['retention_deleted'] = $deleted;
			self::write_status( $status );

			CRB_Log::log(
				sprintf(
					'Backup %s completado. Verificación: %s. Borrados por retención: %d.',
					$stamp,
					$verification['ok'] ? 'OK' : 'CON ADVERTENCIAS',
					count( $deleted )
				)
			);
		} catch ( Exception $e ) {
			$status                = self::status();
			$status['state']       = 'failed';
			$status['step']        = 'failed';
			$status['message']     = 'El backup falló.';
			$status['error']       = $e->getMessage();
			$status['finished_at'] = current_time( 'mysql' );
			self::write_status( $status );

			CRB_Log::log( 'ERROR en backup ' . $stamp . ': ' . $e->getMessage(), 'ERROR' );
		}
	}

	/**
	 * Entrada del proceso worker: valida el token y ejecuta el trabajo.
	 *
	 * @param string $token Token de autorización recibido por CLI.
	 * @return void
	 */
	public static function run_worker( $token ) {
		$status = self::status();

		if ( empty( $status['token'] ) || $status['token'] !== $token || 'running' !== ( isset( $status['state'] ) ? $status['state'] : '' ) ) {
			fwrite( STDERR, "Solicitud de trabajo inválida.\n" );
			exit( 1 );
		}

		self::run_backup();
	}

	/**
	 * Callback del evento programado: lanza el backup y rearma la próxima cita.
	 *
	 * @return void
	 */
	public static function cron_backup() {
		$result = self::start_backup();

		if ( is_wp_error( $result ) ) {
			CRB_Log::log( 'Backup programado omitido: ' . $result->get_error_message(), 'WARN' );
		}

		// Rearma la próxima cita para que la hora no se desplace con el tráfico.
		CRB_Plugin::reschedule();
	}

	/**
	 * Actualiza solo el paso actual del trabajo en el archivo de estado.
	 *
	 * @param string $step    Código del paso.
	 * @param string $message Texto legible del paso.
	 * @return void
	 */
	private static function step( $step, $message ) {
		$status          = self::status();
		$status['step']  = $step;
		$status['message'] = $message;
		self::write_status( $status );
	}

	/**
	 * Comprueba si un proceso sigue vivo en el sistema.
	 *
	 * @param int $pid Identificador del proceso.
	 * @return bool
	 */
	public static function pid_alive( $pid ) {
		if ( intval( $pid ) <= 0 || ! function_exists( 'posix_kill' ) ) {
			return false;
		}

		return posix_kill( intval( $pid ), 0 );
	}

	/**
	 * Indica si una carpeta queda dentro del directorio de WordPress.
	 *
	 * @param string $dir Carpeta a comprobar.
	 * @return bool
	 */
	private static function inside_abspath( $dir ) {
		$abspath = untrailingslashit( ABSPATH );

		return 0 === strpos( $dir . '/', $abspath . '/' );
	}

	/**
	 * Borra restos de un intento anterior con la misma marca de tiempo.
	 *
	 * @param string $dir   Carpeta de backups.
	 * @param string $stamp Marca de tiempo.
	 * @return void
	 */
	private static function remove_stamp_leftovers( $dir, $stamp ) {
		// Formato nuevo: la carpeta completa del sello.
		$folder = $dir . '/' . $stamp;

		if ( is_dir( $folder ) ) {
			CRB_Retention::remove_tree( $folder );
		}

		// Compatibilidad: archivos planos de versiones anteriores.
		$files = array(
			$dir . '/db_' . $stamp . '.sql.gz',
			$dir . '/files_' . $stamp . '.tar.gz',
			$dir . '/manifest_' . $stamp . '.json',
		);

		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				@unlink( $file );
			}
		}
	}
}
