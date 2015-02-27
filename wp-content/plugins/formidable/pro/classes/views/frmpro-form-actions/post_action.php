<?php

class FrmProPostAction extends FrmFormAction {

	function __construct() {
		$action_ops = array(
		    'classes'   => 'ab-icon frm_dashicon_font dashicons-before',
            'limit'     => 1,
            'priority'  => 40,
            'event'     => array('create', 'update'),
            'force_event' => true,
		);

		$this->FrmFormAction('wppost', __('Create Post', 'formidable'), $action_ops);
	}

	function form( $form_action, $args = array() ) {
	    global $wpdb;

	    extract($args);

	    $post_types = FrmProAppHelper::get_custom_post_types();
        if ( ! $post_types ) {
            return;
        }

        $post_type = FrmProFormsHelper::post_type( $args['values']['id'] );
        $taxonomies = get_object_taxonomies($post_type);
        $action_control = $this;

        $echo = true;
        $form_id = $form->id;
        $display = false;
        $displays = array();

        $display_ids = $wpdb->get_col($wpdb->prepare("SELECT post_ID FROM $wpdb->postmeta WHERE meta_key=%s AND meta_value=%d", 'frm_form_id', $form_id));

        if ( $display_ids ) {
            $query = "SELECT p.ID, p.post_title FROM $wpdb->posts p LEFT JOIN $wpdb->postmeta pm ON (p.ID = pm.post_ID)
                    WHERE pm.meta_key='frm_show_count' AND post_type='frm_display'
                    AND pm.meta_value in ('dynamic','calendar','one') AND p.post_status in ('publish', 'private')
                    AND p.ID in (". implode(',', $display_ids) .") ORDER BY p.post_title ASC";
            $displays = $wpdb->get_results($query);

            if ( isset($form_action->post_content['display_id']) ) {
                // get view from settings
                if ( is_numeric($form_action->post_content['display_id']) ) {
                    $display = FrmProDisplay::getOne( $form_action->post_content['display_id'], false, true );
                }
            } else if ( ! is_numeric($form_action->post_content['post_content']) && ! empty($display_ids) ) {
                // get auto view
                $display = FrmProDisplay::get_form_custom_display($form_id);
                if ( $display ) {
                    $display = FrmProDisplaysHelper::setup_edit_vars($display, true);
                }
            }
        }

        // Get array of all custom fields
        $custom_fields = array();
        if ( isset( $form_action->post_content['post_custom_fields'] ) ) {
            foreach ( $form_action->post_content['post_custom_fields'] as $custom_field_opts ) {
                $custom_fields[] = $custom_field_opts['meta_name'];
                unset( $custom_field_opts );
            }
        }

        unset($display_ids);

	    include(dirname(__FILE__) .'/post_options.php');
	}

	function get_defaults() {
	    return array(
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
            'event'         => array('create', 'update'),
        );
	}

	function get_switch_fields() {
	    return array(
            'post_category' => array('field_id'),
            'post_custom_fields' => array('field_id'),
        );
	}
}
