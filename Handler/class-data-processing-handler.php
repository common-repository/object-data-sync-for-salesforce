<?php
/**
 * This file handles the data processing requests.
 *
 * @package object-data-sync-for-salesforce\Handler
 */

namespace MoSfSyncSalesforce\Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Attribute;
use MoSfSyncSalesforce\Helper\Instance;
use MoSfSyncSalesforce\Helper\Plugin_Constants;
use MoSfSyncSalesforce\Services\DB_Utils;
use MoSfSyncSalesforce\Services\Utils;
use MoSfSyncSalesforce\API\Salesforce;
use MoSfSyncSalesforce\Services\Audit_DB;

if ( defined( 'ultimatemember_version' ) ) {
	include_once WP_PLUGIN_DIR . '/ULTIMATE-MEMBER/includes/class-init.php';
}

/**
 * This class is responsible for handling data processing requests.
 */
class Data_Processing_Handler {
	use Instance;

	/**
	 * Instance of Ajax_Handler.
	 *
	 * @var Ajax_Handler
	 */
	private $ajax_handler;

	/**
	 * Instance of DB_Utils.
	 *
	 * @var DB_Utils
	 */
	private $db_utils;

	/**
	 * Object of Salesforce class.
	 *
	 * @var Salesforce
	 */
	private $salesforce;

	/**
	 * Stores the state of enable audit toggle.
	 *
	 * @var bool
	 */
	private $enable_audit_logs;

	/**
	 * Instance of Audit_DB.
	 *
	 * @var Audit_DB
	 */
	private $audit;

	/**
	 * Instance of Object_Sync_Sf_To_Wp.
	 *
	 * @var Object_Sync_Sf_To_Wp
	 */
	private $object_sync_sf_to_wp;

	/**
	 * Creates instance of the class.
	 *
	 *  @return __CLASS__
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance                       = new self();
			self::$instance->salesforce           = new Salesforce();
			self::$instance->enable_audit_logs    = get_option( Plugin_Constants::AUDIT_LOGS );
			self::$instance->ajax_handler         = Ajax_Handler::instance();
			self::$instance->db_utils             = DB_Utils::instance();
			self::$instance->audit                = Audit_DB::instance();
			self::$instance->object_sync_sf_to_wp = new Object_Sync_Sf_To_Wp();
		}
		return self::$instance;
	}

	/**
	 * Modifies the field mapping data before saving in the database.
	 *
	 * @param array $post_data An array of the field mapping data.
	 * @return void
	 */
	public function mo_sf_sync_process_raw_mapping_data( $post_data ) {
		$field_mapping = array(
			'field_map' => array(
				'field_type'      => array(),
				'field_mapping'   => array(),
				'name_constraint' => array(),
				'type_constraint' => array(),
				'size_constraint' => array(),
			),
		);

		if ( empty( $post_data ) ) {
			Utils::mo_sf_sync_show_error_message( 'Empty Data cannot be saved. ' );
			return;
		}

		$sync_direction = isset( $post_data['sync_wp_to_sf'] ) ? 'sync_wp_to_sf' : 'sync_sf_to_wp';

		if ( isset( $post_data['updatable_status'] ) && in_array( 'non_updatable', $post_data['updatable_status'], true ) && 'sync_wp_to_sf' === $sync_direction ) {
			Utils::mo_sf_sync_show_error_for_updatable_fields( 'Record Cannot Be Saved Because You Tried To Map Some Non-Updatable Fields.' );
			return;
		}
		$salesforce_object             = sanitize_text_field( $post_data['object_select'] );
		$wordpress_object              = sanitize_text_field( $post_data['wp_object_select'] );
		$post_data['wordpress_fields'] = array_values( $post_data['wordpress_fields'] );

		$field_mapping['field_map']['field_type'] = $post_data['field_types'];
		foreach ( $post_data['sf_fields'] as $key => $value ) {
			$field_mapping['field_map']['field_mapping'][ $value ] = $post_data['wordpress_fields'][ $key ];
		}
		$field_mapping['field_map']['name_constraint'] = $post_data['name_constraint'];
		$field_mapping['field_map']['type_constraint'] = $post_data['type_constraint'];
		$field_mapping['field_map']['size_constraint'] = $post_data['maxlength_constraint'];
		$this->db_utils->mo_sf_sync_save_mapping_in_object_table( $salesforce_object, $wordpress_object, $sync_direction, $field_mapping );
		Utils::mo_sf_sync_show_success_message( 'Data Saved Successfully !' );
	}

