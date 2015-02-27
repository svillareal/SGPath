<?php
class FrmProEntry{

    public static function pre_validate($errors, $values){
        global $frm_vars;

        $user_ID = get_current_user_id();
        $params = (isset($frm_vars['form_params']) && is_array($frm_vars['form_params']) && isset($frm_vars['form_params'][$values['form_id']])) ? $frm_vars['form_params'][$values['form_id']] : FrmEntriesController::get_params($values['form_id']);

        if($params['action'] != 'create'){
            if(FrmProFormsHelper::going_to_prev($values['form_id'])){
                add_filter('frm_continue_to_create', '__return_false');
                $errors = array();
            } else if ( FrmProFormsHelper::saving_draft() ) {
                //$errors = array();
            }
            return $errors;
        }

        $form = FrmForm::getOne($values['form_id']);
        $form_options = maybe_unserialize($form->options);

        global $wpdb;

        $can_submit = true;
        if (isset($form_options['single_entry']) and $form_options['single_entry']){
            $admin_entry = FrmAppHelper::is_admin();

            if ($form_options['single_entry_type'] == 'cookie' and isset($_COOKIE['frm_form'. $form->id . '_' . COOKIEHASH])){
                $can_submit = $admin_entry ? true : false;
            }else if ($form_options['single_entry_type'] == 'ip'){
                if ( !$admin_entry ) {
                    $prev_entry = FrmEntry::getAll( array( 'it.ip' => FrmAppHelper::get_ip_address() ), '', 1 );
                    if ( $prev_entry ) {
                        $can_submit = false;
                    }
                }
            } else if ( ( $form_options['single_entry_type'] == 'user' || ( isset($form->options['save_draft']) && $form->options['save_draft'] == 1 ) ) && ! $form->editable ) {
                if ( $user_ID ) {
                    $query = $wpdb->prepare('user_id = %d AND form_id = %d', $user_ID, $form->id);
                    if ( $form_options['single_entry_type'] != 'user' ) {
                        $query .= $wpdb->prepare(' AND is_draft = %d', 1);
                    }
                    $meta = $wpdb->get_var('SELECT id FROM '. $wpdb->prefix .'frm_items WHERE '. $query);
                    unset($query);
                }

                if ( isset($meta) && $meta ) {
                    $can_submit = false;
                }
            }
            unset($admin_entry);

            if ( ! $can_submit ) {
                $frmpro_settings = new FrmProSettings();
                $k = is_numeric($form_options['single_entry_type']) ? 'field'. $form_options['single_entry_type'] : 'single_entry';
                $errors[$k] = $frmpro_settings->already_submitted;
                add_filter('frm_continue_to_create', '__return_false');
                return $errors;
            }
        }
        unset($can_submit);

        if ( ( ( $_POST && isset($_POST['frm_page_order_'. $form->id]) ) || FrmProFormsHelper::going_to_prev($form->id) ) && ! FrmProFormsHelper::saving_draft() ) {
            add_filter('frm_continue_to_create', '__return_false');
        } else if ( $form->editable && isset($form_options['single_entry']) && $form_options['single_entry'] && $form_options['single_entry_type'] == 'user' && $user_ID && ! FrmAppHelper::is_admin() ) {
            $meta = $wpdb->get_var($wpdb->prepare('SELECT id FROM '. $wpdb->prefix .'frm_items WHERE user_id = %d AND form_id = %d', $user_ID, $form->id));

            if ( $meta ) {
                if ( ! isset($frmpro_settings) ) {
                    $frmpro_settings = new FrmProSettings();
                }
                $errors['single_entry'] = $frmpro_settings->already_submitted;
                add_filter('frm_continue_to_create', '__return_false');
            }
        }

        if ( FrmProFormsHelper::going_to_prev($values['form_id']) ) {
            $errors = array();
        }

        return $errors;
    }

    public static function validate($params, $fields, $form, $title, $description){
        global $frm_vars;

        $frm_settings = FrmAppHelper::get_settings();

        if ( (($_POST && isset($_POST['frm_page_order_'. $form->id])) || FrmProFormsHelper::going_to_prev($form->id)) && ! FrmProFormsHelper::saving_draft() ) {
            $errors = '';
            $fields = FrmFieldsHelper::get_form_fields($form->id);
            $submit = isset($form->options['submit_value']) ? $form->options['submit_value'] : $frm_settings->submit_value;
            $values = $fields ? FrmEntriesHelper::setup_new_vars($fields, $form) : array();
            require(FrmAppHelper::plugin_path() .'/classes/views/frm-entries/new.php');
            add_filter('frm_continue_to_create', '__return_false');
        }else if ($form->editable and isset($form->options['single_entry']) and $form->options['single_entry'] and $form->options['single_entry_type'] == 'user'){

            $user_ID = get_current_user_id();
            if($user_ID){
                $entry = FrmEntry::getAll(array('it.user_id' => $user_ID, 'it.form_id' => $form->id), '', 1, true);
                if($entry)
                    $entry = reset($entry);
            }else{
                $entry = false;
            }

            if ($entry and !empty($entry) and (!isset($frm_vars['created_entries'][$form->id]) or !isset($frm_vars['created_entries'][$form->id]['entry_id']) or $entry->id != $frm_vars['created_entries'][$form->id]['entry_id'])){
                FrmProEntriesController::show_responses($entry, $fields, $form, $title, $description);
            }else{
                $record = $frm_vars['created_entries'][$form->id]['entry_id'];
                $saved_message = isset($form->options['success_msg']) ? $form->options['success_msg'] : $frm_settings->success_msg;
                if ( FrmProFormsHelper::saving_draft() ) {
                    $frmpro_settings = new FrmProSettings();
                    $saved_message = isset($form->options['draft_msg']) ? $form->options['draft_msg'] : $frmpro_settings->draft_msg;
                }
                $saved_message = apply_filters('frm_content', $saved_message, $form, ($record ? $record : false));
                $message = wpautop(do_shortcode($record ? $saved_message : $frm_settings->failed_msg));
                $message = '<div class="frm_message" id="message">'. $message .'</div>';

                FrmProEntriesController::show_responses($record, $fields, $form, $title, $description, $message);
            }
            add_filter('frm_continue_to_create', '__return_false');
        }else if ( FrmProFormsHelper::saving_draft() ) {
            $record = (isset($frm_vars['created_entries']) and isset($frm_vars['created_entries'][$form->id])) ? $frm_vars['created_entries'][$form->id]['entry_id'] : 0;

            if ( ! $record ) {
                return;
            }

            $saved_message = '';
            FrmProFormsHelper::save_draft_msg( $saved_message, $form, $record );

            $message = '<div class="frm_message" id="message">'. $saved_message .'</div>';

            FrmProEntriesController::show_responses($record, $fields, $form, $title, $description, $message);
            add_filter('frm_continue_to_create', '__return_false');
        }
    }

