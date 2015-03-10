<?php
/**
 * EDD FooLicensing Download Metabox
 * Date: 2013/03/27
 */
if (!class_exists('edd_foolic_download_metabox')) {

    class edd_foolic_download_metabox {

        function __construct() {
            // alter the download file table to select a FooLicense
            add_action('edd_download_file_table_head', array($this, 'download_file_table_head'));
            add_filter('edd_file_row_args', array($this, 'download_file_row_args'), 10, 2);
            add_action('edd_download_file_table_row', array($this, 'download_file_table_row'), 10, 3);
            add_action('save_post', array($this, 'save_download'), 20);
        }

        function save_download($post_id) {
            global $post;

            if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || (defined('DOING_AJAX') && DOING_AJAX) || isset($_REQUEST['bulk_edit'])) return $post_id;

            if (isset($post->post_type) && $post->post_type != 'download') {
                return $post_id;
            }

            if (!current_user_can('edit_pages', $post_id)) {
                return $post_id;
            }

            //if we get this far then we are saving a EDD download

            //we need to loop through files and if we have mapped a file to a license then connect the license to the download
            $files = edd_get_download_files($post_id);

            foreach ($files as $file) {
                $license_id = absint($file['foolicense']);

                if ($license_id > 0) {
                    $this->link_license_to_download($license_id, $post_id);
                }
            }
        }

        function link_license_to_download($license_id, $download_id) {
            //check if the link exists already

            $p2p_id = p2p_type(edd_foolic_post_relationships::DOWNLOAD_TO_LICENSE)->get_p2p_id($download_id, $license_id);
            if (!$p2p_id) {
                p2p_create_connection(edd_foolic_post_relationships::DOWNLOAD_TO_LICENSE, array(
                    'from' => $download_id,
                    'to' => $license_id
                ));
            }
        }

        public function download_file_table_head() {
            ?>
            <th class="foolicense" style="width: 15%;"><?php _e('License', 'edd'); ?></th><?php
        }

        public function download_file_row_args($args, $file) {
            $foolicense = isset($file['foolicense']) ? $file['foolicense'] : '';
            $args['foolicense'] = $foolicense;
            return $args;
        }

        public function download_file_table_row($post_id, $key, $args) {
            extract($args, EXTR_SKIP);
            if (empty($foolicense)) {
                $foolicense = '';
            }
            ?>
            <td>
            <select class="edd_repeatable_name_field" name="edd_download_files[<?php echo $key; ?>][foolicense]"
                    id="edd_download_files[<?php echo $key; ?>][foolicense]">
                <option>--select--</option>
                <?php $foolicenses = foolic_get_license_posts();
                foreach ($foolicenses as $license) {
                    $selected = ($license->ID == $foolicense) ? 'selected="selected"' : '';
                    echo '<option ' . $selected . ' value="' . $license->ID . '">' . $license->post_title . '</option>';
                }
                ?>
            </select>
            </td><?php
        }
    }
}