<?php
class FrmProEntryMeta{

    public static function before_save($values) {
        $field = FrmField::getOne($values['field_id']);
        if ( ! $field ) {
            return $values;
        }

        if ( $field->type == 'date' ) {
            $values['meta_value'] = FrmProAppHelper::maybe_convert_to_db_date($values['meta_value'], 'Y-m-d');
        } else if ( $field->type == 'number' && ! is_numeric($values['meta_value']) ) {
            $values['meta_value'] = (float) $values['meta_value'];
        }

        return $values;
    }

    /**
    * Upload files and add new tags
    *
    * @since 2.0
    * @param $values array (posted values)
    * @param $field_id integer
    * @param $entry_id integer
    * @return $values array
    *
    */
    public static function prepare_data_before_db( $values, $field_id, $entry_id ){
        // If confirmation field, exit now
        if ( ! is_numeric( $field_id ) ) {
            return $values;
        }

        // Get the field object
        $field = FrmField::getOne($field_id);

        // If a file upload field, upload file and get the media ID
        if ( $field->type == 'file' ) {
            // Get new meta values for file upload fields
            self::prepare_file_upload_meta( $values, $field, $entry_id );

        // If tags field, create new tags
        } else if ( $field->type == 'tag' ) {
            // Create new tags
            self::create_new_tags( $field, $entry_id, $values );
        }
        return $values;
    }

    private static function create_new_tags($field, $entry_id, $values) {
        $tax_type = ( isset($field->field_options['taxonomy']) && ! empty($field->field_options['taxonomy']) ) ? $field->field_options['taxonomy'] : 'frm_tag';

        $tags = explode(',', stripslashes($values[$field->id]));
        $terms = array();

        if ( isset($_POST['frm_wp_post']) ) {
            $_POST['frm_wp_post'][$field->id.'=tags_input'] = $tags;
        }

        if ( $tax_type != 'frm_tag' ) {
            return;
        }

        foreach ( $tags as $tag ) {
            $slug = sanitize_title($tag);
            if ( ! isset($_POST['frm_wp_post']) ) {
                if ( ! term_exists($slug, $tax_type) ) {
                    wp_insert_term( trim($tag), $tax_type, array('slug' => $slug));
                }
            }

            $terms[] = $slug;
        }

        wp_set_object_terms($entry_id, $terms, $tax_type);

    }