	/**
	 * Fetches and returns an array of data required for the sync.
	 *
	 * @param int    $id WordPress id of the WordPress object.
	 * @param string $wp_object Name of the WordPress Object.
	 * @return mixed
	 */
	public function mo_sf_sync_get_mapping( $id, $wp_object = 'user' ) {

		$db  = DB_Utils::instance();
		$map = $db->mo_sf_sync_get_all_mapping_data();

		if ( empty( $map ) ) {
			return null;
		}

		$direction = 'wp_to_sf';
		if ( isset( $map['sync_sf_to_wp'] ) && '1' === $map['sync_sf_to_wp'] ) {
			$direction = 'sf_to_wp';
		}

		$object = isset( $map['salesforce_object'] ) ? $map['salesforce_object'] : '';

		if ( empty( $object ) || empty( $map['field_mapping'] ) ) {
			return null;
		}

		$object_table_structure = $this->object_sync_sf_to_wp->mo_sf_sync_get_wp_db_table_structure( $wp_object );
		$id_field               = $object_table_structure['id_field'];
		$wprecord_method        = $object_table_structure['content_methods']['read'];

		if ( substr_compare( $wprecord_method, '_by', -strlen( '_by' ) ) === 0 ) {
			$wprecord = (array) $wprecord_method( $id_field, $id )->data;
		} elseif ( 'get_posts' === $wprecord_method ) {
			$wprecord = get_post( $id, ARRAY_A );
		}

		$wp_meta_method = $object_table_structure['meta_methods']['read'];
		$wp_meta        = (array) $wp_meta_method( $id );

		$field_map = maybe_unserialize( $map['field_mapping'] )['field_map'];

		$field_map_iterator = 0;

		foreach ( $field_map['field_mapping'] as $key => $value ) {

			$sf_field_type = $field_map['type_constraint'][ $field_map_iterator ];
			$mapping_type  = $field_map['field_type'][ $field_map_iterator ];

			if ( 'picklist' === $sf_field_type ) {
				if ( is_array( $value ) ) {
					$new_map[ $key ] = $this->mo_sf_sync_decide_value_based_on_conditions( $value, $wprecord, $wp_meta );
					if ( empty( $new_map[ $key ] ) ) {
						unset( $new_map[ $key ] );
					}
				} else {
					$new_map[ $key ] = $value;
				}
			} else {
				if ( 'static' === $mapping_type ) {
					$new_map[ $key ] = $value;
				} else {
					$new_map[ $key ] = $this->mo_sf_sync_read_value_from_wp_data( $value, $wprecord, $wp_meta );
				}
			}
			$field_map_iterator++;
		}

		return array(
			'object'    => $object,
			'body'      => $new_map,
			'wp_object' => $wp_object,
			'direction' => $direction,
		);
	}

	/**
	 * Fetches values of WordPress fields from the database.
	 *
	 * @param string $wp_attr Name of the WordPress field.
	 * @param array  $wp_record Non meta records.
	 * @param array  $wp_meta Meta records.
	 * @return mixed
	 */
	public function mo_sf_sync_read_value_from_wp_data( $wp_attr, $wp_record, $wp_meta ) {
		if ( ( ! empty( $wp_record[ $wp_attr ] ) || ( isset( $wp_meta[ $wp_attr ] ) && $wp_meta[ $wp_attr ][0] ) ) ) {
			return ! empty( $wp_record[ $wp_attr ] ) ? $wp_record[ $wp_attr ] : $wp_meta[ $wp_attr ][0];
		}
	}

