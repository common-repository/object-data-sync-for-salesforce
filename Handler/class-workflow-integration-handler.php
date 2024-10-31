<?php
/**
 * This file handles the salesforce flows integration for salesforce to WordPress sync.
 *
 * @package object-data-sync-for-salesforce\Handler
 */

namespace MoSfSyncSalesforce\Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MoSfSyncSalesforce\Services\DB_Utils;
use MoSfSyncSalesforce\Services\Audit_DB;
use MoSfSyncSalesforce\Helper\Plugin_Constants;
/**
 * This class is responsible for handling the salesforce to WordPress sync using salesforce flows.
 */
class Workflow_Integration_Handler {

	/**
	 * Global object of `$wpdb`, the WordPress database.
	 *
	 * @var object
	 */
	private $wpdb;
	/**
	 * Constructor of the class.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * Parse the SOAP XML request received from salesforce.
	 *
	 * @param array $req REQUEST body when the SOAP XML is received.
	 * @return void
	 */
	public function mo_sf_sync_xml_parser( $req ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This request is received from salesforce and hence we cannot add nonce here.
		$access_key = isset( $_REQUEST['accesskey'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['accesskey'] ) ) : '';

		if ( empty( $access_key ) ) {
			$this->mo_sf_sync_xml_response();
		}

		$db = DB_Utils::instance();

		$mapping_sf_to_wp = $db->mo_sf_sync_get_all_mapping_data();

		if ( empty( $mapping_sf_to_wp ) ) {
			$this->mo_sf_sync_xml_response();
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This request is received from salesforce and hence we cannot add nonce here.
		$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';

		$result = trim( file_get_contents( 'php://input' ) );
		$xml    = preg_replace( '/(<\/?)(\w+):([^>]*>)/', '$1$2$3', $result );

		$parser_create_xml = xml_parser_create();
		xml_parse_into_struct( $parser_create_xml, $xml, $vals, $index );
		$i     = 0;
		$count = count( $vals );
		while ( $i < $count ) {
			if ( 'SOBJECT' === $vals[ $i ]['tag'] ) {
				if ( isset( $vals[ $i ]['attributes']['XSI:TYPE'] ) ) {
					$s_object = $vals[ $i ]['attributes']['XSI:TYPE'];
				}
			}
			$i++;
		}
		if ( empty( $vals ) ) {
			return;
		} else {
			$xml = simplexml_load_string( $xml );
		}
		if ( empty( $xml ) ) {
			return;
		} else {
			$json                         = wp_json_encode( $xml );
			$req                          = json_decode( $json, true );
			$req['Salesforce Object']     = str_ireplace( 'sf:', '', $s_object );
			$sf_data                      = array();
			$sf_data['Salesforce Object'] = $req['Salesforce Object'];

			if ( isset( $req['soapenvBody']['notifications']['Notification']['sObject'] ) ) {
				foreach ( $req['soapenvBody']['notifications']['Notification']['sObject'] as $key => $value ) {
					$key                                      = substr( $key, 2 );
					$sf_data['Object Information'][0][ $key ] = $value;
				}
			} else {
				$i = count( $req['soapenvBody']['notifications']['Notification'] );
				$j = 0;
				while ( $j < $i - 1 ) {
					foreach ( $req['soapenvBody']['notifications']['Notification'][ $j ]['sObject'] as $key => $value ) {
						$key = substr( $key, 2 );
						$sf_data['Object Information'][ $j ][ $key ] = $value;
					}
					$j++;
				}
			}

			$this->mo_sf_sync_route_soap_request_processing( $sf_data, $mapping_sf_to_wp, $action );
			$this->mo_sf_sync_xml_response();
		}
	}

	/**
	 * Sends the SOAP XML Response.
	 *
	 * @return void
	 */
	public function mo_sf_sync_xml_response() {

		$response = "<?xml version='1.0' encoding='UTF-8'?>
        <soapenv:Envelope xmlns:soapenv='http://schemas.xmlsoap.org/soap/envelope/' xmlns:out='http://soap.sforce.com/2005/09/outbound'>
        <soapenv:Header>
        </soapenv:Header>
        <soapenv:Body>
        <out:notificationsResponse>
        <out:Ack>true</out:Ack>
        </out:notificationsResponse>
        </soapenv:Body>
        </soapenv:Envelope>";
		header( 'HTTP/1.1 200 OK' );
		header( 'Content-Type: application/json;charset=utf-8' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this the soap xml response sent to salesforce, escaping it changes its format, therefore it can't be escaped.
		echo $response;
		exit;
	}

	/**
	 * Sends the SOAP XML Response.
	 *
	 * @param array $sf_data data received from salesforce.
	 * @param array $mapping_sf_to_wp salesforce to WordPress mapping.
	 * @param array $action operation to be performed with the data received from salesforce.
	 * @return void
	 */
	public function mo_sf_sync_route_soap_request_processing( $sf_data, $mapping_sf_to_wp, $action ) {

		switch ( $action ) {
			case 'store':
				$this->mo_sf_sync_store_data_to_wp_objects( $sf_data, $mapping_sf_to_wp );
				break;
		}
	}

	/**
	 * Initiates the save process of salesforce data to WordPress.
	 *
	 * @param array $sf_data data received from salesforce.
	 * @param array $mapping_sf_to_wp salesforce to WordPress mapping.
	 * @return void
	 */
	public function mo_sf_sync_store_data_to_wp_objects( $sf_data, $mapping_sf_to_wp ) {
		$sf_to_wp = new Object_Sync_Sf_To_Wp();

		$wp_obj_db_struct           = $sf_to_wp->mo_sf_sync_get_wp_db_table_structure( $mapping_sf_to_wp['wordpress_object'] );
		$wp_object_non_meta_columns = $this->mo_sf_sync_get_column_names( $wp_obj_db_struct['content_table'] );
		foreach ( $sf_data['Object Information'] as $key => $value ) {
			$this->mo_sf_sync_save_wp_records( array( $value ), $mapping_sf_to_wp['wordpress_object'], $sf_data['Salesforce Object'], maybe_unserialize( $mapping_sf_to_wp['field_mapping'] )['field_map'], $wp_obj_db_struct, $wp_object_non_meta_columns );
		}
	}

	/**
	 * Get the columns of a WordPress table.
	 *
	 * @param  array $table_name WordPress table name.
	 * @return array
	 */
	private function mo_sf_sync_get_column_names( $table_name ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- the parameter $table used in this query is static and its value is decided from a set of pre-defined values in the above functions, therefore no need of prepare statement here.  
		return $this->wpdb->get_col( "DESC {$table_name}", 0 );
	}

	/**
	 * Saves the salesforce records in WordPress.
	 *
	 * @param  array  $records data received from salesforce.
	 * @param  string $wp_object WordPress object name.
	 * @param  string $sf_object Salesforce object name.
	 * @param  array  $field_map Salesforce to WordPress Mapping configured.
	 * @param  array  $object_table_structure All details related to WordPress object database table structures.
	 * @param  mixed  $wp_object_non_meta_columns List of non meta columns for that specific WordPress object.
	 * @return bool
	 */
	private function mo_sf_sync_save_wp_records( $records, $wp_object, $sf_object, $field_map, $object_table_structure, $wp_object_non_meta_columns ) {
		$enable_audit_logs = get_option( Plugin_Constants::AUDIT_LOGS );
		$audit             = Audit_DB::instance();
		foreach ( $records as $record ) {
			$wp_upsert_object_id = false;
			$new_wp_object_data  = $this->mo_sf_sync_build_data_to_save( $field_map, $record );

			$sf_id = $record['Id'];

			$wp_object_data = $this->mo_sf_sync_get_record_if_already_synced( $sf_object, $sf_id, $wp_object );

			$wp_obj_id = false;

			if ( ! empty( $wp_object_data ) ) {
				$wp_obj_id = $wp_object_data[ $object_table_structure['id_field'] ];
				$new_wp_object_data[ $object_table_structure['id_field'] ] = $wp_obj_id;
				$wp_upsert_object_id                                       = $this->mo_sf_sync_upsert_wp_object( $wp_object, $new_wp_object_data, $object_table_structure, $wp_object_non_meta_columns, $wp_obj_id );
			} else {
				$new_wp_object_data[ 'salesforce_' . $sf_object . '_ID' ] = $sf_id;
				$wp_upsert_object_id                                      = $this->mo_sf_sync_upsert_wp_object( $wp_object, $new_wp_object_data, $object_table_structure, $wp_object_non_meta_columns, false );
			}
			if ( $enable_audit_logs ) {
				if ( ! empty( $wp_object_data ) ) {
					if ( $wp_upsert_object_id ) {
						$audit->mo_sf_sync_add_log( Plugin_Constants::SFTOWP, $sf_id, $wp_obj_id, 'Update', 'Success', 'Pull Successful with ' . $sf_object, $wp_object );
					} else {
						$audit->mo_sf_sync_add_log( Plugin_Constants::SFTOWP, $sf_id, $wp_obj_id, 'Update', 'Failed', 'Pull failed Cannot save data to WordPress object', $wp_object );
					}
				} else {
					if ( $wp_upsert_object_id ) {
						$audit->mo_sf_sync_add_log( Plugin_Constants::SFTOWP, $sf_id, $wp_upsert_object_id, 'Create', 'Success', 'Pull Successful with ' . $sf_object, $wp_object );
					} else {
						$audit->mo_sf_sync_add_log( Plugin_Constants::SFTOWP, $sf_id, 'Creation Failed', 'Create', 'Failed', 'Pull failed Cannot save data to WordPress object', $wp_object );
					}
				}
			}
		}
		return true;
	}

	/**
	 * Builds the data received from salesforce to be saved into WordPress.
	 *
	 * @param array $field_map Salesforce to WordPress Mapping configured.
	 * @param array $sf_record data received from salesforce.
	 * @return array
	 */
	private function mo_sf_sync_build_data_to_save( $field_map, $sf_record ) {

		$fields     = $field_map['field_mapping'];
		$data_type  = $field_map['field_type'];
		$field_type = $field_map['type_constraint'];

		$new_wp_object_data = array();
		$field_map_iterator = 0;
		foreach ( $fields as $key => $value ) {
			if ( 'picklist' === $data_type[ $field_map_iterator ] || 'static' === $field_type[ $field_map_iterator ] ) {
				continue;
			}

			if ( ! is_array( $value ) && ! empty( $sf_record[ $key ] ) ) {
				$new_wp_object_data[ $value ] = $sf_record[ $key ];
			}

			$field_map_iterator++;
		}
		return $new_wp_object_data;
	}

	/**
	 * Checks if a record is already synced or not.
	 *
	 * @param string $sf_object Salesforce object name.
	 * @param string $sf_id data salesforce record id.
	 * @param string $wp_object WordPress object name.
	 * @return mixed
	 */
	private static function mo_sf_sync_get_record_if_already_synced( $sf_object, $sf_id, $wp_object ) {
		$query_class = 'WP_Query';
		$method      = 'get_results';
		$args        = array();

		switch ( $wp_object ) {
			case 'user':
				$query_class = 'WP_User_Query';
				break;
			default:
				$args['post_type'] = $wp_object;
				$method            = 'get_posts';
		}
		$results = false;
		if ( class_exists( $query_class ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- we do need to query wpdb meta to search for salesforce record id, therefore this can't be changed.
			$args['meta_query'] = array(
				array(
					'key'   => 'salesforce_' . $sf_object . '_ID',
					'value' => $sf_id,
				),
			);
			$match_query        = new $query_class( $args );
			$results            = $match_query->$method();

			if ( empty( $results ) ) {
				return false;
			}

			if ( 'user' === $wp_object ) {
				$results = (array) $results[0]->data;
			} else {
				$results = (array) $results[0];
			}
		}

		return $results;
	}

	/**
	 * Saves data in the WordPress database.
	 *
	 * @param string $wp_object name of the WordPress object.
	 * @param array  $new_wp_object_data data to be saved in the WordPress database.
	 * @param array  $object_table_structure All details related to WordPress object database table structures.
	 * @param array  $wp_object_non_meta_columns List of non meta columns for that specific WordPress object.
	 * @param mixed  $wp_id Id of the WordPress object.
	 * @return mixed
	 */
	private function mo_sf_sync_upsert_wp_object( $wp_object, $new_wp_object_data, $object_table_structure, $wp_object_non_meta_columns, $wp_id = false ) {
		$wp_non_meta_fields = array();
		foreach ( $new_wp_object_data as $key => $value ) {
			if ( in_array( $key, $wp_object_non_meta_columns, true ) ) {
				$wp_non_meta_fields[ $key ] = $value;
				unset( $key );
			}
		}

		if ( 'post' === $object_table_structure['object_name'] ) {
			$wp_non_meta_fields['post_type'] = $wp_object;
			if ( empty( $wp_id ) ) {
				$wp_non_meta_fields['post_status'] = 'publish';
			}
		}
		$method = $object_table_structure['content_methods']['create'];
		if ( ! empty( $wp_id ) ) {
			$method = $object_table_structure['content_methods']['update'];
		}

			$wp_id = $method( $wp_non_meta_fields );

		if ( empty( $wp_id ) || is_wp_error( $wp_id ) ) {
			return false;
		}

		$meta_method = $object_table_structure['meta_methods']['update'];
		$this->mo_sf_sync_inset_meta( $new_wp_object_data, $wp_id, $meta_method );

		return $wp_id;
	}

	/**
	 * Populates meta according to the method thats passed.
	 *
	 * @param mixed $meta_array Array of key value pairs of meta.
	 * @param mixed $wp_obj_id WordPress object ID.
	 * @param mixed $method WordPress method for meta to be inserted by method determines what wordpress_object data is being inserted for.
	 * @return bool
	 */
	private function mo_sf_sync_inset_meta( $meta_array, $wp_obj_id, $method ) {
		foreach ( $meta_array as $key => $value ) {
			if ( strpos( $value, '-' ) !== false ) {
				$dt = \DateTime::createFromFormat( 'Y-m-d', $value );
				if ( $dt && $dt->format( 'Y-m-d' ) === $value ) {
					$value = str_replace( '-', '', $value );
				}
			} elseif ( strpos( $value, ';' ) !== false ) {
				$value = array_values( explode( ';', $value ) );
			}
			$method( $wp_obj_id, $key, $value );
		}
		return true;
	}

}
