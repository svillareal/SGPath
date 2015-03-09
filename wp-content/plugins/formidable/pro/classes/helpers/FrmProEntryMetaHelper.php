<?php

class FrmProEntryMetaHelper{

    public static function email_value($value, $meta, $entry) {
        if ( $entry->id != $meta->item_id ) {
            $entry = FrmEntry::getOne($meta->item_id);
        }

        $field = FrmField::getOne($meta->field_id);
        if ( ! $field ) {
            return $value;
        }

        if(isset($field->field_options['post_field']) and $field->field_options['post_field']){
            $value = self::get_post_or_meta_value($entry, $field, array('truncate' => true));
            $value = maybe_unserialize($value);
        }

        switch($field->type){
            case 'user_id':
                $value = FrmProFieldsHelper::get_display_name($value);
                break;
            case 'data':
                if (is_array($value)){
                    $new_value = array();
                    foreach($value as $val)
                        $new_value[] = FrmProFieldsHelper::get_data_value($val, $field);
                    $value = $new_value;
                }else{
                    $value = FrmProFieldsHelper::get_data_value($value, $field);
                }
                break;
            case 'file':
                $value = FrmProFieldsHelper::get_file_name($value);
                break;
            case 'date':
                $value = FrmProFieldsHelper::get_date($value);
        }

        if (is_array($value)){
            $new_value = '';
            foreach($value as $val){
                if (is_array($val))
                    $new_value .= implode(', ', $val) . "\n";
            }
            if ($new_value != '')
                $value = $new_value;
        }

        return $value;
    }

    public static function display_value($value, $field, $atts=array()){
        _deprecated_function( __FUNCTION__, '2.0', 'FrmEntriesHelper::display_value');
        return FrmEntriesHelper::display_value($value, $field, $atts);
    }

    public static function get_sub_meta_values($entries, $field, $atts = array()) {
        $values = array();
        foreach ( $entries as $entry ) {
            $sub_val = self::get_post_or_meta_value($entry, $field, $atts);
            if ( $sub_val != '' ) {
                $values[] = $sub_val;
            }
        }
        return $values;
    }

    public static function get_post_or_meta_value($entry, $field, $atts=array()){
        $defaults = array(
            'links' => true, 'show' => '',
            'truncate' => true, 'sep' => ', ',
        );
        $atts = wp_parse_args( (array) $atts, $defaults);

        FrmEntriesHelper::maybe_get_entry( $entry );

        if ( empty($entry) || empty($field) ) {
            return '';
        }

        if ( $entry->post_id ) {
            if ( ! isset($field->field_options['custom_field']) ) {
                $field->field_options['custom_field'] = '';
            }

            if ( ! isset($field->field_options['post_field']) ) {
                $field->field_options['post_field'] = '';
            }

            $links = $atts['links'];

            if ( $field->type == 'tag' || $field->field_options['post_field'] ) {
                $post_args = array(
                    'type' => $field->type, 'form_id' => $field->form_id,
                    'field' => $field, 'links' => $links,
                    'exclude_cat' => $field->field_options['exclude_cat'],
                );

                foreach ( array('show', 'truncate', 'sep') as $p ) {
                    $post_args[$p] = $atts[$p];
                    unset($p);
                }

                $value = self::get_post_value($entry->post_id, $field->field_options['post_field'], $field->field_options['custom_field'], $post_args);
                unset($post_args);
            } else {
                $value = FrmEntryMeta::get_entry_meta_by_field($entry->id, $field->id);
            }
        } else {
            $value = FrmEntryMeta::get_entry_meta_by_field($entry->id, $field->id);

            if ( ( 'tag' == $field->type || (isset($field->field_options['post_field']) && $field->field_options['post_field'] == 'post_category') ) && !empty($value) ) {
                $value = maybe_unserialize($value);

                $new_value = array();
                foreach ( (array) $value as $tax_id ) {
                    if ( is_numeric($tax_id) ) {
                        $cat = get_term( $tax_id, $field->field_options['taxonomy'] );
                        $new_value[] = ($cat) ? $cat->name : $tax_id;
                        unset($cat);
                    } else {
                        $new_value[] = $tax_id;
                    }
                }

                $value = $new_value;
            }
        }

        return $value;
    }

