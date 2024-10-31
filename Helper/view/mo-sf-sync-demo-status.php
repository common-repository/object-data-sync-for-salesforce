<?php
/**
 * This file displays the information about the requested demo.
 *
 * @package object-data-sync-for-salesforce\Helper\view
 */

use MoSfSyncSalesforce\Helper\Plugin_Constants;
use MoSfSyncSalesforce\Services\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Renders the demo request status.
 *
 * @param array $demo_content contains the email and query of the customer.
 * @return void
 */
function mo_sf_sync_show_demo_status( $demo_content ) {
	echo '<div class="mo-sf-sync-tab-content">';
			Utils::mo_sf_sync_show_feedback_message();
	echo '  <div class="mo-sf-bg-white mo-sf-mt-4">
				<h1 class="mo-sf-form-head">Demo Request Status</h1>';
	mo_sf_sync_show_demo_request_content( $demo_content );
	echo '  </div>
	     </div>';
}

/**
 * Constructs the HTML which contains the demo information.
 *
 * @param array $demo_content contains the email and query of the customer.
 * @return void
 */
function mo_sf_sync_show_demo_request_content( $demo_content ) {
	$email = isset( $demo_content['user_email'] ) ? $demo_content['user_email'] : wp_get_current_user()->user_email;
	$query = isset( $demo_content['user_query'] ) ? $demo_content['user_query'] : '';
	?>
		<form id="mo_sf_sync_demo_info_tab" method="post" target="_self">
			<?php wp_nonce_field( 'mo_sf_sync_demo_info' ); ?> 
			<input type="hidden" name="option" value="mo_sf_sync_demo_info">
			<input type="hidden" name="tab" value="demo_setup">
			<div class="mo_sf_sync_help_desc">
			<h4>
				<span>
					One of the miniOrange representatives will reach out to you on the email mentioned below. 
					If you want us to reach out to a different email address, kindly provide the same by clicking on the Edit icon beside the email address below, 
					and then click on the "Request Demo" button.
				</span> 
			</div>
			</br>
			<div class="p-4 mo-sf-shadow-cstm bg-white rounded">
				<table style="width: 800px;">
					<tr style="background: #e9f0ff; width: 85px;">
						<td class="mo-sf-sync-demo-info-email"><h3>Email Id</h3></td>
						<td class="mo-sf-sync-demo-info-email"><h3><span id="email-sent-id"> <?php echo esc_html( $email ); ?> </span>
						<span class="dashicons dashicons-edit mo-sf-sync-edit-email-btn" onclick="mo_sf_sync_make_email_editable()"></span></h3></td>
					</tr>
					<tr style="background: #e9f0ff;">
						<td class="mo-sf-sync-demo-info-query-time"><h3>When you should expect a reply from us</h3></td>
						<td class="mo-sf-sync-demo-info-query-time"><h3>Within 24 hours</h3></td>
					</tr>
					<tr style="background: #e9f0ff;">
						<td class="mo-sf-sync-demo-info-query-time"><h3>Write your query</h3></td>
						<td class="mo-sf-sync-demo-info-query-time">
							<textarea class="mo-sf-w-3" rows="4" type="text" placeholder="Tell us about your requirement." name="query_description"> <?php echo esc_html( $query ); ?> </textarea>
						</td>
					</tr>
				</table>
				</br>
				<input type="submit" class="mo-sf-btn-cstm" value="Request Demo">
			</div>
		</form>
	<?php
}
