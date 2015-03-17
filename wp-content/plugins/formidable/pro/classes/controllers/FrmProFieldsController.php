<?php

class FrmProFieldsController{

    public static function &show_normal_field($show, $field_type){
        if ( in_array( $field_type, array( 'hidden', 'user_id', 'break', 'end_divider') ) ) {
            $show = false;
        }
        return $show;
    }

    public static function &normal_field_html($show, $field_type){
        if ( in_array( $field_type, array( 'hidden', 'user_id', 'break', 'divider', 'end_divider', 'html') ) ) {
            $show = false;
        }
        return $show;
    }

    public static function show_other($field, $form, $args) {
        global $frm_vars;

        // Set the field name
        $field_name = isset( $args['field_name'] ) ? $args['field_name'] : "item_meta[" . $field['id'] . "]";

        require(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-fields/show-other.php');
    }

    public static function &change_type($type, $field){
        global $frm_vars;

        remove_filter('frm_field_type', 'FrmFieldsController::change_type');

		// Don't change user ID fields or repeating sections to hidden
		if ( ! ( $type == 'divider' && $field->field_options['repeat'] ) && $type != 'user_id' && ( isset( $frm_vars['show_fields'] ) && ! empty($frm_vars['show_fields'])) && ! in_array( $field->id, $frm_vars['show_fields'] ) && ! in_array( $field->field_key, $frm_vars['show_fields'] ) ) {
            $type = 'hidden';
        }

        if ( $type == '10radio' ) {
            $type = 'scale';
        }

        if ( ! FrmAppHelper::is_admin() && $type != 'hidden' ) {
            if ( !FrmProFieldsHelper::is_field_visible_to_user($field) ) {
                $type = 'hidden';
            }
        }

        return $type;
    }

    public static function use_field_key_value($opt, $opt_key, $field){
        //if(in_array($field['post_field'], array( 'post_category', 'post_status')) or ($field['type'] == 'user_id' and is_admin() and current_user_can('administrator')))
        if ( ( isset($field['use_key']) && $field['use_key'] ) ||
            ( isset($field['type']) && $field['type'] == 'data' ) ||
            ( isset($field['post_field']) && $field['post_field'] == 'post_status' )
        ) {
            $opt = $opt_key;
        }

        return $opt;
    }

    public static function show_field($field, $form){
        global $frm_vars;

        if ( ! empty( $field['hide_field'] ) ) {
            $first = reset($field['hide_field']);
			if ( is_numeric( $first ) ) {
                if ( ! isset($frm_vars['hidden_fields']) ) {
                    $frm_vars['hidden_fields'] = array();
                }
                $frm_vars['hidden_fields'][] = $field;
            }
        }

        if ( $field['use_calc'] && $field['calc'] ) {
            $ajax = (isset($form->options['ajax_submit']) && $form->options['ajax_submit']) ? true : false;
            if ( $ajax && FrmAppHelper::doing_ajax() ) {
                return;
            }

            global $frm_vars;
            if ( ! isset($frm_vars['calc_fields']) ) {
                $frm_vars['calc_fields'] = array();
            }
            $frm_vars['calc_fields'][$field['field_key']] = array(
				'calc'			=> $field['calc'],
				'calc_dec'		=> $field['calc_dec'],
				'form_id'		=> $form->id,
            );
        }
    }

    public static function show( $field, $name = '' ) {
        $field_name = empty($name) ? 'item_meta' : $name;

        if ( is_object($field) ) {
            $field = FrmFieldsHelper::setup_edit_vars($field);
            if ( in_array($field['type'], array( 'text', 'textarea', 'radio', 'checkbox', 'select', 'captcha')) ) {
                $frm_settings = FrmAppHelper::get_settings();
                $field_name .= '['. $field['id'] .']';
                $html_id = FrmFieldsHelper::get_html_id($field);
                $display = array( 'type' => $field['type'], 'default_blank' => true);
                include(FrmAppHelper::plugin_path() .'/classes/views/frm-fields/show-build.php');
                return;
            }
        }

        $field_name .= '['. $field['id'] .']';
        $html_id = FrmFieldsHelper::get_html_id($field);

        require(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-fields/show.php');
    }

    public static function &label_position($position, $field, $form) {
        if ( $position && $position != '' ) {
            return $position;
        }

        $style_pos = FrmStylesController::get_style_val('position', $form);
        $position = $style_pos == 'none' ? 'top' : $style_pos;

        return $position;
    }

    public static function build_field_class($classes, $field) {
        if ( 'end_divider' == $field['type'] ) {
            //disable hover if parent section is repeatable
            //$classes = str_replace(' ui-state-default ', ' ui-state-disabled ', $classes);
        } else if ('divider' == $field['type'] ) {
            $classes = str_replace(' frm_not_divider ', ' ', $classes);
            if ( $field['repeat'] ) {
                $classes .= ' repeat_section';
            } else {
                $classes .= ' no_repeat_section';
            }
        }

        if ( 'inline' == $field['conf_field'] ) {
            $classes .= ' frm_conf_inline';
        } else if ( 'below' == $field['conf_field'] ) {
            $classes .= ' frm_conf_below';
        }

        return $classes;
    }

    public static function display_field_options($display){
        $display['logic'] = true;
        $display['default_value'] = false;
        $display['calc'] = false;
        $display['visibility'] = true;
		$display['conf_field'] = false;

        if ( $display['type'] == 'number' ) {
            $display['calc'] = true;
        }

        $default_unique = array(
            'default_value' => true,
            'unique'        => true,
        );

        $size_unique = array(
            'size'          => true,
            'unique'        => true,
        );

        $invalid_field = $size_unique + array(
            'clear_on_focus'=> true,
            'invalid'       => true,
            'read_only'     => true,
        );

        $no_visibility = array(
            'default_blank' => false,
            'required'      => false,
            'visibility'    => false,
        );

        $no_vis_desc = $no_visibility + array(
            'description'   => false,
            'label_position'=> false,
        );

        $settings = array(
            'radio'             => $default_unique + array(
                'default_blank' => false,
                'read_only'     => true,
            ),
            'scale'             => $default_unique + array(
                'default_blank' => false,
            ),
            'select'            => array(
                'default_value' => true,
                'read_only'     => true,
                'unique'        => true,
            ),
            'text'              => array(
                'calc'          => true,
                'read_only'     => true,
                'unique'        => true,
            ),
            'user_id'           => $no_vis_desc + array(
                'default_value' => true,
                'logic'         => false,
                'unique'        => true,
            ),
            'hidden'            => $no_vis_desc + array(
                'calc'          => true,
                'logic'         => false,
                'unique'        => true,
            ),
            'form'              => $no_vis_desc,
            'html'              => $no_vis_desc,
            'divider'           => $no_visibility,
            'end_divider'       => $no_vis_desc + array(
                'label'         => false,
                'logic'         => false,
            ),
            'break'             => $no_vis_desc + array(
                'css'           => false,
                'options'       => true,
            ),
            'file'              => array(
                'default_value' => true,
                'invalid'       => true,
                'read_only'     => true,
                'size'          => true,
            ),
            'url'               => $invalid_field,
            'website'           => $invalid_field,
            'phone'             => $invalid_field,
            'image'             => $invalid_field,
            'date'              => $invalid_field,
            'number'            => $invalid_field,
            'email'             => $invalid_field + array(
                'conf_field'    => true,
            ),
            'password'          => $invalid_field + array(
                'conf_field'    => true,
            ),
            'time'              => $size_unique + array(
                'default_value' => true,
            ),
            'rte'               => $size_unique + array(
                'default_blank' => false
            ),
        );

        $settings['checkbox'] = $settings['radio'];
        $settings['textarea'] = $settings['text'];

        if ( isset($settings[$display['type']]) ) {
            $display = array_merge($display, $settings[$display['type']]);
            return $display;
        }

        if ( 'data' == $display['type'] && isset($display['field_data']['data_type']) ) {
            $display['default_value'] = true;
            $display['read_only'] = true;
            $display['unique'] = true;

            if ( $display['field_data']['data_type'] == 'data' ) {
                $display['required'] = false;
                $display['default_blank'] = false;
                $display['read_only'] = false;
                $display['unique'] = false;
            } else if ( $display['field_data']['data_type'] == 'select' ) {
                $display['size'] = true;
            }
        }

        return $display;
    }

    public static function form_fields($field, $field_name, $atts) {
        global $frm_vars;

        $frm_settings = FrmAppHelper::get_settings();

        $errors = isset($atts['errors']) ? $atts['errors'] : array();
        $entry_id = isset($frm_vars['editing_entry']) ? $frm_vars['editing_entry'] : false;
        $html_id = $atts['html_id'];

        if ( $field['type'] == 'form' && $field['form_select'] ) {
			$dup_fields = FrmField::getAll( array( 'fi.form_id' => (int) $field['form_select'], 'fi.type not' => array( 'break', 'captcha') ) );
        } else if ( 'file' == $field['type'] ) {
            $file_name = str_replace('item_meta['. $field['id'] .']', 'file'. $field['id'], $field_name);
            if ( $file_name == $field_name ) {
                // this is a repeating field
                $repeat_meta = explode('-', $html_id);
                $repeat_meta = end($repeat_meta);
                $file_name = 'file'. $field['id'] .'-'. $repeat_meta;
                unset($repeat_meta);
            }
        }

        require(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-fields/form-fields.php');
    }

    public static function input_html( $field, $echo = true ) {
        global $frm_vars;

        $frm_settings = FrmAppHelper::get_settings();
        $add_html = '';

        if ( isset($field['read_only']) && $field['read_only'] && $field['type'] != 'hidden' ) {
            global $frm_vars;

            if ( ( isset($frm_vars['readonly']) && $frm_vars['readonly'] == 'disabled' ) || ( current_user_can('frm_edit_entries') && FrmAppHelper::is_admin() ) ) {
                //not read only
            //}else if($field['type'] == 'select'){
                //$add_html .= ' disabled="disabled" ';
            } else if ( in_array($field['type'], array( 'radio', 'checkbox')) ) {
                $add_html .= ' disabled="disabled" ';
            }else{
                $add_html .= ' readonly="readonly" ';
            }
        }

        if ( FrmFieldsHelper::is_multiple_select($field) ) {
            $add_html .= ' multiple="multiple" ';
        }

        if ( FrmAppHelper::is_admin_page('formidable' ) ) {
            if ( $echo ) {
                echo $add_html;
            }

            //don't continue if we are on the form builder page
            return $add_html;
        }

		if ( $frm_settings->use_html ) {
			if ( isset( $field['autocom'] ) && $field['autocom'] && ($field['type'] == 'select' || ( $field['type'] == 'data' && isset( $field['data_type'] ) && $field['data_type'] == 'select' ) ) ) {
                //add label for autocomplete fields
                $add_html .= ' data-placeholder=" "';
            }

            if ( $field['type'] == 'number' || $field['type'] == 'range' ) {
                if ( ! is_numeric($field['minnum']) ) {
                    $field['minnum'] = 0;
                }
                if ( ! is_numeric($field['maxnum']) ) {
                    $field['maxnum'] = 9999999;
                }
                if ( ! is_numeric($field['step']) ) {
                    $field['step'] = 1;
                }
                $add_html .= ' min="'.$field['minnum'].'" max="'.$field['maxnum'].'" step="'.$field['step'].'"';
			} else if ( in_array( $field['type'], array( 'url', 'email', 'image' ) ) ) {
                if ( ( ! isset($frm_vars['novalidate']) || ! $frm_vars['novalidate'] ) && ( $field['type'] != 'email' || ( isset($field['value']) && $field['default_value'] == $field['value'] ) ) ) {
                    // add novalidate for drafts
                    $frm_vars['novalidate'] = true;
                }
            }
        }

		if ( $echo ) {
            echo $add_html;
		}

        return $add_html;
    }

    public static function add_field_class($class, $field){
        if ( $field['type'] == 'scale' && isset($field['star']) && $field['star'] ) {
            $class .= ' star';
        } else if ( $field['type'] == 'date' ) {
            $class .= ' frm_date';
        } else if ( $field['type'] == 'file' && isset($field['multiple']) && $field['multiple'] ) {
            $class .= ' frm_multiple_file';
        }

		// Hide the "No files selected" text if files are selected
		if ( $field['type'] == 'file' && isset( $field['value'] ) && ! empty( $field['value'] ) ) {
			$class .= ' frm_transparent';
		}

        if ( ! FrmAppHelper::is_admin() && isset($field['autocom']) && $field['autocom'] &&
            ($field['type'] == 'select' || ($field['type'] == 'data' && isset($field['data_type']) && $field['data_type'] == 'select')) ) {
            global $frm_vars;
            $frm_vars['chosen_loaded'] = true;
            $class .= ' frm_chzn';

            $style = FrmStylesController::get_form_style($field['form_id']);
            if ( $style && 'rtl' == $style->post_content['direction'] ) {
                $class .= ' chosen-rtl';
            }
        }

        return $class;
    }

    public static function add_separate_value_opt_label($field){
        $class = $field['separate_value'] ? '' : ' frm_hidden';
        echo '<div class="frm-show-click">';
        echo '<div class="field_'. $field['id'] .'_option_key frm_option_val_label' . $class . '" >'. __( 'Option Label', 'formidable' ) .'</div>';
        echo '<div class="field_'. $field['id'] .'_option_key frm_option_key_label' . $class . '" >'. __( 'Saved Value', 'formidable' ) .'</div>';
        echo '</div>';
    }

    public static function options_form_before($field) {
        if ( 'data' == $field['type'] ) {
            $form_list = FrmForm::getAll( array( 'status' => 'published', 'is_template' => 0), 'name');

            $selected_field = $selected_form_id = '';
            $current_field_id = $field['id'];
            if ( isset($field['form_select']) && is_numeric($field['form_select']) ) {
                $selected_field = FrmField::getOne($field['form_select']);
                if ( $selected_field ) {
                    $selected_form_id = FrmProFieldsHelper::get_parent_form_id($selected_field);
                    $fields = FrmField::get_all_for_form($selected_form_id);
                } else {
                    $selected_field = '';
                }
            } else if ( isset($field['form_select']) ) {
                $selected_field = $field['form_select'];
            }
        }

        include(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-fields/options-form-before.php');
    }

    public static function options_form_top($field, $display, $values) {
        require(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-fields/options-form-top.php');
    }

    public static function options_form($field, $display, $values){
        remove_action('frm_field_options_form', 'FrmFieldsController::add_conditional_update_msg', 50);

        global $frm_vars;

        $frm_settings = FrmAppHelper::get_settings();

        $form_fields = false;
        if ( $display['logic'] && ! empty( $field['hide_field'] ) && is_array( $field['hide_field'] ) ) {
            $form_fields = FrmField::get_all_for_form($field['form_id']);
        }

        if ( 'data' == $field['type'] ) {
            $frm_field_selection = FrmFieldsHelper::pro_field_selection();
        }

        if ( $field['type'] == 'date' ) {
            $locales = FrmAppHelper::locales('date');
        } else if ( $field['type'] == 'file' ) {
            $mimes = get_allowed_mime_types();
        }

        require(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-fields/options-form.php');
    }

    public static function get_field_selection(){
        check_ajax_referer( 'frm_ajax', 'nonce' );

        $ajax = true;
        $current_field_id = (int) $_POST['field_id'];
        if ( is_numeric($_POST['form_id']) ) {
            $selected_field = '';
            $fields = FrmField::get_all_for_form($_POST['form_id']);
            if ($fields)
                require(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-fields/field-selection.php');
        } else {
            $selected_field = sanitize_text_field( $_POST['form_id'] );

            if ( $selected_field == 'taxonomy' ) {
                echo '<span class="howto">'. __( 'Select a taxonomy on the Post tab of the Form Settings page', 'formidable' ) .'</span>';
                echo '<input type="hidden" name="field_options[form_select_'. $current_field_id .']" value="taxonomy" />';
            }
        }

        wp_die();
    }

    public static function get_field_values(){
        check_ajax_referer( 'frm_ajax', 'nonce' );

        $current_field_id = (int) $_POST['current_field'];
        $new_field = FrmField::getOne( (int) $_POST['field_id'] );

        $is_settings_page = ( $_POST['form_action'] == 'update_settings' ) ? true : false;
        $anything = $is_settings_page ? '' : __( 'Anything', 'formidable' );

        if ( ! empty($_POST['name']) && $_POST['name'] != 'undefined' ) {
            $field_name = sanitize_text_field( $_POST['name'] );
        }
        if ( ! empty($_POST['t']) && $_POST['t'] != 'undefined' ) {
            $field_type = sanitize_text_field( $_POST['t'] );
        }

        require(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-fields/field-values.php');
        wp_die();
    }

    public static function get_dynamic_widget_opts(){
        check_ajax_referer( 'frm_ajax', 'nonce' );

        $form_id = get_post_meta( (int) $_POST['display_id'], 'frm_form_id', true );
        if ( ! $form_id ) {
            wp_die();
        }

		$fields = FrmField::getAll( array( 'fi.type not' => FrmFieldsHelper::no_save_fields(), 'fi.form_id' => $form_id ), 'field_order');

        $options = array(
            'titleValues'   => array(),
            'catValues'     => array(),
        );

        foreach ( $fields as $field ) {
            $options['titleValues'][$field->id] = $field->name;
            if ( $field->type == 'select' || $field->type == 'radio' ) {
                $options['catValues'][$field->id] = $field->name;
            }
            unset($field);
        }

        echo json_encode($options);

        wp_die();
    }


    public static function date_field_js($field_id, $options){
        if ( ! isset($options['unique']) || ! $options['unique'] ) {
            return;
        }

        $defaults = array(
            'entry_id' => 0, 'start_year' => 2000, 'end_year' => 2020,
            'locale' => '', 'unique' => 0, 'field_id' => 0
        );

        $options = wp_parse_args($options, $defaults);

        global $wpdb;

        $field = FrmField::getOne($options['field_id']);

        if ( isset($field->field_options['post_field']) && $field->field_options['post_field'] != '' ) {
			$query = array( 'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private' ) );
            if ( $field->field_options['post_field'] == 'post_custom' ) {
				$get_field = 'meta_value';
				$get_table = $wpdb->postmeta .' pm LEFT JOIN '. $wpdb->posts .' p ON (p.ID=pm.post_id)';
				$query['meta_value !'] = '';
				$query['meta_key'] = $field->field_options['custom_field'];
            } else {
				$get_field = sanitize_title( $field->field_options['post_field'] );
				$get_table = $wpdb->posts;
            }

			$post_dates = FrmDb::get_col( $get_table, $query, $get_field );
        }

        if ( ! is_numeric($options['entry_id']) ) {
            $disabled = wp_cache_get($options['field_id'], 'frm_used_dates');
        }

        if ( ! isset($disabled) || ! $disabled ) {
            $disabled = FrmDb::get_col( $wpdb->prefix .'frm_item_metas', array( 'field_id' => $options['field_id'], 'item_id !' => $options['entry_id']), 'meta_value');
        }

        if ( isset($post_dates) && $post_dates ) {
            $disabled = array_unique(array_merge( (array) $post_dates, (array) $disabled ));
        }

		/**
		 * Allows additional logic to be added to selectable dates
		 * To prevent weekends from being selectable, 'true' would be changed to '(day != 0 && day != 6)'
		 *
		 * @since 2.0
		 */
		$selectable_response = apply_filters( 'frm_selectable_dates', 'true', compact( 'field', 'options' ) );

        $disabled = apply_filters('frm_used_dates', $disabled, $field, $options);
		$js_vars = 'var m=(date.getMonth()+1),d=date.getDate(),y=date.getFullYear(),day=date.getDay();';
		if ( empty( $disabled ) ) {
			if ( $selectable_response != 'true' ) {
				// If the filter has been used, include it
				echo ',beforeShowDay:function(date){' . $js_vars . 'return [' . $selectable_response . '];}';
			}

            return;
        }

        if ( ! is_numeric($options['entry_id']) ) {
            wp_cache_set($options['field_id'], $disabled, 'frm_used_dates');
        }

        $formatted = array();
        foreach ( $disabled as $dis ) { //format to match javascript dates
            $formatted[] = date('Y-n-j', strtotime($dis));
        }

        $disabled = $formatted;
        unset($formatted);

		echo ',beforeShowDay: function(date){' . $js_vars . 'var disabled=' . json_encode( $disabled ) . ';if($.inArray(y+"-"+m+"-"+d,disabled) != -1){return [false];} return [' . $selectable_response . '];}';

        //echo ',beforeShowDay: $.datepicker.noWeekends';
    }

    public static function ajax_get_data(){
        check_ajax_referer( 'frm_ajax', 'nonce' );

        $entry_id = FrmAppHelper::get_param('entry_id');
        if ( is_array($entry_id) ){
            $entry_id = implode(',', $entry_id);
        }
        $entry_id = trim($entry_id, ',');
        $field_id = FrmAppHelper::get_param('field_id');
        $current_field = (int) FrmAppHelper::get_param('current_field');
        $hidden_field_id = FrmAppHelper::get_param('hide_id');

        $data_field = FrmField::getOne($field_id);
        $current = FrmField::getOne($current_field);
		if ( strpos( $entry_id, ',' ) ) {
            $entry_id = explode(',', $entry_id);
            $meta_value = array();
			foreach ( $entry_id as $eid ) {
                $new_meta = FrmProEntryMetaHelper::get_post_or_meta_value($eid, $data_field);
                if ( $new_meta ) {
					foreach ( (array) $new_meta as $nm ) {
                        array_push($meta_value, $nm);
                        unset($nm);
                    }
                }
                unset($new_meta, $eid);
            }

        }else{
            $meta_value = FrmProEntryMetaHelper::get_post_or_meta_value($entry_id, $data_field);
        }

        $value = FrmFieldsHelper::get_display_value($meta_value, $data_field, array( 'html' => true));
		if ( is_array( $value ) ) {
			$value = implode( ', ', $value );
		}

		if ( is_array( $meta_value ) ) {
			$meta_value = implode( ', ', $meta_value );
		}

        if ( $value && ! empty($value) ) {
            echo apply_filters('frm_show_it', "<p class='frm_show_it'>". $value ."</p>\n", $value, array( 'field' => $data_field, 'value' => $meta_value, 'entry_id' => $entry_id));
        }

        $current_field = (array) $current;
		foreach ( $current->field_options as $o => $v ) {
            if ( ! isset($current_field[$o]) ) {
                $current_field[$o] = $v;
            }
            unset($o, $v);
        }

        // Set up HTML ID and HTML name
        $html_id = '';
        $field_name = 'item_meta';
        FrmProFieldsHelper::get_html_id_from_container($field_name, $html_id, (array) $current, $hidden_field_id);

        echo '<input type="hidden" id="'. $html_id .'" name="'. $field_name .'" value="'. esc_attr($meta_value) .'" '. do_action('frm_field_input_html', $current_field, false) .'/>';
        wp_die();
    }

    public static function ajax_data_options(){
        check_ajax_referer( 'frm_ajax', 'nonce' );

        $hide_field = FrmAppHelper::get_param('hide_field');
        $entry_id = FrmAppHelper::get_param('entry_id');
        $selected_field_id = FrmAppHelper::get_param('selected_field_id');
        $field_id = FrmAppHelper::get_param('field_id');
        $hidden_field_id = FrmAppHelper::get_param('hide_id');

        $data_field = FrmField::getOne($selected_field_id);

        if ( $entry_id == '' ) {
            wp_die();
        }

        // Makes sure this works with multi-select and non multi-select fields
        if ( ! is_array( $entry_id ) ) {
            $entry_id = explode(',', $entry_id);
        }

        $field_data = FrmField::getOne($field_id);

        $field = array(
            'id' => $field_id, 'value' => '', 'default_value' => '', 'form_id' => $field_data->form_id,
            'type' => apply_filters('frm_field_type', $field_data->type, $field_data, ''),
            'options' => $field_data->options,
            'size' => (isset($field_data->field_options['size']) && $field_data->field_options['size'] != '') ? $field_data->field_options['size'] : '',
            'field_key' => $field_data->field_key
            //'value' => $field_data->value
        );

        $field['size'] = FrmAppHelper::get_field_size($field);

		if ( is_numeric( $selected_field_id ) ) {
            $field['options'] = array();

            $metas = array();
            FrmProEntryMetaHelper::meta_through_join($hide_field, $data_field, $entry_id, $field_data, $metas);
			$metas = stripslashes_deep($metas);

            if ( FrmProFieldsHelper::include_blank_option($metas, $field_data) ) {
                $field['options'][''] = '';
            }

			foreach ( $metas as $meta ) {
                $field['options'][$meta->item_id] = FrmEntriesHelper::display_value($meta->meta_value, $data_field,
                    array( 'type' => $data_field->type, 'show_icon' => true, 'show_filename' => false)
                );
                unset($meta);
            }

            // change the form_select value so the filter doesn't override the values
            $field_data->field_options['form_select'] = 'filtered_'. $field_data->field_options['form_select'];

            $field = apply_filters('frm_setup_new_fields_vars', $field, $field_data);
		} else if ( $selected_field_id == 'taxonomy' ) {
			if ( $entry_id == 0 ) {
                wp_die();
			}

			if ( is_array( $entry_id ) ) {
                $zero = array_search(0, $entry_id);
				if ( $zero !== false ) {
                    unset($entry_id[$zero]);
				}
				if ( empty( $entry_id ) ) {
                    wp_die();
				}
            }

            $field = apply_filters('frm_setup_new_fields_vars', $field, $field_data);
            $cat_ids = array_keys($field['options']);

            $args = array( 'include' => implode(',', $cat_ids), 'hide_empty' => false);

            $post_type = FrmProFormsHelper::post_type($field_data->form_id);
            $args['taxonomy'] = FrmProAppHelper::get_custom_taxonomy($post_type, $field_data);
            if ( ! $args['taxonomy'] ) {
                wp_die();
            }

            $cats = get_categories($args);
			foreach ( $cats as $cat ) {
                if ( ! in_array($cat->parent, (array) $entry_id) ) {
                    unset($field['options'][$cat->term_id]);
                }
            }

            if ( count($field['options']) == 1 && reset($field['options']) == '' ) {
                wp_die();
            }
        } else {
            $field = apply_filters('frm_setup_new_fields_vars', $field, $field_data);
        }

        // Set up HTML ID and HTML name
        $html_id = '';
        $field_name = 'item_meta';
        FrmProFieldsHelper::get_html_id_from_container($field_name, $html_id, $field, $hidden_field_id);

        if ( FrmFieldsHelper::is_multiple_select($field_data) ) {
            $field_name .= '[]';
        }

        $auto_width = (isset($field['size']) && $field['size'] > 0) ? 'class="auto_width"' : '';
        require(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-fields/data-options.php');
        wp_die();
    }

	public static function ajax_time_options(){
        check_ajax_referer( 'frm_ajax', 'nonce' );

        $remove = array();
        self::get_ajax_time_options($_POST, $remove);

	    echo json_encode($remove);
	    wp_die();
	}

    private static function get_ajax_time_options($values, array &$remove) {
        $time_key = str_replace('field_', '', $values['time_field']);
	    $date_key = str_replace('field_', '', $values['date_field']);
	    $values['date'] = FrmProAppHelper::maybe_convert_to_db_date($values['date'], 'Y-m-d');

	    $date_entries = FrmEntryMeta::getEntryIds( array( 'fi.field_key' => $date_key, 'meta_value' => $values['date']));

        $remove = apply_filters('frm_allowed_times', $remove, $values);

        if ( ! $date_entries || empty($date_entries) ) {
            return;
        }

        global $wpdb;

        $query = array( 'fi.field_key' => $time_key, 'it.item_id' => $date_entries);
        if ( is_numeric($values['entry_id']) ) {
            $query['it.item_id !'] = $values['entry_id'];
        }
        $used_times = FrmDb::get_col( $wpdb->prefix .'frm_item_metas it LEFT JOIN '. $wpdb->prefix .'frm_fields fi ON (it.field_id = fi.id)', $query, 'meta_value');

        if ( ! $used_times || empty($used_times) ) {
            return;
        }

        $number_allowed = apply_filters('frm_allowed_time_count', 1, $time_key, $date_key);
        $count = array();
        foreach ( $used_times as $used ) {
            if ( isset($remove[$used]) ) {
                continue;
            }

            if ( ! isset($count[$used]) ) {
                $count[$used] = 0;
            }
            $count[$used]++;

            if ( (int) $count[$used] >= $number_allowed ) {
                $remove[$used] = $used;
            }
        }
    }

	public static function _logic_row(){
        check_ajax_referer( 'frm_ajax', 'nonce' );

	    FrmAppHelper::permission_check('frm_edit_forms', 'show');

		$meta_name = (int) FrmAppHelper::get_param('meta_name');
		$field_id = (int) FrmAppHelper::get_param('field_id');
	    $hide_field = '';

        $field = FrmField::getOne($field_id);
        $field = FrmFieldsHelper::setup_edit_vars($field);

		$form_fields = FrmField::get_all_for_form( $field['form_id'] );

        if ( ! isset( $field['hide_field_cond'][ $meta_name ] ) ) {
            $field['hide_field_cond'][$meta_name] = '==';
        }

        include(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-fields/_logic_row.php');
        wp_die();
	}

	public static function populate_calc_dropdown(){
        check_ajax_referer( 'frm_ajax', 'nonce' );

	    if ( isset($_POST['form_id']) && isset($_POST['field_id']) ) {
	        echo FrmProFieldsHelper::get_shortcode_select($_POST['form_id'], 'frm_calc_'. $_POST['field_id'], 'calc');
        }
	    wp_die();
	}

	public static function create_multiple_fields($new_field, $form_id) {
	    // $args = compact('field_data', 'form_id', 'field');
	    if ( empty($new_field) || $new_field['type'] != 'divider' ) {
	        return;
	    }

	    // Add an "End section" when a section field is created
	    FrmFieldsController::include_new_field('end_divider', $form_id);
	}

	public static function toggle_repeat() {
        check_ajax_referer( 'frm_ajax', 'nonce' );
        global $wpdb;

        $form_id = (int) $_POST['form_id'];
        $parent_form_id = (int) $_POST['parent_form_id'];

        if ( empty( $form_id ) ) {
            $values = array( 'parent_form_id' => $parent_form_id );
            $values = FrmFormsHelper::setup_new_vars( $values );
            $form_id = (int) FrmForm::create( $values );
        }

        if ( $form_id ) {
            echo $form_id;

            // change the form_id for children
            $children = array_filter( (array) $_POST['children'], 'is_numeric');
            if ( ! empty( $children ) ) {
				$where = array( 'id' => $children );
				FrmDb::get_where_clause_and_values( $where );
				array_unshift( $where['values'], $form_id );

				$wpdb->query( $wpdb->prepare( 'UPDATE ' . $wpdb->prefix . 'frm_fields SET form_id=%d ' . $where['where'], $where['values'] ) );
            }
        }

        wp_die();
    }

	public static function duplicate_section($section_field, $form_id) {
        check_ajax_referer( 'frm_ajax', 'nonce' );

	    global $wpdb;

        $children = array_filter( $_POST['children'], 'is_numeric');
		$fields = FrmField::getAll( array( 'fi.id' => $children ), 'field_order');
        array_unshift($fields, $section_field);

		$field_count = FrmDb::get_count( $wpdb->prefix .'frm_fields fi LEFT JOIN '. $wpdb->prefix .'frm_forms fr ON (fi.form_id = fr.id)', array( 'or' => 1, 'fr.id' => $form_id, 'fr.parent_form_id' => $form_id ) );
        $ended = false;

        if ( isset($section_field->field_options['repeat']) && $section_field->field_options['repeat'] ) {
            // create the repeatable form
            $form_values = FrmFormsHelper::setup_new_vars( array( 'parent_form_id' => $form_id ) );
            $new_form_id = FrmForm::create( $form_values );
        } else {
            $new_form_id = $form_id;
        }

        foreach ( $fields as $field ) {
            // keep the current form id or give it the id of the newly created form
            $this_form_id = $field->form_id == $form_id ? $form_id : $new_form_id;

            $values = array();
            FrmFieldsHelper::fill_field( $values, $field, $this_form_id );
            if ( $field->type == 'divider' ) {
                $values['field_options']['form_select'] = $new_form_id;
            }

            $field_count++;
            $values['field_order'] = $field_count;

            $field_id = FrmField::create($values);

            if ( ! $field_id ) {
                continue;
            }

            if ( 'end_divider' == $field->type ) {
                $ended = true;
            }

            FrmFieldsController::include_single_field($field_id, $values);
        }

        if ( ! $ended ) {
            //make sure the section is ended
            self::create_multiple_fields( (array) $section_field, $form_id );
        }

        // Prevent the function in the free version from completing
        wp_die();
    }

}
