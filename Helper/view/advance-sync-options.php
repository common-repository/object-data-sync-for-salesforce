<?php
/**
 * This file handles what to be displayed on the Advanced Sync Options tab.
 *
 * @package object-data-sync-for-salesforce\Helper\view
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MoSfSyncSalesforce\Services\Utils;
use MoSfSyncSalesforce\Helper\Plugin_Constants;
use MoSfSyncSalesforce\Services\DB_Utils;

/**
 * Displays the content of the whole Advanced Sync Options tab.
 *
 * @return void
 */
function mo_sf_sync_display_advanced_sync_options() {
	?>
	<div class="mo-sf-sync-tab-content">

		<?php
		Utils::mo_sf_sync_show_message();
		Utils::mo_sf_sync_show_feedback_message();
		?>

		<div class="mo-sf-sync-tab-content-left-border">

			<?php
			mo_sf_sync_wp_to_sf_tile();
			mo_sf_sync_sf_to_wp_tile();
			?>

	</div>
	</div>
	<?php
}

/**
 * Displays the section of advance sync options for Sync from WordPress to Salesforce.
 *
 * @return void
 */
function mo_sf_sync_wp_to_sf_tile() {
	$config                = Utils::mo_sf_sync_get_settings( Plugin_Constants::PROVISION_OBJECT );
	$automatic_user_update = isset( $config[ Plugin_Constants::AUTO_USER_UPDATE ] ) ? $config[ Plugin_Constants::AUTO_USER_UPDATE ] : '';
	?>
	<form method="post" target="_self">
		<input type="hidden" name="option" value="mo_sf_sync_app_provisioning_config_option">
		<input type="hidden" name="tab" value="app_config">
		<input type="hidden" name="nonce_" value="<?php echo esc_html( wp_create_nonce( 'mo_sf_sync_app_provisioning_config_option' ) ); ?>">
		<div class="mo-sf-sync-tab-content-tile mo-sf-mt-4">
			<div class="mo-sf-sync-tab-content-tile-content">
				<h1 class="mo-sf-form-head">Sync from WordPress to Salesforce</h1>
				<h2 class="mo-sf-form-head mo-sf-form-head-bar mo-sf-mt-10">Realtime Sync</h2>

				<div class="mo-sf-dflex mo-sf-ml-1">
					<div class="mo-sf-note">
						<h4>
							Real time sync allows you to sync WordPress users/posts with Salesforce when they are created/updated in WordPress. <br>
							Note: User/Post will be updated if its record exists in the salesforce object otherwise it will create a record. Please make sure that the sync direction in mapping is set to be WordPress to Salesforce for this to Work.
						</h4>
					</div>
				</div>
				<div class="mo-sf-dflex-realtime-sync-toggle mo-sf-ml-1 mo-sf-mt-4">
					<div class="mo-sf-col-md-6">
						<h2>Enable Real Time Sync</h2>
					</div>
					<div class="mo-sf-col-md-6">
						<label class="mo-sf-sync-switch">
							<input type="checkbox" name="automatic_user_update" <?php checked( $automatic_user_update, 'on', true ); ?>>
							<span class="mo-sf-sync-slider round"></span>
						</label>
					</div>
				</div>
				<div class="mo-sf-dflex mo-sf-ml-1">
					<div class="mo-sf-col-md-6">
						<div class="mo-sf-mt-4">
							<input type="submit" class="mo-sf-btn-cstm" value="Save Configuration">
							<div style="position: relative; left: 140px;bottom :40px" class="loader-placeholder"></div>
						</div>
					</div>
				</div>
			</div>
		</form>
		</br>
		<div class="mo-sf-sync-tab-content-tile-content mo-sf-prem-info">
			<div class="mo-sf-prem-lock">
				<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../../images/lock.png' ); ?>" alt="">
				<p class="mo-sf-prem-text">Available in premium plugin. <a href="?page=mo_sf_sync&tab=licenseplan" class="mo-sf-text-warning">Click here to upgrade</a></p>
			</div>
			<h2 class="mo-sf-form-head mo-sf-form-head-bar">Export WordPress data to Salesforce</h2>

			<div class="mo-sf-note">
				<span> One time push for all your WordPress data to Salesforce. Enables you to sync all WordPress data into Salesforce to make sure that your existing data is maintained and tracked in Salesforce. You can configure multiple object mappings and configure which ones to be pushed to Salesforce.</span>
			</div>
			<tr>
				<td>
					<div class="mo-sf-mt-4">
						<input type="button" disabled class="mo-sf-btn-cstm" value="Push Data to Salesforce">
						<div class="loader-placeholder"></div>
					</div>
				</td>
			</tr>
		</div>
		<br>
		<div class="mo-sf-sync-tab-content-tile-content mo-sf-prem-info">
				<div class="mo-sf-prem-lock">
					<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../../images/lock.png' ); ?>" alt="">
					<p class="mo-sf-prem-text">Available in premium plugin. <a href="?page=mo_sf_sync&tab=licenseplan" class="mo-sf-text-warning">Click here to upgrade</a></p>
				</div>
				<h2 class="mo-sf-form-head mo-sf-form-head-bar">Scheduled Sync </h2>

				<div class="mo-sf-note">
					<span> User/Post details will be synced to Salesforce from WordPress based at the time interval specified by you.</span>
				</div>
				<div class="mo-sf-dflex mo-sf-ml-1 mo-sf-mt-6">
					<div class="mo-sf-col-md-6">
						<h2>Enable Scheduled Sync</h2>
					</div>
					<div class="mo-sf-col-md-6 mo-sf-mt-3">
						<label class="mo-sf-sync-switch">
							<input type="checkbox" name="scheduled_user_sync" id="scheduled_user_sync" disabled>
							<span class="mo-sf-sync-slider round"></span>
						</label>
					</div>
				</div>
				<div id="scheduled_sync_configuration" name="scheduled_sync_configuration">
					<div class="mo-sf-dflex mo-sf-ml-1 mo-sf-mt-8">
						<div class="mo-sf-col-md-6">
							<h2>Sync Duration</h2>
						</div>
						<div class="mo-sf-col-md-6 mo-sf-mt-3">

							<select name="sync_interval" disabled>
								<option value="twicedaily">Twicedaily</option>
								<option value="daily">Daily</option>
							</select>

						</div>
					</div>
					<div class="mo-sf-dflex mo-sf-ml-1 mo-sf-mt-8">
						<div class="mo-sf-col-md-6">
							<h2>Last Synced on</h2>
						</div>
						<div class="mo-sf-col-md-6 mo-sf-mt-3">
							<p>2022-03-14 11.34.30</p>

						</div>
					</div>
				</div>
				<tr>
					<td>
						<div class="mo-sf-mt-4">
							<input type="button" disabled class="mo-sf-btn-cstm" value="Save Configuration">
						</div>
					</td>
				</tr>
			</div>
	</div>
			<?php
}

