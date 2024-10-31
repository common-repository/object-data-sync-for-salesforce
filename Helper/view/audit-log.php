<?php
/**
 * This file has the functionalities to display the audit logs in a table and filter the log transactions based on specific parameters.
 *
 * @package object-data-sync-for-salesforce\Helper\view
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use MoSfSyncSalesforce\Services\Audit_DB;
use MoSfSyncSalesforce\Helper\Plugin_Constants;
use MoSfSyncSalesforce\API\Salesforce;

/**
 * Function that fetches the logs from the custom audit log table and structures the fetched logs in tabular form.
 *
 * @return void
 */
function mo_sf_sync_fetch() {
	$db         = Audit_DB::instance();
	$salesforce = new Salesforce();
	$instance   = $salesforce->instance_url;
	$userlogs   = $db->mo_sf_sync_get_audit_using_advanced_search();
	$userlogs   = json_decode( wp_json_encode( $userlogs ), true );
	?>

	<div class="mo-sf-sync-tab-content">
	<div class="mo-sf-sync-tab-content-tile mo-sf-mt-4"  >
		<h1 class="mo-sf-form-head"><b>Audit Logs</b></h1>		
			<div class="mo-sf-sync-tab-content-left-border">
			<div class="mo-sf-dflex mo-sf-ml-1">
			<div class="mo-sf-note">
				<h4>
					Enabling Audit log will provide the facility to monitor all the objects over various parameters like Action (like "Create" and "Update") , Action Status (like "Success" or "Failed") , Response of the action etc.
				</h4>
			</div>
		</div>
		</br>
			<table id="reports_table" class="display" cellspacing="0" width="100%" >
				<thead>
					<tr>
						<th>Direction</th>
						<th>Salesforce Id</th>
						<th>WordPress Id</th>
						<th>User Action</th>
						<th>Action Status</th>
						<th>Response</th>
						<th>Time Stamp</th>
					</tr>
				</thead>
				<tbody>
				<?php

				foreach ( $userlogs as $log_entry ) {
					if ( 'user' === $log_entry['wordpress_object'] ) {
						$wp_obj_url = get_site_url() . '/wp-admin/user-edit.php?user_id=' . $log_entry['wordpress_id'];
					} else {
						$wp_obj_url = get_permalink( (int) $log_entry['wordpress_id'] );
					}

					echo '<tr style="text-align:center">
                            <td style="width:12%;">' . esc_html( $log_entry['direction'] ) . '</td>
                            <td style="width:12%">';
					if ( 'Creation Failed' !== $log_entry['salesforce_id'] ) {
						echo '<a style="text-decoration:none" href=' . esc_url( $instance . '/' . $log_entry['salesforce_id'] ) . ' target=__blank>' . esc_html( $log_entry['salesforce_id'] ) . '</a>';
					} else {
						echo '<span style=color:red>' . esc_html( $log_entry['salesforce_id'] ) . '</span>';
					}

							echo '</td><td style="width:12%"><a style="text-decoration:none" href=' . esc_url( $wp_obj_url ) . ' target=__blank>' . esc_html( $log_entry['wordpress_id'] ) . '</a></td><td style="width:12%">' . esc_html( $log_entry['user_action'] ) . '</td><td style="width:12%">';
					if ( 'Success' === $log_entry['action_status'] ) {
						echo '<span style=color:darkgreen>' . esc_html( $log_entry['action_status'] ) . '</span>';
					} else {
						echo '<span style=color:red>' . esc_html( $log_entry['action_status'] ) . '</span>';
					}
								echo '</td><td style="width:16%" class="Response_Icon">
                                            <span class="dashicons dashicons-info"></span>
                                        <div class="resp_hide">' . esc_html( $log_entry['response'] ) . '</div></td>';
							echo '</td><td style="width:12%">' . esc_html( $log_entry['time_stamp'] ) . '</td>';
					echo ' </tr>';
				}
				?>
				</tbody>
			</table>
		</div>
	</div>
	</div>
	<?php
}

