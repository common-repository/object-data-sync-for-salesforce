<?php
/**
 * This file handles what to be displayed on the Account Setup tab.
 *
 * @package object-data-sync-for-salesforce\Helper\view
 */

use MoSfSyncSalesforce\Customer;
use MoSfSyncSalesforce\Services\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays the content of the whole Account Setup tab.
 *
 * @return void
 */
function mo_sf_sync_display_account_setup() {   ?>

	<div class="mo-sf-sync-tab-content">
		<?php
		Utils::mo_sf_sync_show_message();
		Utils::mo_sf_sync_show_feedback_message();
		?>
		<?php

		if ( Customer::mo_sf_sync_is_customer_logged_in() ) {
			mo_sf_sync_display_account_information();
			//phpcs:ignore WordPress.Security.NonceVerification.Recommended -- it's a tab navigation
		} elseif ( array_key_exists( 'login', $_GET ) ) {
			?>

			<div class="mo-sf-bg-white mo-sf-mt-4">
				<h1 class="mo-sf-form-head">miniOrange Account Setup</h1>
				<div class="mo-sf-row mo-sf-ml-1">
					<div class="mo-sf-col-md-6">
						<?php mo_sf_sync_why_login(); ?>
					</div>
					<div class="mo-sf-col-md-5 mo-sf-ml-1">
						<?php mo_sf_sync_display_show_login_page(); ?>
					</div>
				</div>
			</div>

			<?php

		} elseif ( ! Customer::mo_sf_sync_is_customer_logged_in() ) {
			?>
			<div class="mo-sf-bg-white mo-sf-mt-4">
				<h1 class="mo-sf-form-head">miniOrange Account Setup</h1>
				<div class="mo-sf-row mo-sf-ml-1">
					<div class="mo-sf-col-md-6">
						<?php mo_sf_sync_why_reg(); ?>
					</div>
					<div class="mo-sf-col-md-5 mo-sf-ml-1">
						<?php mo_sf_sync_display_register_customer(); ?>
					</div>
				</div>
			</div>


			<?php
		} elseif ( Customer::mo_sf_sync_is_customer_logged_in() ) {
			mo_sf_sync_display_account_information();
		}
		?>

	</div>
	<?php
}

/**
 * Displays the reasons to log in with miniOrange account.
 *
 * @return void
 */
function mo_sf_sync_why_login() {
	?>
	<div class="mo-sf-mt-4">
		<h2 class="mo-sf-text-center">Why should I log in?</h2>
		<hr>
		<p class="mo-sf-text" style="text-align: justify ;">You should log in so that you can easily reach out to us in case you face any issues while setting up the plugin.
			<b> You will also need a miniOrange account to upgrade to the premium version of the plugin.</b>
			We do not store any information except the email that you will use to register with us.
		</p>
	</div>
	<?php
}

/**
 * Displays the reasons to register with miniOrange.
 *
 * @return void
 */
function mo_sf_sync_why_reg() {
	?>
	<div class="mo-sf-mt-4">
		<h2 class="mo-sf-text-center">Why should I register?</h2>
		<hr>
		<p class="mo-sf-text" style="text-align: justify ;">You should register so that in case you need help, we can help you with step-by-step instructions.
			We support integrations with many WordPress plugins, you can also reach out in case you want a new integration or need help with setting up the plugin.
			<b>You will also need a miniOrange account to upgrade to the premium version of the plugins.</b>
			We do not store any information except the email that you will use to register with us.
		</p>
	</div>
	<?php
}

/**
 * Displays miniOrange registration form when user is not logged in with miniOrange account.
 *
 * @return void
 */
function mo_sf_sync_display_register_customer() {
	?>
	<form class="mo_sf_sync_ajax_submit_form_2" id="mo_sf_sync_account_form" method="post">
		<input type="hidden" name="option" value="mo_sf_sync_account_registration">
		<input type="hidden" name="tab" value="account_setup">
		<?php wp_nonce_field( 'mo_sf_sync_account_registration' ); ?>

		<div class="mo-sf-mt-4 mo-sf-sync-tab-content-app-config-table mo-sf-acc-setup">		  
		<table class="mo-sf-sync-tab-content-app-config-table">

				<tr>
					<td><span class="mo-sf-text">Email<sup style="color:red">*</sup></span></td>
					<td>
						<input type="email" required placeholder="person@example.com" name="account_email_reg">
					</td>
				</tr>
				<tr>
					<td><span class="mo-sf-text">Password<sup style="color:red">*</sup></span></td>
					<td><input type="password" required placeholder="Enter your password" name="reg_account_pwd" minlength="6" pattern="^[(\w)*(!@#.$%^&*-_)*]+$" title="Minimum 6 characters should be present. Maximum 15 characters should be present. Only following symbols (!@#.$%^&*-_) should be present">
					</td>
				</tr>
				<tr>
					<td><span class="mo-sf-text">Confirm Password<sup style="color:red">*</sup></span></td>
					<td><input type="password" required placeholder="Enter your password" name="confirm_account_pwd" minlength="6" pattern="^[(\w)*(!@#.$%^&*-_)*]+$" title="Minimum 6 characters should be present. Maximum 15 characters should be present. Only following symbols (!@#.$%^&*-_) should be present">
					</td>
				</tr>

			</table>
			<div class="mo-sf-text-center">
				<input type="submit" class="mo-sf-btn-cstm" value="Register">
				<div class="mo-sf-mt-4"><a href="?page=mo_sf_sync&tab=account_setup&login" class="mo-sf-acc-login">Already have an account ? Login</a></div>
			</div>
		</div>
	</form>
	<?php
}

