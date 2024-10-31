<?php
/**
 * This file contains helper class to create singletons.
 *
 * @package object-data-sync-for-salesforce\Helper
 */

namespace MoSfSyncSalesforce\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait Instance {

	/**
	 * Instance variable
	 *
	 * @var mixed $instance
	 */
	private static $instance = null;

	/**
	 * Get instance of this class
	 *
	 * @return self
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

}