    public static function get_post_value($post_id, $post_field, $custom_field, $atts){
        if ( ! $post_id ) {
            return '';
        }
        $post = get_post($post_id);
        if ( ! $post ) {
            return '';
        }

        $defaults = array(
            'sep' => ', ', 'truncate' => true, 'form_id' => false,
            'field' => array(), 'links' => false, 'show' => ''
        );

		$atts = wp_parse_args( $atts, $defaults );

        $value = '';
        if ($atts['type'] == 'tag'){
            if(isset($atts['field']->field_options)){
                $field_options = maybe_unserialize($atts['field']->field_options);
                $tax = isset($field_options['taxonomy']) ? $field_options['taxonomy'] : 'frm_tag';


                if($tags = get_the_terms($post_id, $tax)){
                    $names = array();
                    foreach($tags as $tag){
                        self::get_term_with_link( $tag, $tax, $names, $atts );
                    }
                    $value = implode($atts['sep'], $names);
                }
            }
        }else{
            if($post_field == 'post_custom'){ //get custom post field value
                $value = get_post_meta($post_id, $custom_field, true);
            }else if($post_field == 'post_category'){
                if($atts['form_id']){
                    $post_type = FrmProFormsHelper::post_type($atts['form_id']);
                    $taxonomy = FrmProAppHelper::get_custom_taxonomy($post_type, $atts['field']);
                }else{
                    $taxonomy = 'category';
                }

                $categories = get_the_terms( $post_id, $taxonomy );

                $names = array();
                $cat_ids = array();
                if($categories){
                    foreach($categories as $cat){
                        if ( isset($atts['exclude_cat']) && in_array($cat->term_id, (array) $atts['exclude_cat']) ) {
                            continue;
                        }

                        self::get_term_with_link( $cat, $taxonomy, $names, $atts );

                        $cat_ids[] = $cat->term_id;
                    }
                }

                if($atts['show'] == 'id')
                    $value = implode($atts['sep'], $cat_ids);
                else if($atts['truncate'])
                    $value = implode($atts['sep'], $names);
                else
                    $value = $cat_ids;
            }else{
                $post = (array) $post;
                $value = $post[$post_field];
            }
        }
        return $value;
    }

    private static function get_term_with_link( $tag, $tax, &$names, $atts ) {
        $tag_name = $tag->name;
        if ( $atts['links'] ) {
            $tag_name = '<a href="' . esc_attr( get_term_link($tag, $tax) ) . '" title="' . esc_attr( sprintf( __( 'View all posts filed under %s', 'formidable' ), $tag_name ) ) . '">'. $tag_name . '</a>';
        }
        $names[] = $tag_name;
    }

    public static function set_post_fields($field, $value, $errors) {
        // save file ids for later use
        if ( 'file' == $field->type ) {
            global $frm_vars;
            if ( ! isset($frm_vars['media_id']) ) {
                $frm_vars['media_id'] = array();
            }

            $frm_vars['media_id'][$field->id] = $value;
        }

        if ( empty($value) || ! isset($field->field_options['unique']) || ! $field->field_options['unique'] ) {
            return $errors;
        }

        $post_form_action = FrmFormActionsHelper::get_action_for_form($field->form_id, 'wppost', 1);
        if ( ! $post_form_action ) {
            return $errors;
        }

        // check if this is a regular post field
        $post_field = array_search($field->id, $post_form_action->post_content);
        $custom_field = '';

        if ( ! $post_field ) {
            // check if this is a custom field
            foreach ( $post_form_action->post_content['post_custom_fields'] as $custom_field ) {
                if ( isset($custom_field['field_id']) && ! empty($custom_field['field_id']) && isset($custom_field['meta_name']) && ! empty($custom_field['meta_name']) && $field->id == $custom_field['field_id'] ) {
                    $post_field = 'post_custom';
                    $custom_field = $custom_field['meta_name'];
                }
            }

            if ( ! $post_field ) {
                return $errors;
            }
        }

        // check for unique values in post fields
        $entry_id = ($_POST && isset($_POST['id'])) ? $_POST['id'] : false;
        $post_id = false;
        if ( $entry_id ) {
            global $wpdb;
            $post_id = $wpdb->get_var( $wpdb->prepare('SELECT post_id FROM '. $wpdb->prefix .'frm_items WHERE id = %d', $entry_id) );
        }

        if ( self::post_value_exists($post_field, $value, $post_id, $custom_field) ) {
            $errors['field'. $field->id] = FrmFieldsHelper::get_error_msg($field, 'unique_msg');
        }

        return $errors;
    }

