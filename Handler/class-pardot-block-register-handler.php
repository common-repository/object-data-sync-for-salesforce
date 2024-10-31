<?php
/**
 * This file registers the gutenberg blocks and displays the associated forms/dynamic content.
 *
 * @package object-data-sync-for-salesforce\Handler
 */

namespace MoSfSyncSalesforce\Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MoSfSyncSalesforce\Helper\Instance;

/**
 * Handles the registration of gutenberg blocks and displays the associated forms/dynamic content.
 */
class Pardot_Block_Register_Handler {

	use Instance;

	/**
	 * Instance of Data_Processing_Handler.
	 *
	 * @var Data_Processing_Handler
	 */
	private $data_processing_handler;

	/**
	 * Creates instance of the class.
	 *
	 * @return __CLASS__
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$class                                   = __CLASS__;
			self::$instance                          = new $class();
			self::$instance->data_processing_handler = Data_Processing_Handler::instance();
		}
		return self::$instance;
	}

	/**
	 * Register the gutenberg blocks for pardot forms and dynamic content.
	 *
	 * @return void
	 */
	public function mo_sf_sync_register_gutenberg_blocks() {
		$indexjs_path     = '../Helper/view/includes/js/index.js';
		$rel_indexjs_path = MOSF_DIRC . '/Helper/view/includes/js/index.js';
		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter -- This is required to register the script for gutenberg blocks.
		$val = wp_register_script(
			'mo-sf-sync-gutenberg-block',
			plugins_url( $indexjs_path, __FILE__ ),
			array( 'wp-block-editor', 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-server-side-render' ),
			filemtime( $rel_indexjs_path )
		);
		wp_localize_script( $rel_indexjs_path, 'ajax_object_sf', array( 'ajax_url_sf' => admin_url( '/admin-ajax.php' ) ) );
		register_block_type(
			'object-data-sync-for-salesforce/pardot-form',
			array(
				'editor_script'   => 'mo-sf-sync-gutenberg-block',
				'render_callback' => array( $this, 'mo_sf_sync_pardot_form_block' ),
				'attributes'      => array(
					'form_id'   => array(
						'type'    => 'string',
						'default' => '',
					),
					'height'    => array(
						'type'    => 'string',
						'default' => '',
					),
					'width'     => array(
						'type'    => 'string',
						'default' => '',
					),
					'className' => array(
						'type'    => 'string',
						'default' => '',
					),
					'title'     => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			)
		);
		register_block_type(
			'object-data-sync-for-salesforce/dynamic-content',
			array(
				'editor_script'   => 'mo-sf-sync-gutenberg-block',
				'render_callback' => array( $this, 'mo_sf_sync_dynamic_content_block' ),
				'attributes'      => array(
					'dynamicContent_id'      => array(
						'type'    => 'string',
						'default' => '',
					),
					'dynamicContent_default' => array(
						'type'    => 'string',
						'default' => '',
					),
					'height'                 => array(
						'type'    => 'string',
						'default' => '',
					),
					'width'                  => array(
						'type'    => 'string',
						'default' => '',
					),
					'className'              => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			)
		);
	}

	/**
	 * Renders the selected pardot form block.
	 *
	 * @param array $attributes contains form id and other styles for embedding.
	 * @return mixed
	 */
	public function mo_sf_sync_pardot_form_block( $attributes ) {
		if ( isset( $attributes['form_id'] ) ) {
			$attributes['class'] = $attributes['className'];
			unset( $attributes['className'] );
			return $this->data_processing_handler->mo_sf_sync_get_pardot_form_body( $attributes );
		}
		return '';
	}

	/**
	 * Renders the selected dynamic content block.
	 *
	 * @param array $attributes contains dynamic content id and other styles for embedding.
	 * @return mixed
	 */
	public function mo_sf_sync_dynamic_content_block( $attributes ) {
		if ( isset( $attributes['dynamicContent_id'] ) ) {
			$attributes['class'] = $attributes['className'];
			unset( $attributes['className'] );
			return $this->data_processing_handler->mo_sf_sync_dynamic_content_body( $attributes );
		}
		return '';
	}
}
