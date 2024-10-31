<?php
/**
 * This file defines all required plugin constants.
 *
 * @package object-data-sync-for-salesforce\Helper
 */

namespace MoSfSyncSalesforce\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MoSfSyncSalesforce\Helper\Basic_Enum;

/**
 * Class Contains constants used in the plugin.
 */
class Plugin_Constants extends Basic_Enum {

	const HOSTNAME      = 'https://login.xecurify.com';
	const VERSION       = '1.2.4';
	const ACCESS_TOKEN  = 'access_token';
	const REFRESH_TOKEN = 'refresh_token';

	const MAPPING_OBJECT       = 'mo_sf_sync_object';
	const PROVISION_OBJECT     = 'mo_sf_sync_provision';
	const SF_RESPONSE_OBJECT   = 'mo_sf_sync_salesforce_response';
	const CONFIG_OBJECT        = 'mo_sf_sync_config';
	const DEMO_REQUEST_CONTENT = 'mo_sf_sync_demo_request_content';
	const TOPLEVEL_JS_SCRIPT   = array(
		'toplevel_page_mo_sf_sync',
		'object-data-sync-for-salesforce_page_troubleshoot',
	);
	const TOPLEVEL_CSS_STYLES  = array(
		'toplevel_page_mo_sf_sync',
		'object-data-sync-for-salesforce_page_troubleshoot',
		'object-data-sync-for-salesforce_page_plugin_guide',
		'object-data-sync-for-salesforce_page_pardot_guide',
	);

	const CLIENT_SECRET                       = 'client_secret';
	const CLIENT_ID                           = 'client_id';
	const REDIRECT_URI                        = 'redirect_uri';
	const ENVIRONMENT                         = 'env_select';
	const ENVIRONMENT_LINK                    = 'env_link';
	const AUTO_USER_UPDATE                    = 'automatic_user_update';
	const AUDIT_LOGS                          = 'mo_sf_sync_enable_audit_logs';
	const IS_PARDOT_ENABLED                   = 'is_pardot_int_enabled';
	const PARDOT_BUSSINESSUID                 = 'pardot_business_uid';
	const PARDOT_DOMAIN_LINK                  = 'pardot_env_link';
	const PARDOT_DOMAIN                       = array(
		'demo-pardot' => 'https://pi.demo.pardot.com',
		'live-pardot' => 'https://pi.pardot.com',
	);
	const PARDOT_DOMAIN_WITHOUT_SSL           = array(
		'demo-pardot' => 'demo.pardot.com',
		'live-pardot' => 'pardot.com',
	);
	const PARDOT_FORM_FIELDS                  = 'id,name,campaign.id,embedCode,salesforceId,layoutTemplateId,submitButtonText,beforeFormContent,afterFormContent,thankYouContent,isAlwaysDisplay,redirectLocation,isUseRedirectLocation,fontSize,fontFamily,fontColor,labelAlignment,radioAlignment,checkboxAlignment,requiredCharacter,isCookieless,showNotProspect,isCaptchaEnabled,isDeleted,trackerDomainId,createdAt,updatedAt,createdById,updatedById';
	const PARDOT_DYNAMIC_CONTENT_FIELDS       = 'id,name,embedCode,embedUrl,trackerDomainId,createdAt,updatedAt,createdById,updatedById';
	const PARDOT_DYNAMIC_CONTENT_FIELDS_CP_ID = 'id,name,embedCode,embedUrl,trackerDomainId,baseContent,createdAt,updatedAt,createdById,updatedById';
	const NON_PARDOT_SCOPES                   = 'api refresh_token';
	const PARDOT_SCOPES                       = 'api refresh_token pardot_api';

	const WPTOSF               = 'sync wp to sf';
	const SFTOWP               = 'sync sf to wp';
	const DEFAULT_DEMO_QUERY   = 'Can you please provide us demo of the premium plugin.';
	const DEMO_REQUEST_SUCCESS = 'Demo Request successful. A miniOrange representative will reach out to you on the E-mail id mentioned below.';