    public static function validate($errors, $field, $value, $args) {
        $field->temp_id = $args['id'];

        // Keep current value for "Other" fields because it is needed for correct validation
        if ( ! $args['other'] ) {
            FrmEntriesHelper::get_posted_value($field, $value, $args);
        }

        if ( $field->type == 'form' ||  $field->type == 'divider' ) {
            self::validate_embeded_form($errors, $field);
        } else if ( $field->type == 'user_id' ) {
            // make sure we have a user ID
            if ( ! is_numeric($value) ) {
                $value = FrmAppHelper::get_user_id_param($value);
                FrmEntriesHelper::set_posted_value($field, $value, $args);
            }

            //add user id to post variables to be saved with entry
            $_POST['frm_user_id'] = $value;
        } else if ( $field->type == 'time' && is_array($value) ) {
            $value = $value['H'] .':'. $value['m'] . ( isset($value['A']) ? ' '. $value['A'] : '' );
            FrmEntriesHelper::set_posted_value($field, $value, $args);
        }

        // don't validate if going backwards
        if ( FrmProFormsHelper::going_to_prev($field->form_id) ) {
            return array();
        }

        // clear any existing errors if draft
        if ( FrmProFormsHelper::saving_draft() && isset($errors['field'. $field->temp_id]) ) {
            unset($errors['field'. $field->temp_id]);
        }

        self::validate_file_upload($errors, $field, $args);

        // if saving draft, only check file type since it won't be checked later
        // and confirmation field since the confirmation field value is not saved
        if ( FrmProFormsHelper::saving_draft() ) {

            //Check confirmation field if saving a draft
    		self::validate_confirmation_field($errors, $field, $value, $args);

            return $errors;
        }

        self::validate_no_input_fields($errors, $field);

        if ( empty($args['parent_field_id']) && ! isset($_POST['item_meta'][$field->id]) ) {
            return $errors;
        }
        
        if((($field->type != 'tag' and $value == 0) or ($field->type == 'tag' and $value == '')) and isset($field->field_options['post_field']) and $field->field_options['post_field'] == 'post_category' and $field->required == '1'){
            $frm_settings = FrmAppHelper::get_settings();
            $errors['field'. $field->temp_id] = ( ! isset($field->field_options['blank']) || $field->field_options['blank'] == '' || $field->field_options['blank'] == 'Untitled cannot be blank' ) ? $frm_settings->blank_msg : $field->field_options['blank'];
        }

        //Don't require fields hidden with shortcode fields="25,26,27"
        global $frm_vars;
        if ( isset($frm_vars['show_fields']) && ! empty($frm_vars['show_fields']) && is_array($frm_vars['show_fields']) && $field->required == '1' && isset($errors['field'. $field->temp_id]) && ! in_array($field->id, $frm_vars['show_fields']) && ! in_array($field->field_key, $frm_vars['show_fields'])){
            unset($errors['field'. $field->temp_id]);
            $value = '';
        }

        //Don't require a conditionally hidden field
        self::validate_conditional_field($errors, $field, $value);

        //Don't require a field hidden in a conditional page or section heading
        self::validate_child_conditional_field($errors, $field, $value);

        //make sure the [auto_id] is still unique
        self::validate_auto_id($field, $value);

        //check uniqueness
        self::validate_unique_field($errors, $field, $value);
        self::set_post_fields($field, $value, $errors);

        if ( ! FrmProFieldsHelper::is_field_visible_to_user($field) ) {
            //don't validate admin only fields that can't be seen
            unset($errors['field'. $field->temp_id]);
            FrmEntriesHelper::set_posted_value($field, $value, $args);
            return $errors;
        }

		self::validate_confirmation_field($errors, $field, $value, $args);

        //Don't validate the format if field is blank
        if ( FrmAppHelper::is_empty_value( $value ) ) {
            FrmEntriesHelper::set_posted_value($field, $value, $args);
            return $errors;
        }

        if ( ! is_array($value) ) {
            $value = trim($value);
        }

        $validate_fields = array('number', 'phone', 'date');
        if ( in_array($field->type, $validate_fields) ) {
            $function_name = 'validate_'. $field->type .'_field';
            self::$function_name($errors, $field, $value);
        }

        FrmEntriesHelper::set_posted_value($field, $value, $args);
        return $errors;
    }

    public static function validate_embeded_form(&$errors, $field) {
        // If this is a section, but not a repeating section, exit now
        if ( $field->type == 'divider' && ! FrmProFieldsHelper::is_repeating_field($field) ) {
            return;
        }

        $subforms = array();
        FrmProFieldsHelper::get_subform_ids($subforms, $field);

        if ( empty($subforms) ) {
            return;
        }

        $where = apply_filters('frm_posted_field_ids', 'fi.form_id in ('. implode(',', array_filter($subforms, 'is_numeric')) .')');
        /*if ( $exclude ) {
            $where .= " and fi.type not in ('". implode("','", $exclude) ."')";
        }*/

        $subfields = FrmField::getAll($where, 'field_order');
        unset($where);

        foreach ( $subfields as $subfield ) {
            if ( isset($_POST['item_meta'][$field->id]) ){
                foreach ( $_POST['item_meta'][$field->id] as $k => $values ) {
                    if ( ! empty($k) && in_array($k, array('form', 'id')) ) {
                        continue;
                    }

                    FrmEntry::validate_field( $subfield, $errors,
                        ( isset($values[$subfield->id]) ? $values[$subfield->id] : '' ),
                        array(
                            'parent_field_id'  => $field->id,
                            'key_pointer'   => $k,
                            'id'            => $subfield->id .'-'. $field->id .'-'. $k,
                        )
                    );

                    unset($k, $values);
                }
            } else {
                // TODO: do something if nothing was submitted
            }
        }
    }

