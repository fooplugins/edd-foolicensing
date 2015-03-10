<?php
/**
 * EDD FooLicensing - Get the update pacakge URL
 * Date: 2013/03/29
 */
if (!class_exists('edd_foolic_update_package_url')) {

    class edd_foolic_update_package_url {

        function __construct() {
            add_filter('foolic_license_generate_update_package_url', array($this, 'generate_update_package_url'), 10, 2);
            add_action('edd_foolic_package_download', array( $this, 'process_package_download' ) );
        }

        function generate_update_package_url($package, $license) {
            //get the download that is connected to the license
            $download = $this->get_download_by_license($license);

            if ($download !== false) {

                $files = edd_get_download_files($download->ID);

                //loop thru all files
                foreach( $files as $file_key => $file ) {
                    $license_id = absint( $file['foolicense'] );

                    if ($license_id > 0 && $license_id == $license->ID) {
                        //we have found our file download

                        $license_name = $license->slug; //get_the_title( $download->ID );
                        $hash = md5( $license_name . $file_key . $download->ID );

                        $package_url = add_query_arg( array(
                            'edd_action' 	=> 'foolic_package_download',
                            'id' 			=> $download->ID,
                            'lic'           => $license_id,
                            'key' 			=> $hash,
                            'expires'		=> rawurlencode( base64_encode( strtotime( $license->get_update_expiry_time() ) ) )
                        ), trailingslashit( home_url() ) );

                        return $package_url;
                    }
                }
            }

            return false;
        }

        function get_download_by_license($license) {

            $connected_download = get_posts(array(
                'connected_type' => edd_foolic_post_relationships::DOWNLOAD_TO_LICENSE,
                'connected_items' => $license->get_underlying_post(),
                'post_count' => 1,
                'post_status' => 'any',
                'suppress_filters' => false
            ));
            if ($connected_download) {
                return $connected_download[0];
            }

            return false;
        }

        function send_error_response($message, $code) {
            header($message, true, $code);
            exit;
        }

        function process_package_download() {

            if ( isset( $_GET['key'] ) && isset( $_GET['id'] ) && isset( $_GET['lic'] ) ) {

                $download_id = absint( urldecode( $_GET['id'] ) );
                $hash = urldecode( $_GET['key'] );
                $lic = absint( urldecode( $_GET['lic'] ) );

                $expires = urldecode( base64_decode( $_GET['expires'] ) );

                if ( time() > $expires )
                    $this->send_error_response( __( 'The download has expired', 'edd_folic' ), 400 );

                $license = foolic_get_license($lic);

                if ($license->ID > 0) {
                    //we have a valid license

                    $license_name = $license->slug;

                    $file_key = false;
                    $file_url = false;

                    $files = edd_get_download_files($download_id);

                    //loop thru all files
                    foreach( $files as $key => $file ) {
                        $license_id = absint( $file['foolicense'] );

                        if ($license_id > 0 && $license_id == $license->ID) {
                            //we have found our file download
                            $file_key = $key;
                            $file_url = $file['file'];
                            break;
                        }
                    }

                    $test_hash = md5( $license_name . $file_key . $download_id );

                    //if the hashes match then we can download the file
                    if ($hash === $test_hash) {
                        $this->download_file($file_url);
                    }
                }
            }

            $this->send_error_response( __( 'You do not have permission to download this file', 'edd_folic' ), 401 );
        }

        function download_file($file_url) {

            $requested_file = apply_filters('edd_foolic_proccess_download_file', $file_url);

            $ctype 	= apply_filters('edd_foolic_proccess_download_file_type', 'application/zip');

            if ( ! ini_get( 'safe_mode' ) ) {
                set_time_limit( 0 );
            }

            if ( function_exists( 'get_magic_quotes_runtime' ) && get_magic_quotes_runtime() ) {
                set_magic_quotes_runtime( 0 );
            }

            @session_write_close();
            if( function_exists( 'apache_setenv' ) ) @apache_setenv('no-gzip', 1);
            @ini_set( 'zlib.output_compression', 'Off' );

            nocache_headers();
            header("Robots: none");
            header("Content-Type: " . $ctype . "");
            header("Content-Description: File Transfer");
            header("Content-Disposition: attachment; filename=\"" . apply_filters( 'edd_requested_file_name', basename( $requested_file ) ) . "\";");
            header("Content-Transfer-Encoding: binary");
            header("Location: " . $requested_file );

            exit;
        }
    }
}