<?php
/**
 * This file deals with displaying a table in user's profile either to push it to Salesforce or edit the Salesforce ID it is linked to.
 *
 * @package object-data-sync-for-salesforce\Helper\view\templates
 */

use MoSfSyncSalesforce\API\Salesforce;
use MoSfSyncSalesforce\Services\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays a table in user profile to push it to salesforce or edit the Salesforce ID it is linked to.
 *
 * @param string  $salesforce_id_temp the temporary variable to store new salesforce ID.
 * @param string  $salesforce_object_id the existing salesforce ID of the user.
 * @param WP_User $user the WP_User object of the user whose profile is open.
 * @return void
 */
function mo_sf_sync_table_template( $salesforce_id_temp, $salesforce_object_id, $user ) {
	$url      = new Salesforce();
	$instance = $url->instance_url;

	?>
	<caption><br><a style="color :black; font-size: 15px;"><strong>Object Data Sync For Salesforce</strong></a></caption><p></p>
	<table class="wp-list-table widefat striped mapped-salesforce-user" style="width:auto;">

		<tbody>
			<tr>
				<th><strong>Salesforce Id</strong></th>
				<?php
				if ( empty( $salesforce_id_temp ) && empty( $salesforce_object_id ) ) {
					?>
					<td><a><?php echo ''; ?></a></td>
					<?php
				} elseif ( empty( $salesforce_id_temp ) ) {
					?>
				<td><a href="<?php echo esc_url( $instance . '/' . $salesforce_object_id ); ?>" target="blank"><?php echo esc_attr( $salesforce_object_id ); ?></a></td>
					<?php
				} else {
					?>
				<td><a href="<?php echo esc_url( $instance . '/' . $salesforce_id_temp ); ?>" target="blank"><?php echo esc_attr( $salesforce_id_temp ); ?></a></td>
					<?php
				}
				?>

				<td></td>
			</tr>
			<tr>
				<th><strong>Action</strong></th>
				<?php
				$auth_check = Utils::mo_sf_sync_is_authorization_configured();
				if ( $auth_check ) {
					?>
				<td><a href="<?php echo esc_url( get_admin_url( null, 'user-edit.php?user_id=' . $user->ID ) . '&amp;push=true' ); ?>" class="button button-secondary push_to_salesforce_button"><?php echo esc_html__( 'Push to Salesforce' ); ?></a></td>
				<td><a href="<?php echo esc_url( get_admin_url( null, 'user-edit.php?user_id=' . $user->ID ) . '&amp;edit_salesforce_mapping=true' ); ?>" class="button button-secondary push_to_salesforce_button"><?php echo esc_html__( 'Edit' ); ?></a></td>
					<?php
				} else {
					?>
					<td><input type="button" href="<?php echo esc_url( get_admin_url( null, 'user-edit.php?user_id=' . $user->ID ) . '&amp;push=true' ); ?>" disabled title= "Please Authorize first" class="button button-secondary push_to_salesforce_button" value="Push to Salesforce"/></td>
					<td><input type="button" href="<?php echo esc_url( get_admin_url( null, 'user-edit.php?user_id=' . $user->ID ) . '&amp;edit_salesforce_mapping=true' ); ?>" disabled title= "Please Authorize first" class="button button-secondary push_to_salesforce_button" value="Edit"/></td>
					<?php
				}
				?>
			</tr>
		</tbody> 
	</table>
	<?php
}
