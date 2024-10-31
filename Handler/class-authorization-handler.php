<?php
/**
 * This file initiates and handles the connection to salesforce.
 *
 * @package object-data-sync-for-salesforce\Handler
 */

namespace MoSfSyncSalesforce\Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MoSfSyncSalesforce\Helper\Plugin_Constants;
use MoSfSyncSalesforce\Helper\Instance;
use MoSfSyncSalesforce\Services\Utils;
use MoSfSyncSalesforce\API\Salesforce;
use MoSfSyncSalesforce\Helper\Pre_Connected_App_Enum;
use MoSfSyncSalesforce\Services\Audit_DB;

/**
 * Handles the Authorization of connected application.
 */
class Authorization_Handler {

	use Instance;

	/**
	 * Stores application configuration details.
	 *
	 * @var array
	 */
	private $configurations;

	/**
	 * Stores access token.
	 *
	 * @var string
	 */
	private $access_token;

	/**
	 * Stores refresh token.
	 *
	 * @var string
	 */
	private $refresh_token;

	/**
	 * Stores Instance URL.
	 *
	 * @var string
	 */
	private $instance_url;

	/**
	 * Instance variable for Audit_DB class.
	 *
	 * @var object
	 */
	private $audit_handler;

	/**
	 * Creates instance of the class and initializes required variables.
	 *
	 * @return object
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->mo_sf_sync_set_configurations( self::$instance->mo_sf_sync_client_info() );
			self::$instance->mo_sf_sync_set_access_token( Utils::mo_sf_sync_get_settings( Plugin_Constants::SF_RESPONSE_OBJECT ) );
			self::$instance->audit_handler = Audit_DB::instance();
		}
		return self::$instance;
	}

	/**
	 * Returns array containing Environment link, selected environment and app type.
	 *
	 * @return array
	 */
	private function mo_sf_sync_client_info() {
		return maybe_unserialize( Utils::mo_sf_sync_get_settings( Plugin_Constants::CONFIG_OBJECT ) );
	}