    public static function save_sub_entries($values, $action = 'create') {
        $form_id = isset($values['form_id']) ? (int) $values['form_id']: null;
        if ( !$form_id || !isset($values['item_meta']) ) {
            return $values;
        }

        $form_fields = FrmProFormsHelper::has_field('form', $form_id, false);
        $section_fields = FrmProFormsHelper::has_field('divider', $form_id, false);

        if ( ! $form_fields && ! $section_fields ) {
            // only continue if there could be sub entries
            return $values;
        }

        $form_fields = array_merge($section_fields, $form_fields);

        $new_values = $values;
        unset($new_values['item_meta']);

        // allow for multiple embeded forms
        foreach ( $form_fields as $field ) {
            if ( !isset($values['item_meta'][$field->id]) || !isset($field->field_options['form_select']) || !isset($values['item_meta'][$field->id]['form']) ) {
                // don't continue if we don't know which form to insert the sub entries into
                unset($values['item_meta'][$field->id]);
                continue;
            }

            if ( 'divider' == $field->type && ! FrmProFieldsHelper::is_repeating_field($field) ) {
                // only create sub entries for repeatable sections
                continue;
            }

            $field_values = $values['item_meta'][$field->id];

            $sub_form_id = $field->field_options['form_select'];
            unset($field_values['form']);

            if ( $action != 'create' && isset($field_values['id']) ) {
                $old_ids = FrmEntryMeta::get_entry_meta_by_field($values['id'], $field->id);
                $old_ids = array_filter( (array) $old_ids, 'is_numeric');
                unset($field_values['id']);
            } else {
                $old_ids = array();
            }

            $sub_ids = array();

            foreach ( $field_values as $k => $v ) {
                $entry_values = $new_values;
                $entry_values['form_id'] = $sub_form_id;
                $entry_values['item_meta'] = $v;
                $entry_values['parent_item_id'] = isset($values['id']) ? $values['id'] : 0;

                // set values for later user (file upload and tags fields)
                $_POST['item_meta']['key_pointer'] = $k;
                $_POST['item_meta']['parent_field'] = $field->id;

                if ( !is_numeric($k) && in_array( str_replace('i', '', $k), $old_ids ) ) {
                    // update existing sub entries
                    $entry_values['id'] = str_replace('i', '', $k);
                    FrmEntry::update($entry_values['id'], $entry_values);
                    $sub_id = $entry_values['id'];
                } else {
                    // create new sub entries
                    $sub_id = FrmEntry::create($entry_values);
                }

                if ( $sub_id ) {
                    $sub_ids[] = $sub_id;
                }

                unset($k, $v, $entry_values, $sub_id);
            }

            $values['item_meta'][$field->id] = $sub_ids; // array of sub entry ids

            $old_ids = array_diff($old_ids, $sub_ids);
            if ( ! empty($old_ids) ) {
                // delete entries that were removed from section
                foreach ( $old_ids as $old_id ) {
                    FrmEntry::destroy( $old_id );
                }
            }

            unset($field);
        }

        return $values;
    }

    /*
    * After an entry is duplicated, also duplicate the sub entries
    * @since 2.0
    */
    public static function duplicate_sub_entries( $entry_id, $form_id, $args ) {
        $form_fields = FrmProFormsHelper::has_field('form', $form_id, false);
        $section_fields = FrmProFormsHelper::has_repeat_field($form_id, false);
        $form_fields = array_merge($section_fields, $form_fields);
        if ( empty($form_fields) ) {
            // there are no fields for child entries
            return;
        }

        $entry = FrmEntry::getOne($entry_id, true);

        $sub_ids = array();
        foreach ( $form_fields as $field ) {
            if ( ! isset($entry->metas[$field->id]) ) {
                continue;
            }

            $field_ids = array();
            $ids = maybe_unserialize($entry->metas[$field->id]);
            if ( ! empty($ids) ) {
                // duplicate all entries for this field
                foreach ( (array) $ids as $sub_id ) {
                    $field_ids[] = FrmEntry::duplicate( $sub_id );
                    unset($sub_id);
                }

                FrmEntryMeta::update_entry_meta($entry_id, $field->id, null, $field_ids);
                $sub_ids = array_merge($field_ids, $sub_ids);
            }

            unset($field, $field_ids);
        }

        if ( ! empty($sub_ids) ) {
            // update the parent id for new entries
            global $wpdb;
            $wpdb->query('UPDATE '. $wpdb->prefix .'frm_items SET parent_item_id = '. $entry_id .' WHERE id in ('. implode(',', array_filter($sub_ids, 'is_numeric') ) .')');
        }
    }

