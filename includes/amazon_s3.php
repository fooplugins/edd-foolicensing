<?php
/**
 * EDD FooLicensing Amazon S3 integration
 * Date: 2013/03/31
 */
if (!class_exists('edd_foolic_amazon_s3')) {

    class edd_foolic_amazon_s3 {

        function __construct() {
            add_filter('edd_foolic_proccess_download_file', array($this, 'proccess_download_file'));
        }

        function proccess_download_file($filename) {
            if (class_exists('EDD_Amazon_S3') && isset($GLOBALS['edd_s3'])) {
                return $GLOBALS['edd_s3']->get_s3_url($filename);
            }

			return $filename;
        }
    }
}