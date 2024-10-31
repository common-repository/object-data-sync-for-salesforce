<?php
/**
 * This file handles what to be displayed on the Object Mapping tab.
 *
 * @package object-data-sync-for-salesforce\Helper\view
 */

namespace MoSfSyncSalesforce\Helper\view;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MoSfSyncSalesforce\Helper\Instance;
use MoSfSyncSalesforce\API\Salesforce;
use MoSfSyncSalesforce\Services\Utils;
use MoSfSyncSalesforce\Handler\Object_Sync_Sf_To_Wp;
use MoSfSyncSalesforce\Services\DB_Utils;
use MoSfSyncSalesforce\Handler\Ajax_Handler;

/**
 * This class handles the content to be displayed on Object Mapping tab.
 */
class Field_Mapping {


	use instance;

	/**
	 * Stores all the data related to a mapping.
	 *
	 * @var array
	 */
	private $mapping_data;

	/**
	 * Stores the Salesforce object in a mapping.
	 *
	 * @var string
	 */
	private $object;

	/**
	 * Stores the configured field mapping.
	 *
	 * @var array
	 */
	private $field_mapping;

	/**
	 * Stores the custom field mapping.
	 *
	 * @var array
	 */
	private $custom_mapping;

	/**
	 * Stores all the available Salesforce objects.
	 *
	 * @var array
	 */
	private $sf_objects;

	/**
	 * Stores the configured fields of Salesforce Object.
	 *
	 * @var array
	 */
	private $sf_obj_fields;

	/**
	 * Stores the information whether authorized or not with Salesforce.
	 *
	 * @var bool
	 */
	public $is_auth_configured;

	/**
	 * Stores the object of Salesforce class.
	 *
	 * @var Salesforce
	 */
	public $salesforce;

	/**
	 * Stores the WordPress object.
	 *
	 * @var string
	 */
	private $wordpress_object;

	/**
	 * Instance of DB_Utils class.
	 *
	 * @var DB_Utils
	 */
	private $db;

	/**
	 * Object of Ajax_Handler class.
	 *
	 * @var Ajax_Handler
	 */
	private $ajaxhandler;

	/**
	 * Object of Object_Sync_Sf_To_Wp class.
	 *
	 * @var Object_Sync_Sf_To_Wp
	 */
	private $object_sync_sf_to_wp;

	/**
	 * Stores whether the sync direction is from WordPress to Salesforce or not.
	 *
	 * @var bool
	 */
	private $sync_wp_to_sf;

	/**
	 * Stores whether the sync direction is from Salesforce to WordPress or not.
	 *
	 * @var bool
	 */
	private $sync_sf_to_wp;

	/**
	 * Instance definition of class Field_Mapping.
	 *
	 * @return self
	 */
	public static function instance() {
		self::$instance                       = new self();
		self::$instance->db                   = DB_Utils::instance();
		self::$instance->ajaxhandler          = new Ajax_Handler();
		self::$instance->mapping_data         = self::$instance->db->mo_sf_sync_get_all_mapping_data();
		self::$instance->is_auth_configured   = Utils::mo_sf_sync_is_authorization_configured();
		self::$instance->object_sync_sf_to_wp = new Object_Sync_Sf_To_Wp();
		return self::$instance;
	}

