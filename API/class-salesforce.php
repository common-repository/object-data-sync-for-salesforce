<?php
/**
 * This file contains methods to send requests to Salesforce.
 *
 * @package object-data-sync-for-salesforce\API
 */

namespace MoSfSyncSalesforce\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MoSfSyncSalesforce\Handler\Authorization_Handler;
use MoSfSyncSalesforce\Helper\Instance;
use MoSfSyncSalesforce\Helper\Plugin_Constants;
use MoSfSyncSalesforce\IAPI\Salesforce_Interface;
use MoSfSyncSalesforce\Services\Utils;

/**
 * This class contains all APIs, and Requests for Salesforce sync.
 */
class Salesforce implements Salesforce_Interface {
	use Instance;

	/**
	 * Instance variable for Authorization handler class.
	 *
	 * @var object $authorization_handler
	 */
	private $authorization_handler;

	/**
	 * Stores refresh token.
	 *
	 * @var string
	 */
	private $refresh_token;

	/**
	 * API version.
	 *
	 * @var string
	 */
	public $api_version = 'v53.0';

	/**
	 * Salesforce Object.
	 *
	 * @var string
	 */
	private $sf_object = 'User';

	/**
	 * Stores Instance URL.
	 *
	 * @var string
	 */
	public $instance_url;

	/**
	 * Stores access token.
	 *
	 * @var string
	 */
	public $access_token;

	/**
	 * Stores business unit id for pardot sync.
	 *
	 * @var string
	 */
	public $pardot_business_uid;

	/**
	 * Stores the pardot end point domain.
	 *
	 * @var string
	 */
	public $pardot_domain;

	/**
	 * Creates instance of the class and initializes required variables.
	 */
	public function __construct() {
		$this->authorization_handler = Authorization_Handler::instance();
		$client_configurations       = Utils::mo_sf_sync_get_settings( Plugin_Constants::CONFIG_OBJECT );
		$this->pardot_business_uid   = isset( $client_configurations[ Plugin_Constants::PARDOT_BUSSINESSUID ] ) ? $client_configurations[ Plugin_Constants::PARDOT_BUSSINESSUID ] : '';
		$this->pardot_domain         = isset( $client_configurations[ Plugin_Constants::PARDOT_DOMAIN_LINK ] ) ? $client_configurations[ Plugin_Constants::PARDOT_DOMAIN_LINK ] : '';
		$token                       = $this->authorization_handler->mo_sf_sync_get_access_token();
		if ( $token ) {
			$this->access_token  = $token[ Plugin_Constants::ACCESS_TOKEN ];
			$this->refresh_token = $token[ Plugin_Constants::REFRESH_TOKEN ];
			$this->instance_url  = $token['instance_url'];
		}
	}

	/**
	 * Returns API endpoint.
	 *
	 * @param string $object_type salesforce object type.
	 * @return string
	 */
	private function mo_sf_sync_get_api_endpoint( $object_type ) {

		return $this->instance_url . '/services/data/' . $this->api_version . '/sobjects/' . $object_type;
	}

	/**
	 * Returns Access token.
	 *
	 * @return string
	 */
	public function mo_sf_sync_get_access_token() {
		return $this->access_token;
	}

	/**
	 * Set Access token.
	 *
	 * @param string $access_token Access token from response.
	 * @return void
	 */
	public function mo_sf_sync_set_access_token( $access_token ): void {
		$this->access_token = $access_token;
	}

	/**
	 * Returns Salesforce objects list.
	 *
	 * @return array
	 */
	public function mo_sf_sync_get_objects() {
		$access_token = $this->access_token;

		$url      = $this->instance_url . '/services/data/' . $this->api_version . '/sobjects/';
		$headers  = array(
			'Authorization' => 'Bearer ' . $access_token,
		);
		$response = $this->mo_sf_sync_get_request( $url, $headers );
		$result   = $this->mo_sf_sync_check_token( $url, $headers, $response );
		if ( ! is_wp_error( $result ) ) {
			return $result['sobjects'] ?? array();
		}
		return $result;
	}

	/**
	 * Returns Salesforce objects' fields list.
	 *
	 * @param string $object Salesforce object.
	 * @return array
	 */
	public function mo_sf_sync_get_fields( $object ) {

		$access_token = $this->access_token;
		$url          = $this->instance_url . '/services/data/' . $this->api_version . '/sobjects/' . $object . '/describe/';
		$headers      = array(
			'Authorization' => 'Bearer ' . $access_token,
		);
		$response     = $this->mo_sf_sync_get_request( $url, $headers );
		return $this->mo_sf_sync_check_token( $url, $headers, $response );
	}

