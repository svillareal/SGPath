<?php
class FrmProDisplay{

    public static function duplicate( $id, $copy_keys=false, $blog_id=false ){
        global $wpdb;

        $values = self::getOne( $id, $blog_id, true );

        if(!$values or !is_numeric($values->frm_form_id))
            return false;

        $new_values = array();
        foreach(array('post_name', 'post_title', 'post_excerpt', 'post_content', 'post_status', 'post_type') as $k){
            $new_values[$k] = $values->{$k};
            unset($k);
        }

        $meta = array();
        foreach(array('form_id', 'entry_id', 'post_id', 'dyncontent', 'param', 'type', 'show_count', 'insert_loc') as $k){
            $meta[$k] = $values->{'frm_'. $k};
            unset($k);
        }

        $default = FrmProDisplaysHelper::get_default_opts();
        $meta['options'] = array();
        foreach($default as $k => $v){
            if(isset($meta[$k]))
                continue;

            $meta['options'][$k] = $values->{'frm_'. $k};
            unset($k, $v);
        }
        $meta['options']['copy'] = false;

        if ($blog_id){
            $old_form = FrmForm::getOne($values->frm_form_id, $blog_id);
            $new_form = FrmForm::getOne($old_form->form_key);
            $meta['form_id'] = $new_form->id;
        }else{
            $meta['form_id'] = $values->form_id;
        }

        $post_ID = wp_insert_post( $new_values );

        $new_values = array_merge((array) $new_values, $meta);

        self::update($post_ID, $new_values);

        return $post_ID;
    }

    public static function update( $id, $values ){
        $new_values = array();
        $new_values['frm_param'] = isset($values['param']) ? sanitize_title_with_dashes($values['param']) : '';

        $fields = array('dyncontent', 'insert_loc', 'type', 'show_count', 'form_id', 'entry_id', 'post_id');
        foreach ($fields as $field){
            if(isset($values[$field]))
                $new_values['frm_'. $field] = $values[$field];
        }

        if (isset($values['options'])){
            $new_values['frm_options'] = array();
            foreach ($values['options'] as $key => $value)
                $new_values['frm_options'][$key] = $value;
        }

        foreach($new_values as $key => $val){
            update_post_meta($id, $key, $val);
            unset($key, $val);
        }

        if(!isset($new_values['frm_form_id']) or empty($new_values['frm_form_id']))
            return;

        global $wpdb;

        //update 'frm_display_id' post metas for automatically used views
        $posts = $wpdb->get_col($wpdb->prepare('SELECT post_id FROM '. $wpdb->prefix .'frm_items WHERE post_id > %d and form_id=%d', 0, $new_values['frm_form_id']));
        $first_post = $posts ? reset($posts) : false;
        $qualified = self::get_auto_custom_display(array('form_id' => $new_values['frm_form_id'], 'post_id' => $first_post));

        if(!$qualified){
            //delete any post meta for this display if no qualified displays
            $wpdb->delete($wpdb->postmeta, array('meta_key' => 'frm_display_id', 'meta_value' => $id));
        }else if($qualified->ID == $id){
            //this display is qualified
            if($posts){
                foreach($posts as $p){
                    update_post_meta($p, 'frm_display_id', $id);
                    unset($p);
                }
            }else{
                $wpdb->delete($wpdb->postmeta, array('meta_key' => 'frm_display_id', 'meta_value' => $id));
            }
        }else{
            //this view is not qualified, so set any posts to the next qualified view
            $wpdb->query($wpdb->prepare('UPDATE '. $wpdb->postmeta .' SET meta_value=%d WHERE meta_key=%s AND meta_value=%d', $qualified->ID, 'frm_display_id', $id));
        }

        //update post meta of post selected for auto insertion
        if ( isset($new_values['frm_insert_loc']) && $new_values['frm_insert_loc'] != 'none' && isset($new_values['frm_post_id']) && (int) $new_values['frm_post_id'] ) {
            update_post_meta($new_values['frm_post_id'], 'frm_display_id', $id);
        }

    }

    public static function getOne( $id, $blog_id=false, $get_meta=false, $atts=array() ){
        global $wpdb;

        if ($blog_id and is_multisite())
            switch_to_blog($blog_id);

        if (!is_numeric($id)){
            $id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type = %s AND post_status != %s", $id, 'frm_display', 'trash' ) );

            if (is_multisite() and empty($id))
                return false;
        }

        $post = get_post($id);
        if(!$post or $post->post_type != 'frm_display' or $post->post_status == 'trash'){
            $args = array(
                'post_type' => 'frm_display',
                'meta_key' => 'frm_old_id',
                'meta_value' => $id,
                'numberposts' => 1,
                'post_status' => 'publish'
            );
            $posts = get_posts($args);

            if($posts)
                $post = reset($posts);
        }

        if($post and $post->post_status == 'trash')
            return false;

        if($post and $get_meta){
            $check_post = (isset($atts['check_post'])) ? $atts['check_post'] : false;
            $post = FrmProDisplaysHelper::setup_edit_vars($post, $check_post);
        }

        if ($blog_id and is_multisite())
            restore_current_blog();

        return $post;
    }

