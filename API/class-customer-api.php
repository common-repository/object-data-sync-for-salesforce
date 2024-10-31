<?php
/**
 * This file handles the Customer handling operations.
 *
 * @package object-data-sync-for-salesforce\API
 */

namespace MoSfSyncSalesforce\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MoSfSyncSalesforce\Helper\Plugin_Constants;
use MoSfSyncSalesforce\Services\Utils;

/**
 * This library is miniOrange Authentication Service.
 *
 * Contains Request Calls to Customer service.
 */
class Customer_API {

	/**
	 * Contains the customers' email address.
	 *
	 * @var string
	 */
	public $email;

	/**
	 * Contains the customers' phone number.
	 *
	 * @var string
	 */
	public $phone;

	/**
	 * Initial values are hardcoded to support the miniOrange framework to generate OTP for email.
	 * We need the default value for creating the first time,
	 * As we don't have the Default keys available before registering the user to our server.
	 * This default values are only required for sending an One Time Passcode at the user provided email address.
	 *
	 * @return string
	 */
	public function mo_sf_sync_create_customer() {
		$url = Plugin_Constants::HOSTNAME . '/moas/rest/customer/add';

		$current_user = wp_get_current_user();
		$this->email  = get_option( 'mo_sf_sync_admin_email' );
		$password     = get_option( 'mo_sf_sync_admin_password' );

		$fields       = array(
			'areaOfInterest' => 'Object Data Sync For Salesforce Plugin',
			'email'          => $this->email,
			'password'       => $password,
		);
		$field_string = wp_json_encode( $fields );

		$headers = array(
			'Content-Type'  => 'application/json',
			'charset'       => 'UTF-8',
			'Authorization' => 'Basic',
		);

		$args = array(
			'method'      => 'POST',
			'body'        => $field_string,
			'timeout'     => '20',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => $headers,
		);

		$response = Utils::mo_sf_sync_wp_remote_call( $url, $args );

		return $response;
	}

	/**
	 * Returns the customer key.
	 *
	 * @return string
	 */
	public function mo_sf_sync_get_customer_key() {
		$url = Plugin_Constants::HOSTNAME . '/moas/rest/customer/key';

		$email = get_option( 'mo_sf_sync_admin_email' );

		$password = get_option( 'mo_sf_sync_admin_password' );

		$fields       = array(
			'email'    => $email,
			'password' => $password,
		);
		$field_string = wp_json_encode( $fields );

		$headers = array(
			'Content-Type'  => 'application/json',
			'charset'       => 'UTF-8',
			'Authorization' => 'Basic',
		);
		$args    = array(
			'method'      => 'POST',
			'body'        => $field_string,
			'timeout'     => '20',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => $headers,
		);

		$response = Utils::mo_sf_sync_wp_remote_call( $url, $args );

		return $response;
	}

	/**
	 * Function that checks if the customer exists.
	 *
	 * @return string
	 */
	public function mo_sf_sync_check_customer() {
		$url = Plugin_Constants::HOSTNAME . '/moas/rest/customer/check-if-exists';

		$email = get_option( 'mo_sf_sync_admin_email' );

		$fields       = array(
			'email' => $email,
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
		$response = Utils::mo_sf_sync_wp_remote_call( $url, $args );
		return $response;
	}

	/**
	 * Function that returns the timestamp.
	 *
	 * @return integer
	 */
	public function mo_sf_sync_get_timestamp() {
		$url      = Plugin_Constants::HOSTNAME . '/moas/rest/mobile/get-timestamp';
		$response = Utils::mo_sf_sync_wp_remote_call( $url, array() );
		return $response;
	}
}
