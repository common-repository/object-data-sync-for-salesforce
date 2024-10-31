<?php
/**
 * Dynamically loads the class attempting to be instantiated elsewhere in the
 * plugin.
 *
 * @package object-data-sync-for-salesforce\vendor
 */

spl_autoload_register( 'mo_sf_sync_autoloader' );

/**
 * Dynamically loads the class attempting to be instantiated elsewhere in the
 * plugin by looking at the $class_name parameter being passed as an argument.
 *
 * The namespaces in this plugin map to the paths in the directory structure.
 *
 * @param string $class_name The fully-qualified name of the class to load.
 */
function mo_sf_sync_autoloader( $class_name ) {

	if ( false === strpos( $class_name, 'MoSfSyncSalesforce' ) ) {
		return;
	}
	$file_parts = explode( '\\', $class_name );

	$namespace = '';
	$file_name = '';
	for ( $i = count( $file_parts ) - 1; $i > 0; $i-- ) {

		$current = $file_parts[ $i ];
		$current = str_ireplace( '_', '-', $current );

		if ( count( $file_parts ) - 1 === $i ) {

			if ( strpos( strtolower( $file_parts[ count( $file_parts ) - 1 ] ), 'interface' ) ) {

				$interface_name = explode( '_', $file_parts[ count( $file_parts ) - 1 ] );
				$interface_name = strtolower( $interface_name[0] );

				$file_name = "interface-$interface_name.php";

			} else {
				$current   = strtolower( $current );
				$file_name = "class-$current.php";
			}
		} else {
			$namespace = '/' . $current . $namespace;
		}
	}

	$filepath  = trailingslashit( dirname( dirname( __FILE__ ) ) . $namespace );
	$filepath .= $file_name;

	if ( file_exists( $filepath ) ) {
		include_once $filepath;
	} else {
		wp_die(
			esc_html( "The file attempting to be loaded at $filepath does not exist." )
		);
	}
}