/**
 * Function that provides a audit log toggle to enable/disable whether to provide logs for the sync transactions.
 * Includes functionality to truncate audit logs from the database.
 *
 * @return void
 */
function mo_sf_sync_enable_audit_log_advanced_search() {
	if ( isset( $_POST['option'] ) && 'mo_sf_sync_enable_audit_logs' === $_POST['option'] && check_admin_referer( 'mo_sf_sync_enable_audit_logs' ) ) {
		if ( array_key_exists( 'mo_sf_sync_enable_audit_logs', $_POST ) ) {
			update_option( Plugin_Constants::AUDIT_LOGS, 'true' );
			$auditdb = Audit_DB::instance();
			$auditdb->mo_sf_sync_create_audit_log_table();
		} else {
			update_option( Plugin_Constants::AUDIT_LOGS, '' );
		}
	}
	?>
	<div class="mo-sf-sync-tab-content-tile mo-sf-mt-4">
		<div class="mo-sf-sync-tab-content-tile-content">
		<h1 class="mo-sf-form-head" style="font-size: 20px;">Audit Logs</h1>		
		<div class="mo-sf-dflex mo-sf-ml-1">
			<div class="mo-sf-note">
				<h4 style="color: red;">NOTE: Please enable audit logs only when it is necessary as it might impact the performance of the sync.</h4>
			</div>
		</div>
			<div class="mo-sf-dflex mo-sf-ml-1 mo-sf-mt-4">
				<div class="mo-sf-col-md-6">
					<h2>Enable Audit</h2>
				</div>
				<div class="mo-sf-col-md-6">
					<form name="f" method="post" action="" id="audit_logs">
					<?php wp_nonce_field( 'mo_sf_sync_enable_audit_logs' ); ?>
					<input type="hidden" name="option" value="mo_sf_sync_enable_audit_logs"/>
					&nbsp;&nbsp;&nbsp;&nbsp;
					<label class="mo-sf-sync-switch" style="position: relative;left: 55%;">
						<input type="checkbox" name="mo_sf_sync_enable_audit_logs" <?php echo checked( 'true' === get_option( Plugin_Constants::AUDIT_LOGS ) ); ?>onchange="document.getElementById('audit_logs').submit();">
						<span class="mo-sf-sync-slider round"></span>
					</label>
					</form>
				</div>
			</div>	
			<div style="align-items:center">
				<form action="" method="post">
					<?php wp_nonce_field( 'mo_sf_sync_truncate_audit_logs' ); ?>
					<input type="hidden" name="option" value="mo_sf_sync_truncate_audit_logs"/>
					<input type="submit" class="mo-sf-btn-cstm" value="Delete All Logs" onclick="return confirm('This will delete all logs! Are you sure you want to proceed?')">
				</form>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Function that provides a process to fetch the log transaction results based on filters like WordPress Username, User Action (Create, Update), Direction, Status, From Date and To Date.
 *
 * @return void
 */
function mo_sf_sync_audit_log_filters() {
	?>
<div class="mo-sf-sync-tab-content-tile mo-sf-mt-4" style="width: 92% ;">
	<div  id="mo_sf_sync_advanced_search_div">
			<h2 class="mo-sf-form-head" style="font-size: 20px;">Add Search filters</h2>
	<form id="mo_sf_sync_advanced_reports" method="post" action="">
	<?php wp_nonce_field( 'mo_sf_sync_advanced_reports' ); ?>
		<input type="hidden" name="option" value="mo_sf_sync_advanced_reports">
		<table>
		</br>
		<tr>
		<td width="100%">WordPress Username :<p style="margin: 0%;"></p>
			<input type="text" id="username" name="username" placeholder="Search by username" value="<?php echo esc_attr( get_option( 'mo_sf_sync_advanced_search_username' ) ); ?>">
		</td>
		<td width="100%">User Action :<p style="margin: 0%;">
			<select name="user_action" id="user_action" style="width: 180px;">
				<?php
					$type = get_option( 'mo_sf_sync_advanced_search_action' );
				?>
				<option value="default" <?php echo esc_html( ( 'default' === $type ) ? 'selected="selected"' : '' ); ?>>All</option>
				<option value="Create" <?php echo esc_html( ( 'Create' === $type ) ? 'selected="selected"' : '' ); ?>>Create</option>
				<option value="Update" <?php echo esc_html( ( 'Update' === $type ) ? 'selected="selected"' : '' ); ?>>Update</option>
			</select>
		</td>
		<tr>
		<tr><td><br></td></tr>
		<td width="100%">Direction :<p style="margin: 0%;"></p>
			<select name="direction" id="direction">
				<?php
					$direction = get_option( 'mo_sf_sync_advanced_search_direction' );
				?>
				<option value="default" <?php echo esc_html( ( 'default' === $direction ) ? 'selected="selected"' : '' ); ?>>All</option>
				<option value="sync wp to sf"<?php echo esc_html( ( Plugin_Constants::WPTOSF === $direction ) ? 'selected="selected"' : '' ); ?>>WordPress -> Salesforce</option>
				<option value="sync sf to wp" <?php echo esc_html( ( Plugin_Constants::SFTOWP === $direction ) ? 'selected="selected"' : '' ); ?>>Salesforce -> WordPress</option>
			</select>
		</td>

		<td width="100%">Status :<p style="margin: 0%;">
			<select name="status" id="status" style="width: 180px;" >
				<?php
					$status = get_option( 'mo_sf_sync_advanced_search_status' );
				?>
				<option value="default" <?php echo esc_html( ( 'default' === $status ) ? 'selected="selected"' : '' ); ?>>All</option>
				<option value="success" <?php echo esc_html( ( 'success' === $status ) ? 'selected="selected"' : '' ); ?>>Success</option>
				<option value="failed" <?php echo esc_html( ( 'failed' === $status ) ? 'selected="selected"' : '' ); ?>>Failed</option>
			</select>
		</td>
	</tr>
		</tr>
		<tr><td><br></td></tr>
		<tr>

		<td width="100%">From Date :<p style="margin: 0%;"></p><input class="mo_wpns_table_textbox" style="width: 180px;" type="date" max="<?php echo esc_html( gmdate( 'Y-m-d' ) ); ?>" id="from_date" name="from_date" value="<?php echo esc_attr( get_option( 'mo_sf_sync_advanced_search_from_date' ) ); ?>"></td>
		<td width="100%">To Date :<p style="margin: 0%;"></p><input class="mo_wpns_table_textbox" style="width: 180px;" type="date" max="<?php echo esc_html( gmdate( 'Y-m-d' ) ); ?>" id="to_date" name="to_date" value="<?php echo esc_attr( get_option( 'mo_sf_sync_advanced_search_to_date' ) ); ?>"></td>
		<td></td>
		</tr>
		</table>
		<div class="mo-sf-row">
			<div class="mo-sf-col-md-6">
				<br><input type="submit" name="Search" value="Search" class="mo-sf-btn-cstm mo-sf-mt-4" style="position:relative;left: 15px;">
				</form>
			</div>
			<div class="mo-sf-col-md-6" >
				<?php mo_sf_sync_clear_search_filters(); ?>
			</div>
		</div>
	</form>
	<br>
</div>
	</div>
	<?php
}

/**
 * Function that clears all search filters provided by the user in the advance search form.
 *
 * @return void
 */
function mo_sf_sync_clear_search_filters() {
	?>
	<form id="mo_sf_sync_clear_advance_search" method="post" action="">
		<?php wp_nonce_field( 'mo_sf_sync_clear_advance_search' ); ?>
			<input type="hidden" name="option" value="mo_sf_sync_clear_advance_search">
			<input type="submit" name="clearsearch"  value="Clear Search" class="mo-sf-btn-cstm mo-sf-mt-8"style="position:relative;top: 43px;right: 35%;">
	</form>
	<?php
}

