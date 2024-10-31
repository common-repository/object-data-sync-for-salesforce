<?php
/**
 * This file handles all the AJAX requests and produces response accordingly.
 *
 * @package object-data-sync-for-salesforce\Handler
 */

namespace MoSfSyncSalesforce\Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MoSfSyncSalesforce\API\Salesforce;
use MoSfSyncSalesforce\Customer;
use MoSfSyncSalesforce\Helper\Plugin_Constants;
use MoSfSyncSalesforce\Helper\Instance;
use MoSfSyncSalesforce\Services\Utils;

/**
 * This class provides all the functionalities to handle the various AJAX requests.
 */
class Ajax_Handler {
	use Instance;

	/**
	 * Instance of Salesforce class.
	 *
	 * @var Salesforce
	 */
	private $salesforce;

	/**
	 * Instance of Object_Sync_Sf_To_Wp class.
	 *
	 * @var Object_Sync_Sf_To_Wp
	 */
	private $object_sync_sf_to_wp;

	/**
	 * Creates instance of the class.
	 *
	 * @return __CLASS__
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance                       = new self();
			self::$instance->salesforce           = new Salesforce();
			self::$instance->object_sync_sf_to_wp = new Object_Sync_Sf_To_Wp();
		}
		return self::$instance;
	}

	/**
	 * Function that handles all AJAX request & sends response.
	 *
	 * @return void
	 */
	public function mo_sf_sync_set_settings() {
		if ( ! current_user_can( 'administrator' ) ) {
			wp_send_json_error( 'Not an administrator' );
			return;
		}
		if ( isset( $_POST['get_pardot_forms'] ) ) {
			//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- cannot escape this as this is a gutenberg block selection dropdown.
			echo $this->mo_sf_sync_generate_form_shortcodes();
			return;
		}
		if ( isset( $_POST['get_dynamic_content'] ) ) {
			//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- cannot escape this as this is a gutenberg block selection dropdown.
			echo $this->mo_sf_sync_generate_dynamic_content_shortcodes();
			return;
		}
		if ( isset( $_POST['push'] ) ) {
			$data_processing_handler = Data_Processing_Handler::instance();
			$res                     = $data_processing_handler->mo_sf_sync_push_to_salesforce( sanitize_text_field( wp_unslash( $_POST['push'] ) ) );
			if ( is_wp_error( $res ) ) {
				wp_send_json_error( implode( ', ', $res->get_error_messages() ) );
			} else {
				wp_send_json_success( $res );
			}
			return;
		}

		if ( isset( $_POST['integration_trial_request'] ) ) {
			set_transient( 'mo_sf_sync_integration_trial_notice_dismiss_time', true, 604800 );
			wp_send_json_success( true );
		}

		if ( isset( $_POST['trial_request'] ) ) {
			set_transient( 'mo_sf_sync_normal_trial_notice_dismiss_time', true, 604800 );
			wp_send_json_success( true );
		}

		if ( isset( $_POST['object'] ) ) {
			$res = $this->salesforce->mo_sf_sync_get_fields( sanitize_text_field( wp_unslash( $_POST['object'] ) ) );
			if ( 'Please install miniOrange on your connected salesforce environment' === $res || 'Cannot connect to salesforce, please Authorize Again!' === $res || 'Salesforce Instance URL Not Found, please Authorize Again' === $res ) {
				wp_send_json_error( array( 'error_description' => $res ) );
			}
			if ( is_wp_error( $res ) ) {
				wp_send_json_error( implode( ', ', $res->get_error_messages() ) );
			} else {
				wp_send_json_success( $res );
			}
			return;
		}

		if ( isset( $_POST['wp_object'] ) ) {
			$wp_object = sanitize_text_field( wp_unslash( $_POST['wp_object'] ) );

			$wp_object_fields      = $this->object_sync_sf_to_wp->mo_sf_sync_get_wp_obj_fields( $wp_object );
			$wp_object_fields_keys = array();

			foreach ( $wp_object_fields as $key => $value ) {
				$wp_object_fields_keys[] = $value['key'];
			}

			if ( is_wp_error( $wp_object_fields ) ) {
				wp_send_json( implode( ',', $wp_object_fields->get_error_messages() ) );
			} else {
				wp_send_json_success( $wp_object_fields_keys );
			}
		}

		if ( isset( $_POST['query'] ) ) {
			$users = new \WP_User_Query(
				array(
					'search'         => '*' . sanitize_text_field( wp_unslash( $_POST['query'] ) ) . '*',
					'search_columns' => array(
						'user_login',
						'user_nicename',
						'user_email',
						'user_url',
					),
					'number'         => 10,
				)
			);
			$users = $users->get_results();
			wp_send_json_success( $users );
			return;
		}

		if ( ! isset( $_POST['option'] ) || ! isset( $_POST['nonce_'] ) || ! isset( $_POST['tab'] ) ) {
			wp_send_json_error( 'Invalid Request' );
		}

		check_ajax_referer( sanitize_text_field( wp_unslash( $_POST['option'] ) ), 'nonce_' );
		$tab = sanitize_text_field( wp_unslash( $_POST['tab'] ) );

		$this->mo_sf_sync_handle_ajax_request();
	}

