<?php
/**
 * This file handles functions which involve update or retrieve operation from database.
 *
 * @package object-data-sync-for-salesforce\Services
 */

namespace MoSfSyncSalesforce\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MoSfSyncSalesforce\Helper\Plugin_Constants;

/**
 * This class handles operations which involve site options.
 */
class Utils {

	/**
	 * Checks whether the parameter passed is an array or not, if array then if there is a value corresponding to every key or not.
	 *
	 * @param array $arr_or_val the array to be checked.
	 * @return array
	 */
	public static function mo_sf_sync_check_array_for_empty_or_null( $arr_or_val ) {
		if ( ! is_array( $arr_or_val ) ) {
			if ( ! isset( $arr_or_val ) || empty( $arr_or_val ) ) {
				return array(
					'status' => 'error',
					'key'    => $arr_or_val,
				);
			}
		} else {
			foreach ( $arr_or_val as $key => $value ) {
				if ( ! isset( $value ) || empty( $value ) ) {
					return array(
						'status' => 'error',
						'key'    => $key,
					);
				}
			}
		}
		return array(
			'status' => 'success',
		);
	}

	/**
	 * Updates an option in database.
	 *
	 * @param string $key the name by which the option is saved in database.
	 * @param string $value value corresponding to that option.
	 * @return bool
	 */
	public static function mo_sf_sync_save_settings( $key, $value ) {
		return update_option( $key, $value, false );
	}

	/**
	 * Retrieves an option from database,unserialize it and returns it.
	 *
	 * @param string $key the option name in the database whose to be retrieved.
	 * @return string
	 */
	public static function mo_sf_sync_get_settings( $key ) {
		return maybe_unserialize( get_option( $key ) );
	}

	/**
	 * Deletes an option from database.
	 *
	 * @param string $key the name of the option to be deleted.
	 * @return bool
	 */
	public static function mo_sf_sync_delete_settings( $key ) {
		return delete_option( $key );
	}

	/**
	 * Sanitizes post data.
	 *
	 * @param array|string $post_data data coming in post.
	 * @return array
	 */
	public static function mo_sf_sync_sanitize_and_index( $post_data ) {
		$post_data_sanitized = array();
		if ( strpos( $post_data, Plugin_Constants::SELECTED_OBJECT ) !== false ) {
			$post_data           = urldecode( $post_data );
			$post_data_sanitized = explode( '&', $post_data );
			$i                   = 0;
			$count               = count( $post_data_sanitized );
			while ( $i < $count ) {
				$post_data_sanitized[ $i ] = explode( '=', $post_data_sanitized[ $i ] );
				$post_data_sanitized[ $i ] = array( $post_data_sanitized[ $i ][0], $post_data_sanitized[ $i ][1] );
				$i++;
			}
			$non_field_mapping = 'object_select';
			$data_sanitized    = array();
			foreach ( $post_data_sanitized as $key => $values ) {
				if ( empty( $values[0] ) || empty( $values[1] ) ) {
					continue;
				}
				if ( $values[0] === $non_field_mapping ) {
					$data_sanitized[ $values[0] ] = $values[1];
				} elseif ( substr( $values[0], 0, 7 ) === 'custom_' ) {
					$data_sanitized['custom_field_mapping'][ substr( $values[0], 7 ) ] = $values[1];
				} else {
					$data_sanitized['field_mapping'][ $values[1] ] = $values[0];
				}
			}
			$data_sanitized['mapping_label'] = $data_sanitized['object_select'] . ' Map';
			return $data_sanitized;

		} else {
			parse_str( $post_data, $post_data_sanitized );
			foreach ( $post_data_sanitized as $key => $value ) {
				if ( empty( $value ) ) {
					unset( $key );
				} else {
					$post_data_sanitized[ $key ] = sanitize_text_field( $value );
				}
			}
		}
		return $post_data_sanitized;
	}

	/**
	 * Saves the whole mapping details as it is into different object.
	 *
	 * @param array $mapping configured mapping details.
	 * @return array
	 */
	public static function mo_sf_sync_manage_backward_compatibility( $mapping ) {

		foreach ( $mapping as $key => $value ) {
			if ( empty( $key ) || empty( $value ) ) {
				unset( $mapping[ $key ] );
			}
		}

		$new_mapping[ Plugin_Constants::SELECTED_OBJECT ] = $mapping[ Plugin_Constants::SELECTED_OBJECT ];
		unset( $mapping[ Plugin_Constants::SELECTED_OBJECT ] );
		$new_mapping = array_merge( $new_mapping, array_flip( $mapping ) );
		self::mo_sf_sync_save_settings( Plugin_Constants::MAPPING_OBJECT, $new_mapping );
		return $new_mapping;
	}