    public static function validate_file_upload(&$errors, $field, $args) {
        //if the field is a file upload, check for a file
        if ( $field->type != 'file' ) {
            return;
        }

        $file_name = 'file'. $field->id;
        if ( isset($args['key_pointer']) && $args['key_pointer'] ) {
            $file_name .= '-'. $args['key_pointer'];
        }

        if ( ! isset($_FILES[$file_name]) ) {
            return;
        }

        $file_uploads = $_FILES[$file_name];

        //if the field is a file upload, check for a file
        if ( empty($file_uploads['name']) ) {
            return;
        }

        $filled = true;
        if ( is_array($file_uploads['name']) ) {
            $filled = false;
            foreach ( $file_uploads['name'] as $n ) {
                if ( !empty($n) ) {
                    $filled = true;
                }
            }
        }

        if ( ! $filled ) {
            // no file was uploaded
            return;
        }

        if ( isset($errors['field'. $field->temp_id]) ) {
            unset($errors['field'. $field->temp_id]);
        }

        if ( isset($field->field_options['restrict']) && $field->field_options['restrict'] && isset($field->field_options['ftypes']) && ! empty($field->field_options['ftypes']) ) {
            $mimes = $field->field_options['ftypes'];
        } else {
            $mimes = null;
        }

        if ( is_array($file_uploads['name']) ) {
            foreach ( $file_uploads['name'] as $name ) {

                // check allowed file size
                if ( ! empty($file_uploads['error']) && in_array(1, $file_uploads['error']) ) {
                    $errors['field'. $field->temp_id] = __('This file is too big', 'formidable');
                }

                if ( empty($name) ) {
                    continue;
                }

                //check allowed mime types for this field
                $file_type = wp_check_filetype( $name, $mimes );
                unset($name);

                if ( ! $file_type['ext'] ) {
                    break;
                }
            }
        } else {
            // check allowed file size
            if ( ! empty($file_uploads['error']) && in_array(1, $file_uploads['error']) ) {
                $errors['field'. $field->temp_id] = __('This file is too big', 'formidable');
            }

            $file_type = wp_check_filetype( $file_uploads['name'], $mimes );
        }

        if ( isset($file_type) && ! $file_type['ext'] ) {
            $errors['field'. $field->temp_id] = ($field->field_options['invalid'] == __('This field is invalid', 'formidable') || $field->field_options['invalid'] == '' || $field->field_options['invalid'] == $field->name.' '. __('is invalid', 'formidable')) ? __('Sorry, this file type is not permitted for security reasons.', 'formidable') : $field->field_options['invalid'];
        }
    }

    /*
    * Remove any errors set on fields with no input
    * Also set global to indicate whether section is hidden
    */
    public static function validate_no_input_fields(&$errors, $field) {
        if ( ! in_array($field->type, array('break', 'html', 'divider', 'end_divider')) ) {
            return;
        }

        $hidden = FrmProFieldsHelper::is_field_hidden($field, stripslashes_deep($_POST));
        if ( $field->type == 'break' ) {
            global $frm_hidden_break;
            $frm_hidden_break = array('field_order' => $field->field_order, 'hidden' => $hidden);
        } else if ( $field->type == 'divider' ) {
            global $frm_hidden_divider;
            $frm_hidden_divider = array('field_order' => $field->field_order, 'hidden' => $hidden);
        }

        if ( isset($errors['field'. $field->temp_id]) ) {
            unset($errors['field'. $field->temp_id]);
        }
    }

    public static function validate_hidden_shortcode_field(&$errors, $field, &$value) {
        if ( ! isset($errors['field'. $field->temp_id]) ) {
            return;
        }

        //Don't require fields hidden with shortcode fields="25,26,27"
        global $frm_vars;
        if ( isset($frm_vars['show_fields']) && ! empty($frm_vars['show_fields']) && is_array($frm_vars['show_fields']) && $field->required == '1' && ! in_array($field->id, $frm_vars['show_fields']) && ! in_array($field->field_key, $frm_vars['show_fields'])){
            unset($errors['field'. $field->temp_id]);
            $value = '';
        }
    }

    /*
    * Don't require a conditionally hidden field
    */
    public static function validate_conditional_field(&$errors, $field, &$value) {
        if ( ! isset($field->field_options['hide_field']) || empty($field->field_options['hide_field']) ) {
            return;
        }

        if ( FrmProFieldsHelper::is_field_hidden($field, stripslashes_deep($_POST)) ) {
            if ( isset($errors['field'. $field->temp_id]) ) {
                unset($errors['field'. $field->temp_id]);
            }
            $value = '';
        }
    }

