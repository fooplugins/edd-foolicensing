<?php
/**
 * EDD Foolicensing Renewals System
 * Date: 2013/12/19
 */
if ( !class_exists( 'edd_foolic_renewals' ) ) {

	class edd_foolic_renewals {

		private $_errors = array();

		function __construct() {
			add_action( 'template_redirect', array($this, 'listen_for_licensekey_renewal') );
			add_action( 'foolic_license_listing_item-expires', array($this, 'add_renewal_link'), 10, 2 );
			add_action( 'edd_pre_remove_from_cart', array($this, 'remove_renewal_fee_from_cart') );
			add_action( 'edd_daily_scheduled_events', array($this, 'scheduled_reminders' ) );
			add_filter( 'edd_is_discount_valid', array($this, 'override_cart_discount_valid_for_renewals'), 10, 4 );
			add_action( 'foolic_perform_renewal', array($this, 'remove_sent_renewal_flag' ) );
		}

		function remove_sent_renewal_flag($licensekey_id) {
			delete_post_meta( $licensekey_id, '_foolic_edd_renewal_sent' );
		}

		function set_error($error_id, $error_message) {
			$this->_errors[ $error_id ] = $error_message;
		}

		function print_errors() {
			if ( $this->_errors && count( $this->_errors ) > 0 ) {
				$classes = apply_filters( 'edd_error_class', array(
					'edd_errors'
				) );
				echo '<div class="' . implode( ' ', $classes ) . '">';
				// Loop error codes and display errors
				foreach ( $this->_errors as $error_id => $error ) {
					echo '<p class="edd_error" id="edd_error_' . $error_id . '"><strong>' . __( 'Error', 'edd' ) . '</strong>: ' . $error . '</p>';
				}
				echo '</div>';
			}
		}

		/**
		 * @param foolic_licensekey $key
		 * @param array             $valid
		 */
		function add_renewal_link($key, $valid) {
			if ( $key->expires !== 'never' ) {

				if (!$key->get_license()->renewals_disabled) {
					$urgent = $key->has_expired();

					$renew_link = add_query_arg( array('foolic_renewal_license_key' => $key->ID), edd_get_checkout_uri() );
					echo '<a class="foolic-action' . ($urgent ? ' foolic-action-urgent' : '') . '" href="' . $renew_link . '">'. __('Renew Now!', 'edd_foolic') . '</a>';
				}
			}
		}

		/**
		 * Removes and associated renewal discount fee from the cart when the item is removed
		 * @param $cartkey
		 */
		function remove_renewal_fee_from_cart($cartkey) {
			$cart = edd_get_cart_contents();
			if (is_array($cart) && array_key_exists($cartkey, $cart)) {
				$item = $cart[$cartkey];
				if (array_key_exists('options', $item)) {
					if (array_key_exists('edd_foolic_renewal_fee', $item['options'])) {
						$fee_key = $item['options']['edd_foolic_renewal_fee'];
						EDD()->fees->remove_fee( $fee_key );
					}
				}
			}
		}

		/**
		 * Intercept a renewal request and add the renewal fee to the cart
		 */
		function listen_for_licensekey_renewal() {
			if ( empty($_GET['foolic_renewal_license_key']) ) {
				return;
			}

			//make sure we are on the check out page
			if ( !function_exists( 'edd_is_checkout' ) || !edd_is_checkout() ) {
				return;
			}

			//make sure the user is logged in
			if ( !is_user_logged_in() ) {
				auth_redirect();
				return;
			}

			//remove any previous items from the cart
			edd_empty_cart();

			//get the license key id
			$licensekey_id = $_GET['foolic_renewal_license_key'];

			//get the key
			$licensekey = foolic_licensekey::get_by_id( $licensekey_id );

			//add any errors regarding the renewal
			$renewal_error = false;

			if ( $licensekey->ID === 0 ) {
				//if the license key does not exist then get out!
				$this->set_error( 'foolic_renewal_error_license', __( 'License Renewal Error : Invalid license!!', 'edd_foolic' ) );
				$renewal_error = true;
			}

			if (!$renewal_error) {
				$licensekey_user = $licensekey->get_user();

				//make sure the license key is for the currently logged in user
				if ( $licensekey_user === false || $licensekey_user->ID !== wp_get_current_user()->ID ) {
					$this->set_error( 'foolic_renewal_error_user', __( 'License Renewal Error : You may only renew licenses that belong to you!', 'edd_foolic' ) );
					$renewal_error = true;
				}

				//make sure the license key is not deactivated
				else if ( $licensekey->is_deactivated() ) {
					$this->set_error( 'foolic_renewal_error_deactivated', __( 'License Renewal Error : You cannot renew licenses that have been deactivated!', 'edd_foolic' ) );
					$renewal_error = true;
				}

				//make sure the license key expires
				else if ( !$licensekey->does_expire() ) {
					$this->set_error( 'foolic_renewal_error_not_expire', __( 'License Renewal Error : You do not need to renew licenses that do not expire!', 'edd_foolic' ) );
					$renewal_error = true;
				}

				$renewal_fee_key = 'edd_foolic_renewal_' . $licensekey_id;

				//gather some info needed for the cart
				$download_id = edd_foolic_find_download_id( $licensekey );
				$price_id = edd_foolic_find_price_id( $licensekey, $download_id );
				$cart_options = array(
					'edd_foolic_renewal_licensekey' => $licensekey_id,
					'edd_foolic_renewal_fee' => $renewal_fee_key
				);

				if ( $price_id !== false ) {
					$cart_options['price_id'] = $price_id;
					$download_price = edd_get_price_option_amount( $download_id, $price_id );
				} else {
					$download_price = edd_get_download_price( $download_id );
				}
			}

			if ( empty ( $download_id ) ) {

				$this->set_error( 'foolic_upgrade_error_download', __( 'License Upgrade Error : The download could not be found!', 'edd_foolic' ) );
				$renewal_error = true;
			}

			//we had no renewal errors so we can apply the discount
			if ( !$renewal_error ) {

				//add the item to the cart
				$cart_key = edd_add_to_cart( $download_id, $cart_options );

				if ( isset($cart_key) ) {

					//only add the discount fee once to the cart
					if ( EDD()->fees->get_fee( $renewal_fee_key ) === false ) {

						//apply renewal discount
						$discount_perc  = foolic_renewal_percentage( $licensekey );
						$download_price = floatval( $download_price );

						if ( $download_price > 0 && $discount_perc > 0 ) {
							$discount     = ($download_price * $discount_perc / 100) * -1;
							$discount     = number_format( $discount, 2, '.', '' );
							$renewal_name = '[ ' . $discount_perc . '% ' . __( 'Renewal Discount', 'edd_foolic' ) . ' ] - ' . $licensekey->get_license()->name;
							EDD()->fees->add_fee( $discount, $renewal_name, $renewal_fee_key );
						}
					}

				}
			}

			add_action( 'edd_cart_empty', array($this, 'print_errors') );

			if ( !empty($licensekey_id) && !empty($_GET['send_mail']) ) {
				$emails = new edd_foolic_emails();
				$emails->send_renewal_reminder( $licensekey_id, true );
			}
		}

		/**
		 * Override the cart discount when renewals are in the cart.
		 *
		 * @param $ret
		 *
		 * @return bool
		 */
		function override_cart_discount_valid_for_renewals($return, $discount_id, $code, $user) {

			if ( edd_foolic_cart_contains_renewals() ) {
				return false;
			}

			return $return;
		}

		function scheduled_reminders() {

			$keys = foolic_get_expiring_licensekeys();
			if( ! $keys )
				return;

			$emails = new edd_foolic_emails();

			foreach( $keys as $licensekey_id ) {
				$emails->send_renewal_reminder( $licensekey_id );
			}

		}

	}
}