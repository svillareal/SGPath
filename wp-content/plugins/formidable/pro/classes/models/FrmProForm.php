<?php
class FrmProForm{

    public static function update_options($options, $values){
        $defaults = FrmProFormsHelper::get_default_opts();
        unset($defaults['logged_in']);
        unset($defaults['editable']);
        unset($defaults['notification']);

        foreach ( $defaults as $opt => $default ) {
            $options[$opt] = (isset($values['options'][$opt])) ? $values['options'][$opt] : $default;

            unset($opt, $default);
        }

        unset($defaults);

        $options['single_entry'] = (isset($values['options']['single_entry'])) ? $values['options']['single_entry'] : 0;
        if ($options['single_entry'])
            $options['single_entry_type'] = (isset($values['options']['single_entry_type'])) ? $values['options']['single_entry_type'] : 'cookie';

        if ( is_multisite() ) {
            $options['copy'] = (isset($values['options']['copy'])) ? $values['options']['copy'] : 0;
        }

        return $options;
    }

    public static function save_wppost_actions($settings, $action) {
        $form_id = $action['menu_order'];

        if ( isset($settings['post_custom_fields']) ) {
            foreach ( $settings['post_custom_fields'] as $cf_key => $n ) {
                if ( ! isset($n['custom_meta_name']) ) {
                    continue;
                }

                if ( $n['meta_name'] == '' && $n['custom_meta_name'] != '' ) {
                    $settings['post_custom_fields'][$cf_key]['meta_name'] = $n['custom_meta_name'];
                }

                unset($settings['post_custom_fields'][$cf_key]['custom_meta_name']);

                unset($cf_key, $n);
            }
        }

        self::create_post_category_field($settings, $form_id);
        self::create_post_status_field($settings, $form_id);

        //update/create View
        if ( ! empty($settings['display_id']) ) {

            if ( is_numeric($settings['display_id']) ) {
                //updating View
                $type = get_post_meta($settings['display_id'], 'frm_show_count', true);

                if ( 'one' == $type ) {
                    $display = get_post($settings['display_id'], ARRAY_A);
                    $display['post_content'] = $_POST['dyncontent'];
                    wp_insert_post( $display );
                } else {
                    update_post_meta($settings['display_id'], 'frm_dyncontent', $_POST['dyncontent']);
                }
            } else if ( 'new' == $settings['display_id'] ) {
                // Get form name for View title
                $form = FrmForm::getOne( $form_id );
                if ( !empty( $form->name ) ) {
                    $post_title = $form->name;
                } else {
                    $post_title = __( 'Single Post', 'formidable' );
                }

                //create new
                $cd_values = array(
                    'post_status'   => 'publish',
                    'post_type'     => 'frm_display',
                    'post_title'    => $post_title,
                    'post_excerpt'  => __( 'Used for the single post page', 'formidable' ),
                    'post_content'  => $_POST['dyncontent'],
                );

                $display_id = wp_insert_post( $cd_values );
                $settings['display_id'] = $display_id;

                unset($cd_values);

                update_post_meta($display_id, 'frm_param', 'entry');
                update_post_meta($display_id, 'frm_type', 'display_key');
                update_post_meta($display_id, 'frm_show_count', 'one');
                update_post_meta($display_id, 'frm_form_id', $form_id);
            }
        }

        return $settings;
    }

    private static function create_post_category_field(array &$settings, $form_id) {
        if ( ! isset($settings['post_category']) || ! $settings['post_category'] ) {
            return;
        }

        foreach ( $settings['post_category'] as $k => $field_name ) {
            if ( $field_name['field_id'] != 'checkbox' ) {
                continue;
            }

            //create a new field
            $new_values = apply_filters('frm_before_field_created', FrmFieldsHelper::setup_new_vars('checkbox', $form_id));
            $new_values['field_options']['taxonomy'] = isset($field_name['meta_name']) ? $field_name['meta_name'] : 'category';
            $new_values['name'] = ucwords(str_replace('_', ' ', $new_values['field_options']['taxonomy']));
            $new_values['field_options']['post_field'] = 'post_category';
            $new_values['field_options']['exclude_cat'] = isset($field_name['exclude_cat']) ? $field_name['exclude_cat'] : 0;

            $settings['post_category'][$k]['field_id'] = FrmField::create( $new_values );

            unset($new_values, $k, $field_name);
        }
    }

