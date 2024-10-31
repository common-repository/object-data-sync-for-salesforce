<?php
/**
 * Un-Installation of the Plugin.
 *
 * @package object-data-sync-for-salesforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once 'vendor/autoload.php';

use MoSfSyncSalesforce\Helper\Plugin_Constants;
use MoSfSyncSalesforce\Services\Utils;


if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}
if ( empty( get_option( 'mo_sf_sync_keep_settings_on_deletion' ) ) ) {
	delete_option( Plugin_Constants::CONFIG_OBJECT );
	delete_option( Plugin_Constants::MAPPING_OBJECT );
	delete_option( Plugin_Constants::PROVISION_OBJECT );
	delete_option( Plugin_Constants::SF_RESPONSE_OBJECT );
	delete_option( Plugin_Constants::AUDIT_LOGS );
	delete_option( Plugin_Constants::CONNECTION_TYPE );
	delete_transient( Plugin_Constants::MO_SF_SYNC_CONFIG_TRANSIENT );
	delete_transient( Plugin_Constants::TRANSIENT_TIMEOUT_PLUGIN_ACTIVATED );
	delete_transient( Plugin_Constants::TRANSIENT_PLUGIN_ACTIVATED );
	delete_transient( Plugin_Constants::TRANSIENT_AUTHORIZATION_STATE );
	delete_transient( Plugin_Constants::TRANSIENT_LEAD_OBJECT );
	delete_transient( Plugin_Constants::TRANSIENT_TRIAL_REQUEST );
	delete_transient( Plugin_Constants::TRANSIENT_GET_OBJECT );
	delete_transient( Plugin_Constants::TRANSIENT_TRIAL_NOTICE );
	delete_transient( Plugin_Constants::TRANSIENT_INTEGRATION_NOTICE );
	Utils::mo_sf_sync_drop_table( 'mo_sf_sync_audit_log_db' );
	Utils::mo_sf_sync_drop_table( 'mo_sf_sync_object_mapping_meta' );
	Utils::mo_sf_sync_drop_table( 'mo_sf_sync_object_mapping' );
}
