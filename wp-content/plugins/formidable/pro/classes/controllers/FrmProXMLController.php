<?php

class FrmProXMLController{

    public static function import_default_templates($files) {
        $files[] = FrmAppHelper::plugin_path() .'/pro/classes/views/xml/default-templates.xml';
        return $files;
    }

    public static function route($continue, $action) {
        if ( $action == 'import_csv' ) {
            self::import_csv();
            $continue = false;
        }
        return $continue;
    }

    public static function importing_xml($imported, $xml) {
        if ( ! isset($xml->view) && ! isset($xml->item) ) {
            return $imported;
        }

        $append = array(
            'items' => 0,
        );
        $imported['updated'] = array_merge($imported['updated'], $append);
        $imported['imported'] = array_merge($imported['imported'], $append);
        unset($append);

	    // get entries
	    if ( isset($xml->item) ) {
            $imported = FrmProXMLHelper::import_xml_entries($xml->item, $imported);
	        unset($xml->item);
	    }

        return $imported;
    }

    public static function csv_instructions_1(){
        return __( 'Upload your Formidable XML or CSV file to import forms, entries, and views into this site. <br/><strong>Note: If your imported form/entry/view key and creation date match an item on your site, that item will be updated. You cannot undo this action.</strong>', 'formidable' );
    }

    public static function csv_instructions_2(){
        return __( 'Choose a Formidable XML or any CSV file', 'formidable' );
    }

    public static function csv_opts($forms) {
        $csv_del = FrmAppHelper::get_param('csv_del', ',');
        $form_id = FrmAppHelper::get_param('form_id');

        include(FrmAppHelper::plugin_path() .'/pro/classes/views/xml/csv_opts.php');
    }

    public static function xml_export_types($types) {
        $types['items'] = __( 'Entries', 'formidable' );
        $types['posts'] = __( 'Views', 'formidable' );
        $types['styles'] = __( 'Styles', 'formidable' );

        return $types;
    }

    public static function export_formats($formats) {
        $formats['csv'] = array( 'name' => 'CSV', 'support' => 'items', 'count' => 'single');
        $formats['xml']['support'] = 'forms|items|posts|styles';

        return $formats;
    }

    public static function export_csv($atts) {
        $form_ids = $atts['ids'];
        if ( empty($form_ids) ) {
            wp_die(__( 'Please select a form', 'formidable' ));
        }
        FrmProEntriesController::csv(reset($form_ids));
    }

    // map fields from csv
    public static function map_csv_fields() {
        $name = 'frm_import_file';

        if ( ! isset($_FILES) || ! isset($_FILES[$name]) || empty($_FILES[$name]['name']) || (int) $_FILES[$name]['size'] < 1) {
            return;
        }

        $file = $_FILES[$name]['tmp_name'];

        // check if file was uploaded
        if ( ! is_uploaded_file($file) ) {
            return;
        }

        if ( empty($_POST['form_id']) ) {
            $errors = array(__( 'All Fields are required', 'formidable' ));
            FrmXMLController::form($errors);
            return;
        }

        //upload
        $media_id = ( isset( $_POST[ $name ] ) && ! empty( $_POST[ $name ] ) && is_numeric( $_POST[ $name ] ) ) ? $_POST[ $name ] : FrmProAppHelper::upload_file( $name );
        if ( $media_id && ! is_wp_error( $media_id ) ) {
            $filename = get_attached_file($media_id);
        }

        if ( empty($filename) ) {
            $errors = array(__( 'That CSV was not uploaded. Are CSV files allowed on your site?', 'formidable' ));
            FrmXMLController::form($errors);
            return;
        }

        $headers = $example = '';
        $csv_del = FrmAppHelper::get_param('csv_del', ',');
        $form_id = FrmAppHelper::get_param('form_id');

        setlocale(LC_ALL, get_locale());
        if ( ( $f = fopen($filename, 'r') ) !== false ) {
            $row = 0;
            while ( ( $data = fgetcsv($f, 100000, $csv_del) ) !== false ) {
            //while (($raw_data = fgets($f, 100000))){
                $row++;
				if ( $row == 1 ) {
                    $headers = $data;
                } else if ( $row == 2 ) {
                    $example = $data;
				} else {
                    continue;
				}
            }
            fclose($f);
        } else {
            $errors = array(__( 'CSV cannot be opened.', 'formidable' ));
            FrmXMLController::form($errors);
            return;
        }

        $fields = FrmField::get_all_for_form($form_id);

        include(FrmAppHelper::plugin_path() .'/pro/classes/views/xml/map_csv_fields.php');
    }

    public static function import_csv() {
        //Import csv to entries
        $import_count = 250;
        $media_id = FrmAppHelper::get_param('frm_import_file');
        $current_path = get_attached_file($media_id);
        $row = FrmAppHelper::get_param('row');
        $csv_del = FrmAppHelper::get_param('csv_del', ',');
        $form_id = FrmAppHelper::get_param('form_id');

        $opts = get_option('frm_import_options');

        $left = ( $opts && isset($opts[$media_id]) ) ? ( (int) $row - (int) $opts[$media_id]['imported'] - 1 ) : ( $row - 1 );
        if ( $row < 300 && ( ! isset($opts[$media_id]) || $opts[$media_id]['imported'] < 300 ) ) {
            // if the total number of rows is less than 250
            $import_count = ceil($left/2);
        }

        if ( $import_count > $left ) {
            $import_count = $left;
        }

        $mapping = FrmAppHelper::get_param('data_array');
        $url_vars = "&csv_del=". urlencode($csv_del) ."&form_id={$form_id}&frm_import_file={$media_id}&row={$row}&max={$import_count}";

        foreach ( $mapping as $mkey => $map ) {
            $url_vars .= "&data_array[$mkey]=$map";
		}

        include(FrmAppHelper::plugin_path() .'/pro/classes/views/xml/import_csv.php');
    }

    public static function import_csv_entries() {
        check_ajax_referer( 'frm_ajax', 'nonce' );
        FrmAppHelper::permission_check('frm_create_entries');

        $opts = get_option('frm_import_options');
        if ( ! $opts ) {
            $opts = array();
        }

        $vars = $_POST;
        $file_id = $vars['frm_import_file'];
        $current_path = get_attached_file($file_id);
        $start_row = isset($opts[$file_id]) ? $opts[$file_id]['imported'] : 1;

        $imported = FrmProXMLHelper::import_csv($current_path, $vars['form_id'], $vars['data_array'], 0, $start_row+1, $vars['csv_del'], $vars['max']);

        $opts[$file_id] = array( 'row' => $vars['row'], 'imported' => $imported);
        echo $remaining = ( (int) $vars['row'] - (int) $imported );

        // check if the import is complete
        if ( ! $remaining ) {
            unset($opts[$file_id]);

            // since we are finished with this csv, delete it
            wp_delete_attachment($file_id, true);
        }

        update_option('frm_import_options', $opts);

        wp_die();
    }

}