	/**
	 * Drops a table from database.
	 *
	 * @param string $table_name the name of the table to be dropped.
	 * @return void
	 */
	public static function mo_sf_sync_drop_table( $table_name ) {
		global $wpdb;
		$select_query = "DROP TABLE IF EXISTS {$table_name}";
		//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is a constant value so no need of prepare statement.
		$wpdb->query( $select_query );
	}
	/**
	 * Truncate a table from database.
	 *
	 * @param string $table_name the name of the table to be Truncate.
	 * @return void
	 */
	public static function mo_sf_sync_truncate_table( $table_name ) {
		global $wpdb;
		$select_query = 'SET FOREIGN_KEY_CHECKS = 0';
		//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is a constant value so no need of prepare statement.
		$wpdb->query( $select_query );
		$select_query = "TRUNCATE TABLE {$table_name}";
		//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is a constant value so no need of prepare statement.
		$wpdb->query( $select_query, $table_name );
		$select_query = 'SET FOREIGN_KEY_CHECKS = 1';
		//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is a constant value so no need of prepare statement.
		$wpdb->query( $select_query );
	}

	/**
	 * Assigns the default environment to configuration object.
	 *
	 * @param array $app_config the config object having empty environment details.
	 * @return array
	 */
	public static function mo_sf_sync_update_app_config( $app_config ) {
		$app_config[ Plugin_Constants::ENVIRONMENT ]      = 'test';
		$app_config[ Plugin_Constants::ENVIRONMENT_LINK ] = 'https://test.salesforce.com';
		self::mo_sf_sync_save_settings( Plugin_Constants::CONFIG_OBJECT, $app_config );
		return $app_config;
	}

