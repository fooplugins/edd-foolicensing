<?php
/**
 * EDD FooLicensing - Post Notifications
 * Date: 2013/03/28
 */
if (!class_exists('edd_foolic_post_relationships')) {

    class edd_foolic_post_relationships {

        const DOWNLOAD_TO_LICENSEKEY = 'download_to_licensekey';
        const DOWNLOAD_TO_LICENSE = 'download_to_license';

        function __construct() {
            add_action('foolic_p2p_register_connections', array('edd_foolic_post_relationships', 'register_connections'), 1);
        }

        public static function register_connections() {
            p2p_register_connection_type( array(
                'name' => self::DOWNLOAD_TO_LICENSEKEY,
                'from' => 'download',
                'to' =>  FOOLIC_CPT_LICENSE_KEY,
                'cardinality' => 'one-to-many',
                'admin_box' => array(
                    'show' => 'to',
                    'context' => 'side'
                )
            ) );

            p2p_register_connection_type( array(
                'name' => self::DOWNLOAD_TO_LICENSE,
                'from' => 'download',
                'to' =>  FOOLIC_CPT_LICENSE,
                'cardinality' => 'one-to-one',
                'admin_box' => array(
                    'context' => 'side'
                )
            ) );
        }
    }
}