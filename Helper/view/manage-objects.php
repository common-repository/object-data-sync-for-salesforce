<?php
/**
 * This file handles the display for the Manage Objects(Object Mapping) tab.
 *
 * @package object-data-sync-for-salesforce\helper\view
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MoSfSyncSalesforce\Services\Utils;
use MoSfSyncSalesforce\Helper\view\Field_Mapping;

/**
 * Handles the display for the manage objects tab.
 *
 * @return void
 */
function mo_sf_sync_display_manage_users() {
	$view = Field_Mapping::instance();
	?>

	<div class="mo-sf-sync-tab-content">
		<?php
		if ( ! $view->is_auth_configured ) {
			Utils::mo_sf_sync_information_message( 'Please Configure & Authorize the Salesforce Connected Application in the <a href="?page=mo_sf_sync&tab=app_config">Manage Application</a> tab!', 'alert' );
		}

		Utils::mo_sf_sync_show_message();
		Utils::mo_sf_sync_show_feedback_message();
		Utils::mo_sf_sync_show_updatable_fields_info();
		?>

		<div class="mo-sf-sync-tab-content-left-border">

			<?php
			$view->mo_sf_sync_multiple_object_menu();
			?>
		</div>
	</div>
	<?php
}

