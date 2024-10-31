<?php
/**
 * This file takes care of account creation or log in to the miniOrange account.
 *
 * @package object-data-sync-for-salesforce\handler
 */

namespace MoSfSyncSalesforce\Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MoSfSyncSalesforce\API\Customer_API;
use MoSfSyncSalesforce\Customer;
use MoSfSyncSalesforce\Helper\Instance;
use MoSfSyncSalesforce\Helper\Plugin_Constants;
use MoSfSyncSalesforce\Services\Utils;

/**
 * This class takes care of account creation or log in to the miniOrange account.
 */
class Account_Setup_Handler {
	use Instance;

	/**
	 * Creates an instance of the class.
	 *
	 * @return Account_Setup_Handler
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		Utils::mo_sf_sync_show_message();
		Utils::mo_sf_sync_show_feedback_message();
		return self::$instance;
	}

	/**
	 * Delegates the control flow to appropriate handler function as per the option in request.
	 *
	 * @return void
	 */
	public function mo_sf_sync_account_setup_controller() {
		if ( isset( $_POST['option'] ) ) {
			$option = sanitize_text_field( wp_unslash( $_POST['option'] ) );

			switch ( $option ) {
				case 'mo_sf_sync_account_setup_option':
					if ( check_admin_referer( 'mo_sf_sync_account_setup_option' ) ) {
						$this->mo_sf_sync_account_setup( $_POST );
					}
					break;
				case 'mo_sf_sync_remove_account_option':
					if ( check_admin_referer( 'mo_sf_sync_remove_account_option' ) ) {
						$this->mo_sf_sync_remove_account();
					}
					break;
				case 'mo_sf_sync_account_registration':
					if ( check_admin_referer( 'mo_sf_sync_account_registration' ) ) {
						$this->mo_sf_sync_account_registration_option( $_POST );
					}
					break;
			}
		}
	}

	/**
	 * Checks if an extension is installed.
	 *
	 * @param string $extension_name Name of the extension.
	 * @return int
	 */
	private function mo_sf_sync_is_extension_installed( $extension_name ) {
		if ( in_array( $extension_name, get_loaded_extensions(), true ) ) {
			return 1;
		} else {
			return 0;
		}
	}

	/**
	 * Handles user login in miniOrange.
	 *
	 * @param string $post_array Array of customer details.
	 * @return void
	 */
	private function mo_sf_sync_account_setup( $post_array ) {

		if ( ! $this->mo_sf_sync_is_extension_installed( 'curl' ) ) {
			Utils::mo_sf_sync_show_error_message( 'ERROR: PHP cURL extension is not installed or disabled. Login failed.' );
			return;
		}

		$email    = '';
		$password = '';
		if ( empty( $post_array['account_email'] ) || empty( $post_array['account_pwd'] ) ) {
			Utils::mo_sf_sync_show_error_message( 'All the fields are required. Please enter valid entries.' );
			return;
		} elseif ( Customer::mo_sf_sync_check_password_pattern( wp_strip_all_tags( $post_array['account_pwd'] ) ) ) {
			Utils::mo_sf_sync_show_error_message( 'Minimum 6 characters should be present. Maximum 15 characters should be present. Only following symbols (!@#.$%^&*-_) should be present.' );
			return;
		} else {
			$email    = sanitize_email( $post_array['account_email'] );
			$password = stripslashes( wp_strip_all_tags( $post_array['account_pwd'] ) );
		}

		update_option( 'mo_sf_sync_admin_email', $email );
		update_option( 'mo_sf_sync_admin_password', $password );
		$customer = new Customer_API();
		$content  = $customer->mo_sf_sync_get_customer_key();
		if ( ! $content ) {
			return;
		}

		$customer_key = json_decode( $content, true );

		if ( json_last_error() === JSON_ERROR_NONE ) {
			update_option( 'mo_sf_sync_admin_customer_key', $customer_key['id'] );
			update_option( 'mo_sf_sync_admin_api_key', $customer_key['apiKey'] );
			update_option( 'mo_sf_sync_customer_token', $customer_key['token'] );
			if ( ! empty( $customer_key['phone'] ) ) {
				update_option( 'mo_sf_sync_admin_phone', $customer_key['phone'] );
			}
			update_option( 'mo_sf_sync_admin_password', '' );
			update_option( 'mo_sf_sync_registration_status', Plugin_Constants::EXISTING_USER );
			delete_option( 'mo_sf_sync_verify_customer' );
			Utils::mo_sf_sync_show_success_message( 'Successfully Logged In' );

		} else {
			Utils::mo_sf_sync_show_error_message( 'Invalid username or password. Please try again.' );
		}

			update_option( 'mo_sf_sync_admin_password', '' );

	}

	/**
	 * Removes account related constants from db.
	 *
	 * @return void
	 */
	private function mo_sf_sync_remove_account() {
		Customer::mo_sf_sync_deactivate();
		add_option( 'mo_sf_sync_registration_status', Plugin_Constants::REMOVED_ACCOUNT );
	}

