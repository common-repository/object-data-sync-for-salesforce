<?php  // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- cannot change main file's name
/**
 * Plugin Name: Object Data Sync For Salesforce
 * Plugin URI: http://miniorange.com
 * Description: Object Data Sync For Salesforce Plugin synchronizes WordPress users/posts with selected object/record in Salesforce and keeps Salesforce object/record in sync with the WordPress.
 * Version: 1.2.4
 * Author: miniOrange
 * Author URI: http://miniorange.com/
 * License: MIT/Expat
 * License URI: https://docs.miniorange.com/mit-license
 *
 * @package object-data-sync-for-salesforce
 */

namespace MoSfSyncSalesforce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MoSfSyncSalesforce\Services\Utils;
use MoSfSyncSalesforce\Helper\Instance;
use MoSfSyncSalesforce\Handler\Ajax_Handler;
use MoSfSyncSalesforce\Handler\Account_Setup_Handler;
use MoSfSyncSalesforce\Handler\Authorization_Handler;
use MoSfSyncSalesforce\Handler\Bulk_Action_Handler;
use MoSfSyncSalesforce\Handler\Data_Processing_Handler;
use MoSfSyncSalesforce\Handler\Pardot_Block_Register_Handler;
use MoSfSyncSalesforce\Helper\Plugin_Constants;
use MoSfSyncSalesforce\Handler\Demo_Setup_Handler;
use MoSfSyncSalesforce\Services\Audit_DB;
use MoSfSyncSalesforce\Handler\Workflow_Integration_Handler;
use MoSfSyncSalesforce\Handler\Test_Configuration_Handler;
use MoSfSyncSalesforce\Services\DB_Utils;

define( 'MOSF_DIRC', __DIR__ );
require_once __DIR__ . '/Helper/view/view.php';
require 'feedback-form.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/Helper/view/mo-sf-troubleshoot.php';
require_once __DIR__ . '/Helper/view/plugin-guide.php';
require_once __DIR__ . '/Helper/view/pardot-setup-guide.php';
require_once __DIR__ . '/Helper/view/templates/edit-template.php';
require_once __DIR__ . '/Helper/view/templates/table-template.php';

/**
 * The main class for Object Data Sync for Salesforce plugin.
 *
 * @package object-data-sync-for-salesforce
 */
class MoSfSync {

	use Instance;

	/**
	 * Stores instance of class.
	 *
	 * @var __CLASS__|null
	 */
	private static $instance;

	/**
	 * Instance of Data_Processing_Handler.
	 *
	 * @var Data_Processing_Handler
	 */
	private $data_processing_handler;

	/**
	 * Creates instance of the class.
	 *
	 * @return __CLASS__
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$class          = __CLASS__;
			self::$instance = new $class();
			self::$instance->mo_sf_sync_loadhooks();
			self::$instance->data_processing_handler = Data_Processing_Handler::instance();
			new Bulk_Action_Handler();
		}
		return self::$instance;
	}

	/**
	 * Initializes all hooks required for the plugin.
	 *
	 * @return void
	 */
	public function mo_sf_sync_loadhooks() {
		register_activation_hook( __FILE__, array( $this, 'mo_sf_sync_plugin_init' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'mo_sf_sync_action_links' ) );
		add_action( 'init', array( $this, 'mo_sf_sync_handle_auth_code' ) );
		add_action( 'admin_init', array( $this, 'mo_sf_sync_save' ) );
		add_action( 'admin_menu', array( $this, 'mo_sf_sync_menu' ) );
		add_action( 'admin_footer', array( $this, 'mo_sf_sync_feedback_request' ) );
		add_filter( 'bulk_actions-users', array( $this, 'mo_sf_sync_show_bulk_action' ) );
		add_filter( 'bulk_actions-edit-post', array( $this, 'mo_sf_sync_show_bulk_action_post' ) );
		add_action( 'restrict_manage_users', array( $this, 'mo_sf_sync_add_custom_search_filter' ) );
		add_action( 'restrict_manage_posts', array( $this, 'mo_sf_sync_add_custom_search_filter' ) );
		add_filter( 'pre_get_users', array( $this, 'mo_sf_sync_filter_users_by_sync_status' ) );
		add_filter( 'pre_get_posts', array( $this, 'mo_sf_sync_filter_users_by_sync_status' ) );
		add_action( 'wp_ajax_mo_sf_sync_ajax_submit', array( $this, 'mo_sf_sync_ajax_submit_handler' ) );
		add_action( 'init', array( Pardot_Block_Register_Handler::instance(), 'mo_sf_sync_register_gutenberg_blocks' ) );

		$provision_config = Utils::mo_sf_sync_get_settings( Plugin_Constants::PROVISION_OBJECT );
		if ( isset( $provision_config[ Plugin_Constants::AUTO_USER_UPDATE ] ) && 'on' === $provision_config[ Plugin_Constants::AUTO_USER_UPDATE ] ) {
			$this->mo_sf_sync_load_real_time_sync_hooks();
		}

		$this->mo_sf_sync_ad_hoc_sync_box();
	}

