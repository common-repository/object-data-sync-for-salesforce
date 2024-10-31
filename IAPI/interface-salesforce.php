<?php
/**
 * Declare the interface 'Salesforce_Interface'
 *
 * @package object-data-sync-for-salesforce\IAPI
 */

namespace MoSfSyncSalesforce\IAPI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface Salesforce_Interface {
	/**
	 * Get Salesforce objects list
	 *
	 * @return array
	 */
	public function mo_sf_sync_get_objects();

	/**
	 * Get Salesforce object fields list
	 *
	 * @param string $object Salesforce object.
	 * @return array
	 */
	public function mo_sf_sync_get_fields( $object);

	/**
	 * Return response for update record on salesforce side.
	 *
	 * @param string $id Salesforce ID.
	 * @param string $object Salesforce object.
	 * @param object $body request body.
	 * @return array
	 */
	public function mo_sf_sync_update_record( $id, $object, $body);

	/**
	 * Return response for create record on salesforce side.
	 *
	 * @param string $object Salesforce object.
	 * @param object $body request body.
	 * @return array
	 */
	public function mo_sf_sync_create_record( $object, $body);

}