	/**
	 * Decides the value to be synced based on picklist conditions.
	 *
	 * @param array $conditions rules to decide the value to be synced.
	 * @param array $wp_record Non meta records.
	 * @param array $wp_meta Meta records.
	 * @return string
	 */
	public function mo_sf_sync_decide_value_based_on_conditions( $conditions, $wp_record, $wp_meta ) {
		$user_field_data  = array();
		$conditions_count = count( $conditions['picklist_wp_fields'] );
		for ( $condition_iterator = 0; $condition_iterator < $conditions_count; $condition_iterator++ ) {
			$wp_field  = $conditions['picklist_wp_fields'][ $condition_iterator ];
			$condition = $conditions['picklist_conditions'][ $condition_iterator ];
			$match     = $conditions['picklist_output'][ $condition_iterator ];
			$result    = $conditions['picklist_result'][ $condition_iterator ];

			if ( ! isset( $user_field_data[ $wp_field ] ) ) {
				$user_field_data[ $wp_field ] = $this->mo_sf_sync_read_value_from_wp_data( $wp_field, $wp_record, $wp_meta ) ?? '';
			}
			if ( $this->mo_sf_sync_evaluate_condition( trim( $user_field_data[ $wp_field ] ), trim( $match ), trim( $condition ) ) ) {
				return $result;
			}
		}
		return '';
	}

	/**
	 * Evaluates the conditions for the sync.
	 *
	 * @param string $operand1 WordPress field name.
	 * @param string $operand2 value to be considered for operation.
	 * @param string $operator type of operation.
	 * @return bool
	 */
	public function mo_sf_sync_evaluate_condition( $operand1, $operand2, $operator ) {
		switch ( $operator ) {
			case 'starts-with':
				return strpos( $operand1, $operand2 ) === 0;
			case 'ends-with':
				return ( ( strrpos( $operand1, $operand2 ) + strlen( $operand2 ) ) === strlen( $operand1 ) );
			case 'includes':
				return ( strpos( $operand1, $operand2 ) !== false );
			case 'must-not-include':
				return ( strpos( $operand1, $operand2 ) === false );
		}
		return false;
	}

	/**
	 * Initiates WordPress to Salesforce Sync Flow.
	 *
	 * @param int    $wpid WordPress id of the WordPress object.
	 * @param string $wp_object Name of the WordPress Object.
	 * @return mixed
	 */
	public function mo_sf_sync_push_to_salesforce( $wpid, $wp_object = 'user' ) {
		$map = $this->mo_sf_sync_get_mapping( $wpid, $wp_object );

		if ( isset( $map['direction'] ) && 'sf_to_wp' === $map['direction'] ) {
			$error          = new \stdClass();
			$error->message = 'Mapping Saved is for Salesforce to WordPress Sync';
			return $error;
		}

		if ( is_null( $map ) ) {
			$error          = new \stdClass();
			$error->message = 'Object and mapping not saved';
			return $error;
		}
		$object_table_structure = $this->object_sync_sf_to_wp->mo_sf_sync_get_wp_db_table_structure( $wp_object );
		$meta_fetch_method      = $object_table_structure['meta_methods']['read'];
		$sfid                   = $meta_fetch_method( $wpid, 'salesforce_' . $map['object'] . '_ID', true );
		if ( ! empty( $sfid ) ) {
			$response = $this->salesforce->mo_sf_sync_update_record( $sfid, $map['object'], $map['body'] );
			if ( is_array( $response ) && count( $response ) && isset( $response[0]['errorCode'] ) && 'ENTITY_IS_DELETED' === $response[0]['errorCode'] ) {
				return $this->mo_sf_sync_create_record_in_salesforce( $map, $wpid, $object_table_structure, $wp_object );
			}
			if ( $this->enable_audit_logs ) {
				if ( is_array( $response ) && count( $response ) && isset( $response[0]['errorCode'] ) ) {
					$this->audit->mo_sf_sync_add_log( Plugin_Constants::WPTOSF, $sfid, $wpid, 'Update', 'Failed', $response[0]['message'], $wp_object );
				} else {
					$this->audit->mo_sf_sync_add_log( Plugin_Constants::WPTOSF, $sfid, $wpid, 'Update', 'Success', 'Sync Successful with ' . $map['object'], $wp_object );
				}
			} else {
				return $response;
			}
		} else {
			return $this->mo_sf_sync_create_record_in_salesforce( $map, $wpid, $object_table_structure, $wp_object );
		}
	}

