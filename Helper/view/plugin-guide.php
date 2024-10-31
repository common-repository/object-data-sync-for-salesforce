<?php
/**
 * This file provides the complete setup guide for the plugin.
 *
 * @package object-data-sync-for-salesforce\Helper\view
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __FILE__ ) . '/view.php';

/**
 * Function that displays the configuration guide and the feedback support form in the plugin guide page.
 *
 * @return void
 */
function mo_sf_sync_display_plugin_guide() {
		mo_sf_sync_display_header();
	?>
	<div style="display: flex;">
		<div class="mo-sf-sync-tab-content">
			<div class="mo-sf-sync-tab-content-left-border"  >
			<?php
				mo_sf_sync_plugin_guide();
			?>
			</div>
		</div>
		<div class="mo-sf-col-md-4">
			<?php mo_sf_sync_support(); ?>
		</div>
	</div>
	<?php
}

/**
 * Function that displays the Plugin Guide.
 *
 * @return void
 */
function mo_sf_sync_plugin_guide() {
	?>
	<div class="mo-sf-sync-tab-content-tile mo-sf-mt-4">
		<div class="mo-sf-sync-tab-content-tile-content mo-sf-sync-guide-text">
			<h1 class="mo-sf-form-head">How to configure the plugin
				<a title="Visit online guide" style="text-decoration:none" target="_blank" href="https://plugins.miniorange.com/salesforce-wordpress-object-sync">
					<span class="dashicons dashicons-admin-links"></span>
				</a>
			</h1>
			<h2 class="mo-sf-form-head mo-sf-form-head-bar mo-sf-mt-5">1. Configure Salesforce App</h2>
			<h4>To connect to Salesforce, there are two options available in the plugin as shown below:</h4>
			<div id="mo_sf_sync_app_config_access_desc" class="mo_sf_sync_help_desc">
				<h3 class="mo-sf-form-head mo-sf-form-head-bar">Automatic (Pre-connected App)</h3>
				<h4>
					<span >
					It can also be called as <b>One Click Authorization</b> mechanism.
					In this method of authorization you don't need client credentials (like Consumer Key or Consumer Secret)
					to connect to Salesforce.
					</span> 
			</div>
			</br>
			<div id="mo_sf_sync_app_config_access_desc" class="mo_sf_sync_help_desc">
				<h3 class="mo-sf-form-head mo-sf-form-head-bar">Manual (Custom App)</h3>
				<h4>
					<span >
					In this method of authorization you require client credentials (like Consumer Key or Consumer Secret)
					to your Salesforce application.
					</span> 
			</div>
			</br>

			<div id="sf_sync_preappconnect_guide">
				<h3 class="mo-sf-form-head mo-sf-form-head-bar mo-sf-mt-5">Configuration Using Pre-connected App</h3> 
				<ul class="mo-sf-sync-guide-ul">
					<li>
						Click on <b>Get Started</b> button of <b>Automatic (Pre-Integrated App) section</b>.
					</li>
					<li>
						Select your <b> Salesforce Environment </b> to determine salesforce URL. If you have a custom URL select the last option
					</li>
					<li>
						Now, click on <b>Connect to Salesforce</b> button to establish the connection with salesforce.
					</li>
					<img width="95%" class="mo-sf-sync-guide-image" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../../images/guide/pre-connected-app-connect.png' ); ?>" alt="Select Application Type" />
					<li>
						You will be prompted to enter your salesforce credentials, once entered, you will be asked certain permissions, please click on <b>Allow</b> to connect to Salesforce.
					</li>
					<img width="95%" class="mo-sf-sync-guide-image" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../../images/guide/miniorange-allow-access.png' ); ?>" alt="Select Application Type" />
					<li>
						After clicking <b>Allow</b> you will be successfully connected to Salesforce.
					</li>
					<img width="95%" class="mo-sf-sync-guide-image" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../../images/guide/authorization-successfull-window.png' ); ?>" alt="Select Application Type" />
					<li>
						Now, to prevent refresh token from expiring go to your Salesforce account and navigate to <b>Setup » Connected Apps OAuth Usage</b> and click <b>Install</b>.
					</li>
					<img width="95%" class="mo-sf-sync-guide-image" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../../images/guide/install-miniorange-app-salesforce.png' ); ?>" alt="Select Application Type" />

					<div id="mo_sf_sync_app_config_access_desc" class="mo_sf_sync_help_desc">
					<h3>Refresh Token Policy for Automatic (Pre-connected App)</h3>
					<h4>
						<span style="content: justify ;">
						By default miniOrange is installed with an indefinite refresh token.
						However, we have seen some Salesforce apps with different security policies that cause the miniOrange app to get installed with a temporary refresh token.
						That means that when the refresh token expires, you’ll need to click the Reauthorize With Salesforce link again.
						To fix this, head to  Settings » Connected Apps » Manage Connected Apps » miniOrange and make sure the Refresh token is set to be Valid Until Revoked.
						</span>

						<img width="95%" class="mo-sf-sync-guide-image" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../../images/guide/check-refresh-token-policy.png' ); ?>" alt="Configure Salesforce for Object sync - Home Screen" />
					</div>
					<li>
						<span style="color:red ;">NOTE: </span>If you have established a connection using pre-connected app you can directly jump to <a href="#plugin_guide_step_2"><b>STEP 2</b></a>
					</li>
				</ul>
			</div>
			<div id="sf_sync_manualappconnect_guide">
			<h3 class="mo-sf-form-head mo-sf-form-head-bar mo-sf-mt-5">Configuration Using Manual/Custom App</h3> 
				<h4>Follow the steps below to connect to salesforce using manual/custom app.</h4>

				<ul class="mo-sf-sync-guide-ul">
					<li>
						Go to Salesforce login page and login as an <b>Administrator</b>.
					</li>
					<li>
						Navigate to <b>SETUP</b> tab.
					</li>
					<img width="95%" class="mo-sf-sync-guide-image" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../../images/guide/navigate-to-setup-tab.png' ); ?>" alt="Configure Salesforce for Object sync - AppManager">
					<li>
						Under the <b>PLATFORM TOOLS</b> section, navigate to the <b>Apps</b> in the left menu. Select the <b>App Manager</b> option.
					</li>
					<img width="95%" class="mo-sf-sync-guide-image" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../../images/guide/Salesforce-sync-appmanager.webp' ); ?>" alt="Configure Salesforce for Object sync - AppManager">
					<li>
						In the same window, head to the top right corner and select the option <b>New Connected App</b> to create new application.
					</li>
					<img width="95%" class="mo-sf-sync-guide-image" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../../images/guide/Salesforce-sync-newapp.webp' ); ?>" alt="Configure Salesforce for Object sync - NewApp">
					<li>
						Fill the required information in below boxes.
					</li>
					<li>
						Under the <b>API (Enable OAuth Settings)</b>, check the option of <b>Enable OAuth settings</b>.
					</li>
					<img width="95%" class="mo-sf-sync-guide-image" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../../images/guide/Salesforce-sync-ouathsetting.webp' ); ?>" alt="Configure Salesforce for Object sync - OauthSetting">
					<table class="mo-sf-sync-guide-table">
						<tbody>
							<tr>
								<td>Connected App Name</td>
								<td>Enter any name of your choice.
									<hr>
									<b>NOTE:</b> The Connected App Name can only contain underscores and alphanumeric characters. It must be unique, begin with a letter, not include spaces, not end with an underscore, and not contain two consecutive underscores.</b>
								</td>
							</tr>
							<tr>
								<td>API Name</td>
								<td>Enter any name of your choice. By default, it just copies the <b>Connected App Name</b>
								<hr>
								<b>NOTE:</b> The API Name can only contain underscores and alphanumeric characters. It must be unique, begin with a letter, not include spaces, not end with an underscore, and not contain two consecutive underscores.</b>
								</td>
							</tr>
							<tr>
								<td>Contact Email</td>
								<td>Enter any email of your choice</td>
							</tr>
						</tbody>
					</table>
					<li>
						Inside the <b>Callback URL</b> block, enter your <b>WordPress Site URL</b>.
					</li>
					<label style="font-size:small"><b>Note</b>: <b>Make sure that URL must be present in https:// format</b>.</label>
					<li>
						Under the <b>Available OAuth Scopes</b> users have to select <b>Manage user data via APIs (api)</b> and <b>Perform requests at any time (refresh_token, offline_access)</b> options then click on <b>SAVE</b>.
					</li>
					<img width="95%" class="mo-sf-sync-guide-image" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../../images/guide/manage-api.webp' ); ?>" alt="Configure Salesforce for Object sync - Callbackurl">
					<li>
						Now the user will be prompted with the confirmation page, click on <b>Continue</b> and move on next page.
					</li>
					<img width="95%" class="mo-sf-sync-guide-image" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../../images/guide/Salesforce-sync-continue.webp' ); ?>" alt="Configure Salesforce for Object sync - Continue">
					<li>
						After this user will be able to view the app they configured, click on <b>Manage Consumer Details</b>, salesforce will send an OTP to your admin email.
					</li>
					<img width="95%" class="mo-sf-sync-guide-image" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../../images/guide/get-consumer-details-sf.png' ); ?>" alt="Configure Salesforce for Object sync - Consumerkey">
					<li>
						Enter the OTP received, and you will get your application's <b>Consumer Key and Consumer Secret</b>. You will need it while configuring the plugin.
					</li>
					<img width="95%" class="mo-sf-sync-guide-image" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../../images/guide/consumer-details-sf.png' ); ?>" alt="Configure Salesforce for Object sync - Consumerkey">

					<li>
						You have successfully configured your Salesforce application.
					</li>
				</ul>
				<h3 class="mo-sf-form-head mo-sf-form-head-bar mo-sf-mt-5">Configure WP Salesforce Object Sync plugin (Only for Manual Application Type)</h3>
				<ul class="mo-sf-sync-guide-ul">
					<li>
						Navigate to the WP object Salesforce Sync plugin.
					</li>
					<li>
						Under the tab <b>Manage Application</b>, paste the <b>Consumer Key and Consumer Secret</b> from the Salesforce App.
					</li>
					<table class="mo-sf-sync-guide-table">
						<tbody>
							<tr>
								<td>Select Environment</td>
								<td>Select your <b>Salesforce Environment</b> to determine salesforce URL. If you have a custom URL select the last option
									<hr>
									<b>NOTE:</b> If you use custom url make sure it ends in <b><i>.salesforce.com</i></b>
								</td>
							</tr>
							<tr>
								<td>Authorization URL</td>
								<td>If you select <b>Custom URL</b> option in the previous option you will need to provide the link here.</td>
							</tr>
							<tr>
								<td>Application ID</td>
								<td>Paste the <b>Consumer Key</b> from Salesforce App.</td>
							</tr>
							<tr>
								<td>Client Secrets</td>
								<td>Paste the <b>Consumer Secret</b> from the Salesforce App. </td>
							</tr>
							<tr>
								<td>Redirect URI</td>
								<td>Enter the <strong>Callback URL</strong>from the Salesforce App.</td>
							</tr>
							<tr>
								<td>Scopes</td>
								<td><strong>api refresh_token</strong> </td>
							</tr>
						</tbody>
					</table>
					<img width="95%" class="mo-sf-sync-guide-image" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../../images/guide/manual-app-connect.png' ); ?>" alt="Configure Salesforce - WordPress Object Sync plugin- Manageapp">
					<li>
						Click on <b>Save & Authorize</b> to establish the connection with salesforce.
					</li>
				</ul>
			</div>
			<h2 class="mo-sf-form-head mo-sf-form-head-bar mo-sf-mt-5" id="plugin_guide_step_2">2. Object Mapping</h2>
			<ul class="mo-sf-sync-guide-ul">
				<li>
					Under the <b>Object Mapping tab,</b> you can click on the <b>Add Object Mapping</b> button to add a new Object mapping.
				</li>
				<img width="95%" class="mo-sf-sync-guide-image" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../../images/guide/add-object-mapping-plug.png' ); ?>" alt="WP object Salesforce Sync- Object Mapping">
			</ul>
			<ul class="mo-sf-sync-guide-ul">
				<li>
					In the first section titled <b>Select WordPress Object</b> select the WordPress object to be synced from the dropdown.
				</li>
				<img width="95%" class="mo-sf-sync-guide-image" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../../images/guide/select-wp-object.png' ); ?>" alt="WP object Salesforce Sync- Field Mapping">
			</ul>
			<ul class="mo-sf-sync-guide-ul">
				<li>
					In the second section titled <b>Select Sync Direction</b> select the direction in which you want to enable the sync.
				</li>
				<img width="95%" class="mo-sf-sync-guide-image" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../../images/guide/select-sync-direction.png' ); ?>" alt="WP object Salesforce Sync- Field Mapping">
			</ul>
			<ul class="mo-sf-sync-guide-ul">
				<li>
					In the third section titled <b>Select Salesforce Object</b> select the Salesforce object to be synced from the dropdown.
				</li>
				<img width="95%" class="mo-sf-sync-guide-image" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../../images/guide/select-sf-object.png' ); ?>" alt="WP object Salesforce Sync- Field Mapping">
			</ul>
			<ul class="mo-sf-sync-guide-ul">
				<li>
					In the fourth section titled <b>Map WordPress Fields to Salesforce fields</b> we will be mapping the fields of Salesforce object with WordPress.
				</li>
				<li>
					You can click the <button class="mo-sf-btn-cstm" style="cursor: none;">Add Salesforce Field</button> button to add a new mapping record.
				</li>
				<li>
					To configure the mapping select the Salesforce field in the left dropdown and select the WordPress attribute in the right dropdown. You can add as many fields as you require.
				</li>
				<li>
					If you don't require a field map you can simply press the <span class="dashicons dashicons-trash"></span> button next to it to delete it.
				</li>
				<img width="95%" class="mo-sf-sync-guide-image" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../../images/guide/object-attribute-mapping.png' ); ?>" alt="WP object Salesforce Sync- Field Mapping">
			</ul>
			<ul class="mo-sf-sync-guide-ul">
				<li>Finally, you can click the <b>Save Object Mapping</b> button available in this section to save the entire mapping.</li>
			</ul>

			<div>You have successfully configured WordPress (WP) Salesforce Object Sync.</div><br>

			<div class="mo-sf-note">
				<h3 class="mo-sf-text-center">Reach out to us at <a href="mailto:salesforcesupport@xecurify.com">salesforcesupport@xecurify.com</a> if you need any assistance or have any additional requirements.</h3>
			</div>
		</div>
	</div>
	<?php
}
