<?php
/**
 * This file takes care of rendering all components (header, navbar, body) for plugin pages.
 *
 * @package object-data-sync-for-salesforce\Helper\view
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MoSfSyncSalesforce\Helper\Plugin_Constants;
use MoSfSyncSalesforce\Customer;
use MoSfSyncSalesforce\Services\Utils;

require_once 'import-export.php';
require_once 'test-connection.php';

/**
 * Calls the appropriate functions to display header, tab list and body for a page.
 *
 * @return void
 */
function mo_sf_sync_display_view() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading tab name from URL.
	if ( isset( $_GET['tab'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading tab name from URL.
		$active_tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
	} else {
		$active_tab = 'app_config';
	}
	echo '<div id="mo-sf-sync-mo-container" class="mo-sf-sync-mo-container">';
	mo_sf_sync_display_header();
	if ( 'licenseplan' === $active_tab ) {
		include 'mo-sf-licenseplans.html';
	} elseif ( array_key_exists( 'mapping_label', $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading from GET URL. 
		mo_sf_sync_display_tabs( $active_tab );
		mo_sf_sync_display_body( $active_tab );
	} else {
		mo_sf_sync_display_tabs( $active_tab );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading from GET URL. 
		if ( 'account_setup' === $active_tab || array_key_exists( 'mapping_label', $_GET ) ) {
			echo '<div class="mo-sf-row mo-sf-container-fluid">';

			if ( Customer::mo_sf_sync_is_customer_logged_in() ) {
				echo '<div class="mo-sf-col-md-8">';
				mo_sf_sync_display_body( $active_tab );
				echo '</div></div>';
				echo '<div class="mo-sf-col-md-4">';
				mo_sf_sync_support( $active_tab );
			} else {
				echo '<div class="mo-sf-col-md-12">';
				mo_sf_sync_display_body( $active_tab );
			}
		} else {
			echo '<div class="mo-sf-row mo-sf-container-fluid">
				<div class="mo-sf-col-md-8">';
			mo_sf_sync_display_body( $active_tab );
			if ( 'account_setup' !== $active_tab ) {
				echo '		</div>
				<div class="mo-sf-col-md-4">';
				mo_sf_sync_support( $active_tab );
			}
		}
		if ( '' === $active_tab || 'app_config' === $active_tab ) {
			mo_sf_sync_save_data_from_deletion();
		}
		if ( 'advance_sync_options' === $active_tab ) {
			mo_sf_sync_advertise_add_on();
		}
		echo '		</div>
		</div>';
		mo_sf_sync_loader_class();
		echo '</div>';
	}
}

/**
 * Displays the keep settings intact section.
 *
 * @return void
 */
function mo_sf_sync_save_data_from_deletion() {
	?>
	<div class="mo_sf_sync_keep_configuration_intact" style=" position: relative;">
		<h1 class="mo-sf-cnt-head">
			<?php esc_html_e( 'Keep configuration Intact', 'object-data-sync-for-salesforce' ); ?>
		</h1>
		<form name="f" method="post" action="" id="settings_intact">
			<?php wp_nonce_field( 'mo_sf_sync_keep_settings_on_deletion' ); ?>

			<input type="hidden" name="option" value="mo_sf_sync_keep_settings_on_deletion" />
			<label class="switch">
				<input type="checkbox" name="mo_sf_sync_keep_settings_on_deletion" <?php echo checked( 'true' === get_option( 'mo_sf_sync_keep_settings_on_deletion' ) ); ?> onchange="document.getElementById('settings_intact').submit();" />
				<span class="slider round"></span>
			</label><span class="mo-sf-ml-1 mo-sf-text">Enabling this would keep your settings intact when plugin is uninstalled.</span>
			<p class="mo-sf-text"><b>Please enable this option when you are updating to a Premium version.</b></p>

			</label>
		</form>

		<hr>

		<input type="button" name="mo_sf_sync_request" id="export-import-config" value="Export Plugin Configuration" class="mo-sf-btn-cstm mo-sf-mt-4" onclick="jQuery('#mo_export').submit();" /><br></br>
		<form method="post" action="" name="mo_export" id="mo_export">
			<?php
			wp_nonce_field( 'mo_sf_sync_export' );
			?>
			<input type="hidden" name="option" value="mo_sf_sync_export" />
		</form>
		<hr>
		<form method="post" id="import_config" name="import_config" action="" enctype="multipart/form-data">
			<?php wp_nonce_field( 'mo_sf_sync_import' ); ?>
			<input type="hidden" name="option" value="mo_sf_sync_import" />
			<label>
				<h2 class="mo-sf-cnt-head"><b>Import Configurations</b></h2>
				<input type="file" name="configuration_file" id="configuration_file">
				<input type="button" name="button" style="width: auto" class="mo-sf-btn-cstm" value="Import" onclick="jQuery('#import_config').submit();" />
			</label>
		</form>
	</div>
	<?php
}

/**
 * Displays plugin header.
 *
 * @return void
 */
function mo_sf_sync_display_header() {
	?>
	<div class="mo-sf-dflex">
		<div class="mo-sf-col-md-6">
			<h1>Object Data Sync For Salesforce</h1>
		</div>
		<div class="mo-sf-col-md-6">
			<a href="?page=troubleshoot">
					<button class="upgrade-to-premium-plan-button" > <strong>Troubleshoot</strong></button>
			</a>
			<a href="?page=mo_sf_sync&tab=licenseplan">
				<button class="upgrade-to-premium-plan-button" > <strong> Premium Plans | Upgrade Now </strong></button>
			</a>
			<a href="https://wordpress.org/support/plugin/object-data-sync-for-salesforce/" rel="noopener noreferrer" target="_blank">
				<button class="upgrade-to-premium-plan-button"> <strong> Support Forum </strong></button>
			</a>
		</div>
	</div>
	<?php
	if ( get_transient( 'mo_sf_sync_integration_trial_notice_dismiss_time' ) !== true && get_transient( 'mo_sf_sync_made_integration_trial_request' ) !== true ) {
		mo_sf_sync_show_integration_message();
	}
}

/**
 * Displays tab list.
 *
 * @param string $active_tab Current active tab name.
 * @return void
 */
function mo_sf_sync_display_tabs( $active_tab ) {
	if ( isset( $_SERVER['REQUEST_URI'] ) ) {
		$_SERVER['REQUEST_URI'] = esc_url_raw( remove_query_arg( array( 'mapping_label', 'action' ), esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) );
	}
	?>
	<div id="mo_sf_sync_tab_view" class="mo-sf-sync-tab sf-sync-tab-background mo-sf-sync-tab-border">
		<ul id="mo_sf_sync_tab_view_ul" class="mo-sf-sync-tab-ul" role="toolbar">
			<li id="app_config" class="mo-sf-sync-tab-li" role="presentation" title="Application">
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'app_config' ) ); ?>">
					<div id="application_div_id" class="mo-sf-sync-tab-li-div 
					<?php
					if ( 'app_config' === $active_tab ) {
						echo 'mo-sf-sync-tab-li-div-active';
					}
					?>
					" aria-label="Application" title="Application Configuration" role="button" tabindex="0">
						<div id="add_icon" class="mo-sf-sync-tab-li-icon">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-gear" viewBox="0 0 16 16">
								<path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/>
								<path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115l.094-.319z"/>
							</svg>
						</div>
						<div id="add_app_label" class="mo-sf-sync-tab-li-label">
							Manage Application
						</div>
					</div>
				</a>
			</li>
			<li id="user_sync" class="mo-sf-sync-tab-li" role="presentation" title="user_sync">
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'manage_users' ) ); ?>">
					<div id="user_sync_div_id" class="mo-sf-sync-tab-li-div 
					<?php
					if ( 'manage_users' === $active_tab ) {
						echo 'mo-sf-sync-tab-li-div-active';
					}
					?>
					" aria-label="user_sync" title="User Management" role="button" tabindex="1">
						<div id="user_sync_icon" class="mo-sf-sync-tab-li-icon">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-diagram-3" viewBox="0 0 16 16">
								<path fill-rule="evenodd" d="M6 3.5A1.5 1.5 0 0 1 7.5 2h1A1.5 1.5 0 0 1 10 3.5v1A1.5 1.5 0 0 1 8.5 6v1H14a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-1 0V8h-5v.5a.5.5 0 0 1-1 0V8h-5v.5a.5.5 0 0 1-1 0v-1A.5.5 0 0 1 2 7h5.5V6A1.5 1.5 0 0 1 6 4.5v-1zM8.5 5a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1zM0 11.5A1.5 1.5 0 0 1 1.5 10h1A1.5 1.5 0 0 1 4 11.5v1A1.5 1.5 0 0 1 2.5 14h-1A1.5 1.5 0 0 1 0 12.5v-1zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zm4.5.5A1.5 1.5 0 0 1 7.5 10h1a1.5 1.5 0 0 1 1.5 1.5v1A1.5 1.5 0 0 1 8.5 14h-1A1.5 1.5 0 0 1 6 12.5v-1zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zm4.5.5a1.5 1.5 0 0 1 1.5-1.5h1a1.5 1.5 0 0 1 1.5 1.5v1a1.5 1.5 0 0 1-1.5 1.5h-1a1.5 1.5 0 0 1-1.5-1.5v-1zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1z"/>
							</svg>
						</div>
						<div id="user_sync_label" class="mo-sf-sync-tab-li-label">
							Object Mapping
						</div>
					</div>
				</a>
			</li>
			<li id="advance_sync_options" class="mo-sf-sync-tab-li" role="presentation" title="advance_sync_options">
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'advance_sync_options' ) ); ?>">
					<div id="advance_sync_options_div_id" class="mo-sf-sync-tab-li-div 
					<?php
					if ( 'advance_sync_options' === $active_tab ) {
						echo 'mo-sf-sync-tab-li-div-active';
					}
					?>
					" aria-label="advance_sync_options" title="Advance Sync Options" role="button" tabindex="1">
						<div id="advance_sync_options_icon" class="mo-sf-sync-tab-li-icon">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-repeat" viewBox="0 0 16 16">
								<path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/>
								<path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"/>
							</svg>
						</div>
						<div id="advance_sync_options_label" class="mo-sf-sync-tab-li-label">
							Advanced Sync Options
						</div>
					</div>
				</a>
			</li>


			<li id="account_setup" class="mo-sf-sync-tab-li" role="presentation" title="account_setup">
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'account_setup' ) ); ?>">
					<div id="account_setup_div_id" class="mo-sf-sync-tab-li-div 
					<?php
					if ( 'account_setup' === $active_tab ) {
						echo 'mo-sf-sync-tab-li-div-active';
					}
					?>
					" aria-label="account_setup" title="account_setup" role="button" tabindex="1">
						<div id="account_setup_icon" class="mo-sf-sync-tab-li-icon">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16">
								<path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
								<path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
							</svg>
						</div>
						<div id="account_setup_label" class="mo-sf-sync-tab-li-label">
							Account Setup
						</div>
					</div>
				</a>
			</li>
			<li id="demo_setup" class="mo-sf-sync-tab-li" role="presentation" title="demo_setup">
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'demo_setup' ) ); ?>">
					<div id="demo_setup_div_id" class="mo-sf-sync-tab-li-div 
					<?php
					if ( 'demo_setup' === $active_tab ) {
						echo 'mo-sf-sync-tab-li-div-active';
					}
					?>
					" aria-label="demo_setup" title="demo_setup" role="button" tabindex="1">
						<div id="demo_setup_icon" class="mo-sf-sync-tab-li-icon">
							<span class="dashicons dashicons-plugins-checked"></span>
						</div>
						<div id="demo_setup_label" class="mo-sf-sync-tab-li-label">
							Demo Setup
						</div>
					</div>
				</a>
			</li>

			<li id="integrations" class="mo-sf-sync-tab-li" role="presentation" title="integrations">
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'integrations' ) ); ?>">
					<div id="integrations_div_id" class="mo-sf-sync-tab-li-div 
					<?php
					if ( 'integrations' === $active_tab ) {
						echo 'mo-sf-sync-tab-li-div-active';
					}
					?>
					" aria-label="integrations" title="integrations" role="button" tabindex="1">
						<div id="integrations_icon" class="mo-sf-sync-tab-li-icon">
							<span class="dashicons dashicons-share-alt"></span>
						</div>
						<div id="integrations_label" class="mo-sf-sync-tab-li-label">
							Integrations
						</div>
					</div>
				</a>
			</li>
		</ul>
	</div>
	<?php
}

