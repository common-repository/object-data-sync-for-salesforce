<?php
/**
 *  This file contains the whole feedback form to be displayed at the time of deactivation of the plugin.
 *
 * @package object-data-sync-for-salesforce
 */

namespace MoSfSyncSalesforce;

use MoSfSyncSalesforce\Helper\Plugin_Constants;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays the feedback form at the time of plugin deactivation.
 *
 * @return void
 */
function mo_sf_sync_display_feedback_form() {
	if ( isset( $_SERVER['PHP_SELF'] ) && 'plugins.php' !== basename( sanitize_text_field( wp_unslash( $_SERVER['PHP_SELF'] ) ) ) ) {
		return;
	}
	wp_enqueue_style( 'wp-pointer' );
	wp_enqueue_script( 'wp-pointer' );
	wp_enqueue_script( 'utils' );
	wp_enqueue_style( 'mo_sf_sync_admin_plugins_page_style', plugins_url( 'Helper/view/includes/css/mo_sf_sync_settings.min.css', __FILE__ ), array(), Plugin_Constants::VERSION );
	?>
	<div id="mo_sf_sync_feedback_modal" class="mo_sf_sync_modal"
		style="width:90%; margin-left:12%; margin-top:5%; text-align:center;">
		<div class="mo_sf_sync_modal-content" style="width:50%;">
			<h3 style="margin: 2%; text-align:center;"><b>
					<?php esc_attr_e( 'Your feedback', 'object-data-sync-for-salesforce' ); ?>
				</b><span class="mo_sf_sync_close" style="cursor: pointer">&times;</span>
			</h3>
			<hr style="width:75%;">
			<form name="f" method="post" action="" id="mo_sf_sync_feedback">
				<?php wp_nonce_field( 'mo_sf_sync_feedback' ); ?>
				<input type="hidden" name="option" value="mo_sf_sync_feedback" />
				<div>
					<p style="margin:2%">
					<h4 style="margin: 2%; text-align:center;">
						<?php esc_attr_e( 'Please help us to improve our plugin by giving your opinion.', 'object-data-sync-for-salesforce' ); ?><br>
					</h4>
					<div id="mo_sf_sync_smi_rate" style="text-align:center">
						<input type="radio" name="rate" id="mo_sf_sync_angry" value="1" />
						<label for="mo_sf_sync_angry"><img class="mo_sf_sync_sm"
								src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . 'images/angry.png'; ?>" />
						</label>

						<input type="radio" name="rate" id="mo_sf_sync_sad" value="2" />
						<label for="mo_sf_sync_sad"><img class="mo_sf_sync_sm"
								src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . 'images/sad.png'; ?>" />
						</label>


						<input type="radio" name="rate" id="mo_sf_sync_neutral" value="3" />
						<label for="mo_sf_sync_neutral"><img class="mo_sf_sync_sm"
								src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . 'images/normal.png'; ?>" />
						</label>

						<input type="radio" name="rate" id="mo_sf_sync_smile" value="4" />
						<label for="mo_sf_sync_smile">
							<img class="mo_sf_sync_sm" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . 'images/smile.png'; ?>" />
						</label>

						<input type="radio" name="rate" id="mo_sf_sync_happy" value="5" checked />
						<label for="mo_sf_sync_happy"><img class="mo_sf_sync_sm"
								src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . 'images/happy.png'; ?>" />
						</label>

						<div id="mo_sf_sync_outer" style="visibility:visible"><span id="mo_sf_sync_result">
								<?php esc_attr_e( 'Thank you for appreciating our work', 'object-data-sync-for-salesforce' ); ?>
							</span></div>
					</div><br>
					<hr style="width:75%;">
					<?php
							$user  = wp_get_current_user();
							$email = $user->user_email;
					?>
					<div style="text-align:center;">

						<div style="display:inline-block; width:60%;">
							<input type="email" id="mo_sf_sync_query_mail" name="query_mail"
								style="text-align:center; border:0px solid black; border-style:solid; background:#f0f3f7; width:20vw;border-radius: 6px;"
								placeholder="<?php esc_attr_e( 'Please enter your email address', 'object-data-sync-for-salesforce' ); ?>"
								required value="<?php echo esc_attr( $email ); ?>" readonly="readonly" />

							<input type="radio" name="edit" id="mo_sf_sync_edit" value="" />
							<label for="edit"><img class="mo_sf_sync_editable"
									src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . 'images/61456.png'; ?>" />
							</label>

						</div>
						<br><br>
						<textarea id="query_feedback" name="query_feedback" rows="4" style="width: 60%"
							placeholder="<?php esc_attr_e( 'Tell us what happened!', 'object-data-sync-for-salesforce' ); ?>"></textarea>
						<br><br>
						<input type="checkbox" name="get_reply" value="yes" checked>
						<?php esc_attr_e( 'miniOrange representative will reach out to you at the email-address entered above.', 'object-data-sync-for-salesforce' ); ?></input>
					</div>
					<br>

					<div class="mo_sf_sync_modal-footer" style="text-align: center;margin-bottom: 2%">
						<input type="submit" name="miniorange_feedback_submit"
							class="button button-primary button-large"
							value="<?php esc_attr_e( 'Send', 'object-data-sync-for-salesforce' ); ?>" />
						<span width="30%">&nbsp;&nbsp;</span>
						<input type="submit" name="miniorange_feedback_submit" class="button button-primary button-large"
							value="<?php esc_attr_e( 'Skip', 'object-data-sync-for-salesforce' ); ?>"/>
					</div>
				</div>
			</form>
		</div>
	</div>

	<script>
		jQuery(document).ready(function(){
			jQuery('a[aria-label="Deactivate Object Data Sync For Salesforce"]').click(function() {

			jQuery("#mo_sf_sync_feedback_modal").show()
			jQuery("#mo_sf_sync_feedback_modal #query_feedback").focus()

			jQuery(".mo_sf_sync_close").click(function(){
				jQuery("#mo_sf_sync_feedback_modal").hide()
			})

			return false;

			});

			const INPUTS = document.querySelectorAll('#mo_sf_sync_smi_rate input');
			INPUTS.forEach(el => el.addEventListener('click', mo_sf_sync_update_value));

			jQuery(".mo_sf_sync_editable").click(function(){    
				document.querySelector('#mo_sf_sync_query_mail').removeAttribute('readonly');
				document.querySelector('#mo_sf_sync_query_mail').focus();
				return false;
			})
			function mo_sf_sync_update_value(e) {
				document.querySelector('#mo_sf_sync_outer').style.visibility = "visible";
				var result = '<?php esc_html_e( 'Thank you for appreciating our work', 'object-data-sync-for-salesforce' ); ?>';
				switch (e.target.value) {
					case '1': result = '<?php esc_html_e( 'Not happy with our plugin ? Let us know what went wrong', 'object-data-sync-for-salesforce' ); ?>';
						break;
					case '2': result = '<?php esc_html_e( 'Found any issues ? Let us know and we\'ll fix it ASAP', 'object-data-sync-for-salesforce' ); ?> ';
						break;
					case '3': result = '<?php esc_attr_x( 'Let us know if you need any help', 'object-data-sync-for-salesforce' ); ?>';
						break;
					case '4': result = '<?php esc_html_e( 'We\'re glad that you are happy with our plugin', 'object-data-sync-for-salesforce' ); ?> ';
						break;
					case '5': result = '<?php esc_attr_e( 'Thank you for appreciating our work' ); ?>';
						break;
				}
				jQuery('#mo_sf_sync_result').html(result);
			}
		})
	</script>
	<?php
}
