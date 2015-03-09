<?php

class FrmProFieldsHelper{

    public static function get_default_value($value, $field, $dynamic_default=true, $return_array=false){
        if (is_array(maybe_unserialize($value))) return $value;

        $prev_val = '';
        if($field and $dynamic_default){
            $field->field_options = maybe_unserialize($field->field_options);
            if ( isset($field->field_options['dyn_default_value']) && $field->field_options['dyn_default_value'] != '' ) {
                $prev_val = $value;
                $value = $field->field_options['dyn_default_value'];
            }
        }

        $shortcode_functions = array(
            'date'          => array('FrmProAppHelper', 'get_date'),
            'time'          => array('FrmProAppHelper', 'get_time'),
            'email'         => array('FrmProAppHelper', 'get_current_user_value'),
            'login'         => array('FrmProAppHelper', 'get_current_user_value'),
            'username'      => array('FrmProAppHelper', 'get_current_user_value'),
            'display_name'  => array('FrmProAppHelper', 'get_current_user_value'),
            'first_name'    => array('FrmProAppHelper', 'get_current_user_value'),
            'last_name'     => array('FrmProAppHelper', 'get_current_user_value'),
            'user_role'     => array('FrmProAppHelper', 'get_current_user_value'),
            'user_id'       => array('FrmProAppHelper', 'get_user_id'),
            'post_id'       => array('FrmProAppHelper', 'get_current_post_value'),
            'post_title'    => array('FrmProAppHelper', 'get_current_post_value'),
            'post_author_email' => 'get_the_author_meta',
            'ip'            => array('FrmAppHelper', 'get_ip_address'),
            'admin_email'   => array('FrmFieldsHelper', 'dynamic_default_values'),
            'siteurl'       => array('FrmFieldsHelper', 'dynamic_default_values'),
            'frmurl'        => array('FrmFieldsHelper', 'dynamic_default_values'),
            'sitename'      => array('FrmFieldsHelper', 'dynamic_default_values'),
        );

        $match_shortcodes = implode('|', array_keys($shortcode_functions));
        $match_shortcodes .= '|user_meta|post_meta|server|auto_id|get';
        preg_match_all( "/\[(". $match_shortcodes ."|get-(.?))\b(.*?)(?:(\/))?\]/s", $value, $matches, PREG_PATTERN_ORDER);

        if ( ! isset($matches[0]) ) {
            return do_shortcode($value);
        }

        $shortcode_atts = array(
            'email'         => 'user_email',
            'login'         => 'user_login',
            'username'      => 'user_login',
            'display_name'  => 'display_name',
            'first_name'    => 'user_firstname',
            'last_name'     => 'user_lastname',
            'user_role'     => 'roles',
            'post_id'       => 'ID',
            'post_title'    => 'post_title',
            'post_author_email' => 'user_email',
            'admin_email'   => 'admin_email',
            'siteurl'       => 'siteurl',
            'frmurl'        => 'frmurl',
            'sitename'      => 'sitename',
        );

        foreach ( $matches[1] as $match_key => $shortcode ) {
            $new_value = '';
            $atts = shortcode_parse_atts(stripslashes($matches[3][$match_key]));
            if ( isset($atts['return_array']) ) {
                $return_array = $atts['return_array'];
            }

            if ( isset($shortcode_functions[$shortcode]) ) {
                $new_value = call_user_func( $shortcode_functions[$shortcode], isset($shortcode_atts[$shortcode]) ? $shortcode_atts[$shortcode] : '' );
            } else {
                switch ( $shortcode ) {
                    case 'user_meta':
                        if ( isset($atts['key']) ) {
                            $new_value = FrmProAppHelper::get_current_user_value($atts['key'], false);
                        }
                    break;

                    case 'post_meta':
                        if ( isset($atts['key']) ) {
                            $new_value = FrmProAppHelper::get_current_post_value($atts['key']);
                        }
                    break;

                    case 'get':
                        $atts['prev_val'] = $prev_val;
                        $new_value = FrmFieldsHelper::dynamic_default_values( $shortcode, $atts, $return_array );
                    break;

                    case 'auto_id':
                        $last_entry = FrmProEntryMetaHelper::get_max($field);

                        if ( ! $last_entry && isset($atts['start']) ) {
                            $new_value = (int) $atts['start'];
                        } else {
                            $new_value = $last_entry + 1;
                        }
                    break;

                    case 'server':
                        if ( isset($atts['param']) ) {
                            $new_value = FrmAppHelper::get_server_value($atts['param']);
                        }
                    break;

                    default:
                        $val = $matches[0][$match_key];
                        $new_value = self::check_posted_item_meta( $val, $shortcode, $atts, $return_array );

                        // reverse compatability for [get-param] shortcode
                        if ( preg_match("/\[get-(.?)\b(.*?)?\]/s", $matches[0][$match_key]) ) {
                            $param = str_replace('[get-', '', $val);
                            if ( preg_match("/\[/s", $param) ) {
                                $val .= ']';
                            } else {
                                $param = trim($param, ']'); //only if is doesn't create an imbalanced []
                            }
                            $new_value = FrmFieldsHelper::process_get_shortcode( compact('param'), $return_array );
                        }
                    break;
                }
            }

            if ( is_array($new_value) ) {
                if ( count($new_value) === 1 ) {
                    $new_value = reset($new_value);
                }
                $value = $new_value;
            } else {
                $value = str_replace($matches[0][$match_key], $new_value, $value);
            }

            unset($new_value);
        }

        unset($matches);

        self::replace_field_id_shortcodes( $value, $return_array );
        self::do_shortcode( $value );
        self::maybe_force_array( $value, $field, $return_array );

        return $value;
    }

    /*
    * Check for shortcodes in default values but prevent the form shortcode from filtering
    *
    * @since 2.0
    */
    private static function do_shortcode( &$value ) {
        global $frm_vars;
        $frm_vars['skip_shortcode'] = true;
        if ( is_array($value) ) {
            foreach ( $value as $k => $v ) {
                $value[$k] = do_shortcode($v);
                unset($k, $v);
            }
        } else {
            $value = do_shortcode($value);
        }
        $frm_vars['skip_shortcode'] = false;
    }

    private static function replace_field_id_shortcodes( &$value, $return_array ) {
        if ( empty($value) ) {
            return;
        }

        if ( is_array($value) ) {
            foreach ( $value as $k => $v ) {
                self::replace_each_shortcode( $v, $return_array );
                $value[$k] = $v;
                unset($k, $v);
            }
        } else {
            self::replace_each_shortcode( $value, $return_array );
        }
    }

    private static function replace_each_shortcode( &$value, $return_array ) {
        preg_match_all( "/\[(\d*)\b(.*?)(?:(\/))?\]/s", $value, $matches, PREG_PATTERN_ORDER);
        if ( ! isset($matches[0]) ) {
            return;
        }

        foreach ( $matches[0] as $match_key => $val ) {
            $shortcode = $matches[1][$match_key];
            if ( ! is_numeric($shortcode) || ! isset($_REQUEST) || ! isset($_REQUEST['item_meta']) ) {
                continue;
            }

            $new_value = FrmAppHelper::get_param('item_meta['. $shortcode .']', false, 'post');
            if ( ! $new_value && isset($atts['default']) ) {
                $new_value = $atts['default'];
            }

            if ( is_array($new_value) && ! $return_array ) {
                $new_value = implode(', ', $new_value);
            }

            if ( is_array($new_value) ) {
                $value = $new_value;
            } else {
                $value = str_replace($val, $new_value, $value);
            }
        }
    }

    /*
    * If this default value should be an array, we will make sure it is
    *
    * @since 2.0
    */
    private static function maybe_force_array( &$value, $field, $return_array ) {
        if ( ! $return_array || is_array($value) || strpos($value, ',') === false ) {
            // this is already in the correct format
            return;
        }

		//If checkbox, multi-select dropdown, or checkbox data from entries field and default value has a comma
		if ( FrmFieldsHelper::is_field_with_multiple_values( $field ) && ( $field->type == 'data' || ! in_array($value, $field->options) ) ) {
			//If the default value does not match any options OR if data from entries field (never would have commas in values), explode to array
			$value = explode(',', $value);
		}
    }

    private static function check_posted_item_meta( $val, $shortcode, $atts, $return_array ) {
        if ( ! is_numeric($shortcode) || ! isset($_REQUEST) || ! isset($_REQUEST['item_meta']) ) {
            return $val;
        }

        //check for posted item_meta
        $new_value = FrmAppHelper::get_param('item_meta['. $shortcode .']', false, 'post');

        if ( ! $new_value && isset($atts['default']) ) {
            $new_value = $atts['default'];
        }

        if ( is_array($new_value) && ! $return_array ) {
            $new_value = implode(', ', $new_value);
        }

        return $new_value;
    }

    /*
    * Get the input name and id
    * Called when loading a dynamic DFE field
    * @since 2.0
    */
    public static function get_html_id_from_container(&$field_name, &$html_id, $field, $hidden_field_id) {
        $id_parts = explode('-', str_replace('_container', '', $hidden_field_id));
        $plus = ( count($id_parts) == 3 ) ? '-' . end($id_parts) : ''; // this is in a sub field
        $html_id = FrmFieldsHelper::get_html_id($field, $plus);
        if ( $plus != '' ) {
            // get the name for the sub field
            $field_name .= '['. $id_parts[1] .']['. end($id_parts) .']';
        }
        $field_name .= '['. $field['id'] .']';
    }

    public static function setup_new_field_vars($values){
        $values['field_options'] = maybe_unserialize($values['field_options']);
        $defaults = self::get_default_field_opts($values);

        foreach ($defaults as $opt => $default)
            $values[$opt] = (isset($values['field_options'][$opt])) ? $values['field_options'][$opt] : $default;

        unset($defaults);

        if ( ! empty($values['hide_field']) && ! is_array($values['hide_field']) ) {
            $values['hide_field'] = (array) $values['hide_field'];
        }

        return $values;
    }

    public static function setup_new_vars($values, $field){
        $values['use_key'] = false;

        foreach ( self::get_default_field_opts($values, $field) as $opt => $default ) {
            $values[$opt] = (isset($field->field_options[$opt]) && $field->field_options[$opt] != '') ? $field->field_options[$opt] : $default;
            unset($opt, $default);
        }

        $values['hide_field'] = (array) $values['hide_field'];
        $values['hide_field_cond'] = (array) $values['hide_field_cond'];
        $values['hide_opt'] = (array) $values['hide_opt'];

        if ($values['type'] == 'data' && in_array($values['data_type'], array('select', 'radio', 'checkbox')) && is_numeric($values['form_select'])){
            $check = self::check_data_values($values);

            if ( $check ) {
                $values['options'] = self::get_linked_options($values, $field);
            } else if ( is_numeric($values['value']) ) {
                $values['options'] = array($values['value'] => FrmEntryMeta::get_entry_meta_by_field($values['value'], $values['form_select']));
            }
            unset($check);
        }else if ($values['type'] == 'scale'){
            $values['minnum'] = 1;
            $values['maxnum'] = 10;
        }else if ($values['type'] == 'date'){
            $values['value'] = FrmProAppHelper::maybe_convert_from_db_date($values['value'], 'Y-m-d');
        }else if($values['type'] == 'time'){
            $values['options'] = self::get_time_options($values);
        } else if ( $values['type'] == 'user_id' && FrmAppHelper::is_admin() && current_user_can('frm_edit_entries') && ! FrmAppHelper::is_admin_page('formidable') ) {
            if ( self::field_on_current_page($field) ) {
                $user_ID = get_current_user_id();
                $values['type'] = 'select';
                $values['options'] = self::get_user_options();
                $values['use_key'] = true;
                $values['custom_html'] = FrmFieldsHelper::get_default_html('select');
                $values['value'] = ($_POST and isset($_POST['item_meta'][$field->id])) ? $_POST['item_meta'][$field->id] : $user_ID;
            }
        }else if(!empty($values['options'])){
            foreach($values['options'] as $val_key => $val_opt){
                if(is_array($val_opt)){
                    foreach($val_opt as $opt_key => $opt){
                        $values['options'][$val_key][$opt_key] = self::get_default_value($opt, $field, false);
                        unset($opt_key);
                        unset($opt);
                    }
                }else{
                   $values['options'][$val_key] = self::get_default_value($val_opt, $field, false);
                }
                unset($val_key);
                unset($val_opt);
            }
        }

        if($values['post_field'] == 'post_category'){
            $values['use_key'] = true;
            $values['options'] = self::get_category_options($values);
            if ( $values['type'] == 'data' && $values['data_type'] == 'select' && ( ! $values['multiple'] || $values['autocom'] ) ) {
                // add a blank option
                $values['options'] = array('' => '') + (array) $values['options'];
            }
        }else if($values['post_field'] == 'post_status'){
            $values['use_key'] = true;
            $values['options'] = self::get_status_options($field);
        }

        if(is_array($values['value'])){
            foreach($values['value'] as $val_key => $val)
                $values['value'][$val_key] = apply_filters('frm_filter_default_value', $val, $field, false);
        }else if(!empty($values['value'])){
            $values['value'] = apply_filters('frm_filter_default_value', $values['value'], $field, false);
        }

        self::setup_conditional_fields($values);

        return $values;
    }

    public static function setup_edit_vars($values, $field, $entry_id=false){
        $values['use_key'] = false;

        foreach (self::get_default_field_opts($values, $field) as $opt => $default){
            $values[$opt] = ($_POST and isset($_POST['field_options'][$opt.'_'.$field->id]) ) ? stripslashes_deep($_POST['field_options'][$opt.'_'.$field->id]) : (isset($field->field_options[$opt]) ? $field->field_options[$opt]: $default);
        }

        $values['hide_field'] = (array) $values['hide_field'];
        $values['hide_field_cond'] = (array) $values['hide_field_cond'];
        $values['hide_opt'] = (array) $values['hide_opt'];

        if ( $values['type'] == 'data' && in_array($values['data_type'], array('select', 'radio', 'checkbox')) && is_numeric($values['form_select']) ) {
            $check = self::check_data_values($values);

            if ( $check ) {
                $values['options'] = self::get_linked_options($values, $field, $entry_id);
            } else if ( is_numeric($values['value']) ) {
                $values['options'] = array($values['value'] => FrmEntryMeta::get_entry_meta_by_field($values['value'], $values['form_select']));
            }
            unset($check);
        } else if ( $values['type'] == 'date' ) {
            $to_format = preg_match('/^\d{4}-\d{2}-\d{2}$/', $values['value']) ? 'Y-m-d' : 'Y-m-d H:i:s';
            $values['value'] = FrmProAppHelper::maybe_convert_from_db_date($values['value'], $to_format);
        }else if ($values['type'] == 'file'){
            //if (isset($_POST)) ???
            if($values['post_field'] != 'post_custom'){
                $values['value'] = FrmEntryMeta::get_entry_meta_by_field($entry_id, $values['id']);
            }
        } else if ( $values['type'] == 'hidden' && FrmAppHelper::is_admin() && current_user_can('administrator') && ! FrmAppHelper::is_admin_page('formidable') ) {
            if ( self::field_on_current_page($field) ) {
                $values['type'] = 'text';
                $values['custom_html'] = FrmFieldsHelper::get_default_html('text');
            }
        } else if ( $values['type'] == 'time' ) {
            $values['options'] = self::get_time_options($values);
        } else if ( $values['type'] == 'user_id' && FrmAppHelper::is_admin() && current_user_can('frm_edit_entries') && ! FrmAppHelper::is_admin_page('formidable') ) {
            if ( self::field_on_current_page($field) ) {
                $values['type'] = 'select';
                $values['options'] = self::get_user_options();
                $values['use_key'] = true;
                $values['custom_html'] = FrmFieldsHelper::get_default_html('select');
            }
        }else if($values['type'] == 'tag'){
            if(empty($values['value'])){
                self::tags_to_list($values, $entry_id);
            }
        } else if ( ! empty($values['options']) && ( ! FrmAppHelper::is_admin() || ! FrmAppHelper::is_admin_page('formidable') ) ) {
            foreach($values['options'] as $val_key => $val_opt){
                if(is_array($val_opt)){
                    foreach($val_opt as $opt_key => $opt){
                        $values['options'][$val_key][$opt_key] = self::get_default_value($opt, $field, false);
                        unset($opt_key, $opt);
                    }
                }else{
                   $values['options'][$val_key] = self::get_default_value($val_opt, $field, false);
                }
                unset($val_key, $val_opt);
            }
        }

        if($values['post_field'] == 'post_category'){
            $values['use_key'] = true;
            $values['options'] = self::get_category_options($values);
            if ( $values['type'] == 'data' && $values['data_type'] == 'select' && ( ! $values['multiple'] || $values['autocom'] ) ) {
                $values['options'] = array('' => '') + (array) $values['options'];
            }
        }else if($values['post_field'] == 'post_status'){
            $values['use_key'] = true;
            $values['options'] = self::get_status_options($field);
        }

        self::setup_conditional_fields($values);

        return $values;
    }

