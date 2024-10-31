<?php
/**
 * This file handles what to be displayed on the Troubleshoot submenu.
 *
 * @package object-data-sync-for-salesforce\Helper\view
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __FILE__ ) . '/view.php';

/**
 * Handles what to be displayed on the troubleshoot submenu.
 *
 * @return void
 */
function mo_sf_sync_display_troubleshoot() {
	//phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verification on tab navigation is not necessary
	if ( isset( $_GET['tab'] ) && 'auditlog' === $_GET['tab'] ) {
		//phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verification on tab navigation is not necessary
		$troubleshootsub_active_tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
	} else {
		$troubleshootsub_active_tab = 'troubleshoot';
	}
	mo_sf_sync_display_header();
	echo '<div id="mo-sf-sync-mo-container" class="mo-sf-sync-mo-container">';
	mo_sf_sync_display_troubleshoot_tabs( $troubleshootsub_active_tab );
			echo '<div class="mo-sf-row mo-sf-container-fluid">
				<div class="mo-sf-col-md-8">';
				mo_sf_sync_troubleshootsub_body( $troubleshootsub_active_tab );
					echo '		</div>';

					echo '<div class="mo-sf-col-md-4">';
	if ( 'auditlog' !== $troubleshootsub_active_tab ) {
		mo_sf_sync_support( $troubleshootsub_active_tab );
		mo_sf_sync_save_data_from_deletion();
	} else {
		mo_sf_sync_enable_audit_log_advanced_search();
		mo_sf_sync_audit_log_filters();
	}

		echo '		</div>';

		echo '</div>';
		mo_sf_sync_loader_class();
		echo '</div>';
}

/**
 * Displays the available tabs below header in the troubleshoot submenu.
 *
 * @param string $troubleshootsub_active_tab The active tab in the troubleshoot submenu.
 * @return void
 */
function mo_sf_sync_display_troubleshoot_tabs( $troubleshootsub_active_tab ) {
	?>
	<div id="mo_sf_sync_tab_view" class="mo-sf-sync-tab sf-sync-tab-background mo-sf-sync-tab-border">
		<ul id="mo_sf_sync_tab_view_ul" class="mo-sf-sync-tab-ul" role="toolbar">
		<li id="troubleshoot" class="mo-sf-sync-tab-li" role="presentation" title="troubleshoot">
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'troubleshoot' ) ); ?>">
					<div id="troubleshoot_div_id" class="mo-sf-sync-tab-li-div 
					<?php
					if ( 'troubleshoot' === $troubleshootsub_active_tab ) {
						echo 'mo-sf-sync-tab-li-div-active';
					}
					?>
					" aria-label="troubleshoot" title="Troubleshoot" role="button" tabindex="1">
						<div id="troubleshoot_icon" class="mo-sf-sync-tab-li-icon">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-wrench-adjustable-circle" viewBox="0 0 16 16">
								<path d="M12.496 8a4.491 4.491 0 0 1-1.703 3.526L9.497 8.5l2.959-1.11c.027.2.04.403.04.61Z"/>
								<path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0Zm-1 0a7 7 0 1 0-13.202 3.249l1.988-1.657a4.5 4.5 0 0 1 7.537-4.623L7.497 6.5l1 2.5 1.333 3.11c-.56.251-1.18.39-1.833.39a4.49 4.49 0 0 1-1.592-.29L4.747 14.2A7 7 0 0 0 15 8Zm-8.295.139a.25.25 0 0 0-.288-.376l-1.5.5.159.474.808-.27-.595.894a.25.25 0 0 0 .287.376l.808-.27-.595.894a.25.25 0 0 0 .287.376l1.5-.5-.159-.474-.808.27.596-.894a.25.25 0 0 0-.288-.376l-.808.27.596-.894Z"/>
							</svg>
						</div>
						<div id="troubleshoot_label" class="mo-sf-sync-tab-li-label">
							Troubleshoot
						</div>
					</div>
				</a>
			</li>

			<li id="auditlog" class="mo-sf-sync-tab-li" role="presentation" title="auditlog">
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'auditlog' ) ); ?>">
					<div id="auditlog_div_id" class="mo-sf-sync-tab-li-div 
					<?php
					if ( 'auditlog' === $troubleshootsub_active_tab ) {
						echo 'mo-sf-sync-tab-li-div-active';
					}
					?>
					" aria-label="auditlog" title="auditlog" role="button" tabindex="1">
						<div id="auditlog_icon" class="mo-sf-sync-tab-li-icon">
							<span class="dashicons dashicons-welcome-write-blog"></span>
						</div>
						<div id="auditlog_label" class="mo-sf-sync-tab-li-label">
							Audit Reports
						</div>
					</div>
				</a>
			</li>
		</ul>
	</div>
	<?php
}

/**
 * Displays and switches the body in the troubleshoot submenu as per the active tab.
 *
 * @param string $troubleshootsub_active_tab The active tab in the troubleshoot submenu.
 * @return void
 */
