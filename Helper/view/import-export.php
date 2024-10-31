<?php
/**
 * This file handles the import and export configurations of the plugin.
 *
 * @package object-data-sync-for-salesforce\Helper\view
 */

add_action( 'admin_init', 'mo_sf_sync_import_export' );

use MoSfSyncSalesforce\Helper\Plugin_Constants;
use MoSfSyncSalesforce\Services\Utils;
use MoSfSyncSalesforce\Services\DB_Utils;

/**
 * Function that determines whether to keep the settings on deletion. Also for export, creates a new json data.
 * Handles the export and import plugin configuration processes.
 *
 * @return void
 */
function mo_sf_sync_import_export() {
	if ( array_key_exists( 'option', $_POST ) && 'mo_sf_sync_keep_settings_on_deletion' === $_POST['option'] && check_admin_referer( 'mo_sf_sync_keep_settings_on_deletion' ) ) {
		if ( array_key_exists( 'mo_sf_sync_keep_settings_on_deletion', $_POST ) ) {
			update_option( 'mo_sf_sync_keep_settings_on_deletion', 'true' );
		} else {
			update_option( 'mo_sf_sync_keep_settings_on_deletion', '' );
		}
	} elseif ( array_key_exists( 'option', $_POST ) && 'mo_sf_sync_export' === $_POST['option'] && check_admin_referer( 'mo_sf_sync_export' ) ) {
		$configuration_array                     = array();
		$mapping_object                          = get_option( 'mo_sf_sync_object' );
		$config_object                           = get_option( 'mo_sf_sync_config' );
		$provision_object                        = get_option( 'mo_sf_sync_provision' );
		$configuration_array['PROVISION_OBJECT'] = maybe_unserialize( $provision_object );
		$db                                      = DB_Utils::instance();
		$mapping_object                          = $db->mo_sf_sync_get_all_mapping_data();
		$configuration_array['MAPPING_OBJECT']   = $mapping_object;
		$configuration_array['CONFIG_OBJECT']    = maybe_unserialize( $config_object );
		$configuration_array['Version_dependencies'] = mo_sf_sync_get_version_information();
		$version                                     = phpversion();
		if ( substr( $version, 0, 3 ) === '5.3' ) {
			$json_string_escaped = ( wp_json_encode( $configuration_array, JSON_PRETTY_PRINT ) );
		} else {
			$json_string_escaped = ( wp_json_encode( $configuration_array, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
		}
		header( 'Content-Disposition: attachment; filename=miniorange-sf_sync-config.json' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- It was giving an improper file while escaping.
		echo ( $json_string_escaped );
		exit;

	} elseif ( array_key_exists( 'option', $_POST ) && 'mo_sf_sync_import' === $_POST['option'] ) {
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$file_type = isset( $_FILES['configuration_file']['type'] ) ? sanitize_text_field( wp_unslash( $_FILES['configuration_file']['type'] ) ) : '';
		$file_ext  = substr( $file_type, strpos( $file_type, '/' ) + 1 );
		if ( 'json' !== $file_ext ) {
			update_option( 'mo_sf_sync_message', 'Please upload a valid configuration file in .json format' );
			return;
		}
		if ( isset( $_FILES['configuration_file']['tmp_name'] ) && ! empty( $_FILES['configuration_file']['tmp_name'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents,WordPress.PHP.NoSilencedErrors.Discouraged -- Filename contains path and hence cannot be unslashed. 
			$file                = @file_get_contents( sanitize_text_field( $_FILES['configuration_file']['tmp_name'] ) );
			$configuration_array = json_decode( $file, true );
			mo_sf_sync_update_configuration_array( $configuration_array );
		}

		Utils::mo_sf_sync_show_success_message( 'Configuration import completed! Please Authorize again to connect to salesforce.' );
	}
}

/**
 * Function that provides the version information about the plugin.
 *
 * @return array
 */
function mo_sf_sync_get_version_information() {
	$array_version                      = array();
	$array_version['Plugin_version']    = Plugin_Constants::VERSION;
	$array_version['PHP_version']       = phpversion();
	$array_version['Wordpress_version'] = get_bloginfo( 'version' );
	return $array_version;
}

/**
 * Function to update the configuration array in the database.
 *
 * @param array $configuration_array The configuration array that includes the configuration of the plugin.
 * @return void
 */
function mo_sf_sync_update_configuration_array( $configuration_array ) {
	$db = DB_Utils::instance();
	if ( isset( $configuration_array['MAPPING_OBJECT'] ) ) {
		$db->mo_sf_sync_insert_imported_mapping_data( $configuration_array['MAPPING_OBJECT'] );
	}
	if ( isset( $configuration_array['PROVISION_OBJECT'] ) ) {
		update_option( 'mo_sf_sync_provision', $configuration_array['PROVISION_OBJECT'] );
	}
	if ( isset( $configuration_array['CONFIG_OBJECT'] ) ) {
		update_option( 'mo_sf_sync_config', $configuration_array['CONFIG_OBJECT'] );
	}
}