    public static function tags_to_list(&$values, $entry_id) {
        global $wpdb;
        $post_id = $wpdb->get_var($wpdb->prepare('SELECT post_id FROM '. $wpdb->prefix .'frm_items WHERE id=%d', $entry_id));
        if ( ! $post_id ) {
            return;
        }

        $tags = get_the_terms( $post_id, $values['taxonomy'] );
        if ( empty($tags) ) {
            $values['value'] = '';
            return;
        }

        $names = array();
        foreach ( $tags as $tag ) {
            $names[] = $tag->name;
        }

        $values['value'] = implode(', ', $names);
    }

    public static function get_default_field_opts($values=false, $field=false){
        $minnum = 1;
        $maxnum = 10;
        $step = 1;
        $align = 'block';
        $show_hide = 'show';
        if($values){
            switch ($values['type']){
                case 'number':
                    $minnum = 0;
                    $maxnum = 9999;
                    $step = '.01';
                break;
                case 'scale':
                    if($field){
                        $range = maybe_unserialize($field->options);
                        $minnum = $range[0];
                        $maxnum = end($range);
                    }
                break;
                case 'time':
                    $step = 30;
                break;
                case 'radio':
                    $align = FrmStylesController::get_style_val('radio_align', ($field ? $field->form_id : 'default'));
                break;
                case 'checkbox':
                    $align = FrmStylesController::get_style_val('check_align', ($field ? $field->form_id : 'default'));
                break;
                case 'break':
                    $show_hide = 'hide';
                break;
            }
        }
        $end_minute = 60 - (int) $step;

        $frm_settings = FrmAppHelper::get_settings();

        $opts = array(
            'slide' => 0, 'form_select' => '', 'show_hide' => $show_hide, 'any_all' => 'any', 'align' => $align,
            'hide_field' => array(), 'hide_field_cond' =>  array('=='), 'hide_opt' => array(), 'star' => 0,
            'post_field' => '', 'custom_field' => '', 'taxonomy' => 'category', 'exclude_cat' => 0, 'ftypes' => array(),
            'data_type' => 'select', 'restrict' => 0, 'start_year' => 2000, 'end_year' => 2020, 'read_only' => 0,
            'admin_only' => '', 'locale' => '', 'attach' => false, 'minnum' => $minnum, 'maxnum' => $maxnum,
            'step' => $step, 'clock' => 12, 'start_time' => '00:00', 'end_time' => '23:'.$end_minute,
            'unique' => 0, 'use_calc' => 0, 'calc' => '',
            'dyn_default_value' => '', 'multiple' => 0, 'unique_msg' => $frm_settings->unique_msg, 'autocom' => 0,
            'format' => '', 'repeat' => 0, 'add_label' => __('Add', 'formidable'), 'remove_label' => __('Remove', 'formidable'),
            'conf_field' => '', 'conf_input' => '', 'conf_desc' => '',
            'conf_msg' => __('The entered values do not match', 'formidable'), 'other' => 0,
        );

        $opts = apply_filters('frm_default_field_opts', $opts, $values, $field);
        unset($values);
        unset($field);

        return $opts;
    }

    public static function check_data_values($values){
        $check = true;
        if ( ! empty($values['hide_field']) && ( ! empty($values['hide_opt']) || ! empty($values['form_select']) ) ) {
            foreach ( $values['hide_field'] as $hkey => $f ) {
                if ( ! empty($values['hide_opt'][$hkey]) ) {
                    continue;
                }
                $f = FrmField::getOne($f);
                if ( $f && $f->type == 'data' ) {
                    $check = false;
                    break;
                }
                unset($f, $hkey);
            }
        }

        return $check;
    }

    public static function setup_input_masks($field) {
        if ( ! isset($field['format']) || empty($field['format']) || strpos($field['format'], '^') === 0 ) {
            return;
        }
        global $frm_input_masks;
        if ( ! isset($frm_input_masks[$field['id']]) ) {
            $frm_input_masks[$field['id']] = preg_replace('/\d/', '9', $field['format']);
        }
    }

    public static function setup_conditional_fields($field){
        if ( FrmAppHelper::is_admin_page('formidable') ) {
            return;
        }

        global $frm_vars;

        //conditional rules only once on the page
        if ( FrmAppHelper::doing_ajax() && ( ! isset($frm_vars['footer_loaded']) || $frm_vars['footer_loaded'] !== true ) ) {
            return;
        }

        //don't continue if the field has no conditiona
        if ( empty($field['hide_field']) || ( empty($field['hide_opt']) && empty($field['form_select']) ) ) {
            return;
        }

        $conditions = array();

        if ( ! isset($field['show_hide']) ) {
            $field['show_hide'] = 'show';
        }

        if ( ! isset($field['any_all']) ) {
            $field['any_all'] = 'any';
        }

        foreach ( $field['hide_field'] as $i => $cond ) {
            if ( ! is_numeric($cond) ) {
                continue;
            }

            $parent_field = FrmField::getOne($cond);

            if ( ! $parent_field ) {
                continue;
            }

            $parent_opts = maybe_unserialize($parent_field->field_options);

            if ( empty($conditions) ) {
                foreach ( $field['hide_field'] as $i2 => $cond2 ) {
                    if ( ! is_numeric($cond2) ) {
                        continue;
                    }

                    $sub_opts = array();
                    if ( (int) $cond2 == (int) $parent_field->id ) {
                        $sub_field = $parent_field;
                        $sub_opts = $parent_opts;
                    } else {
                        $sub_field = FrmField::getOne($cond2);
                        if ( $sub_field ) {
                            $sub_opts = maybe_unserialize($sub_field->field_options);
                        }
                    }

                    $field['org_type'] = $field['type'];
                    if ( $sub_field->type == 'data' && $field['type'] == 'hidden' ) {
                        $org_field = FrmField::getOne($field['id']);
                        $field['org_type'] = $org_field->type;
                        unset($org_field);
                    }

                    $condition = array('FieldName' => $sub_field->id, 'Condition' => $field['hide_field_cond'][$i2]);

                    if ( $sub_field->type == 'data' && $field['org_type'] == 'data' && ( is_numeric($field['form_select']) || $field['form_select'] == 'taxonomy') ) {
                        $condition['LinkedField'] = $field['form_select'];
                        $condition['DataType'] = empty($field['data_type']) ? 'data' : $field['data_type'];
                    }

                    if ( isset($field['hide_opt']) && ( ! empty($field['hide_opt'][$i2]) || $field['hide_opt'][$i2] == 0) ) {
                        $condition['Value'] = str_replace('"', '&quot;', self::get_default_value($field['hide_opt'][$i2], FrmField::getOne($field['id']), false ));
                    }

                    if ( $sub_field->type == 'scale' ) {
                        $sub_field->type = 'radio';
                    }
                    $condition['Type'] = $sub_field->type . (($sub_field->type == 'data') ? '-'. $sub_opts['data_type'] : '');
                    $conditions[] = $condition;
                }
            }

            $rule = array('Show' => $field['show_hide'], 'MatchType' => $field['any_all']);

            $rule['Setting'] = array('FieldName' => $field['id']);

            $rule['Conditions'] = $conditions;

            if ( ! isset($frm_vars['rules']) || ! $frm_vars['rules'] ) {
                $frm_vars['rules'] = array();
            }

            if ( ! isset($frm_vars['rules'][$parent_field->id]) ) {
                $frm_vars['rules'][$parent_field->id] = array();
            }

            // If field has confirmation field, add script for confirmation field as well
            if ( isset( $field['conf_field'] ) && !empty( $field['conf_field'] ) ) {
                $conf_rule = $rule;
                $conf_rule['Setting']['FieldName'] = 'conf_' . $conf_rule['Setting']['FieldName'];
                $frm_vars['rules'][$parent_field->id][] = $conf_rule;
            }

            $included = false;
            foreach ( $frm_vars['rules'][$parent_field->id] as $checked_cond ) {
                // this condition is already included
                if ( $checked_cond == $rule ) {
                    $included = true;
                }
                unset($checked_cond);
            }

            if ( ! $included ) {
                $frm_vars['rules'][$parent_field->id][] = $rule;
            }

            unset($rule, $parent_field, $i, $cond);
        }
    }

    public static function get_category_options($field){
        $field = (array) $field;
        $post_type = FrmProFormsHelper::post_type($field['form_id']);
        if ( ! isset($field['exclude_cat']) ) {
            $field['exclude_cat'] = 0;
        }

        $exclude = (is_array($field['exclude_cat'])) ? implode(',', $field['exclude_cat']) : $field['exclude_cat'];
        $exclude = apply_filters('frm_exclude_cats', $exclude, $field);

        $args = array(
            'orderby' => 'name', 'order' => 'ASC', 'hide_empty' => false,
            'exclude' => $exclude, 'type' => $post_type
        );

        if($field['type'] != 'data')
            $args['parent'] = '0';

        $args['taxonomy'] = FrmProAppHelper::get_custom_taxonomy($post_type, $field);
        if ( ! $args['taxonomy'] ) {
            return;
        }

        $args = apply_filters('frm_get_categories', $args, $field);

        $categories = get_categories($args);

        $options = array();
        foreach($categories as $cat)
            $options[$cat->term_id] = $cat->name;

        $options = apply_filters('frm_category_opts', $options, $field, array('cat' => $categories, 'args' => $args) );

        return $options;
    }

    public static function get_child_checkboxes($args){
        $defaults = array(
            'field' => 0, 'field_name' => false, 'opt_key' => 0, 'opt' => '',
            'type' => 'checkbox', 'value' => false, 'exclude' => 0, 'hide_id' => false,
            'tax_num' => 0
        );
        $args = wp_parse_args($args, $defaults);

        if ( ! $args['field'] || ! isset($args['field']['post_field']) || $args['field']['post_field'] != 'post_category' ) {
            return;
        }

        if ( ! $args['value'] ) {
            $args['value'] = isset($args['field']['value']) ? $args['field']['value'] : '';
        }

        if ( ! $args['exclude'] ) {
            $args['exclude'] = is_array($args['field']['exclude_cat']) ? implode(',', $args['field']['exclude_cat']) : $args['field']['exclude_cat'];
            $args['exclude'] = apply_filters('frm_exclude_cats', $args['exclude'], $args['field']);
        }

        if ( ! $args['field_name'] ) {
            $args['field_name'] = 'item_meta['. $args['field']['id'] .']';
        }

        if ( $args['type'] == 'checkbox' ) {
            $args['field_name'] .= '[]';
        }
        $post_type = FrmProFormsHelper::post_type($args['field']['form_id']);
        $taxonomy = 'category';

        $cat_atts = array(
            'orderby' => 'name', 'order' => 'ASC', 'hide_empty' => false,
            'parent' => $args['opt_key'], 'exclude' => $args['exclude'], 'type' => $post_type,
        );
        if ( ! $args['opt_key'] ) {
            $cat_atts['taxonomy'] = FrmProAppHelper::get_custom_taxonomy($post_type, $args['field']);
            if( ! $cat_atts['taxonomy'] ) {
                echo '<p>'. __('No Categories', 'formidable' ) .'</p>';
                return;
            }

            $taxonomy = $cat_atts['taxonomy'];
        }

        $children = get_categories($cat_atts);
        unset($cat_atts);
    
        $level = $args['opt_key'] ? 2 : 1;
    	foreach ( $children as $key => $cat ) {  ?>
    	<div class="frm_catlevel_<?php echo (int) $level ?>"><?php self::_show_category(array(
            'cat' => $cat, 'field' => $args['field'], 'field_name' => $args['field_name'],
            'exclude' => $args['exclude'], 'type' => $args['type'], 'value' => $args['value'],
            'level' => $level, 'onchange' => '', 'post_type' => $post_type,
            'taxonomy' => $taxonomy, 'hide_id' => $args['hide_id'], 'tax_num' => $args['tax_num'],
        )) ?></div>
<?php   }
    }

    /**
    * Get the max depth for any given taxonomy (recursive function)
    *
    * Since 2.0
    *
    * @param $cat_name string - taxonomy name
    * @param $parent int - parent ID, 0 by default
    * @param $cur_depth int - depth of current taxonomy path
    * @param $max_depth int - max depth of given taxonomy
    * @return $max_depth int - max depth of given taxonomy
    */
    public static function get_category_depth( $cat_name, $parent = 0, $cur_depth = 0, $max_depth = 0 ){
        if ( ! $cat_name ) {
            $cat_name = 'category';
        }

        // Return zero if taxonomy is not hierarchical
        if ( $parent == 0 && ! is_taxonomy_hierarchical( $cat_name ) ) {
            $max_depth = 0;
            return $max_depth;
        }

        // Get all level one categories first
        $categories = get_categories( array( 'number' => 10, 'taxonomy' => $cat_name, 'parent' => $parent, 'orderby' => 'name', 'order' => 'ASC', 'hide_empty' => false ) );

        //Only go 5 levels deep at the most
        if ( empty( $categories ) || $cur_depth == 5 ) {
            // Only update the max depth, if the current depth is greater than the max depth so far
            if ( $cur_depth > $max_depth ) {
                $max_depth = $cur_depth;
            }

            return $max_depth;
        }

        // Increment the current depth
        $cur_depth++;

        foreach ( $categories as $key => $cat ) {
            $parent = $cat->cat_ID;
            // Get children
            $max_depth = self::get_category_depth( $cat_name, $parent, $cur_depth, $max_depth );
        }
        return $max_depth;
    }