	/**
	 * Gathers all necessary information related to mapping.
	 *
	 * @return void
	 */
	private function mo_sf_sync_load_mapping_data() {
		$this->salesforce = new Salesforce();
		if ( isset( $this->mapping_data['salesforce_object'] ) ) {
			$this->object = $this->mapping_data['salesforce_object'];

			if ( ! array_key_exists( 'wp_object_select', $this->mapping_data ) && ! array_key_exists( 'sync_sf_to_wp', $this->mapping_data ) && ! array_key_exists( 'sync_wp_to_sf', $this->mapping_data ) ) {
				$this->field_mapping    = isset( $this->mapping_data['field_mapping'] ) ? $this->mapping_data['field_mapping'] : array();
				$this->custom_mapping   = isset( $this->mapping_data['custom_field_mapping'] ) ? $this->mapping_data['custom_field_mapping'] : array();
				$this->wordpress_object = 'user';
				$this->sync_wp_to_sf    = true;
				$this->sync_sf_to_wp    = false;
			} else {
				$this->field_mapping    = isset( $this->mapping_data['field_mapping'] ) ? maybe_unserialize( $this->mapping_data['field_mapping'] )['field_map'] : array();
				$this->wordpress_object = isset( $this->mapping_data['wordpress_object'] ) ? $this->mapping_data['wordpress_object'] : array();
				$this->sync_wp_to_sf    = ! empty( $this->mapping_data['sync_wp_to_sf'] ) ? true : false;
				$this->sync_sf_to_wp    = ! empty( $this->mapping_data['sync_sf_to_wp'] ) ? true : false;
			}
			if ( $this->is_auth_configured ) {
				$get_object_fields = $this->salesforce->mo_sf_sync_get_fields( $this->mapping_data['salesforce_object'] );
			}
		} else {
			$this->field_mapping    = array();
			$this->custom_mapping   = array();
			$this->object           = 'Lead';
			$this->wordpress_object = 'user';
			$this->sync_wp_to_sf    = true;
			$this->sync_sf_to_wp    = false;
			if ( $this->is_auth_configured ) {
				$get_object_fields = Utils::mo_sf_sync_get_transient( 'transient_lead_object' );
			}
		}

		if ( $this->is_auth_configured ) {
			$res = get_transient( 'transient_get_object' );
			if ( empty( $res ) ) {
				$res = $this->salesforce->mo_sf_sync_get_objects();
				if ( ! is_array( $res ) && 'Please install miniOrange on your connected salesforce environment' === $res ) {
					Utils::mo_sf_sync_information_message( 'Please install miniOrange on your connected salesforce environment', 'alert' );
				} elseif ( is_array( $res ) && is_string( $res[0] ) && stripos( $res[0], 'cURL error' ) === 0 ) {
					Utils::mo_sf_sync_information_message( 'Unable to connect to Salesforce please refresh and try again', 'alert' );
				}
			}

			$this->sf_objects    = $res;
			$this->sf_obj_fields = $get_object_fields['fields'] ?? array();
		} else {
			$this->sf_objects    = array();
			$this->sf_obj_fields = array();
		}
	}

	/**
	 * Displays object mapping list and test configuration if mapping label is not set otherwise displays the object mapping itself.
	 *
	 * @return void
	 */
	public function mo_sf_sync_multiple_object_menu() {
		//phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verification already done in the 
		if ( isset( $_GET['mapping_label'] ) && ! isset( $_GET['action'] ) ) {
			$this->mo_sf_sync_object_display();
		} else {
			$this->mo_sf_sync_show_object_list( $this->mapping_data );
			$this->mo_sf_sync_display_test_config();
		}
	}