	/**
	 * Calls the create record in salesforce API function to create a record in salesforce.
	 *
	 * @param array  $map Data to be synced to salesforce.
	 * @param int    $wpid WordPress Object Id.
	 * @param array  $object_table_structure table structure for WordPress object.
	 * @param string $wp_object WordPress Object Name.
	 * @return array
	 */
	public function mo_sf_sync_create_record_in_salesforce( $map, $wpid, $object_table_structure, $wp_object ) {
		if ( ! isset( $map ) ) {
			wp_send_json_success(
				array(
					array( 'message' => 'Something went wrong processing the request!' ),
				)
			);
			return;
		}
		$response = $this->salesforce->mo_sf_sync_create_record( $map['object'], $map['body'] );
		if ( isset( $response['id'] ) ) {
			$meta_update_method = $object_table_structure['meta_methods']['update'];
			$meta_update_method( $wpid, 'salesforce_' . $map['object'] . '_ID', sanitize_text_field( $response['id'] ) );
		}
		if ( $this->enable_audit_logs ) {
			if ( isset( $response[0]['errorCode'] ) ) {
				$this->audit->mo_sf_sync_add_log( Plugin_Constants::WPTOSF, 'Creation Failed', $wpid, 'Create', 'Failed', $response[0]['message'], $wp_object );
			} elseif ( isset( $response['id'] ) ) {
				$this->audit->mo_sf_sync_add_log( Plugin_Constants::WPTOSF, $response['id'], $wpid, 'Create', 'Success', 'Sync Successful with ' . $map['object'], $wp_object );
			}
		}
		return $response;
	}

	/**
	 * Creates and sends the composite request to salesforce for WordPress to Salesforce sync using composite APIs.
	 *
	 * @param array $wpid_list list of WordPress Object Ids.
	 * @param array $sfid_list list of salesforce Ids.
	 * @param array $request_content array of data required for the sync.
	 * @return array
	 */
	public function mo_sf_sync_create_composite_request( $wpid_list, $sfid_list, $request_content ) {
		$composite_request = array();
		$i                 = 0;
		foreach ( $wpid_list as $wpids ) {
			$sub_request = array();
			$object_name = $request_content[ $wpids ]['object'];
			if ( ! empty( $sfid_list[ $wpids ] ) ) {
				$method = 'PATCH';
				$url    = '/services/data/' . $this->salesforce->api_version . '/sobjects/' . $object_name;
				$url   .= '/' . $sfid_list[ $wpids ];
			} else {
				$method = 'POST';
				$url    = '/services/data/' . $this->salesforce->api_version . '/sobjects/' . $object_name;
			}

			$random_no = strval( wp_rand() );
			$refid     = 'ref' . $object_name . $random_no;
			$body      = $request_content[ $wpids ]['body'];

			$sub_request['method']      = $method;
			$sub_request['url']         = $url;
			$sub_request['referenceId'] = $refid;
			$sub_request['body']        = $body;

			$composite_request[ $i ] = $sub_request;
			++$i;
		}
		$access_token           = $this->salesforce->access_token;
		$composite_url          = $this->salesforce->instance_url . '/services/data/' . $this->salesforce->api_version . '/composite';
		$headers                = array(
			'Authorization' => 'Bearer ' . $access_token,
			'Content-Type'  => 'application/json',
		);
		$composite_request_body = wp_json_encode( array( 'compositeRequest' => $composite_request ) );

		$request = $this->salesforce->mo_sf_sync_send_composite_request( $composite_url, $headers, $composite_request_body );
		return $request;

	}

