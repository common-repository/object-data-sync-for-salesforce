<?php
/**
 * This file displays demo setup Form.
 *
 * @package object-data-sync-for-salesforce\Helper\view
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MoSfSyncSalesforce\Helper\Plugin_Constants;
use MoSfSyncSalesforce\Services\Utils;

/**
 * Displays demo setup Form.
 *
 * @return void
 */
function mo_sf_sync_setup_demo() {  ?>
	<div class="mo-sf-sync-tab-content">
	<?php
	Utils::mo_sf_sync_show_message();
	Utils::mo_sf_sync_show_feedback_message();
	?>
		<div class="mo-sf-sync-tab-content-left-border">
			<form class="mo_sf_sync_ajax_submit_form_2" id="mo_sf_sync_demo_form" method="post">
				<input type="hidden" name="option" value="mo_sf_sync_demo_setup">
				<input type="hidden" name="tab" value="demo_setup">
				<?php wp_nonce_field( 'mo_sf_sync_demo_setup' ); ?>
				<div class="mo-sf-sync-tab-content-tile mo-sf-mt-4">
					<h1 class="mo-sf-form-head">Request for Demo</h1>

					<table class="mo-sf-sync-tab-content-app-config-table">
						<tr>
							<td class="left-div"><span>Email<sup style="color:red">*</sup></span></td>
							<td>
								<input class="mo-sf-w-3" type="email" required placeholder="person@example.com" name="demo_email" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>">
							</td>
						</tr>
						<tr>
							<td class="left-div"><span>Description<sup style="color:red">*</sup></span></td>
							<td>
								<textarea class="mo-sf-w-3" rows="4" type="text" required placeholder="Tell us about your requirement." name="demo_description"></textarea>

							</td>
						</tr>
						</table>
								<h2 class="mo-sf-form-head mo-sf-form-head-bar">Select the Add-ons you are interested in (Optional) :</h2>
								<?php
								$column       = 0;
								$column_start = 0;
								foreach ( Plugin_Constants::INTEGRATIONS_TITLE as $key => $value ) {
									?>

									<?php
									if ( 0 === $column % 3 ) {
										$column_start = $column;
										?>
										<div class="mo-sf-row mo-sf-ml-1 mo-sf-mt-4 mo-sf_sync-opt-add-ons">
										<?php } ?>
										<div class="mo-sf-col-md-4">
											<input type="checkbox" name="<?php echo esc_attr( $key ); ?>" value="true"> <span class="mo-sf-text"><?php echo esc_html( $value ); ?></span>
										</div>
										<?php if ( $column === $column_start + 2 ) { ?>
										</div>
									<?php } ?>

									<?php
									$column++;
								}
								?>

								<div class="mo-sf-mt-4">
									<input type="submit" class="mo-sf-btn-cstm" name="submit" value="Send Request">
								</div>
				</div>
			</form>
		</div>
	</div>
	<?php
}