	const SELECTED_OBJECT = 'object_select';
	const WP_OBJECT       = 'wp_object_select';
	const CONNECTION_TYPE = 'mo_sf_sync_connection_type';
	const LAST_AUTH_TIME  = 'mo_sf_sync_last_auth_time';

	const TRANSIENT_CONFIG_OBJECT            = 'mo_sf_sync_config_transient';
	const MO_SF_SYNC_CONFIG_TRANSIENT        = '_transient_mo_sf_sync_config_transient';
	const TRANSIENT_TIMEOUT_PLUGIN_ACTIVATED = '_transient_timeout_mo_sf_sync_plugin_activated';
	const TRANSIENT_PLUGIN_ACTIVATED         = '_transient_mo_sf_sync_plugin_activated';
	const TRANSIENT_AUTHORIZATION_STATE      = '_transient_mo_sf_sync_transient_authorization_state';
	const TRANSIENT_LEAD_OBJECT              = 'transient_lead_object';
	const TRANSIENT_TRIAL_REQUEST            = 'mo_sf_sync_made_trial_request';
	const TRANSIENT_GET_OBJECT               = 'transient_get_object';
	const TRANSIENT_TRIAL_NOTICE             = 'mo_sf_sync_normal_trial_notice_dismiss_time';
	const TRANSIENT_INTEGRATION_NOTICE       = 'mo_sf_sync_integration_trial_notice_dismiss_time';

	const SUGGESTED_ADDONS = array(
		'Employee-Staff-Directory' => array(
			'title'    => 'Employee Staff Directory',
			'text'     => 'Employee Directory plugin creates a central directory of your Employees, Staff, Members, or Team and displays the listing on your WordPress site. Provides an easily searchable, sortable list of all the Employees, group or tag your Employees based on custom categories, Password protect your Employee details, and many more.',
			'link'     => 'https://wordpress.org/plugins/employee-staff-directory/',
			'knw-link' => 'https://plugins.miniorange.com/employee-directory-and-staff-listing-for-wordpress',
		),
	);

	const EXISTING_USER   = 'Existing User';
	const REMOVED_ACCOUNT = 'removed_account';

	const INTEGRATIONS_DESC = array(
		'ultimate_member'     => 'Create User profile with Ultimate Member with the data fetched from the Salesforce CRM. Easily show any data from Salesforce on your user profile.',
		'contact_form_7'      => 'Sync any and all data forms filled by your website visitors with Salesforce. With our Contact Form 7 integration you can immediately sync data to Salesforce when a website visitor registers for an event, subscribes to a newsletter, etc.',
		'woo_commerce'        => 'Keep your Woocommerce data in constant sync with Salesforce, sync data to Salesforce depending on various woocommerce events like order status change, order getting placed, etc. Create Leads, Order Records, Cases, Opportunities, etc based on order data.',
		'paid_membership_pro' => 'Map Membership level, User credentials, User information from Membership Checkout page with a Salesforce object field. Keep your membership in constant sync with Salesforce and maintain an effective membership lifecycle.',
		'acf'                 => 'Sync data of any custom fields of any type with Salesforce Object fields, this integration allows you to extend your normal WordPress data store to cover data of various types as per your needs',
		'cpt_ui'              => 'Keep all of your custom posts bi-directionally synced with Salesforce, this integration allows you to sync custom posts to and from Salesforce in real time whenever a custom post is created/updated in WordPress it will be synced to Salesforce and same for other way around.',
		'ninja_forms'         => 'Our Ninja Forms integration allows you to sync any data submitted by your users to Salesforce objects, for example you will be able to immediately create a Lead in Salesforce if someone fills out a contact us form, you can also set up accounts and contacts to be created when user registration happens.',
		'wp_forms'            => 'Sync data of any form on your site: your contact forms, lead magnets, webinar sign-up forms, and more with Salesforce objects. Each form on your website can create a different object in Salesforce. And you can map the form fields to the fields in Salesforce however you need to.',
		'gravity_forms'       => 'Allows you to connect WordPress Gravity Forms with Salesforce. To automatically add/update Gravity Forms form submissions to your Salesforce objects, simply integrate your Gravity Forms form with any Salesforce object.',
		'learndash'           => 'The integration allows you to sync all your course data with Salesforce you can easily manage your course data and student access management based on data stored in Salesforce.',
		'buddypress'          => 'Sync data of your community site created with buddypress with Salesforce, sync buddypress attributes of a user like profile content, groups, etc to Salesforce',
		'Affiliate_WP'        => 'Connect AffiliateWP to Salesforce and sync all your customer and affiliate data to Salesforce, alternatively you can sync data fro Salesforce to AffiliateWp',
	);