    /*
    * After the sub entry and parent entry are created, we can update the parent id field
    * @since 2.0
    */
    public static function update_parent_id($entry_id, $form_id) {
        $form_fields = FrmProFormsHelper::has_field('form', $form_id, false);
        $section_fields = FrmProFormsHelper::has_repeat_field($form_id, false);

        if ( ! $form_fields && ! $section_fields ) {
            FrmProEntryMeta::create($entry_id, $form_id);
            return;
        }

        $form_fields = array_merge($section_fields, $form_fields);
        $entry = FrmEntry::getOne($entry_id, true);

        if ( ! $entry || $entry->form_id != $form_id ) {
            FrmProEntryMeta::create($entry_id, $form_id);
            return;
        }

        $sub_ids = array();
        foreach ( $form_fields as $field ) {
            if ( ! isset($entry->metas[$field->id]) ) {
                continue;
            }

            $ids = maybe_unserialize($entry->metas[$field->id]);
            if ( ! empty($ids) ) {
                $sub_ids = array_merge($ids, $sub_ids);
            }

            unset($field);
        }

        if ( ! empty($sub_ids) ) {
            global $wpdb;
            $wpdb->query("UPDATE {$wpdb->prefix}frm_items SET parent_item_id = $entry_id WHERE id in (". implode(',', array_filter($sub_ids, 'is_numeric') ) .")");
        }

        FrmProEntryMeta::create($entry, $form_id);
    }

    public static function get_sub_entries($entry_id, $meta = false) {
        $where = array('parent_item_id' => $entry_id);
        $entries = FrmEntry::getAll($where, '', '', $meta, false);
        return $entries;
    }

    public static function save_post($action, $entry, $form) {
        if ( $entry->post_id ) {
            $post = get_post($entry->post_id, ARRAY_A);
            unset($post['post_content']);
            $new_post = self::setup_post($action, $entry, $form);
            self::insert_post($entry, $new_post, $post, $form, $action);
        } else {
            self::create_post($entry, $form, $action);
        }
    }

    /**
    *
    * Modify values just before creating entry or saving form
    *
    * @since 2.0
    *
    * @param $values - array of posted values
    * @param $location string. If Other vals are not cleared by JavaScript when selection is changed, value should be cleared in this function. Other vals are not cleared with JavaScript on the back-end.
    * @return $values array
    */
    public static function mod_other_vals( $values = false, $location = 'front' ){
        $set_post = false;
        if ( !$values ) {
            $values = $_POST;
            $set_post = true;
        }

        if ( ! isset( $values['item_meta']['other'] ) ) {
            return $values;
        }

        $other_array = (array) $values['item_meta']['other'];
        foreach ( $other_array as $f_id => $o_val ) {
            // For checkboxes and multi-select dropdowns
            if ( is_array( $o_val ) ) {
                if ( $location == 'back' ) {
                    // Check if "other" item was selected. If not, remove other text string from saved array
                    foreach ( $o_val as $opt_key => $saved_val ) {
                        if ( $saved_val && !empty( $values['item_meta'][$f_id][$opt_key] ) ) {
                            $values['item_meta'][$f_id][$opt_key] = $saved_val;
                        }
                        unset( $opt_key, $saved_val);
                    }
                } else {
                    $values['item_meta'][$f_id] = array_merge( (array)$values['item_meta'][$f_id], $o_val );
                }

            //For radio buttons and regular dropdowns
            } else if ( $o_val ) {
                if ( $location == 'back' && isset( $values['item_meta'][$f_id] ) && !empty( $values['item_meta'][$f_id] ) ) {
                    $field = FrmField::getOne( $f_id );

                    if ( $field ) {
                        // Get array key for Other option
                        $other_key = array_filter( array_keys( $field->options ), 'is_string' );
                        $other_key = reset( $other_key );

                        // Check if the Other option is selected. If so, set the value in text field.
                        if ( $values['item_meta'][$f_id] == $field->options[$other_key] ) {
                            $values['item_meta'][$f_id] = $o_val;
                        }
                    }
                } else {
                    $values['item_meta'][$f_id] = $o_val;
                }
            }
            unset( $f_id, $o_val );
        }
        unset( $values['item_meta']['other'] );

        // Modify post values directly, if needed
        if ( $set_post ) {
            $_POST['item_meta'] = $values['item_meta'];
        }

        return $values;
    }

    /*
    * Insert all post variables into the post array
    * return array
    */
    public static function setup_post($action, $entry, $form) {
        $temp_fields = FrmField::get_all_for_form($form->id);
        $fields = array();
        foreach ( $temp_fields as $f ) {
            $fields[$f->id] = $f;
            unset($f);
        }
        unset($temp_fields);

        $new_post = array(
            'post_custom' => array(),
            'taxonomies'    => array(),
            'post_category' => array(),
        );

        self::populate_post_fields( $action, $entry, $new_post );

        // populate custom fields
        self::populate_custom_fields( $action, $entry, $fields, $new_post );

        // populate taxonomies
        self::populate_taxonomies( $action, $entry, $fields, $new_post );

        // Reverse compatability for custom code
        self::populate_from_custom_code($new_post);

        $new_post = apply_filters('frm_new_post', $new_post, compact('form', 'action', 'entry'));

        return $new_post;
    }