    /*
    * Don't require a field hidden in a conditional page or section heading
    */
    public static function validate_child_conditional_field(&$errors, $field, &$value) {
        if ( ! isset($errors['field'. $field->temp_id]) && $value == '' ) {
            return;
        }

        global $frm_hidden_break, $frm_hidden_divider;
        if ( ( $frm_hidden_break && $frm_hidden_break['hidden'] ) || ( $frm_hidden_divider && $frm_hidden_divider['hidden'] && ( ! $frm_hidden_break || $frm_hidden_break['field_order'] < $frm_hidden_divider['field_order'] ) ) ) {
            if ( isset($errors['field'. $field->temp_id]) ) {
                unset($errors['field'. $field->temp_id]);
            }
            $value = '';
        }
    }

    /*
    * Make sure the [auto_id] is still unique
    */
    public static function validate_auto_id($field, &$value) {
        if ( empty($field->default_value) || is_array($field->default_value) || empty($value) || ! is_numeric($value) || strpos($field->default_value, '[auto_id') === false ) {
            return;
        }

        //make sure we are not editing
        if ( ( $_POST && ! isset($_POST['id']) ) || ! is_numeric($_POST['id']) ) {
            $value = FrmProFieldsHelper::get_default_value($field->default_value, $field);
        }
    }

    public static function validate_unique_field(&$errors, $field, $value) {
        //check uniqueness
        if ( empty($value) || ! isset($field->field_options['unique']) || ! $field->field_options['unique'] ) {
            return;
        }
        
        $entry_id = ( $_POST && isset($_POST['id']) ) ? $_POST['id'] : false;

        // get the child entry id for embedded or repeated fields
        if ( isset($field->temp_id) ) {
            $temp_id_parts = explode('-i', $field->temp_id);
            if ( isset($temp_id_parts[1]) ) {
                $entry_id = $temp_id_parts[1];
            }
        }

        if ( $field->type == 'time' ) {
            //TODO: add server-side validation for unique date-time
        } else if ( $field->type == 'date' ) {
            $value = FrmProAppHelper::maybe_convert_to_db_date($value, 'Y-m-d');

            if ( FrmProEntryMetaHelper::value_exists($field->id, $value, $entry_id) ) {
                $errors['field'. $field->temp_id] = FrmFieldsHelper::get_error_msg($field, 'unique_msg');
            }
        } else if ( FrmProEntryMetaHelper::value_exists($field->id, $value, $entry_id) ) {
            $errors['field'. $field->temp_id] = FrmFieldsHelper::get_error_msg($field, 'unique_msg');
        }
    }

    public static function validate_confirmation_field(&$errors, $field, $value, $args) {
		//Make sure confirmation field matches original field
		if ( ! isset($field->field_options['conf_field']) || ! $field->field_options['conf_field'] ) {
            return;
        }

        if ( FrmProFormsHelper::saving_draft() ) {
            //Check confirmation field if saving a draft
            $args['action'] = ( $_POST['frm_action'] == 'create' ) ? 'create' : 'update';
            self::validate_check_confirmation_field($errors, $field, $value, $args);
            return;
        }

        $args['action'] = ( $_POST['frm_action'] == 'update' ) ? 'update' : 'create';
        
        self::validate_check_confirmation_field($errors, $field, $value, $args);
    }

    public static function validate_check_confirmation_field(&$errors, $field, $value, $args) {
        $conf_val = '';
        $field_id = $field->id;
        $field->id = 'conf_'. $field_id;
        FrmEntriesHelper::get_posted_value($field, $conf_val, $args);
        $field->id = $field_id;
        unset($field_id);

        //If editing entry or if user hits Next/Submit on a draft
        if ( $args['action'] == 'update' ) {
            //If in repeating section
            if ( isset( $args['key_pointer'] ) && $args['key_pointer'] ) {
                $entry_id = str_replace( 'i', '', $args['key_pointer'] );
            } else {
                $entry_id = ( $_POST && isset($_POST['id']) ) ? $_POST['id'] : false;
            }

            $prev_value = FrmEntryMeta::get_entry_meta_by_field($entry_id, $field->id);

            if ( $prev_value != $value && $conf_val != $value ) {
                $errors['conf_field'. $field->temp_id] = isset($field->field_options['conf_msg']) ? $field->field_options['conf_msg'] : __('The entered values do not match', 'formidable');
                $errors['field' . $field->temp_id] = '';
            }
        } else if ( $args['action'] == 'create' && $conf_val != $value ) {
            //If creating entry
            $errors['conf_field'. $field->temp_id] = isset($field->field_options['conf_msg']) ? $field->field_options['conf_msg'] : __('The entered values do not match', 'formidable');
            $errors['field' . $field->temp_id] = '';
        }
    }

