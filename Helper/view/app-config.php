<?php
/**
 * This file is responsible for the display of connected application Types.
 *
 * @package object-data-sync-for-salesforce\Helper\view
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MoSfSyncSalesforce\Services\Utils;
use MoSfSyncSalesforce\Handler\Authorization_Handler;
use MoSfSyncSalesforce\Helper\Plugin_Constants;
use MoSfSyncSalesforce\API\Salesforce;
/**
 * Handles the feedback message and calls the display function for connected application types.
 *
 * @return void
 */
function mo_sf_sync_display_app_config() {  ?>
		<div class="mo-sf-sync-tab-content">

		<?php
			Utils::mo_sf_sync_show_feedback_message();
			mo_sf_sync_display_client_config()
		?>

		</div>
	<?php
}

/**
 * Displays the connected application types information.
 */
function mo_sf_sync_display_client_config() {
	$app                 = maybe_unserialize( Utils::mo_sf_sync_get_settings( Plugin_Constants::CONFIG_OBJECT ) );
	$client_id           = isset( $app[ Plugin_Constants::CLIENT_ID ] ) ? $app[ Plugin_Constants::CLIENT_ID ] : '';
	$client_secret       = isset( $app[ Plugin_Constants::CLIENT_SECRET ] ) ? $app[ Plugin_Constants::CLIENT_SECRET ] : '';
	$redirect_uri        = isset( $app[ Plugin_Constants::REDIRECT_URI ] ) ? $app[ Plugin_Constants::REDIRECT_URI ] : site_url();
	$app_type            = isset( $app['app_type'] ) ? $app['app_type'] : '';
	$env                 = isset( $app[ Plugin_Constants::ENVIRONMENT ] ) ? $app[ Plugin_Constants::ENVIRONMENT ] : 'test';
	$custom_link         = isset( $app[ Plugin_Constants::ENVIRONMENT_LINK ] ) ? $app[ Plugin_Constants::ENVIRONMENT_LINK ] : 'https://test.salesforce.com';
	$response_object     = maybe_unserialize( Utils::mo_sf_sync_get_settings( Plugin_Constants::SF_RESPONSE_OBJECT ) );
	$pardot_sync_status  = isset( $app[ Plugin_Constants::IS_PARDOT_ENABLED ] ) ? $app[ Plugin_Constants::IS_PARDOT_ENABLED ] : '';
	$pardot_business_uid = isset( $app[ Plugin_Constants::PARDOT_BUSSINESSUID ] ) ? $app[ Plugin_Constants::PARDOT_BUSSINESSUID ] : '';
	$pardot_link         = isset( $app[ Plugin_Constants::PARDOT_DOMAIN_LINK ] ) ? $app[ Plugin_Constants::PARDOT_DOMAIN_LINK ] : '';
	$connection_type     = get_option( Plugin_Constants::CONNECTION_TYPE );
	if ( ! empty( $client_id ) && ! empty( $client_secret ) && ! isset( $app[ Plugin_Constants::ENVIRONMENT ] ) ) {
		$app = Utils::mo_sf_sync_update_app_config( $app );
	}

	?>

	<form id="app_config_save_client_configuration" method="post" target="_self">
		<input type="hidden" id="mo_sf_sync_home_url" value="<?php echo esc_url( home_url() ); ?>">
		<input type="hidden" name="option" value="mo_sf_sync_client_config_option">
		<input type="hidden" name="tab" value="app_config">
		<input type="hidden" id="mo_sf_sync_app_type" value="<?php echo esc_attr( $app_type ); ?>"> 
		<input type="hidden" id="resp_obj" value="<?php echo wp_json_encode( $response_object ); ?>">
		<input type="hidden" name="nonce_" value="<?php echo esc_attr( wp_create_nonce( 'mo_sf_sync_client_config_option' ) ); ?>">
		<div class="mo-sf-sync-tab-content-tile mo-sf-mt-4">
			<div class="mo-sf-sync-tab-content-tile-content">
				<h1 class="mo-sf-form-head">Configure Salesforce Connected Application</h1>
				<div id="mo_sf_sync_app_config_access_desc" class="mo_sf_sync_help_desc">
					<h4>
						<span>
						Configure following settings to register your Salesforce Application here.
						You can check your settings correctly configured or not using <b> Authorize </b> button.</br></br>
						<b><a class="mo-sf-sync-guide-button" target="blank" href="https://plugins.miniorange.com/salesforce-wordpress-object-sync">Click Here</a></b>
						to open an extensive<b> step by step guide </b> to configure the following settings.
						</span>
				</div>
			<div class="mo-sf-row">
				<div class="mo-sf-col-md-6" style="position: relative;left: 15px;">
					<h2 class="mo-sf-form-head mo-sf-form-head-bar mo-sf-mt-6" >Select Application Type</h2>
				</div>
				<div class="mo-sf-col-md-6 mo-sf-dflex mo-sf-justify-content-end">
					<input type="button" id="back_to_manual" class="mo-sf-btn-cstm mo-sf-mt-0 Manually" value="Configure App Manually" onclick="mo_sf_sync_show_manual_configuration();" >
					<input type="button" id="back_to_automatic" class="mo-sf-btn-cstm mo-sf-mt-0 Automatically" value="Configure App Automatically" onclick="mo_sf_sync_show_automatic_configuration();" hidden>
				</div>
			</div>
				<div id="mo_sf_sync_app_config_access_desc" class="automatic_display config_selection mo-sf-sync-guide-button"hidden >
					<h2>
						<span style="color:white">
							Manual (Custom App)
						</span>
						<div class="tooltip">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6" style="width: 1rem;">
								<path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
							</svg>
							<span class="tooltiptext" style="font-size: 0.9rem;">
							Selecting this application type, you are required to enter the client credentials <br>(client id and client secret) 
								by creating an application in your salesforce account.
							</span>
						</div>

				</div>
				<div id="mo_sf_sync_app_config_access_desc" class="manual_display config_selection mo-sf-sync-guide-button" >
					<h2>
						<span style="color:white">
							Automatic (Pre-integrated App)
						</span>
						<div class="tooltip">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6" style="width: 1rem;">
								<path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
							</svg>
							<span class="tooltiptext" style="font-size: 0.9rem;">
							Selecting this application type, allows you to connect to salesforce without <br>entering client credentials (client id and client secret). 
								No need to create<br>application in your Salesforce account.
							</span>
						</div>						
				</div>
			</br>

			<div id="configuration_body"> 

				<table class="mo-sf-sync-tab-content-app-config-table mo-sf-sync-config-table">
					<tbody>
						<tr>
							<td class="left-div"><span style="color: black;">Select Environment</span> <span class="mo_sf_required_span">*</span></td>
							<td class="right-div">
								<input type="radio" checked id="test" value="test" name="env_select">
								<label for="test" class="mo-sf-mr-5"><b>Sandbox / Testing</b></label>
								<input type="radio" 
								<?php
								if ( 'prod' === $env ) {
									echo 'checked';}
								?>
								id="prod" value="prod" name="env_select">
								<label for="prod" class="mo-sf-mr-5"><b>Production</b></label>
								<input type="radio" 
								<?php
								if ( 'custom' === $env ) {
									echo 'checked';}
								?>
								id="custom" value="custom" name="env_select">
								<label for="custom"><b>Custom URL</b></label>
							</td>
						</tr>
						<tr id="auth_uri">
							<td class="left-div" ><span style="color: black;">Authorization URI</span> <span class="mo_sf_required_span">*</span></td>
							<td class="right-div">
								<div class="env_url">
									<input type="url" pattern=".*\.salesforce.com$"  title="URL must end with salesforce.com" value="<?php echo esc_attr( $custom_link ); ?>" name="env_link" onchange="mo_sf_sync_assign_url_for_buid()"/>
								</div>
							</td>
						</tr>
						<tr id="pardot_sync">
							<td class="left-div"><span style="color: black;">Enable Pardot Sync</span>
							<div class="tooltip" style="color:black">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6" style="width: 1rem;">
									<path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
								</svg>
								<span class="tooltiptext tooltip-line-height" style="font-size: 0.9rem;">
									Enable this toggle only if you want to embed pardot forms and dynamic content<br>on your WordPress site.
								</span>
							</div>
							</td>
							<td class="right-div">
								<div class="pardot_sync_enable">
									<label class="mo-sf-sync-switch">
										<input type="checkbox" name="is_pardot_int_enabled" <?php checked( $pardot_sync_status, 'on', true ); ?> onchange="mo_sf_sync_show_business_uid(event)"/>
										<span class="mo-sf-sync-slider round"></span>
									</label>
								</div>
							</td>
						</tr>
						<tr id="pardot_business" 
						<?php
						if ( empty( $pardot_business_uid ) ) {
							echo 'hidden';
						} else {
							echo '';
						}
						?>
						>
							<td class="left-div"><span style="color: black;">Business Unit ID </span><span class="mo_sf_required_span">*</span>
							<div class="tooltip" style="color:black">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6" style="width: 1rem;">
									<path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
								</svg>
								<span class="tooltiptext tooltip-line-height" style="font-size: 0.9rem;">
									This is a required field if you have enabled pardot sync toggle. It will be used to embed pardot<br>forms and dynamic content on your WordPress site.
								</span>
							</div>
							</td>
							<td class="right-div">
								<div class="mo-sf-sync-pardot-dflex">
									<div class="mo-sf-col-md-5">
										<input type="text" id="pardot_business_uid" name = "pardot_business_uid" value="<?php echo esc_attr( $pardot_business_uid ); ?>"/>
									</div>
									<div>
										<a href="<?php echo esc_attr( $custom_link ); ?>./lightning/setup/PardotAccountSetup/home" target="_blank" class = "mo-sf-sync-pid-button">Get Pardot Business Unit ID</a>
									</div>
								</div>
							</td>
						</tr>

						<tr id="pardot_env" 
						<?php
						if ( empty( $pardot_business_uid ) ) {
							echo 'hidden';
						} else {
							echo '';
						}
						?>
						>
							<td class="left-div"><span style="color: black;">Pardot Environment: </span></td>
							<td class="right-div">
								<select name="pardot_env_link" id="pardot_env_link">
									<option value="demo-pardot" 
									<?php
									if ( 'demo-pardot' === $pardot_link ) {
										echo 'selected';
									} else {
										echo '';
									}
									?>
									>Demo/Sandbox Environment(pi.demo.pardot)</option>
									<option value="live-pardot" 
									<?php
									if ( 'live-pardot' === $pardot_link ) {
										echo 'selected';
									} else {
										echo '';
									}
									?>
									>Live/Production Environment(pi.pardot)</option>
								</select>
							</td>
						</tr>
					</tbody>
					</br>
					<tbody id="auto-config">
						<tr>
							<td class="left-div"><span style="color: black;">Salesforce Connection</span></td>
							<td class="right-div" id="automatic-app-connect">
								<input type="submit" class="mo-sf-btn-cstm mo-sf-mt-4" value="Save Selected Environment">
								<input type="button" 
								<?php
								if ( 'preconnected' === $app_type ) {
									echo '';
								} else {
									echo 'disabled';
								}
								?>
								id="preconn_conn_to_sf" class="mo-sf-btn-cstm mo-sf-mt-4" value="Connect to Salesforce" onclick="mo_sf_sync_open_window_for_authorization('preconnected')">
								<div class="loader-placeholder" style="display:none;"></div>
								<button type="button" class="button-8" style="display:
								<?php
								if ( isset( $connection_type ) && 'automatic' === $connection_type ) {
									echo 'true';
								} else {
									echo 'none';
								}
								?>
								" ><span class="dashicons dashicons-yes-alt"></span>&nbsp; Connected To Salesforce with Pre-connected App</button>
							</td>
						</tr>
					</tbody>
					<tbody id="manual-config" hidden>
						<tr>
							<td class="left-div"><span style="color: black;">Application ID</span> <span class="mo_sf_required_span">*</span></td>
							<td class="right-div"><input  type="text" id="Application_ID" name="client_id" value="<?php echo esc_attr( $client_id ); ?>"></td>
						</tr>
						<tr>
							<?php
							$decrypt = Authorization_Handler::mo_sf_sync_decrypt_data( $client_secret, hash( 'sha256', $client_id ) );

							if ( empty( $decrypt ) ) {
								$client_secret_decrypted = Authorization_Handler::mo_sf_sync_decrypt_data( $client_secret, hash( 'sha256', get_site_url() ) );
								?>
								<td class="left-div"><span style="color: black;">Client Secret</span> <span class="mo_sf_required_span">*</span></td>
								<td class="right-div"><input  type="password" name="client_secret" value="<?php echo esc_attr( $client_secret_decrypted ); ?>"></td>
								<?php
								if ( ! empty( $client_secret_decrypted ) ) {
									$config_object                  = get_option( 'mo_sf_sync_config' );
									$client_secret_encrypted        = Authorization_Handler::mo_sf_sync_encrypt_data( $client_secret, hash( 'sha256', $config_object['client_id'] ) );
									$config_object['client_secret'] = $client_secret_encrypted;
									update_option( 'mo_sf_sync_config', $config_object );
								}
							} else {
								?>
								<td class="left-div" ><span style="color: black;">Client Secret</span> <span class="mo_sf_required_span">*</span></td>
								<td class="right-div"><input  type="password" name="client_secret" value="<?php echo esc_attr( Authorization_Handler::mo_sf_sync_decrypt_data( $client_secret, hash( 'sha256', $client_id ) ) ); ?>"></td>
								<?php
							}
							?>
						</tr>
						<tr>
							<td class="left-div"><span style="color: black;">Redirect URI</span> <span class="mo_sf_required_span">*</span></td>
							<td class="right-div"><input  type="url" name="redirect_uri" value="<?php echo esc_attr( $redirect_uri ); ?>"></td>
						</tr>
						<tr>
							<td class="left-div"><span style="color: black;">Scopes</span></td>
							<td class="right-div"><input  type="text" name="spo_id" readonly value="api refresh_token"></td>
						</tr>
					</tbody>
					<tbody>
						<tr>
							<td></td>
							<td id="manual-app-connect" hidden >
								<input type="submit" id="save_config" class="mo-sf-btn-cstm mo-sf-mt-4" value="Save Configuration">
								<input type="button" 
								<?php
								if ( 'manual' === $app_type ) {
									echo '';
								} else {
									echo 'disabled';
								}
								?>
								id="authorize_config" class="mo-sf-btn-cstm mo-sf-mt-4"  value="Connect To Salesforce" onclick="mo_sf_sync_open_window_for_authorization('manual')">
								<div class="loader-placeholder" style="display:none;"></div>
								<button type="button" class="button-8" style="display:
								<?php
								if ( ! empty( $response_object ) && ! empty( $connection_type ) && 'manual' === $connection_type ) {
									echo 'true';
								} else {
									echo 'none';
								}
								?>
								" ><span class="dashicons dashicons-yes-alt"></span>&nbsp; Connected To Salesforce with Manual App</button>
							</td>
					</tbody>
				</table>
			</div>
			</div>
		</div>
	</form>
	<?php
}
