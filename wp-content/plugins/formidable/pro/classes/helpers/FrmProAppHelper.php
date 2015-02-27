<?php

class FrmProAppHelper{

    /*
    * Get the Pro settings
    *
    * @since 2.0
    *
    * @param None
    * @return Object
    */
    public static function get_settings() {
        global $frmpro_settings;
        if ( empty($frmpro_settings) ) {
            $frmpro_settings = new FrmProSettings();
        }
        return $frmpro_settings;
    }

    /*
    * Get the current date in the display format
    * Used by [date] shortcode
    *
    * @since 2.0
    * @return string
    */
    public static function get_date( $format = '' ) {
        if ( empty($format) ) {
            $frmpro_settings = new FrmProSettings();
            $format = $frmpro_settings->date_format;
        }

        return date_i18n($format, strtotime(current_time('mysql')));
    }

    /*
    * Get the current time
    * Used by [time] shortcode
    *
    * @since 2.0
    * @return string
    */
    public static function get_time() {
        return date('H:i:s', strtotime(current_time('mysql')));
    }

    /*
    * Get a value from the current user profile
    *
    * @since 2.0
    * return string|array
    */
    public static function get_current_user_value($value, $return_array = false) {
        global $current_user;
        $new_value = isset($current_user->{$value}) ? $current_user->{$value} : '';
        if ( is_array($new_value) && ! $return_array ) {
            $new_value = implode(', ', $new_value);
        }

        return $new_value;
    }

    /*
    * Get the id of the current user
    * Used by [user_id] shortcode
    *
    * @since 2.0
    * @return string
    */
    public static function get_user_id() {
        $user_ID = get_current_user_id();
        return $user_ID ? $user_ID : '';
    }

    /*
    * Get a value from the currently viewed post
    *
    * @since 2.0
    * return string
    */
    public static function get_current_post_value($value) {
        global $post;
        if ( ! $post ) {
            return;
        }

        if ( isset($post->{$value}) ) {
            $new_value = $post->{$value};
        } else {
            $new_value = get_post_meta($post->ID, $value, true);
        }

        return $new_value;
    }

    /*
    * Get the email of the author of current post
    * Used by [post_author_email] shortcode
    *
    * @since 2.0
    * @return string
    */
    public static function get_post_author_email() {
        return get_the_author_meta('user_email');
    }

    public static function maybe_convert_to_db_date( $date_str, $to_format = 'Y-m-d' ) {
        $date_str = trim($date_str);
        $in_db_format = preg_match('/^\d{4}-\d{2}-\d{2}/', $date_str);

        if ( ! $in_db_format ) {
            $date_str = self::convert_date($date_str, 'db', $to_format);
        }

        return $date_str;
    }

    public static function maybe_convert_from_db_date( $date_str, $from_format = 'Y-m-d' ) {
        $date_str = trim($date_str);
        $in_db_format = preg_match('/^\d{4}-\d{2}-\d{2}/', $date_str);

        if ( $in_db_format ) {
            $date_str = self::convert_date($date_str, $from_format, 'db');
        }

        return $date_str;
    }

    public static function convert_date($date_str, $from_format, $to_format){
        if ( 'db' == $to_format ) {
            $frmpro_settings = new FrmProSettings();
            $to_format = $frmpro_settings->date_format;
        } else if ( 'db' == $from_format ) {
            $frmpro_settings = new FrmProSettings();
            $from_format = $frmpro_settings->date_format;
        }

        $base_struc     = preg_split("/[\/|.| |-]/", $from_format);
        $date_str_parts = preg_split("/[\/|.| |-]/", $date_str );

        $date_elements = array();

        $p_keys = array_keys( $base_struc );
        foreach ( $p_keys as $p_key ){
            if ( !empty( $date_str_parts[$p_key] ))
                $date_elements[$base_struc[$p_key]] = $date_str_parts[$p_key];
            else
                return false;
        }

        if(is_numeric($date_elements['m']))
            $dummy_ts = mktime(0, 0, 0, $date_elements['m'], (isset($date_elements['j']) ? $date_elements['j'] : $date_elements['d']), (isset($date_elements['Y']) ? $date_elements['Y'] : $date_elements['y']) );
        else
            $dummy_ts = strtotime($date_str);

        return date( $to_format, $dummy_ts );
    }