    public static function validate_number_field(&$errors, $field, $value) {
        //validate the number format
        if ( $field->type != 'number' ) {
            return;
        }
        
        if ( ! is_numeric($value) ) {
            $errors['field'. $field->temp_id] = FrmFieldsHelper::get_error_msg($field, 'invalid');
        }

        // validate number settings
		if ( $value != '' ) {
		    $frm_settings = FrmAppHelper::get_settings();
		    // only check if options are available in settings
		    if ( $frm_settings->use_html && isset($field->field_options['minnum']) && isset($field->field_options['maxnum']) ) {
		        //minnum maxnum
		        if ( (float) $value < $field->field_options['minnum'] ) {
		            $errors['field'. $field->temp_id] = __('Please select a higher number', 'formidable');
		        } else if ( (float) $value > $field->field_options['maxnum'] ) {
		            $errors['field'. $field->temp_id] = __('Please select a lower number', 'formidable');
		        }
		    }

		}
    }

    public static function validate_phone_field(&$errors, $field, $value) {
        if ( $field->type != 'phone' ) {
            return;
        }

        $pattern = ( isset($field->field_options['format']) && ! empty($field->field_options['format']) ) ? $field->field_options['format'] : '^((\+\d{1,3}(-|.| )?\(?\d\)?(-| |.)?\d{1,5})|(\(?\d{2,6}\)?))(-|.| )?(\d{3,4})(-|.| )?(\d{4})(( x| ext)\d{1,5}){0,1}$';
        $pattern = apply_filters('frm_phone_pattern', $pattern, $field);

        //check if format is already a regular expression
        if ( strpos($pattern, '^') !== 0 ) {
            //if not, create a regular expression
            $pattern = preg_replace('/\d/', '\d', preg_quote($pattern));
            $pattern = str_replace('a', '[a-z]', $pattern);
            $pattern = str_replace('A', '[A-Z]', $pattern);
            $pattern = str_replace('/', '\/', $pattern);
            $pattern = '/^'. $pattern .'$/';
        } else {
            $pattern = '/'. $pattern .'/';
        }

        if ( ! preg_match($pattern, $value) ) {
            $errors['field'. $field->temp_id] = FrmFieldsHelper::get_error_msg($field, 'invalid');
        }
    }

    public static function validate_date_field(&$errors, $field, $value) {
        if ( $field->type != 'date' ) {
            return;
        }

        if ( ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ) {
            $frmpro_settings = new FrmProSettings();
            $formated_date = FrmProAppHelper::convert_date($value, $frmpro_settings->date_format, 'Y-m-d');

            //check format before converting
            if ( $value != date($frmpro_settings->date_format, strtotime($formated_date)) ) {
                $errors['field'. $field->temp_id] = FrmFieldsHelper::get_error_msg($field, 'invalid');
            }

            $value = $formated_date;
            unset($formated_date);
        }
        $date = explode('-', $value);

        if ( count($date) != 3 || ! checkdate( (int) $date[1], (int) $date[2], (int) $date[0]) ) {
            $errors['field'. $field->temp_id] = FrmFieldsHelper::get_error_msg($field, 'invalid');
        }
    }