/**
 * Includes necessary files and displays body for selected tab.
 *
 * @param string $active_tab Current active tab.
 * @return void
 */
function mo_sf_sync_display_body( $active_tab ) {
	switch ( $active_tab ) {
		case 'app_config':
				include_once dirname( __FILE__ ) . '/app-config.php';
				mo_sf_sync_display_app_config();
			break;
		case 'manage_users':
				include_once dirname( __FILE__ ) . '/manage-objects.php';
				mo_sf_sync_display_manage_users();
			break;
		case 'advance_sync_options':
				include_once dirname( __FILE__ ) . '/advance-sync-options.php';
				mo_sf_sync_display_advanced_sync_options();
			break;
		case 'plugin_guide':
				include_once dirname( __FILE__ ) . '/plugin-guide.php';
				mo_sf_sync_display_plugin_guide();
			break;
		case 'troubleshoot':
				include_once dirname( __FILE__ ) . '/mo-sf-troubleshoot.php';
				mo_sf_sync_display_troubleshooting();
				mo_sf_sync_reset();
			break;
		case 'account_setup':
				include_once dirname( __FILE__ ) . '/account-setup.php';
				mo_sf_sync_display_account_setup();
			break;
		case 'demo_setup':
				$demo_content = Utils::mo_sf_sync_get_settings( Plugin_Constants::DEMO_REQUEST_CONTENT );
			if ( ! empty( $demo_content ) ) {
				include_once dirname( __FILE__ ) . '/mo-sf-sync-demo-status.php';
				mo_sf_sync_show_demo_status( $demo_content );
			} else {
				include_once dirname( __FILE__ ) . '/demo-setup.php';
				mo_sf_sync_setup_demo();
			}
			break;
		case 'integrations':
				include_once dirname( __FILE__ ) . '/mo-sf-integrations.php';
				mo_sf_sync_show_integrations();
			break;
	}
}