    private static function create_post_status_field(array &$settings, $form_id) {
        if ( ! isset($settings['post_status']) || 'dropdown' != $settings['post_status'] ) {
            return;
        }

        //create a new field
        $new_values = apply_filters('frm_before_field_created', FrmFieldsHelper::setup_new_vars('select', $form_id));
        $new_values['name'] = __( 'Status', 'formidable' );
        $new_values['field_options']['post_field'] = 'post_status';
        $settings['post_status'] = FrmField::create( $new_values );
    }

    public static function update_form_field_options($field_options, $field) {
        $field_options['post_field'] = $field_options['custom_field'] = '';
        $field_options['taxonomy'] = 'category';
        $field_options['exclude_cat'] = 0;

        $post_action = FrmFormActionsHelper::get_action_for_form($field->form_id, 'wppost', 1);
        if ( ! $post_action ) {
            return $field_options;
        }

        $post_fields = array(
            'post_content', 'post_excerpt', 'post_title',
            'post_name', 'post_date', 'post_status', 'post_password',
        );

        $this_post_field = array_search($field->id, $post_action->post_content);
        if ( in_array($this_post_field, $post_fields) ) {
            $field_options['post_field'] = $this_post_field;
        }
        unset($this_post_field);

        //Set post categories
        foreach ( (array) $post_action->post_content['post_category'] as $field_name ) {
            if ( ! isset($field_name['field_id']) || $field_name['field_id'] != $field->id ) {
                continue;
            }

            $field_options['post_field'] = 'post_category';
            $field_options['taxonomy'] = isset($field_name['meta_name']) ? $field_name['meta_name'] : 'category';
            $field_options['exclude_cat'] = isset($field_name['exclude_cat']) ? $field_name['exclude_cat'] : 0;
        }

        //Set post custom fields
        foreach ( (array) $post_action->post_content['post_custom_fields'] as $field_name ) {
            if ( ! isset($field_name['field_id']) || $field_name['field_id'] != $field->id ) {
                continue;
            }

            $field_options['post_field'] = 'post_custom';
            $field_options['custom_field'] = ( $field_name['meta_name'] == '' && isset($field_name['custom_meta_name']) && $field_name['custom_meta_name'] != '' ) ? $field_name['custom_meta_name'] : $field_name['meta_name'];
        }

        return $field_options;
    }

	public static function update( $id, $values ) {
        global $wpdb;

		if ( isset( $values['options'] ) ) {
            $logged_in = isset($values['logged_in']) ? $values['logged_in'] : 0;
            $editable = isset($values['editable']) ? $values['editable'] : 0;
            $updated = $wpdb->update( $wpdb->prefix .'frm_forms', array( 'logged_in' => $logged_in, 'editable' => $editable), array( 'id' => $id ) );
			if ( $updated ) {
				wp_cache_delete( $id, 'frm_form' );
				unset( $updated );
			}
        }
    }

    public static function after_duplicate($new_opts) {
        if ( isset($new_opts['success_url']) ) {
            $new_opts['success_url'] = FrmFieldsHelper::switch_field_ids($new_opts['success_url']);
        }

        return $new_opts;
    }

    public static function validate( $errors, $values ){
        /*
        if (isset($values['item_meta'])){
            foreach($values['item_meta'] as $key => $value){
                $field = FrmField::getOne($key);
                if ($field && $field->type == 'hidden' and empty($value))
                    $errors[] = __( 'Hidden fields must have a value.', 'formidable' );
            }

        }
        */

        // add a user id field if the form requires one
        if ( isset($values['logged_in']) || isset($values['editable']) || (isset($values['single_entry']) && isset($values['options']['single_entry_type']) && $values['options']['single_entry_type'] == 'user') || (isset($values['options']['save_draft']) && $values['options']['save_draft'] == 1) ) {
            $form_id = $values['id'];

            $user_field = FrmField::get_all_types_in_form($form_id, 'user_id', 1);
            if ( ! $user_field ) {
                $new_values = FrmFieldsHelper::setup_new_vars('user_id', $form_id);
                $new_values['name'] = __( 'User ID', 'formidable' );
                FrmField::create($new_values);
            }
        }

        if (isset($values['options']['auto_responder'])){
            if ( ! isset($values['options']['ar_email_message']) || $values['options']['ar_email_message'] == '' ) {
                $errors[] = __( 'Please insert a message for your auto responder.', 'formidable' );
            }
            if ( isset($values['options']['ar_reply_to']) && ! is_email( trim( $values['options']['ar_reply_to'] ) ) ) {
                $errors[] = __( 'That is not a valid reply-to email address for your auto responder.', 'formidable' );
            }
        }

        return $errors;
    }

}
