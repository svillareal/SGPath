<?php

class FrmProDisplaysHelper{

    public static function setup_new_vars(){
        $values = array();
        $defaults = self::get_default_opts();
		foreach ( $defaults as $var => $default ) {
			$values[ $var ] = FrmAppHelper::get_param( $var, $default );
		}

        return $values;
    }

    public static function setup_edit_vars( $post, $check_post = true ) {
        if ( ! $post ) {
            return false;
        }

        $values = (object) $post;
        $defaults = self::get_default_opts();

        foreach ( array( 'form_id', 'entry_id', 'post_id', 'dyncontent', 'param', 'type', 'show_count', 'insert_loc') as $var ) {
            $values->{'frm_'. $var} = get_post_meta($post->ID, 'frm_'. $var, true);
            if ( $check_post ) {
                $values->{'frm_'. $var} = FrmAppHelper::get_param($var, $values->{'frm_'. $var});
            }
        }

        $options = get_post_meta($post->ID, 'frm_options', true);
		foreach ( $defaults as $var => $default ) {
            if ( ! isset( $values->{'frm_'. $var} ) ) {
				$values->{'frm_'. $var} = isset($options[$var]) ? $options[$var] : $default;
                if ( $check_post ) {
                    $values->{'frm_'. $var} = FrmAppHelper::get_post_param('options['. $var .']', $values->{'frm_'. $var});
                }
            } else if ( $var == 'param' && empty($values->{'frm_'. $var}) ) {
                $values->{'frm_'. $var} = $default;
            }
        }

	    $values->frm_form_id = (int) $values->frm_form_id;
		$values->frm_order_by = empty($values->frm_order_by) ? array() : (array) $values->frm_order_by;
		$values->frm_order = empty($values->frm_order) ? array() : (array) $values->frm_order;

        return $values;
    }

    public static function get_default_opts(){

        return array(
            'name' => '', 'description' => '', 'display_key' => '',
            'form_id' => 0, 'date_field_id' => '', 'edate_field_id' => '',
			'repeat_event_field_id' => '', 'repeat_edate_field_id' => '', 'entry_id' => '',
            'post_id' => '', 'before_content' => '', 'content' => '',
            'after_content' => '', 'dyncontent' => '', 'param' => 'entry',
            'type' => '', 'show_count' => 'all', 'insert_loc' => 'none',
            'insert_pos' => 1, 'no_rt' => 0,
            'order_by' => array(), 'order' => array(), 'limit' => '', 'page_size' => '',
            'empty_msg' => __( 'No Entries Found', 'formidable' ), 'copy' => 0,
			'where' => array(), 'where_is' => array(), 'where_val' => array(),
			'group_by' => '',
        );
    }

    public static function is_edit_view_page() {
        global $pagenow;
        return is_admin() && $pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] == FrmProDisplaysController::$post_type;
    }

    public static function prepare_duplicate_view( &$post ) {
        $post = self::get_current_view($post);
        $post = self::setup_edit_vars($post);
    }

    /**
    * Check if a View has been duplicated. If it has, get the View object to be duplicated. If it has not been duplicated, just get the new post object.
    *
    * @param object $post
    * @return the View to be copied or the View that is being created (if it is not being duplicated)
    */
    public static function get_current_view( $post ) {
        if ( $post->post_type == FrmProDisplaysController::$post_type && isset($_GET['copy_id']) ) {
            global $copy_display;
            return $copy_display;
        }
        return $post;
    }

    public static function get_shortcodes($content, $form_id) {
        if ( empty($form_id) ) {
            return false;
        }

        $tagregexp = array(
            'deletelink', 'detaillink',
            'evenodd', 'get', 'entry_count', 'event_date',
        );

        $form_id = (int) $form_id;
        $form_ids = array( $form_id );

        //get linked form ids
        $fields = FrmProFormsHelper::has_repeat_field($form_id, false);
        foreach ( $fields as $field ) {
            if ( isset($field->field_options['form_select']) && $field->field_options['form_select'] ) {
                $form_ids[] = $field->field_options['form_select'];
                $tagregexp[] = $field->id;
                $tagregexp[] = $field->field_key;
            }
            unset($field);
        }

        foreach ( $form_ids as $form_id ) {
            $fields = FrmField::get_all_for_form($form_id, '', 'include');
            foreach ( $fields as $field ) {
                $tagregexp[] = $field->id;
                $tagregexp[] = $field->field_key;
            }
        }

        $tagregexp = implode('|', $tagregexp) .'|';
        $tagregexp .= FrmFieldsHelper::allowed_shortcodes();
        preg_match_all("/\[(if |foreach )?($tagregexp)\b(.*?)(?:(\/))?\](?:(.+?)\[\/\2\])?/s", $content, $matches, PREG_PATTERN_ORDER);

        // run conditional and foreach first
        $new_order = $matches[0];
        $move_up = array();
        foreach ( $new_order as $short_key => $tag ) {
            $conditional = preg_match('/^\[if/s', $matches[0][$short_key]) ? true : false;
            $foreach = preg_match('/^\[foreach/s', $matches[0][$short_key]) ? true : false;
            if ( $conditional || $foreach ) {
                $move_up[$short_key] = $tag;
            }
        }

        if ( ! empty($move_up) ) {
            $matches[0] = $move_up + $matches[0];
        }

        return $matches;
    }

}
