<?php
/**
 * Tests de regresión de retención: remove_tree no debe seguir enlaces
 * simbólicos en la raíz ni el patrón de sello aceptar saltos de línea.
 * Ejecutar desde la carpeta del plugin: php tests/test-retention.php
 */

// Stubs mínimos para cargar la clase sin WordPress.
define( 'ABSPATH', __DIR__ . '/..' );
function untrailingslashit( $s ) { return rtrim( $s, '/\\' ); }

require_once __DIR__ . '/../includes/class-crb-retention.php';

$failures = 0;
$tmp      = sys_get_temp_dir() . '/crb-retention-test-' . getmypid();

mkdir( $tmp . '/backups', 0777, true );
mkdir( $tmp . '/victim/sub', 0777, true );
file_put_contents( $tmp . '/victim/important.txt', 'KEEP' );
file_put_contents( $tmp . '/victim/sub/keep.txt', 'KEEP' );

// Caso 1 (enlace simbólico raíz): remove_tree debe rechazarlo sin tocar el destino.
symlink( $tmp . '/victim', $tmp . '/backups/2026-11-07_1523' );
CRB_Retention::remove_tree( $tmp . '/backups/2026-11-07_1523' );

if ( ! is_file( $tmp . '/victim/important.txt' ) || ! is_file( $tmp . '/victim/sub/keep.txt' ) ) {
	echo "FAIL 1: remove_tree siguió el enlace simbólico raíz y borró el destino\n";
	$failures++;
}

// Caso 2 (carpeta real): una carpeta propia del plugin sí debe borrarse entera.
mkdir( $tmp . '/backups/2026-11-06_0900' );
file_put_contents( $tmp . '/backups/2026-11-06_0900/db_2026-11-06_0900.sql.gz', 'x' );
CRB_Retention::remove_tree( $tmp . '/backups/2026-11-06_0900' );

if ( is_dir( $tmp . '/backups/2026-11-06_0900' ) ) {
	echo "FAIL 2: remove_tree no borró la carpeta real\n";
	$failures++;
}

// Caso 3 (patrón de sello): con el modificador D rechaza saltos de línea finales.
if ( 1 === preg_match( '/^\d{4}-\d{2}-\d{2}_\d{4}$/D', "2026-11-07_1523\n" ) ) {
	echo "FAIL 3: el patrón acepta un salto de línea final (falta el modificador D)\n";
	$failures++;
}

echo ( 0 === $failures ) ? "OK: 3/3 casos de regresión pasan\n" : "FALLARON $failures casos\n";

exit( 0 === $failures ? 0 : 1 );
