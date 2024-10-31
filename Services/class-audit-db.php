<?php
/**
 * This file handles the database related calls for the audit logs.
 *
 * @package object-data-sync-for-salesforce\Services
 */

namespace MoSfSyncSalesforce\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MoSfSyncSalesforce\Helper\Instance;
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
/**
 * This class is responsible for handling the database related calls for the audit logs.
 */
class Audit_DB {

	use Instance;

	/**
	 * Name of the audit log WordPress table.
	 *
	 * @var string
	 */
	public $audit_log_table_name;

	/**
	 * Global object of `$wpdb`, the WordPress database.
	 *
	 * @var object
	 */
	public $db;

	/**
	 * Creates instance of the class.
	 *
	 * @return __CLASS__
	 */
	public static function instance() {
		global $wpdb;
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		self::$instance->db                   = $wpdb;
		self::$instance->audit_log_table_name = 'mo_sf_sync_audit_log_db';
		return self::$instance;
	}

	/**
	 * Creates the audit log table.
	 *
	 * @return bool
	 */
	public function mo_sf_sync_create_audit_log_table() {

		$current_charset_collate = $this->db->get_charset_collate();
		$create_table_query      = "CREATE TABLE $this->audit_log_table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            direction text COLLATE utf8mb4_unicode_ci NOT NULL,
            salesforce_id text COLLATE utf8mb4_unicode_ci NOT NULL,
            wordpress_object text COLLATE utf8mb4_unicode_ci NOT NULL,
            wordpress_id text COLLATE utf8mb4_unicode_ci NOT NULL,  
            user_action text COLLATE utf8mb4_unicode_ci NOT NULL,
            action_status text COLLATE utf8mb4_unicode_ci NOT NULL,
            response text COLLATE utf8mb4_unicode_ci NOT NULL,
            time_stamp timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (id)
          ) ENGINE=InnoDB $current_charset_collate";

		$created_table = dbDelta( $create_table_query, true );