    private static function populate_post_fields( $action, $entry, &$new_post ) {
        $post_fields = array(
            'post_content', 'post_excerpt', 'post_title',
            'post_name', 'post_date', 'post_status',
            'post_password',
        );

        foreach ( $post_fields as $setting_name ) {
            if ( ! is_numeric( $action->post_content[$setting_name] ) ) {
                continue;
            }

            $new_post[$setting_name] = isset( $entry->metas[$action->post_content[$setting_name]]) ? $entry->metas[$action->post_content[$setting_name]] : '';

            if ( 'post_date' == $setting_name ) {
                $new_post[$setting_name] = FrmProAppHelper::maybe_convert_to_db_date( $new_post[$setting_name], 'Y-m-d H:i:s' );
            }

            unset($setting_name);
        }
    }

    /*
    * Add custom fields to the post array
    */
    private static function populate_custom_fields( $action, $entry, $fields, &$new_post ) {
        // populate custom fields
        foreach ( $action->post_content['post_custom_fields'] as $custom_field ) {
            if ( empty($custom_field['field_id']) || empty($custom_field['meta_name']) || ! isset($fields[$custom_field['field_id']]) ) {
                continue;
            }

            $value = isset($entry->metas[$custom_field['field_id']]) ? $entry->metas[$custom_field['field_id']] : '';

            if ( $fields[$custom_field['field_id']]->type == 'date' ) {
                $value = FrmProAppHelper::maybe_convert_to_db_date($value);
            }

            if ( isset($new_post['post_custom'][$custom_field['meta_name']]) ) {
                $new_post['post_custom'][$custom_field['meta_name']] = (array) $new_post['post_custom'][$custom_field['meta_name']];
                $new_post['post_custom'][$custom_field['meta_name']][] = $value;
            } else {
                $new_post['post_custom'][$custom_field['meta_name']] = $value;
            }

            unset($value);
        }
    }

    private static function populate_taxonomies( $action, $entry, $fields, &$new_post ) {
        foreach ( $action->post_content['post_category'] as $taxonomy ) {
            if ( empty($taxonomy['field_id']) || empty($taxonomy['meta_name']) ) {
                continue;
            }

            $tax_type = ( isset($taxonomy['meta_name']) && ! empty($taxonomy['meta_name']) ) ? $taxonomy['meta_name'] : 'frm_tag';
            $value = isset($entry->metas[$taxonomy['field_id']]) ? $entry->metas[$taxonomy['field_id']] : '';

            if ( $fields[$taxonomy['field_id']]->type == 'tag' ) {
                $value = trim($value);
                $value = array_map('trim', explode(',', $value));

                if ( is_taxonomy_hierarchical($tax_type) ) {
                    //create the term or check to see if it exists
                    $terms = array();
                    foreach ( $value as $v ) {
                        $term_id = term_exists($v, $tax_type);

                        // create new terms if they don't exist
                        if ( ! $term_id ) {
                            $term_id = wp_insert_term($v, $tax_type);
                        }

                        if ( $term_id && is_array($term_id) )  {
                            $term_id = $term_id['term_id'];
                        }

                        if ( is_numeric($term_id) ) {
                            $terms[$term_id] = $v;
                        }

                        unset($term_id, $v);
                    }

                    $value = $terms;
                    unset($terms);
                }

                if ( isset($new_post['taxonomies'][$tax_type]) ) {
                    $new_post['taxonomies'][$tax_type] += (array) $value;
                } else {
                    $new_post['taxonomies'][$tax_type] = (array) $value;
                }
            } else {
                $value = (array) $value;

                // change text to numeric ids while importing
                if ( defined('WP_IMPORTING') ){
                    foreach ( $value as $k => $val ) {
                        if ( empty($val) ) {
                            continue;
                        }

                        $term = term_exists($val, $fields[$taxonomy['field_id']]->field_options['taxonomy']);
                        if ( $term ) {
                            $value[$k] = is_array($term) ? $term['term_id'] : $term;
                        }

                        unset($k, $val, $term);
                    }
                }

                if ( 'category' == $tax_type ) {
                    if ( ! empty($value) ) {
                        $new_post['post_category'] = array_merge( $new_post['post_category'], $value );
                    }
                } else {
                    $new_value = array();
                    foreach ( $value as $val ) {
                        if ( $val == 0 ) {
                            continue;
                        }

                        $term = get_term($val, $fields[$taxonomy['field_id']]->field_options['taxonomy']);

                        if ( ! isset($term->errors) ) {
                            $new_value[$val] = $term->name;
                        } else {
                            $new_value[$val] = $val;
                        }

                        unset($term);
                    }

                    if ( isset($new_post['taxonomies'][$tax_type]) ) {
                        foreach ( $new_value as $new_key => $new_name ) {
                            $new_post['taxonomies'][$tax_type][$new_key] = $new_name;
                        }
                    } else {
                        $new_post['taxonomies'][$tax_type] = $new_value;
                    }
                }
            }
        }
    }

    private static function populate_from_custom_code( &$new_post ) {
        if ( isset($_POST['frm_wp_post']) ) {
            __deprecated_argument('frm_wp_post', '2.0', __('Use <code>frm_new_post</code> filter instead.', 'formidable'));
            foreach ( (array) $_POST['frm_wp_post']  as $key => $value ) {
                list($field_id, $meta_name) = explode('=', $key);
                if ( ! empty($meta_name) ) {
                    $new_post[$meta_name] = $value;
                }

                unset($field_id, $meta_name, $key, $value);
            }
        }

        if ( isset($_POST['frm_wp_post_custom']) ) {
            __deprecated_argument('frm_wp_post_custom', '2.0', __('Use <code>frm_new_post</code> filter instead.', 'formidable'));
            foreach ( (array) $_POST['frm_wp_post_custom']  as $key => $value ) {
                list($field_id, $meta_name) = explode('=', $key);
                if ( ! empty($meta_name) ) {
                    $new_post['post_custom'][$meta_name] = $value;
                }

                unset($field_id, $meta_name, $key, $value);
            }
        }

        if ( isset($_POST['frm_tax_input']) ) {
            __deprecated_argument('frm_tax_input', '2.0', __('Use <code>frm_new_post</code> filter instead.', 'formidable'));
            foreach ( (array) $_POST['frm_tax_input']  as $key => $value ) {
                if ( isset($new_post['taxonomies'][$key]) ) {
                    foreach ( (array) $value as $new_name ) {
                        $new_post['taxonomies'][$key][] = $new_name;
                    }
                } else {
                    $new_post['taxonomies'][$key] = $value;
                }

                unset($key, $value);
            }
        }
    }