    public static function _show_category($atts) {
    	if ( ! is_object($atts['cat']) ) {
    	    return;
    	}

    	if ( is_array($atts['value']) ) {
    		$checked = (in_array($atts['cat']->cat_ID, $atts['value'])) ? 'checked="checked" ' : '';
    	} else if ( $atts['cat']->cat_ID == $atts['value'] ) {
    	    $checked = 'checked="checked" ';
    	} else {
    	    $checked = '';
    	}

    	$sanitized_name = ( isset($atts['field']['id']) ? $atts['field']['id'] : $atts['field']['field_options']['taxonomy'] ) .'-'. $atts['cat']->cat_ID;
        // Makes sure ID is unique for excluding checkboxes in Categories/Taxonomies in Create Post action
        if ( $atts['tax_num'] ) {
            $sanitized_name .= '-' . $atts['tax_num'];
        }

    	?>
    	<div class="frm_<?php echo esc_attr( $atts['type'] ) ?>" id="frm_<?php echo esc_attr( $atts['type'] .'_'. $sanitized_name ) ?>">
    	    <label for="field_<?php echo esc_attr( $sanitized_name ) ?>"><input type="<?php echo esc_attr( $atts['type'] ) ?>" name="<?php echo esc_attr( $atts['field_name'] ) ?>" <?php
    	    echo ( isset($atts['hide_id']) && $atts['hide_id'] ) ? '' : 'id="field_'. esc_attr( $sanitized_name ) .'"';
    	    ?> value="<?php echo esc_attr( $atts['cat']->cat_ID ) ?>" <?php
    	    echo $checked;
    	    do_action('frm_field_input_html', $atts['field']);
    	    //echo ($onchange);
    	    ?> /><?php echo esc_html( $atts['cat']->cat_name ) ?></label>
<?php
    	$children = get_categories(array(
    	    'type' => $atts['post_type'], 'orderby' => 'name',
    	    'order' => 'ASC', 'hide_empty' => false, 'exclude' => $atts['exclude'],
    	    'parent' => $atts['cat']->cat_ID, 'taxonomy' => $atts['taxonomy'],
    	));

    	if ( $children ) {
    	    $atts['level']++;
    	    foreach ( $children as $key => $cat ) {
    	        $atts['cat'] = $cat; ?>
    	<div class="frm_catlevel_<?php echo esc_attr( $atts['level'] ) ?>"><?php self::_show_category( $atts ); ?></div>
<?php       }
        }
    	echo '</div>';
    }

    public static function get_status_options($field){
        $post_type = FrmProFormsHelper::post_type($field->form_id);
        $post_type_object = get_post_type_object($post_type);
        $options = array();

        if ( ! $post_type_object ) {
            return $options;
        }

        $can_publish = current_user_can($post_type_object->cap->publish_posts);
        $options = get_post_statuses(); //'draft', pending, publish, private

        // Contributors only get "Unpublished" and "Pending Review"
        if ( ! $can_publish ) {
        	unset($options['publish']);
        	if(isset($options['future']))
        	    unset($options['future']);
        }
        return $options;
    }

    public static function get_user_options(){
        global $wpdb;
        $users = get_users(array( 'fields' => array('ID','user_login','display_name'), 'blog_id' => $GLOBALS['blog_id'], 'orderby' => 'display_name'));
        $options = array('' => '');
        foreach($users as $user)
            $options[$user->ID] = (!empty($user->display_name)) ? $user->display_name : $user->user_login;
        return $options;
    }

    public static function get_linked_options($values, $field, $entry_id=false){
        global $user_ID, $wpdb;

        $metas = array();
        $selected_field = FrmField::getOne($values['form_select']);

        if ( ! $selected_field ) {
            return array();
        }

        $linked_posts = (isset($selected_field->field_options['post_field']) and
            $selected_field->field_options['post_field'] and
            $selected_field->field_options['post_field'] != '') ? true : false;

        $post_ids = array();

        $frmdb = new FrmDb();

        if (is_numeric($values['hide_field']) and (empty($values['hide_opt']))){
            if ( isset($_POST) && isset($_POST['item_meta']) ) {
                $observed_field_val = (isset($_POST['item_meta'][$values['hide_field']])) ? $_POST['item_meta'][$values['hide_field']] : '';
            } else if ( $entry_id ) {
                $observed_field_val = FrmEntryMeta::get_entry_meta_by_field($entry_id, $values['hide_field']);
            } else {
                $observed_field_val = '';
            }

            $observed_field_val = maybe_unserialize($observed_field_val);

            $metas = array();
            FrmProEntryMetaHelper::meta_through_join($values['hide_field'], $selected_field, $observed_field_val, false, $metas);

        }else if ($values['restrict'] and $user_ID){
            $entry_user = $user_ID;
            if ( $entry_id && FrmAppHelper::is_admin() ) {
                $entry_user = $wpdb->get_var( $wpdb->prepare('SELECT user_id FROM '. $wpdb->prefix .'frm_items WHERE id = %d', $entry_id) );
                if ( ! $entry_user || empty($entry_user) ) {
                    $entry_user = $user_ID;
                }
            }

            if (isset($selected_field->form_id)){
                $linked_where = array('form_id' => $selected_field->form_id, 'user_id' => $entry_user);
                if($linked_posts){
                    $post_ids = $frmdb->get_records($wpdb->prefix .'frm_items', $linked_where, '', '', 'id, post_id');
                }else{
                    $entry_ids = $frmdb->get_col($wpdb->prefix .'frm_items', $linked_where, 'id');
                }
                unset($linked_where);
            }

            if ( isset($entry_ids) && !empty($entry_ids) ) {
                $metas = FrmEntryMeta::getAll("it.item_id in (".implode(',', $entry_ids).") and field_id=". (int) $values['form_select'], ' ORDER BY meta_value', '');
            }
        }else{
            $limit = '';
            if ( FrmAppHelper::is_admin_page('formidable') ) {
                $limit = 500;
            }
            $metas = $frmdb->get_records($wpdb->prefix .'frm_item_metas', array('field_id' => $values['form_select']), 'meta_value', $limit, 'item_id, meta_value');
            $post_ids = $frmdb->get_records($wpdb->prefix .'frm_items', array('form_id' => $selected_field->form_id), '', $limit, 'id, post_id');
        }

        if($linked_posts and !empty($post_ids)){
            foreach($post_ids as $entry){
                $meta_value = FrmProEntryMetaHelper::get_post_value($entry->post_id, $selected_field->field_options['post_field'], $selected_field->field_options['custom_field'], array('type' => $selected_field->type, 'form_id' => $selected_field->form_id, 'field' => $selected_field));
                $metas[] = array('meta_value' => $meta_value, 'item_id' => $entry->id);
            }
        }

        $options = array();
        foreach ($metas as $meta){
            $meta = (array) $meta;
            if($meta['meta_value'] == '') continue;

            if($selected_field->type == 'image')
                $options[$meta['item_id']] = $meta['meta_value'];
            else
                $options[$meta['item_id']] = FrmEntriesHelper::display_value($meta['meta_value'], $selected_field, array('type' => $selected_field->type, 'show_icon' => true, 'show_filename' => false));

            unset($meta);
        }

        $options = apply_filters('frm_data_sort', $options, array('metas' => $metas, 'field' => $selected_field));
        unset($metas);

        if ( self::include_blank_option($options, $field) ) {
            $options = array('' => '') + (array) $options;
        }

        return stripslashes_deep($options);
    }

    /*
    * A dropdown field should include a blank option if it is not multiselect
    * unless it autocomplete is also enabled
    *
    * @ since 2.0
    * @return boolean
    */
    public static function include_blank_option($options, $field) {
        if ( empty($options) || $field->type != 'data' ) {
            return false;
        }

        if ( ! isset($field->field_options['data_type']) || $field->field_options['data_type'] != 'select' ) {
            return false;
        }

        return  ( ! FrmFieldsHelper::is_multiple_select($field) || ( isset($field->field_options['autocom']) && $field->field_options['autocom'] ) );
    }

    public static function get_time_options($values){
        $time = strtotime($values['start_time']);
        $end_time = strtotime($values['end_time']);
        $step = explode(':', $values['step']);
        $step = (isset($step[1])) ? ($step[0] * 3600 + $step[1] * 60) : ($step[0] * 60);
        if ( empty($step) ) {
            // force an hour step if none was defined to prevent infinite loop
            $step = 60;
        }
        $format = ($values['clock'] == 24) ? 'H:i' : 'h:i A';

        $options = array('');
        while($time <= $end_time){
            $options[] = date($format, $time);
            $time += $step;
        }

        return $options;

        /*
        //Separate dropdowns
        $step = $values['step'];
        $hour_step = floor($step / 60);
        if(!$hour_step)
            $hour_step = 1;
        $start_time = $values['start_time'];
        $end_time = $values['end_time'];
        $show24Hours = ((isset($values['clock']) and $values['clock'] == 24) ? true : false);
        $separator = ':';

        $start = explode($separator, $start_time);
        $end = explode($separator, $end_time);

        if($end[0] < $start[0])
            $end[0] += 12;

        $options = array();
        $options['H'] = range($start[0], $end[0], $hour_step);
        foreach($options['H'] as $k => $h){
            if(!$show24Hours and $h > 12)
                $options['H'][$k] = $h - 12;

            if(!$options['H'][$k]){
                unset($options['H'][$k]); //remove 0
                continue;
            }

            if($options['H'][$k] < 10)
                $options['H'][$k] = '0'. $options['H'][$k];

            unset($k);
            unset($h);
        }
        $options['H'] = array_unique($options['H']);
        sort($options['H']);
        array_unshift($options['H'], '');

        if($step > 60){
            if($step %60 == 0){
                //the step is an even hour
                $step = 60;
            }else{
                //get the number of minutes
                $step = $step - ($hour_step*60);
            }
        }

        $options['m'] = range($start[1], 59, $step);
        foreach($options['m'] as $k => $m){
            if($m < 10)
                $options['m'][$k] = '0'. $m;
            unset($k);
            unset($m);
        }

        array_unshift($options['m'], '');

        if(!$show24Hours)
            $options['A'] = array('', 'AM', 'PM');

        return $options;*/
    }

    public static function posted_field_ids($where){
        if ( isset($_POST['form_id']) && isset($_POST['frm_page_order_'. $_POST['form_id']]) ) {
            global $wpdb;
            $where .= $wpdb->prepare(' and fi.field_order < %d', $_POST['frm_page_order_'. $_POST['form_id']]);
        }
        return $where;
    }

    public static function set_field_js($field, $id=0){
        global $frm_vars;

        if ( ! isset($frm_vars['datepicker_loaded']) || ! is_array($frm_vars['datepicker_loaded']) ) {
            return;
        }

        $field_key = '';
        if ( isset($frm_vars['datepicker_loaded']['^field_'. $field['field_key']]) && $frm_vars['datepicker_loaded']['^field_'. $field['field_key']] ) {
            $field_key = '^field_'. $field['field_key'];
        } else if ( isset($frm_vars['datepicker_loaded']['field_'. $field['field_key']]) && $frm_vars['datepicker_loaded']['field_'. $field['field_key']] ) {
            $field_key = 'field_'. $field['field_key'];
        }

        if ( empty($field_key) ) {
            return;
        }

        $default_date = '';
		if ( strlen($field['start_year']) == 4 || strlen($field['end_year']) == 4 ) {
            if ( $field['start_year'] > date('Y') || $field['end_year'] < date('Y') ) {
                // add a default date if current year is outside of the range
			    $default_date = $field['start_year'] .',00,01';
            }
        } else if ( $field['start_year'] > 0 || $field['end_year'] < 0 ) {
            // allow for dynamic year range
            $default_date = date('Y', strtotime($field['start_year'] .' years')) .',00,01';
        }

        $field_js = array(
            'start_year' => $field['start_year'], 'end_year' => $field['end_year'],
            'locale' => $field['locale'], 'unique' => $field['unique'],
            'field_id' => $field['id'], 'entry_id' => $id, 'default_date' => $default_date,
        );
        $frm_vars['datepicker_loaded'][$field_key] = $field_js;
    }

    public static function get_form_fields($fields, $form_id, $error=false){
        global $frm_vars, $frm_page_num;

        $prev_page = (int) FrmAppHelper::get_param('frm_page_order_'. $form_id, false);

        $go_back = $next_page = false;
        if(FrmProFormsHelper::going_to_prev($form_id)){
            $go_back = true;
            $next_page = FrmAppHelper::get_param('frm_next_page');
            $prev_page = $set_prev = $next_page - 1;
        } else if ( FrmProFormsHelper::saving_draft() && ! $error ) {
            $next_page = FrmAppHelper::get_param('frm_page_order_'. $form_id, false);

            // If $next_page is zero, assume user clicked "Save Draft" on last page of form
            if ( $next_page == 0 ) {
                $next_page = count( $fields ) - 1;
            }

            $prev_page = $set_prev = $next_page - 1;
        }

        //$current_form_id = FrmAppHelper::get_param('form_id', false);

        //if (is_numeric($current_form_id) and $current_form_id != $form_id)
        //    return $fields;

        $get_last = false;

        if ( $error ) {
            $set_prev = $prev_page;

            if ( $prev_page ) {
                $prev_page = $prev_page - 1;
            } else {
                $prev_page = 999;
                $get_last = true;
            }
        }

        $form = FrmForm::getOne($form_id);
        $ajax = ( isset($form->options['ajax_submit']) && $form->options['ajax_submit'] ) ? true : false;
        unset($form);

        $ajax_now = ! FrmAppHelper::doing_ajax();

        $page_breaks = array();

        foreach ( (array) $fields as $k => $f ) {

            // prevent sub fields from showing
            if ( $f->form_id != $form_id ) {
                unset($fields[$k]);
            }

            if($ajax){
                switch ($f->type){
                    case 'date':
                        if ( ! isset($frm_vars['datepicker_loaded']) || ! is_array($frm_vars['datepicker_loaded']) ) {
                            $frm_vars['datepicker_loaded'] = array();
                        }
                        $frm_vars['datepicker_loaded']['field_'. $f->field_key] = $ajax_now;
                    break;
                    case 'time':
                        if ( isset($f->field_options['unique']) && $f->field_options['unique'] ) {
                            if ( ! isset($frm_vars['timepicker_loaded']) ) {
                                $frm_vars['timepicker_loaded'] = array();
                            }
                            $frm_vars['timepicker_loaded']['field_'. $f->field_key] = $ajax_now;
                        }
                    break;
                    case 'phone':
                        if ( isset($f->field_options['format']) && ! empty($f->field_options['format']) && strpos( $f->field_options['format'], '^' ) !== 0 ) {
                            global $frm_input_masks;
                            $frm_input_masks[$f->id] = $ajax_now ? preg_replace('/\d/', '9', $f->field_options['format']) : false;
                        }
                    break;
                    default:
                        //do_action('frm_check_ajax_js_load', $f, $ajax_now);
                    break;
                }
            }

            if ($f->type != 'break')
                continue;

            $page_breaks[$f->field_order] = $f;


            if ( ( $prev_page || $go_back ) && ! $get_last ) {
                if (( ( $error || $go_back ) && $f->field_order < $prev_page ) || ( ! $error && ! $go_back && ! isset($prev_page_obj) && $f->field_order == $prev_page ) ) {
                    $prev_page_obj = true;
                    $prev_page = $f->field_order;
                } else if ( isset($set_prev) && $f->field_order < $set_prev ) {
                    $prev_page_obj = true;
                    $prev_page = $f->field_order;
                } else if ( ( $f->field_order > $prev_page ) && ! isset($set_next) && ( ! $next_page || is_numeric( $next_page ) ) ) {
                    $next_page = $f;
                    $set_next = true;
                }

            }else if($get_last){
                $prev_page_obj = true;
                $prev_page = $f->field_order;
                $next_page = false;
            } else if ( ! $next_page ) {
                $next_page = $f;
            } else if ( is_numeric( $next_page ) && $f->field_order == $next_page ) {
                $next_page = $f;
            }

            unset($f, $k);
        }
        unset($ajax);

        if ( ! isset($prev_page_obj) && $prev_page ) {
            $prev_page = 0;
        }

        if($prev_page){
            $current_page = $page_breaks[$prev_page];
            if ( self::is_field_hidden($current_page, stripslashes_deep($_POST)) ) {
                $current_page = apply_filters('frm_get_current_page', $current_page, $page_breaks, $go_back);
                if ( ! $current_page || $current_page->field_order != $prev_page ) {
                    $prev_page = ($current_page) ? $current_page->field_order : 0;
                    foreach ( $page_breaks as $o => $pb ) {
                        if ( $o > $prev_page ) {
                            $next_page = $pb;
                            break;
                        }
                    }

                    if($next_page->field_order <= $prev_page)
                        $next_page = false;
                }
            }
        }

        if ($prev_page)
            $frm_vars['prev_page'][$form_id] = $prev_page;
        else
            unset($frm_vars['prev_page'][$form_id]);

        if ( ! isset($next_page) ) {
            $next_page = false;
        }

        if($next_page){
            if ( is_numeric($next_page) && isset($page_breaks[$next_page]) ) {
                $next_page = $page_breaks[$next_page];
            }

            if ( ! is_numeric($next_page) ) {
                $frm_vars['next_page'][$form_id] = $next_page;
                $next_page = $next_page->field_order;
            }
        }else{
            unset($frm_vars['next_page'][$form_id]);
        }

        $pages = array_keys($page_breaks);
        $frm_page_num = $prev_page ? (array_search($prev_page, $pages) + 2) : 1;

        unset($page_breaks);

        if ($next_page or $prev_page){
            foreach($fields as $f){
                if($f->type == 'hidden' or $f->type == 'user_id')
                    continue;

                if($prev_page and $next_page and ($f->field_order < $prev_page) and ($f->field_order > $next_page)){
                    $f->type = 'hidden';
                }else if($prev_page and $f->field_order < $prev_page){
                    $f->type = 'hidden';
                }else if($next_page and $f->field_order > $next_page){
                    $f->type = 'hidden';
                }

                unset($f);
            }
        }

        return $fields;
    }