/**
 * Loads feedback messages in the page to be displayed later on.
 *
 * @return void
 */
function mo_sf_sync_loader_class() {
	?>
	<div class="mo-sf-sync-loader-container">
		<div id="loader" class="mo-sf-sync-loader mo-sf-sync-ellipsis">
			<div></div>
			<div></div>
			<div></div>
			<div></div>
		</div>
		<div id="success_m" class="mo-sf-sync-loader mo-sf-sync-isa-success" style="position:relative;right: 50px;">
			<i class="fa fa-check"></i>
			Configuration Saved Successfully!
		</div>
		<div id="error_m" class="mo-sf-sync-loader mo-sf-sync-isa-error">
			<i class="fa fa-times-circle"></i>
			An error occurred while processing your request, please try again.
		</div>
		<div id="support_m" class="mo-sf-sync-loader mo-sf-sync-isa-success">
			<i class="fa fa-times-circle"></i>
			Thanks for getting in touch! We shall get back to you shortly.
		</div>
	</div>
	<?php
}

/**
 * Displays the support form.
 *
 * @param string $active_tab Current active tab.
 * @return void
 */
function mo_sf_sync_support( $active_tab = '' ) {
	?>
	<div class="mo_sf_sync_support_layout mo-sf-mt-4">
		<h1 class="mo-sf-cnt-head">
			<?php echo 'Feature Request/Contact Us <br> (24*7 Support)'; ?>
		</h1>
		<div>
			<div class="mo-sf-text"><b>
					<?php esc_html_e( 'Call us at +1 978 658 9387 in case of any help', 'object-data-sync-for-salesforce' ); ?>
				</b></div>
		</div>
		<p class="mo-sf-text">
			<?php esc_html_e( 'We can help you with configuring your Salesforce. Just send us a query and we will get back to you soon.', 'object-data-sync-for-salesforce' ); ?><br>
		</p>

		<form class="mo_sf_sync_ajax_submit_form">
			<input type="hidden" name="option" value="mo_sf_sync_contact_us_query_option" />
			<input type="hidden" name="tab" value="<?php echo esc_attr( $active_tab ); ?>">
			<input type="hidden" name="nonce_" value="<?php echo esc_attr( wp_create_nonce( 'mo_sf_sync_contact_us_query_option' ) ); ?>">
			<table class="mo_sf_sync_settings_table">
				<tr>
					<td><input type="email" id="mo_sf_sync_support_email" placeholder="<?php esc_html_e( 'Enter your email', 'object-data-sync-for-salesforce' ); ?>" class="mo_sf_sync_table_textbox" name="mo_sf_sync_contact_us_email" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" required>
					</td>
				</tr>
				<tr>
					<td><textarea class="mo_sf_sync_table_textbox" onkeypress="mo_sf_sync_valid_query(this)" onkeyup="mo_sf_sync_valid_query(this)" onblur="mo_sf_sync_valid_query(this)" name="mo_sf_sync_contact_us_query" rows="4" style="resize: vertical;" required placeholder="<?php esc_html_e( 'Write your query here', 'object-data-sync-for-salesforce' ); ?>" id="mo_sf_sync_query"></textarea>
					</td>
				</tr>
			</table>
			<div>
				<input type="submit" name="submit" style="margin:15px; width:120px;" class="mo-sf-btn-cstm" value="Submit"/>
				<div style="margin-right:6rem;" class="loader-placeholder"></div>
			</div>
		</form>
	</div><br>

	<?php
}

