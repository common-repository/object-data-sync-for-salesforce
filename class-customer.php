<?php
/**
 * This file handles the customer demo requests and email alerts.
 *
 * @package object-data-sync-for-salesforce
 */

namespace MoSfSyncSalesforce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MoSfSyncSalesforce\Helper\Plugin_Constants;

/**
 * Handles the customer account and demo/support request email operations.
 */
class Customer {

	/**
	 * Contains the email of the customer.
	 *
	 * @var string
	 */
	public $email;

	/**
	 * Contains the phone number of the customer.
	 *
	 * @var string
	 */
	public $phone;

	/**
	 * Contains the default customer key of the customer.
	 *
	 * @var string
	 */
	private $default_customer_key = '16555';

	/**
	 * Contains the default API key.
	 *
	 * @var string
	 */
	private $default_api_key = 'fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq';

	/**
	 * Function that processes the customer details and chooses whether to provide demo request or not. And sends the email to support mail.
	 *
	 * @param string  $email                email of the customer.
	 * @param string  $phone                phone number of the customer.
	 * @param string  $query                query type of the customer.
	 * @param boolean $call_setup           call setup.
	 * @param boolean $demo_request         indicates whether the customer has chosen demo request.
	 * @param string  $integration_selected integration selected by the customer.
	 * @param string  $integration_trial    the trials requested by the customer.
	 * @return boolean
	 */
	public function mo_sf_sync_submit_contact_us( $email, $phone, $query, $call_setup, $demo_request = false, $integration_selected = '', $integration_trial = '' ) {
		$url          = Plugin_Constants::HOSTNAME . '/moas/rest/customer/contact-us';
		$current_user = wp_get_current_user();

		$application_type = $this->mo_sf_sync_get_application_type();
		if ( $call_setup ) {
			$query = '[Call Request - Object Data Sync for Salesforce] ' . $query . '<br><br>Selected Application Type : ' . $application_type . '<br><br>Site URL : ' . home_url();
		} else {
			if ( true === $demo_request && ( 'integration_trial_request' === $integration_trial || 'normal_trial_request' === $integration_trial ) ) {
				$query = '[Trial Request - Object Data Sync for Salesforce] ' . $query . ' <br><br>Requested Integration : ' . $integration_selected . ' <br><br>Selected Application Type : ' . $application_type . '<br><br>Site URL : ' . home_url();

			} elseif ( $demo_request ) {
				$query = '[Demo Request - Object Data Sync for Salesforce] ' . $query . ' <br><br>Requested Integration : ' . $integration_selected . '  <br><br>Selected Application Type : ' . $application_type . '<br><br>Site URL : ' . home_url();

			} else {
				$query = '[Object Data Sync for Salesforce] ' . $query . '<br><br>Selected Application Type : ' . $application_type . '<br><br>Site URL : ' . home_url();
			}
		}

		$fields = array(
			'firstName' => $current_user->user_firstname,
			'lastName'  => $current_user->user_lastname,
			'company'   => isset( $_SERVER ['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : '',
			'email'     => $email,
			'ccEmail'   => 'salesforcesupport@xecurify.com',
			'phone'     => $phone,
			'query'     => $query,
		);

		$field_string = wp_json_encode( $fields );

		$headers  = array(
			'Content-Type'  => 'application/json',
			'charset'       => 'UTF-8',
			'Authorization' => 'Basic',
		);
		$args     = array(
			'method'      => 'POST',
			'body'        => $field_string,
			'timeout'     => '20',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => $headers,
		);
		$response = wp_remote_post( $url, $args );
		return ! is_wp_error( $response ) ? $response['body'] : implode( $response->get_error_messages() );

	}

	/**
	 * Function that sends the email alert to the company support mail with the message body.
	 *
	 * @param string  $email        Email address.
	 * @param string  $phone        Phone number.
	 * @param string  $message      Message body to send.
	 * @param boolean $demo_request Whether it is a Demo request.
	 * @return boolean
	 */
	public function mo_sf_sync_send_email_alert( $email, $phone, $message, $demo_request = false ) {

		$url = Plugin_Constants::HOSTNAME . '/moas/api/notify/send';

		$customer_key = $this->default_customer_key;
		$api_key      = $this->default_api_key;

		$current_time_in_millis = self::mo_sf_sync_get_timestamp();
		$current_time_in_millis = number_format( $current_time_in_millis, 0, '', '' );
		$string_to_hash         = $customer_key . $current_time_in_millis . $api_key;
		$hash_value             = hash( 'sha512', $string_to_hash );
		$from_email             = 'no-reply@xecurify.com';
		$subject                = 'Feedback: Object Data Sync for Salesforce';
		if ( $demo_request ) {
			$subject = 'DEMO REQUEST: Object Data Sync for Salesforce';
		}
		$site_url = site_url();

		global $user;
		$user = wp_get_current_user();

		$application_type = $this->mo_sf_sync_get_application_type();

		$query = '[Object Data Sync for Salesforce: ]: ' . $message . '<br><br>Selected Application Type : ' . $application_type;

		if ( isset( $_SERVER['SERVER_NAME'] ) ) {
			$content = '<div >Hello, <br><br>First Name :' . $user->user_firstname . '<br><br>Last  Name :' . $user->user_lastname . '   <br><br>Company :<a href="' . sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) . '" target="_blank" >' . sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) . '</a><br><br>Phone Number :' . $phone . '<br><br>Email :<a href="mailto:' . $email . '" target="_blank">' . $email . '</a><br><br>Query :' . $query . '</div>';
		}

		$fields       = array(
			'customerKey' => $customer_key,
			'sendEmail'   => true,
			'email'       => array(
				'customerKey' => $customer_key,
				'fromEmail'   => $from_email,
				'bccEmail'    => $from_email,
				'fromName'    => 'Xecurify',
				'toEmail'     => 'info@xecurify.com',
				'toName'      => 'salesforcesupport@xecurify.com',
				'bccEmail'    => 'salesforcesupport@xecurify.com',
				'subject'     => $subject,
				'content'     => $content,
			),
		);
		$field_string = wp_json_encode( $fields );

		$headers  = array(
			'Content-Type'  => 'application/json',
			'Customer-Key'  => $customer_key,
			'Timestamp'     => $current_time_in_millis,
			'Authorization' => $hash_value,
		);
		$args     = array(
			'method'      => 'POST',
			'body'        => $field_string,
			'timeout'     => '20',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => $headers,
		);
		$response = wp_remote_post( $url, $args );
		return ! is_wp_error( $response ) ? json_decode( $response['body'], true ) : implode( $response->get_error_messages() );

	}