    public static function get_current_page($next_page, $page_breaks, $go_back, $order = 'asc'){
        $first = $next_page;
        $set_back = false;

        if ( $go_back && $order == 'asc' ) {
            $order = 'desc';
            $page_breaks = array_reverse( $page_breaks, true );
        }

        foreach($page_breaks as $o => $pb){
            if ( $go_back && $o < $next_page->field_order ) {
                $next_page = $pb;
                $set_back = true;
                break;
            } else if ( ! $go_back && $o > $next_page->field_order && ( $pb->field_order != $first->field_order ) ) {
                $next_page = $pb;
                break;
            }
            unset($o);
            unset($pb);
        }

        if ( $go_back && ! $set_back ) {
            $next_page = 0;
        }

        if ( $next_page && self::is_field_hidden($next_page, stripslashes_deep($_POST)) ) {
            if($first == $next_page){
                //TODO: submit form if last page is conditional
            }
            $next_page = self::get_current_page($next_page, $page_breaks, $go_back, $order);
        }

        return $next_page;
    }

    public static function show_custom_html($show, $field_type) {
        if ( in_array($field_type, array('hidden', 'user_id', 'break', 'end_divider')) ) {
            $show = false;
        }
        return $show;
    }

    public static function get_default_html($default_html, $type){
        if ($type == 'divider'){
            $default_html = <<<DEFAULT_HTML
<div id="frm_field_[id]_container" class="frm_form_field frm_section_heading form-field[error_class]">
<h3 class="frm_pos_[label_position][collapse_class]">[field_name]</h3>
[collapse_this]
[if description]<div class="frm_description">[description]</div>[/if description]
</div>
DEFAULT_HTML;
        }else if($type == 'html'){
            $default_html = '<div id="frm_field_[id]_container" class="frm_form_field form-field">[description]</div>';
        }
        return $default_html;
    }

    /**
    * Check if field is radio or Dynamic radio
    *
    * Since 2.0
    *
    * @param $field ARRAY
    * @return true if field type is radio or Dynamic radio
    */
    public static function is_radio( $field ) {
        if ( $field['type'] == 'radio' || ( $field['type'] == 'data' && $field['data_type'] == 'radio' ) ) {
            return true;
        }
    }

    /**
    * Check if field is checkbox or Dynamic checkbox
    *
    * Since 2.0
    *
    * @param $field ARRAY
    * @return true if field type is checkbox or Dynamic checkbox
    */
    public static function is_checkbox( $field ) {
        if ( $field['type'] == 'checkbox' || ( $field['type'] == 'data' && $field['data_type'] == 'checkbox' ) ) {
            return true;
        }
    }

    public static function before_replace_shortcodes($html, $field){
        if ( isset($field['align']) && ( self::is_radio( $field ) || self::is_checkbox( $field ) ) ) {
            $required_class = '[required_class]';

            if ( ( self::is_radio( $field ) && $field['align'] != FrmStylesController::get_style_val('radio_align', $field['form_id']) ) ||
                ( self::is_checkbox( $field ) && $field['align'] != FrmStylesController::get_style_val('check_align', $field['form_id']) ) ) {
                $required_class .= ($field['align'] == 'inline') ? ' horizontal_radio' : ' vertical_radio';

                $html = str_replace('[required_class]', $required_class, $html);
            }
        }

        if(isset($field['classes']) and strpos($field['classes'], 'frm_grid') !== false){
            $opt_count = count($field['options']) + 1;
            $html = str_replace('[required_class]', '[required_class] frm_grid_'. $opt_count, $html);
            if(strpos($html, ' horizontal_radio'))
                $html = str_replace(' horizontal_radio', ' vertical_radio', $html);
            unset($opt_count);
        }

        if($field['type'] == 'html' and isset($field['classes']))
            $html = str_replace('frm_form_field', 'frm_form_field '. $field['classes'], $html);

        return $html;
    }

    public static function replace_html_shortcodes($html, $field, $atts) {
        if ( 'divider' == $field['type'] ) {
            global $frm_vars;

            $html = str_replace(array('frm_none_container', 'frm_hidden_container', 'frm_top_container', 'frm_left_container', 'frm_right_container'), '', $html);

            if ( isset($frm_vars['collapse_div']) && $frm_vars['collapse_div'] ) {
                $html = "</div>\n". $html;
                $frm_vars['collapse_div'] = false;
            }

            if ( isset($frm_vars['div']) && $frm_vars['div'] && $frm_vars['div'] != $field['id'] ) {
                // close the div if it's from a different section
                $html = "</div>\n". $html;
                $frm_vars['div'] = false;
            }

            if ( isset($field['slide']) && $field['slide'] ) {
                $trigger =  ' frm_trigger';
                $collapse_div = '<div class="frm_toggle_container" style="display:none;">';
            } else {
                $trigger = $collapse_div = '';
            }

            if ( isset($field['repeat']) && $field['repeat'] ) {
                $errors = isset($atts['errors']) ? $atts['errors'] : array();
                $field_name = 'item_meta['. $field['id'] .']';
                $html_id = FrmFieldsHelper::get_html_id($field);
                $frm_settings = FrmAppHelper::get_settings();

                ob_start();
                include(FrmAppHelper::plugin_path() .'/classes/views/frm-fields/input.php');
                $input = ob_get_contents();
                ob_end_clean();

                if ( isset($field['slide']) && $field['slide'] ) {
                    $input = $collapse_div . $input .'</div>';
                }

                $html = str_replace('[collapse_this]', $input, $html);

            } else {
                if ( preg_match('/\<\/div\>$/', $html) ) {

                    // indicate that the div is open
                    $frm_vars['div'] = $field['id'];

                    // if the HTML ends with a div, remove it
                    $html = preg_replace('/\<\/div\>$/', '', $html);
                }

                if ( strpos($html, '[collapse_this]') !== false ) {
                    $html = str_replace('[collapse_this]', $collapse_div, $html);

                    // indicate that a second div is open
                    if ( ! empty($collapse_div) ) {
                        $frm_vars['collapse_div'] = $field['id'];
                    }
                }
            }


            if ( ! empty($trigger) ) {
                $style = FrmStylesController::get_form_style($field['form_id']);

                // insert the collapse icon with the heading
                preg_match_all( "/\<h[2-6]\b(.*?)(?:(\/))?\>\b(.*?)(?:(\/))?\<\/h[2-6]>/su", $html, $headings, PREG_PATTERN_ORDER);
                if ( isset($headings[3]) && ! empty($headings[3]) ) {
                    foreach ( $headings[3] as $heading ) {
                        if ( 'before' == $style->post_content['collapse_pos'] ) {
                            $html = str_replace($heading, '<i class="frm_icon_font frm_arrow_icon"></i> '. $heading, $html);
                        } else {
                            $html = str_replace($heading, $heading .' <i class="frm_icon_font frm_arrow_icon"></i>', $html);
                        }
                        break;
                    }
                }
                unset($style);
            }

            $html = str_replace('[collapse_class]', $trigger, $html);
        }else if($field['type'] == 'html'){
            if(apply_filters('frm_use_wpautop', true))
                $html = wpautop($html);
            $html = apply_filters('frm_get_default_value', $html, (object) $field, false);
            $html = do_shortcode($html);
        } else if ( isset($field['conf_field']) && $field['conf_field'] ) {//Add confirmation field

            //Get confirmation field ready for replace_shortcodes function
            $conf_html = $field['custom_html'];
            $conf_field = $field;
            $conf_field['id'] = 'conf_' . $field['id'];
            $conf_field['name'] = __('Confirm', 'formidable') . ' ' . $field['name'];
            $conf_field['description'] = $field['conf_desc'];
            $conf_field['field_key'] = 'conf_' . $field['field_key'];

            if ( $conf_field['classes'] ) {
                $conf_field['classes'] = str_replace( 'first_', '', $conf_field['classes'] );
            } else if ( $conf_field['conf_field'] == 'inline' ) {
                $conf_field['classes'] = ' frm_last_half';
            }

            //Prevent loop
            $conf_field['conf_field'] = 'stop';

            //If inside of repeating section
            $args = array();
            if ( isset( $atts['section_id'] ) ) {
                $args['field_name'] = preg_replace('/\[' . $field['id'] . '\]$/', '', $atts['field_name']);
                $args['field_name'] = $args['field_name'] . '[conf_' . $field['id'] . ']';
                $args['field_id'] = 'conf_' . $atts['field_id'];
                $args['field_plus_id'] = $atts['field_plus_id'];
                $args['section_id'] = $atts['section_id'];
            }

            // Filter default value/placeholder text
            $field['conf_input'] = apply_filters('frm_get_default_value', $field['conf_input'], (object) $field, false);

            //If clear on focus, set default value. Otherwise, set value.
            if ( $conf_field['clear_on_focus'] == 1 ) {
                $conf_field['default_value'] = $field['conf_input'];
                $conf_field['value'] = '';
            } else {
                $conf_field['value'] = $field['conf_input'];
            }

            //If going back and forth between pages, keep value in confirmation field
            if ( isset( $_POST['item_meta'] ) ) {
                $temp_args = array();
                if ( isset( $atts['section_id'] ) ) {
                    $temp_args = array('parent_field_id' => $atts['section_id'], 'key_pointer' => str_replace( '-', '', $atts['field_plus_id'] ) );
                }
                FrmEntriesHelper::get_posted_value( $conf_field['id'], $conf_field['value'], $temp_args );
            }

            //Replace shortcodes
            $conf_html = FrmFieldsHelper::replace_shortcodes($conf_html, $conf_field, '', '', $args);

            //Add a couple of classes
            $conf_html = str_replace('frm_primary_label', 'frm_primary_label frm_conf_label', $conf_html);
            $conf_html = str_replace('frm_form_field', 'frm_form_field frm_conf_field', $conf_html);

            //Remove label if stacked. Hide if inline.
            if ( $field['conf_field'] == 'inline' ) {
                $conf_html = str_replace('frm_form_field', 'frm_form_field frm_hidden_container', $conf_html);
            } else {
               $conf_html = str_replace('frm_form_field', 'frm_form_field frm_none_container', $conf_html);
            }

            $html .= $conf_html;
        }

        if ( strpos($html, '[collapse_this]') ) {
            $html = str_replace('[collapse_this]', '', $html);
        }

        return $html;
    }

    public static function get_export_val($val, $field){
        if ($field->type == 'user_id'){
            $val = self::get_display_name($val, 'user_login');
        }else if ($field->type == 'file'){
            $val = self::get_file_name($val, false);
        }else if ($field->type == 'date'){
            $wp_date_format = apply_filters('frm_csv_date_format', 'Y-m-d');
            $val = self::get_date($val, $wp_date_format);
        }else if ($field->type == 'data'){
            $new_val = maybe_unserialize($val);
            if(is_numeric($new_val)){
                $val = self::get_data_value($new_val, $field); //replace entry id with specified field
            }else if(is_array($new_val)){
                $field_value = array();
                foreach($new_val as $v){
                    $field_value[] = self::get_data_value($v, $field);
                    unset($v);
                }
                $val = implode(', ', $field_value);
            }
        }

        return $val;
    }

    public static function get_file_icon($media_id){
        if ( ! $media_id || ! is_numeric( $media_id ) ) {
            return;
        }

        $attachment = get_post($media_id);
        if ( ! $attachment ) {
            return;
        }

        $image = $orig_image = wp_get_attachment_image($media_id, 'thumbnail', true);

        //if this is a mime type icon
        if ( $image && ! preg_match("/wp-content\/uploads/", $image) ) {
            $label = basename($attachment->guid);
            $image .= " <span id='frm_media_$media_id' class='frm_upload_label'><a href='". wp_get_attachment_url($media_id) ."'>$label</a></span>";
        } else if ( $image ) {
            $image = '<a href="'. wp_get_attachment_url($media_id) .'">'. $image .'</a>';
        }

        $image = apply_filters('frm_file_icon', $image, array('media_id' => $media_id, 'image' => $orig_image));

        return $image;
    }

    public static function get_file_name($media_ids, $short=true){
        $value = '';
        foreach ( (array) $media_ids as $media_id ) {
            if ( ! is_numeric($media_id) ) {
                continue;
            }

            $attachment = get_post($media_id);
            if ( ! $attachment ) {
                continue;
            }

            $url = wp_get_attachment_url($media_id);

            $label = $short ? basename($attachment->guid) : $url;

            if ( $_GET && ( (isset($_GET['frm_action']) && $_GET['frm_action'] == 'csv') || (isset($_GET['action']) && $_GET['action'] == 'frm_entries_csv') ) ) {
                if ( !empty($value) ) {
                    $value .= ', ';
                }
            } else if ( FrmAppHelper::is_admin() ) {
                $url = '<a href="'. $url .'">'. $label .'</a>';
                if ( isset($_GET) && isset($_GET['page']) && strpos($_GET['page'], 'formidable') === 0 ) {
                    $url .= '<br/><a href="'. admin_url('media.php') .'?action=edit&attachment_id='. $media_id .'">'. __('Edit Uploaded File', 'formidable') .'</a>';
                }
            } else if ( !empty($value) ) {
                $value .= "<br/>\r\n";
            }

            $value .= $url;

            unset($media_id);
	    }
	    return $value;
    }