	/**
	 * Checks in an array if the given key exists and if there's any value corresponding to it.
	 *
	 * @param string $key the key to be checked.
	 * @param array  $array the array in which to be checked.
	 * @return bool
	 */
	public static function mo_sf_sync_check_isset_empty( $key, $array ) {
		if ( ! isset( $array[ $key ] ) || empty( $array[ $key ] ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Checks whether authorization was successful or not.
	 *
	 * @return bool
	 */
	public static function mo_sf_sync_is_authorization_configured() {

		$sf_response = get_option( 'mo_sf_sync_salesforce_response' );

		if ( empty( $sf_response ) || ! is_array( $sf_response ) ) {
			return false;
		}

		if ( ! self::mo_sf_sync_check_isset_empty( 'access_token', $sf_response ) || ! self::mo_sf_sync_check_isset_empty( 'refresh_token', $sf_response ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Displays a message if any notice message exists and then deletes the option corresponding to the message from database.
	 *
	 * @return void
	 */
	public static function mo_sf_sync_show_feedback_message() {
		$message = self::mo_sf_sync_get_settings( 'mo_sf_sync_notice_message' );
		if ( empty( $message ) ) {
			return;
		}
		$message_type = self::mo_sf_sync_get_settings( 'mo_sf_sync_notice_message_type' );

		echo '<div class= "mo-sf-sync-alert mo-sf-sync-alert-' . esc_attr( $message_type ) . ' mo-sf-mt-4" style="width: fit-content;height: fit-content">
			<div class="mo-sf-sync-popup-text" style="margin-top:15px">
				<span class="dashicons dashicons-' . esc_attr( $message_type ) . ' " ></span>
				<span>  ' . esc_html( $message ) . ' </span>
			</div>
			</div>
		';
		self::mo_sf_sync_delete_settings( 'mo_sf_sync_notice_message' );
		self::mo_sf_sync_delete_settings( 'mo_sf_sync_notice_message_type' );
	}

	/**
	 * Displays an information message if any and then deletes the option corresponding to it form database.
	 *
	 * @return void
	 */
	public static function mo_sf_sync_show_message() {
		$message = self::mo_sf_sync_get_settings( 'mo_sf_sync_information_message' );
		if ( empty( $message ) ) {
			return;
		}
		$message_type = self::mo_sf_sync_get_settings( 'mo_sf_sync_information_message_type' );
		$allowed_html = array(
			'a' => array(
				'href' => array(),
			),
		);
		echo '
			<div class="mo-sf-sync-info-alert mo-sf-sync-info-info-alert-' . esc_attr( $message_type ) . ' mo-sf-mt-4">
				<span class="dashicons dashicons-' . esc_attr( $message_type ) . '"></span>
				<span>' . wp_kses( $message, $allowed_html ) . '</span>
			</div>
		';
		self::mo_sf_sync_delete_settings( 'mo_sf_sync_information_message' );
		self::mo_sf_sync_delete_settings( 'mo_sf_sync_information_message_type' );
	}

	/**
	 * Saves an error message in the database to be displayed if any error occurs.
	 *
	 * @param string $message the message to be saved.
	 * @return void
	 */
	public static function mo_sf_sync_show_error_message( $message ) {
		self::mo_sf_sync_save_settings( 'mo_sf_sync_notice_message', $message );
		self::mo_sf_sync_save_settings( 'mo_sf_sync_notice_message_type', 'warning' );
	}

	/**
	 * Saves an information message in the database to be displayed whenever it is relevant.
	 *
	 * @param string $message the message to be saved.
	 * @param string $message_type the message category.
	 * @return void
	 */
	public static function mo_sf_sync_information_message( $message, $message_type ) {
		self::mo_sf_sync_save_settings( 'mo_sf_sync_information_message', $message );
		self::mo_sf_sync_save_settings( 'mo_sf_sync_information_message_type', 'warning' );
	}

	/**
	 * Saves a success message in the database to be displayed when any operation is successful.
	 *
	 * @param string $message the message to be saved.
	 * @return void
	 */
	public static function mo_sf_sync_show_success_message( $message ) {
		self::mo_sf_sync_save_settings( 'mo_sf_sync_notice_message', $message );
		self::mo_sf_sync_save_settings( 'mo_sf_sync_notice_message_type', 'info' );
	}

	/**
	 * Stores a configuration message to be displayed and its category.
	 *
	 * @param string $message the message to be saved.
	 * @return void
	 */
	public static function mo_sf_sync_display_config_related_message( $message ) {
		self::mo_sf_sync_save_settings( 'mo_sf_sync_notice_message', $message );
		self::mo_sf_sync_save_settings( 'mo_sf_sync_notice_message_type', 'config-message' );
	}

	/**
	 * Shows an error message if there are any uneditable fields.
	 *
	 * @param string $message The error message to be shown.
	 * @return void
	 */
	public static function mo_sf_sync_show_error_for_updatable_fields( $message ) {
		self::mo_sf_sync_save_settings( 'mo_sf_sync_updatable_fields_notice', $message );
	}

	/**
	 * Displays an error message while saving an object mapping if there are some uneditable fields.
	 *
	 * @return void
	 */
	public static function mo_sf_sync_show_updatable_fields_info() {
		$message = self::mo_sf_sync_get_settings( 'mo_sf_sync_updatable_fields_notice' );
		if ( empty( $message ) ) {
			return;
		}
		echo '<section class="accordion mo-sf-mt-4 " >
					<input type="checkbox" name="collapse" checked="checked" >
					<h2 class="handle">
						<label class = "mo-sf-sync-coll-div-head mo_sf_sync_updatable_error" ><b>' . esc_html( $message ) . '</b></label>
					</h2>	
					<div class="mo-sf-dflex mo-sf-sync-tab-content-tile content" style="border: none;box-shadow:none">
						<span><b>Note:</b> Non-Updatable Fields in Salesforce are the fields whose values cannot be updated through APIs.
							However you can change these fields from non-updatable to updatable from your Salesforce Account. After these fields
							are marked as updatable from salesforce, the plugin will allow you to map these fields to the desired WordPress Fields.
						<span><br/><br/>
						<span>Here are some links which can help you out in marking fields as updatable in Salesforce:</p>
							<ul style="list-style: disc;padding-left: 45px;">
								<li>Change the Field Type From Non Updatable to Updatable Using This <a href="">[LINK]</a></li>
								<li>Make Salesforce Object Accessible through REST APIs using This <a href="">[LINK]</a></li>
								<li>Change Permission Sets For Users Using This <a href="">[LINK]</a></li>
							</ul>
						<span>Please reach out to us if you face any queries or if you have any difficulties.
						You can either mail us at <b>salesforcesupport@xecurify.com</b> or Submit your query to us using the support form adjacent to this information box.
						</span>
					</div>	
				</section>';
		self::mo_sf_sync_delete_settings( 'mo_sf_sync_updatable_fields_notice' );
	}

	/**
	 * Resets plugin configuration.
	 *
	 * @return void
	 */
	public static function mo_sf_sync_reset_plugin() {
		if ( self::mo_sf_sync_check_option_admin_referer( 'mo_sf_sync_reset' ) ) {

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
			self::mo_sf_sync_truncate_table( 'mo_sf_sync_audit_log_db' );
			self::mo_sf_sync_truncate_table( 'mo_sf_sync_object_mapping_meta' );
			self::mo_sf_sync_truncate_table( 'mo_sf_sync_object_mapping' );
		}
	}

	/**
	 * Calls the function to delete a mapping as per the mapping label.
	 *
	 * @param string $mapping_label label of the mapping to be deleted.
	 * @return void
	 */
	public static function mo_sf_sync_delete_mapping( $mapping_label ) {
		$db = DB_Utils::instance();
		$db->mo_sf_sync_delete_mapping_from_db( $mapping_label );
	}

	/**
	 * Checks nonce of an input field.
	 *
	 * @param string $option_name the input field whose nonce to be checked.
	 * @return bool
	 */
	public static function mo_sf_sync_check_option_admin_referer( $option_name ) {
		return ( isset( $_POST['option'] ) && $_POST['option'] === $option_name && check_admin_referer( $option_name ) );
	}

	/**
	 * Decides whether pardot integration is enabled or not.
	 *
	 * @return bool
	 */
	public static function mo_sf_sync_is_pardot_configured() {
		$client_config = self::mo_sf_sync_get_settings( Plugin_Constants::CONFIG_OBJECT );
		if ( isset( $client_config[ Plugin_Constants::IS_PARDOT_ENABLED ] ) && 'on' === $client_config[ Plugin_Constants::IS_PARDOT_ENABLED ] ) {
			return true;
		}
		return false;
	}

	/**
	 * Converts the url in the embedded code to https.
	 *
	 * @param string $embed_code code to be embedded in the post.
	 * @return string
	 */
	public static function mo_sf_sync_convert_embed_code_to_https( $embed_code ) {
		if ( self::mo_sf_sync_is_pardot_configured() ) {
			$reg_ex_url = apply_filters( 'mo_sf_sync_pardot_https_regex', "/(http|https)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,63}(\/\S[^'\"]*)?/" );
			preg_match_all( $reg_ex_url, $embed_code, $urls );
			$reg_ex_url_http_only = "/(http)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,63}(\/\S[^'\"]*)?/";
			foreach ( $urls[0] as $url ) {
				$pardot_domain_link = self::mo_sf_sync_get_settings( Plugin_Constants::CONFIG_OBJECT )[ Plugin_Constants::PARDOT_DOMAIN_LINK ];
				if ( strcasecmp( substr( $url, 0, 8 ), 'https://' ) ) {
					$urlpieces  = wp_parse_url( $url );
					$httpsurl   = 'https://go.' . Plugin_Constants::PARDOT_DOMAIN_WITHOUT_SSL[ $pardot_domain_link ] . $urlpieces['path'];
					$embed_code = preg_replace( $reg_ex_url_http_only, $httpsurl, $embed_code, 1 );
				}
			}

			return $embed_code;
		}
	}

	/**
	 * Performs an HTTP request and retrieves response.
	 *
	 * @param string  $url URL to retrieve.
	 * @param array   $args Request arguments.
	 * @param boolean $is_get if the request should be made by GET method or not.
	 * @return array|bool
	 */
	public static function mo_sf_sync_wp_remote_call( $url, $args = array(), $is_get = false ) {
		if ( ! $is_get ) {
			$response = wp_remote_post( $url, $args );
		} else {
			$response = wp_remote_get( $url, $args );
		}
		if ( ! is_wp_error( $response ) ) {
			return $response['body'];
		} else {
			self::mo_sf_sync_show_error_message( 'Unable to connect to the Internet. Please try again.' );
			return false;
		}
	}

	/**
	 * Checks whether the value of a particular attribute is empty or null.
	 *
	 * @param string $value The value to be checked.
	 * @return bool
	 */
	public static function mo_sf_sync_check_empty_or_null( $value ) {
		if ( ! isset( $value ) || empty( $value ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Sets or updates a transient.
	 *
	 * @param string       $key Transient name.
	 * @param array|string $value Transient value.
	 * @param integer      $expiration expiry of transient.
	 * @return void
	 */
	public static function mo_sf_sync_set_transient( $key, $value, $expiration = 0 ) {
		set_transient( $key, $value, $expiration );
	}

	/**
	 * Retrieves the value of a transient.
	 *
	 * @param string $key the name with which a transient was stored.
	 * @return void
	 */
	public static function mo_sf_sync_get_transient( $key ) {
		get_transient( $key );
	}

	/**
	 * Checks if class corresponding to a integration exists or not.
	 *
	 * @return array
	 */
	public static function mo_sf_sync_class_exists_check() {
		$integrations = array();
		foreach ( Plugin_Constants::INTEGRATIONS_ADVERTISEMENT as $key => $value ) {
			if ( class_exists( $key ) ) {
				if ( empty( $integrations ) ) {
					$integrations = array( $value );
				} else {
					array_push( $integrations, $value );
				}
			}
		}
		return $integrations;
	}
}