	/**
	 * Returns API version.
	 *
	 * @return string
	 */
	public function mo_sf_sync_get_api_versions() {
		$access_token = $this->access_token;
		$url          = $this->instance_url . '/services/data';
		$headers      = array(
			'Authorization' => 'Bearer ' . $access_token,
		);
		$response     = $this->mo_sf_sync_get_request( $url, $headers );
		return $this->mo_sf_sync_check_token( $url, $headers, $response );
	}

	/**
	 * Checks if the token is expired or not if expired gets new token and retries request.
	 *
	 * @param string $url salesforce side url.
	 * @param array  $headers request header.
	 * @param array  $response response from salesforce side.
	 * @param string $method method used.
	 * @param object $body request body.
	 * @return array
	 */
	public function mo_sf_sync_check_token( $url, $headers, $response, $method = 'GET', $body = '' ) {
		if ( ( is_array( $response ) && count( $response ) && isset( $response[0]['errorCode'] ) && 'INVALID_SESSION_ID' === $response[0]['errorCode'] ) || ( isset( $response['code'] ) && 184 === $response['code'] ) ) {
			$data = $this->authorization_handler->mo_sf_sync_get_new_token();
			if ( 'Please install miniOrange on your connected salesforce environment' === $data || 'Salesforce Instance URL Not Found, please Authorize Again' === $data || 'Cannot connect to salesforce, please Authorize Again!' === $data || ! isset( $data['access_token'] ) ) {
				return $data;
			}

			$this->access_token       = $data['access_token'];
			$headers['Authorization'] = 'Bearer ' . $this->access_token;
			if ( 'GET' !== $method ) {
				return $this->mo_sf_sync_post_request( $url, $method, $headers, $body );
			}
			$response = $this->mo_sf_sync_get_request( $url, $headers );
			if ( is_array( $response ) && count( $response ) && isset( $response[0]['errorCode'] ) && 'INVALID_SESSION_ID' === $response[0]['errorCode'] ) {
				return 'Invalid session';
			} else {
				return $response;
			}
		} else {
			return $response;
		}
	}

	/**
	 * Return response for update record on salesforce side.
	 *
	 * @param string $id Salesforce ID.
	 * @param string $object Salesforce object.
	 * @param array  $body request body.
	 * @return array
	 */
	public function mo_sf_sync_update_record( $id, $object, $body ) {
		$access_token = $this->access_token;
		$url          = $this->instance_url . '/services/data/' . $this->api_version . '/sobjects/' . $object . '/' . $id;
		$headers      = array(
			'Authorization' => 'Bearer ' . $access_token,
			'Content-Type'  => 'application/json',
		);
		$response     = $this->mo_sf_sync_post_request( $url, 'PATCH', $headers, wp_json_encode( $body ) );
		return $this->mo_sf_sync_check_token( $url, $headers, $response, 'PATCH', wp_json_encode( $body ) );
	}

	/**
	 * Return response for create record on salesforce side.
	 *
	 * @param string $object Salesforce object.
	 * @param array  $body request body.
	 * @return array
	 */
	public function mo_sf_sync_create_record( $object, $body ) {
		$access_token = $this->access_token;
		$url          = $this->instance_url . '/services/data/' . $this->api_version . '/sobjects/' . $object;
		$headers      = array(
			'Authorization' => 'Bearer ' . $access_token,
			'Content-Type'  => 'application/json',
		);
		$response     = $this->mo_sf_sync_post_request( $url, 'POST', $headers, wp_json_encode( $body ) );
		return $this->mo_sf_sync_check_token( $url, $headers, $response, 'POST', wp_json_encode( $body ) );
	}

	/**
	 * This function performs HTTP request using the GET method and returns its response.
	 *
	 * @param string $url salesforce side url.
	 * @param array  $headers request header.
	 * @return array
	 */
	public function mo_sf_sync_get_request( $url, $headers ) {
		$response = wp_remote_get(
			$url,
			array(
				'headers' => $headers,
				'timeout' => 10,
			)
		);
		if ( is_array( $response ) && ! is_wp_error( $response ) ) {
			return json_decode( $response['body'], true );
		} else {
			return $response->get_error_messages();
		}
	}

