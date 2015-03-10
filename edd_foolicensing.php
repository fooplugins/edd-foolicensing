<?php
/*
Plugin Name: Easy Digital Downloads - FooLicense Extension
Plugin URI: http://easydigitaldownloads.com/extension/foolicenses/
Description: EDD Licensing done right (with FooLicenses)
Version: 0.4.0
Author: Brad Vincent
Author URI:  http://fooplugins.com
*/

if ( ! defined( 'EDD_FOOLIC_PLUGIN_URL' ) ) {
    define( 'EDD_FOOLIC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if (!class_exists('EDD_FooLicensing')) {

    require_once 'includes/download_metabox.php';
    require_once 'includes/licensekey_manager.php';
    require_once 'includes/post_relationships.php';
    require_once 'includes/purchase_history.php';
    require_once 'includes/email_templates.php';
    require_once 'includes/update_package_url.php';
    require_once 'includes/amazon_s3.php';
	require_once 'includes/renewals.php';
	require_once 'includes/upgrades.php';
	require_once 'includes/functions.php';
	require_once 'includes/admin_settings.php';
	require_once 'includes/renewal_emails.php';

    class EDD_FooLicensing {

        public function __construct() {
            new edd_foolic_licensekey_manager();
            add_action('admin_init', array(&$this, 'admin_init'));
            new edd_foolic_post_relationships();
            new edd_foolic_purchase_history();
            new edd_foolic_email_templates();
            new edd_foolic_update_package_url();
            new edd_foolic_amazon_s3();
			new edd_foolic_renewals();
			new edd_foolic_upgrades();
			new edd_foolic_admin_settings();
        }

        /**
         * Run action and filter hooks in Admin
         *
         * @since 0.1
         *
         * @access private
         * @return void
         */
        function admin_init() {
            if (class_exists('foolicensing')) {
                new edd_foolic_download_metabox();
            }
        }

        function get_licensekey_by_purchase($purchase_id, $download_id, $price_id) {

			$query = array(
				array(
					'key' => 'edd_payment_id',
					'value' => $purchase_id
				),
				array(
					'key' => 'edd_download_id',
					'value' => $download_id
				)
			);

			if ($price_id !== false) {
				$query[] = array(
					'key' => 'edd_price_id',
					'value' => $price_id
				);
			}

            $args = array(
                'posts_per_page' => -1,
                'meta_query' => $query,
                'post_type' => FOOLIC_CPT_LICENSE_KEY
            );

			$licensekeys = get_posts($args);

            if ($licensekeys) {
                return foolic_licensekey::get($licensekeys[0]);
            }

            return false;
        }
    }
}

$GLOBALS['EDD_FooLicensing'] = new EDD_FooLicensing();