	/**
	 * Handles the composite API requests to sync data from WordPress to Salesforce.
	 *
	 * @param array  $wpid_list list of WordPress object ids.
	 * @param string $wp_object WordPress Object Name.
	 * @return string
	 */
	public function mo_sf_sync_composite_call_handler( $wpid_list, $wp_object = 'user' ) {
		$object_table_structure    = $this->object_sync_sf_to_wp->mo_sf_sync_get_wp_db_table_structure( $wp_object );
		$meta_fetch_method         = $object_table_structure['meta_methods']['read'];
		$composite_request_content = array();
		foreach ( $wpid_list as $wpid ) {
			$map                                = $this->mo_sf_sync_get_mapping( $wpid, $wp_object );
			$sfid_list[ $wpid ]                 = $meta_fetch_method( $wpid, 'salesforce_' . $map['object'] . '_ID', true );
			$composite_request_content[ $wpid ] = $map;
			$composite_request_content[ $wpid ]['nomenclature'] = 'salesforce_' . $map['object'] . '_ID';

		}
		$response                 = $this->mo_sf_sync_create_composite_request( $wpid_list, $sfid_list, $composite_request_content );
		$composite_response_array = $response['compositeResponse'];
		$meta_fetch_method        = $object_table_structure['meta_methods']['read'];
		$meta_update_method       = $object_table_structure['meta_methods']['update'];
		$i                        = 0;
		foreach ( $wpid_list as $wpid ) {
			$map              = $this->mo_sf_sync_get_mapping( $wpid, $wp_object );
			$update_failed    = false;
			$current_response = $composite_response_array[ $i ]['body'];
			if ( isset( $current_response['id'] ) ) {
				if ( isset( $composite_request_content[ $wpid ]['nomenclature'] ) && ! empty( $composite_request_content[ $wpid ]['nomenclature'] ) ) {
					$meta_update_method( $wpid, $composite_request_content[ $wpid ]['nomenclature'], sanitize_text_field( $current_response['id'] ) );
				} else {
					$meta_update_method( $wpid, 'salesforce_' . $composite_request_content[ $wpid ]['object'] . '_ID', sanitize_text_field( $current_response['id'] ) );
				}
			} elseif ( is_array( $current_response ) && count( $current_response ) && isset( $current_response[0]['errorCode'] ) ) {
				if ( 'ENTITY_IS_DELETED' === $current_response[0]['errorCode'] ) {
					$res = $this->mo_sf_sync_create_record_in_salesforce( $map, $wpid, $object_table_structure, $wp_object );
					if ( isset( $res[0]['errorCode'] ) ) {
						$update_failed = true;
					}
					$current_response = $res;
				}
			}
			if ( $this->enable_audit_logs ) {
				$status_code = $composite_response_array[ $i ]['httpStatusCode'];
				$sf_obj      = $composite_request_content[ $wpid ]['object'];
				if ( is_array( $current_response ) && count( $current_response ) && isset( $current_response[0]['errorCode'] ) && '204' !== $status_code ) {
					if ( $update_failed ) {
						$this->audit->mo_sf_sync_add_log( Plugin_Constants::WPTOSF, $meta_fetch_method( $wpid, $composite_request_content[ $wpid ]['nomenclature'], true ), $wpid, 'Update', 'Failed', $current_response[0]['message'], $wp_object );
					} else {
						$this->audit->mo_sf_sync_add_log( Plugin_Constants::WPTOSF, 'Creation Failed', $wpid, 'Create', 'Failed', $current_response[0]['message'], $wp_object );
					}
				} elseif ( 204 === $status_code ) {
					$this->audit->mo_sf_sync_add_log( Plugin_Constants::WPTOSF, $meta_fetch_method( $wpid, $composite_request_content[ $wpid ]['nomenclature'], true ), $wpid, 'Update', 'Success', 'Sync Successful with ' . $sf_obj, $wp_object );

				} else {
					$this->audit->mo_sf_sync_add_log( Plugin_Constants::WPTOSF, $current_response['id'], $wpid, 'Create', 'Success', 'Sync Successful with ' . $sf_obj, $wp_object );
				}
			}
			++$i;
		}

		return 'Composite Call Complete';
	}