function mo_sf_sync_troubleshootsub_body( $troubleshootsub_active_tab ) {
	switch ( $troubleshootsub_active_tab ) {
		case 'troubleshoot':
			include_once dirname( __FILE__ ) . '/mo-sf-troubleshoot.php';
			mo_sf_sync_display_troubleshooting();
			mo_sf_sync_reset();
			break;
		case 'auditlog':
			include_once dirname( __FILE__ ) . '/audit-log.php';
			mo_sf_sync_fetch();
			break;

	}
}

/**
 * Calls the function to display Troubleshooting section when the active tab is Troubleshoot.
 *
 * @return void
 */
function mo_sf_sync_display_troubleshooting() {
	?>

	<div class="mo-sf-sync-tab-content">
			<div class="mo-sf-sync-tab-content-left-border">
				<?php
					mo_sf_sync_plugin_error_messages();
				?>
			</div>
	</div>
	<?php
}

/**
 * Displays the Troubleshooting Error Codes section.
 *
 * @return void
 */
function mo_sf_sync_plugin_error_messages() {
	?>
		<div class="mo-sf-sync-tab-content-tile mo-sf-mt-4">
		<h1 class="mo-sf-form-head">Troubleshooting</h1>
				<table class="mo-sf-sync-troubleshoot-table">
					<tr>
						<td class="title-text mo-sf-text-center"><b>Error Code</b></td>
						<td class="title-text mo-sf-text-center"><b>Reason</b></td>
					</tr>
					<tr>
						<td>
							<strong>MOSFSYNCERR001</strong>
						</td>
						<td class="mo-sf-sync-content-td">
							Invalid client credentials. Save valid credentials. One of the following is empty: Client-ID, Client-Secret, Redirect URI.
							<span style="color: red ;"> NOTE: Salesforce can take some time to reflect the changes of connected app, so you may also encounter this error when a new connected app is created/edited in Salesforce</span>
						</td>
					</tr>
					<tr>
						<td>
							<strong>MOSFSYNCERR002</strong>
						</td>
						<td class="mo-sf-sync-content-td">
							Salesforce returned an error message while authenticating client credentials. The error description is displayed in the message.
						</td>
					</tr>
					<tr>
						<td>
							<strong>MOSFSYNCERR003</strong>
						</td>
						<td class="mo-sf-sync-content-td">
							While authorizing client credentials something went wrong with and a WordPress error object was returned. The error description is displayed in the alert.
						</td>
					</tr>
					<tr>
						<td>
							<strong>MOSFSYNCERR004</strong>
						</td>
						<td class="mo-sf-sync-content-td">
							Unknown error: Something went wrong while authorization of client credentials.
						</td>
					</tr>
					<tr>
						<td>
							<strong>MOSFSYNCERR005</strong>
						</td>
						<td class="mo-sf-sync-content-td">
							Unknown error: Error description will be there in the error message.
						</td>
					</tr>
					<tr>
						<td>
							<strong>MOSFSYNCERR006</strong>
						</td>
						<td class="mo-sf-sync-content-td">
							While syncing an object salesforce returned an error, the error message is displayed in an alert.
						</td>
					</tr>
				</table>
				<br>
				<div class="mo-sf-note">
					<h3 class="mo-sf-text-center">Reach out to us at <a href="mailto:salesforcesupport@xecurify.com">salesforcesupport@xecurify.com</a> if you need any assistance.</h3>
				</div>
		</div>
	<?php
}

/**
 * Calls the function to display reset plugin configuration when the active tab is Troubleshoot.
 *
 * @return void
 */
function mo_sf_sync_reset() {
	?>

	<div class="mo-sf-sync-tab-content">

			<div class="mo-sf-sync-tab-content-left-border">
				<?php
					mo_sf_sync_reset_plugin_configuration();
				?>
			</div>
	</div>
	<?php
}

/**
 * Displays the Reset Plugin Configuration section.
 *
 * @return void
 */
function mo_sf_sync_reset_plugin_configuration() {
	?>
	<div class="mo-sf-sync-tab-content-tile mo-sf-mt-4">
	<h1 class="mo-sf-form-head">Reset Plugin Configuration</h1>
			<h3 class="mo-sf-mt-5">Using the "Reset Plugin Configuration" button will have the following consequences :-
			</h3>
			<h4 >
				<li>Whole configuration of plugin will be deleted.</li><br>
				<li style="text-align: justify;">Data like Mapping labels, Basic App Configuration(like client id and client secret) will be deleted and provisioning data(like <br>&nbsp;&nbsp;&nbsp;&nbsp; Automatic User Sync) will be reset.</li> <br>
				<li>User will need to reconfigure the plugin again after the reset.</li> 
			</h4>
			<br>
			<form id="mo_sf_sync_reset" method="post" action="">
				<?php
				wp_nonce_field( 'mo_sf_sync_reset' );
				?>
				<input type="hidden" name="option" value="mo_sf_sync_reset">
				<input type="submit" name="clearsearch" value="Reset Plugin Configuration" class="mo-sf-btn-cstm" onclick="return confirm('This will delete all the plugin configurations. Do you really want to reset the plugin ?')">
			</form>
	</div>
	<?php
}
