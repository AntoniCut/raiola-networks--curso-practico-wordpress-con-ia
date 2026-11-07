<?php
/**
 * Página de administración del plugin Cafetería Raiola Backup.
 * Menú "Copias de seguridad" con estado, creación, listado, programación,
 * guía de restauración y log. Las acciones de creación y borrado van por AJAX.
 */

// Impide el acceso directo al archivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase CRB_Admin: menú, página y puntos de entrada AJAX.
 */
class CRB_Admin {

	/**
	 * Slug de la página del menú.
	 */
	const PAGE = 'crb-backup';

	/**
	 * Registra menú, estilos y acciones de administración.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_crb_save_settings', array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_post_crb_download_backup', array( 'CRB_Download', 'handle' ) );
		add_action( 'admin_post_crb_download_package', array( 'CRB_Download', 'package' ) );
		add_action( 'wp_ajax_crb_start_backup', array( __CLASS__, 'ajax_start_backup' ) );
		add_action( 'wp_ajax_crb_get_status', array( __CLASS__, 'ajax_get_status' ) );
		add_action( 'wp_ajax_crb_delete_backup', array( __CLASS__, 'ajax_delete_backup' ) );
	}

	/**
	 * Añade la página al menú de administración.
	 *
	 * @return void
	 */
	public static function menu() {
		add_menu_page(
			'Copias de seguridad',
			'Copias de seguridad',
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render' ),
			'dashicons-backup',
			80
		);
	}