	/**
	 * This function handles the authorization of connected application.
	 *
	 * @return void
	 */
	public function mo_sf_sync_handle_code() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- When authorization code is received.
		if ( isset( $_REQUEST['code'] ) ) {
			$client = $this->mo_sf_sync_client_info();
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This request is received when connected to Salesforce.
			if ( array_key_exists( 'preconnected', $_REQUEST ) && 'true' === $_REQUEST['preconnected'] ) {
				update_option( Plugin_Constants::CONNECTION_TYPE, 'automatic' );
				$manual_app_config = get_option( Plugin_Constants::CONFIG_OBJECT );
				if ( isset( $manual_app_config ) ) {
					unset( $manual_app_config[ Plugin_Constants::CLIENT_ID ] );
					unset( $manual_app_config[ Plugin_Constants::CLIENT_SECRET ] );
					unset( $manual_app_config[ Plugin_Constants::REDIRECT_URI ] );
					update_option( Plugin_Constants::CONFIG_OBJECT, $manual_app_config );
				}

				$client[ Plugin_Constants::CLIENT_ID ]     = Pre_Connected_App_Enum::PRE_CONNECTED_APP_CLIENT_ID;
				$client[ Plugin_Constants::CLIENT_SECRET ] = Pre_Connected_App_Enum::PRE_CONNECTED_APP_CLIENT_SECRET;
				$client[ Plugin_Constants::CLIENT_SECRET ] = self::mo_sf_sync_encrypt_data( $client[ Plugin_Constants::CLIENT_SECRET ], hash( 'sha256', $client[ Plugin_Constants::CLIENT_ID ] ) );
				$client[ Plugin_Constants::REDIRECT_URI ]  = Pre_Connected_App_Enum::PRE_CONNECTED_APP_REDIRECT_URI;
			} else {
				update_option( Plugin_Constants::CONNECTION_TYPE, 'manual' );
			}

			$sf_env_url = ! empty( $client[ Plugin_Constants::ENVIRONMENT_LINK ] ) ? $client[ Plugin_Constants::ENVIRONMENT_LINK ] : 'https://test.salesforce.com';

			if ( ! empty( $client[ Plugin_Constants::CLIENT_SECRET ] ) ) {
				$client[ Plugin_Constants::CLIENT_SECRET ] = self::mo_sf_sync_decrypt_data( $client[ Plugin_Constants::CLIENT_SECRET ], hash( 'sha256', $client[ Plugin_Constants::CLIENT_ID ] ) );
			}
			if ( empty( $client[ Plugin_Constants::CLIENT_ID ] ) || empty( $client[ Plugin_Constants::CLIENT_SECRET ] ) || empty( $client[ Plugin_Constants::REDIRECT_URI ] ) ) {
				wp_die( '<b>[MOSFSYNCERR001]:</b> Invalid client credentials. Save valid credentials.', 'miniOrange Authorization Error' );
				return;
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- When authorization code is received.
			$code = urldecode( sanitize_text_field( wp_unslash( $_REQUEST['code'] ) ) );
			if ( ! empty( $code ) ) {
				$env = 'login';
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This request is received when connected to Salesforce.
				if ( array_key_exists( 'preconnected', $_REQUEST ) && 'true' === $_REQUEST['preconnected'] ) {
					$body = array(
						'client_id'     => Pre_Connected_App_Enum::PRE_CONNECTED_APP_CLIENT_ID,
						'client_secret' => Pre_Connected_App_Enum::PRE_CONNECTED_APP_CLIENT_SECRET,
						'redirect_uri'  => Pre_Connected_App_Enum::PRE_CONNECTED_APP_REDIRECT_URI,
						'grant_type'    => 'authorization_code',
						'code'          => $code,
					);
				} else {
					$body = array(
						'client_id'     => $client[ Plugin_Constants::CLIENT_ID ],
						'client_secret' => $client[ Plugin_Constants::CLIENT_SECRET ],
						'redirect_uri'  => $client[ Plugin_Constants::REDIRECT_URI ],
						'grant_type'    => 'authorization_code',
						'code'          => $code,
					);
				}

				$body     = http_build_query( $body );
				$auth_url = $sf_env_url . '/services/oauth2/token';
				$this->audit_handler->mo_sf_sync_add_log( 'Authentication Call', '', '', '', 'Info', 'Call URL: ' . $auth_url, $wp_object = 'user' );
				$res = wp_remote_post(
					$auth_url,
					array(
						'headers' => array( 'content-type' => 'application/x-www-form-urlencoded' ),
						'body'    => $body,
					)
				);

				if ( is_wp_error( $res ) ) {
					wp_die( '<b>[MOSFSYNCERR003]:</b> ' . esc_html( $res->get_error_message() ) . '</br></br>Note: This issue can occur due to active caching on your site. Please try again after clearing the cache. If the issue still persists, please contact us at <b>salesforcesupport@xecurify.com</b>', 'miniOrange Authorization Error' );
					exit();
				}
				$token = json_decode( $res['body'], true );
				if ( ( array_key_exists( Plugin_Constants::REFRESH_TOKEN, $token ) ) && ( array_key_exists( Plugin_Constants::ACCESS_TOKEN, $token ) ) && ( array_key_exists( 'instance_url', $token ) ) ) {
					$sf_response = array(
						'access_token'  => sanitize_text_field( $token[ Plugin_Constants::ACCESS_TOKEN ] ),
						'refresh_token' => sanitize_text_field( $token[ Plugin_Constants::REFRESH_TOKEN ] ),
						'instance_url'  => esc_url( $token['instance_url'] ),
					);
					update_option( Plugin_Constants::SF_RESPONSE_OBJECT, $sf_response );
					$this->mo_sf_sync_set_access_token( Utils::mo_sf_sync_get_settings( Plugin_Constants::SF_RESPONSE_OBJECT ) );
					$salesforce        = new Salesforce();
					$lead_fields       = $salesforce->mo_sf_sync_get_fields( 'lead', true );
					$get_objects       = $salesforce->mo_sf_sync_get_objects( true );
					$processed_objects = array();
					foreach ( $get_objects as $key => $value ) {
						$processed_objects[ $key ] = array(
							'name'  => $value['name'],
							'label' => $value['label'],
						);
					}
					Utils::mo_sf_sync_set_transient( 'transient_lead_object', $lead_fields, 100 );
					Utils::mo_sf_sync_set_transient( 'transient_get_object', $processed_objects, 0 );

				} elseif ( array_key_exists( 'error_description', $token ) ) {
					wp_die( '<b>[MOSFSYNCERR002]:</b> ' . wp_kses_post( $token['error_description'] ), 'miniOrange Authorization Error' );
				} else {
					wp_die( '<b>[MOSFSYNCERR004]:</b> Unknown error occurred', 'miniOrange Authorization Error' );
				}

				mo_sf_sync_connect_to_salesforce_successful_message();
				exit();
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- When connection to Salesforce fails.
			if ( isset( $_REQUEST['error'] ) && isset( $_REQUEST['error_description'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- When connection to Salesforce fails.
				$token['error_description'] = wp_kses_post( wp_unslash( $_REQUEST['error_description'] ) );
				wp_die( '<b>[MOSFSYNCERR005]:</b> ' . esc_html( $token['error_description'] ), 'miniOrange Authorization Error' );
			}
		}
	}

	/**
	 * Stores Authorization details and initiates the connection to salesforce.
	 *
	 * @return void
	 */
	public function mo_sf_sync_connect_to_salesforce() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This GET request is received when test connection pop-up window is opened.
		if ( isset( $_GET['app_type'] ) ) {
			$client     = $this->mo_sf_sync_client_info();
			$sf_env_url = ! empty( $client[ Plugin_Constants::ENVIRONMENT_LINK ] ) ? $client[ Plugin_Constants::ENVIRONMENT_LINK ] : 'https://test.salesforce.com';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This GET request is received when test connection pop-up window is opened.
			if ( 'preconnected' === $_GET['app_type'] ) {
				$client_id    = Pre_Connected_App_Enum::PRE_CONNECTED_APP_CLIENT_ID;
				$redirect_uri = Pre_Connected_App_Enum::PRE_CONNECTED_APP_REDIRECT_URI;
				$scopes       = Utils::mo_sf_sync_is_pardot_configured() ? Plugin_Constants::PARDOT_SCOPES : Plugin_Constants::NON_PARDOT_SCOPES;
				$url          = $sf_env_url . '/services/oauth2/authorize?response_type=code&state=' . home_url() . '&grant_type=authorization_code&scope=' . $scopes . '&client_id=' . $client_id . '&redirect_uri=' . $redirect_uri;
			} elseif ( 'manual' === $_GET['app_type'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This GET request is received when test connection pop-up window is opened.
				$client_id    = $client[ Plugin_Constants::CLIENT_ID ];
				$redirect_uri = $client[ Plugin_Constants::REDIRECT_URI ];
				$scopes       = Utils::mo_sf_sync_is_pardot_configured() ? Plugin_Constants::PARDOT_SCOPES : Plugin_Constants::NON_PARDOT_SCOPES;
				$url          = $sf_env_url . '/services/oauth2/authorize?response_type=code&grant_type=authorization_code&scope=' . $scopes . '&client_id=' . $client_id . '&redirect_uri=' . $redirect_uri;
			}
			// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Need to redirect to a different domain.
			wp_redirect( $url );
			exit();
		}
	}

	/**
	 * Returns a new token.
	 *
	 * @return mixed
	 */
	public function mo_sf_sync_get_new_token() {
		$client          = $this->mo_sf_sync_get_configurations();
		$connection_type = get_option( Plugin_Constants::CONNECTION_TYPE );

		if ( 'automatic' === $connection_type ) {
			$client[ Plugin_Constants::CLIENT_ID ]     = Pre_Connected_App_Enum::PRE_CONNECTED_APP_CLIENT_ID;
			$client[ Plugin_Constants::CLIENT_SECRET ] = Pre_Connected_App_Enum::PRE_CONNECTED_APP_CLIENT_SECRET;
			$sf_env_url                                = $client[ Plugin_Constants::ENVIRONMENT_LINK ];
		} else {
			$client[ Plugin_Constants::CLIENT_SECRET ] = self::mo_sf_sync_decrypt_data( $client[ Plugin_Constants::CLIENT_SECRET ], hash( 'sha256', $client[ Plugin_Constants::CLIENT_ID ] ) );
			$sf_env_url                                = $client[ Plugin_Constants::ENVIRONMENT_LINK ];
		}

		$tokens = $this->mo_sf_sync_get_access_token();
		if ( empty( $tokens ) || ! is_array( $tokens ) || ! isset( $tokens['refresh_token'] ) ) {
			return 'Cannot connect to salesforce, please Authorize Again!';
		}

		$refresh_token = sanitize_text_field( $tokens['refresh_token'] );

		if ( isset( $sf_env_url ) ) {
			$url = $sf_env_url . '/services/oauth2/token';
		} else {
			return 'Salesforce Instance URL Not Found, please Authorize Again';
		}
		$data     = array(
			'grant_type'    => Plugin_Constants::REFRESH_TOKEN,
			'client_id'     => $client[ Plugin_Constants::CLIENT_ID ],
			'client_secret' => $client[ Plugin_Constants::CLIENT_SECRET ],
			'refresh_token' => $refresh_token,
		);
		$response = wp_remote_post(
			$url,
			array(
				'body' => $data,
			)
		);
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			return "Something went wrong: $error_message";
		} else {
			$body = json_decode( $response['body'], true );
			if ( isset( $body['access_token'] ) ) {
				$this->access_token = sanitize_text_field( $body['access_token'] );
				$option             = get_option( Plugin_Constants::SF_RESPONSE_OBJECT );
				$new_option         = array_merge( (array) $option, (array) $body );
				update_option( Plugin_Constants::SF_RESPONSE_OBJECT, $new_option );
				return $this->access_token;
			} else {
				return $body;
			}
		}
	}

	/**
	 * Returns app configuration details.
	 *
	 * @return array
	 */
	public function mo_sf_sync_get_configurations() {
		return $this->configurations;
	}

	/**
	 * Set the configuration variable.
	 *
	 * @param array $configurations contains a array of app configuration details.
	 * @return void
	 */
	public function mo_sf_sync_set_configurations( $configurations ): void {
		$this->configurations = $configurations;
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
	 */
	public function mo_sf_sync_set_access_token( $access_token ): void {
		$this->access_token = $access_token;
	}

	/**
	 * Returns Refresh token.
	 *
	 * @return string
	 */
	public function mo_sf_sync_get_refresh_token() {
		return $this->refresh_token;
	}

	/**
	 * Set Refresh token.
	 *
	 * @param string $refresh_token Refresh token from response.
	 * @return void
	 */
	public function mo_sf_sync_set_refresh_token( $refresh_token ): void {
		$this->refresh_token = $refresh_token;
	}

	/**
	 * Function that encrypts the data using the key passed.
	 *
	 * @param string $data data to encrypt.
	 * @param string $key  key to encrypt.
	 * @return string
	 */
	public static function mo_sf_sync_encrypt_data( $data, $key ) {
		$key       = openssl_digest( $key, 'sha256' );
		$method    = 'aes-128-ecb';
		$str_crypt = openssl_encrypt( $data, $method, $key, OPENSSL_RAW_DATA || OPENSSL_ZERO_PADDING );
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- We need to encrypt and encode the client secret before storing.
		return base64_encode( $str_crypt );
	}

	/**
	 * Function that decrypts the data using the key passed.
	 *
	 * @param string $data encrypted data.
	 * @param string $key  key to decrypt.
	 * @return string|Bool
	 */
	public static function mo_sf_sync_decrypt_data( $data, $key ) {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- We need to decrypt and decode the client secret before storing.
		$str_input = base64_decode( $data );
		$key       = openssl_digest( $key, 'sha256' );
		$method    = 'AES-128-ECB';
		$iv_size   = openssl_cipher_iv_length( $method );
		$iv        = substr( $str_input, 0, $iv_size );
		$data      = substr( $str_input, $iv_size );
		$clear     = openssl_decrypt( $data, $method, $key, OPENSSL_RAW_DATA || OPENSSL_ZERO_PADDING, $iv );

		return $clear;
	}

}