		if ( empty( $created_table ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Adds an entry to the audit log table.
	 *
	 * @param string $direction direction of the sync.
	 * @param string $salesforce_id salesforce record id.
	 * @param string $username WordPress username/post_title.
	 * @param string $user_action sync action either it is create or update.
	 * @param string $action_status status of the sync, either success or fail.
	 * @param string $response response of the sync action.
	 * @param string $wp_object WordPress object name.
	 * @return void
	 */
	public function mo_sf_sync_add_log( $direction = '', $salesforce_id = '', $username = '', $user_action = '', $action_status = '', $response = '', $wp_object = 'user' ) {
		$data   = array(
			'direction'        => $direction,
			'salesforce_id'    => $salesforce_id,
			'wordpress_id'     => $username,
			'user_action'      => $user_action,
			'action_status'    => $action_status,
			'response'         => $response,
			'wordpress_object' => $wp_object,
		);
		$format = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' );
		$this->db->insert( $this->audit_log_table_name, $data, $format );
	}

	/**
	 * Fetches all audit entries from the audit log table.
	 *
	 * @return array
	 */
	public function mo_sf_sync_get_audit_logs() {
		$select_query = "SELECT * FROM $this->audit_log_table_name";
		$query_result = $this->db->get_results( $select_query );

		return $query_result;
	}

	/**
	 * Delete all the audit entries from the audit log table.
	 *
	 * @return void
	 */
	public function mo_sf_sync_clear_all_logs() {
		$this->db->query( "TRUNCATE TABLE `$this->audit_log_table_name`" );
	}

	/**
	 * Saves the search settings for the audit logs.
	 *
	 * @return void
	 */
	public function mo_sf_sync_save_advance_search_settings() {
		if ( $this->mo_sf_sync_check_option_admin_referer( 'mo_sf_sync_advanced_reports' ) ) {

			$username    = '';
			$direction   = '';
			$status      = '';
			$user_action = '';
			$from_date   = '';
			$to_date     = '';
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- no need for nonce verification here as its already done on line 131 of this file.
			if ( isset( $_POST['username'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- no need for nonce verification here as its already done on line 131 of this file.
				$username = sanitize_text_field( wp_unslash( $_POST['username'] ) );
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- no need for nonce verification here as its already done on line 131 of this file.
			if ( isset( $_POST['direction'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- no need for nonce verification here as its already done on line 131 of this file.
				$direction = sanitize_text_field( wp_unslash( $_POST['direction'] ) );
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- no need for nonce verification here as its already done on line 131 of this file.
			if ( isset( $_POST['status'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- no need for nonce verification here as its already done on line 131 of this file.
				$status = sanitize_text_field( wp_unslash( $_POST['status'] ) );
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- no need for nonce verification here as its already done on line 131 of this file.
			if ( isset( $_POST['user_action'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- no need for nonce verification here as its already done on line 131 of this file.
				$user_action = sanitize_text_field( wp_unslash( $_POST['user_action'] ) );
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- no need for nonce verification here as its already done on line 131 of this file.
			if ( isset( $_POST['from_date'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- no need for nonce verification here as its already done on line 131 of this file.
				$from_date = sanitize_text_field( wp_unslash( $_POST['from_date'] ) );
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- no need for nonce verification here as its already done on line 131 of this file.
			if ( isset( $_POST['to_date'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- no need for nonce verification here as its already done on line 131 of this file.
				$to_date = sanitize_text_field( wp_unslash( $_POST['to_date'] ) );
			}

			update_option( 'mo_sf_sync_advanced_search_username', $username );
			update_option( 'mo_sf_sync_advanced_search_direction', $direction );
			update_option( 'mo_sf_sync_advanced_search_status', $status );
			update_option( 'mo_sf_sync_advanced_search_action', $user_action );
			update_option( 'mo_sf_sync_advanced_search_from_date', $from_date );
			update_option( 'mo_sf_sync_advanced_search_to_date', $to_date );
			update_option( 'mo_sf_sync_advanced_reports', true );
		} elseif ( $this->mo_sf_sync_check_option_admin_referer( 'mo_sf_sync_clear_advance_search' ) ) {
			delete_option( 'mo_sf_sync_advanced_search_username' );
			delete_option( 'mo_sf_sync_advanced_search_direction' );
			delete_option( 'mo_sf_sync_advanced_search_status' );
			delete_option( 'mo_sf_sync_advanced_search_action' );
			delete_option( 'mo_sf_sync_advanced_search_from_date' );
			delete_option( 'mo_sf_sync_advanced_search_to_date' );
			delete_option( 'mo_sf_sync_advanced_reports' );
		}
	}
	/**
	 * Used for nonce verification.
	 *
	 * @param string $option_name name of the option for nonce verification.
	 */
	public function mo_sf_sync_check_option_admin_referer( $option_name ) {
		return ( isset( $_POST['option'] ) && $_POST['option'] === $option_name && check_admin_referer( $option_name ) );
	}

	/**
	 * Fetch audit entries from the audit table according to the saved search settings.
	 *
	 * @return array
	 */
	public function mo_sf_sync_get_audit_using_advanced_search() {
		$myrows = '';
		if ( get_option( 'mo_sf_sync_advanced_reports' ) ) {

			$username    = get_option( 'mo_sf_sync_advanced_search_username' );
			$direction   = get_option( 'mo_sf_sync_advanced_search_direction' );
			$status      = get_option( 'mo_sf_sync_advanced_search_status' );
			$user_action = get_option( 'mo_sf_sync_advanced_search_action' );
			$from_date   = get_option( 'mo_sf_sync_advanced_search_from_date' );
			$to_date     = get_option( 'mo_sf_sync_advanced_search_to_date' );

			$where_clause      = ' where ';
			$is_previous_added = false;
			if ( $username ) {
				$where_clause     .= " wordpress_id LIKE '" . $username . "%'";
				$is_previous_added = true;
			}

			if ( $direction && 'default' !== $direction ) {
				if ( $is_previous_added ) {
					if ( 'sync wp to sf' === $direction ) {
						$where_clause .= " AND direction != 'sync sf to wp'";
					} else {
						$where_clause .= " AND direction = '" . $direction . "'";
					}
				} else {
					if ( 'sync wp to sf' === $direction ) {
						$where_clause .= " direction != 'sync sf to wp'";
					} else {
						$where_clause .= " direction = '" . $direction . "'";
					}

					$is_previous_added = true;
				}
			}

			if ( $status && 'default' !== $status ) {
				if ( $is_previous_added ) {
					if ( 'failed' === $status ) {
						$where_clause .= " AND action_status != 'success'";
					} else {
						$where_clause .= " AND action_status = '" . $status . "'";
					}
				} else {
					if ( 'failed' === $status ) {
						$where_clause .= " action_status != 'success'";
					} else {
						$where_clause .= " action_status = '" . $status . "'";
					}
					$is_previous_added = true;
				}
			}

			if ( $user_action && 'default' !== $user_action ) {
				if ( $is_previous_added ) {
					if ( 'Create' === $user_action ) {
						$where_clause .= " AND user_action != 'Update'";
					} else {
						$where_clause .= " AND user_action = '" . $user_action . "'";
					}
				} else {
					if ( 'Create' === $user_action ) {
						$where_clause .= " user_action != 'Update'";
					} else {
						$where_clause .= " user_action = '" . $user_action . "'";
					}
					$is_previous_added = true;
				}
			}
			$has_date_error = false;
			if ( $from_date && $to_date && $from_date !== $to_date ) {
				$frm_date = \DateTime::createFromFormat( 'Y-m-d', $from_date );
				$t_date   = \DateTime::createFromFormat( 'Y-m-d', $to_date );
				if ( $frm_date->getTimestamp() > $t_date->getTimestamp() ) {
					update_site_option( 'mo_sf_sync_message', 'Invalid selection date interval' );
					$has_date_error = true;
				} else {
					$where_clause .= 'AND time_stamp BETWEEN ' . "'$from_date 00:00:00 '" . ' AND ' . "'$to_date 00:00:00 '";
				}
			}

			if ( $has_date_error ) {
				$myrows = $this->db->get_results( 'SELECT * FROM ' . $this->audit_log_table_name . ' order by id desc limit 5000' );
			} else {
				if ( ' where ' === $where_clause ) {
					$where_clause = '';
				}
				$myrows = $this->db->get_results( 'SELECT * FROM ' . $this->audit_log_table_name . $where_clause );
			}
		} else {
			$myrows = $this->db->get_results( 'SELECT * FROM ' . $this->audit_log_table_name . ' order by id desc limit 5000' );
		}

		return $myrows;
	}
}
