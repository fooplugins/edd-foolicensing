<?php
/**
 * EDD Foolicensing Update Paths
 * Date: 2013/12/19
 */
if ( !class_exists( 'edd_foolic_upgrades' ) ) {

	class edd_foolic_upgrades {

		private $_errors = array();

		function __construct() {
			add_action( 'template_redirect', array($this, 'listen_for_licensekey_upgrade') );
			add_action( 'foolic_license_listing_item-limits', array($this, 'add_upgrade_links'), 10, 2 );
			add_action( 'edd_pre_remove_from_cart', array($this, 'remove_upgrade_fee_from_cart') );
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
		 * @param array             $attached_domains
		 */
		function add_upgrade_links($key, $attached_domains) {

			$urgent = $key->has_exceeded_domain_limit();

			$license = $key->get_license();

			if ($license->ID > 0) {

				$upgrades = $license->get_upgrade_paths();

				if ( $upgrades !== false && count($upgrades) > 0 ) {
					foreach ($upgrades as $upgrade) {
						$name = empty( $upgrade['name'] ) ? $upgrade['license']->post_title : $upgrade['name'];
						$upgrade_link = add_query_arg( array(
							'foolic_upgrade_license_key' => $key->ID,
							'foolic_upgrade_to' => $upgrade['license']->ID
						), edd_get_checkout_uri() );

						echo '<a class="foolic-action' . ($urgent ? ' foolic-action-urgent' : '') . '" href="' . $upgrade_link . '">'. __('Upgrade to ', 'edd_foolic') . $name . '</a>';
					}
				}
			}
		}

		/**
		 * Removes and associated upgrade discount fee from the cart when the item is removed
		 * @param $cartkey
		 */
		function remove_upgrade_fee_from_cart($cartkey) {
			$cart = edd_get_cart_contents();
			if (is_array($cart) && array_key_exists($cartkey, $cart)) {
				$item = $cart[$cartkey];
				if (array_key_exists('options', $item)) {
					if (array_key_exists('edd_foolic_upgrade_fee', $item['options'])) {
						$fee_key = $item['options']['edd_foolic_upgrade_fee'];
						EDD()->fees->remove_fee( $fee_key );
					}
				}
			}
		}

		/**
		 * Intercept a upgrade request and add the upgrade discount fee to the cart
		 */
		function listen_for_licensekey_upgrade() {
			if ( empty($_GET['foolic_upgrade_license_key']) || empty($_GET['foolic_upgrade_to']) ) {
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
			$licensekey_id = $_GET['foolic_upgrade_license_key'];

			//get the key
			$licensekey = foolic_licensekey::get_by_id( $licensekey_id );

			//get the license
			$license = $licensekey->get_license();

			//get the upgrade license id
			$upgrade_license_id = $_GET['foolic_upgrade_to'];

			//get the upgrade license
			$upgrade_license = foolic_license::get_by_id( $upgrade_license_id );

			$upgrade_fee_key = 'edd_foolic_upgrade_' . $upgrade_license_id;

			//find the current price of the existing license
			$download_id = edd_foolic_find_download_id( $licensekey );
			$price_id = edd_foolic_find_price_id( $licensekey, $download_id );

			if ( $price_id !== false ) {
				$download_price = edd_get_price_option_amount( $download_id, $price_id );
			} else {
				$download_price = edd_get_download_price( $download_id );
			}

			//find the current price of the upgrade license
			$upgrade_download_id = edd_foolic_find_download_id_for_license( $upgrade_license );
			$upgrade_price_id = edd_foolic_find_price_id_for_license(  $upgrade_license, $upgrade_download_id );

			$cart_options = array(
				'edd_foolic_upgrade_license' => $upgrade_license_id,
				'edd_foolic_upgrade_licensekey' => $licensekey_id,
				'edd_foolic_upgrade_fee' => $upgrade_fee_key
			);

			if ( $upgrade_price_id !== false ) {
				$cart_options['price_id'] = $upgrade_price_id;
				$upgrade_download_price = edd_get_price_option_amount( $download_id, $upgrade_price_id );
			} else {
				$upgrade_download_price = edd_get_download_price( $download_id );
			}

			//add any errors regarding the upgrade
			$upgrade_error = false;

			if ( $licensekey->ID === 0 ) {
				$this->set_error( 'foolic_upgrade_error_license', __( 'License Upgrade Error : The license could not be found!', 'edd_foolic' ) );
				//if the license key does not exist, then get out!
				$upgrade_error = true;

			} else if ( $upgrade_license->ID === 0 ) {

				$this->set_error( 'foolic_upgrade_error_upgrade_license', __( 'License Upgrade Error : The upgrade license could not be found!', 'edd_foolic' ) );
				//if the upgrade license does not exist, then get out!
				$upgrade_error = true;
			} else if ( empty ( $download_id ) ) {

				$this->set_error( 'foolic_upgrade_error_download', __( 'License Upgrade Error : The download could not be found!', 'edd_foolic' ) );
				$upgrade_error = true;
			}

			if ( !$upgrade_error ) {

				//make sure the upgrade is valid
				if ( !$license->check_can_upgrade_to_license( $upgrade_license->ID ) ) {
					$this->set_error( 'foolic_upgrade_error_invalid', __( 'License Upgrade Error : The upgrade path is invalid!', 'edd_foolic' ) );
					$upgrade_error = true;
				}

				$licensekey_user = $licensekey->get_user();

				//make sure the license key is for the currently logged in user
				if ( $licensekey_user === false || $licensekey_user->ID !== wp_get_current_user()->ID ) {
					$this->set_error( 'foolic_upgrade_error_user', __( 'License Upgrade Error : You may only upgrade licenses that belong to you!', 'edd_foolic' ) );
					$upgrade_error = true;
				}

				//make sure the license key is not deactivated
				else if ( $licensekey->is_deactivated() ) {
					$this->set_error( 'foolic_upgrade_error_deactivated', __( 'License Upgrade Error : You cannot upgrade licenses that have been deactivated!', 'edd_foolic' ) );
					$upgrade_error = true;
				}
			}

			//we had no upgrade errors so we can apply the discount
			if ( !$upgrade_error ) {

				//add item to the cart
				$cart_key = edd_add_to_cart( $download_id, $cart_options );

				if ( isset($cart_key) ) {
					//only add the discount fee once to the cart
					if ( EDD()->fees->get_fee( $upgrade_fee_key ) === false ) {

						$download_price = floatval( $download_price );
						$upgrade_download_price = floatval( $upgrade_download_price );

						if ( $download_price > 0 && $upgrade_download_price > 0 ) {
							$discount     = $download_price * -1;
							$discount     = number_format( $discount, 2, '.', '' );
							$upgrade_name = '[ ' . __( 'Upgrade Discount', 'edd_foolic' ) . ' ] - '  . __('Upgrade from','edd_foolic') . ' ' . $license->name;
							EDD()->fees->add_fee( $discount, $upgrade_name, $upgrade_fee_key );
						}
					}
				}
			}

			add_action( 'edd_cart_empty', array($this, 'print_errors') );

		}
	}
}