	/**
	 * Displays the table of object mapping details.
	 *
	 * @param array $mapping_list The information of a mapping.
	 * @return void
	 */
	private function mo_sf_sync_show_object_list( $mapping_list ) {
		$is_mapping_configured = ( isset( $mapping_list ) && ! empty( $mapping_list ) );
		$mapping_direction     = ( $is_mapping_configured && $mapping_list['sync_wp_to_sf'] ) ? 'WordPress to Salesforce' : 'Salesforce to WordPress';
		?>
		<input type="hidden" id="mo_sf_sync_home_url" value="<?php echo esc_url( home_url() ); ?>">
		<div class="mo-sf-sync-tab-content-tile mo-sf-mt-4">
			<h1 class="mo-sf-form-head">Configure Attribute Mapping</h1>

			<div class="mo-sf-sync-tab-content-tile-content">

				<table class="mo-sf-sync-object-list">
					<h3 class="mo-sf-form-head mo-sf-form-head-bar mo-sf-mt-5">Object Mapping List:</h3>
					<thead>
						<tr>
							<td width="20%">Mapping Label</td>
							<td width="45%">Sync Direction</td>
							<td width="18%" style="text-align:center">Edit Mapping</td>
							<td style="text-align:center">Delete Mapping</td>
						</tr>
					</thead>
					<?php
					if ( $is_mapping_configured ) {
						?>
						<tr>
							<td><?php echo esc_attr( $mapping_list['label'] ); ?></td>
							<td><?php echo esc_attr( $mapping_direction ); ?></td>
							<td class="mo-sf-sync-object-list-edit-button">
								<a 
								<?php
								if ( $this->is_auth_configured ) {
									echo 'href="?page=mo_sf_sync&tab=manage_users&mapping_label=' . esc_attr( $mapping_list['label'] ) . '"';}
								?>
								>Edit</a>
							</td>
							<td>
								<form method="post" id="delete_config" name="delete_config" action="" enctype="multipart/form-data">
									<?php wp_nonce_field( 'mo_sf_sync_delete' ); ?>
									<center>
										<label>
											<input type="hidden" name="option" value="mo_sf_sync_delete" />
											<input type="hidden" name="mapping_label" value="<?php echo esc_attr( $mapping_list['label'] ); ?>">
											<p></p>
											<input type="submit"  class="mo-sf-sync-object-list-delete-button" name="button" value="Delete" onclick="return confirm('Do you really want to delete the mapping ?')" />
										</label>
									</center>
								</form>
							</td>
						</tr>
						<?php
					}
					?>
					</tbody>
				</table>
				<div>
					<?php
					$message = ( ! $this->is_auth_configured ) ? 'Authorization not configured' : 'Available with premium version of the plugin';
					if ( ! $is_mapping_configured && $this->is_auth_configured ) {
						?>
						<div style="font-size:medium;margin:10px 0px 10px 0px"><b>No object mapping configured!</b><br></div>
						<div class="mo-sf-text-center">
							<a href="?page=mo_sf_sync&tab=manage_users&mapping_label=">
								<button class="mo-sf-btn-cstm">Add Object Mapping</button>
							</a>
						</div>
					<?php } else { ?>
						<p></p>
						<div class="mo-sf-sync-tab-content-tile-content mo-sf-prem-info">
							<div style="text-align:center;">
								<button style="height:40px;" style="position: relative;" disabled class="mo-sf-btn-cstm">Add Object mapping</button>
							</div>

							<div class="mo-sf-prem-lock" style="margin-top:-17px">
								<img style="height: 40px;margin-top:-50px" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../../images/crown.png' ); ?>" alt="">
								<?php

								if ( ! $this->is_auth_configured ) {
									echo '<p class="mo-sf-prem-text">Please Configure & Authorize the Salesforce Connected Application in the <a href="?page=mo_sf_sync&tab=app_config" class="mo-sf-text-warning">Manage Applications</a> tab</p>';
								} else {
									echo '<p class="mo-sf-prem-text">Available in premium plugin. <a href="?page=mo_sf_sync&tab=licenseplan" class="mo-sf-text-warning">Click here to upgrade</a></p>';
								}
								?>
							</div>							
						</div>

					<?php } ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Displays the content while adding or editing an object mapping.
	 *
	 * @return void
	 */
	public function mo_sf_sync_object_display() {
		$this->mo_sf_sync_load_mapping_data();
		$this->mo_sf_sync_mapping_config();
	}

	/**
	 * Displays the object mapping configuration.
	 *
	 * @return void
	 */
	private function mo_sf_sync_mapping_config() {

		$wordpress_types_not_posts_include = array( 'user', 'post', 'comment', 'category', 'tag' );
		$post_types                        = get_post_types();
		unset( $post_types['post'] );
		$wp_object_types          = array_merge( $wordpress_types_not_posts_include, $post_types );
		$wp_object_types_allowed  = array( 'user', 'post' );
		$data_fields              = $this->object_sync_sf_to_wp->mo_sf_sync_get_wp_obj_fields( $this->wordpress_object );
		$is_mapping_configured    = ( isset( $this->mapping_data ) && ! empty( $this->mapping_data ) );
		$wp_object_change_message = ( $is_mapping_configured ) ? '' : 'hidden';
		?>
		<form method="post" action="<?php echo esc_url( admin_url() ) . 'admin.php?page=mo_sf_sync&tab=manage_users'; ?>">
			<input type="hidden" name="option" value="mo_sf_sync_client_object">
			<input type="hidden" name="nonce_" value="<?php echo esc_attr( wp_create_nonce( 'mo_sf_sync_client_object' ) ); ?>">
			<input type="hidden" id="saved_mapping" value='<?php echo ( isset( $this->field_mapping['name_constraint'] ) ) ? wp_json_encode( $this->field_mapping['name_constraint'] ) : ''; ?>'> 

			<section class="accordion mo-sf-mt-4" >
				<input type="checkbox" name="collapse" checked="checked" >
				<h2 class="handle">
					<label class = "mo-sf-sync-coll-div-head" ><b>1. Select WordPress Object</b></label>
				</h2>
				<div class="mo-sf-dflex mo-sf-sync-tab-content-tile content" style="border: none;box-shadow:none">
					<div class=" mo-sf-col-md-3">
						<b><span>WordPress object <span class="mo-sf-text-danger">*</span></b></span>
					</div>
					<div class="mo-sf-col-md-8-field-mapping mo-sf-dflex">
						<div>
							<?php
							if ( $is_mapping_configured ) {
								?>
										<input type="text" class="wp_object_select" id="wp_object_select" style="width: 300px;" name="wp_object_select" readonly value="<?php echo esc_attr( $this->wordpress_object ); ?>" >
									<?php
							} else {
								?>
							<select class="form-control wp_object_select" id="wp_object_select" style="width: 300px;" name="wp_object_select" data-wp-object="<?php echo esc_attr( $this->wordpress_object ); ?>" >
								<?php

								foreach ( $wp_object_types as $value ) {
									$select_wp_obj   = $value === $this->wordpress_object ? 'selected' : '';
									$disabled_wp_obj = in_array( $value, $wp_object_types_allowed, true ) ? '' : 'disabled';
									$premium_ad      = '';
									if ( ! empty( $disabled_wp_obj ) ) {
										$premium_ad = ' [Available in Premium]';
									}
									echo "<option value='";
									echo esc_attr( $value );
									echo "'";
									echo esc_attr( $select_wp_obj ) . ' ' . esc_attr( $disabled_wp_obj );
									echo '>' . esc_html( $value ) . esc_html( $premium_ad ) . '</option>';
								}
								?>
							</select>
							<?php } ?>
							<br/><br/>
							<div class="mo_sf_sync_help_desc" <?php echo esc_attr( $wp_object_change_message ); ?>>To change the WordPress Object please delete the existing mapping and create a new one.</div>
						</div>
						<div style="margin-left:100px">Support for Custom post types and additional WordPress objects and Integrations available in Premium version<br> <a href='?page=mo_sf_sync&tab=demo_setup'><b>Try Now For Free</b></a>
						</div>
					</div>
				</div>
			</section>

			<section class="accordion mo-sf-mt-4" >
				<input type="checkbox" name="collapse" checked="checked">
				<h2 class="handle">
					<label class = "mo-sf-sync-coll-div-head"><b>2. Select Sync Direction</b></label>
				</h2>
				<div class="mo-sf-sync-tab-content-tile content" style="box-shadow:none">
					<div class="mo-sf-dflex" style="border: none;box-shadow:none;">
						<div class=" mo-sf-col-md-3">
							<b><span>Choose Sync Direction <span class="mo-sf-text-danger">*</span></span></b>
						</div>
						<div class="mo-sf-col-md-8-field-mapping">
							<span class="mo-sf-col-md-5" style="background-color: white;">
								<input type="radio" name="sync_wp_to_sf" id="sync_wp_to_sf" <?php checked( $this->sync_wp_to_sf, true ); ?> onchange="mo_sf_sync_change_direction('sync_wp_to_sf')">
								<span>Sync From WordPress to Salesforce</span>
							</span>
							<span class="mo-sf-col-md-5 mo-sf-ml" style="background-color:white;">
								<input type="radio" name="sync_sf_to_wp" id="sync_sf_to_wp" <?php checked( $this->sync_sf_to_wp, true ); ?> onchange="mo_sf_sync_change_direction('sync_sf_to_wp')">
								<span>Sync From Salesforce to WordPress</span>
							</span>
						</div>
					</div>
				</div>
			</section>

			<section class="accordion mo-sf-mt-4" >
				<input type="checkbox" name="collapse" checked="checked">
				<h2 class="handle">
					<label class = "mo-sf-sync-coll-div-head" ><b>3. Select Salesforce Object</b></label>
				</h2>
				<div class="mo-sf-dflex mo-sf-sync-tab-content-tile content" style="border: none;box-shadow: none">
					<div class=" mo-sf-col-md-3">
						<b><span>Salesforce object <span class="mo-sf-text-danger">*</span></span></b>
					</div>
					<div class="mo-sf-col-md-8-field-mapping">
						<div>
							<select class="form-control" id="object_select" style="width: 300px;" name="object_select" data-object="<?php echo esc_attr( $this->object ); ?>">
								<option value="">Select a Salesforce Object</option>
								<?php
								foreach ( $this->sf_objects as $obj ) {
									echo '<option value=' . esc_attr( $obj['name'] );
									$_temp = $obj['name'] === $this->object ? ' selected' : '';
									echo esc_attr( $_temp );
									echo '>' . esc_attr( $obj['label'] ) . '</option>';
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</section>

			<div style="text-align:center" id="sf-obj-search" hidden>
				<div class="" style="display: flex;align-items: center;justify-content: center;">
					<img  alt="Loading..." src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../../images/preloder1.gif' ); ?>" />
					<span style="font-style: italic;margin-left: -30px;">Loading... Please Wait...</span></div>
				</div>

			<section class="accordion field_mapping mo-sf-mt-4" hidden>
				<input type="checkbox" name="collapse" checked="checked" >
				<h2 class="handle">
					<label class = "mo-sf-sync-coll-div-head" ><b>4. Map WordPress fields to Salesforce fields</b></label>
				</h2>
				<div class="mo-sf-sync-tab-content-tile mo-sf-dflex content" style="box-shadow: none;">
					<div class="mo-sf-col-md-3">
						<b><span>Field Mapping</span></b><span style="margin-top:2px;margin-left:2px" class="dashicons dashicons-editor-help information">
							<p class="information-text">Create a map between all the Salesforce and WordPress fields you require to sync. </p>
						</span>
					</div>
					<div class="sf-wp-mapping-div mo-sf-col-md-8-field-mapping">
						<?php
						if ( isset( $this->field_mapping['name_constraint'] ) ) {
							foreach ( $this->field_mapping['name_constraint'] as $key => $value ) {
								?>
								<section class="accordion">
									<input type="checkbox" name="collapse" id="<?php echo esc_attr( $value ); ?>" checked="checked">
									<input type="hidden" name="sf_fields[]" value="<?php echo esc_attr( $value ); ?>">
									<input type="hidden" name="name_constraint[]" value="<?php echo esc_attr( $value ); ?>">
									<input type="hidden" name="type_constraint[]" value="<?php echo esc_attr( $this->field_mapping['type_constraint'][ $key ] ); ?>">
									<input type="hidden" name="maxlength_constraint[]" value="<?php echo esc_attr( $this->field_mapping['size_constraint'][ $key ] ); ?>">
									<h2 class="handle">
										<label class = "mo-sf-sync-coll-div-head" for="<?php echo esc_attr( $value ); ?>"><b>
																									<?php
																									echo esc_html( $value );
																									if ( $this->mo_sf_sync_check_required_field( $value ) && $this->sync_wp_to_sf ) {
																										echo '<span class="required-fields-asterisk" style="font-size: 14px !important; color:red">*</span>';
																									} elseif ( $this->mo_sf_sync_check_required_field( $value ) && ! $this->sync_wp_to_sf ) {
																										echo '<span class="required-fields-asterisk"></span>';
																									}
																									?>
										</b><span style="font-size: 14px !important;font-style: italic;">  [Name:<?php echo esc_html( $value ); ?>, Type: <?php echo esc_html( $this->field_mapping['type_constraint'][ $key ] ); ?>, Max-length: <?php echo esc_html( $this->field_mapping['size_constraint'][ $key ] ); ?>] </span>
										<?php
										if ( $this->sync_sf_to_wp || $this->mo_sf_sync_check_required_field( $value ) === null ) {
											?>
											<div class="nonreq-field-delete-button" style="float:right ;">
												<button style="border:none;cursor:pointer;background:none" ><span class="dashicons dashicons-trash"  onclick="deleteAttr(event, <?php echo esc_html( $value ); ?>);return false;"></span></button>
											</div>
											<?php
										} else {
											?>
											<div class="req-field-delete-button" style="float:right ;"></div>
											<?php
										}
										?>
										</label>
									</h2>
									<div class="mo-sf-dflex content" style="border-bottom: 0;">
										<div class=" mo-sf-col-md-3" style="border-bottom:none">
											<span>Field Type</span>
										</div>
										<div class="mo-sf-col-md-8-field-mapping">
											<div>
												<select class="form-control" id="field_type_<?php echo esc_attr( $value ); ?>" style="width: 300px;" name="field_types[]" onchange="mo_sf_sync_change_field_type('<?php echo esc_attr( $value ); ?>')">
													<option 
													<?php
													if ( 'standard' === $this->field_mapping['field_type'][ $key ] ) {
														echo 'selected';
													} else {
														echo '';
													}
													?>
													value="standard">
													<?php
													if ( 'picklist' === $this->field_mapping['type_constraint'][ $key ] ) {
														echo 'Standard fields';
													} else {
														echo 'Standard WordPress fields';
													}
													?>
													</option> 
													<option 
													<?php
													if ( 'static' === $this->field_mapping['field_type'][ $key ] ) {
														echo 'selected';
													} else {
														echo '';
													}
													?>
													value="static">
													<?php
													if ( 'picklist' === $this->field_mapping['type_constraint'][ $key ] ) {
														echo 'Conditional fields';
													} else {
														echo 'Static';
													}
													?>
													</option> 
												</select>
											</div>
										</div>
									</div>
									<div class="mo-sf-dflex content" style="margin-top:-20px !important">
										<div class="mo-sf-col-md-3">
											<span>Select Field</b></span>
										</div>
											<?php
											if ( 'picklist' === $this->field_mapping['type_constraint'][ $key ] ) {
												if ( 'static' === $this->field_mapping['field_type'][ $key ] ) {
													?>
												<div class="mo-sf-col-md-8-field-mapping">
													<div id="sync_sel_fields_div_<?php echo esc_attr( $value ); ?>">
														<div id="picklist_conditon_table_<?php echo esc_attr( $value ); ?>">
															<?php
															$i = 0;
															foreach ( $this->field_mapping['field_mapping'][ $value ]['picklist_wp_fields'] as $left => $right ) {
																?>
																<?php
																if ( $i > 0 ) {
																	echo '<div style="text-align:center"><div class="mo_sf_sync_conditional_or">OR</div></div>';
																} $i++;
																?>
																<div class="mo-sf-dflex">
																	<div>
																		<select class="form-control" name="wordpress_fields[<?php echo esc_attr( $key ); ?>][picklist_wp_fields][]" style="width:115px">
																			<?php
																			foreach ( $data_fields as $wpfield => $key_array ) {
																				echo '<option ';
																				$selected = $key_array['key'] === $right ? ' selected' : '';
																				echo esc_attr( $selected );
																				echo '>' . esc_attr( $key_array['key'] ) . '</option>';
																			}
																			?>
																		</select>
																	</div>
																	<div><span>Must</span></div>
																	<div>
																		<select class="form-control operator" name="wordpress_fields[<?php echo esc_attr( $key ); ?>][picklist_conditions][]" style="width:115px" >
																			<option  value='starts-with' 
																			<?php
																			if ( 'starts-with' === $this->field_mapping['field_mapping'][ $value ]['picklist_conditions'][ $left ] ) {
																				echo 'selected';}
																			?>
																			>Starts with</option>
																			<option  value='ends-with' 
																			<?php
																			if ( 'ends-with' === $this->field_mapping['field_mapping'][ $value ]['picklist_conditions'][ $left ] ) {
																				echo 'selected';}
																			?>
																			>Ends With</option>
																			<option  value='includes' 
																			<?php
																			if ( 'includes' === $this->field_mapping['field_mapping'][ $value ]['picklist_conditions'][ $left ] ) {
																				echo 'selected';}
																			?>
																			>Includes</option>
																			<option  value='must-not-include' 
																			<?php
																			if ( 'must-not-include' === $this->field_mapping['field_mapping'][ $value ]['picklist_conditions'][ $left ] ) {
																				echo 'selected';}
																			?>
																			>Must Not Include</option>
																		</select>
																	</div>
																	<div ><span>the value</span></div>
																	<div>
																		<input type="text" readonly class="form-control" name="wordpress_fields[<?php echo esc_attr( $key ); ?>][picklist_output][]" style="width:115px" value="<?php echo esc_attr( $this->field_mapping['field_mapping'][ $value ]['picklist_output'][ $left ] ); ?> ">
																	</div>
																	<div >then value synced will be</div>
																	<div>
																		<input type="text" readonly class="form-control" name="wordpress_fields[<?php echo esc_attr( $key ); ?>][picklist_result][]" style="width:115px" value="<?php echo esc_attr( $this->field_mapping['field_mapping'][ $value ]['picklist_result'][ $left ] ); ?> ">
																	</div>

																</div>									
																<?php
															}
															?>
														</div>   
													</div>
											</div>
										</div>
													<?php
												} else {
													?>
										<div class="mo-sf-col-md-8-field-mapping">
												<div id="sync_sel_fields_div_<?php echo esc_attr( $value ); ?>">
												<input type="text" class="form-control" name="wordpress_fields[]" style="width:300px" value="<?php echo esc_attr( $this->field_mapping['field_mapping'][ $value ] ); ?>">
												</div>
											</div>
										</div>
													<?php
												}
											} else {
												?>
									<div class="mo-sf-col-md-8-field-mapping">
										<div id="sync_sel_fields_div_<?php echo esc_attr( $value ); ?>">
												<?php
												if ( 'static' !== $this->field_mapping['field_type'][ $key ] ) {
													?>
												<select class="form-control select2" style="width: 300px;" name="wordpress_fields[]">
													<?php
													foreach ( $data_fields as $wpfield => $key_array ) {
														echo '<option ';
														$selected = $key_array['key'] === $this->field_mapping['field_mapping'][ $value ] ? 'selected' : '';
														echo esc_attr( $selected );
														echo '>' . esc_attr( $key_array['key'] ) . '</option>';
													}
													?>
												</select>
													<?php
												} else {
													?>
											<input type="text" style="width: 300px;" name="wordpress_fields[]" value="<?php echo esc_attr( $this->field_mapping['field_mapping'][ $value ] ); ?>">
													<?php
												}
												?>
										</div>
									</div>
									</div>   
												<?php
											}
											?>

								</section>     
									<?php
							}
						}
						?>
					</div>
				</div>
			</section>
			</div>
			<div id="save_obj_map_btn" style="text-align: center;" hidden>
				<input type="submit" class="mo-sf-btn-cstm"  value="Save Object Mapping" >
			</div>
		</form>
		<?php
		if ( ! isset( $this->mapping_data ) || empty( $this->mapping_data ) ) {
			?>
			<input type="hidden" id="set_field_map" value="field_map_is_not_set">
			<?php
		}
	}

	/**
	 * Checks whether a field is a required field or not.
	 *
	 * @param string $selected_field The field to be checked.
	 * @return bool
	 */
	private function mo_sf_sync_check_required_field( $selected_field ) {
		foreach ( $this->sf_obj_fields as $field ) {
			if ( ( $selected_field === $field['name'] ) && ( 'boolean' !== $field['createable'] && ! $field['nillable'] && ! $field['defaultedOnCreate'] && $field['type'] ) ) {
				return true;
			}
		}
	}

	/**
	 * Creates Salesforce fields.
	 *
	 * @param string $selected_field The selected field.
	 * @return void
	 */
	public function mo_sf_sync_create_fields( $selected_field ) {
		$disabled = ' ';
		foreach ( $this->sf_obj_fields as $field ) {
			if ( in_array( $field['name'], $this->field_mapping['name_constraint'], true ) ) {
				$disabled = 'disabled';
			}
			$required = '';
			if ( $field['createable'] && ! $field['nillable'] && ! $field['defaultedOnCreate'] && 'boolean' !== $field['type'] ) {
				$required = '* ';
			}
			echo '<option value="' . esc_attr( $field['name'] ) . ',' . esc_attr( $field['type'] ) . ',' . esc_attr( $field['length'] ) . '"' . selected( $field['name'], $selected_field, false ) . ' ' . esc_attr( $disabled ) . '>' . esc_attr( $required ) . esc_attr( $field['label'] ) . ' (' . esc_attr( $field['type'] ) . ')</option>';
		}
	}

	/**
	 * Displays test configuration section in Object Mapping tab if the mapping is from WordPress to Salesforce otherwise displays the Real Time Salesforce to WordPress Sync section.
	 *
	 * @return void
	 */
	public function mo_sf_sync_display_test_config() {
		if ( empty( $this->mapping_data ) ) {
			return;
		}
		if ( 'user' !== $this->mapping_data['wordpress_object'] && 'post' !== $this->mapping_data['wordpress_object'] ) {
			return;
		}
		$is_mapping_configured = ( isset( $this->mapping_data['label'] ) && ! empty( $this->mapping_data['label'] ) ? true : false );
		if ( array_key_exists( 'sync_sf_to_wp', $this->mapping_data ) && '1' === $this->mapping_data['sync_sf_to_wp'] ) {
			$outbound_url = $this->db->mo_sf_sync_get_data_from_metatable( $this->mapping_data['id'], 'outbound_redirect_uri' )['outbound_redirect_uri'];
			?>
			</br>
				<div class="mo-sf-sync-tab-content-tile" id="test-config-tile" 
			<?php
			if ( Utils::mo_sf_sync_is_authorization_configured() && $is_mapping_configured ) {
				echo '';
			} else {
				echo 'hidden';
			}
			?>
				>
					<h2 class="mo-sf-form-head mo-sf-form-head-bar mo-sf-mt-4">Real Time Salesforce to WordPress Sync</h2>
					<div class="mo-sf-sync-tab-content-tile-content">
						<div class="mo_sf_sync_help_desc">
						<h4>
							<span>
								<b><a class="mo-sf-sync-guide-button" target="blank" href="https://plugins.miniorange.com/salesforce-wordpress-real-time-sync-workflow-automation">Click Here</a></b>
								to open an extensive<b> step by step guide </b> to configure <b>Real Time Salesforce to WordPress Sync</b>.
							</span>
						</div>
						</br>
						<div class="mo-sf-dflex mo-sf-ml-1 mo-sf-mt-8">
							<div class="mo-sf-col-md-4" >
								<h2>Redirect URI for outbound message: </h2>
							</div>
							<div class="mo-sf-dflex mo-sf-ml-1">
								<div class="mo_sf_sync_help_desc" style="word-break: break-all;">
									<code id="ob_redirect_url" style="background: none;"><b><?php echo esc_url( $outbound_url ); ?></b></code>
								</div>								
								<div style="margin-left: 30px ;">
									<i  class="mo_copy copytooltip rounded-circle" onclick="mo_sf_sync_copyToClipboard(this, '#ob_redirect_url', '#metadata_url_copy');"><span class="dashicons dashicons-admin-page"></span><span id="ob_redirect_url_copy" class="copytooltiptext">Copy to Clipboard</span></i>
								</div>
							</div>
						</div>
					</div>
				</div>
			<?php
		} else {
			?>
			</br>
				<div class="mo-sf-sync-tab-content-tile" id="test-config-tile" 
			<?php
			if ( Utils::mo_sf_sync_is_authorization_configured() && $is_mapping_configured ) {
				echo '';
			} else {
				echo 'hidden';
			}
			?>
				>
					<h2 class="mo-sf-form-head mo-sf-form-head-bar mo-sf-mt-4">Test Connection</h2>
					<div class="mo-sf-sync-tab-content-tile-content">
						<table class="mo-sf-sync-tab-content-app-config-table">
							<tr>
								<td colspan="2" class="mo-sf-note">
									<h4> Select a WordPress user to test the connection. This will create a record in the selected Salesforce object as per the configured field mapping.</h4>
								<?php if ( ! $is_mapping_configured ) { ?>
										<h4 style="color:red">Please configure field mapping first before testing the connection!</h4>
									<?php } ?>
								</td>
							</tr>
							<tr>
								<td><br></td>
							</tr>
							<tr>
								<td class="left-div">
									<span>Search Users</span>
								</td>
								<td class="mo-sf-dflex">
									<input type="text" class="mo-sf-w-3" name="query" id="enter_search" placeholder="Enter Username/User Email to search user." />
									<input type="button" id="search_users" class="mo-sf-ml-5 mo-sf-btn-cstm" value="Search">
									<span class="mo-sf-sync-user-search-alert" id="push_plh" name="push_plh" type="text" hidden>No User Found </span>
								</td>
							</tr>
							<tr>
								<td class="left-div">
									<span>Users</span>
								</td>
								<td class="right-div mo-sf-dflex">
									<select class="form-control mo-sf-w-3" id="upn_id" name="upn_id">
									</select>
									<input id="push_attributes" type="button" class="mo-sf-ml-5 mo-sf-btn-cstm" value="Push" style="width:100px" 
									<?php
									if ( ! $is_mapping_configured ) {
										echo 'disabled';}
									?>
									>
								</td>
							</tr>
						</table>
					</div>
				</div>
			<?php
		}
	}
}
