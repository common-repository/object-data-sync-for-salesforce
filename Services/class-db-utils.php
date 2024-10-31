<?php
/**
 *  This file is responsible for handling database calls to the custom field mapping tables in the WordPress database.
 *
 * @package object-data-sync-for-salesforce\Services
 */

namespace MoSfSyncSalesforce\Services;

use MoSfSyncSalesforce\Helper\Instance;
use MoSfSyncSalesforce\Helper\Plugin_Constants;

/**
 * This class is responsible for handling database calls to the custom field mapping tables in the WordPress database.
 */
class DB_Utils {
	use Instance;
	/**
	 *
	 * Global object of `$wpdb`, the WordPress database.
	 *
	 * @var object
	 */
	public $db;

	/**
	 *
	 * Name of the field mapping custom table.
	 *
	 * @var string
	 */
	public $table_name;

	/**
	 *
	 * Name of the field mapping custom meta table.
	 *
	 * @var string
	 */
	public $meta_table_name;

	/**
	 *
	 * Creates instance of the class.
	 *
	 * @return __CLASS__
	 */
	public static function instance() {
		global $wpdb;
		self::$instance                  = new self();
		self::$instance->db              = $wpdb;
		self::$instance->table_name      = 'mo_sf_sync_object_mapping';
		self::$instance->meta_table_name = 'mo_sf_sync_object_mapping_meta';
		return self::$instance;
	}

	/**
	 *
	 * Creates field mapping custom table and custom meta in the database.
	 *
	 * @return void
	 */
	public function mo_sf_sync_create_mapping_tables() {
		$current_charset_collate = $this->db->get_charset_collate();
		$create_table_query      = "CREATE TABLE IF NOT EXISTS $this->table_name(
            id bigint(20) NOT NULL AUTO_INCREMENT,
            label varchar(64) NOT NULL,
            salesforce_object varchar(255) NOT NULL,
            wordpress_object varchar(128) NOT NULL,
            sync_sf_to_wp tinyint(1) NOT NULL,
            sync_wp_to_sf tinyint(1) NOT NULL,
            PRIMARY KEY (id)
          )ENGINE=InnoDB $current_charset_collate";

		$this->db->get_results( $create_table_query );

