<?php
/**
 * EDD FooLisencing License Key Manager
 * Date: 2013/03/27
 */
if (!class_exists('edd_foolic_licensekey_manager')) {

    class edd_foolic_licensekey_manager {

        function __construct() {
            // creates / stores a license during purchase
            add_action( 'edd_update_payment_status', array( $this, 'generate_license_key' ), 0, 3 );
        }

        /*
         * Generates ( if needed ) a license key for the buyer at time of purchase
         * This key will be used to activate all products for this purchase
         */
        function generate_license_key( $payment_id, $new_status, $old_status ) {

            if ( $old_status != 'publish' && $new_status != 'publish' )
                return; // this payment is not complete

            $purchase_details = edd_get_payment_meta_cart_details( $payment_id );
            $user_info = edd_get_payment_meta_user_info( $payment_id );
            $user_id = isset( $user_info['id'] ) && $user_info['id'] != -1 ? $user_info['id'] : 0;
            $user_identifier = $user_info['email'];
            $user_fullname = trim($user_info['first_name'] . ' ' . $user_info['last_name']);
			$discount = isset( $user_info['discount'] ) && $user_info['discount'] != '' && $user_info['discount'] != 'none' ? $user_info['discount'] : false;

            foreach( $purchase_details as $item ) {
                $download_id = absint($item['id']);
                $price_id = null;
                $price = $item['price'];

                //dealing with variable pricing
                if( ! empty( $item['item_number']['options'] ) ) {
                    $price_id = absint($item['item_number']['options']['price_id']);
                }

				$is_renewal = array_key_exists( 'edd_foolic_renewal_licensekey', $item['item_number']['options'] );

				if ( $is_renewal ) {
					$renewal_licensekey_id = $item['item_number']['options']['edd_foolic_renewal_licensekey'];

					foolic_perform_renewal( $renewal_licensekey_id );

					//add postmeta to the payment to mark it as a renewal
					add_post_meta( $payment_id, '_edd_foolic_is_renewal', '1' );

					//do not create a license key for a renewal!
					continue;
				}

				$is_upgrade = array_key_exists( 'edd_foolic_upgrade_license', $item['item_number']['options'] );

				if ( $is_upgrade ) {
					$licensekey_id = $item['item_number']['options']['edd_foolic_upgrade_licensekey'];
					$upgrade_license_id = $item['item_number']['options']['edd_foolic_upgrade_license'];

					foolic_perform_upgrade( $licensekey_id, $upgrade_license_id );

					//add postmeta to the payment to mark it as an upgrade
					add_post_meta( $payment_id, '_edd_foolic_is_upgrade', '1' );

					//do not create a license key for an upgrade
					continue;
				}

                $files = edd_get_download_files($download_id, $price_id);

                foreach ($files as $file) {
                    $license_id = absint( $file['foolicense'] );

                    //load the license
                    $license = foolic_get_license($license_id);

                    if ($license && $license->ID > 0 && $license->ID == $license_id) {
                        //valid license
                        $meta = array(
                            'payment_id' => $payment_id,
                            'download_id' => $download_id,
                            'price_id' => $price_id,
                            'file' => $file['name'],
                            'price' => $price
                        );

                        $meta_display = array(
                            'Customer' => $user_fullname,
                            'Email' => $user_identifier,
                            'Download' => $item['name'],
                            'Price' => edd_currency_filter($price)
                        );

						if ($discount !== false) {
							$meta_display['Discount Code'] = $discount;
						}

                        //generate and insert the key
                        $licensekey = $license->create_license_key($user_id, $user_identifier, $meta, $meta_display);

                        if ($licensekey !== false) {
                            //save some metadata for this license_key like the download_id and price_id
                            add_post_meta($licensekey->ID, 'edd_download_id', $download_id);
                            add_post_meta($licensekey->ID, 'edd_payment_id', $payment_id);
                            add_post_meta($licensekey->ID, 'edd_price_id', $price_id);

                            //link the licensekey to the EDD download
                            $this->link_licensekey_to_download($licensekey->ID, $download_id);

                            //call an action (use for logging)
                            do_action( 'edd_foolic_generate_license_key', $licensekey->ID, $download_id, $price_id, $payment_id );
                        }
                    }
                }
            }
        }

        function link_licensekey_to_download($licensekey_id, $download_id) {
            p2p_create_connection( edd_foolic_post_relationships::DOWNLOAD_TO_LICENSEKEY, array(
                'from' => $download_id,
                'to' => $licensekey_id
            ) );
        }
    }
}