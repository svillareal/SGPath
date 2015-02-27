<?php

class FrmRegAction extends FrmFormAction {

	function __construct() {
		$action_ops = array(
		    'classes'   => 'frm_register_icon frm_icon_font',
            'limit'     => 1,
            'active'    => true,
            'force_event' => true,
            'priority'  => 30,
		);
		
	    $this->FrmFormAction('register', __('Register User', 'formidable'), $action_ops);
	}

	function form( $form_action, $args = array() ) {
	    extract($args);
	    
	    global $wpdb;

	    $fields = FrmField::getAll($wpdb->prepare('fi.form_id=%d', $form->id) . " and fi.type not in ('end_divider', 'divider', 'html', 'break', 'captcha', 'rte')", ' ORDER BY field_order');
	    
	    include(FrmRegAppHelper::path() .'/views/_register_settings.php');
	}
	
	function get_defaults() {
	    return FrmRegAppHelper::get_default_options();
	}

	public function migrate_values($action, $form) {
	    if ( ! empty($action->post_content['reg_usermeta']) ) {
            $new_usermeta = array();
            foreach ( $action->post_content['reg_usermeta']  as $meta_name => $field_id ) {
                $new_usermeta[] = array( 'meta_name' => $meta_name, 'field_id' => $field_id );
                unset( $meta_name, $field_id );
            }
            $action->post_content['reg_usermeta'] = $new_usermeta;
        }

        $action->post_content['event'] = array('create', 'update');

	    return $action;
	}
}