	/**
	 * Handles user registration in miniOrange.
	 *
	 * @param array $post_array Array of customer details.
	 * @return void
	 */
	private function mo_sf_sync_account_registration_option( $post_array ) {

		if ( ! $this->mo_sf_sync_is_extension_installed( 'curl' ) ) {
			Utils::mo_sf_sync_show_error_message( 'ERROR: PHP cURL extension is not installed or disabled. Login failed.' );
			return;
		}
		$email            = '';
		$password         = '';
		$confirm_password = '';

		if ( ( array_key_exists( 'account_email_reg', $post_array ) && empty( $post_array['account_email_reg'] ) ) || ( array_key_exists( 'reg_account_pwd', $post_array ) && empty( $post_array['reg_account_pwd'] ) ) || ( array_key_exists( 'confirm_account_pwd', $post_array ) && empty( $post_array['confirm_account_pwd'] ) ) ) {
			Utils::mo_sf_sync_show_error_message( 'All the fields are required. Please enter valid entries.' );
			return;
		} elseif ( Customer::mo_sf_sync_check_password_pattern( $post_array['reg_account_pwd'] ) ) {
			Utils::mo_sf_sync_show_error_message( 'Minimum 6 characters should be present. Maximum 15 characters should be present. Only following symbols (!@#.$%^&*-_) should be present.' );
			return;
		} else {
			$email            = sanitize_email( $post_array['account_email_reg'] );
			$password         = stripslashes( $post_array['reg_account_pwd'] );
			$confirm_password = stripslashes( $post_array['confirm_account_pwd'] );
		}
		update_option( 'mo_sf_sync_admin_email', $email );

		if ( strcmp( $password, $confirm_password ) === 0 ) {
			update_option( 'mo_sf_sync_admin_password', $password );
			$customer    = new Customer_API();
				$content = json_decode( $customer->mo_sf_sync_check_customer(), true );

			if ( ! is_null( $content ) ) {
				if ( strcasecmp( $content['status'], 'CUSTOMER_NOT_FOUND' ) === 0 ) {

					$response = $this->mo_sf_sync_create_customer();

					if ( is_array( $response ) && array_key_exists( 'status', $response ) && 'success' === $response['status'] ) {
						Utils::mo_sf_sync_show_success_message( 'Successfully Logged In' );
						wp_safe_redirect( admin_url( '/admin.php?page=mo_sf_sync&tab=account_setup' ) );
						exit;
					}
				} else {
					$response = $this->mo_sf_sync_get_current_customer();
					if ( is_array( $response ) && array_key_exists( 'status', $response ) && 'success' === $response['status'] ) {
						Utils::mo_sf_sync_show_success_message( 'Successfully Logged In' );
						wp_safe_redirect( admin_url( '/admin.php?page=mo_sf_sync&tab=account_setup' ) );
						exit;
					}
				}
			}
		} else {
			Utils::mo_sf_sync_show_error_message( "Passwords don't match!" );
			delete_option( 'mo_sf_sync_verify_customer' );
		}
	}

	/**
	 * Stores all customer related constants in options.
	 *
	 * @return array
	 */
	private function mo_sf_sync_create_customer() {
		$customer     = new Customer_API();
		$customer_key = json_decode( $customer->mo_sf_sync_create_customer(), true );
		if ( ! is_null( $customer_key ) ) {
			$response = array();
			if ( strcasecmp( $customer_key['status'], 'CUSTOMER_USERNAME_ALREADY_EXISTS' ) === 0 ) {
				$api_response = $this->mo_sf_sync_get_current_customer();
				if ( $api_response ) {
					$response['status'] = 'success';
				} else {
					$response['status'] = 'error';
				}
			} elseif ( strcasecmp( $customer_key['status'], 'SUCCESS' ) === 0 ) {
				update_option( 'mo_sf_sync_admin_customer_key', $customer_key['id'] );
				update_option( 'mo_sf_sync_admin_api_key', $customer_key['apiKey'] );
				update_option( 'mo_sf_sync_customer_token', $customer_key['token'] );
				update_option( 'mo_sf_sync_free_version', 1 );
				update_option( 'mo_sf_sync_admin_password', '' );
				update_option( 'mo_sf_sync_registration_status', '' );
				delete_option( 'mo_sf_sync_verify_customer' );
				$response['status'] = 'success';
				return $response;
			}

			update_option( 'mo_sf_sync_admin_password', '' );
			return $response;
		}
	}

	/**
	 * Stores customer key related data to options table.
	 *
	 * @return array
	 */
	private function mo_sf_sync_get_current_customer() {
		$customer = new Customer_API();
		$content  = $customer->mo_sf_sync_get_customer_key();

		if ( ! is_null( $content ) ) {
			$customer_key = json_decode( $content, true );

			$response = array();
			if ( json_last_error() === JSON_ERROR_NONE ) {
				update_option( 'mo_sf_sync_admin_customer_key', $customer_key['id'] );
				update_option( 'mo_sf_sync_admin_api_key', $customer_key['apiKey'] );
				update_option( 'mo_sf_sync_customer_token', $customer_key['token'] );
				update_option( 'mo_sf_sync_admin_password', '' );

				delete_option( 'mo_sf_sync_verify_customer' );
				$response['status'] = 'success';
				return $response;
			} else {

				Utils::mo_sf_sync_show_success_message( 'You already have an account with miniOrange. Please enter a valid password.' );
				$response['status'] = 'error';
				return $response;
			}
		}
	}
}