	/**
	 * Function that returns the time stamp.
	 *
	 * @return boolean
	 */
	public function mo_sf_sync_get_timestamp() {
		$url      = Plugin_Constants::HOSTNAME . '/moas/rest/mobile/get-timestamp';
		$response = wp_remote_post( $url, array() );
		return ! is_wp_error( $response ) ? json_decode( $response['body'], true ) : implode( $response->get_error_messages() );

	}

	/**
	 * Function that returns the selected application type.
	 *
	 * @return string
	 */
	public function mo_sf_sync_get_application_type() {
		$config_object   = get_option( Plugin_Constants::CONFIG_OBJECT );
		$resp_object     = get_option( Plugin_Constants::SF_RESPONSE_OBJECT );
		$connection_type = get_option( Plugin_Constants::CONNECTION_TYPE );
		if ( empty( $resp_object ) && empty( $config_object ) ) {
			$application_type = 'None';
		} elseif ( 'automatic' === $connection_type ) {
			$application_type = 'Pre-connected (Automatic)';
		} else {
			$application_type = 'Manual (Custom)';
		}
		return $application_type;
	}

	/**
	 * Function that performs a check if the customer is logged in or not.
	 *
	 * @param boolean $html_element html element.
	 * @return boolean
	 */
	public static function mo_sf_sync_is_customer_logged_in( $html_element = false ) {
		$email        = get_option( 'mo_sf_sync_admin_email' );
		$customer_key = get_option( 'mo_sf_sync_admin_customer_key' );
		if ( ! $email || ! $customer_key || ! is_numeric( trim( $customer_key ) ) ) {
			return $html_element ? 'disabled' : 0;
		}
		return $html_element ? '' : 1;
	}

	/**
	 * Function that checks for a pattern in the given password.
	 *
	 * @param string $password The password upon which the regex pattern match is checked .
	 * @return int|boolean
	 */
	public static function mo_sf_sync_check_password_pattern( $password ) {
		$pattern = '/^[(\w)*(\!\@\#\$\%\^\&\*\.\-\_)*]+$/';
		return ! preg_match( $pattern, $password );
	}

	/**
	 * Function that deletes the options for removing the customer account as a process of deactivation.
	 *
	 * @return void
	 */
	public static function mo_sf_sync_deactivate() {
		delete_option( 'mo_sf_sync_admin_password' );
		delete_option( 'mo_sf_sync_admin_customer_key' );
		delete_option( 'mo_sf_sync_admin_api_key' );
		delete_option( 'mo_sf_sync_customer_token' );
		delete_option( 'vl_check_t' );
		delete_option( 'vl_check_s' );
	}
}