	/**
	 *  This function performs HTTP request using the POST method and returns its response.
	 *
	 * @param string $url salesforce side url.
	 * @param string $method method used.
	 * @param array  $headers request header.
	 * @param object $body request body.
	 * @return array
	 */
	public function mo_sf_sync_post_request( $url, $method, $headers, $body ) {
		$response = wp_remote_post(
			$url,
			array(
				'method'  => $method,
				'body'    => $body,
				'headers' => $headers,
				'timeout' => 15,
			)
		);
		if ( is_array( $response ) && ! is_wp_error( $response ) ) {
			return json_decode( $response['body'], true );
		} else {
			return $response->get_error_messages();
		}
	}

	/**
	 * Send composite request to salesforce.
	 *
	 * @param string $composite_url salesforce side composite url.
	 * @param array  $headers request header.
	 * @param object $composite_request_body request body.
	 * @return mixed
	 */
	public function mo_sf_sync_send_composite_request( $composite_url, $headers, $composite_request_body ) {
		$composite_response = $this->mo_sf_sync_post_request( $composite_url, 'POST', $headers, $composite_request_body );
		return $this->mo_sf_sync_check_token( $composite_url, $headers, $composite_response, 'POST', $composite_request_body );
	}

	/**
	 * Fetches all the campaigns from pardot.
	 *
	 * @return array
	 */
	public function mo_sf_sync_get_all_pardot_campaigns() {
		$business_uid = $this->pardot_business_uid;
		$access_token = $this->access_token;
		$url          = Plugin_Constants::PARDOT_DOMAIN [ $this->pardot_domain ] . '/api/v5/objects/campaigns?fields=id,name&orderBy=id';
		$headers      = array(
			'Authorization'           => 'Bearer ' . $access_token,
			'Pardot-Business-Unit-Id' => $business_uid,
		);
		$response     = $this->mo_sf_sync_get_request( $url, $headers );
		return $this->mo_sf_sync_check_token( $url, $headers, $response );
	}

	/**
	 * Fetches all the forms from pardot.
	 *
	 * @return array
	 */
	public function mo_sf_sync_get_pardot_forms() {
		$business_uid = $this->pardot_business_uid;
		$access_token = $this->access_token;
		$url          = Plugin_Constants::PARDOT_DOMAIN [ $this->pardot_domain ] . '/api/v5/objects/forms?fields=' . Plugin_Constants::PARDOT_FORM_FIELDS;
		$headers      = array(
			'Authorization'           => 'Bearer ' . $access_token,
			'Pardot-Business-Unit-Id' => $business_uid,
		);
		$response     = $this->mo_sf_sync_get_request( $url, $headers );
		return $this->mo_sf_sync_check_token( $url, $headers, $response );
	}

	/**
	 * Fetches the list of dynamic content from pardot.
	 *
	 * @return array
	 */
	public function mo_sf_sync_get_dynamic_content() {
		$business_uid = $this->pardot_business_uid;
		$access_token = $this->access_token;
		$url          = Plugin_Constants::PARDOT_DOMAIN [ $this->pardot_domain ] . '/api/v5/objects/dynamic-contents?fields=' . Plugin_Constants::PARDOT_DYNAMIC_CONTENT_FIELDS;
		$headers      = array(
			'Authorization'           => 'Bearer ' . $access_token,
			'Pardot-Business-Unit-Id' => $business_uid,
		);
		$response     = $this->mo_sf_sync_get_request( $url, $headers );
		return $this->mo_sf_sync_check_token( $url, $headers, $response );
	}

	/**
	 * Fetches the dynamic content from pardot using the dynamic content id.
	 *
	 * @param int $dynamic_content_id dynamic content id.
	 * @return array
	 */
	public function mo_sf_sync_get_dynamic_content_using_dy_cn_id( $dynamic_content_id ) {
		$business_uid = $this->pardot_business_uid;
		$access_token = $this->access_token;
		$url          = Plugin_Constants::PARDOT_DOMAIN [ $this->pardot_domain ] . "/api/v5/objects/dynamic-contents/$dynamic_content_id?fields=" . Plugin_Constants::PARDOT_DYNAMIC_CONTENT_FIELDS_CP_ID;
		$headers      = array(
			'Authorization'           => 'Bearer ' . $access_token,
			'Pardot-Business-Unit-Id' => $business_uid,
		);
		$response     = $this->mo_sf_sync_get_request( $url, $headers );
		return $this->mo_sf_sync_check_token( $url, $headers, $response );
	}
}
