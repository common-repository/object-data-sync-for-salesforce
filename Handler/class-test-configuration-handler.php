<?php
/**
 * This file takes care of displaying the Test connection window for WordPress to Salesforce sync.
 *
 * @package obect-data-sync-for-salesforce\handler
 */

namespace MoSfSyncSalesforce\Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MoSfSyncSalesforce\Helper\Instance;

/**
 * This class takes care of displaying the Test connection window for WordPress to Salesforce sync.
 */
class Test_Configuration_Handler {

	use Instance;

	/**
	 * Instance of the Data_Processing_Handler.
	 *
	 * @var Data_Processing_Handler
	 */
	private $data_processing_handler;

	/**
	 * Creates instance of the class.
	 *
	 * @return Test_Configuration_Handler
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance                          = new self();
			self::$instance->data_processing_handler = Data_Processing_Handler::instance();
		}
		return self::$instance;
	}

	/**
	 * Displays the test configuration window for WordPress to Salesforce user sync.
	 *
	 * @param int $wpid WordPress user id.
	 * @return void
	 */
	public function mo_sf_sync_show_test_connection_window( $wpid ) {

		$response = $this->data_processing_handler->mo_sf_sync_push_to_salesforce( $wpid );
		if ( empty( $response ) ) {
			$action = 'Update';
		} elseif ( array_key_exists( 'id', $response ) && array_key_exists( 'success', $response ) ) {
			$action = 'Create';
		} else {
			$action = 'none';
		}
		if ( 'none' !== $action ) {
			echo '<div style="color: #3c763d;
				background-color: #dff0d8; padding:2%;margin-bottom:20px;text-align:center; border:1px solid #AEDB9A; font-size:18pt; border-radius:10px;margin-top:17px;">TEST SUCCESSFUL</div>
				<div style="display:block;text-align:center;margin-bottom:4%;margin-top:-3.5rem;"><div class="animation-ctn">
				<div class="icon icon--order-success svg">
					<svg xmlns="http://www.w3.org/2000/svg" width="154px" height="154px">  
					  <g fill="none" stroke="rgba(76, 175, 80, 0.5)" stroke-width="5"> 
						<circle cx="77" cy="77" r="72" style="stroke-dasharray:480px, 480px; stroke-dashoffset: 960px;" stroke-width="7"></circle>
						<circle id="colored" fill="#fff" cx="77" cy="77" r="72" style="stroke-dasharray:480px, 480px; stroke-dashoffset: 960px;"></circle>
						<polyline class="st0" stroke="rgba(76, 175, 80, 1)" stroke-width="7" points="43.5,77.8 63.7,97.9 112.2,49.4 " style="stroke-dasharray:100px, 100px; stroke-dashoffset: 200px;"/>   
					  </g> 
					</svg>
				  </div>
		  </div>
		  <style>
			.animation-ctn{
				text-align:center;
				margin-top:5em;
			}
			@-webkit-keyframes checkmark {
				0% {
					stroke-dashoffset: 100px
				}
				100% {
					stroke-dashoffset: 200px
				}
			}
			@-ms-keyframes checkmark {
				0% {
					stroke-dashoffset: 100px
				}
				100% {
					stroke-dashoffset: 200px
				}
			}
			@keyframes checkmark {
				0% {
					stroke-dashoffset: 100px
				}
				100% {
					stroke-dashoffset: 0px
				}
			}
			@-webkit-keyframes checkmark-circle {
				0% {
					stroke-dashoffset: 480px
				}
				100% {
					stroke-dashoffset: 960px;
				}
			}
			@-ms-keyframes checkmark-circle {
				0% {
					stroke-dashoffset: 240px
				}
				100% {
					stroke-dashoffset: 480px
				}
			}
			@keyframes checkmark-circle {
				0% {
					stroke-dashoffset: 480px 
				}
				100% {
					stroke-dashoffset: 960px
				}
			}
			@keyframes colored-circle { 
				0% {
					opacity:0
				}
				100% {
					opacity:100
				}
			}
			.inlinesvg .svg svg {
				display: inline
			}
			.icon--order-success svg polyline {
				-webkit-animation: checkmark 0.25s ease-in-out 0.7s backwards;
				animation: checkmark 0.25s ease-in-out 0.7s backwards
			}
			.icon--order-success svg circle {
				-webkit-animation: checkmark-circle 0.6s ease-in-out backwards;
				animation: checkmark-circle 0.6s ease-in-out backwards;
			}
			.icon--order-success svg circle#colored {
				-webkit-animation: colored-circle 0.6s ease-in-out 0.7s backwards;
				animation: colored-circle 0.6s ease-in-out 0.7s backwards;
			} 
			</style></div>';

			echo '
		<table style="border-collapse:collapse;border-spacing:0; display:table;width:100%; font-size:14pt;word-break:break-all;">
		<tr style="text-align:center;background:#d3e1ff;border:2.5px solid #ffffff";word-break:break-all;><td style="font-weight:bold;padding:2%;border-top-left-radius: 10px;border:2.5px solid #ffffff">ATTRIBUTE NAME</td><td style="font-weight:bold;padding:2%;border:2.5px solid #ffffff; word-wrap:break-word;border-top-right-radius:10px">ATTRIBUTE VALUE</td></tr>';

			echo "<tr><td style='border:2.5px solid #ffffff;padding:2%;background:#e9f0ff;'>" . esc_html( 'Action' ) . "</td><td style='padding:2%;border:2.5px solid #ffffff;background:#e9f0ff;word-wrap:break-word;'>" . esc_html( $action ) . '</td></tr>';
			if ( 'Create' === $action ) {
				echo "<tr><td style='border:2.5px solid #ffffff;padding:2%;background:#e9f0ff;'>" . esc_html( 'Salesforce Id' ) . "</td><td style='padding:2%;border:2.5px solid #ffffff;background:#e9f0ff;word-wrap:break-word;'>" . esc_html( $response['id'] ) . '</td></tr>';
			} elseif ( 'Update' === $action ) {
				$map  = $this->data_processing_handler->mo_sf_sync_get_mapping( $wpid );
				$sfid = get_user_meta( $wpid, 'salesforce_' . $map['object'] . '_ID', true );
				echo "<tr><td style='border:2.5px solid #ffffff;padding:2%;background:#e9f0ff;'>" . esc_html( 'Salesforce Id' ) . "</td><td style='padding:2%;border:2.5px solid #ffffff;background:#e9f0ff;word-wrap:break-word;'>" . esc_html( $sfid ) . '</td></tr>';
			}
		} else {
			if ( isset( $response[0]['message'] ) ) {
				wp_die( '<b>Salesforce returned issue:</b> ' . esc_html( $response[0]['message'] ) . ' </br><b>Please Contact us at salesforcesupport@xecurify.com if you are unable to solve the issue.</b>' );
			} elseif ( isset( $response[0] ) && ( strpos( $http_response_header[0], 'cURL' ) === 0 ) ) {
				wp_die( '<b>Connection Error:</b> ' . esc_html( $response[0] ) . ' </br><b>Please Contact us at salesforcesupport@xecurify.com if you are unable to solve the issue.</b>' );
			}
		}
		exit;
	}
}