    public static function create_post($entry, $form, $action = false) {
        global $wpdb;

        $entry_id = is_object($entry) ? $entry->id : $entry;
        $form_id = is_object($form) ? $form->id : $form;

        if ( ! $action ) {
            $action = FrmFormActionsHelper::get_action_for_form($form_id, 'wppost', 1);

            if ( ! $action ) {
                return;
            }
        }

        $post = self::setup_post($action, $entry, $form);
        $post['post_type'] = $action->post_content['post_type'];

        if ( isset($_POST['frm_user_id']) && is_numeric($_POST['frm_user_id']) ) {
            $post['post_author'] = $_POST['frm_user_id'];
        }

        $status = ( isset($post['post_status']) && ! empty($post['post_status']) ) ? true : false;

        if ( ! $status && $action && 'publish' == $action->post_content['post_status'] ) {
            $post['post_status'] = 'publish';
        }

        if ( isset($action->post_content['display_id']) ) {
            $post['post_custom']['frm_display_id'] = $action->post_content['display_id'];
        } else {
            //check for auto view and set frm_display_id
            $display = FrmProDisplay::get_auto_custom_display(compact('form_id', 'entry_id'));
            if ( $display ) {
                $post['post_custom']['frm_display_id'] = $display->ID;
            }
        }

        $post_id = self::insert_post($entry, $post, array(), $form, $action);
        return $post_id;
    }

    public static function insert_post($entry, $new_post, $post, $form = false, $action = false) {
        if ( ! $action ) {
            $action = FrmFormActionsHelper::get_action_for_form($form->id, 'wppost', 1);

            if ( ! $action ) {
                return;
            }
        }

        $post_fields = array(
            'post_content', 'post_excerpt', 'post_title',
            'post_name', 'post_date', 'post_status', 'post_author',
            'post_password', 'post_type', 'post_category', 'post_parent',
        );

        $editing = true;
        if ( empty($post) ) {
            $editing = false;
            $post = array();
        }

        foreach ( $post_fields as $post_field ) {
            if ( isset($new_post[$post_field]) ) {
                $post[$post_field] = $new_post[$post_field];
            }
            unset($post_field);
        }
        unset($post_fields);

        $dyn_content = '';
        self::post_value_overrides( $post, $new_post, $editing, $form, $entry, $dyn_content );

        $post_ID = wp_insert_post( $post );

    	if ( is_wp_error( $post_ID ) || empty($post_ID) ) {
    	    return;
    	}

        self::save_taxonomies( $new_post, $post_ID );
        self::link_post_attachments( $post_ID, $editing );
        self::save_post_meta( $new_post, $post_ID );
        self::save_post_id_to_entry($post_ID, $entry, $editing);
        self::save_dynamic_content( $post, $post_ID, $dyn_content, $form, $entry );
        self::delete_duplicated_meta( $action, $entry );

    	return $post_ID;
    }

    /*
    * Override the post content and date format
    */
    private static function post_value_overrides( &$post, $new_post, $editing, $form, $entry, &$dyn_content ) {
        //if empty post content and auto display, then save compiled post content
        $display_id = ( $editing ) ? get_post_meta($post['ID'], 'frm_display_id', true) : ( isset($new_post['post_custom']['frm_display_id']) ? $new_post['post_custom']['frm_display_id'] : 0 );

        if ( ! isset($post['post_content']) && $display_id ) {
            $display = FrmProDisplay::getOne( $display_id, false, true);
            $dyn_content = ( 'one' == $display->frm_show_count ) ? $display->post_content : $display->frm_dyncontent;
            $post['post_content'] = apply_filters('frm_content', $dyn_content, $form, $entry);
        }

        if ( isset($post['post_date']) && ! empty($post['post_date']) && ( ! isset($post['post_date_gmt']) || $post['post_date_gmt'] == '0000-00-00 00:00:00' ) ) {
            // set post date gmt if post date is set
            $post['post_date_gmt'] = get_gmt_from_date($post['post_date']);
		}
    }

    /*
    * Add taxonomies after save in case user doesn't have permissions
    */
    private static function save_taxonomies( $new_post, $post_ID ) {
    	foreach ( $new_post['taxonomies'] as $taxonomy => $tags ) {
            if ( is_taxonomy_hierarchical($taxonomy) ) {
    			$tags = array_keys($tags);
    		}

            wp_set_post_terms( $post_ID, $tags, $taxonomy );

    		unset($taxonomy, $tags);
        }
    }

    /*
    * link the uploads to the post
    */
    private static function link_post_attachments( $post_ID, $editing ) {
    	global $frm_vars, $wpdb;

    	$exclude_attached = array();
    	if ( isset($frm_vars['media_id']) && ! empty($frm_vars['media_id']) ) {

    	    foreach ( (array) $frm_vars['media_id'] as $media_id ) {
    	        $exclude_attached = array_merge($exclude_attached, (array) $media_id);

    	        if ( is_array($media_id) ) {
    	            $attach_string = implode( ',', array_filter($media_id) );
    	            if ( ! empty($attach_string) ) {
    				    $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET post_parent = %d WHERE post_type = %s AND ID IN ( $attach_string )", $post_ID, 'attachment' ) );

    	                foreach ( $media_id as $m ) {
    	                    clean_attachment_cache( $m );
    	                    unset($m);
    	                }
    	            }
    	        } else {
    	            $wpdb->update( $wpdb->posts, array('post_parent' => $post_ID), array( 'ID' => $media_id, 'post_type' => 'attachment' ) );
    	            clean_attachment_cache( $media_id );
    	        }
    	    }
    	}

        self::unlink_post_attachments($post_ID, $editing, $exclude_attached);
    }