/**
 * Displays addon integration information.
 *
 * @return void
 */
function mo_sf_sync_advertise_add_on() {
	$suggested_addons = Plugin_Constants::SUGGESTED_ADDONS;

	foreach ( $suggested_addons as $addon ) {
		?>
	</br>
		<div class="mo-sf-sync-card-glass ">
			<div class="mo-sf-sync-ads-text" style="text-align: center;">
				<span class="font5"><?php echo esc_html( $addon['title'] ); ?></span>
				<hr />
				<ul class="pl-1" style="text-align:justify;color:black">
					<p style="font-size: 13px;"><?php echo esc_attr( $addon['text'] ); ?></p>
					<a target="_blank" href="<?php echo esc_attr( $addon['link'] ); ?>" class="text-warning">Download</a>
					<a target="_blank" href="<?php echo esc_attr( $addon['knw-link'] ); ?>" class="text-warning float-right">Know More</a>
				</ul>
			</div>
		</div>

		<?php
	}
}

/**
 * Checks which 3rd part addons are installed and displays integration notice.
 *
 * @return void
 */
function mo_sf_sync_show_integration_message() {
	$integrations = Utils::mo_sf_sync_class_exists_check();
	if ( ! empty( $integrations ) && is_array( $integrations ) ) {
		mo_sf_sync_show_integration_notice( $integrations );
	}
}

