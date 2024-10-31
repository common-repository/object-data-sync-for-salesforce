<?php
/**
 * This file displays the information about all the integrations provided by the plugin
 *
 * @package object-data-sync-for-salesforce\Helper\view
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MoSfSyncSalesforce\Helper\Plugin_Constants;

/**
 * Function that iterates and displays all the integrations provided in the form of tile view.
 *
 * @return void
 */
function mo_sf_sync_show_integrations() {
	?>
	<div class="mo-sf-sync-tab-content">
		<div class="mo-sf-sync-tab-content-tile mo-sf-mt-4" style="background: none;box-shadow:none">
			<h1 class="mo-sf-form-head"><?php esc_html_e( 'Check out all our Integrations', 'Object Data Sync for Salesforce' ); ?></h1>

		<?php
		foreach ( Plugin_Constants::INTEGRATIONS_DESC as $key => $value ) {
			mo_sf_sync_get_addon_tile( $key, Plugin_Constants::INTEGRATIONS_TITLE[ $key ], $value, Plugin_Constants::INTEGRATIONS_URL[ $key ] );
		}
		?>
		</div>
	</div>
	<?php
}

/**
 * Function to display each Integrations'/Addon details provided by the plugin.
 *
 * @param string $addon_name  The name of the Addon.
 * @param string $addon_title The title of the Addon.
 * @param string $addon_desc  The brief description of the Addon.
 * @param string $addon_url   The page URL of the Addon.
 * @return void
 */
function mo_sf_sync_get_addon_tile( $addon_name, $addon_title, $addon_desc, $addon_url ) {

	$icon_url = plugin_dir_url( __FILE__ ) . '../../images/integrations/' . $addon_name . '.png';
	?>
	<div class="mo-sf-sync-add-ons-cards mo-sf-mt-4">
		<h4 class="mo-sf-sync-addons-head"><?php echo esc_html( $addon_title ); ?></h4>
		<p class="pe-2 pb-4 ps-4"><?php echo esc_html( $addon_desc ); ?></p>
		<img src="<?php echo esc_url( $icon_url ); ?>" class="mo-sf-sync-addons-logo" alt=" Image">
		<span class="mo-sf-sync-add-ons-rect"></span>
		<span class="mo-sf-sync-add-ons-tri"></span>
		<a class="mo-sf-sync-addons-readmore" href="<?php echo esc_url( $addon_url ); ?>" target="_blank">Learn More</a>
	</div>
	<?php
}