    public static function meta_through_join($hide_field, $selected_field, $observed_field_val, $this_field = false, &$metas) {
        if ( is_array($observed_field_val) ) {
            $observed_field_val = array_filter($observed_field_val);
        }

        if ( empty($observed_field_val) || ( ! is_numeric($observed_field_val) && ! is_array($observed_field_val) ) ) {
            return;
        }

        $observed_info = FrmField::getOne($hide_field);

        if ( ! $selected_field ) {
            return;
        }

        $form_id = FrmProFieldsHelper::get_parent_form_id($selected_field);
        $join_fields = FrmField::get_all_types_in_form($form_id, 'data');
        if ( empty($join_fields) ) {
            return;
        }

        foreach ( $join_fields as $jf ) {
            if ( isset($jf->field_options['form_select']) && isset($observed_info->field_options['form_select']) && $jf->field_options['form_select'] == $observed_info->field_options['form_select'] ) {
                $join_field = $jf->id;
            }
        }

        if ( ! isset($join_field) ) {
            return;
        }

        $observed_field_val = array_filter( (array) $observed_field_val);
        $query = '(it.meta_value in ('. implode(',', $observed_field_val) .')';
        foreach ( $observed_field_val as $obs_val ) {
            $query .= " or it.meta_value LIKE '%s:". strlen($obs_val). ":\"". $obs_val ."\"%'";
        }

        $query .= ') and field_id ='. (int) $join_field;

        $user_id = '';
        if ( $this_field && isset($this_field->field_options['restrict']) && $this_field->field_options['restrict'] ) {
            $user_id = get_current_user_id();
        }
        // the ids of all the entries that have been selected in the linked form
        $entry_ids = FrmEntryMeta::getEntryIds($query, '', '', true, array('user_id' => $user_id));

        if ( ! empty($entry_ids) ) {
            if ( $form_id != $selected_field->form_id ) {
                // this is a child field so we need to get the child entries
                global $wpdb;
                $entry_ids = $wpdb->get_col('SELECT id FROM '. $wpdb->prefix .'frm_items WHERE parent_item_id in ('. implode(',', $entry_ids).')');
            }

            $metas = FrmEntryMeta::getAll('item_id in ('. implode(',', $entry_ids).') and field_id='. $selected_field->id, ' ORDER BY meta_value');
        }
    }

    public static function &value_exists($field_id, $value, $entry_id = false) {
        global $wpdb;
        if ( is_object($field_id) ) {
            $field_id = $field_id->id;
        }
        // Makes sure this works when $value is an array
        $value = maybe_serialize( $value );

        $query = $wpdb->prepare('SELECT id FROM '. $wpdb->prefix .'frm_item_metas WHERE meta_value=%s AND field_id=%d', $value, $field_id);
        if ( $entry_id ) {
            $query .= $wpdb->prepare(' AND item_id != %d', $entry_id);
        }

        $cache_key = 'value_exists_'. maybe_serialize($value) .'_field_'. $field_id .'_entry_'. $entry_id;
        $value = FrmAppHelper::check_cache($cache_key, 'frm_entry', $query, 'get_var');

        return $value;
    }

    public static function post_value_exists($post_field, $value, $post_id, $custom_field = '') {
        global $wpdb;
        if ( $post_field == 'post_custom' ) {
            $query = $wpdb->prepare("SELECT post_id FROM $wpdb->postmeta pm LEFT JOIN $wpdb->posts p ON (p.ID=pm.post_id) WHERE meta_value=%s and meta_key=%s", $value, $custom_field);
            if($post_id and is_numeric($post_id))
                $query .= $wpdb->prepare(" and post_id != %d", $post_id);
        } else {
            $query = $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE $post_field=%s", $value);
            if ( $post_id && is_numeric($post_id) ) {
                $query .= $wpdb->prepare(" and ID != %d", $post_id);
            }
        }
        $query .= " and post_status in ('publish','draft','pending','future')";

        return $wpdb->get_var($query);
    }

    public static function &get_max($field) {
        global $wpdb;

        if ( ! is_object($field) ) {
            $field = FrmField::getOne($field);
        }

        if ( ! $field ) {
            return;
        }

        $max = $wpdb->get_var($wpdb->prepare("SELECT meta_value +0 as odr FROM {$wpdb->prefix}frm_item_metas WHERE field_id=%d ORDER BY odr DESC LIMIT 1", $field->id));

        if ( isset($field->field_options['post_field']) && $field->field_options['post_field'] == 'post_custom' ) {
            $post_max = $wpdb->get_var($wpdb->prepare("SELECT meta_value +0 as odr FROM $wpdb->postmeta WHERE meta_key= %s ORDER BY odr DESC LIMIT 1", $field->field_options['custom_field']));
            if ( $post_max && (float) $post_max > (float) $max ) {
                $max = $post_max;
            }
        }

        return $max;
    }

}