    /**
    * Get media ID(s) to be saved to database and set global media ID values
    *
    * @since 2.0
    * @param $values array (posted values)
    * @param $field_id integer
    * @param $entry_id integer
    * @return $values array
    *
    */
    private static function prepare_file_upload_meta( &$values, $field, $entry_id ) {
        // If there are no files to be uploaded, exit now
        if ( ! isset( $_FILES ) ) {
            return;
        }

        // Assume this field is not repeating
        $repeating = $key_pointer = $parent_field = $file_name = false;

        // Get file name
        self::get_file_name( $field->id, $file_name, $parent_field, $key_pointer, $repeating );

        // If there isn't a file uploaded in this field, exit now
        if ( ! isset( $_FILES[$file_name]) || empty($_FILES[$file_name]['name']) || (int) $_FILES[$file_name]['size'] == 0 ) {
            return;
        }

        // Upload the file now
        $media_ids = FrmProAppHelper::upload_file($file_name);

        // Get filtered media IDs
        $mids = self::get_final_media_ids( $media_ids, $field, $values );

        // If no media IDs to upload, end now
        if ( empty($mids) ) {
            return;
        }

        // Get global frm_vars variable
        global $frm_vars;

        // Set up progress bar to display on form submission
        if ( ! isset($frm_vars['loading']) || ! $frm_vars['loading'] ) {
            $frm_vars['loading'] = true;
        }

        // Set up global media_id vars. This will be used for post fields.
        if ( ! isset( $frm_vars['media_id'] ) ) {
            $frm_vars['media_id'] = array();
        }

        // If not inside of a repeating section, set the media IDs for this field
        if ( ! $repeating ) {
            $frm_vars['media_id'][$field->id] = $mids;
        }

        // Set new posted values
        self::set_file_posted_vals( $field->id, $mids, array( 'repeating' => $repeating, 'parent_field' => $parent_field, 'key_pointer' => $key_pointer ) );

        // If this is a post field
        if ( isset( $_POST['frm_wp_post'] ) && isset( $field->field_options['post_field'] ) && $field->field_options['post_field'] ) {
            $_POST['frm_wp_post_custom'][$field->id .'='. $field->field_options['custom_field']] = $mids;
        }
    }

    /**
    *
    * @since 2.0
    * @param $field_id
    * @param $new_value to set
    * @param $args array with repeating, key_pointer, and parent_field
    */
    private static function set_file_posted_vals( $field_id, $new_value, $args = array() ) {
        // If in repeating section
        if ( $args['repeating'] ) {
            $_POST['item_meta'][$args['parent_field']][$args['key_pointer']][$field_id] = $new_value;

        // If not in repeating section
        } else {
            $_POST['item_meta'][$field_id] = $new_value;
        }
    }

    /**
    * Get final meta value for file upload fields and print errors if there are any
    *
    * @since 2.0
    * @param $media_ids, usually array
    * @param $field object
    * @param $values array to save to database
    * @return $mids array of numeric media ids
    */
    private static function get_final_media_ids( $media_ids, $field, &$values ) {
        $mids = array();
        foreach ( (array) $media_ids as $media_id ) {
            if ( is_numeric($media_id) ) {
               $mids[] = $media_id;
            } else {
                foreach ( $media_id->errors as $error ) {
                    if ( ! is_array($error[0]) ) {
                        echo $error[0];
                    }
                    unset($error);
                }
            }
            unset($media_id);
        }

        // If no media IDs to upload, end now
        if ( empty( $mids ) ) {
            return;
        }

        // Get the new meta_value for multi-file uploads
        if ( isset($field->field_options['multiple']) && $field->field_options['multiple'] ) {
            if ( isset($values[$field->id]) ) {
                // Set new value
                $mids = array_filter($mids);
                $values[$field->id] = array_merge( (array) $values[$field->id], $mids );
            }

        // Get the new meta_value for single file uploads
        } else {
            $mids = reset($mids);

            if ( isset($values[$field->id]) && count($values[$field->id]) == 1 && $values[$field->id] != $mids ) {
                $frm_vars['detached_media'][] = $values[$field->id];
            }
            // Set new value
            $values[$field->id] = $mids;
        }

        return $mids;
    }