/**
 * Displays integration notice.
 *
 * @param array $ints_name List of integration names.
 * @return void
 */
function mo_sf_sync_show_integration_notice( $ints_name ) {
	$integrations = implode( ' ,', $ints_name );
	$class        = 'notice notice-info';
		echo "<div id='int_trial_div_id' class='" . esc_attr( $class ) . "'> <p>" . 'We have <b><a href="?page=mo_sf_sync&tab=integrations"> Integration Recommendation </a></b> for you, check out <b>' . esc_html( implode( ' ,', $ints_name ) ) . '</b> integration with our plugin.</p>';
	?>
		<form action="" method="post">
			<?php wp_nonce_field( 'mo_sf_sync_trial_request_for_integrations' ); ?>
			<input type="hidden" name="option" value="mo_sf_sync_trial_request_for_integrations">
			<input type="hidden" name="requested_integrations" value="<?php echo esc_html( $integrations ); ?>">
			<input type="submit" name="mo_sf_sync_trial_request_for_integrations" value="Click here to try out 
			<?php
			if ( count( $ints_name ) > 1 ) {
				echo 'these integrations';
			} else {
				echo esc_html( implode( ' ,', $ints_name ) );}
			?>
			integration with our plugin" class="button button-primary">
			<input type="button" name="mo_sf_sync_dismiss" value="Dismiss"class="button-secondary um_secondary_dimiss" onclick="mo_sf_sync_dismiss_int_trial_notification_bar()">
		</form>
		<?php
		echo "<p style='margin-bottom:5px'></p></div>";
}

/**
 * Displays setup trial option.
 *
 * @return void
 */
function mo_sf_sync_setup_trial() {
	$class = 'notice notice-info';
		echo "<div id='normal_trial_div_id' class='" . esc_attr( $class ) . "'> <p>" . '<b>Get started with Salesforce Sync today! Start your 10-day free trial right now.</b></p>';
	?>
		<form action="" method="post">
			<?php wp_nonce_field( 'mo_sf_sync_setup_trial' ); ?>
			<input type="hidden" name="option" value="mo_sf_sync_setup_trial">
			<input type="submit" name="mo_sf_sync_setup_trial" value="Click Here to get your free trial Now" class="button button-primary">
			<?php
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading tab name from GET URL. 
			if ( ( array_key_exists( 'tab', $_GET ) && 'licenseplan' !== $_GET['tab'] ) || ! array_key_exists( 'tab', $_GET ) ) {
				?>
				<input type="button" name="mo_sf_sync_setup_trial_dismiss" value="Dismiss" class="button-secondary um_secondary_dimiss" onclick="mo_sf_sync_dismiss_notification_bar()">
			<?php } ?>
		</form>
		<?php
		echo "<p style='margin-bottom:5px'></p></div>";
}