	/**
	 * Adds Hooks required for real time user and post sync.
	 *
	 * @return void
	 */
	public function mo_sf_sync_load_real_time_sync_hooks() {
		add_action( 'user_register', array( $this, 'mo_sf_sync_update_salesforce' ), 10, 1 );
		add_action( 'profile_update', array( $this, 'mo_sf_sync_update_salesforce' ), 10, 1 );
		add_action( 'save_post', array( $this, 'mo_sf_sync_post_updated' ), 10, 3 );
	}

	/**
	 * Adds hooks required for the ad hoc sync box on user profile.
	 *
	 * @return void
	 */
	public function mo_sf_sync_ad_hoc_sync_box() {
		add_action( 'edit_user_profile', array( $this, 'mo_sf_sync_show_salesforce_user_fields' ), 10, 1 );
		add_action( 'show_user_profile', array( $this, 'mo_sf_sync_show_salesforce_user_fields' ), 10, 1 );
		add_action( 'personal_options_update', array( $this, 'mo_sf_sync_update_show_salesforce_user_fields' ), 10, 1 );
		add_action( 'edit_user_profile_update', array( $this, 'mo_sf_sync_update_show_salesforce_user_fields' ), 10, 1 );
	}

	/**
	 * Adds the Settings action in plugin list section.
	 *
	 * @param array $links Array of existing action links.
	 * @return array
	 */
	public function mo_sf_sync_action_links( $links ) {
		$url = esc_url(
			add_query_arg(
				'page',
				'mo_sf_sync',
				get_admin_url() . 'admin.php?page=mo_sf_sync'
			)
		);

		$license_link = "<a href='$url'>" . esc_html__( 'Settings' ) . '</a>';

		array_push(
			$links,
			$license_link
		);
		return $links;
	}

	/**
	 * Adds bulk action for user.
	 *
	 * @param array $bulk_actions An array of the available bulk actions.
	 * @return array
	 */
	public function mo_sf_sync_show_bulk_action( $bulk_actions ) {
		$db           = DB_Utils::instance();
		$nomenclature = $db->mo_sf_sync_get_salesforce_record_id_for_wp_users();
		if ( isset( $nomenclature ) && ! empty( $nomenclature ) ) {
			$bulk_actions['sync_user_to_sf'] = __( 'Sync Users to Salesforce', 'sync_user_to_sf' );
		}
		return $bulk_actions;
	}

	/**
	 * Adds bulk actions for post.
	 *
	 * @param array $bulk_actions An array of the available bulk actions.
	 * @return array
	 */
	public function mo_sf_sync_show_bulk_action_post( $bulk_actions ) {
		$db           = DB_Utils::instance();
		$nomenclature = $db->mo_sf_sync_get_salesforce_record_id_for_wp_posts();
		if ( isset( $nomenclature ) && ! empty( $nomenclature ) ) {
			$bulk_actions['sync_post_to_sf'] = __( 'Sync Posts to Salesforce', 'sync_post_to_sf' );
		}
		return $bulk_actions;
	}

