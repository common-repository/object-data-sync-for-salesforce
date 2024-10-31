<?php
/**
 * This file deals with displaying a form in user's profile to edit the Salesforce ID with which that user is linked to.
 *
 * @package object-data-sync-for-salesforce\Helper\view\templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays the form in the user's profile to edit the Salesforce ID with which that user is linked to.
 *
 * @param string $salesforce_id_temp the temporary variable to store new salesforce ID.
 * @param string $salesforce_object_id the existing salesforce ID of the user.
 * @return void
 */
function mo_sf_sync_edit_template( $salesforce_id_temp, $salesforce_object_id ) {
	?>
		<h2><?php echo esc_html__( 'Salesforce' ); ?></h2>

		<p><?php echo esc_html__( 'You can change the Salesforce object that this WordPress user maps to by changing the ID and updating this user.' ); ?></p>

		<table class="form-table">
			<tr>
				<th><label for="salesforce_ID"><?php esc_html_e( 'Salesforce Id' ); ?></label></th>
				<td>
					<input type="text" id="salesforce_ID" name="salesforce_ID"
						value="
						<?php
						if ( null === $salesforce_id_temp ) {
							if ( isset( $salesforce_object_id ) ) {
								$salesforce_id_temp = $salesforce_object_id;
							}
							echo esc_attr( $salesforce_id_temp );
						} else {
							if ( isset( $salesforce_object_id ) ) {
								$salesforce_id_temp = $salesforce_object_id;
							}
							echo esc_attr( $salesforce_id_temp );
						}
						?>
						"
						class="regular-text"
					/>
				</td>
			</tr>
		</table>
	<?php
}