/**
 * Displays the section of advance sync options for Sync from Salesforce to WordPress.
 *
 * @return void
 */
function mo_sf_sync_sf_to_wp_tile() {
	?>
	<div class="mo-sf-sync-tab-content-tile mo-sf-mt-4">
		<h1 class="mo-sf-form-head">Sync from Salesforce to WordPress</h1>
		<div class="mo-sf-sync-tab-content-tile-content">
			<h2 class="mo-sf-form-head mo-sf-form-head-bar mo-sf-mt-10">Realtime Sync</h2>
			<?php
				$db      = DB_Utils::instance();
				$mapping = $db->mo_sf_sync_get_all_mapping_data();

			if ( ! empty( $mapping ) && array_key_exists( 'sync_sf_to_wp', $mapping ) && '1' === $mapping['sync_sf_to_wp'] ) {
				$is_mapping_configured = ( isset( $mapping['label'] ) && ! empty( $mapping['label'] ) ? true : false );
				?>
					</br>
						<div class="mo-sf-sync-tab-content-tile" 
					<?php
					if ( Utils::mo_sf_sync_is_authorization_configured() && $is_mapping_configured ) {
						echo '';
					} else {
						echo 'hidden';
					}
					?>
						>
							<div>
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
											<code id="outbound_redirect_url" style="background: none;"><b><?php echo esc_url( $mapping['outbound_redirect_uri'] ); ?></b></code>
										</div>								
										<div style="margin-left: 30px ;">
											<i class="mo_copy copytooltip rounded-circle" onclick="mo_sf_sync_copyToClipboard(this, '#outbound_redirect_url', '#metadata_url_copy');"><span class="dashicons dashicons-admin-page"></span><span id="outbound_redirect_url_copy" class="copytooltiptext">Copy to Clipboard</span></i>
										</div>
									</div>
								</div>
							</div>
						</div>
					<?php
			} else {
				?>
					<div class="mo-sf-dflex mo-sf-ml-1">
						<div class="mo-sf-note">
							<h4>
								Real time sync allows you to sync WordPress user/posts with Salesforce when they are created/updated in WordPress. <br>
								Note: Users/Post will be updated if its record exists in the salesforce object otherwise it will create a record.
							</h4>
						</div>
					</div>
				<?php
			}
			?>

	</div>
	<br>
	<div class="mo-sf-sync-tab-content-tile-content mo-sf-prem-info">
		<div class="mo-sf-prem-lock">
			<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../../images/lock.png' ); ?>" alt="">
			<p class="mo-sf-prem-text">Available in premium plugin. <a href="?page=mo_sf_sync&tab=licenseplan" class="mo-sf-text-warning">Click here to upgrade</a></p>
		</div>
		<h2 class="mo-sf-form-head mo-sf-form-head-bar">Import Salesforce Data to WordPress</h2>

		<div class="mo-sf-note">
			<span> One time pull of all data from Salesforce to WordPress. Enables you to sync all Salesforce data into any WordPress Object to make sure that your existing data is maintained. You can configure multiple object mappings and configure which ones to be pulled from Salesforce.</span>
		</div>
		<tr>
			<td>
				<div class="mo-sf-mt-4">
					<input type="button" disabled class="mo-sf-btn-cstm" value="Pull Salesforce Data to WordPress">
					<div class="loader-placeholder"></div>
				</div>
			</td>
		</tr>
	</div>
	<br>
	<div class="mo-sf-sync-tab-content-tile-content mo-sf-prem-info">
		<div class="mo-sf-prem-lock">
			<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../../images/lock.png' ); ?>" alt="">
			<p class="mo-sf-prem-text">Available in premium plugin. <a href="?page=mo_sf_sync&tab=licenseplan" class="mo-sf-text-warning">Click here to upgrade</a></p>
		</div>
		<h2 class="mo-sf-form-head mo-sf-form-head-bar">Scheduled Sync </h2>

		<div class="mo-sf-note">
			<span> Object details will be synced from salesforce based at the time interval specified by you.</span>
		</div>
		<div class="mo-sf-dflex mo-sf-ml-1 mo-sf-mt-6">
			<div class="mo-sf-col-md-6">
				<h2>Enable Scheduled Object Sync</h2>
			</div>
			<div class="mo-sf-col-md-6 mo-sf-mt-3">
				<label class="mo-sf-sync-switch">
					<input type="checkbox" name="scheduled_user_sync" id="scheduled_user_sync" disabled>
					<span class="mo-sf-sync-slider round"></span>
				</label>
			</div>
		</div>
		<div id="scheduled_sync_configuration" name="scheduled_sync_configuration">
			<div class="mo-sf-dflex mo-sf-ml-1 mo-sf-mt-8">
				<div class="mo-sf-col-md-6">
					<h2>Sync Duration</h2>
				</div>
				<div class="mo-sf-col-md-6 mo-sf-mt-3">

					<select name="sync_interval" disabled>
						<option value="twicedaily">Twicedaily</option>
						<option value="daily">Daily</option>
					</select>

				</div>
			</div>
			<div class="mo-sf-dflex mo-sf-ml-1 mo-sf-mt-8">
				<div class="mo-sf-col-md-6">
					<h2>Last Synced on</h2>
				</div>
				<div class="mo-sf-col-md-6">

					<p>2022-03-14 11.34.30</p>

				</div>
			</div>
		</div>
		<tr>
			<td>
				<div class="mo-sf-mt-4">
					<input type="button" disabled class="mo-sf-btn-cstm" value="Save Configuration">
					<div class="loader-placeholder"></div>
				</div>
			</td>
		</tr>
	</div>
	</div>
	<?php
}
