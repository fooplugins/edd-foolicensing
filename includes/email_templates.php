<?php
/**
 * EDD FooLicensing - Email template enchangements
 * Date: 2013/03/28
 */
if (!class_exists('edd_foolic_email_templates')) {

    class edd_foolic_email_templates {

        function __construct() {
            add_filter('edd_email_template_tags', array($this, 'email_template_tags'), 10, 3);
        }

        function email_template_tags($message, $payment_data, $payment_id) {

            $license_keys = '';

            $licensing = $GLOBALS['EDD_FooLicensing'];

            $downloads = edd_get_payment_meta_downloads($payment_id);

            if ($downloads) {
                foreach ($downloads as $download) {
                    $download_id = $download['id'];

					$download_title = get_the_title($download_id);

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

                    $licensekey = $licensing->get_licensekey_by_purchase($payment_id, $download_id, $price_id);
                    if ($licensekey) {
                        $license_keys .= $download_title . ": " . $licensekey->license_key . "\n\r";
                    }
                }
            }

            $message = str_replace('{license_keys}', $license_keys, $message);

            return $message;
        }
    }
}