	/**
	 * Carga estilos y scripts solo en la página del plugin.
	 *
	 * @param string $hook Página actual del admin.
	 * @return void
	 */
	public static function assets( $hook ) {
		if ( 'toplevel_page_' . self::PAGE !== $hook ) {
			return;
		}

		wp_enqueue_style( 'crb-admin', CRB_PLUGIN_URL . 'assets/css/admin.css', array(), CRB_VERSION );
		wp_enqueue_script( 'crb-admin', CRB_PLUGIN_URL . 'assets/js/admin.js', array(), CRB_VERSION, true );

		wp_localize_script(
			'crb-admin',
			'CRB_JS',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'crb_backup' ),
				'statusRunning' => self::is_running(),
				'downloadBase'  => add_query_arg(
					array(
						'action'   => 'crb_download_backup',
						'_wpnonce' => wp_create_nonce( 'crb_download' ),
					),
					admin_url( 'admin-post.php' )
				),
				'packageBase'   => add_query_arg(
					array(
						'action'   => 'crb_download_package',
						'_wpnonce' => wp_create_nonce( 'crb_download' ),
					),
					admin_url( 'admin-post.php' )
				),
			)
		);
	}

	/**
	 * Indica si hay un backup en curso ahora mismo.
	 *
	 * @return bool
	 */
	private static function is_running() {
		$status = CRB_Job::status();

		return isset( $status['state'] ) && 'running' === $status['state'];
	}

	/**
	 * Guarda los ajustes del formulario y vuelve a la página.
	 *
	 * @return void
	 */
	public static function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No tienes permisos para cambiar los ajustes.' );
		}

		check_admin_referer( 'crb_save_settings' );

		$input = isset( $_POST['crb'] ) ? (array) $_POST['crb'] : array();

		CRB_Settings::update( CRB_Settings::sanitize( $input ) );
		CRB_Plugin::reschedule();
		CRB_Log::log( 'Ajustes actualizados.' );

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE . '&updated=1' ) );
		exit;
	}

	/**
	 * AJAX: lanza un backup en segundo plano.
	 *
	 * @return void
	 */
	public static function ajax_start_backup() {
		check_ajax_referer( 'crb_backup' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'No tienes permisos.' ), 403 );
		}

		$result = CRB_Job::start_backup();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX: devuelve el estado actual del trabajo en curso.
	 *
	 * @return void
	 */
	public static function ajax_get_status() {
		check_ajax_referer( 'crb_backup' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'No tienes permisos.' ), 403 );
		}

		wp_send_json_success( CRB_Job::status() );
	}

	/**
	 * AJAX: borra un backup completo (db + archivos + manifiesto).
	 *
	 * @return void
	 */
	public static function ajax_delete_backup() {
		check_ajax_referer( 'crb_backup' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'No tienes permisos.' ), 403 );
		}

		$stamp = isset( $_POST['stamp'] ) ? sanitize_text_field( wp_unslash( $_POST['stamp'] ) ) : '';

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}_\d{4}$/D', $stamp ) ) {
			wp_send_json_error( array( 'message' => 'Marca de tiempo inválida.' ) );
		}

		$dir    = CRB_Job::dir();
		$deleted = 0;

		// Formato nuevo: borra la carpeta completa del backup.
		$folder = $dir . '/' . $stamp;

		if ( is_dir( $folder ) ) {
			$deleted += CRB_Retention::remove_tree( $folder ) ? 1 : 0;
		}

		// Compatibilidad: archivos planos de versiones anteriores.
		$files  = array(
			$dir . '/db_' . $stamp . '.sql.gz',
			$dir . '/files_' . $stamp . '.tar.gz',
			$dir . '/manifest_' . $stamp . '.json',
		);

		foreach ( $files as $file ) {
			if ( is_file( $file ) && @unlink( $file ) ) {
				$deleted++;
			}
		}

		CRB_Log::log( 'Backup ' . $stamp . ' borrado desde el administrador (' . $deleted . ' archivos).' );

		wp_send_json_success( array( 'deleted' => $deleted ) );
	}

	/**
	 * Dibuja la página completa de copias de seguridad.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = CRB_Settings::get();
		$dir      = untrailingslashit( $settings['backup_dir'] );
		$backups  = self::list_backups( $dir );
		$running  = self::is_running();
		$status   = CRB_Job::status();
		?>
		<div class="wrap crb-wrap">
			<h1>Copias de seguridad</h1>
			<p class="crb-intro">
				Regla de oro: un backup no está terminado hasta que existe <strong>fuera del servidor</strong>.
				Cada copia reúne las dos partes juntas: la base de datos y los archivos del sitio.
			</p>

			<?php self::notices( $settings, $dir, $running, $status ); ?>

			<?php self::render_server_card( $dir ); ?>
			<?php self::render_create_card( $running, $status ); ?>
			<?php self::render_backups_card( $backups, $running ); ?>
			<?php self::render_settings_card( $settings ); ?>
			<?php self::render_restore_card(); ?>
			<?php self::render_log_card(); ?>
		</div>
		<?php
	}

	/**
	 * Avisos de estado: carpeta, última descarga, guardado y fallos.
	 *
	 * @param array  $settings Ajustes actuales.
	 * @param string $dir      Carpeta de backups.
	 * @param bool   $running  Si hay un backup en curso.
	 * @param array  $status   Estado del último trabajo.
	 * @return void
	 */
	private static function notices( $settings, $dir, $running, $status ) {
		// Aviso si la carpeta de backups no está lista.
		$state = self::dir_state( $dir );

		if ( 'ok' !== $state ) {
			echo '<div class="notice notice-error"><p><strong>La carpeta de backups no está lista.</strong> ';
			echo 'Ejecuta una vez por SSH (fuera de la terminal del navegador):<br>';
			echo '<code class="crb-code">sudo mkdir -p ' . esc_html( $dir ) . ' &amp;&amp; sudo chown www-data:www-data ' . esc_html( $dir ) . '</code>';
			echo '</p></div>';
		}

		// Aviso 3-2-1: hace cuánto no se descarga un backup al equipo.
		$last_download = (int) get_option( 'crb_last_download', 0 );

		if ( 0 === $last_download ) {
			echo '<div class="notice notice-warning"><p><strong>Regla 3-2-1:</strong> todavía no descargaste ningún backup a tu equipo. Un backup que solo vive en el servidor no te salva de una caída del VPS.</p></div>';
		} elseif ( ( time() - $last_download ) > intval( $settings['download_warning_days'] ) * DAY_IN_SECONDS ) {
			echo '<div class="notice notice-warning"><p><strong>Regla 3-2-1:</strong> hace más de ' . esc_html( $settings['download_warning_days'] ) . ' días que no descargás un backup fuera del servidor. Bajate el último cuando puedas.</p></div>';
		}

		// Confirmación de guardado de ajustes.
		if ( isset( $_GET['updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>Ajustes guardados.</p></div>';
		}

		// Fallo del último trabajo.
		if ( ! $running && isset( $status['state'] ) && 'failed' === $status['state'] ) {
			echo '<div class="notice notice-error"><p><strong>El último backup falló:</strong> ' . esc_html( $status['error'] ) . '</p></div>';
		}
	}

	/**
	 * Tarjeta con el estado del servidor y la carpeta de backups.
	 *
	 * @param string $dir Carpeta de backups.
	 * @return void
	 */
	private static function render_server_card( $dir ) {
		$state   = self::dir_state( $dir );
		$checks  = array(
			'PHP puede ejecutar comandos (exec)' => CRB_Shell::has_exec(),
			'mysqldump'                          => CRB_DB_Dump::available(),
			'tar'                                => CRB_Shell::command_exists( 'tar' ),
			'gzip'                               => CRB_Shell::command_exists( 'gzip' ),
			'PHP en línea de comandos'           => '' !== CRB_Shell::php_binary(),
		);
		?>
		<section class="crb-card">
			<h2>Estado del servidor</h2>
			<ul class="crb-checks">
				<?php foreach ( $checks as $label => $ok ) : ?>
					<li>
						<span class="crb-dot <?php echo $ok ? 'crb-dot-ok' : 'crb-dot-error'; ?>"></span>
						<?php echo esc_html( $label ); ?>
					</li>
				<?php endforeach; ?>
				<li>
					<span class="crb-dot <?php echo 'ok' === $state ? 'crb-dot-ok' : 'crb-dot-error'; ?>"></span>
					Carpeta de backups (<?php echo esc_html( $dir ); ?>)
				</li>
			</ul>

			<?php if ( 'ok' !== $state ) : ?>
				<p class="crb-help">
					PHP corre como usuario <code>www-data</code>, así que la carpeta debe crearla el administrador del VPS una sola vez:
				</p>
				<pre class="crb-code-block" id="crb-sudo-command">sudo mkdir -p <?php echo esc_html( $dir ); ?> &amp;&amp; sudo chown www-data:www-data <?php echo esc_html( $dir ); ?></pre>
				<p>
					<button type="button" class="button" id="crb-copy-command" data-copy-target="crb-sudo-command">Copiar comando</button>
				</p>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * Tarjeta con el botón de crear backup y el progreso en vivo.
	 *
	 * @param bool  $running Si hay un backup en curso.
	 * @param array $status  Estado del último trabajo.
	 * @return void
	 */
	private static function render_create_card( $running, $status ) {
		?>
		<section class="crb-card">
			<h2>Crear backup</h2>
			<p class="crb-help">
				Ideal antes de cualquier cambio riesgoso (actualizaciones, tocar el tema, migraciones).
				El proceso corre en segundo plano; podés seguir usando WordPress.
			</p>
			<p>
				<button type="button" class="button button-primary button-hero" id="crb-start-backup" <?php disabled( $running ); ?>>
					<?php echo $running ? 'Backup en curso…' : 'Crear backup ahora'; ?>
				</button>
			</p>
			<p id="crb-progress" class="crb-progress <?php echo $running ? '' : 'hidden'; ?>" role="status">
				<span class="spinner is-active"></span>
				<span id="crb-progress-text"><?php echo esc_html( $status['message'] ?? '' ); ?></span>
			</p>
			<p id="crb-result" class="hidden" role="status"></p>
		</section>
		<?php
	}

	/**
	 * Tarjeta con la tabla de backups existentes.
	 *
	 * @param array $backups Lista de backups agrupados por marca.
	 * @param bool  $running Si hay un backup en curso.
	 * @return void
	 */
	private static function render_backups_card( $backups, $running ) {
		?>
		<section class="crb-card">
			<h2>Backups en el servidor</h2>
			<?php if ( empty( $backups ) ) : ?>
				<p class="crb-help">Todavía no hay backups. Creá el primero con el botón de arriba.</p>
			<?php else : ?>
				<p class="crb-help">
					<strong>Descargar a carpeta</strong> abre el selector de carpetas del navegador (Chrome o Edge)
					y guarda ahí la copia completa, en una subcarpeta con su fecha. En Firefox o Safari
					baja un único archivo combinado.
				</p>
				<table class="widefat striped crb-table">
					<thead>
						<tr>
							<th>Fecha</th>
							<th>Base de datos</th>
							<th>Archivos</th>
							<th>Verificación</th>
							<th>Acciones</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $backups as $backup ) : ?>
							<tr>
								<td><?php echo esc_html( $backup['date'] ); ?></td>
								<td><?php echo esc_html( $backup['db_size'] ); ?></td>
								<td><?php echo esc_html( $backup['files_size'] ); ?></td>
								<td><?php echo self::verification_badge( $backup['verification'] ); ?></td>
								<td>
									<button type="button" class="button button-small crb-download-folder" data-stamp="<?php echo esc_attr( $backup['stamp'] ); ?>">Descargar a carpeta</button>
									<a class="button button-small" href="<?php echo esc_url( self::download_url( 'db', $backup['stamp'] ) ); ?>">Descargar BD</a>
									<a class="button button-small" href="<?php echo esc_url( self::download_url( 'files', $backup['stamp'] ) ); ?>">Descargar archivos</a>
									<?php if ( $backup['manifest'] ) : ?>
										<a class="button button-small" href="<?php echo esc_url( self::download_url( 'manifest', $backup['stamp'] ) ); ?>">Manifiesto</a>
									<?php endif; ?>
									<button type="button" class="button button-small crb-delete" data-stamp="<?php echo esc_attr( $backup['stamp'] ); ?>" <?php disabled( $running ); ?>>Borrar</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * Tarjeta con el formulario de programación y ajustes.
	 *
	 * @param array $settings Ajustes actuales.
	 * @return void
	 */
	private static function render_settings_card( $settings ) {
		$weekdays = array(
			1 => 'Lunes',
			2 => 'Martes',
			3 => 'Miércoles',
			4 => 'Jueves',
			5 => 'Viernes',
			6 => 'Sábado',
			7 => 'Domingo',
		);
		?>
		<section class="crb-card">
			<h2>Programación</h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="crb-settings-form">
				<input type="hidden" name="action" value="crb_save_settings">
				<?php wp_nonce_field( 'crb_save_settings' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="crb-schedule">Frecuencia</label></th>
						<td>
							<select name="crb[schedule]" id="crb-schedule">
								<option value="daily" <?php selected( $settings['schedule'], 'daily' ); ?>>Diaria</option>
								<option value="weekly" <?php selected( $settings['schedule'], 'weekly' ); ?>>Semanal</option>
								<option value="disabled" <?php selected( $settings['schedule'], 'disabled' ); ?>>Desactivada</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="crb-schedule-time">Hora</label></th>
						<td>
							<input type="time" name="crb[schedule_time]" id="crb-schedule-time" value="<?php echo esc_attr( $settings['schedule_time'] ); ?>">
							<p class="crb-help">Hora local del sitio (Europe/Madrid).</p>
						</td>
					</tr>
					<tr id="crb-weekday-row" class="<?php echo 'weekly' === $settings['schedule'] ? '' : 'hidden'; ?>">
						<th scope="row"><label for="crb-weekday">Día de la semana</label></th>
						<td>
							<select name="crb[weekday]" id="crb-weekday">
								<?php foreach ( $weekdays as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['weekday'], $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="crb-retention-days">Retención (días)</label></th>
						<td>
							<input type="number" min="1" max="365" name="crb[retention_days]" id="crb-retention-days" value="<?php echo esc_attr( $settings['retention_days'] ); ?>">
							<p class="crb-help">Los backups más antiguos que este límite se borran solos.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="crb-download-warning-days">Aviso de descarga (días)</label></th>
						<td>
							<input type="number" min="1" max="365" name="crb[download_warning_days]" id="crb-download-warning-days" value="<?php echo esc_attr( $settings['download_warning_days'] ); ?>">
							<p class="crb-help">Si no descargás un backup en este plazo, el plugin te avisa (regla 3-2-1).</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="crb-exclude">Excluir del tar</label></th>
						<td>
							<textarea name="crb[exclude][]" id="crb-exclude" rows="4" class="large-text code"><?php echo esc_textarea( implode( "\n", $settings['exclude'] ) ); ?></textarea>
							<p class="crb-help">Una ruta relativa por línea, sin barra inicial (ej. wp-content/cache).</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="crb-backup-dir">Carpeta de backups</label></th>
						<td>
							<input type="text" name="crb[backup_dir]" id="crb-backup-dir" value="<?php echo esc_attr( $settings['backup_dir'] ); ?>" class="regular-text code">
							<p class="crb-help">Debe quedar <strong>fuera</strong> del directorio del sitio. Cambiarla no mueve los backups existentes.</p>
						</td>
					</tr>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary">Guardar ajustes</button>
				</p>
			</form>

			<p class="crb-help">
				El cron de WordPress se ejecuta cuando el sitio recibe visitas. Si el sitio tiene poco tráfico,
				apuntá un cron real del sistema a <code>wp-cron.php</code>, por ejemplo cada 5 minutos:
			</p>
			<pre class="crb-code-block">*/5 * * * * wget -qO- https://cafeteria-raiola.antonydev.tech/wp-cron.php?doing_wp_cron &gt; /dev/null</pre>
		</section>
		<?php
	}

	/**
	 * Tarjeta con la guía resumida de restauración por SSH.
	 *
	 * @return void
	 */
	private static function render_restore_card() {
		?>
		<section class="crb-card">
			<h2>Cómo restaurar un backup</h2>
			<p class="crb-help">
				Antes de restaurar, <strong>creá un backup del estado actual</strong>: siempre se rompe algo cuando no hay marcha atrás.
				La restauración se hace por SSH porque necesita permisos de administrador del servidor.
			</p>
			<ol class="crb-steps">
				<li>Copiá los archivos <code>db_STAMP.sql.gz</code> y <code>files_STAMP.tar.gz</code> al VPS (por <code>scp</code>) si los tenías en tu equipo. En el servidor viven dentro de una carpeta con el mismo sello, por ejemplo <code>2026-11-07_1523/</code>.</li>
				<li>Restaurá los archivos:
					<pre class="crb-code-block">sudo tar -xzf files_STAMP.tar.gz -C /var/www/cafeteria-raiola
sudo chown -R www-data:www-data /var/www/cafeteria-raiola</pre>
				</li>
				<li>Restaurá la base de datos:
					<pre class="crb-code-block">sudo zcat db_STAMP.sql.gz | mysql --defaults-extra-file=/root/.backup-my.cnf cafeteria_raiola</pre>
				</li>
				<li>Después de restaurar: abrí el sitio y comprobá que carga; guardá en <em>Ajustes &gt; Enlaces permanentes</em>; vaciá la caché si la hay.</li>
			</ol>
		</section>
		<?php
	}

	/**
	 * Tarjeta con las últimas líneas del log.
	 *
	 * @return void
	 */
	private static function render_log_card() {
		$lines = CRB_Log::recent( 50 );
		?>
		<section class="crb-card">
			<h2>Log de actividad</h2>
			<?php if ( empty( $lines ) ) : ?>
				<p class="crb-help">Todavía no hay actividad registrada.</p>
			<?php else : ?>
				<pre class="crb-log"><?php echo esc_html( implode( "\n", $lines ) ); ?></pre>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * Comprueba el estado de la carpeta de backups.
	 *
	 * @param string $dir Carpeta a comprobar.
	 * @return string ok | missing | not_writable
	 */
	private static function dir_state( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return 'missing';
		}

		if ( ! is_writable( $dir ) ) {
			return 'not_writable';
		}

		return 'ok';
	}

	/**
	 * Construye la URL de descarga de un archivo de backup.
	 *
	 * @param string $type  Tipo: db, files o manifest.
	 * @param string $stamp Marca de tiempo del backup.
	 * @return string
	 */
	private static function download_url( $type, $stamp ) {
		return add_query_arg(
			array(
				'action'   => 'crb_download_backup',
				'type'     => $type,
				'stamp'    => $stamp,
				'_wpnonce' => wp_create_nonce( 'crb_download' ),
			),
			admin_url( 'admin-post.php' )
		);
	}

	/**
	 * Lista los backups de la carpeta agrupados por marca de tiempo.
	 *
	 * @param string $dir Carpeta de backups.
	 * @return array
	 */
	private static function list_backups( $dir ) {
		$backups = array();

		if ( ! is_dir( $dir ) ) {
			return $backups;
		}

		$db_files = array();

		// Formato nuevo: cada backup vive en su carpeta con el sello.
		$nested = glob( $dir . '/*/db_*.sql.gz' );

		if ( is_array( $nested ) ) {
			$db_files = array_merge( $db_files, $nested );
		}

		// Compatibilidad: archivos planos de la v1.0.0 en la raíz.
		$flat = glob( $dir . '/db_*.sql.gz' );

		if ( is_array( $flat ) ) {
			$db_files = array_merge( $db_files, $flat );
		}

		foreach ( $db_files as $db_file ) {
			$base  = basename( $db_file );
			$stamp = substr( $base, 3, -7 ); // Quita db_ y .sql.gz.

			// La carpeta contenedora: la del sello o la raíz (v1.0.0).
			$folder     = dirname( $db_file );
			$files_file = $folder . '/files_' . $stamp . '.tar.gz';
			$manifest   = $folder . '/manifest_' . $stamp . '.json';

			$verification = 'unknown';
			$has_manifest = is_file( $manifest );

			if ( $has_manifest ) {
				$data = json_decode( (string) file_get_contents( $manifest ), true );

				if ( is_array( $data ) && isset( $data['verification_ok'] ) ) {
					$verification = $data['verification_ok'] ? 'ok' : 'failed';
				}
			}

			$date = DateTime::createFromFormat( 'Y-m-d_Hi', $stamp, wp_timezone() );

			$backups[] = array(
				'stamp'        => $stamp,
				'date'         => $date ? $date->format( 'd/m/Y H:i' ) : $stamp,
				'db_size'      => is_file( $db_file ) ? size_format( filesize( $db_file ) ) : '—',
				'files_size'   => is_file( $files_file ) ? size_format( filesize( $files_file ) ) : '—',
				'verification' => $verification,
				'manifest'     => $has_manifest,
			);
		}

		// Más reciente primero.
		usort(
			$backups,
			static function ( $a, $b ) {
				return strcmp( $b['stamp'], $a['stamp'] );
			}
		);

		return $backups;
	}

	/**
	 * Devuelve el HTML de la insignia de verificación.
	 *
	 * @param string $verification ok | failed | unknown.
	 * @return string HTML escapado.
	 */
	private static function verification_badge( $verification ) {
		if ( 'ok' === $verification ) {
			return '<span class="crb-badge crb-badge-ok">Verificado</span>';
		}

		if ( 'failed' === $verification ) {
			return '<span class="crb-badge crb-badge-error">Falló la verificación</span>';
		}

		return '<span class="crb-badge">Sin manifiesto</span>';
	}
}
