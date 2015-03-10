<?php
/**
 * EDD FooLicensing Functions
 * Date: 2013/12/19
 */

/**
 * @param foolic_licensekey $licensekey
 *
 *  @returns mixed
 */
function edd_foolic_find_download_id( $licensekey ) {
	$download_id = get_post_meta( $licensekey->ID, 'edd_download_id', true );

	if ( empty($download_id) ) {

		//get the linked download
		$connected_downloads = get_posts(array(
			'connected_type' => edd_foolic_post_relationships::DOWNLOAD_TO_LICENSEKEY,
			'connected_items' => $licensekey->get_underlying_post(),
			'post_count' => 1,
			'suppress_filters' => false
		));
		if ($connected_downloads) {
			$connected_download = $connected_downloads[0];
			return $connected_download->ID;
		}
	}

	return $download_id;
}

/**
 * @param int $download_id
 * @param foolic_licensekey $licensekey
 *
 * @returns mixed
 */
function edd_foolic_find_price_id($licensekey, $download_id = 0) {
	if ( $download_id === 0) {
		$download_id = edd_foolic_find_download_id( $licensekey );
	}

	if ( edd_has_variable_prices( $download_id ) ) {
		$price_id = get_post_meta( $licensekey->ID, 'edd_price_id', true );
		if ( !empty($price_id) ) {
			return $price_id;
		}

		$license = $licensekey->get_license();

		$files = edd_get_download_files($download_id);

		foreach ($files as $key => $file_info) {
			$license_id = array_key_exists('foolicense', $file_info) ? absint( $file_info['foolicense'] ) : false;
			if ($license->ID == $license_id) {
				return $key;
			}
		}
	}
	return false;
}

/**
 * @param foolic_license $license
 *
 *  @returns mixed
 */
function edd_foolic_find_download_id_for_license( $license ) {
	//get the linked download
	$connected_downloads = get_posts(array(
		'connected_type' => edd_foolic_post_relationships::DOWNLOAD_TO_LICENSE,
		'connected_items' => $license->get_underlying_post(),
		'post_count' => 1,
		'suppress_filters' => false
	));
	if ($connected_downloads) {
		$connected_download = $connected_downloads[0];
		return $connected_download->ID;
	}
}

/**
 * @param int $download_id
 * @param foolic_license $license
 *
 * @returns mixed
 */
function edd_foolic_find_price_id_for_license($license, $download_id = 0) {
	if ( $download_id === 0) {
		$download_id = edd_foolic_find_download_id_for_license( $license );
	}

	if ( edd_has_variable_prices( $download_id ) ) {
		$files = edd_get_download_files($download_id);

		foreach ($files as $key => $file_info) {
			$license_id = array_key_exists('foolicense', $file_info) ? absint( $file_info['foolicense'] ) : false;
			if ($license->ID == $license_id) {
				return $key;
			}
		}
	}
	return false;
}

/**
 * Returns true if the cart contains a renewal
 * @return bool
 */
function edd_foolic_cart_contains_renewals() {
	$cart_items = edd_get_cart_contents();
	
	if ( $cart_items ) {
		foreach( $cart_items as $key => $item ) {
			if ( array_key_exists( 'edd_foolic_renewal_licensekey', $cart_items[$key]['options'] ) ) {
				return true;
			}
		}
	}
	return false;
}