    /*
    * unattach files from this post
    */
    private static function unlink_post_attachments( $post_ID, $editing, $exclude_attached ) {
    	if ( ! $editing || ! count($_FILES) ) {
            return;
        }

	    $args = array(
	        'post_type' => 'attachment', 'numberposts' => -1,
	        'post_status' => null, 'post_parent' => $post_ID,
	        'exclude' => $exclude_attached
	    );

        global $wpdb;

        $attachments = get_posts( $args );
        foreach ( $attachments as $attachment ) {
            $wpdb->update( $wpdb->posts, array('post_parent' => null), array( 'ID' => $attachment->ID ) );
        }
    }

    private static function save_post_meta( $new_post, $post_ID ) {
    	foreach ( $new_post['post_custom'] as $post_data => $value ) {
            if ( $value == '' ) {
                delete_post_meta($post_ID, $post_data);
            } else {
                update_post_meta($post_ID, $post_data, $value);
            }

            unset($post_data, $value);
        }

        global $user_ID;
    	update_post_meta( $post_ID, '_edit_last', $user_ID );
    }

    /*
    * save post_id with the entry
    */
    private static function save_post_id_to_entry($post_ID, $entry, $editing) {
        if ( $editing ) {
            return;
        }

        global $wpdb;
        $updated = $wpdb->update( $wpdb->prefix .'frm_items', array('post_id' => $post_ID), array( 'id' => $entry->id ) );
        if ( $updated ) {
            wp_cache_delete( $entry->id, 'frm_entry' );
            wp_cache_delete( $entry->id .'_nometa', 'frm_entry' );
        }
    }

    /*
    * update dynamic content after all post fields are updated
    */
    private static function save_dynamic_content( $post, $post_ID, $dyn_content, $form, $entry ) {
        if ( $dyn_content == '' ) {
            return;
        }

        $new_content = apply_filters('frm_content', $dyn_content, $form, $entry);
        if ( $new_content != $post['post_content'] ) {
            global $wpdb;
            $wpdb->update( $wpdb->posts, array( 'post_content' => $new_content ), array('ID' => $post_ID) );
        }
    }

    /*
    * delete entry meta so it won't be duplicated
    */
    private static function delete_duplicated_meta( $action, $entry ) {
        global $wpdb;

        $field_ids = array();
        foreach ( $action->post_content as $name => $value ) {
            if ( is_numeric($value) ) {
                $field_ids[] = $value;
            } else if ( is_array($value) && isset($value['field_id']) && is_numeric($value['field_id']) ) {
                $field_ids[] = $value['field_id'];
            }
            unset($name, $value);
        }

        if ( ! empty($field_ids) ) {
            $wpdb->query( $wpdb->prepare('DELETE FROM '. $wpdb->prefix .'frm_item_metas WHERE item_id=%d AND field_id', $entry->id ) . ' IN ('. implode(',', $field_ids) .')');
        }
    }

    public static function destroy_post($entry_id, $entry = false) {
        global $wpdb;

        if ( $entry ) {
            $post_id = $entry->post_id;
        } else {
            $post_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->prefix}frm_items WHERE id=%d", $entry_id));
        }

