<?php
/**
 *  This file is responsible to make read calls to get the fields and structure of the WordPress database tables.
 *
 * @package object-data-sync-for-salesforce\Handler
 */

namespace MoSfSyncSalesforce\Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class is responsible for handling the requests for WordPress Object database structure and WordPress fields.
 */
class Object_Sync_Sf_To_Wp {
	/**
	 *
	 * Global object of `$wpdb`, the WordPress database.
	 *
	 * @var object
	 */
	private $wpdb;
	/**
	 *
	 * Constructor of the class.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * Get WordPress data based on what WordPress object it is.
	 *
	 * @param string $wp_object WordPress Object Name.
	 * @param string $wp_id_field WordPress Object database structure unique identifier field.
	 * @return array
	 */
	public function mo_sf_sync_get_wp_obj_fields( $wp_object, $wp_id_field = 'ID' ) {
		$wp_obj_db_struct   = $this->mo_sf_sync_get_wp_db_table_structure( $wp_object );
		$wp_meta_table      = $wp_obj_db_struct['meta_table'];
		$wp_meta_methods    = maybe_unserialize( $wp_obj_db_struct['meta_methods'] );
		$wp_content_table   = $wp_obj_db_struct['content_table'];
		$wp_content_methods = maybe_unserialize( $wp_obj_db_struct['content_methods'] );
		$wp_id_field        = $wp_obj_db_struct['id_field'];
		$wp_object_name     = $wp_obj_db_struct['object_name'];
		$where_clause       = $wp_obj_db_struct['where'];
		$wp_ignore_keys     = $wp_obj_db_struct['ignore_keys'];

		$get_all_wp_obj_fields = $this->mo_sf_sync_get_all_fields_of_selected_obj( $wp_object_name, $wp_id_field, $wp_content_table, $wp_content_methods, $wp_meta_table, $wp_meta_methods, $where_clause, $wp_ignore_keys );
		return $get_all_wp_obj_fields;
	}

	/**
	 * Returns the meta and non meta table name and functions to fetch data for a WorPress Object.
	 *
	 * @param string $wp_object WordPress Object Type.
	 * @return array
	 */
	public function mo_sf_sync_get_wp_db_table_structure( $wp_object ) {
		switch ( $wp_object ) {
			case 'post':
				$wp_obj_db_struct = array(
					'object_name'     => 'post',
					'content_methods' => array(
						'create' => 'wp_insert_post',
						'read'   => 'get_posts',
						'update' => 'wp_update_post',
						'delete' => 'wp_delete_post',
						'match'  => 'get_posts',
					),
					'meta_methods'    => array(
						'create' => 'add_post_meta',
						'read'   => 'get_post_meta',
						'update' => 'update_post_meta',
						'delete' => 'delete_post_meta',
						'match'  => 'WP_Query',
					),
					'content_table'   => $this->wpdb->prefix . 'posts',
					'id_field'        => 'ID',
					'meta_table'      => $this->wpdb->prefix . 'postmeta',
					'meta_join_field' => 'post_id',
					'where'           => 'AND ' . $this->wpdb->prefix . 'posts.post_type = "' . $wp_object . '"',
					'ignore_keys'     => array(),
				);
				break;
			case 'user':
				$wp_obj_db_struct = array(
					'object_name'     => 'user',
					'content_methods' => array(
						'create' => 'wp_insert_user',
						'read'   => 'get_user_by',
						'update' => 'wp_update_user',
						'delete' => 'wp_delete_user',
						'match'  => 'get_user_by',
					),
					'meta_methods'    => array(
						'create' => 'update_user_meta',
						'read'   => 'get_user_meta',
						'update' => 'update_user_meta',
						'delete' => 'delete_user_meta',
					),
					'content_table'   => $this->wpdb->prefix . 'users',
					'id_field'        => 'ID',
					'meta_table'      => $this->wpdb->prefix . 'usermeta',
					'meta_join_field' => 'user_id',
					'where'           => '',
					'ignore_keys'     => array(
						'user_pass',
						'user_activation_key',
						'session_tokens',
					),
				);
				break;
		}
		return $wp_obj_db_struct;
	}

	/**
	 * Get all the fields for an object
	 *
	 * @param string $wp_object_name The name of the WordPress object.
	 * @param string $wp_id_field The database filed that contains its ID.
	 * @param string $wp_content_table The table that normally contains such objects.
	 * @param array  $wp_content_methods functions that are used to perform read/write operations on the WordPress database tables.
	 * @param string $wp_meta_table The table where meta values for a WordPress object is stored.
	 * @param array  $wp_meta_methods functions that are used to perform read/write operations on the WordPress database meta tables.
	 * @param string $where_clause where clause for the SQL query.
	 * @param array  $wp_ignore_keys Fields to ignore from the database.
	 * @return array
	 */
	private function mo_sf_sync_get_all_fields_of_selected_obj( $wp_object_name, $wp_id_field, $wp_content_table, $wp_content_methods, $wp_meta_table, $wp_meta_methods, $where_clause, $wp_ignore_keys ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- the parameter $wp_content_table used in this query is static and its value is decided from a set of pre-defined values in the above functions, therefore no need of prepare statement here.
		$wp_data_fields = $this->wpdb->get_col( "DESC {$wp_content_table}", 0 );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- the parameter $wp_content_table used in this query is static and its value is decided from a set of pre-defined values in the above functions, therefore no need of prepare statement here.  
		$wp_data_field_types = $this->wpdb->get_col( "DESC {$wp_content_table}", 1 );

		if ( is_array( $wp_meta_table ) ) {
			$wp_meta_table = $wp_meta_table[0];
		}
		$wp_select_meta = '
		SELECT DISTINCT ' . $wp_meta_table . '.meta_key
		FROM ' . $wp_content_table . '
		LEFT JOIN ' . $wp_meta_table . '
		ON ' . $wp_content_table . '.' . $wp_id_field . ' = ' . $wp_meta_table . '.' . $wp_object_name . '_id
		WHERE ' . $wp_meta_table . '.meta_key != ""
		' . $where_clause . '
		';
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- all the paramters of the $wp_select_meta query are static and its value is decided from a set of pre-defined values in the above functions, therefore no need to use prepare statement here.
		$wp_meta_fields = $this->wpdb->get_results( $wp_select_meta );
		$all_wp_fields  = array();

		foreach ( $wp_data_fields as $key => $value ) {
			if ( ! in_array( $value, $wp_ignore_keys, true ) ) {
				$editable = true;
				if ( $value === $wp_id_field ) {
					$editable = false;
				}
				$all_wp_fields[] = array(
					'key'      => $value,
					'table'    => $wp_content_table,
					'methods'  => maybe_serialize( $wp_content_methods ),
					'type'     => $wp_data_field_types[ $key ],
					'editable' => $editable,
				);
			}
		}
		foreach ( $wp_meta_fields as $key => $value ) {
			if ( ! in_array( $value->meta_key, $wp_ignore_keys, true ) ) {
				$editable = true;
				if ( $value === $wp_id_field ) {
					$editable = false;
				}
				$all_wp_fields[] = array(
					'key'      => $value->meta_key,
					'table'    => $wp_meta_table,
					'methods'  => maybe_serialize( $wp_meta_methods ),
					'editable' => $editable,
				);
			}
		}
		return $all_wp_fields;
	}

}