    /**
    * Get name of uploaded file
    *
    * @since 2.0
    * @param $field_id integer
    * @param $file_name string, pass by reference
    * @param $parent_field. Retrieves ID of repeating section.
    * @param $key_pointer. Gets pointer if in repeating section.
    * @param $repeating boolean. Tells whether field is inside of repeating section.
    *
    */
    public static function get_file_name( $field_id, &$file_name, &$parent_field, &$key_pointer, &$repeating ) {
        $file_name = 'file'. $field_id;

        // Check if there are repeating sections in the form, and adjust the filename accordingly
        if ( isset( $_POST['item_meta']['key_pointer'] ) && isset( $_POST['item_meta']['parent_field'] ) ) {
            // Get the current pointer
            $key_pointer = $_POST['item_meta']['key_pointer'];

            // Get the current parent
            $parent_field = $_POST['item_meta']['parent_field'];

            // Check if the current field is inside of the parent/pointer
            if ( isset( $_POST['item_meta'][$parent_field][$key_pointer][$field_id] ) ) {
                $file_name .= '-'. $key_pointer;
                $repeating = true;
            }
        }
    }

    /*
    * Get metas for post or non-post fields
    *
    * @since 2.0
    */
    public static function get_all_metas_for_field( $field, $args=array() ){
        global $wpdb;

        // If field is not a post field
        if ( ! isset( $field->field_options['post_field'] ) || ! $field->field_options['post_field'] ) {
            $query = 'SELECT em.meta_value FROM '. $wpdb->prefix .'frm_item_metas em ';
            $query .= 'INNER JOIN '. $wpdb->prefix .'frm_items e ON (e.id=em.item_id)';
            $query .= $wpdb->prepare('WHERE em.field_id=%d', $field->id) . ' AND e.is_draft=0';

        // If field is a custom field
        } else if ( $field->field_options['post_field'] == 'post_custom' ) {
            $query = "SELECT pm.meta_value FROM " . $wpdb->postmeta . " pm";
            $query .= " INNER JOIN " . $wpdb->prefix . "frm_items e ON pm.post_id=e.post_id";
            $query .= " WHERE pm.meta_key='" . $field->field_options['custom_field'] . "'";

            // Make sure to only get post metas that are linked to this form
            $query .= " AND e.form_id=" . $field->form_id;

        // If field is a non-category post field
        } else if ( $field->field_options['post_field'] != 'post_category'){
            $query = "SELECT p." . $field->field_options['post_field'] . " FROM " . $wpdb->posts . " p";
            $query .= " INNER JOIN " . $wpdb->prefix . "frm_items e ON p.ID=e.post_id";
            // Make sure to only get post metas that are linked to this form
            $query .= " WHERE e.form_id=" . $field->form_id;

        // If field is a category field
        } else {
            //TODO: Make this work
            return array();
            //$field_options = FrmProFieldsHelper::get_category_options( $field );
        }

        // Add queries for additional args
        self::add_meta_query( $query, $args );

        // Cache it
        $cache_key = 'all_metas_for_field_'. $field->id . maybe_serialize($args);

        // Get the metas
        $metas = FrmAppHelper::check_cache( $cache_key, 'frm_entry', $query, 'get_col' );

        // Maybe unserialize
        foreach ( $metas as $k => $v ) {
            $metas[$k] = maybe_unserialize($v);
            unset($k, $v);
        }

        // Strip slashes
        $metas = stripslashes_deep( $metas );

        return $metas;
    }

    public static function add_meta_query( &$query, $args ) {
        global $wpdb;

        // If entry IDs is set
        if ( isset( $args['entry_ids'] ) ) {
            $query .= ' AND e.id in (' . implode( ',', $args['entry_ids'] ) . ')';
        }

        // If user ID is set
        if ( isset( $args['user_id'] ) ) {
            $query .= $wpdb->prepare( ' AND e.user_id=%d', $args['user_id'] );
        }

        // If start date is set
        if ( isset( $args['start_date'] ) ) {
            $start_date = $wpdb->prepare('%s', date('Y-m-d', strtotime( $args['start_date'] ) ) );
            $query .= ' AND e.created_at>=' . $start_date;
        }

        // If end date is set
        if ( isset( $args['end_date'] ) ) {
            $end_date = $wpdb->prepare('%s', date('Y-m-d 23:59:59', strtotime( $args['end_date'] )));
            $query .= ' AND e.created_at<=' . $end_date;
        }
    }

    public static function set_post_fields($field, $value, &$errors) {
        $errors = FrmProEntryMetaHelper::set_post_fields($field, $value, $errors);
        return $errors;
    }

}