	/**
	 * Save state for realtime sync toggle
	 *
	 * @param array $post_data state of realtime sync toggle.
	 * @return void
	 */
	public function mo_sf_sync_save_realtime_sync( $post_data ) {
		if ( array_key_exists( 'automatic_user_update', $post_data ) ) {
			$map[ Plugin_Constants::AUTO_USER_UPDATE ] = 'on';
			Utils::mo_sf_sync_save_settings( Plugin_Constants::PROVISION_OBJECT, $map );
		} else {
			$map[ Plugin_Constants::AUTO_USER_UPDATE ] = 'off';
			Utils::mo_sf_sync_save_settings( Plugin_Constants::PROVISION_OBJECT, $map );
		}
		Utils::mo_sf_sync_show_success_message( 'Saved Configuration Successfully !!' );
	}
	/**
	 * Fetches the selected pardot form.
	 *
	 * @param array $attributes contains form id and other styles for embedding.
	 * @return array
	 */
	public function mo_sf_sync_get_pardot_form_body( $attributes ) {
		$body_html = false;
		if ( isset( $attributes['form_id'] ) ) {
			$form_id = $attributes['form_id'];
			$forms   = $this->salesforce->mo_sf_sync_get_pardot_forms();
			if ( ! isset( $forms['values'] ) ) {
				return;
			}
			foreach ( $forms['values'] as $form ) {
				//phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison -- form id comparision therefore strict comparision not required.
				if ( $form_id == $form['id'] ) {
					$selected_form = $form;
					break;
				}
			}
			if ( isset( $selected_form['embedCode'] ) ) {
				$body_html = $selected_form['embedCode'];
			}
			if ( ! empty( $attributes['height'] ) ) {
				$height = $attributes['height'];
				preg_match( '#height="[^"]+"#', $body_html, $matches );
				$body_html = str_replace( $matches[0], "height=\"{$height}\"", $body_html );
			}
			if ( ! empty( $attributes['width'] ) ) {
				$width = $attributes['width'];
				preg_match( '#width="[^"]+"#', $body_html, $matches );
				$body_html = str_replace( $matches[0], "width=\"{$width}\"", $body_html );
			}
			if ( ! empty( $attributes['class'] ) ) {
				$class     = $attributes['class'];
				$body_html = str_replace( '<iframe', "<iframe class=\"mo-sf-sync-pardotform {$class}\"", $body_html );
			}
		}
		$body_html = Utils::mo_sf_sync_convert_embed_code_to_https( $body_html );
		return $body_html;
	}

	/**
	 * Fetches the selected dynamic content.
	 *
	 * @param array $attributes contains dynamic content id and other styles for embedding.
	 * @return array
	 */
	public function mo_sf_sync_dynamic_content_body( $attributes ) {
		$dynamic_content_html = false;
		if ( isset( $attributes['dynamicContent_id'] ) ) {
			$dynamic_content_id = $attributes['dynamicContent_id'];
			$dynamic_content    = $this->salesforce->mo_sf_sync_get_dynamic_content_using_dy_cn_id( $dynamic_content_id );
			if ( isset( $dynamic_content['code'] ) ) {
				return;
			}
			$dynamic_content_html    = $dynamic_content['embedCode'];
			$dynamic_content_url     = esc_url( $dynamic_content['embedUrl'] );
			$dynamic_content_default = $dynamic_content['baseContent'];
			if ( $dynamic_content_url ) {
				$dynamic_content_html = "<div data-dc-url='" . $dynamic_content_url . "' style='height:auto;width:auto;' class='mo-sf-sync-pardotdc'>" . $dynamic_content_default . '</div>';
			} else {
				$dynamic_content_html = $dynamic_content_html . '<noscript>' . $dynamic_content_default . '</noscript>';
			}
			if ( ! empty( $attributes['height'] ) ) {
				$dynamic_content_html = str_replace( 'height:auto', "height:{$attributes['height']}", $dynamic_content_html );
			}

			if ( ! empty( $attributes['width'] ) ) {
				$dynamic_content_html = str_replace( 'width:auto', "width:{$attributes['width']}", $dynamic_content_html );
			}

			if ( ! empty( $attributes['class'] ) ) {
				$dynamic_content_html = str_replace( 'mo-sf-sync-pardotdc', "mo-sf-sync-pardotdc {$attributes['class']}", $dynamic_content_html );
			}
		}
		$dynamic_content_html = Utils::mo_sf_sync_convert_embed_code_to_https( $dynamic_content_html );
		return $dynamic_content_html;
	}
}
