<?php

class FrmProFormActionsController{

    public static function register_actions($actions) {
        $actions['wppost'] = 'FrmProPostAction';

        include_once(FrmAppHelper::plugin_path() . '/pro/classes/views/frmpro-form-actions/post_action.php');

        return $actions;
    }

    public static function email_action_control($settings) {
	    $settings['event'] = array('create', 'update', 'delete');
	    $settings['priority'] = 41;

	    return $settings;
	}

    public static function form_action_settings($form_action, $atts){
        global $wpdb;
        extract($atts);

        $show_logic = ( ! empty($form_action->post_content['conditions']) && count($form_action->post_content['conditions']) > 2 ) ? true : false;

        // Text for different actions
        if ( $form_action->post_excerpt == 'email' ) {
            $send =  __('Send', 'formidable');
            $stop = __('Stop', 'formidable');
            $this_action_if = __('this notification if', 'formidable');
        } if ( $form_action->post_excerpt == 'wppost' ) {
            $send =  __('Create', 'formidable');
            $stop = __('Don\'t create', 'formidable');
            $this_action_if = __('this post if', 'formidable');
        } else if ( $form_action->post_excerpt == 'register' ) {
            $send =  __('Register', 'formidable');
            $stop = __('Don\'t register', 'formidable');
            $this_action_if = __('user if', 'formidable');
        } else {
            $send =  __('Do', 'formidable');
            $stop = __('Don\'t do', 'formidable');
            $this_action_if = __('this action if', 'formidable');
        }

        $form_fields = $atts['values']['fields'];
        unset($atts['values']['fields']);
        include(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-form-actions/_form_action.php');
    }

    public static function _logic_row(){
	    $meta_name = FrmAppHelper::get_param('meta_name');
	    $form_id = FrmAppHelper::get_param('form_id');
	    $key = FrmAppHelper::get_param('email_id');
	    $type = FrmAppHelper::get_param('type');

        $form = FrmForm::getOne($form_id);

        $notification = (isset($form->options['notification']) && isset($form->options['notification'][$key])) ? $form->options['notification'][$key] : array();

        if ( ! isset($notification['conditions']) ) {
            $notification['conditions'] = array();
        }

        if ( isset($notification['conditions'][$meta_name]) ) {
            $condition = $notification['conditions'][$meta_name];
        } else {
            $condition = array('hide_field_cond' => '==', 'hide_field' => '');
        }

        if ( ! isset($condition['hide_field_cond']) ) {
            $condition['hide_field_cond'] = '==';
        }

        FrmProFormsController::include_logic_row(array(
            'form_id' => $form->id,
            'form' => $form,
            'meta_name' => $meta_name,
            'condition' => $condition,
            'key' => $key,
            'name'  => 'frm_' . $type .'_action['. $key .'][post_content][conditions]['. $meta_name .']',
        ));

        die();
	}

    public static function fill_action_options($action, $type) {
        if ( 'wppost' == $type ) {

            $default_values = array(
                'post_type'     => 'post',
                'post_category' => array(),
                'post_content'  => '',
                'post_excerpt'  => '',
                'post_title'    => '',
                'post_name'     => '',
                'post_date'     => '',
                'post_status'   => '',
                'post_custom_fields' => array(),
                'post_password' => '',
            );

            $action->post_content = array_merge($default_values, (array) $action->post_content);
        }

        return $action;
    }

    public static function trigger_update_actions($entry_id, $form_id) {
        FrmFormActionsController::trigger_actions('update', $form_id, $entry_id);
    }

    public static function trigger_delete_actions($entry_id, $entry = false) {
        if ( ! empty($entry) ) {
            $entry = FrmEntry::getOne($entry_id);
        }
        FrmFormActionsController::trigger_actions('delete', $entry->form_id, $entry);
    }

    public static function _postmeta_row(){
        global $wpdb;
        $custom_data = array('meta_name' => $_POST['meta_name'], 'field_id' => '');
        $action_key = $_POST['action_key'];
        $action_control = FrmFormActionsController::get_form_actions( 'wppost' );
        $action_control->_set($action_key);

        $values = array();

        if ( isset($_POST['form_id']) ) {
            $values['fields'] = FrmField::getAll($wpdb->prepare('fi.form_id=%d and fi.type not in', $_POST['form_id']) ." ('". implode("','", FrmFieldsHelper::no_save_fields()) ."')", 'field_order');
        }
        $echo = false;

        $limit = (int) apply_filters( 'postmeta_form_limit', 40 );
    	$cf_keys = $wpdb->get_col( "SELECT meta_key FROM $wpdb->postmeta GROUP BY meta_key ORDER BY meta_key LIMIT $limit" );
    	if ( ! is_array($cf_keys) ) {
            $cf_keys = array();
        }
        if ( ! in_array('_thumbnail_id', $cf_keys) ) {
            $cf_keys[] = '_thumbnail_id';
        }
        if ( ! empty($cf_keys) ) {
    		natcasesort($cf_keys);
        }

        include(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-form-actions/_custom_field_row.php');
        die();
    }

    public static function _posttax_row(){
        if ( isset($_POST['field_id']) ) {
            $field_vars = array(
                'meta_name'     => $_POST['meta_name'],
                'field_id'      => $_POST['field_id'],
                'show_exclude'  => (int) $_POST['show_exclude'],
                'exclude_cat'   => ( (int) $_POST['show_exclude'] ) ? '-1' : 0
            );
        } else {
            $field_vars = array('meta_name' => '', 'field_id' => '', 'show_exclude' => 0, 'exclude_cat' => 0);
        }

        $tax_meta = $_POST['tax_key'];
        $post_type = $_POST['post_type'];
        $action_key = $_POST['action_key'];
        $action_control = FrmFormActionsController::get_form_actions( 'wppost' );
        $action_control->_set($action_key);

        if ( $post_type ) {
            $taxonomies = get_object_taxonomies($post_type);
        }

        $values = array();

        if ( isset($_POST['form_id']) ) {
            $values['fields'] = FrmField::getAll("fi.form_id='". (int) $_POST['form_id'] ."' and fi.type in ('checkbox', 'radio', 'select', 'tag', 'data')", 'field_order');
            $values['id'] = $_POST['form_id'];
        }

        $echo = false;
        include(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-form-actions/_post_taxonomy_row.php');
        die();
    }
}
