<?php
/**
 * This file contains helper methods for the constants.
 *
 * @package object-data-sync-for-salesforce\Helper
 */

namespace MoSfSyncSalesforce\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *  This class contains method to extract constants of a child class in a form of array.
 */
abstract class Basic_Enum {
	/**
	 * Array to Store Cached Constants.
	 *
	 * @var array
	 */
	private static $const_cache_array = null;

	/**
	 * Get the value of a constant.
	 *
	 * @return array
	 */
	public static function mo_sf_sync_get_constants() {
		if ( null === self::$const_cache_array ) {
			self::$const_cache_array = array();
		}
		$called_class = get_called_class();
		if ( ! array_key_exists( $called_class, self::$const_cache_array ) ) {
			$reflect                                  = new \ReflectionClass( $called_class );
			self::$const_cache_array[ $called_class ] = $reflect->getConstants();
		}
		return self::$const_cache_array[ $called_class ];
	}
	/**
	 * Checks if constant name is valid.
	 *
	 * @param string  $name name of constant.
	 * @param boolean $strict boolean value.
	 * @return boolean
	 */
	public static function mo_sf_sync_is_valid_name( $name, $strict = false ) {
		$constants = self::mo_sf_sync_get_constants();

		if ( $strict ) {
			return array_key_exists( $name, $constants );
		}

		$keys = array_map( 'strtolower', array_keys( $constants ) );
		return in_array( strtolower( $name ), $keys, true );
	}
	/**
	 * Checks value of constant.
	 *
	 * @param string $value value to check.
	 * @return boolean
	 */
	public static function mo_sf_sync_is_valid_value( $value ) {
		$values = array_values( self::mo_sf_sync_get_constants() );
		return in_array( $value, $values, true );
	}
}