        // delete child entries
        $child_entries = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}frm_items WHERE parent_item_id=%d", $entry_id));
        foreach ( $child_entries as $child_entry ) {
            FrmEntry::destroy($child_entry);
        }

        if ( $post_id ) {
            wp_delete_post($post_id);
        }
    }

    public static function create_comment($entry_id, $form_id){
        $comment_post_ID = isset($_POST['comment_post_ID']) ? (int) $_POST['comment_post_ID'] : 0;

        $post = get_post($comment_post_ID);

        if ( empty($post->comment_status) )
        	return;

        // get_post_status() will get the parent status for attachments.
        $status = get_post_status($post);

        $status_obj = get_post_status_object($status);

        if ( !comments_open($comment_post_ID) ) {
        	do_action('comment_closed', $comment_post_ID);
        	//wp_die( __('Sorry, comments are closed for this item.') );
        	return;
        } else if ( 'trash' == $status ) {
        	do_action('comment_on_trash', $comment_post_ID);
        	return;
        } else if ( ! $status_obj->public && ! $status_obj->private ) {
        	do_action('comment_on_draft', $comment_post_ID);
        	return;
        } else if ( post_password_required($comment_post_ID) ) {
        	do_action('comment_on_password_protected', $comment_post_ID);
        	return;
        } else {
        	do_action('pre_comment_on_post', $comment_post_ID);
        }

        $comment_content      = ( isset($_POST['comment']) ) ? trim($_POST['comment']) : '';

        // If the user is logged in
        $user_ID = get_current_user_id();
        if ( $user_ID ) {
            global $current_user;

        	$display_name = (!empty( $current_user->display_name )) ? $current_user->display_name : $current_user->user_login;
        	$comment_author       = $display_name;
        	$comment_author_email = ''; //get email from field
        	$comment_author_url   = $current_user->user_url;
        }else{
            $comment_author       = ( isset($_POST['author']) )  ? trim(strip_tags($_POST['author'])) : '';
            $comment_author_email = ( isset($_POST['email']) )   ? trim($_POST['email']) : '';
            $comment_author_url   = ( isset($_POST['url']) )     ? trim($_POST['url']) : '';
        }

        $comment_type = '';

        if (!$user_ID and get_option('require_name_email') and (6 > strlen($comment_author_email) || $comment_author == '') )
        		return;

        if ( $comment_content == '')
        	return;


        $commentdata = compact('comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_type', 'user_ID');

        wp_new_comment( $commentdata );

    }

	//If page size is set for views, only get the current page of entries
	public static function get_view_page( $current_p, $p_size, $where, $args ){
		//Make sure values are ints for use in DB call
		$current_p = (int) $current_p;
		$p_size = (int) $p_size;

		//Calculate end_index and start_index
        $end_index = $current_p * $p_size;
        $start_index = $end_index - $p_size;

		//Set limit and pass it to get_view_results
		$args['limit'] = " LIMIT $start_index,$p_size";
		$results = self::get_view_results($where, $args);

        return $results;
    }

	//Get ordered and filtered entries for Views
    public static function get_view_results($where, $args){
        global $wpdb;

		$defaults = array(
			'order_by_array' => array(), 'order_array' => array(),
			'limit' 	=> '', 'posts' => array(), 'meta' => 'get_meta',
			'display'   => false,
		);

		$args = wp_parse_args($args, $defaults);
        $args['time_field'] = false;

        $query = array(
            'select'    => 'SELECT it.id, it.item_key, it.name, it.ip, it.form_id, it.post_id, it.user_id, it.updated_by,
        it.created_at, it.updated_at, it.is_draft FROM '. $wpdb->prefix .'frm_items it',
            'where'     => $where,
            'order'     => 'ORDER BY it.created_at ASC',
        );

        //If order is set
		if ( ! empty($args['order_by_array']) ) {
			self::prepare_entries_query($query, $args);
		}
        $query = apply_filters('frm_view_order', $query, $args);

        if ( ! empty($query['where']) ) {
            $query['where'] =  'WHERE '. $query['where'];
        }

		$query['order'] = rtrim($query['order'], ', ');

		$query = implode($query, ' ') . $args['limit'];
        $entries = $wpdb->get_results($query, OBJECT_K);

		unset($query, $where);

		//If meta is not needed or if there aren't any entries, end function
        if ( $args['meta'] != 'get_meta' || ! $entries ) {
			return stripslashes_deep($entries);
		}

		$get_entry_ids = array_keys($entries);

		$cache_key = 'meta_form_id_'. (int) $args['display']->frm_form_id;
		$metas = wp_cache_get($cache_key, 'frm_entry');
		if ( false === $metas ) {
		    $metas = wp_cache_get($cache_key . implode('', $get_entry_ids), 'frm_entry');
		}

		if ( false === $metas ) {
    		//Get metas
    		foreach ( $get_entry_ids as $k => $e ) {
    			if ( wp_cache_get($e, 'frm_entry') ) {
    				unset($get_entry_ids[$k]);
    			}
    			unset($k, $e);
    		}

    		if ( empty($get_entry_ids) ) {
    			return stripslashes_deep($entries);
    		}

            if ( count($get_entry_ids) > 50 ) {
                $meta_where = $wpdb->prepare('fi.form_id = %d', $args['display']->frm_form_id);
            } else {
                $meta_where = 'item_id in ('. implode(',', array_filter($get_entry_ids, 'is_numeric')) .')';
                $cache_key .= implode('', $get_entry_ids);
            }

            $query = "SELECT item_id, meta_value, field_id, field_key FROM {$wpdb->prefix}frm_item_metas it
                LEFT OUTER JOIN {$wpdb->prefix}frm_fields fi ON it.field_id=fi.id
                WHERE $meta_where and field_id != 0";

            $metas = $wpdb->get_results($query);
            unset($query);

            wp_cache_set($cache_key, $metas, 'frm_entry', 300);
		}

        if ( $metas ) {
            foreach ( $metas as $m_key => $meta_val ) {
                if ( ! in_array($meta_val->item_id, $get_entry_ids) || ! isset($entries[$meta_val->item_id]) ) {
                    continue;
				}

                if ( !isset($entries[$meta_val->item_id]->metas) ) {
                    $entries[$meta_val->item_id]->metas = array();
				}

				$entries[$meta_val->item_id]->metas[$meta_val->field_id] = maybe_unserialize($meta_val->meta_value);
				unset($m_key, $meta_val);
            }

			//Cache each entry
            foreach ( $entries as $entry ) {
                wp_cache_set( $entry->id, $entry, 'frm_entry');
                unset($entry);
            }
        }

        self::reorder_time_entries($entries, $args['time_field']);

        return stripslashes_deep($entries);
    }

    private static function prepare_entries_query( &$query, &$args ) {
        if ( in_array('rand', $args['order_by_array']) ) {
            //If random is set, set the order to random
            $query['order'] = ' ORDER BY RAND()';
            return;
        }

		//Remove other ordering fields if created_at or updated_at is selected for first ordering field
		if ( reset($args['order_by_array']) == 'created_at' || reset($args['order_by_array']) == 'updated_at' ) {
			foreach ( $args['order_by_array'] as $o_key => $order_by_field ) {
				if ( is_numeric($order_by_field) ) {
					unset($args['order_by_array'][$o_key]);
					unset($args['order_array'][$o_key]);
				}
			}
            $numeric_order_array = array();
		} else {
		//Get number of fields in $args['order_by_array'] - this will not include created_at, updated_at, or random
		    $numeric_order_array = array_filter($args['order_by_array'], 'is_numeric');
		}

        if ( ! count($numeric_order_array) ) {
            //If ordering by creation date and/or update date without any fields
			$query['order'] = ' ORDER BY';

			foreach ( $args['order_by_array'] as $o_key => $order_by ) {
				$query['order'] .= ' it.' . $order_by . ' ' . $args['order_array'][$o_key] . ', ';
				unset($order_by);
			}
            return;
        }

	    //If ordering by at least one field (not just created_at, updated_at, or entry ID)
		$order_fields = array();
		foreach ( $args['order_by_array'] as $o_key => $order_by_field ) {
			if ( is_numeric($order_by_field) ) {
				$order_fields[$o_key] = FrmField::getOne($order_by_field);
			} else {
				$order_fields[$o_key] = $order_by_field;
			}
		}

		//Get all post IDs for this form
        $linked_posts = array();
       	foreach ( $args['posts'] as $post_meta ) {
        	$linked_posts[$post_meta->post_id] = $post_meta->id;
        }

        $first_order = true;
        $query['order'] = 'ORDER BY ';
		foreach ( $order_fields as $o_key => $o_field ) {
            self::prepare_ordered_entries_query( $query, $args, $o_key, $o_field, $first_order );
            $first_order = false;
			unset($o_field);
		}
    }

    private static function prepare_ordered_entries_query( &$query, &$args, $o_key, $o_field, $first_order ) {
        global $wpdb;

        //if field is some type of post field
		if ( isset($o_field->field_options['post_field']) && $o_field->field_options['post_field'] ) {

            //if field is custom field
			if ( $o_field->field_options['post_field'] == 'post_custom' ) {
                $query['select'] .= $wpdb->prepare(' LEFT JOIN '. $wpdb->postmeta .' pm'. $o_key .' ON pm'. $o_key .'.post_id=it.post_id AND pm'. $o_key .'.meta_key = %s ', $o_field->field_options['custom_field']);
                $query['order'] .= 'CASE when pm'. $o_key .'.meta_value IS NULL THEN 1 ELSE 0 END, pm'. $o_key .'.meta_value '. $args['order_array'][$o_key] .', ';
            } else if ( $o_field->field_options['post_field'] != 'post_category' ) {
                //if field is a non-category post field
                $query['select'] .= $first_order ? ' INNER ' : ' LEFT ';
				$query['select'] .= 'JOIN '. $wpdb->posts .' p'. $o_key .' ON p'. $o_key .'.ID=it.post_id ';

                $query['order'] .= 'CASE p'. $o_key .'.'. $o_field->field_options['post_field']." WHEN '' THEN 1 ELSE 0 END, p$o_key.". $o_field->field_options['post_field'].' '. $args['order_array'][$o_key] .', ';
            }
        } else if ( is_numeric($args['order_by_array'][$o_key]) ) {
            //if ordering by a normal, non-post field
            $query['select'] .= $wpdb->prepare(' LEFT JOIN '. $wpdb->prefix .'frm_item_metas em'. $o_key .' ON em'. $o_key .'.item_id=it.id AND em'. $o_key .'.field_id=%d ', $o_field->id);
            $query['order'] .= 'CASE when em'. $o_key .'.meta_value IS NULL THEN 1 ELSE 0 END, em'. $o_key .'.meta_value '. ( in_array($o_field->type, array('number', 'scale')) ? '+0 ' : '') . $args['order_array'][$o_key] .', ';

			//Meta value is only necessary for time field reordering and only if time field is first ordering field
			//Check if time field (for time field ordering)
			if ( $first_order && $o_field->type == 'time' ) {
                $args['time_field'] = $o_field;
            }
        } else {
            $query['order'] .= 'it.'. $o_field .' '. $args['order_array'][$o_key] .', ';
        }
    }

    /*
    * Reorder entries if 12 hour time field is selected for first ordering field.
    * If the $time_field variable is set, this means the first ordering field is a time field.
    */
    private static function reorder_time_entries( &$entries, $time_field ) {
        if ( ! $time_field || ! is_array($entries) || empty($entries) ) {
            return;
        }

        if ( isset($time_field->field_options['clock']) && $time_field->field_options['clock'] != 12 ) {
            // only reorder with 12 hour times
            return;
        }

		//Reorder entries
    	$new_order = array();
		$empty_times = array();
		foreach ( $entries as $e_key => $entry ) {
			if ( ! isset($entry->metas[$time_field->id]) ) {
				$empty_times[$e_key] = '';
				continue;
			}

        	$parts = str_replace(array(' PM',' AM'), '', $entry->metas[$time_field->id]);
        	$parts = explode(':', $parts);
        	if ( is_array($parts) ) {
            	if ( ( preg_match('/PM/', $entry->metas[$time_field->id]) && ( (int) $parts[0] != 12 ) ) ||
                ( ( (int) $parts[0] == 12 ) && preg_match('/AM/', $entry->metas[$time_field->id]) ) ) {
                	$parts[0] = ((int) $parts[0] + 12);
                }
        	}

        	$new_order[$e_key] = (int) $parts[0] . $parts[1];

        	unset($e_key, $entry);
		}

    	//array with sorted times
    	asort($new_order);

		$new_order = $new_order + $empty_times;

    	$final_order = array();
    	foreach ( $new_order as $key => $time ) {
        	$final_order[] = $entries[$key];
        	unset($key, $time);
    	}

        $entries = $final_order;
    }

    public static function get_field($field='is_draft', $id){
        _deprecated_function( __FUNCTION__, '2.0', 'FrmProEntriesHelper::get_field');
        return FrmProEntriesHelper::get_field($field, $id);
    }

    public static function update_post() {
        _deprecated_function( __FUNCTION__, '2.0', array('FrmProEntry::save_post') );
    }
}
