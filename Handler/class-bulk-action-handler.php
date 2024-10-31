<?php
/**
 *  This file is responsible for handling bulk sync actions.
 *
 * @package object-data-sync-for-salesforce\Handler
 */

namespace MoSfSyncSalesforce\Handler;

/**
 * This class is responsible for handling bulk sync actions.
 */
class Bulk_Action_Handler {

	/**
	 *
	 * Constructor of the class.
	 */
	public function __construct() {
		add_filter( 'handle_bulk_actions-users', array( $this, 'mo_sf_sync_bulk_action_handler' ), 10, 3 );
		add_filter( 'handle_bulk_actions-edit-post', array( $this, 'mo_sf_sync_bulk_action_handler' ), 9, 3 );
		add_action( 'admin_notices', array( $this, 'mo_sf_sync_bulk_action_notice' ) );
	}

	/**
	 *
	 * Shows notice after the completion of bulk sync action.
	 *
	 * @return void.
	 */
	public function mo_sf_sync_bulk_action_notice() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verification not required, as it is already done by WordPress because it is picked after WordPress default form submission.
		if ( ! empty( $_REQUEST['sync_user_to_sf'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verification not required, as it is already done by WordPress because it is picked after WordPress default form submission.
			$num_changed = (int) sanitize_text_field( wp_unslash( $_REQUEST['sync_user_to_sf'] ) );
			$base_url    = get_option( 'siteurl' );
			$url         = $base_url . '/wp-admin/users.php';
			printf(
				'<div id="message" class="updated" ><p  style="display:inline-block;position:relative;margin-right: 57.5rem;min-width: 333px;"> %d Users will be synced to Salesforce. </p><a href="%s"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6" style="width: 15px;"> <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg></a></div>',
				esc_attr( $num_changed ),
				esc_attr( $url )
			);
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verification not required, as it is already done by WordPress because it is picked after WordPress default form submission.
		} elseif ( ! empty( $_REQUEST['sync_post_to_sf'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verification not required, as it is already done by WordPress because it is picked after WordPress default form submission.
			$num_changed = (int) sanitize_text_field( wp_unslash( $_REQUEST['sync_post_to_sf'] ) );
			$base_url    = get_option( 'siteurl' );
			$url         = $base_url . '/wp-admin/edit.php';
			printf(
				'<div id="message" class="updated" ><p  style="display:inline-block;position:relative;margin-right: 57.5rem;min-width: 333px;"> %d Posts will be synced to Salesforce.</p><a href="%s"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6" style="width: 15px; "  >    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg></a></div>',
				esc_attr( $num_changed ),
				esc_attr( $url )
			);
		}
	}

	/**
	 *
	 * Handles the bulk sync operation.
	 *
	 * @param string $redirect_url url to be redirected to.
	 * @param string $action the sync action that is been taken.
	 * @param array  $ids WordPress record ids.
	 * @return string
	 */
	public function mo_sf_sync_bulk_action_handler( $redirect_url, $action, $ids ) {

		global $pagenow;
		if ( 'sync_user_to_sf' !== $action && 'sync_post_to_sf' !== $action ) {
			return $redirect_url;
		}
		$data_processing_handler = Data_Processing_Handler::instance();
		$ids_count               = count( $ids );
		if ( 'users.php' === $pagenow ) {

			if ( $ids_count > 1 ) {
				$data_processing_handler->mo_sf_sync_composite_call_handler( $ids );
			} else {
				$data_processing_handler->mo_sf_sync_push_to_salesforce( $ids[0] );
			}

			$redirect_to = add_query_arg( 'sync_user_to_sf', $ids_count, $redirect_url );
		} else {

			if ( $ids_count > 1 ) {
				$data_processing_handler->mo_sf_sync_composite_call_handler( $ids, 'post' );
			} else {
				$data_processing_handler->mo_sf_sync_push_to_salesforce( $ids[0], 'post' );
			}

			$redirect_to = add_query_arg( 'sync_post_to_sf', $ids_count, $redirect_url );
		}

		return $redirect_to;
	}
}