	/**
	 * Function that selects the option from the the POST data on which further handling is to be performed accordingly.
	 *
	 * @return void
	 */
	public function mo_sf_sync_handle_ajax_request() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification done globally.
		if ( isset( $_POST['option'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification done globally.
			$option = sanitize_text_field( wp_unslash( $_POST['option'] ) );
		}
		$this->mo_sf_sync_route_to_correct_handler( $option );
	}

	/**
	 * Routes the input option to the intended handler function for further operations.
	 *
	 * @param string $option The option received from $_POST to be handled.
	 * @return void
	 */
	private function mo_sf_sync_route_to_correct_handler( $option ) {
		switch ( $option ) {
			case 'mo_sf_sync_contact_us_query_option':
				$this->mo_sf_sync_get_support();
				break;
		}
	}

	/**
	 * Function that handles the authorization flow through manual app or a pre-connected app.
	 *
	 * @param array $postdata The $_POST data array containing the input field values.
	 * @return void
	 */
	public function mo_sf_sync_handle_config_object_save( $postdata ) {
		if ( ! isset( $postdata['client_id'] ) || empty( $postdata['client_id'] ) ) {
			$data['env_link']              = esc_url_raw( $postdata['env_link'] );
			$data['env_select']            = sanitize_text_field( $postdata['env_select'] );
			$data['is_pardot_int_enabled'] = isset( $postdata['is_pardot_int_enabled'] ) ? sanitize_text_field( $postdata['is_pardot_int_enabled'] ) : '';
			$data['pardot_business_uid']   = isset( $postdata['pardot_business_uid'] ) && isset( $postdata['is_pardot_int_enabled'] ) ? sanitize_text_field( $postdata['pardot_business_uid'] ) : '';
			$data['pardot_env_link']       = isset( $postdata['pardot_env_link'] ) ? sanitize_text_field( $postdata['pardot_env_link'] ) : '';
			$data['app_type']              = 'preconnected';
			if ( empty( $data['env_link'] ) ) {
				Utils::mo_sf_sync_show_error_message( 'Save Unsuccessful because no Environment selected' );
			}
			set_transient( Plugin_Constants::TRANSIENT_CONFIG_OBJECT, $data );
			Utils::mo_sf_sync_save_settings( Plugin_Constants::CONFIG_OBJECT, $data );
			Utils::mo_sf_sync_show_success_message( 'Selected Environment Saved Successfully !' );
		} else {
			$data['env_link']              = esc_url_raw( $postdata['env_link'] );
			$data['env_select']            = sanitize_text_field( $postdata['env_select'] );
			$data['client_id']             = sanitize_text_field( $postdata['client_id'] );
			$data['client_secret']         = sanitize_text_field( $postdata['client_secret'] );
			$data['client_secret']         = Authorization_Handler::mo_sf_sync_encrypt_data( $data['client_secret'], hash( 'sha256', $data['client_id'] ) );
			$data['redirect_uri']          = esc_url_raw( $postdata['redirect_uri'] );
			$data['is_pardot_int_enabled'] = isset( $postdata['is_pardot_int_enabled'] ) ? sanitize_text_field( $postdata['is_pardot_int_enabled'] ) : '';
			$data['pardot_business_uid']   = isset( $postdata['pardot_business_uid'] ) && isset( $postdata['is_pardot_int_enabled'] ) ? sanitize_text_field( $postdata['pardot_business_uid'] ) : '';
			$data['pardot_env_link']       = isset( $postdata['pardot_env_link'] ) ? sanitize_text_field( $postdata['pardot_env_link'] ) : '';
			$data['app_type']              = 'manual';
			set_transient( Plugin_Constants::TRANSIENT_CONFIG_OBJECT, $data );
			Utils::mo_sf_sync_save_settings( Plugin_Constants::CONFIG_OBJECT, $data );
			delete_option( Plugin_Constants::CONNECTION_TYPE );
			Utils::mo_sf_sync_show_success_message( 'Configuration Saved Successfully !' );
		}
	}

	/**
	 * Generate the pardot form for embedding.
	 *
	 * @return string
	 */
	public function mo_sf_sync_generate_form_shortcodes() {
		$forms = $this->salesforce->mo_sf_sync_get_pardot_forms();
		$html  = array();
		if ( ! empty( $forms ) ) {
			$select_id   = 'mo_sf_sync_pardot_forms';
			$select_name = 'mo_sf_sync_pardot_forms';
			$html[]      = "<select id=\"{$select_id}\" name=\"{$select_name}\">";
			$html[]      = '<option value="0">Select</option>';
			foreach ( $forms['values'] as $form ) {
				if ( isset( $form['id'] ) ) {
					$html[] = "<option value=\"[mo-sf-sync-pardot-form id=&quot;{$form['id']}&quot; title=&quot;{$form['name']}&quot;]\">{$form['name']}</option>";
				}
			}
			$html[] = '</select>';
		}
		return implode( '', $html );
	}
	/**
	 * Generate the pardot dynamic content for embedding.
	 *
	 * @return string
	 */
	public function mo_sf_sync_generate_dynamic_content_shortcodes() {
		$dynamic_content = $this->salesforce->mo_sf_sync_get_dynamic_content();
		$html            = array();
		if ( ! empty( $dynamic_content ) ) {
			$select_id   = 'mo_sf_sync_dynamic_content';
			$select_name = 'mo_sf_sync_dynamic_content';
			$html[]      = "<select id=\"{$select_id}\" name=\"{$select_name}\">";
			$html[]      = '<option value="0">Select</option>';
			foreach ( $dynamic_content['values'] as $dynamic_content ) {
				$html[] = "<option value=\"[mo-sf-sync-pardot-dynamic-content id=&quot;{$dynamic_content['id']}&quot; default=&quot;{Default Dynamic Content}&quot;]\">{$dynamic_content['name']}</option>";
			}
			$html[] = '</select>';
		}
		return implode( '', $html );
	}

	/**
	 * Function that handles the feature request/contact us form .
	 *
	 * @return void
	 */
	public function mo_sf_sync_get_support() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification done in mo_sf_sync_set_settings function.
		if ( isset( $_POST['data'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Nonce verification done in mo_sf_sync_set_settings function,sanitization and unslash are done using custom function.
			$map = Utils::mo_sf_sync_sanitize_and_index( $_POST['data'] );
		}
		$email = $map['mo_sf_sync_contact_us_email'];
		$query = $map['mo_sf_sync_contact_us_query'];
		if ( empty( $email ) || empty( $query ) ) {
			wp_send_json_error( 'Empty email or query' );
		} else {
			$support  = new Customer();
			$response = $support->mo_sf_sync_submit_contact_us( $email, '', $query, false );
			if ( 'Query submitted.' === $response ) {
				wp_send_json_success( $response );
			} else {
				wp_send_json_error( $response );
			}
		}
	}
}