/**
 * Displays the miniOrange account information when user is logged in with miniOrange account.
 *
 * @return void
 */
function mo_sf_sync_display_account_information() {
	?>
	<form class="mo_sf_sync_ajax_submit_form_2" id="mo_sf_sync_remove_account_form" method="post">
		<input type="hidden" name="option" value="mo_sf_sync_remove_account_option">
		<input type="hidden" name="tab" value="account_setup">
		<?php wp_nonce_field( 'mo_sf_sync_remove_account_option' ); ?>
	<div style="width: 100%;">
		<div class="mo-sf-sync-tab-content-tile mo-sf-mt-4 ">
				<h1 class="mo-sf-form-head">Thank you for registering with miniOrange.</h1>

				<div class="mo-sf-row mo-sf-container-fluid">
				<div class="mo-sf-col-md-8 mo-sf-mt-4 mo-sf-ml-5">
					<div class="p-4 mo-sf-shadow-cstm bg-white rounded">
					<table style="width: 800px;">
						<tr style="background: #e9f0ff; width: 85px;">
							<td style="width:50%; padding: 10px; border:none;"><h3>miniOrange Account Email</h3></td>
							<td style="width:50%; padding: 10px; border:none;"><h3><?php echo esc_attr( get_option( 'mo_sf_sync_admin_email' ) ); ?></h3></td>
						</tr>
						<tr style="background: #e9f0ff;">
							<td style="width:50%; padding: 10px;"><h3>Customer ID</h3></td>
							<td style="width:50%; padding: 10px;"><h3><?php echo esc_attr( get_option( 'mo_sf_sync_admin_customer_key' ) ); ?></h3></td>
						</tr>
					</table>
					</br>
					<input type="submit" class="mo-sf-btn-cstm" value="Remove Account">
				</div>
				</div>
				</div>
		</div>
	</form>
	<?php

}

/**
 * Displays the login form to setup miniOrange account.
 *
 * @return void
 */
function mo_sf_sync_display_show_login_page() {
	$account_email = get_option( 'mo_sf_sync_admin_email' );

	?>
	<div class="login">
		<form class="mo_sf_sync_ajax_submit_form_2" id="mo_sf_sync_account_form" method="post">
			<input type="hidden" name="option" value="mo_sf_sync_account_setup_option">
			<input type="hidden" name="tab" value="account_setup">
			<?php wp_nonce_field( 'mo_sf_sync_account_setup_option' ); ?>
			<div class="mo-sf-mt-4 mo-sf-sync-tab-content-app-config-table mo-sf-acc-setup">
				<table class="mo-sf-sync-tab-content-app-config-table">
					<tr>
						<td><span class="mo-sf-text">Email<sup style="color:red">*</sup></span></td>
						<td>
							<input type="email" required placeholder="person@example.com" name="account_email" value='<?php echo esc_html( $account_email ); ?>'>
						</td>
					</tr>
					<tr>
						<td><span class="mo-sf-text">Password<sup style="color:red">*</sup></span></td>
						<td><input type="password" required placeholder="Enter your password" name="account_pwd" minlength="6" pattern="^[(\w)*(!@#.$%^&*-_)*]+$" title="Minimum 6 characters should be present. Maximum 15 characters should be present. Only following symbols (!@#.$%^&*-_) should be present">
						</td>
					</tr>

					<tr>
						<td>
						</td>
						<td>

						</td>
					</tr>
				</table>
				<div class="mo-sf-text-center">
					<input type="submit" class="mo-sf-btn-cstm" value="Login" onclick="mo_sf_sync_display_customer_info();">
					<a class="mo-sf-btn-cstm" href="?page=mo_sf_sync&tab=account_setup">Register</a> 
				<div class="mo-sf-mt-4"><a href="https://login.xecurify.com/moas/idp/resetpassword" target="_blank" class="mo-sf-acc-login">Click here if you forgot your password?</a></div></div>

				</div>
			</div>
		</form>
	</div>
	<?php
}
