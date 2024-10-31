<?php
/**
 * This file takes care of rendering all the connection successful messages.
 *
 * @package object-data-sync-for-salesforce\Helper\view
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MoSfSyncSalesforce\Services\Utils;

/**
 * Displays success message and further steps on successful connection with salesforce.
 *
 * @return void
 */
function mo_sf_sync_connect_to_salesforce_successful_message() {
	echo '<div style="color: #3c763d;
				background-color: #dff0d8; padding:2%;margin-bottom:20px;text-align:center; border:1px solid #AEDB9A; font-size:18pt; border-radius:10px;margin-top:17px;">Your connection with Salesforce is successful</div>
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
		  </div><style>
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

	if ( Utils::mo_sf_sync_is_pardot_configured() ) {
		echo '
		 <br>
		 <div style="background: #d5e2ff;padding: 0.2rem 1.5rem;border-radius: 5px;
				font-size: 1.1em;line-height: 1.4;border-left: 5px solid #77a1ff;">
				<b>Note:</b> Click on <b>Configure Object Mapping</b> to map Salesforce and WordPress fields as next step to configure the sync between salesforce and WordPress.
				If you want to embed pardot forms/dynamic content on your WordPress site, click on <b>Get pardot integration Steps</b>.
		</div>
		 ';
		echo '
		 <div style="margin:3%;display:block;text-align:center;">
		 <input style="margin-right: 10px;background: linear-gradient(0deg,rgb(14 42 71) 0,rgb(26 69 138) 100%)!important;cursor: pointer;font-size:15px;border: none;font-size: 1.1em;padding:0.7rem 1.5rem;border-radius:5px;color: #FFF;cursor: pointer;text-decoration: none;"
		 type="button" value="Configure Object Mapping" onclick="close_and_redirect_to_field_mapping();" >
		 <input style="margin-right: 10px;background: linear-gradient(0deg,rgb(14 42 71) 0,rgb(26 69 138) 100%)!important;cursor: pointer;font-size:15px;border: none;font-size: 1.1em;padding:0.7rem 1.5rem;border-radius:5px;color: #FFF;cursor: pointer;text-decoration: none;"
		 type="button" value="Get pardot integration Steps" onclick="close_and_redirect_to_pardot_guide();" >
		 </div>';
	} else {
		echo '
		 <br>
		 <div style="background: #d5e2ff;padding: 0.2rem 1.5rem;border-radius: 5px;
				font-size: 1.1em;line-height: 1.4;border-left: 5px solid #77a1ff;">
				<b>Note:</b> Click on <b>Next</b> to map Salesforce and WordPress fields as next step to configure the sync between salesforce and WordPress
		</div>
		 ';
			echo '
		 <div style="margin:3%;display:block;text-align:center;">
		 <input style="margin-right: 10px;background: linear-gradient(0deg,rgb(14 42 71) 0,rgb(26 69 138) 100%)!important;cursor: pointer;font-size:15px;border: none;font-size: 1.1em;padding:0.7rem 1.5rem;border-radius:5px;color: #FFF;cursor: pointer;text-decoration: none;"
		 type="button" value="Next" onclick="close_and_redirect_to_field_mapping();" >
		 </div>';
	}

			echo '
		 <script>
		window.opener.test_configuration_status()
		function close_and_redirect_to_field_mapping(){
			window.opener.redirect_to_field_mapping();
			self.close();
		} 

		function close_and_redirect_to_pardot_guide(){
			window.opener.redirect_to_pardot_guide();
			self.close();
		}

		function close_and_redirect_to_advance_sync(){
			window.opener.close_and_redirect_to_advance_sync();
			self.close();
		}
		</script> 
		 ';
}
