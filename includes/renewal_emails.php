<?php
/**
 * EDD FooLicensing Renewal Emails
 * Date: 2013/12/19
 */
if ( !class_exists( 'edd_foolic_emails' ) ) {

	class edd_foolic_emails {

		function __construct() {

		}

		public function send_renewal_reminder($licensekey_id = 0, $force = false) {

			if ( empty($licensekey_id) ) {
				return;
			}

			if ( !foolic_get_option( 'enable_renewal_emails', false ) ) {
				return;
			}

			if ( !$force && get_post_meta( $licensekey_id, '_foolic_edd_renewal_sent', true ) == '1' ) {
				return; //already sent reminder
			}

			$licensekey = foolic_licensekey::get_by_id( $licensekey_id );

			if ( $licensekey->ID === 0 || $licensekey->is_deactivated() || !$licensekey->does_expire() ) {
				return;
			}

			if ( $licensekey->has_expired() ) {
				$subject = foolic_get_option( 'renewal_email_subject_expired' );
				$message = foolic_get_option( 'renewal_email_message_expired' );
			} else {
				$subject = foolic_get_option( 'renewal_email_subject' );
				$message = foolic_get_option( 'renewal_email_message' );
			}
			$user = $licensekey->get_user();

			$message = $this->filter_reminder_template_tags( $message, $licensekey, $user );
			$subject = $this->filter_reminder_template_tags( $subject, $licensekey, $user );

			$send_to_admin = foolic_get_option( 'send_renewals_to_admin', 'on' );
			$send_html = foolic_get_option( 'renewal_email_html' );

			$from_name  = get_bloginfo( 'name' );
			$from_email = foolic_get_option( 'renewal_email_address', get_bloginfo( 'admin_email' ) );

			if ( 'on' === $send_to_admin ) {
				$email_to = $from_email;
			} else {
				$email_to = $user->user_email;
			}

			$headers[] = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>";
			$headers[] = "Reply-To: " . $from_email;
			$headers[] = "Bcc: $from_email";

			if ( 'on' === $send_html ) {
				add_filter( 'wp_mail_content_type', array($this, 'set_html_content_type' ) );
			}

			if ( wp_mail( $email_to, $subject, $message, $headers ) ) {
				update_post_meta( $licensekey_id, '_foolic_edd_renewal_sent', '1' ); // Prevent renewal notices from being sent more than once
			}

			remove_filter( 'wp_mail_content_type', array($this, 'set_html_content_type' ) );
		}

		function set_html_content_type() {
			return 'text/html';
		}

		/**
		 * @param string            $text
		 * @param foolic_licensekey $licensekey
		 * @param WP_User           $user_info
		 *
		 * @return mixed|string
		 */
		function filter_reminder_template_tags($text = '', $licensekey, $user_info) {

			$download_id   = edd_foolic_find_download_id( $licensekey );
			$download_name = get_the_title( $download_id );
			$customer_name = $user_info->display_name;
			$license_key   = $licensekey->license_key;
			$expiry        = $licensekey->expires;
			$discount      = foolic_renewal_percentage( $licensekey );
			$renew_link    = add_query_arg( array('foolic_renewal_license_key' => $licensekey->ID), edd_get_checkout_uri() );
			$duration      = $licensekey->when_expires();

			$text = str_replace( '{duration}', $duration, $text );
			$text = str_replace( '{name}', $customer_name, $text );
			$text = str_replace( '{license_key}', $license_key, $text );
			$text = str_replace( '{download}', $download_name, $text );
			$text = str_replace( '{expiry}', $expiry, $text );
			$text = str_replace( '{discount}', $discount, $text );
			$text = str_replace( '{renewal_link}', $renew_link, $text );

			return $text;
		}
	}
}