    public static function getAll( $where = array(), $order_by = 'post_date', $limit = 99 ) {
        if ( ! is_numeric($limit) ) {
            $limit = (int) $limit;
        }

        $query = array(
            'numberposts'   => $limit,
            'orber_by'      => $order_by,
            'post_type'     => 'frm_display'
        );
        $query = array_merge($where, $query);

        $results = get_posts($query);
        return $results;
    }

    /**
     * Check for a qualified view.
     * Qualified:   1. set to show calendar or dynamic
     *              2. published
     *              3. form has posts/entry is linked to a post
     */
    public static function get_auto_custom_display($args){
        $defaults = array('post_id' => false, 'form_id' => false, 'entry_id' => false);
        extract(wp_parse_args( $args, $defaults ));

        global $wpdb;

        if($form_id){
            $display_ids = $wpdb->get_col($wpdb->prepare('SELECT post_ID FROM '. $wpdb->postmeta .' WHERE meta_key=%s AND meta_value=%d', 'frm_form_id', $form_id));

            if(!$display_ids)
                return false;

            if(!$post_id and !$entry_id){
                //does form have posts?
                $entry_id = $wpdb->get_var($wpdb->prepare('SELECT post_id FROM '. $wpdb->prefix .'frm_items WHERE form_id=%d', $form_id));
            }
        }

        if($post_id and !$entry_id){
            //is post linked to an entry?
            $entry_id = $wpdb->get_var($wpdb->prepare('SELECT id FROM '. $wpdb->prefix .'frm_items WHERE post_id=%d', $post_id));

            //is post selected for auto-insertion?
            if(!$entry_id){
                $query = $wpdb->prepare('SELECT post_ID FROM '. $wpdb->postmeta .' WHERE meta_key=%s AND meta_value=%s', 'frm_post_id', $post_id);
                if(isset($display_ids))
                    $query .= " AND post_ID in (". implode(',', $display_ids) .")";
                $display_ids = $wpdb->get_col($query);

                if(!$display_ids)
                    return false;
            }
        }

        //this post does not have an auto display
        if(!$entry_id)
            return false;

        $query = "SELECT p.* FROM $wpdb->posts p LEFT JOIN $wpdb->postmeta pm ON (p.ID = pm.post_ID) WHERE pm.meta_key='frm_show_count' AND post_type='frm_display' AND pm.meta_value in ('dynamic','calendar','one') AND p.post_status='publish' ";

        if(isset($display_ids))
            $query .= "AND p.ID in (". implode(',', $display_ids) .") ";

        $query .= "ORDER BY p.ID ASC LIMIT 1";

        $display = $wpdb->get_row($query);

        return $display;
    }

    public static function get_form_custom_display($form_id){
        global $wpdb;

        $display_ids = $wpdb->get_col($wpdb->prepare('SELECT post_ID FROM '. $wpdb->postmeta .' WHERE meta_key=%s AND meta_value=%d', 'frm_form_id', $form_id));

        if(!$display_ids)
            return false;

        $query = "SELECT p.* FROM $wpdb->posts p LEFT JOIN $wpdb->postmeta pm ON (p.ID = pm.post_ID) WHERE pm.meta_key='frm_show_count' AND post_type='frm_display' AND pm.meta_value in ('dynamic','calendar','one') AND p.post_status='publish' AND p.ID in (". implode(',', $display_ids) .") ORDER BY p.ID ASC LIMIT 1";

        $display = $wpdb->get_row($query);

        return $display;
    }

    public static function validate( $values ){
        $errors = array();

        if( $values['post_title'] == '' )
            $errors[] = __('Name cannot be blank', 'formidable');

        if( $values['excerpt'] == __('This is not displayed anywhere, but is just for your reference. (optional)', 'formidable' ))
            $_POST['excerpt'] = '';

        if( $values['content'] == '' )
            $errors[] = __('Content cannot be blank', 'formidable');

        if ($values['insert_loc'] != 'none' && $values['post_id'] == '')
            $errors[] = __('Page cannot be blank if you want the content inserted automatically', 'formidable');

        if (!empty($values['options']['limit']) && !is_numeric($values['options']['limit']))
            $errors[] = __('Limit must be a number', 'formidable');

        if ($values['show_count'] == 'dynamic'){
            if ($values['dyncontent'] == '')
                $errors[] = __('Dynamic Content cannot be blank', 'formidable');

            if( !FrmProAppHelper::rewriting_on() ){
                if ($values['param'] == '')
                     $errors[] = __('Parameter Name cannot be blank if content is dynamic', 'formidable');

                 if ($values['type'] == '')
                     $errors[] = __('Parameter Value cannot be blank if content is dynamic', 'formidable');
            }else{
                if ($values['type'] == '')
                     $errors[] = __('Detail Link cannot be blank if content is dynamic', 'formidable');
            }
        }

        if(isset($values['options']['where'])){
            $_POST['options']['where'] = FrmProAppHelper::reset_keys($values['options']['where']);
            $_POST['options']['where_is'] = FrmProAppHelper::reset_keys($values['options']['where_is']);
            $_POST['options']['where_val'] = FrmProAppHelper::reset_keys($values['options']['where_val']);
        }

        return $errors;
    }

}