		$create_meta_table_query = "CREATE TABLE IF NOT EXISTS $this->meta_table_name (
            id tinyint(4) NOT NULL AUTO_INCREMENT,
            mapping_id bigint(20) NOT NULL,
            meta_key longtext NOT NULL,
            meta_value longtext NOT NULL,
            PRIMARY KEY (id),
            FOREIGN KEY (mapping_id) REFERENCES $this->table_name(id)
          )";
		$this->db->get_results( $create_meta_table_query );
	}

	/**
	 *
	 * Saves field mapping in the created custom tables.
	 *
	 * @param string $sf_object Salesforce object name.
	 * @param string $wp_object WordPress object name.
	 * @param string $direction Direction of the sync, either from salesforce to WordPress or from WordPress to Salesforce.
	 * @param string $field_map Field mapping between the salesforce and WordPress fields.
	 * @return void
	 */
	public function mo_sf_sync_save_mapping_in_object_table( $sf_object, $wp_object, $direction, $field_map ) {
		if ( 'sync_sf_to_wp' === $direction ) {
			$sync_sf_to_wp = 1;
			$sync_wp_to_sf = 0;
		} else {
			$sync_sf_to_wp = 0;
			$sync_wp_to_sf = 1;
		}
		$data       = array(
			'label'             => $sf_object . '_' . $wp_object . '_Map',
			'salesforce_object' => $sf_object,
			'wordpress_object'  => $wp_object,
			'sync_sf_to_wp'     => $sync_sf_to_wp,
			'sync_wp_to_sf'     => $sync_wp_to_sf,
		);
		$format     = array( '%s', '%s', '%s', '%d', '%d' );
		$mapping_id = $this->mo_sf_sync_if_mapping_already_exist();

		if ( $sync_sf_to_wp ) {
			$outbound_redirect_uri = home_url() . '/?method=soap&action=store&mappinglabel=' . rawurlencode( $data['label'] ) . '&accesskey=' . $this->mo_sf_sync_create_key();
		} else {
			$outbound_redirect_uri = '';
		}

		if ( ! empty( $mapping_id ) ) {
			$this->db->update( $this->table_name, $data, array( 'id' => $mapping_id ), $format, array( '%d' ) );
			if ( ! empty( $outbound_redirect_uri ) ) {
				$this->mo_sf_sync_save_data_in_meta_table( $mapping_id, 'outbound_redirect_uri', $outbound_redirect_uri );
			}
		} else {
			$this->db->insert( $this->table_name, $data, $format );
			$mapping_id = $this->db->insert_id;
			if ( ! empty( $outbound_redirect_uri ) ) {
				$this->mo_sf_sync_save_data_in_meta_table( $mapping_id, 'outbound_redirect_uri', $outbound_redirect_uri );
			}
		}
		$this->mo_sf_sync_save_data_in_meta_table( $mapping_id, 'field_mapping', $field_map );
	}

	/**
	 *
	 * Creates an access key.
	 *
	 * @return string
	 */
	private function mo_sf_sync_create_key() {
		$token = bin2hex( random_bytes( 8 ) );
		return $token;
	}

	/**
	 *
	 * Checks if a field mapping already exists.
	 *
	 * @return mixed
	 */
	private function mo_sf_sync_if_mapping_already_exist() {
		$select_query = "SELECT id from $this->table_name LIMIT 1";
		$result       = $this->db->get_results( $select_query );

		if ( ! empty( $result ) ) {
			$id = $result[0]->id;
			return $id;
		} else {
			return false;
		}
	}

	/**
	 * Saves data in field mapping custom meta table.
	 *
	 * @param int    $mapping_id  Identifier for a field mapping.
	 * @param string $meta_key    label for a value saved in the custom field mapping table.
	 * @param string $meta_value  value to be store in the the custom field mapping table.
	 * @return void
	 */
	public function mo_sf_sync_save_data_in_meta_table( $mapping_id, $meta_key, $meta_value ) {
		$data        = array(
			'mapping_id' => $mapping_id,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- we use meta_key as label to store values in custom meta table, therefore this can't be changed.
			'meta_key'   => $meta_key,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- we use meta_value to store values in custom meta table, therefore this can't be changed.
			'meta_value' => maybe_serialize( $meta_value ),
		);
		$format      = array( '%d', '%s', '%s' );
		$meta_exists = $this->mo_sf_sync_if_meta_exists( $mapping_id, $meta_key );
		if ( false !== $meta_exists ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- we use meta_key as label to store values in custom meta table, therefore this can't be changed.
			$this->db->update( $this->meta_table_name, $data, array( 'meta_key' => $meta_key ), $format, array( '%s' ) );
		} else {
			$this->db->insert( $this->meta_table_name, $data, $format );
		}
	}

	/**
	 *
	 * Checks if a meta exists for a mapping or not.
	 *
	 * @param int    $mapping_id  Identifier for a field mapping.
	 * @param string $meta_key    label for a value saved in the custom field mapping table.
	 * @return bool
	 */
	private function mo_sf_sync_if_meta_exists( $mapping_id, $meta_key ) {
		$select_query = "SELECT COUNT(*) from $this->meta_table_name where mapping_id=%d AND meta_key=%s";
		$select_query = $this->db->prepare( $select_query, array( $mapping_id, $meta_key ) );
		$result       = $this->db->get_results( $select_query, ARRAY_A );
		if ( isset( $result[0]['COUNT(*)'] ) && $result[0]['COUNT(*)'] > 0 ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 *
	 * Fetches all the field mappings.
	 *
	 * @return null|array
	 */
	public function mo_sf_sync_get_all_mapping_data() {
		$select_query = "SELECT * from $this->table_name";
		$result       = $this->db->get_results( $select_query, ARRAY_A );
		if ( isset( $result[0]['id'] ) ) {
			$id = $result[0]['id'];
		} else {
			return;
		}
		$meta_data    = $this->mo_sf_sync_get_data_from_metatable( $id );
		$mapping_data = array_merge( $result[0], maybe_unserialize( $meta_data ) );
		return $mapping_data;
	}

	/**
	 *
	 * Fetches data from field mapping meta table.
	 *
	 * @param int    $mapping_id  Identifier for a field mapping.
	 * @param string $meta_key    label for a value saved in the custom field mapping table.
	 * @return array
	 */
	public function mo_sf_sync_get_data_from_metatable( $mapping_id, $meta_key = '' ) {
		$select_query = "SELECT * from $this->meta_table_name where mapping_id = %d";
		if ( ! empty( $meta_key ) ) {
			$select_query .= ' AND meta_key = %s';
			$select_query  = $this->db->prepare( $select_query, array( $mapping_id, $meta_key ) );
		} else {
			$select_query = $this->db->prepare( $select_query, array( $mapping_id ) );
		}

		$result = $this->db->get_results( $select_query, ARRAY_A );
		$ret    = array();
		foreach ( $result as $key => $value ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- we use meta_key as label to store values in custom meta table, therefore this can't be changed.
			$ret[ $value['meta_key'] ] = $value['meta_value'];
		}

		return $ret;
	}

	/**
	 *
	 * Fetches mapping id of a mapping using mapping label.
	 *
	 * @param  string $mapping_label Label for a field mapping.
	 * @return int
	 */
	private function mo_sf_sync_get_mapping_id_from_mapping_label( $mapping_label ) {
		$select_query = "SELECT id from $this->table_name where label = %s";
		$select_query = $this->db->prepare( $select_query, array( $mapping_label ) );
		$result       = $this->db->get_results( $select_query, ARRAY_A );
		return $result[0]['id'];
	}

	/**
	 *
	 * Deletes data from field mapping meta table.
	 *
	 * @param int    $mapping_id  Identifier for a field mapping.
	 * @param string $meta_key    label for a value saved in the custom field mapping table.
	 * @return void
	 */
	public function mo_sf_sync_delete_data_from_metatable( $mapping_id, $meta_key = '' ) {
		$delete_query = "DELETE FROM $this->meta_table_name where mapping_id = %d";
		if ( ! empty( $meta_key ) ) {
			$delete_query += ' AND meta_key = %s';
			$delete_query  = $this->db->prepare( $delete_query, array( $mapping_id, $meta_key ) );
		} else {
			$delete_query = $this->db->prepare( $delete_query, array( $mapping_id ) );
		}

		$this->db->get_results( $delete_query );
	}

	/**
	 *
	 * Deletes mapping from the custom tables.
	 *
	 * @param string $mapping_label Label for a field mapping.
	 * @return void
	 */
	public function mo_sf_sync_delete_mapping_from_db( $mapping_label ) {
		$id = $this->mo_sf_sync_get_mapping_id_from_mapping_label( $mapping_label );
		$this->mo_sf_sync_delete_data_from_metatable( $id );
		$delete_query = "DELETE FROM $this->table_name WHERE label = %s";
		$delete_query = $this->db->prepare( $delete_query, array( $mapping_label ) );
		$this->db->get_results( $delete_query );
	}

	/**
	 *
	 * Inserts imported data into the database.
	 *
	 * @param array $mapping_data data to be inserted into database.
	 * @return void
	 */
	public function mo_sf_sync_insert_imported_mapping_data( $mapping_data ) {

		foreach ( Plugin_Constants::REQUIRED_FIELDS_FOR_FIELD_MAP_IMPORT as $required_value ) {
			if ( ! isset( $mapping_data[ $required_value ] ) ) {
				return;
			}
		}

		$data   = array(
			'label'             => $mapping_data['label'],
			'salesforce_object' => $mapping_data['salesforce_object'],
			'wordpress_object'  => $mapping_data['wordpress_object'],
			'sync_sf_to_wp'     => $mapping_data['sync_sf_to_wp'],
			'sync_wp_to_sf'     => $mapping_data['sync_wp_to_sf'],
		);
		$format = array( '%s', '%s', '%s', '%d', '%d' );

		foreach ( Plugin_Constants::REQUIRED_FIELDS_FOR_FIELD_MAP_IMPORT as $key_name ) {
			unset( $mapping_data[ $key_name ] );
		}

		if ( isset( $mapping_data['id'] ) ) {
			unset( $mapping_data['id'] );
		}

		$get_query = "SELECT * from $this->table_name";
		$result    = $this->db->get_results( $get_query, ARRAY_A );
		if ( isset( $result[0] ) && is_array( $result[0] ) && ! empty( $result[0] ) ) {
			$this->db->update(
				$this->table_name,
				$data,
				array(
					'salesforce_object' => $result[0]['salesforce_object'],
					'wordpress_object'  => $result[0]['wordpress_object'],
				),
				$format,
				array( '%s', '%s' )
			);
			$id = $result[0]['id'];

		} else {
			$this->db->insert( $this->table_name, $data, $format );
			$id = $this->db->insert_id;
		}

		if ( ! empty( $mapping_data ) ) {
			foreach ( $mapping_data as $key => $value ) {

				if ( 'outbound_redirect_uri' === $key ) {
					$value = str_replace( substr( $value, 0, strpos( $value, '?' ) - 1 ), site_url(), $value );
				}
				$this->mo_sf_sync_save_data_in_meta_table( $id, $key, maybe_unserialize( $value ) );
			}
		}
	}

	/**
	 *
	 * Fetches salesforce record id for WordPress users.
	 *
	 * @return mixed
	 */
	public function mo_sf_sync_get_salesforce_record_id_for_wp_users() {
		$select_query = "SELECT salesforce_object FROM $this->table_name where wordpress_object = %s ";
		$select_query = $this->db->prepare( $select_query, array( 'user' ) );
		$data         = $this->db->get_results( $select_query );
		if ( ! empty( $data ) ) {
			return 'salesforce_' . $data[0]->salesforce_object . '_ID';
		}
		return false;
	}

	/**
	 *
	 * Fetches salesforce record id for WordPress posts.
	 *
	 * @return mixed
	 */
	public function mo_sf_sync_get_salesforce_record_id_for_wp_posts() {
		$select_query = "SELECT salesforce_object FROM $this->table_name where wordpress_object = %s ";
		$select_query = $this->db->prepare( $select_query, array( 'post' ) );
		$data         = $this->db->get_results( $select_query );
		if ( ! empty( $data ) ) {
			return 'salesforce_' . $data[0]->salesforce_object . '_ID';
		}
		return array();
	}

}
