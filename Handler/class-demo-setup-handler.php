<?php
/**
 * This file handles the demo setup requests.
 *
 * @package object-data-sync-for-salesforce\Handler
 */

namespace MoSfSyncSalesforce\Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MoSfSyncSalesforce\Customer;
use MoSfSyncSalesforce\Services\Utils;
use MoSfSyncSalesforce\Helper\Plugin_Constants;

/**
 * This class is responsible for handling the demo setup requests.
 */
class Demo_Setup_Handler {
	/**
	 * Variable that stores all the integrations.
	 *
	 * @var array
	 */
	public static $integration_title = Plugin_Constants::INTEGRATIONS_TITLE;

	/**
	 * Handles the demo setup requests.
	 *
	 * @return void
	 */
	public function mo_sf_sync_request_demo() {

		if ( isset( $_POST['option'] ) && 'mo_sf_sync_demo_setup' === $_POST['option'] && check_admin_referer( 'mo_sf_sync_demo_setup' ) ) {
			if ( isset( $_POST['demo_email'] ) ) {
				$demo_email = sanitize_email( wp_unslash( $_POST['demo_email'] ) );
			} else {
				wp_send_json_error( 'Empty email or query' );
			}
			if ( isset( $_POST['demo_description'] ) ) {
				$demo_description = sanitize_textarea_field( wp_unslash( $_POST['demo_description'] ) );
			}

			$addons_selected = array();
			$addons          = self::$integration_title;
			foreach ( $addons as $key => $value ) {

				if ( isset( $_POST[ $key ] ) && 'true' === $_POST[ $key ] ) {
					$addons_selected[ $key ] = $value;
				}
			}

			$integrations_selected = implode( ', ', array_values( $addons_selected ) );

			if ( empty( $demo_email ) ) {
				wp_send_json_error( 'Empty email or query' );
			} else {
				$demo_setup = new Customer();
				$response   = $demo_setup->mo_sf_sync_submit_contact_us( $demo_email, '', $demo_description, false, true, $integrations_selected );

				if ( 'Query submitted.' === $response ) {
					$demo_request_content = array(
						'user_email' => $demo_email,
						'user_query' => $demo_description,
					);
					Utils::mo_sf_sync_save_settings( Plugin_Constants::DEMO_REQUEST_CONTENT, $demo_request_content );
					Utils::mo_sf_sync_show_success_message( Plugin_Constants::DEMO_REQUEST_SUCCESS );
				} else {
					Utils::mo_sf_sync_show_error_message( $response );
				}
			}
		}
	}

}