	/**
	 * Displays the ad-hoc sync box on user profile.
	 *
	 * @param WP_User $user WordPress user object.
	 * @return boolean
	 */
	public function mo_sf_sync_show_salesforce_user_fields( $user ) {

		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$map                  = $this->data_processing_handler->mo_sf_sync_get_mapping( $user->ID );
		$salesforce_object_id = '';
		$get_data             = filter_input_array( INPUT_GET, FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( isset( $get_data['edit_salesforce_mapping'] ) && true === filter_var( $get_data['edit_salesforce_mapping'], FILTER_VALIDATE_BOOLEAN ) ) {
			if ( isset( $map['object'] ) ) {
				$salesforce_object_id = get_user_meta( $user->ID, 'salesforce_' . $map['object'] . '_ID', true );
			}

			$salesforce_id_temp = get_user_meta( $user->ID, 'salesforce_ID', true );
			mo_sf_sync_edit_template( $salesforce_id_temp, $salesforce_object_id );
		} else {
			if ( isset( $get_data['push'] ) && true === filter_var( $get_data['push'], FILTER_VALIDATE_BOOLEAN ) ) {
				if ( isset( $map['object'] ) ) {
					$salesforce_object_id = get_user_meta( $user->ID, 'salesforce_' . $map['object'] . '_ID', true );
				}

				$salesforce_id_temp = get_user_meta( $user->ID, 'salesforce_ID', true );
				if ( ! empty( $salesforce_id_temp ) && isset( $map['object'] ) ) {
						update_user_meta( $user->ID, 'salesforce_' . $map['object'] . '_ID', $salesforce_id_temp );
				}

				$response = $this->data_processing_handler->mo_sf_sync_push_to_salesforce( $user->ID );
				$response = json_decode( wp_json_encode( $response ), true );

				if ( isset( $response['message'] ) && ! empty( $response['message'] ) ) {
					echo "<p style='color:darkred;font-size:15px;'><strong>Push Unsuccessful: " . esc_html( $response['message'] ) . '!</strong><br><p>';
				} elseif ( isset( $response[0]['errorCode'] ) ) {
					echo "<p style='color:darkred;font-size:15px;'><strong>Push Unsuccessful Salesforce returned error : " . esc_html( $response[0]['message'] ) . '</strong><br><p>';
					if ( array_key_exists( 'object', $map ) ) {
						update_user_meta( $user->ID, 'salesforce_' . $map['object'] . '_ID', $salesforce_object_id );
					}
				} else {
					echo "<p style='color:darkgreen;font-size:15px;'><strong>Push Successful</strong><br></p>";
				}
			}

			if ( isset( $map['object'] ) ) {
				$salesforce_object_id = get_user_meta( $user->ID, 'salesforce_' . $map['object'] . '_ID', true );
			}
			$salesforce_id_temp = get_user_meta( $user->ID, 'salesforce_ID', true );

			mo_sf_sync_table_template( $salesforce_id_temp, $salesforce_object_id, $user );
		}
	}

	/**
	 * Saves Salesforce ID in user meta when profile is updated.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return int|bool
	 */
	public function mo_sf_sync_update_show_salesforce_user_fields( $user_id ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( check_admin_referer( 'update-user_' . $user_id ) && array_key_exists( 'salesforce_ID', $_POST ) ) {
			update_user_meta( $user_id, 'salesforce_ID', sanitize_text_field( wp_unslash( $_POST['salesforce_ID'] ) ) );
		}
	}

	/**
	 * Performs operations when plugin is installed.
	 *
	 * @return void
	 */
	public function mo_sf_sync_plugin_init() {
		$db = DB_Utils::instance();
		$db->mo_sf_sync_create_mapping_tables();
		$mapping = Utils::mo_sf_sync_get_settings( Plugin_Constants::MAPPING_OBJECT );
		set_transient( 'mo_sf_sync_plugin_activated', true, 86400 );
		if ( isset( $mapping[ Plugin_Constants::SELECTED_OBJECT ] ) && isset( $mapping['user_login'] ) ) {
			Utils::mo_sf_sync_manage_backward_compatibility( $mapping );
		}
		$app_config = maybe_unserialize( Utils::mo_sf_sync_get_settings( Plugin_Constants::CONFIG_OBJECT ) );
		$app_config = isset( $app_config ) && false !== $app_config ? $app_config : array();
		if ( ! isset( $app_config[ Plugin_Constants::ENVIRONMENT ] ) ) {
			Utils::mo_sf_sync_update_app_config( $app_config );
		}

		$this->mo_sf_sync_audit_log();
	}

	/**
	 * Creates audit log table.
	 *
	 * @return void
	 */
	public function mo_sf_sync_audit_log() {
		$audit = Audit_DB::instance();
		$audit->mo_sf_sync_create_audit_log_table();
	}

	/**
	 * Adds custom filter drop down.
	 *
	 * @param string $which Location of the extra table nav markup.
	 * @return void
	 */
	public function mo_sf_sync_add_custom_search_filter( $which ) {
		$db = DB_Utils::instance();
		global $pagenow;
		if ( 'users.php' === $pagenow ) {
			$nomenclature = $db->mo_sf_sync_get_salesforce_record_id_for_wp_users();
		} else {
			$nomenclature = $db->mo_sf_sync_get_salesforce_record_id_for_wp_posts();
		}
		if ( ! isset( $nomenclature ) || empty( $nomenclature ) ) {
			return $which;
		}
		$st = '<select name="select_sync_status%s" style="float:none;margin-left:10px;">
			<option value="">%s</option>%s</select>';

		$options = '
			<option value="get_recs">Get Records</option>
			<option value="synced">Synced</option>
			<option value="not-synced">Not-Synced</option>
			';

		$select = sprintf( $st, $which, __( 'Sync Status ...' ), $options );

		$allowed_html = array(
			'select' => array(
				'name'  => array(),
				'style' => array(),
			),
			'option' => array(
				'value' => array(),
			),
		);

		echo wp_kses( $select, $allowed_html );
		if ( 'users.php' === $pagenow ) {
			submit_button( __( 'Filter' ), 'secondary', $which, false );
		}
	}

	/**
	 * Operates on selection of the customer user list filter dropdown.
	 *
	 * @param WP_User_Query $query WP_User_Query.
	 * @return WP_User_Query
	 */
	public function mo_sf_sync_filter_users_by_sync_status( $query ) {
		$db = DB_Utils::instance();
		global $pagenow;

		if ( 'users.php' === $pagenow && is_admin() ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This GET request is received when an option is selected on WordPress user page.
				$top          = isset( $_GET['select_sync_statustop'] ) ? sanitize_text_field( wp_unslash( $_GET['select_sync_statustop'] ) ) : null;
				$nomenclature = $db->mo_sf_sync_get_salesforce_record_id_for_wp_users();
			if ( isset( $top ) && 'not-synced' === $top ) {
				$meta_query = array(
					array(
						'key'     => $nomenclature,
						'compare' => 'NOT EXISTS',
					),
				);
			} elseif ( isset( $top ) && 'synced' === $top ) {
				$meta_query = array(
					array(
						'key'     => $nomenclature,
						'compare' => 'EXISTS',
					),
				);
			}
			if ( isset( $meta_query ) ) {
				$query->set( 'meta_query', $meta_query );
			}
		} else {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This GET request is received when an option is selected on WordPress user page.
				$top = isset( $_GET['select_sync_statuspost'] ) ? sanitize_text_field( wp_unslash( $_GET['select_sync_statuspost'] ) ) : null;

				$nomenclature = $db->mo_sf_sync_get_salesforce_record_id_for_wp_posts();

			if ( isset( $top ) && 'not-synced' === $top ) {
				$meta_query = array(
					array(
						'key'     => $nomenclature,
						'compare' => 'NOT EXISTS',
					),
				);

			} elseif ( isset( $top ) && 'synced' === $top ) {

				$meta_query = array(
					array(
						'key'     => $nomenclature,
						'compare' => 'EXISTS',
					),
				);

			}
			if ( isset( $meta_query ) ) {
				$query->set( 'meta_query', $meta_query );
			}
		}
		return $query;
	}

	/**
	 * Runs on admin_init, takes care of redirecting to correct tabs.
	 *
	 * @return void
	 */
	public function mo_sf_sync_save() {

		if ( isset( $_POST['tab'] ) ) {
			$tab = sanitize_text_field( wp_unslash( $_POST['tab'] ) );
			switch ( $tab ) {
				case 'account_setup':
					$handler = Account_Setup_Handler::instance();
					$handler->mo_sf_sync_account_setup_controller();
					break;

				case 'demo_setup':
					$handler = new Demo_Setup_Handler();
					$handler->mo_sf_sync_request_demo();
					break;
			}
		}

		if ( isset( $_POST['option'] ) ) {
			$option = sanitize_text_field( wp_unslash( $_POST['option'] ) );
			switch ( $option ) {
				case 'mo_sf_sync_delete':
					if ( check_admin_referer( 'mo_sf_sync_delete' ) && isset( $_POST['mapping_label'] ) ) {
						$db = DB_Utils::instance();
						$db->mo_sf_sync_delete_mapping_from_db( sanitize_text_field( wp_unslash( $_POST['mapping_label'] ) ) );
					}
					break;
				case 'mo_sf_sync_reset':
					if ( check_admin_referer( 'mo_sf_sync_reset' ) ) {
						Utils::mo_sf_sync_reset_plugin();
					}
					break;
				case 'mo_sf_sync_feedback':
					if ( ! check_admin_referer( 'mo_sf_sync_feedback' ) ) {
						break;
					}
					$miniorange_feedback_submit = ( isset( $_POST['miniorange_feedback_submit'] ) ) ? sanitize_text_field( wp_unslash( $_POST['miniorange_feedback_submit'] ) ) : '';
					$email                      = ( isset( $_POST['query_mail'] ) ) ? sanitize_text_field( wp_unslash( $_POST['query_mail'] ) ) : '';
					$message                    = 'Plugin Deactivated';
					if ( 'Send' === $miniorange_feedback_submit ) {
						$rate      = ( isset( $_POST['rate'] ) ) ? sanitize_text_field( wp_unslash( $_POST['rate'] ) ) : '';
						$query     = ( isset( $_POST['query_feedback'] ) ) ? sanitize_text_field( wp_unslash( $_POST['query_feedback'] ) ) : '';
						$get_reply = isset( $_POST['get_reply'] ) ? 'yes' : 'no';
						$message  .= ', [Reply:' . $get_reply . '], Feedback: ' . $query . ', [Rating: ' . $rate . ']';
					} else {
						$message .= ', Feedback: Skipped';
					}
					$support  = new Customer();
					$response = $support->mo_sf_sync_send_email_alert( $email, '', $message );
					deactivate_plugins( __FILE__ );
					wp_safe_redirect( 'plugins.php' );
					break;
				case 'mo_sf_sync_client_object':
					if ( ! check_admin_referer( 'mo_sf_sync_client_object', 'nonce_' ) ) {
						break;
					}
					$handler        = Data_Processing_Handler::instance();
					$processed_data = $handler->mo_sf_sync_process_raw_mapping_data( $_POST );
					break;
				case 'mo_sf_sync_client_config_option':
					if ( ! check_admin_referer( 'mo_sf_sync_client_config_option', 'nonce_' ) ) {
						break;
					}
					$handler = Ajax_Handler::instance();
					$handler->mo_sf_sync_handle_config_object_save( $_POST );
					break;
				case 'mo_sf_sync_app_provisioning_config_option':
					if ( ! check_admin_referer( 'mo_sf_sync_app_provisioning_config_option', 'nonce_' ) ) {
						break;
					}
					$handler        = Data_Processing_Handler::instance();
					$processed_data = $handler->mo_sf_sync_save_realtime_sync( $_POST );
					break;
				case 'mo_sf_sync_advanced_reports':
					if ( ! check_admin_referer( 'mo_sf_sync_advanced_reports' ) ) {
						break;
					}
					$audit = Audit_DB::instance();
					$audit->mo_sf_sync_save_advance_search_settings();
					break;
				case 'mo_sf_sync_clear_advance_search':
					if ( ! check_admin_referer( 'mo_sf_sync_clear_advance_search' ) ) {
						break;
					}
					$audit = Audit_DB::instance();
					$audit->mo_sf_sync_save_advance_search_settings();
					break;
				case 'mo_sf_sync_trial_request_for_integrations':
					if ( ! check_admin_referer( 'mo_sf_sync_trial_request_for_integrations' ) ) {
						break;
					}

					$check_already_made_trial_request = get_transient( 'mo_sf_sync_made_integration_trial_request' );
					if ( ! empty( $check_already_made_trial_request ) && true === $check_already_made_trial_request ) {
						Utils::mo_sf_sync_show_error_message( 'You have already made the integration trial request' );
					} else {
						$customer               = new Customer();
						$requested_integrations = isset( $_POST['requested_integrations'] ) ? sanitize_text_field( wp_unslash( $_POST['requested_integrations'] ) ) : '';
						$response               = $customer->mo_sf_sync_submit_contact_us( wp_get_current_user()->user_email, '', Plugin_Constants::DEFAULT_DEMO_QUERY, false, true, $requested_integrations, 'integration_trial_request' );
						if ( 'Query submitted.' === $response ) {
							$demo_request_content = array(
								'user_email' => wp_get_current_user()->user_email,
								'user_query' => Plugin_Constants::DEFAULT_DEMO_QUERY,
							);
							Utils::mo_sf_sync_save_settings( Plugin_Constants::DEMO_REQUEST_CONTENT, $demo_request_content );
							wp_safe_redirect( '?page=mo_sf_sync&tab=demo_setup' );
							set_transient( 'mo_sf_sync_made_integration_trial_request', true, 0 );
							Utils::mo_sf_sync_show_success_message( Plugin_Constants::DEMO_REQUEST_SUCCESS );
						} else {
							Utils::mo_sf_sync_show_error_message( $response );
						}
					}
					break;
				case 'mo_sf_sync_truncate_audit_logs':
					if ( ! check_admin_referer( 'mo_sf_sync_truncate_audit_logs' ) ) {
						break;
					}
					$audit = Audit_DB::instance();
					$audit->mo_sf_sync_clear_all_logs();
					break;
				case 'mo_sf_sync_demo_info':
					if ( ! check_admin_referer( 'mo_sf_sync_demo_info' ) ) {
						break;
					}
					$followup_email = isset( $_POST['mo_sf_sync_demo_email'] ) ? sanitize_text_field( wp_unslash( $_POST['mo_sf_sync_demo_email'] ) ) : wp_get_current_user()->user_email;
					$followup_query = isset( $_POST['query_description'] ) ? sanitize_text_field( wp_unslash( $_POST['query_description'] ) ) : 'Followup on the previous demo request';
					$customer       = new Customer();
					$response       = $customer->mo_sf_sync_submit_contact_us( $followup_email, '', $followup_query, false, true, 'None' );
					if ( 'Query submitted.' === $response ) {
						Utils::mo_sf_sync_show_success_message( Plugin_Constants::DEMO_REQUEST_SUCCESS );
						$demo_request_content = array(
							'user_email' => $followup_email,
							'user_query' => $followup_query,
						);
						Utils::mo_sf_sync_save_settings( Plugin_Constants::DEMO_REQUEST_CONTENT, $demo_request_content );
					} else {
						Utils::mo_sf_sync_show_error_message( $response );
					}
					break;
				case 'mo_sf_sync_setup_trial':
					if ( ! check_admin_referer( 'mo_sf_sync_setup_trial' ) ) {
						break;
					}
					$check_already_made_trial_request = get_transient( 'mo_sf_sync_made_trial_request' );
					if ( ! empty( $check_already_made_trial_request ) && true === $check_already_made_trial_request ) {
						Utils::mo_sf_sync_show_error_message( 'You have already made the trial request' );
					} else {
						$customer = new Customer();
						$response = $customer->mo_sf_sync_submit_contact_us( wp_get_current_user()->user_email, '', Plugin_Constants::DEFAULT_DEMO_QUERY, true, true, 'None', 'normal_trial_request' );
						if ( 'Query submitted.' === $response ) {
							$demo_request_content = array(
								'user_email' => wp_get_current_user()->user_email,
								'user_query' => Plugin_Constants::DEFAULT_DEMO_QUERY,
							);
							Utils::mo_sf_sync_save_settings( Plugin_Constants::DEMO_REQUEST_CONTENT, $demo_request_content );
							wp_safe_redirect( '?page=mo_sf_sync&tab=demo_setup' );
							set_transient( 'mo_sf_sync_made_trial_request', true, 0 );
							Utils::mo_sf_sync_show_success_message( Plugin_Constants::DEMO_REQUEST_SUCCESS );
						} else {
							Utils::mo_sf_sync_show_error_message( $response );
						}
					}
					break;
			}
		}
	}

	/**
	 * Loads the feedback form on admin footer.
	 *
	 * @return void
	 */
	public function mo_sf_sync_feedback_request() {
		mo_sf_sync_display_feedback_form();
	}

	/**
	 * Delegates call for user update/create to appropriate handler.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function mo_sf_sync_update_salesforce( $user_id ) {
		$response = $this->data_processing_handler->mo_sf_sync_push_to_salesforce( $user_id );
	}

	/**
	 * Delegates call for post create/update to appropriate handler.
	 *
	 * @param int     $id Post id.
	 * @param Post    $post Post Object.
	 * @param boolean $update Whether this is an existing post being updated.
	 * @return void
	 */
	public function mo_sf_sync_post_updated( $id, $post, $update ) {
		$post_status = get_post_status( $id );
		if ( 'publish' === $post_status ) {
			$response = $this->data_processing_handler->mo_sf_sync_push_to_salesforce( $id, 'post' );
		}
	}

	/**
	 * Displays menu for the plugin.
	 *
	 * @return void
	 */
	public function mo_sf_sync_menu() {
		add_menu_page(
			'MiniOrange Salesforce sync' . __( 'Configure WP Salesforce Object Sync', 'mo_sf_sync' ),
			'Object Data Sync For Salesforce',
			'administrator',
			'mo_sf_sync',
			array( $this, 'mo_sf_sync_display' ),
			plugin_dir_url( __FILE__ ) . 'images/miniorange.png'
		);
		add_action( 'admin_enqueue_scripts', array( $this, 'mo_sf_sync_settings_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'mo_sf_sync_settings_style' ) );
		add_submenu_page( 'mo_sf_sync', 'Troubleshoot', 'Troubleshoot', 'administrator', 'troubleshoot', array( $this, 'mo_sf_sync_troubleshoot' ) );
		add_submenu_page( 'mo_sf_sync', 'Configuration Guide', 'Configuration Guide', 'administrator', 'plugin_guide', array( $this, 'mo_sf_sync_plugin_guide' ) );
		add_submenu_page( 'mo_sf_sync', 'Pardot Setup Guide', 'Pardot Setup Guide', 'administrator', 'pardot_guide', 'mo_sf_sync_display_pardot_setup_guide' );
	}

	/**
	 * Shows the troubleshoot page.
	 *
	 * @return void
	 */
	public static function mo_sf_sync_troubleshoot() {
		mo_sf_sync_display_troubleshoot();
	}

	/**
	 * Shows the plugin guide page.
	 *
	 * @return void
	 */
	public static function mo_sf_sync_plugin_guide() {
		mo_sf_sync_display_plugin_guide();
	}

	/**
	 * Delegates call to the views page which displays appropriate plugin tabs.
	 *
	 * @return void
	 */
	public static function mo_sf_sync_display() {
		mo_sf_sync_display_view();
	}

	/**
	 * Enqueues all required js scripts for the plugin.
	 *
	 * @param string $page Contains the value of the page parameter from the URL along with its level and is used to make sure the js is loaded only where it is required.
	 * @return void
	 */
	public function mo_sf_sync_settings_scripts( $page ) {
		if ( ! in_array( $page, Plugin_Constants::TOPLEVEL_JS_SCRIPT, true ) ) {
			return;
		}
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'mo_sf_sync_LicensePlan', plugins_url( '/Helper/view/includes/js/mo_sf_sync_license_plan.min.js', __FILE__ ), array( 'jquery' ), Plugin_Constants::VERSION, false );
		wp_enqueue_script( 'selectwoo.js', plugins_url( '/Helper/view/includes/js/vendor/selectwoo.min.js', __FILE__ ), array( 'jquery' ), Plugin_Constants::VERSION, false );
		wp_enqueue_script( 'mo_sf_sync_save_settings_ajax', plugins_url( '/Helper/view/includes/js/mo_sf_sync_settings.min.js', __FILE__ ), array( 'jquery', 'selectwoo.js' ), Plugin_Constants::VERSION, false );
		wp_localize_script( 'mo_sf_sync_save_settings_ajax', 'ajax_object_sf', array( 'ajax_url_sf' => admin_url( '/admin-ajax.php' ) ) );
		wp_enqueue_script( 'mo_sf_sync_datatable_script', plugins_url( 'Helper/view/includes/js/jquery.dataTables.min.js', __FILE__ ), array( 'jquery' ), Plugin_Constants::VERSION, false );
	}

	/**
	 * Enqueues all the css files required by the plugin
	 *
	 * @param string $page Contains the value of the page parameter from the URL along with its level and is used to make sure the css is loaded only where it is required.
	 * @return void
	 */
	public function mo_sf_sync_settings_style( $page ) {
		if ( ! in_array( $page, Plugin_Constants::TOPLEVEL_CSS_STYLES, true ) ) {
			return;
		}
		$css_url = plugins_url( '/Helper/view/includes/css/mo_sf_sync_settings.min.css', __FILE__ );
		wp_enqueue_style( 'selectwoo.css', plugins_url( '/Helper/view/includes/css/vendor/selectwoo.min.css', __FILE__ ), array(), Plugin_Constants::VERSION );
		wp_enqueue_style( 'mo_sf_sync_css', $css_url, array( 'selectwoo.css' ), Plugin_Constants::VERSION );
		wp_enqueue_style( 'license_style_css', plugins_url( '/Helper/view/includes/css/license_style.min.css', __FILE__ ), array(), Plugin_Constants::VERSION );
		wp_enqueue_style( 'mo_sf_sync_datatable_style', plugins_url( 'Helper/view/includes/css/jquery.dataTables.min.css', __FILE__ ), array(), Plugin_Constants::VERSION );
	}

	/**
	 * Redirects all ajax calls to the plugin's ajax handler.
	 *
	 * @return void
	 */
	public function mo_sf_sync_ajax_submit_handler() {
		$handler = Ajax_Handler::instance();
		$handler->mo_sf_sync_set_settings();
	}

	/**
	 * Runs on init to handle the salesforce authorization, test connection and soap handler flows.
	 *
	 * @return void
	 */
	public function mo_sf_sync_handle_auth_code() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This Authorization flow is triggered by a js redirect to salesforce, we cant add nonce verification here.
		if ( array_key_exists( 'option', $_GET ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This Authorization flow is triggered by a js redirect to salesforce, we cant add nonce verification here.
			$option = sanitize_text_field( wp_unslash( $_GET['option'] ) );
			switch ( $option ) {
				case 'authorization_flow':
					$auth_handler = Authorization_Handler::instance();
					$auth_handler->mo_sf_sync_connect_to_salesforce();
					break;
				case 'mo_sf_sync_test_connection':
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The Test Connection flow is triggered by a js based redirection.
					if ( isset( $_GET['mo_sf_sync_wpid'] ) ) {
						// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The Test Connection flow is triggered by a js based redirection.
						$wp_id   = sanitize_text_field( wp_unslash( $_GET['mo_sf_sync_wpid'] ) );
						$handler = Test_Configuration_Handler::instance();
						$handler->mo_sf_sync_show_test_connection_window( $wp_id );
					}
					break;
			}
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This request is received from salesforce and hence we cannot add nonce here.
		if ( isset( $_REQUEST['method'] ) && 'soap' === $_REQUEST['method'] ) {
			$handler = new Workflow_Integration_Handler();
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This request is received from salesforce and hence we cannot add nonce here.
			$handler->mo_sf_sync_xml_parser( $_REQUEST );
		}
		$handler = Authorization_Handler::instance();
		$handler->mo_sf_sync_handle_code();
	}
}

MoSfSync::instance();