	const INTEGRATIONS_TITLE = array(
		'ultimate_member'     => 'Ultimate Member',
		'paid_membership_pro' => 'Paid Membership Pro',
		'woo_commerce'        => 'Woocommerce',
		'contact_form_7'      => 'Contact Form 7',
		'ninja_forms'         => 'Ninja Forms',
		'gravity_forms'       => 'Gravity Forms',
		'wp_forms'            => 'WP Forms',
		'buddypress'          => 'Buddypress',
		'acf'                 => 'Advance Custom Fields',
		'cpt_ui'              => 'Custom Post Type UI',
		'Affiliate_WP'        => 'Affiliate WP',
		'learndash'           => 'Learndash',
	);

	const INTEGRATIONS_URL = array(
		'ultimate_member'     => 'https://plugins.miniorange.com/connect-ultimate-member-to-salesforce-in-wordpress',
		'paid_membership_pro' => 'https://plugins.miniorange.com/salesforce-paid-memberships-pro-integration',
		'woo_commerce'        => 'https://plugins.miniorange.com/woocommerce-salesforce-integration-sync-orders',
		'contact_form_7'      => 'https://plugins.miniorange.com/wordpress-object-sync-for-salesforce',
		'ninja_forms'         => 'https://plugins.miniorange.com/wordpress-object-sync-for-salesforce',
		'gravity_forms'       => 'https://plugins.miniorange.com/wordpress-object-sync-for-salesforce',
		'wp_forms'            => 'https://plugins.miniorange.com/wordpress-object-sync-for-salesforce',
		'buddypress'          => 'https://plugins.miniorange.com/wordpress-object-sync-for-salesforce',
		'acf'                 => 'https://plugins.miniorange.com/wordpress-object-sync-for-salesforce',
		'cpt_ui'              => 'https://plugins.miniorange.com/scheduled-automation-sync-to-wp-using-salesforce-cpt-ui-acf-integration',
		'Affiliate_WP'        => 'https://plugins.miniorange.com/scheduled-automation-sync-to-wp-using-salesforce-cpt-ui-acf-integration',
		'learndash'           => 'https://plugins.miniorange.com/wordpress-object-sync-for-salesforce',
	);

	const INTEGRATIONS_ADVERTISEMENT = array(
		'um\core\Member_Directory' => 'Ultimate Member',
		'PMPro_Membership_Level'   => 'Paid Membership Pro',
		'WC_Admin_Assets'          => 'Woocommerce',
		'WPCF7_Integration'        => 'Contact Form 7',
		'WPForms_Settings'         => 'WP Forms',
		'BuddyPress'               => 'Buddypress',
		'ACF'                      => 'Advance Custom Fields',
		'cptui_admin_ui'           => 'Custom Post Type UI',
		'sfwd-lms/sfwd_lms.php'    => 'Learndash',
		'GFForms'                  => 'Gravity Forms',
		'Affiliate_WP'             => 'Affiliate WP',
	);

	const REQUIRED_FIELDS_FOR_FIELD_MAP_IMPORT = array( 'label', 'salesforce_object', 'wordpress_object', 'sync_wp_to_sf', 'sync_sf_to_wp' );
}