    public static function get_data_value($value, $field, $atts=array()){
        global $wpdb;

        if ( ! is_object($field) ) {
            $field = FrmField::getOne($field);
        }

        $orig_val = $value;
        $linked_field_id = isset($atts['show']) ? $atts['show'] : false;

        if ( is_numeric($value) && ( ! isset($field->field_options['form_select']) || $field->field_options['form_select'] != 'taxonomy' ) ) {
            if ( ! $linked_field_id && is_numeric($field->field_options['form_select']) ) {
                $linked_field_id = $field->field_options['form_select'];
            }

            if ($linked_field_id){
                $linked_field = FrmField::getOne($linked_field_id);
                if ( $linked_field && isset($linked_field->field_options['post_field']) && $linked_field->field_options['post_field'] ) {
                    $frmdb = new FrmDb();
                    $post_id = $frmdb->get_var($wpdb->prefix .'frm_items', array('id' => $value), 'post_id');
                    if($post_id){
                        if ( ! isset($atts['truncate']) ) {
                            $atts['truncate'] = false;
                        }

                        $new_value = FrmProEntryMetaHelper::get_post_value($post_id, $linked_field->field_options['post_field'], $linked_field->field_options['custom_field'], array('form_id' => $linked_field->form_id, 'field' => $linked_field, 'type' => $linked_field->type, 'truncate' => $atts['truncate']));
                    }else{
                        $new_value = FrmEntryMeta::get_entry_meta_by_field($value, $linked_field->id);
                    }
                }else if($linked_field){
                    $new_value = FrmEntryMeta::get_entry_meta_by_field($value, $linked_field->id);
                }else{
                    //no linked field
                    $user_id = $wpdb->get_var($wpdb->prepare('SELECT user_id FROM '. $wpdb->prefix .'frm_items WHERE id=%d', $value));
                    if($user_id)
                        $new_value = self::get_display_name($user_id, $linked_field_id, array('blank' => true));
                    else
                        $new_value = '';
                }

                $value = (!empty($new_value) or $new_value === 0) ? $new_value : $value;

                if($linked_field){
                    if ( isset($atts['show']) && ! is_numeric($atts['show']) ) {
                        $atts['show'] = $linked_field->id;
                    } else if ( isset($atts['show']) && ( (int) $atts['show'] == $linked_field->id || $atts['show'] == $linked_field->field_key ) ) {
                        unset($atts['show']);
                    }
                    if ( ! isset($atts['show']) && isset($atts['show_info']) ) {
                        $atts['show'] = $atts['show_info'];
                    }
                    $value = FrmFieldsHelper::get_display_value($value, $linked_field, $atts); //get display value
                }
            }
        }

        if ( $field->field_options['form_select'] != 'taxonomy' && $value == $orig_val && $field->field_options['data_type'] != 'data' ) {
            $value = '';
        }

        if(is_array($value))
            $value = implode((isset($atts['show']) ? $atts['show'] : ', '), $value);

        return $value;
    }

    public static function get_date($date, $date_format=false){
        if ( empty($date) ) {
            return $date;
        }

        if ( ! $date_format ) {
            $date_format = get_option('date_format');
        }

        if ( is_array($date) ) {
            $dates = array();
            foreach ( $date as $d ) {
                $dates[] = self::get_single_date($d, $date_format);
                unset($d);
            }
            $date = $dates;
        } else {
            $date = self::get_single_date($date, $date_format);
        }

        return $date;
    }

    public static function get_single_date($date, $date_format) {
        if (preg_match('/^\d{1-2}\/\d{1-2}\/\d{4}$/', $date)){
            $frmpro_settings = new FrmProSettings();
            $date = FrmProAppHelper::convert_date($date, $frmpro_settings->date_format, 'Y-m-d');
        }

        return date_i18n($date_format, strtotime($date));
    }

    public static function get_display_name($user_id, $user_info='display_name', $args=array()){
        $defaults = array(
            'blank' => false, 'link' => false, 'size' => 96
        );

        $args = wp_parse_args($args, $defaults);

        $user = get_userdata($user_id);
        $info = '';

        if ( $user ) {
            if ( $user_info == 'avatar' ) {
                $info = get_avatar( $user_id, $args['size'] );
            } else {
                $info = isset($user->$user_info) ? $user->$user_info : '';
            }

            if ( empty($info) && ! $args['blank'] ) {
                $info = $user->user_login;
            }
        }

        if ( $args['link'] ) {
            $info = '<a href="'.  admin_url('user-edit.php') .'?user_id='. $user_id .'">'. $info .'</a>';
        }

        return $info;
    }

    public static function get_subform_ids(&$subforms, $field) {
        if ( isset($field->field_options['form_select']) && is_numeric($field->field_options['form_select']) ) {
            $subforms[] = $field->field_options['form_select'];
        }
    }

    public static function get_field_options($form_id, $value='', $include='not', $types="'break','divider','end_divider','data','file','captcha'") {
        $fields = FrmField::getAll("fi.type $include in ($types) and fi.form_id=". (int) $form_id, 'field_order');
        foreach ($fields as $field){
            if ( $field->type == 'data' && ( ! isset($field->field_options['data_type']) || $field->field_options['data_type'] == 'data' || $field->field_options['data_type'] == '' ) ) {
                continue;
            }

            ?>
            <option value="<?php echo (int) $field->id ?>" <?php selected($value, $field->id) ?>><?php echo esc_html( FrmAppHelper::truncate($field->name, 50) ) ?></option>
        <?php
        }
    }