    public static function get_edit_link($id){
        $output = '';
    	if ( current_user_can('administrator') ) {
    		$output = '<a href="'. admin_url() .'?page=formidable-entries&frm_action=edit&id='. $id .'">'. __('Edit') .'</a>';
        }

    	return $output;
    }

    public static function rewriting_on(){
      $permalink_structure = get_option('permalink_structure');

      return ($permalink_structure and !empty($permalink_structure));
    }

    public static function current_url() {
        $pageURL = 'http';
        if (is_ssl()) $pageURL .= "s";
        $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "80")
            $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
        else
            $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];

        return $pageURL;
    }

    public static function get_permalink_pre_slug_uri(){
      preg_match('#^([^%]*?)%#', get_option('permalink_structure'), $struct);
      return $struct[1];
    }

    public static function get_custom_post_types(){
        $custom_posts = get_post_types(array(), 'object');
        foreach (array('revision', 'attachment', 'nav_menu_item') as $unset) {
            unset($custom_posts[$unset]);
        }
        return $custom_posts;
    }

    public static function get_custom_taxonomy($post_type, $field){
        $taxonomies = get_object_taxonomies($post_type);
        if(!$taxonomies){
            return false;
        }else{
            $field = (array) $field;
            if(!isset($field['taxonomy'])){
                $field['field_options'] = maybe_unserialize($field['field_options']);
                $field['taxonomy'] = $field['field_options']['taxonomy'];
            }

            if ( isset($field['taxonomy']) && in_array($field['taxonomy'], $taxonomies) ) {
                return $field['taxonomy'];
            } else if($post_type == 'post' ) {
                return 'category';
            } else {
                return reset($taxonomies);
            }
        }
    }

    public static function sort_by_array($array, $order_array){
        $array = (array) $array;
        $order_array = (array) $order_array;
        $ordered = array();
        foreach($order_array as $key){
            if(array_key_exists($key, $array)){
                $ordered[$key] = $array[$key];
                unset($array[$key]);
            }
        }
        return $ordered + $array;
    }


    public static function reset_keys($arr){
        $new_arr = array();
        if(empty($arr))
            return $new_arr;

        foreach($arr as $val){
            $new_arr[] = $val;
            unset($val);
        }
        return $new_arr;
    }

    public static function filter_where($entry_ids, $args){
        global $wpdb;

        $defaults = array(
            'where_opt' => false, 'where_is' => '=', 'where_val' => '',
            'form_id' => false, 'form_posts' => array(), 'after_where' => false,
            'display' => false, 'drafts' => 0,
        );

        $args = wp_parse_args($args, $defaults);

        if ( ! (int) $args['form_id'] || ! $args['where_opt'] || ! is_numeric($args['where_opt']) ) {
            return $entry_ids;
        }

        $where_field = FrmField::getOne($args['where_opt']);
        if ( ! $where_field ) {
            return $entry_ids;
        }

        self::prepare_where_args($args, $where_field, $entry_ids);

        $new_ids = array();
        self::filter_entry_ids( $args, $where_field, $entry_ids, $new_ids );

        unset($args['temp_where_is']);

        self::prepare_post_filter( $args, $where_field, $new_ids );

        if ( $args['after_where'] ) {
            //only use entries that are found with all wheres
            $entry_ids = array_intersect($new_ids, $entry_ids);
        } else {
            $entry_ids = $new_ids;
        }

        return $entry_ids;
    }

    /*
    * Called by the filter_where function
    */
    private static function prepare_where_args( &$args, $where_field, $entry_ids ) {
        if ( $args['where_val'] == 'NOW' ) {
            $args['where_val'] = self::get_date('Y-m-d');
        }

        if ( $where_field->type == 'date' && ! empty($args['where_val']) ) {
            $args['where_val'] = date('Y-m-d', strtotime($args['where_val']));
        } else if ( $args['where_is'] == '=' && $args['where_val'] != '' && FrmFieldsHelper::is_field_with_multiple_values( $where_field ) ) {
            if ( $where_field->type != 'data' || $where_field->field_options['data_type'] != 'checkbox' || is_numeric($args['where_val']) ) {
                // leave $args['where_is'] the same if this is a data from entries checkbox with a numeric value
                $args['where_is'] =  'LIKE';
            }
        }

        $args['temp_where_is'] = str_replace(array('!', 'not '), '', $args['where_is']);

        //get values that aren't blank and then remove them from entry list
        if ( $args['where_val'] == '' && $args['temp_where_is'] == '=' ) {
            $args['temp_where_is'] = '!=';
        }

        /*if($where_field->form_id != $args['form_id']){
            //TODO: get linked entry IDs and get entries where data field value(s) in linked entry IDs
        }*/

		$args['orig_where_val'] = $args['where_val'];
		if ( in_array( $args['where_is'], array('LIKE', 'not LIKE') ) ) {
             //add extra slashes to match values that are escaped in the database
            $args['where_val_esc'] = "'%". esc_sql(FrmAppHelper::esc_like(addslashes($args['where_val']))) ."%'";
            $args['where_val'] = "'%". esc_sql(FrmAppHelper::esc_like($args['where_val'])) ."%'";
        } else if ( ! strpos($args['where_is'], 'in') && !is_numeric( $args['where_val'] ) ) {
            $args['where_val_esc'] = "'". str_replace('\\', '\\\\\\', esc_sql($args['where_val'])) ."'";
            $args['where_val'] = "'". esc_sql($args['where_val']) ."'";
        }
        $filter_args = $args;
        $filter_args['entry_ids'] = $entry_ids;
        $args['where_val'] = apply_filters('frm_filter_where_val', $args['where_val'], $filter_args);

        self::prepare_dfe_text($args, $where_field);
    }

    /*
    * Filter by DFE text
    */
    private static function prepare_dfe_text( &$args, $where_field ) {
        if ( $where_field->type != 'data' || is_numeric($args['where_val']) || $args['orig_where_val'] == '' || ( isset($where_field->field_options['post_field']) && $where_field->field_options['post_field'] == 'post_category' ) ) {
            return;
        }

        global $wpdb;

		//Get entry IDs by DFE text
		if ( $args['where_is'] == 'LIKE' || $args['where_is'] == 'not LIKE' ) {
			$linked_id = FrmEntryMeta::search_entry_metas($args['orig_where_val'], $where_field->field_options['form_select'], $args['temp_where_is']);
		} else {
		    $cache_key = 'item_id_field_'. $where_field->field_options['form_select'] .'_value_'. $args['temp_where_is'] . $args['orig_where_val'];
		    $query = $wpdb->prepare('SELECT item_id FROM '. $wpdb->prefix .'frm_item_metas WHERE field_id=%d AND meta_value '. $args['temp_where_is'] .' %s', $where_field->field_options['form_select'], $args['orig_where_val']);
		    $linked_id = FrmAppHelper::check_cache($cache_key, 'frm_entry', $query, 'get_col');
			unset($cache_key);
		}

		//If text doesn't return any entry IDs, get entry IDs from entry key
		if ( ! $linked_id ) {
			$linked_field = FrmField::getOne($where_field->field_options['form_select']);
			$linked_id = $wpdb->get_col($wpdb->prepare('SELECT id FROM '. $wpdb->prefix .'frm_items WHERE form_id=%d AND item_key '. $args['temp_where_is'] .' %s', $linked_field->form_id, $args['where_val']));
		}

        if ( ! $linked_id ) {
            return;
        }

        //Change $args['where_val'] to linked entry IDs
		$linked_id = (array) $linked_id;
        if ( FrmFieldsHelper::is_field_with_multiple_values( $where_field ) ) {
			$args['where_val'] = "'%". implode("%' OR meta_value LIKE '%", $linked_id) ."%'";
			if ( in_array($args['where_is'], array('!=', 'not LIKE') ) ) {
				$args['temp_where_is'] = 'LIKE';
			} else if ( in_array($args['where_is'], array('=', 'LIKE') ) ) {
				$args['where_is'] = $args['temp_where_is'] = 'LIKE';
            }
		}else{
            $args['where_is'] = $args['temp_where_is'] = ( strpos($args['where_is'], '!') === false && strpos($args['where_is'], 'not') === false ) ? ' in ' : ' not in ';
            $args['where_val'] = '('. implode(',', $linked_id) .')';
        }
		unset($args['where_val_esc']);

		$args['where_val'] = apply_filters('frm_filter_dfe_where_val', $args['where_val'], $args);
    }

    private static function filter_entry_ids( $args, $where_field, $entry_ids, &$new_ids ) {
        $where_statement = '(meta_value '. ( in_array($where_field->type, array('number', 'scale')) ? ' +0 ' : '') . $args['temp_where_is'] .' '. $args['where_val'] .' ';
        if ( isset($args['where_val_esc']) && $args['where_val_esc'] != $args['where_val'] ) {
            $where_statement .= ' OR meta_value '. ( in_array($where_field->type, array('number', 'scale')) ? ' +0 ' : '') . $args['temp_where_is'] .' '. $args['where_val_esc'];
        }

        $where_statement .= ') and fi.id='. (int) $args['where_opt'];
        $args['entry_ids'] = $entry_ids;
        $where_statement = apply_filters('frm_where_filter', $where_statement, $args);

        $new_ids = FrmEntryMeta::getEntryIds($where_statement, '', '', true, array('is_draft' => $args['drafts']));

        if ( $args['where_is'] != $args['temp_where_is'] ) {
            $new_ids = array_diff($entry_ids, $new_ids);
        }
    }

    /*
    * if there are posts linked to entries for this form
    */
    private static function prepare_post_filter( $args, $where_field, &$new_ids ) {
        if ( empty($args['form_posts']) ) {
            // there are not posts related to this view
            return;
        }

        if ( ! isset($where_field->field_options['post_field']) || ! in_array($where_field->field_options['post_field'], array('post_category', 'post_custom', 'post_status', 'post_content', 'post_excerpt', 'post_title', 'post_name', 'post_date')) ) {
            // this is not a post field
            return;
        }

        $post_ids = array();
        foreach ( $args['form_posts'] as $form_post ) {
            $post_ids[$form_post->post_id] = $form_post->id;
            if ( ! in_array($form_post->id, $new_ids) ) {
                $new_ids[] = $form_post->id;
            }
        }

        if ( empty($post_ids) ) {
            return;
        }

        global $wpdb;

        if ( $where_field->field_options['post_field'] == 'post_category' ) {
            //check categories

            $args['temp_where_is'] = str_replace(array('!', 'not '), '', $args['where_is']);

            $join_with = ' OR ';
            $t_where = 't.term_id '. $args['temp_where_is'] .' '. $args['where_val'];
            $t_where .= ' '. $join_with .' t.slug '. $args['temp_where_is'] .' '. $args['where_val'];
            $t_where .= ' '. $join_with .' t.name '. $args['temp_where_is'] .' '. $args['where_val'];
            unset($args['temp_where_is']);

            $query = $wpdb->prepare('SELECT tr.object_id FROM '. $wpdb->terms .' AS t INNER JOIN '. $wpdb->term_taxonomy .' AS tt ON tt.term_id = t.term_id INNER JOIN '. $wpdb->term_relationships .' AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = %s', $where_field->field_options['taxonomy']) .' AND ('. $t_where .')';
            $add_posts = $wpdb->get_col($query);
            $add_posts = array_intersect($add_posts, array_keys($post_ids));

            if ( in_array($args['where_is'], array('!=', 'not LIKE') ) ) {
                $remove_posts = $add_posts;
                $add_posts = false;
            } else if ( ! $add_posts ) {
                $new_ids = array();
                return;
            }
        } else {

            if ( $where_field->field_options['post_field'] == 'post_custom' && $where_field->field_options['custom_field'] != '' ) {
                //check custom fields
                $cache_key = 'frmpostmeta_'. $where_field->field_options['custom_field'] . $args['where_is'] . $args['where_val'];
                $query = $wpdb->prepare('SELECT post_id FROM '. $wpdb->postmeta .' WHERE meta_key = %s AND meta_value ', $where_field->field_options['custom_field']);
            } else {
                //if field is post field
                $cache_key = 'frmpost_'. $where_field->field_options['post_field'] . $args['where_is'] . $args['where_val'];
                $query = 'SELECT ID FROM '. $wpdb->posts .' WHERE '. $where_field->field_options['post_field'];
            }

            $query .= ( in_array($where_field->type, array('number', 'scale')) ? ' +0 ' : ' ') . $args['where_is'] .' '. $args['where_val'];

            $add_posts = wp_cache_get($cache_key, 'frm_where');
            if ( false === $add_posts ) {
                $add_posts = $wpdb->get_col($query);
                $add_posts = array_intersect($add_posts, array_keys($post_ids));
                wp_cache_set($cache_key, $add_posts, 'frm_where', 300);
            }
        }

        if ( $add_posts && ! empty($add_posts) ) {
            $new_ids = array();
            foreach ( $add_posts as $add_post ) {
                if ( ! in_array($post_ids[$add_post], $new_ids) ) {
                    $new_ids[] = $post_ids[$add_post];
                }
            }
        }

        if ( isset($remove_posts) ) {
            if ( ! empty($remove_posts) ) {
                foreach ( $remove_posts as $remove_post ) {
                    $key = array_search($post_ids[$remove_post], $new_ids);
                    if ( $key && $new_ids[$key] == $post_ids[$remove_post] ) {
                        unset($new_ids[$key]);
                    }

                    unset($key);
                }
            }
        } else if ( ! $add_posts ) {
            $new_ids = array();
        }
    }

    /*
    * Let WordPress process the uploads
    */
    public static function upload_file($field_id){
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $media_ids = $errors = array();
        add_filter('upload_dir', array('FrmProAppHelper', 'upload_dir'));

        if(is_array($_FILES[$field_id]['name'])){
            foreach($_FILES[$field_id]['name'] as $k => $n){
                if(empty($n))
                    continue;

                $f_id = $field_id . $k;
                $_FILES[$f_id] = array(
                    'name'  => $n,
                    'type'  => $_FILES[$field_id]['type'][$k],
                    'tmp_name' => $_FILES[$field_id]['tmp_name'][$k],
                    'error' => $_FILES[$field_id]['error'][$k],
                    'size'  => $_FILES[$field_id]['size'][$k]
                );

                unset($k);
                unset($n);

                $media_id = media_handle_upload($f_id, 0);
                if (is_numeric($media_id))
                    $media_ids[] = $media_id;
                else
                    $errors[] = $media_id;
            }
        }else{
            $media_id = media_handle_upload($field_id, 0);
            if (is_numeric($media_id))
                $media_ids[] = $media_id;
            else
                $errors[] = $media_id;
        }

        remove_filter('upload_dir', array('FrmProAppHelper', 'upload_dir'));

        unset($media_id);

        if(empty($media_ids))
            return $errors;

        if(count($media_ids) == 1)
            $media_ids = reset($media_ids);

        return $media_ids;
    }

    //Upload files into "formidable" subdirectory
    public static function upload_dir($uploads){
        $relative_path = apply_filters('frm_upload_folder', 'formidable');
        $relative_path = untrailingslashit($relative_path);

        if(!empty($relative_path)){
            $uploads['path'] = $uploads['basedir'] .'/'. $relative_path;
            $uploads['url'] = $uploads['baseurl'] .'/'. $relative_path;
            $uploads['subdir'] = '/'. $relative_path;
        }

        return $uploads;
    }

    public static function get_rand($length){
        $all_g = "ABCDEFGHIJKLMNOPQRSTWXZ";
        $pass = "";
        srand((double)microtime()*1000000);
        for($i=0;$i<$length;$i++) {
            srand((double)microtime()*1000000);
            $pass .= $all_g[ rand(0, strlen($all_g) - 1) ];
        }
        return $pass;
    }

    /* Genesis Integration */
    public static function load_genesis(){
        // Add classes to view pagination
        add_filter('frm_pagination_class', 'FrmProAppHelper::gen_pagination_class');
        add_filter('frm_prev_page_label', 'FrmProAppHelper::gen_prev_label');
        add_filter('frm_next_page_label', 'FrmProAppHelper::gen_next_label');
        add_filter('frm_prev_page_class', 'FrmProAppHelper::gen_prev_class');
        add_filter('frm_next_page_class', 'FrmProAppHelper::gen_next_class');
    }

    public static function gen_pagination_class($class){
        $class .= ' archive-pagination pagination';
        return $class;
    }

    public static function gen_prev_label(){
        return apply_filters( 'genesis_prev_link_text', '&#x000AB;' . __( 'Previous Page', 'formidable' ) );
    }

    public static function gen_next_label(){
        return apply_filters( 'genesis_next_link_text', __( 'Next Page', 'formidable' ) . '&#x000BB;' );
    }

    public static function gen_prev_class($class){
        $class .= ' pagination-previous';
        return $class;
    }

    public static function gen_next_class($class){
        $class .= ' pagination-next';
        return $class;
    }

    public static function gen_dots_class($class){
        $class .= ' pagination-omission';
        return $class;
    }
    /* End Genesis */

    public static function import_csv($path, $form_id, $field_ids, $entry_key=0, $start_row=2, $del=',', $max=250) {
        _deprecated_function( __FUNCTION__, '1.07.05', 'FrmProXMLHelper::import_csv()' );
        return FrmProXMLHelper::import_csv($path, $form_id, $field_ids, $entry_key, $start_row, $del, $max);
    }

    public static function get_user_id_param($user_id){
        _deprecated_function( __FUNCTION__, '2.0', 'FrmAppHelper::get_user_id_param' );
        return FrmAppHelper::get_user_id_param($user_id);
    }

    public static function get_formatted_time($date, $date_format=false, $time_format=false){
        _deprecated_function( __FUNCTION__, '2.0', 'FrmAppHelper::get_formatted_time' );
        return FrmAppHelper::get_formatted_time($date, $date_format, $time_format);
    }

    public static function get_current_form_id(){
        _deprecated_function( __FUNCTION__, '2.0', 'FrmEntriesHelper::get_current_form_id' );
        return FrmEntriesHelper::get_current_form_id();
    }

    public static function get_shortcodes($content, $form_id){
        _deprecated_function( __FUNCTION__, '2.0', 'FrmFieldsHelper::get_shortcodes' );
        return FrmFieldsHelper::get_shortcodes($content, $form_id);
    }

    public static function human_time_diff( $from, $to = '' ) {
        _deprecated_function( __FUNCTION__, '2.0', 'FrmAppHelper::human_time_diff' );
        return FrmAppHelper::human_time_diff( $from, $to );
    }
}
