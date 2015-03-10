<?php
/**
 * EDD FooLicensing - Purchase History Table Enhancements
 * Date: 2013/03/28
 */
if (!class_exists('edd_foolic_purchase_history')) {

    class edd_foolic_purchase_history {

        function __construct() {

			add_action('edd_download_history_files',  array($this, 'add_file_license'), 10, 5 );
			add_action('edd_purchase_history_files',  array($this, 'add_file_license'), 10, 5 );

            //customize the reciept page
            add_action('edd_receipt_files',  array($this, 'receipt_files'), 10, 5 );
        }

        function receipt_files($filekey, $file, $download_id, $payment_id, $meta) {
            $licensing = $GLOBALS['EDD_FooLicensing'];

            $licensekey = $licensing->get_licensekey_by_purchase($payment_id, $download_id, $filekey);
            if ($licensekey) {
                echo '<li class="edd_license_key">';
                echo __('License Key', 'edd_foolic') . ' : <span>' . $licensekey->license_key . '</span>';
                echo '</li>';
            }
        }

		function add_file_license($filekey, $file, $download_id, $payment_id, $purchase_data) {
			$licensing = $GLOBALS['EDD_FooLicensing'];

			if ( !edd_has_variable_prices( $download_id ) ) {
				$filekey = false;
			}

			$licensekey = $licensing->get_licensekey_by_purchase($payment_id, $download_id, $filekey);

			if ($licensekey) {
				echo '<span class="edd_foolic_license_wrap">';
				echo '<img src="' . EDD_FOOLIC_PLUGIN_URL . 'img/key.png" />';
				echo '<span class="edd_foolic_license_key">&nbsp;' . $licensekey->license_key . '</span>';
				echo '</span>';
			}
		}

        function add_column_header() {
            echo '<th class="foolic_license_key">' . __('License Keys', 'foolic') . '</th>';
        }

        function add_row_data($payment_id, $purchase_data) {

            $downloads = edd_get_payment_meta_downloads($payment_id);

            $licensing = $GLOBALS['EDD_FooLicensing'];

            if ($downloads) :
                echo '<td class="foolic_license_key">';
                foreach ($downloads as $download) {

					$download_id = $download['id'];

					$download_title = get_the_title($download['id']);

					if ( edd_has_variable_prices( $download_id ) ) {
						$prices = edd_get_variable_prices( $download_id );
						$price_id = (isset($download['options']) && isset($download['options']['price_id'])) ? $download['options']['price_id'] : false;
						foreach ($prices as $key => $value) {
							if ($key == $price_id) {
								$download_title = $value['name'];
								break;
							}
						}
					}


                    $licensekey = $licensing->get_licensekey_by_purchase($payment_id, $download);
                    if ($licensekey) {
                        echo '<div class="edd_foolic_license_wrap">';
                        echo '<span class="edd_foolic_license_title">' . $download_title . '</span>&nbsp;';
                        echo '<a href="#" class="edd_foolic_show_key" title="' . __('Click to view license key', 'foolic') . '"><img src="' . EDD_FOOLIC_PLUGIN_URL . 'img/key.png" /></a>';
                        echo '<span class="edd_foolic_license_key" style="display:none;">' . $licensekey->license_key . '</span>';
                        echo '</div>';
                    }
                }
                echo '</td>';
            endif;
        }

        function add_history_css() {
            echo '<script type="text/javascript">jQuery(document).ready(function($){ $(".edd_foolic_show_key").on("click", function(e) {e.preventDefault();$(this).next().fadeToggle("fast");});});</script>';
            echo '<style>.edd_foolic_license_wrap { position: relative; } .edd_foolic_license_key { display:block; } .edd_foolic_show_key img { border-radius:none; box-shadow:none; }</style>';
        }
    }
}