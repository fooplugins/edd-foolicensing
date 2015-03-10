<?php
/**
 * EDD FooLicensing Admin Settings Class
 * Date: 2013/12/19
 */

if (!class_exists('edd_foolic_admin_settings')) {
	class edd_foolic_admin_settings {

		function __construct() {
			add_action('foolicensing_admin_settings', array($this, 'add_settings') );
		}

		/**
		 * @param foolicensing $foolic
		 */
		function add_settings($foolic) {
			$foolic->admin_settings_add_section_to_tab( 'renewals', 'renewal_emails', __( 'Renewal Emails', 'edd_foolic' ) );

			$foolic->admin_settings_add( array(
				'id'      => 'enable_renewal_emails',
				'title'   => __( 'Enable Renewal Emails', 'edd_foolic' ),
				'type'    => 'checkbox',
				'section' => 'renewal_emails',
				'tab'     => 'renewals'
			) );

			$foolic->admin_settings_add( array(
				'id'      => 'renewal_email_address',
				'title'   => __( 'Renewal Email From Address', 'edd_foolic' ),
				'type'    => 'text',
				'default' => get_bloginfo( 'admin_email' ),
				'section' => 'renewal_emails',
				'tab'     => 'renewals'
			) );

			$foolic->admin_settings_add( array(
				'id'      => 'send_renewals_to_admin',
				'title'   => __( 'Send Renewal Emails to Admin', 'foolicensing' ),
				'desc'	  => __( '(For testing purposes)', 'foolicensing' ),
				'type'    => 'checkbox',
				'default' => 'on',
				'section' => 'renewal_emails',
				'tab'     => 'renewals'
			) );

			$foolic->admin_settings_add( array(
				'id'      => 'renewal_email_html',
				'title'   => __( 'Send HTML Renewal Emails', 'foolicensing' ),
				'type'    => 'checkbox',
				'default' => 'on',
				'section' => 'renewal_emails',
				'tab'     => 'renewals'
			) );

			$foolic->admin_settings_add( array(
				'id'      => 'renewal_email_subject',
				'title'   => __( 'Renewal Email Subject', 'edd_foolic' ),
				'type'    => 'text',
				'default' => __( 'Your License Key Expires In {duration}', 'edd_foolic' ),
				'section' => 'renewal_emails',
				'tab'     => 'renewals'
			) );

			$foolic->admin_settings_add( array(
				'id'      => 'renewal_email_message',
				'title'   => __( 'Renewal Email Message', 'edd_foolic' ),
				'type'    => 'textarea',
				'default' => __( "Hi {name},\n\nYour license key for {download} is about to expire.\n\nTo renew your license right now and get a {discount}% discount, click the link below.\n\nYour license expires on: {expiry}.\nYour expiring license key is: {license_key}.\nRenew now: {renewal_link}.", 'edd_foolic' ),
				'section' => 'renewal_emails',
				'tab'     => 'renewals',
				'class'   => 'medium_textarea'
			) );

			$foolic->admin_settings_add( array(
				'id'      => 'renewal_email_subject_expired',
				'title'   => __( 'Expired Renewal Email Subject', 'edd_foolic' ),
				'type'    => 'text',
				'default' => __( 'Your License Key Has Expired!', 'edd_foolic' ),
				'section' => 'renewal_emails',
				'tab'     => 'renewals'
			) );

			$foolic->admin_settings_add( array(
				'id'      => 'renewal_email_message_expired',
				'title'   => __( 'Expired Renewal Email Message', 'edd_foolic' ),
				'type'    => 'textarea',
				'default' => __( "Hi {name},\n\nYour license key for {download} expired on {expiry}.\n\nTo renew your license right now and get a {discount}% discount, click the link below.\n\nYour expiring license key is: {license_key}.\nRenew now: {renewal_link}.", 'edd_foolic' ),
				'section' => 'renewal_emails',
				'tab'     => 'renewals',
				'class'   => 'medium_textarea'
			) );
		}
	}
}