    public static function get_field_stats($id, $type='total', $user_id=false, $value=false, $round=100, $limit='', $atts=array(), $drafts=false){
        global $wpdb, $frm_post_ids;

        $field = FrmField::getOne($id);

        if ( ! $field ) {
            return 0;
        }

        $id = $field->id;

        if ( isset($atts['thousands_sep']) && $atts['thousands_sep'] ) {
            $thousands_sep = $atts['thousands_sep'];
            unset($atts['thousands_sep']);
            $round = ( $round == 100 ? 2 : $round );
        }

        $where_value = '';
        if ( $value ) {
            $slash_val = ( strpos($value, '\\') === false ) ? addslashes($value) : $value;
            if ( FrmFieldsHelper::is_field_with_multiple_values( $field ) ) {
                $where_value = $wpdb->prepare(" AND (meta_value LIKE %s OR meta_value LIKE %s )", '%'. FrmAppHelper::esc_like($value) .'%', '%'. FrmAppHelper::esc_like($slash_val) .'%');
                //add extra slashes to match values that are escaped in the database
            } else {
                $where_value = $wpdb->prepare(" AND (meta_value = %s OR meta_value = %s )", FrmAppHelper::esc_like($value), addcslashes( $slash_val, '_%' ) );
            }
            unset($slash_val);
        }

        //if(!$frm_post_ids)
            $frm_post_ids = array();

        $post_ids = array();

        if(isset($frm_post_ids[$id])){
            $form_posts = $frm_post_ids[$id];
        }else{
            $where_post = array('form_id' => $field->form_id, 'post_id >' => 1);
            if ( $drafts != 'both' ) {
                $where_post['is_draft'] = $drafts;
            }
            if($user_id)
                $where_post['user_id'] = $user_id;

            $frmdb = new FrmDb();
            $form_posts = $frmdb->get_records($wpdb->prefix .'frm_items', $where_post, '', '', 'id,post_id');

            $frm_post_ids[$id] = $form_posts;
        }

        if($form_posts){
            foreach($form_posts as $form_post)
                $post_ids[$form_post->id] = $form_post->post_id;
        }

        if(!empty($limit))
            $limit = " LIMIT ". $limit;

        if($value)
            $atts[$id] = $value;

        if(!empty($atts)){
            $entry_ids = array();

            if(isset($atts['entry_id']) and $atts['entry_id'] and is_numeric($atts['entry_id']))
                $entry_ids[] = $atts['entry_id'];

            $after_where = false;

            foreach($atts as $orig_f => $val){
                // Accommodate for times when users are in Visual tab
                $val = str_replace( array('&gt;','&lt;'), array('>','<'), $val );

                // If first character is a quote, but the last character is not a quote
                if((strpos($val, '"') === 0 and substr($val, -1) != '"') or (strpos($val, "'") === 0 and substr($val, -1) != "'")){
                    //parse atts back together if they were broken at spaces
                    $next_val = array('char' => substr($val, 0, 1), 'val' => $val);
                    continue;
                // If we don't have a previous value that needs to be parsed back together
                } else if ( ! isset($next_val) ) {
                    $temp = FrmAppHelper::replace_quotes($val);
                    foreach(array('"', "'") as $q){
                        // Check if <" or >" exists in string and string does not end with ".
                        if(substr($temp, -1) != $q and (strpos($temp, '<'. $q) or strpos($temp, '>'. $q))){
                            $next_val = array('char' => $q, 'val' => $val);
                            $cont = true;
                        }
                        unset($q);
                    }
                    unset($temp);
                    if(isset($cont)){
                        unset($cont);
                        continue;
                    }
                }

                // If we have a previous value saved that needs to be parsed back together (due to WordPress pullling it apart)
                if(isset($next_val)){
                    if(substr(FrmAppHelper::replace_quotes($val), -1) == $next_val['char']){
                        $val = $next_val['val'] .' '. $val;
                        unset($next_val);
                    }else{
                        $next_val['val'] .= ' '. $val;
                        continue;
                    }
                }

                $entry_ids = self::get_field_matches(compact('entry_ids', 'orig_f', 'val', 'id', 'atts', 'field', 'form_posts', 'after_where', 'drafts'));
                $after_where = true;
            }

            if(empty($entry_ids)){
                if($type == 'star'){
                    $stat = '';
                    ob_start();
                    include(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-fields/star_disabled.php');
                    $contents = ob_get_contents();
                    ob_end_clean();
                    return $contents;
                }else{
                    return 0;
                }
            }

            foreach($post_ids as $entry_id => $post_id){
                if ( ! in_array($entry_id, $entry_ids) ) {
                    unset($post_ids[$entry_id]);
                }
            }


            $where_value .= " AND it.item_id in (". implode(',', $entry_ids).")";
        }

        $join = '';

        if((is_numeric($id))){
            $where = $wpdb->prepare("field_id=%d", $id);
        }else{
            $join .= ' LEFT OUTER JOIN '. $wpdb->prefix .'frm_fields fi ON it.field_id=fi.id';
            $where = $wpdb->prepare("fi.field_key=%s", $id);
        }
        $where .= $where_value;

        if($user_id)
            $where .= $wpdb->prepare(" AND en.user_id=%d", $user_id);

        $join .= ' LEFT OUTER JOIN '. $wpdb->prefix .'frm_items en ON en.id=it.item_id';
        if ( $drafts != 'both' ) {
            $where .= $wpdb->prepare(' AND en.is_draft=%d', $drafts);
        }

        $field_metas = $wpdb->get_col('SELECT meta_value FROM '. $wpdb->prefix .'frm_item_metas it '. $join .' WHERE '. $where .' ORDER BY it.created_at DESC'. $limit);

        if(!empty($post_ids)){
            if(isset($field->field_options['post_field']) and $field->field_options['post_field']){
                if($field->field_options['post_field'] == 'post_custom'){ //get custom post field value
                    $post_values = $wpdb->get_col($wpdb->prepare("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key= %s AND post_id in (".implode(',', $post_ids) .")", $field->field_options['custom_field']));
                }else if($field->field_options['post_field'] == 'post_category'){
                    $post_query = "SELECT tr.object_id FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON tt.term_id = t.term_id INNER JOIN $wpdb->term_relationships AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = %d AND tr.object_id in (". implode(',', $post_ids) .")";
                    $post_query_vars = array($field->field_options['taxonomy']);

                    if($value){
                        $post_query .= ' AND (t.term_id = %s OR t.slug = %s OR t.name = %s)';
                        $post_query_vars[] = $value;
                        $post_query_vars[] = $value;
                        $post_query_vars[] = $value;
                    }

                    $post_values = $wpdb->get_col($wpdb->prepare($post_query, $post_query_vars));
                    $post_values = array_unique($post_values);
                }else{
                    $post_values = $wpdb->get_col("SELECT {$field->field_options['post_field']} FROM $wpdb->posts WHERE ID in (".implode(',', $post_ids) .")");
                }

                $field_metas = array_merge($post_values, $field_metas);
            }
        }

        if($type != 'star')
            unset($field);

        if (empty($field_metas)){
            if($type == 'star'){
                $stat = '';
                ob_start();
                include(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-fields/star_disabled.php');
                $contents = ob_get_contents();
                ob_end_clean();
                return $contents;
            }else{
                return 0;
            }
        }

        $count = count($field_metas);
        $total = array_sum($field_metas);

        switch($type){
            case 'average':
            case 'mean':
            case 'star':
                $stat = ($total / $count);
            break;
            case 'median':
                rsort($field_metas);
                $n = ceil($count / 2); // Middle of the array
                if ($count % 2){
                    $stat = $field_metas[$n-1]; // If number is odd
                }else{
                    $n2 = floor($count / 2); // Other middle of the array
                    $stat = ($field_metas[$n-1] + $field_metas[$n2-1]) / 2;
                }
                $stat = maybe_unserialize($stat);
                if (is_array($stat))
                    $stat = 0;
            break;
            case 'deviation':
                $mean = ($total / $count);
                $stat = 0.0;
                foreach ($field_metas as $i)
                    $stat += pow($i - $mean, 2);

                if($count > 1){
                    $stat /= ( $count - 1 );

                    $stat = sqrt($stat);
                }else{
                    $stat = 0;
                }
            break;
            case 'minimum':
                $stat = min($field_metas);
            break;
            case 'maximum':
                $stat = max($field_metas);
            break;
            case 'count':
                $stat = $count;
            break;
            case 'unique':
                $stat = array_unique($field_metas);
                $stat = count($stat);
            break;
            case 'total':
            default:
                $stat = $total;
        }

        $stat = round($stat, $round);
        if($type == 'star'){
            ob_start();
            include(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-fields/star_disabled.php');
            $contents = ob_get_contents();
            ob_end_clean();
            return $contents;
        }
        if ( ( $round && $round < 5 ) || isset($thousands_sep) ) {
            $thousands_sep = ( isset($thousands_sep) ? $thousands_sep : ',');
            $stat = number_format($stat, $round, '.', $thousands_sep);
        }

        return $stat;
    }

    public static function get_field_matches( $args ){
        extract( $args );

        $f = $orig_f;
        $where_is = '=';

        //If using <, >, <=, >=, or != TODO: %, !%.
        //Note: $f will be numeric if using <, >, <=, >=, != OR if using x=val, but the code in the if/else statement will not actually do anything to x=val.
        if ( is_numeric( $f ) ) {//Note: $f will count up for certain atts
            $orig_val = $val;
            $lpos = strpos($val, '<');
            $gpos = strpos($val, '>');
            $not_pos = strpos($val, '!=');
            $dash_pos = strpos( $val, '-' );

			if ( $not_pos !== false ) { //If string contains !=

                //If entry IDs have not been set by a previous $atts
        		if ( empty( $entry_ids ) && $after_where == 0) {
        			global $wpdb;
        			$query = $wpdb->prepare("SELECT id FROM {$wpdb->prefix}frm_items WHERE form_id=%d", $field->form_id);
                    //By default, don't get drafts
                    if ( $drafts != 'both' ) {
                        $query .= $wpdb->prepare(" AND is_draft=%d", $drafts);
                    }
                    $entry_ids = $wpdb->get_col($query);
                    unset($query);
        		}

				$where_is = '!=';
				$str = explode( $where_is, $orig_val );
				$f = $str[0];
                $val = $str[1];
			} else if ( $lpos !== false || $gpos !== false ) { //If string contains greater than or less than
                $where_is = ( ( $gpos !== false && $lpos !== false && $lpos > $gpos ) || $lpos === false ) ? '>' : '<';
                $str = explode($where_is, $orig_val);

                if ( count( $str ) == 2 ) {
                    $f = $str[0];
                    $val = $str[1];
                } else if ( count( $str ) == 3 ) {
                    //3 parts assumes a structure like '-1 month'<255<'1 month'
                    $val = str_replace($str[0] . $where_is, '', $orig_val);
                    $entry_ids = self::get_field_matches(compact('entry_ids', 'orig_f', 'val', 'id', 'atts', 'field', 'form_posts', 'after_where', 'drafts'));

                    $after_where = true;

                    $f = $str[1];
                    $val = $str[0];
                    $where_is = ($where_is == '<') ? '>' : '<';
                }

                if ( strpos( $val, '=' ) === 0 ) {
                    $where_is .= '=';
                    $val = substr( $val, 1 );
                }

            // If field key contains a dash, then it won't be put in as $f automatically (WordPress quirk maybe?)
            // Use $f < 5 to decrease the likelihood of this section being used when $f is a field ID (like x=val)
            } else if ( $dash_pos !== false && strpos( $val, '=' ) !== false && $f < 5 ) {
                $str = explode( $where_is, $orig_val );
                $f = $str[0];
                $val = $str[1];
            }
        }

        // If this function has looped through at least once, and there aren't any entry IDs
        if ( $after_where && ! $entry_ids ) {
            return array();
        }

        //If using field key
        if ( ! is_numeric( $f ) ) {
            if ( in_array( $f, array( 'created_at', 'updated_at' ) ) ) {
                global $wpdb;

                $val = FrmAppHelper::replace_quotes( $val );
                $val = str_replace( array('"', "'"), "", $val );
                $val = date( 'Y-m-d', strtotime($val) );
                $query = $wpdb->prepare("SELECT id FROM {$wpdb->prefix}frm_items WHERE $f $where_is %s AND form_id = %d", $val, $field->form_id);

                // Entry IDs may be set even if after_where isn't true
                if ( $entry_ids ) {
                    $query .= ' AND id in ('. implode(',', $entry_ids) .')';
                }

                $entry_ids = $wpdb->get_col($query);
                return $entry_ids;
            } else {
                //check for field keys
                $this_field = FrmField::getOne($f);
                if ( $this_field ) {
                    $f = $this_field->id;
                } else {
                    //If no field ID
                    return $entry_ids;
                }
                unset($this_field);
            }
        }
        unset($orig_f);

        //Prepare val
		$val = FrmAppHelper::replace_quotes( $val );
		$val = trim( trim( $val, "'" ), '"' );

        $where_atts = apply_filters('frm_stats_where', array('where_is' => $where_is, 'where_val' => $val), array('id' => $id, 'atts' => $atts));
        $val = $where_atts['where_val'];
        $where_is = $where_atts['where_is'];
        unset($where_atts);

        $entry_ids = FrmProAppHelper::filter_where($entry_ids, array(
            'where_opt' => $f, 'where_is' => $where_is, 'where_val' => $val,
            'form_id' => $field->form_id, 'form_posts' => $form_posts,
            'after_where' => $after_where, 'drafts' => $drafts,
        ));

        unset($f);
        unset($val);

        return $entry_ids;
    }

    public static function value_meets_condition($observed_value, $cond, $hide_opt) {
        _deprecated_function( __FUNCTION__, '2.0', 'FrmFieldsHelper::value_meets_condition' );
        return FrmFieldsHelper::value_meets_condition($observed_value, $cond, $hide_opt);
    }

    public static function get_shortcode_select($form_id, $target_id='content', $type='all'){
        $field_list = array();
        $exclude = FrmFieldsHelper::no_save_fields();

        if ( is_numeric($form_id) ) {
            if ( $type == 'field_opt' ) {
                $exclude[] = 'data';
                $exclude[] = 'checkbox';
            } else if ( $type == 'calc' ) {
                $exclude[] = 'data';
            }

            $field_list = FrmField::get_all_for_form($form_id, '', 'include');
        }

        $linked_forms = array();
        ?>
        <select class="frm_shortcode_select frm_insert_val" data-target="<?php echo esc_attr( $target_id ) ?>">
            <option value="">&mdash; <?php _e('Select a value to insert into the box below', 'formidable') ?> &mdash;</option>
            <?php if($type != 'field_opt' and $type != 'calc'){ ?>
            <option value="id"><?php _e('Entry ID', 'formidable') ?></option>
            <option value="key"><?php _e('Entry Key', 'formidable') ?></option>
            <option value="post_id"><?php _e('Post ID', 'formidable') ?></option>
            <option value="ip"><?php _e('User IP', 'formidable') ?></option>
            <option value="created-at"><?php _e('Entry creation date', 'formidable') ?></option>
            <option value="updated-at"><?php _e('Entry update date', 'formidable') ?></option>

            <optgroup label="<?php _e('Form Fields', 'formidable') ?>">
            <?php }

            if ( ! empty($field_list) ) {
            foreach ( $field_list as $field ) {
                if ( in_array($field->type, $exclude) ) {
                    continue;
                }

                if ( $field->type == 'data' && ( ! isset($field->field_options['data_type']) || $field->field_options['data_type'] == 'data' || $field->field_options['data_type'] == '' ) ) {
                    continue;
                }
            ?>
                <option value="<?php echo esc_attr( $field->id ) ?>"><?php echo $field_name = esc_html( FrmAppHelper::truncate($field->name, 60) ) ?> (<?php _e( 'ID', 'formidable' ) ?>)</option>
                <option value="<?php echo esc_attr( $field->field_key ) ?>"><?php echo $field_name ?> (<?php _e( 'Key', 'formidable' ) ?>)</option>
                <?php if ( $field->type == 'file' && $type != 'field_opt' && $type != 'calc' ) { ?>
                    <option class="frm_subopt" value="<?php echo esc_attr( $field->field_key ) ?> size=thumbnail"><?php _e( 'Thumbnail', 'formidable' ) ?></option>
                    <option class="frm_subopt" value="<?php echo esc_attr( $field->field_key ) ?> size=medium"><?php _e( 'Medium', 'formidable' ) ?></option>
                    <option class="frm_subopt" value="<?php echo esc_attr( $field->field_key ) ?> size=large"><?php _e( 'Large', 'formidable' ) ?></option>
                    <option class="frm_subopt" value="<?php echo esc_attr( $field->field_key ) ?> size=full"><?php _e( 'Full Size', 'formidable' ) ?></option>
                <?php } else if ( $field->type == 'data' ) { //get all fields from linked form
                    if ( isset($field->field_options['form_select']) && is_numeric($field->field_options['form_select']) ) {
                        global $wpdb;

                        $linked_form = $wpdb->get_var( $wpdb->prepare('SELECT form_id FROM '. $wpdb->prefix .'frm_fields WHERE id = %d', $field->field_options['form_select']) );
                        if ( ! in_array($linked_form, $linked_forms) ) {
                            $linked_forms[] = $linked_form;
                            $linked_fields = FrmField::getAll("fi.type not in ('". implode("','", FrmFieldsHelper::no_save_fields() ) ."') and fi.form_id =". (int) $linked_form);
                            foreach ( $linked_fields as $linked_field ) { ?>
                    <option class="frm_subopt" value="<?php echo esc_attr( $field->id .' show='. $linked_field->id ) ?>"><?php echo esc_html( FrmAppHelper::truncate($linked_field->name, 60) ) ?> (<?php _e( 'ID', 'formidable' ) ?>)</option>
                    <option class="frm_subopt" value="<?php echo esc_attr( $field->field_key .' show='. $linked_field->field_key ) ?>"><?php echo esc_html( FrmAppHelper::truncate($linked_field->name, 60) ) ?> (<?php _e( 'Key', 'formidable' ) ?>)</option>
                    <?php
                            }
                        }
                    }
                }
            }
            }

            if ( $type != 'field_opt' && $type != 'calc' ) { ?>
            </optgroup>
            <optgroup label="<?php _e('Helpers', 'formidable') ?>">
                <option value="editlink"><?php _e('Admin link to edit the entry', 'formidable') ?></option>
                <?php if ($target_id == 'content'){ ?>
                <option value="detaillink"><?php _e('Link to view single page if showing dynamic entries', 'formidable') ?></option>
                <?php }

                if ( $type != 'email' ) { ?>
                <option value="evenodd"><?php _e('Add a rotating \'even\' or \'odd\' class', 'formidable') ?></option>
                <?php }else if($target_id == 'email_message'){ ?>
                <option value="default-message"><?php _e('Default Email Message', 'formidable') ?></option>
                <?php } ?>
                <option value="siteurl"><?php _e('Site URL', 'formidable') ?></option>
                <option value="sitename"><?php _e('Site Name', 'formidable') ?></option>
            </optgroup>
            <?php } ?>
        </select>
    <?php
    }

    public static function replace_shortcodes($content, $entry, $shortcodes, $display=false, $show='one', $odd='', $args=array()){
        global $post;

        if ( $display ) {
            $param_value = ($display->frm_type == 'id') ? $entry->id : $entry->item_key;

            if ( $entry->post_id ) {
                $args['detail_link'] = get_permalink($entry->post_id);
            } else {
                $param = ( isset($display->frm_param) && ! empty($display->frm_param) ) ? $display->frm_param : 'entry';
                if ( $post ) {
                    $args['detail_link'] = add_query_arg($param, $param_value, get_permalink($post->ID));
                } else {
                    $args['detail_link'] = add_query_arg($param, $param_value);
                }
                //if( FrmProAppHelper::rewriting_on() && $frmpro_settings->permalinks )
                //    $args['detail_link'] = get_permalink($post->ID) .$param_value .'/';
            }
        }
        $args['odd'] = $odd;
        $args['show'] = $show;

        foreach ( $shortcodes[0] as $short_key => $tag ) {
            self::replace_single_shortcode($shortcodes, $short_key, $tag, $entry, $display, $args, $content);
        }

        if ( empty($shortcodes[0]) ) {
            return $content;
        }

        return FrmFieldsHelper::replace_content_shortcodes($content, $entry, $shortcodes);
    }

    public static function replace_single_shortcode($shortcodes, $short_key, $tag, $entry, $display, $args, &$content) {
        global $post;

        $conditional = preg_match('/^\[if/s', $shortcodes[0][$short_key]) ? true : false;
        $foreach = preg_match('/^\[foreach/s', $shortcodes[0][$short_key]) ? true : false;
        $atts = shortcode_parse_atts( $shortcodes[3][$short_key] );

        $tag = FrmFieldsHelper::get_shortcode_tag($shortcodes, $short_key, compact('conditional', 'foreach'));
        if ( strpos($tag, '-') ) {
            $switch_tags = array(
                'post-id', 'created-at', 'updated-at',
                'created-by', 'updated-by',
            );
            if ( in_array($tag, $switch_tags) ) {
                $tag = str_replace('-', '_', $tag);
            }
            unset($switch_tags);
        }

        $tags = array(
            'event_date', 'entry_count', 'detaillink', 'editlink', 'deletelink',
            'created_at', 'updated_at', 'created_by', 'updated_by',
            'evenodd', 'post_id',
        );

        if ( in_array($tag, $tags) ) {
            $args['entry'] = $entry;
            $args['tag'] = $tag;
            $args['conditional'] = $conditional;
            $function_name = 'do_shortcode_'. $tag;
            self::$function_name($content, $atts, $shortcodes, $short_key, $args, $display);
            return;
        }

        $field = FrmField::getOne( $tag );
        if ( ! $field ) {
            return;
        }

        $sep = isset($atts['sep']) ? $atts['sep'] : ', ';

        if ( $field->form_id == $entry->form_id ) {
            $replace_with = FrmProEntryMetaHelper::get_post_or_meta_value($entry, $field, $atts);
        } else {
            // get entry ids linked through repeat field or embeded form
            $child_entries = FrmProEntry::get_sub_entries($entry->id, true);
            $replace_with = FrmProEntryMetaHelper::get_sub_meta_values($child_entries, $field, $atts);
        }

        $atts['entry_id'] = $entry->id;
        $atts['entry_key'] = $entry->item_key;
        $atts['post_id'] = $entry->post_id;
        $replace_with = apply_filters('frmpro_fields_replace_shortcodes', $replace_with, $tag, $atts, $field);

        self::get_file_from_atts($atts, $field, $replace_with);

        if ( is_array($replace_with) ) {
            $replace_with = implode($sep, $replace_with);
        }

        if ( $foreach ) {
            $atts['short_key'] = $shortcodes[0][$short_key];
            $args['display'] = $display;
            self::check_conditional_shortcode($content, $replace_with, $atts, $tag, 'foreach', $args);
        } else if ( $conditional ) {
            $atts['short_key'] = $shortcodes[0][$short_key];
            self::check_conditional_shortcode($content, $replace_with, $atts, $tag, 'if', array( 'field' => $field ));
        } else {
            if ( isset($atts['show']) && $atts['show'] == 'field_label' ) {
                $replace_with = $field->name;
            } else if ( isset($atts['show']) && $atts['show'] == 'description' ) {
                $replace_with = $field->description;
            } else if ( empty($replace_with) && $replace_with != '0' ) {
                $replace_with = '';
                if ( $field->type == 'number' ) {
                    $replace_with = '0';
                }
            } else {
                $replace_with = FrmFieldsHelper::get_display_value($replace_with, $field, $atts);
            }

            self::trigger_shortcode_atts($atts, $display, $replace_with);
            $content = str_replace($shortcodes[0][$short_key], $replace_with, $content);
        }
    }

    public static function replace_calendar_date_shortcode($content, $date) {
        preg_match_all("/\[(calendar_date)\b(.*?)(?:(\/))?\]/s", $content, $matches, PREG_PATTERN_ORDER);
        if ( empty($matches) ) {
            return $content;
        }

        foreach ( $matches[0] as $short_key => $tag ) {
            $atts = shortcode_parse_atts( $matches[2][$short_key] );
            self::do_shortcode_event_date($content, $atts, $matches, $short_key, array('event_date' => $date));
        }
        return $content;
    }

    public static function do_shortcode_event_date(&$content, $atts, $shortcodes, $short_key, $args) {
        $event_date = '';
        if ( isset($args['event_date']) ) {
            if ( ! isset($atts['format']) ) {
                $atts['format'] = get_option('date_format');
            }
            $event_date = self::get_date($args['event_date'], $atts['format']);
        }
        $content = str_replace($shortcodes[0][$short_key], $event_date, $content);
    }

    public static function do_shortcode_entry_count(&$content, $atts, $shortcodes, $short_key, $args) {
        $content = str_replace($shortcodes[0][$short_key], ( isset($args['record_count']) ? $args['record_count'] : '' ), $content);
    }

    public static function do_shortcode_detaillink(&$content, $atts, $shortcodes, $short_key, $args, $display) {
        if ( $display && $args['detail_link'] ) {
            $content = str_replace($shortcodes[0][$short_key], $args['detail_link'], $content);
        }
    }

    public static function do_shortcode_editlink(&$content, $atts, $shortcodes, $short_key, $args) {
        global $post;

        $replace_with = '';
        $link_text = isset($atts['label']) ? $atts['label'] : false;
        if ( ! $link_text ) {
            $link_text = isset($atts['link_text']) ? $atts['link_text'] : __('Edit');
        }

        $class = isset($atts['class']) ? $atts['class'] : '';
        $page_id = isset($atts['page_id']) ? $atts['page_id'] : ($post ? $post->ID : 0);

        if ( (isset($atts['location']) && $atts['location'] == 'front') || ( isset($atts['prefix']) && ! empty($atts['prefix']) ) || ( isset($atts['page_id']) && ! empty($atts['page_id']) ) ) {
            $edit_atts = $atts;
            $edit_atts['id'] = $args['entry']->id;
            $edit_atts['page_id'] = $page_id;

            $replace_with = FrmProEntriesController::entry_edit_link($edit_atts);
        } else {
            if ( $args['entry']->post_id ) {
                $replace_with = get_edit_post_link($args['entry']->post_id);
            } else if ( current_user_can('frm_edit_entries') ) {
                $replace_with = esc_url(admin_url('admin.php?page=formidable-entries&frm_action=edit&id='. $args['entry']->id ) );
            }

            if ( ! empty($replace_with) ) {
                $replace_with = '<a href="'. $replace_with . '" class="frm_edit_link '. $class .'">'. $link_text .'</a>';
            }

        }

        $content = str_replace($shortcodes[0][$short_key], $replace_with, $content);
    }

    public static function do_shortcode_deletelink(&$content, $atts, $shortcodes, $short_key, $args) {
        global $post;

        $page_id = isset($atts['page_id']) ? $atts['page_id'] : ($post ? $post->ID : 0);

        if ( ! isset( $atts['label'] ) ) {
            $atts['label'] = false;
        }
        $delete_atts = $atts;
        $delete_atts['id'] = $args['entry']->id;
        $delete_atts['page_id'] = $page_id;

        $replace_with = FrmProEntriesController::entry_delete_link($delete_atts);

        $content = str_replace($shortcodes[0][$short_key], $replace_with, $content);
    }

    public static function do_shortcode_evenodd(&$content, $atts, $shortcodes, $short_key, $args) {
        $content = str_replace($shortcodes[0][$short_key], $args['odd'], $content);
    }

    public static function do_shortcode_post_id(&$content, $atts, $shortcodes, $short_key, $args) {
        $content = str_replace($shortcodes[0][$short_key], $args['entry']->post_id, $content);
    }

    public static function do_shortcode_created_at(&$content, $atts, $shortcodes, $short_key, $args) {
        if ( isset($atts['format']) ) {
            $time_format = ' ';
        } else {
            $atts['format'] = get_option('date_format');
            $time_format = '';
        }

        if ( $args['conditional'] ) {
            $atts['short_key'] = $shortcodes[0][$short_key];
            self::check_conditional_shortcode($content, $args['entry']->{$args['tag']}, $atts, $args['tag']);
        } else {
            if ( isset($atts['time_ago']) ) {
                $date = FrmAppHelper::human_time_diff( strtotime($args['entry']->{$args['tag']}) );
            } else {
                $date = FrmAppHelper::get_formatted_time($args['entry']->{$args['tag']}, $atts['format'], $time_format);
            }

            $content = str_replace($shortcodes[0][$short_key], $date, $content);
        }
    }

    public static function do_shortcode_updated_at(&$content, $atts, $shortcodes, $short_key, $args) {
        self::do_shortcode_created_at($content, $atts, $shortcodes, $short_key, $args);
    }

    public static function do_shortcode_created_by(&$content, $atts, $shortcodes, $short_key, $args) {
        $replace_with = FrmFieldsHelper::get_display_value($args['entry']->{$args['tag']}, (object) array('type' => 'user_id'), $atts);

        if ( $args['conditional'] ) {
            $atts['short_key'] = $shortcodes[0][$short_key];
            self::check_conditional_shortcode($content, $args['entry']->{$args['tag']}, $atts, $args['tag']);
        } else {
            $content = str_replace($shortcodes[0][$short_key], $replace_with, $content);
        }
    }

    public static function do_shortcode_updated_by(&$content, $atts, $shortcodes, $short_key, $args) {
        self::do_shortcode_created_by($content, $atts, $shortcodes, $short_key, $args);
    }

    public static function get_file_from_atts($atts, $field, &$replace_with) {
        if ( $field->type != 'file' ) {
            return;
        }

        //size options are thumbnail, medium, large, or full, label
        $size = isset($atts['size']) ? $atts['size'] : (isset($atts['show']) ? $atts['show'] : 'thumbnail');
        $inc_html = ( isset($atts['html']) && $atts['html'] ) ? true : false;
        $inc_links = ( isset($atts['links']) && $atts['links'] ) ? true : false;
        $show_filename = ( isset($atts['show_filename']) && $atts['show_filename'] ) ? true : false;

        if ( $size != 'id' && ! empty($replace_with) ) {
            $replace_with = self::get_media_from_id($replace_with, $size, array('html' => $inc_html, 'links' => $inc_links, 'show_filename' => $show_filename));
        } else if ( is_array($replace_with) ) {
            $replace_with = array_filter($replace_with);
        }
    }

    public static function check_conditional_shortcode(&$content, $replace_with, $atts, $tag, $condition = 'if', $args = array() ) {
        $defaults = array('field' => false);
        $args = wp_parse_args($args, $defaults);

        if ( 'if' == $condition ) {
            $replace_with = self::conditional_replace_with_value( $replace_with, $atts, $args['field'], $tag );
            $replace_with = apply_filters('frm_conditional_value', $replace_with, $atts, $args['field'], $tag);
        }

        $start_pos = strpos($content, $atts['short_key']);

        if ( $start_pos === false ) {
            return;
        }

        $start_pos_len = strlen($atts['short_key']);
        $end_pos = strpos($content, '[/'. $condition .' '. $tag .']', $start_pos);
        $end_pos_len = strlen('[/'. $condition .' '. $tag .']');

        if ( $end_pos === false ) {
            $end_pos = strpos($content, '[/'. $condition .']', $start_pos);
            $end_pos_len = strlen('[/'. $condition .']');

            if ( $end_pos === false ) {
                return;
            }
        }

        $total_len = ( $end_pos + $end_pos_len ) - $start_pos;

        if ( empty($replace_with) ) {
            $content = substr_replace($content, '', $start_pos, $total_len);
        } else if ( 'foreach' == $condition ) {
            $content_len = $end_pos - ( $start_pos + $start_pos_len );
            $repeat_content = substr($content, $start_pos + $start_pos_len, $content_len);
            self::foreach_shortcode($replace_with, $args, $repeat_content);
            $content = substr_replace($content, $repeat_content, $start_pos, $total_len);
        } else {
            $content = substr_replace($content, '', $end_pos, $end_pos_len);
            $content = substr_replace($content, '', $start_pos, $start_pos_len);
        }
    }

    /*
    * Loop through each entry linked through a repeating field when using [foreach]
    */
    public static function foreach_shortcode($replace_with, $args, &$repeat_content) {
        $foreach_content = '';

        $sub_entries = explode(',', $replace_with);
        foreach ( $sub_entries as $sub_entry ) {
            $sub_entry = trim($sub_entry);
            if ( ! is_numeric($sub_entry) ) {
                continue;
            }

            $entry = FrmEntry::getOne($sub_entry);

            $shortcodes = FrmProDisplaysHelper::get_shortcodes($repeat_content, $entry->form_id);
            $repeating_content = $repeat_content;
            foreach ( $shortcodes[0] as $short_key => $tag ) {
                self::replace_single_shortcode($shortcodes, $short_key, $tag, $entry, $args['display'], $args, $repeating_content);
            }
            $foreach_content .= $repeating_content;
        }

        $repeat_content = $foreach_content;
    }

    public static function conditional_replace_with_value($replace_with, $atts, $field, $tag) {
        $conditions = array(
            'equals', 'not_equal',
            'like', 'not_like',
            'less_than', 'greater_than',
        );

        if ( $field && $field->type == 'data' ) {
            $old_replace_with = $replace_with;
            $replace_with = FrmFieldsHelper::get_display_value($replace_with, $field, $atts);

            if ( $old_replace_with == $replace_with ) {
                $replace_with = '';
            }
        } else if ( ($field && $field->type == 'user_id') || in_array($tag, array('updated_by', 'created_by')) ) {
            // check if conditional is for current user
            if ( isset($atts['equals']) && $atts['equals'] == 'current' ) {
                $atts['equals'] = get_current_user_id();
            }

            if ( isset($atts['not_equal']) && $atts['not_equal'] == 'current' ) {
                $atts['not_equal'] = get_current_user_id();
            }
        } else if ( (in_array($tag, array('created-at', 'created_at', 'updated-at', 'updated_at')) || ( $field && $field->type == 'date') ) ) {
            foreach ( $conditions as $att_name ) {
                if ( isset($atts[$att_name]) && $atts[$att_name] != '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($atts[$att_name])) ) {
                    $atts[$att_name] = date_i18n('Y-m-d', strtotime($atts[$att_name]) );
                }
                unset($att_name);
            }
        }

        self::eval_conditions($conditions, $atts, $replace_with, $field);

        return $replace_with;
    }

    private static function eval_conditions($conditions, $atts, &$replace_with, $field) {
        foreach ( $conditions as $condition ) {
            if ( ! isset($atts[$condition]) ) {
                continue;
            }

            $function_name = 'eval_'. $condition .'_condition';
            self::$function_name($atts, $replace_with, $field);
        }
    }

    private static function eval_equals_condition($atts, &$replace_with, $field) {
        if ( $replace_with != $atts['equals'] ) {
            if ( $field && $field->type == 'data' ) {
                $replace_with = FrmFieldsHelper::get_display_value($replace_with, $field, $atts);
                if ( $replace_with != $atts['equals'] ) {
                    $replace_with = '';
                }
            } else if ( isset($field->field_options['post_field']) && $field->field_options['post_field'] == 'post_category' ) {
                $cats = explode(', ', $replace_with);
                $replace_with = '';
                foreach ( $cats as $cat ) {
                    if ( $atts['equals'] == strip_tags($cat) ) {
                        $replace_with = true;
                        return;
                    }
                }
            } else {
                $replace_with = '';
            }
        } else if ( ( $atts['equals'] == '' && $replace_with == '' ) || ( $atts['equals'] == '0' && $replace_with == '0' ) ) {
            //if the field is blank, give it a value
            $replace_with = true;
        }
    }

    private static function eval_not_equal_condition($atts, &$replace_with, $field) {
        if ( $replace_with == $atts['not_equal'] ) {
            $replace_with = '';
        } else if ( $replace_with == '' && $atts['not_equal'] !== '' ) {
			$replace_with = true;
        } else if ( ! empty($replace_with) && isset($field->field_options['post_field']) && $field->field_options['post_field'] == 'post_category' ) {
            $cats = explode(', ', $replace_with);
            foreach ( $cats as $cat ) {
                if ( $atts['not_equal'] == strip_tags($cat) ) {
                    $replace_with = '';
                    return;
                }

                unset($cat);
            }
		}
    }

    private static function eval_like_condition($atts, &$replace_with) {
        if ( $atts['like'] == '' ) {
            return;
        }

        if ( strpos($replace_with, $atts['like']) === false ) {
             $replace_with = '';
        }
    }

    private static function eval_not_like_condition($atts, &$replace_with) {
        if ( $atts['not_like'] == '' ) {
            return;
        }

        if ( $replace_with == '' ) {
            $replace_with = true;
        } else if ( strpos($replace_with, $atts['not_like']) !== false ) {
            $replace_with = '';
        }
    }

    private static function eval_less_than_condition($atts, &$replace_with) {
        if ( $atts['less_than'] <= $replace_with ) {
            $replace_with = '';
        } else if ( $atts['less_than'] > 0 && $replace_with == '0' ) {
            $replace_with = true;
        }
    }

    private static function eval_greater_than_condition($atts, &$replace_with) {
        if ( $atts['greater_than'] >= $replace_with ) {
            $replace_with = '';
        }
    }

    public static function trigger_shortcode_atts($atts, $display, &$replace_with) {
        $frm_atts = array(
            'sanitize', 'sanitize_url',
            'truncate', 'clickable',
        );
        $included_atts = array_intersect($frm_atts, array_keys($atts));

        foreach ( $included_atts as $included_att ) {
            $function_name = 'atts_'. $included_att;
            $replace_with = self::$function_name($replace_with, $atts, $display);
        }
    }

    public static function atts_sanitize($replace_with) {
        return sanitize_title_with_dashes($replace_with);
    }

    public static function atts_sanitize_url($replace_with) {
        if ( seems_utf8($replace_with) ) {
            $replace_with = utf8_uri_encode($replace_with, 200);
        }
        return urlencode($replace_with);
    }

    public static function atts_truncate($replace_with, $atts, $display) {
        if ( isset($atts['more_text']) ) {
            $more_link_text = $atts['more_text'];
        } else {
            $more_link_text = isset($atts['more_link_text']) ? $atts['more_link_text'] : '. . .';
        }

        if ( $display && $display->frm_show_count == 'dynamic' ) {
            $more_link_text = ' <a href="'. $atts['detail_link'] .'">'. $more_link_text .'</a>';
            return FrmAppHelper::truncate($replace_with, (int) $atts['truncate'], 3, $more_link_text);
        }

        $replace_with = wp_specialchars_decode(strip_tags($replace_with), ENT_QUOTES);
        $part_one = substr($replace_with, 0, (int) $atts['truncate']);
        $part_two = substr($replace_with, (int) $atts['truncate']);
        if ( ! empty($part_two) ) {
            $replace_with = $part_one .'<a href="#" onclick="jQuery(this).next().css(\'display\', \'inline\');jQuery(this).css(\'display\', \'none\');return false;" class="frm_text_exposed_show"> '. $more_link_text .'</a><span style="display:none;">'. $part_two .'</span>';
        }

        return $replace_with;
    }

    public static function atts_clickable($replace_with) {
        return make_clickable($replace_with);
    }

    public static function get_media_from_id($ids, $size='thumbnail', $atts=array()){
        $defaults = array('html' => false, 'links' => false, 'show_filename' => false);
        $atts = wp_parse_args( $atts, $defaults );
        if ( $atts['show_filename'] ) {
            $size = 'label';
        }

        $replace_with = array();
        foreach ( (array) $ids as $id ) {
            if ( ! is_numeric($id) ) {
                if ( ! empty($id) && ! $atts['show_filename'] ) {
                    $replace_with[] = $id;
                }
                continue;
            }

            $text = $icon = false;
            if ( 'label' == $size ) {
                $attachment = get_post($id);
                if ( ! $attachment ) {
                    continue;
                }

                $img = $text = basename($attachment->guid);

                if ( $atts['html'] ) {
                    $atts['links'] = true;
                }
            } else {
                $image = wp_get_attachment_image_src($id, $size); //Returns an array (url, width, height) or false

                if ( $image ) {
                    $img = $image[0];
                    if ( $atts['html'] ) {
                        $img = '<img src="'. $img .'" />';
                    }
                } else {
                    if ( ! $atts['html'] && ! $atts['links'] ) {
                        $img = wp_get_attachment_url($id);
                    }

                    if ( $atts['html'] ) {
                        $atts['links'] = true;
                        $icon = true;
                    }
                }
            }

            if ( $atts['links'] ) {
                // show the sized image representation of the attachment if available, and link to the raw file
                $img = wp_get_attachment_link($id, $size, false, $icon, $text);
            }

            if ( isset($img) ) {
                $replace_with[] = $img;
            }

            unset($img, $id);
        }


        if(count($replace_with) == 1)
            $replace_with = reset($replace_with);

        return $replace_with;
    }

    public static function get_display_value($replace_with, $field, $atts=array()) {
        $function_name = 'get_'. $field->type .'_display_value';
        if ( method_exists(__CLASS__, $function_name) ) {
            $replace_with = self::$function_name($replace_with, $atts, $field);
        }

        return $replace_with;
    }

    public static function get_user_id_display_value($replace_with, $atts) {
        $user_info = isset($atts['show']) ? $atts['show'] : 'display_name';
        $replace_with = self::get_display_name($replace_with, $user_info, $atts);
        if ( ! is_array($replace_with) ) {
            return $replace_with;
        }

        $new_val = array();
        foreach ( $replace_with as $key => $val ) {
            $new_val[] = $key .'. '. $val;
        }

        return implode(', ', $new_val);
    }

    public static function get_date_display_value($replace_with, $atts) {
        $defaults = array(
            'format'    => false,
        );
        $atts = wp_parse_args($atts, $defaults);

        if ( ! isset($atts['time_ago']) ) {
            if ( is_array($replace_with) ) {
                foreach ( $replace_with as $k => $v ) {
                    $replace_with[$k] = self::get_date($v, $atts['format']);
                }
            } else {
                $replace_with = self::get_date($replace_with, $atts['format']);
            }
            return $replace_with;
        }

        $replace_with = self::get_date($replace_with, 'Y-m-d H:i:s');
        $replace_with = FrmAppHelper::human_time_diff( strtotime($replace_with), strtotime(date_i18n('Y-m-d')) );

        return $replace_with;
    }

    public static function get_file_display_value($replace_with, $atts) {
        if ( ! is_numeric($replace_with) && ! is_array($replace_with) ) {
            return $replace_with;
        }

        $defaults = array(
            'show'  => 'thumbnail', 'sep'   => ' ',
            'html'  => false, 'links' => false,
        );
        $atts = wp_parse_args($atts, $defaults);

        //size options are thumbnail, medium, large, or full
        $atts['size'] = isset($atts['size']) ? $atts['size'] : $atts['show'];

        if ( $atts['size'] != 'id' ) {
            $atts['html'] = $atts['html'] ? true : false;
            $atts['links'] = $atts['links'] ? true : false;

            $replace_with = self::get_media_from_id($replace_with, $atts['size'], array(
                'html' => $atts['html'], 'links' => $atts['links'],
            ) );
        }

        if ( is_array($replace_with) ) {
            $replace_with = implode($atts['sep'], $replace_with);
        }

        return $replace_with;
    }

    public static function get_number_display_value($replace_with, $atts) {
        $defaults = array(
            'dec_point' => '.', 'thousands_sep' => '',
            'sep'       => ', ',
        );
        $atts = wp_parse_args($atts, $defaults);

        $new_val = array();
        $replace_with = array_filter( (array) $replace_with, 'strlen' );

        foreach ( $replace_with as $v ) {
            if ( strpos($v, $atts['sep']) ) {
                $v = explode($atts['sep'], $v);
            }

            foreach ( (array) $v as $n ) {
                if ( ! isset($atts['decimal']) ) {
                    $num = explode('.', $n);
                    $atts['decimal'] = isset($num[1]) ? strlen($num[1]) : 0;
                }

                $n = number_format($n, $atts['decimal'], $atts['dec_point'], $atts['thousands_sep']);
                $new_val[] = $n;
            }

            unset($v);
        }
        $new_val = array_filter( (array) $new_val, 'strlen' );

        return implode($atts['sep'], $new_val);
    }

    public static function get_data_display_value($replace_with, $atts, $field) {
        //if ( is_numeric($replace_with) || is_array($replace_with) )

        if ( ! isset($field->field_options['form_select']) || $field->field_options['form_select'] == 'taxonomy' ) {
            return $replace_with;
        }

        $sep = isset($atts['sep']) ? $atts['sep'] : ', ';
        $atts['show'] = isset($atts['show']) ? $atts['show'] : false;

        if ( ! empty($replace_with) && ! is_array($replace_with) ) {
            $replace_with = explode($sep, $replace_with);
        }

        $linked_ids = (array) $replace_with;
        $replace_with = array();

        if ( $atts['show'] == 'id' ) {
            // keep the values the same since we already have the ids
            $replace_with = $linked_ids;
        } else if ( in_array($atts['show'], array('key', 'created-at', 'created_at', 'updated-at', 'updated_at, updated-by, updated_by', 'post_id')) ) {

            $nice_show = str_replace('-', '_', $atts['show']);

            foreach ( $linked_ids as $linked_id ) {
                $linked_entry = FrmEntry::getOne($linked_id);

                if ( isset($linked_entry->{$atts['show']}) ) {
                    $replace_with[] = $linked_entry->{$atts['show']};
                } else if ( isset($linked_entry->{$nice_show}) ) {
                    $replace_with[] = $linked_entry->{$nice_show};
                } else {
                    $replace_with[] = $linked_entry->item_key;
                }
            }
        } else {
            foreach ( $linked_ids as $linked_id ) {
                $new_val = self::get_data_value($linked_id, $field, $atts);

                if ( $linked_id == $new_val ) {
                    continue;
                }
                if ( is_array($new_val) ){
                    $new_val = implode($sep, $new_val);
                }

                $replace_with[] = $new_val;

                unset($new_val);
            }
        }

        return implode($sep, $replace_with);
    }

     public static function is_field_hidden($field, $values){
         $field->field_options = maybe_unserialize($field->field_options);

         if($field->type == 'user_id' or $field->type == 'hidden')
             return false;

         if ( ! isset($field->field_options['hide_field']) || empty($field->field_options['hide_field']) ) {
             return false;
         }

         //TODO: check if field is included in conditional heading

         $field->field_options['hide_field'] = (array) $field->field_options['hide_field'];
         if ( ! isset($field->field_options['hide_field_cond']) ) {
             $field->field_options['hide_field_cond'] = array('==');
         }
         $field->field_options['hide_field_cond'] = (array) $field->field_options['hide_field_cond'];
         $field->field_options['hide_opt'] = (array) $field->field_options['hide_opt'];

         if ( ! isset($field->field_options['show_hide']) ) {
             $field->field_options['show_hide'] = 'show';
         }

         if ( ! isset($field->field_options['any_all']) ) {
             $field->field_options['any_all'] = 'any';
         }

         $hidden = false;
         $hide = array();

         foreach($field->field_options['hide_field'] as $hide_key => $hide_field){
             if($hidden and $field->field_options['any_all'] == 'any' and $field->field_options['show_hide'] == 'hide')
                 continue;

             $observed_value = isset($values['item_meta'][$hide_field]) ? $values['item_meta'][$hide_field] : '';

             if($field->type == 'data' and empty($field->field_options['hide_opt'][$hide_key]) and (is_numeric($observed_value) or is_array($observed_value))){
                 $observed_field = FrmField::getOne($hide_field);
                 if($observed_field->type == 'data')
                     $field->field_options['hide_opt'][$hide_key] = $observed_value;

                 unset($observed_field);
             }

             $hidden = FrmFieldsHelper::value_meets_condition($observed_value, $field->field_options['hide_field_cond'][$hide_key], $field->field_options['hide_opt'][$hide_key]);
             if($field->field_options['show_hide'] == 'show')
                 $hidden = ($hidden) ? false : true;

             $hide[$hidden] = $hidden;
         }

         if($field->field_options['any_all'] == 'all' and !empty($hide) and isset($hide[0]) and isset($hide[1]))
             $hidden = ($field->field_options['show_hide'] == 'show') ? true : false;
         else if($field->field_options['any_all'] == 'any' and $field->field_options['show_hide'] == 'show' and isset($hide[0]))
             $hidden = false;

         return $hidden;
    }

    public static function &is_field_visible_to_user($field) {
        $visible = true;

        if ( ! isset($field->field_options['admin_only']) || empty($field->field_options['admin_only']) ) {
            return $visible;
        }

        if ( $field->field_options['admin_only'] == 1 ) {
            $field->field_options['admin_only'] = 'administrator';
        }

        if ( ( $field->field_options['admin_only'] == 'loggedout' && is_user_logged_in() ) ||
            ( $field->field_options['admin_only'] == 'loggedin' && ! is_user_logged_in() ) ||
            ( ! in_array($field->field_options['admin_only'], array('loggedout', 'loggedin', '') ) &&
            ! FrmAppHelper::user_has_permission( $field->field_options['admin_only'] ) ) ) {
                $visible = false;
        }

        return $visible;
    }

    public static function is_repeating_field($field) {
        return ( 'divider' == $field->type && isset($field->field_options['repeat']) && $field->field_options['repeat'] );
    }

    /*
    * Loop through value in hidden field and display arrays in separate fields
    * @since 2.0
    */
    public static function insert_hidden_fields($field, $field_name, $checked, $opt_key) {
        if ( is_array($checked) ) {
            foreach ($checked as $k => $checked2){
                $checked2 = apply_filters('frm_hidden_value', $checked2, $field);
                self::insert_hidden_fields($field, $field_name .'['. $k .']', $checked2, $k);
                unset($k, $checked2);
            }
        } else { ?>
            <input type="hidden" name="<?php echo esc_attr( $field_name ) ?>" value="<?php echo esc_attr( $checked ) ?>" <?php do_action( 'frm_field_input_html', $field ) ?> />
            <?php
            self::extra_hidden_fields( $field, $opt_key );
        }
    }

    /**
    * Add confirmation and "other" hidden fields to help carry all data throughout the form
    *
    * @since 2.0
    *
    * @param $field array
    * @param $opt_key string
    */
    public static function extra_hidden_fields( $field, $opt_key = false ) {
        //If confirmation field on previous page, store value in hidden field
        if ( isset($field['conf_field']) && $field['conf_field'] && isset( $_POST['item_meta']['conf_' . $field['id']] ) ) {
            ?>
            <input type="hidden" name="item_meta[conf_<?php echo esc_attr( $field['id'] ) ?>]" value="<?php echo esc_attr( $_POST['item_meta']['conf_' . $field['id']] ); ?>" />
            <?php

        //If Other field on previous page, store value in hidden field
        } else if ( isset( $field['other'] ) && $field['other'] && isset( $_POST['item_meta']['other'][$field['id']] ) && $_POST['item_meta']['other'][$field['id']] ) {

            // Checkbox and multi-select dropdown fields
            if ( $opt_key && ! is_numeric( $opt_key ) && isset( $_POST['item_meta']['other'][ $field['id'] ][ $opt_key ] ) && $_POST['item_meta']['other'][ $field['id'] ][ $opt_key ] ) {
                ?>
                <input type="hidden" name="item_meta[other][<?php echo esc_attr( $field['id'] ) ?>][<?php echo esc_attr( $opt_key ) ?>]" value="<?php echo esc_attr( $_POST['item_meta']['other'][ $field['id'] ][ $opt_key ] ); ?>" />
                <?php

            // Radio fields and regular dropdowns
            } else if ( ! is_array( $field['value'] ) && ! is_array( $_POST['item_meta']['other'][ $field['id'] ] ) ) { ?>
                <input type="hidden" name="item_meta[other][<?php echo esc_attr( $field['id'] ) ?>]" value="<?php echo esc_attr( $_POST['item_meta']['other'][ $field['id'] ] ); ?>" />
                <?php
            }
        }
    }

    /*
    * Check if the field is in a child form and return the parent form id
    * @since 2.0
    * @return int The ID of the form or parent form
    */
    public static function get_parent_form_id($field) {
        $form = FrmForm::getOne($field->form_id);

        // include the parent form ids if this is a child field
        $form_id = $field->form_id;
        if ( ! empty($form->parent_form_id) ) {
            $form_id = $form->parent_form_id;
        }

        return $form_id;
    }

    /*
    * Get the parent section field
    *
    * @since 2.0
    * @return Object|false The section field object if there is one
    */
    public static function get_parent_section($field, $form_id = 0) {
        global $wpdb;

        if ( ! $form_id ) {
            $form_id = $field->form_id;
        }

        $query = $wpdb->prepare('fi.field_order < %d AND fi.form_id=%d AND (fi.type = %s OR fi.type = %s)', $field->field_order, $form_id, 'divider', 'end_divider');
        $section = FrmField::getAll($query, 'field_order', 1);

        return $section;
    }

    public static function field_on_current_page($field) {
        global $frm_vars;
        $current = true;

        $prev = 0;
        $next = 9999;
        if ( ! is_object($field) ) {
            $field = FrmField::getOne($field);
        }

        if ( $frm_vars['prev_page'] && is_array($frm_vars['prev_page']) && isset($frm_vars['prev_page'][$field->form_id]) ) {
            $prev = $frm_vars['prev_page'][$field->form_id];
        }

        if ( $frm_vars['next_page'] && is_array($frm_vars['next_page']) && isset($frm_vars['next_page'][$field->form_id]) ) {
            $next = $frm_vars['next_page'][$field->form_id];
            if ( is_object($next) ) {
                $next = $next->field_order;
            }
        }

        if ( $field->field_order < $prev || $field->field_order > $next ) {
            $current = false;
        }

        $current = apply_filters('frm_show_field_on_page', $current, $field);
        return $current;
    }

    public static function switch_field_ids($val){
        // for reverse compatability
        return FrmFieldsHelper::switch_field_ids($val);
    }

     public static function get_table_options($field_options){
 		$columns = array();
 		$rows = array();
 		if (is_array($field_options)){
 			foreach ($field_options as $opt_key => $opt){
 				switch(substr($opt_key,0,3)){
 				case 'col':
 					$columns[$opt_key] = $opt;
 					break;
 				case 'row':
 					$rows[$opt_key] = $opt;
 					break;
 				}
 			}
 		}
 		return array($columns,$rows);
 	}

 	public static function set_table_options($field_options, $columns, $rows){
 		if (is_array($field_options)){
 			foreach ($field_options as $opt_key => $opt){
 				if (substr($opt_key, 0, 3) == 'col' or substr($opt_key, 0, 3) == 'row')
 					unset($field_options[$opt_key]);
 			}
 		}else
 			$field_options = array();

 		foreach ($columns as $opt_key => $opt)
 			$field_options[$opt_key] = $opt;

 		foreach ($rows as $opt_key => $opt)
 			$field_options[$opt_key] = $opt;

 		return $field_options;
 	}

 	public static function mobile_check(){
 	    _deprecated_function( __FUNCTION__, '1.07.10', 'wp_is_mobile' );
 	    return wp_is_mobile();
    }

    public static function get_error_msg($field, $error) {
        _deprecated_function( __FUNCTION__, '2.0', 'FrmFieldsHelper::get_error_msg' );
        return FrmFieldsHelper::get_error_msg($field, $error);
    }
}
