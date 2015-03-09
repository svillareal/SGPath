<?php

class FrmProEntriesController{

    public static function admin_js() {
        $frm_settings = FrmAppHelper::get_settings();

        add_filter('manage_'. sanitize_title($frm_settings->menu) .'_page_formidable-entries_columns', 'FrmProEntriesController::manage_columns', 25);

        if ( current_user_can('administrator') && ! current_user_can('frm_edit_entries') ) {
            global $wp_roles;
            $frm_roles = FrmAppHelper::frm_capabilities();
            foreach ( $frm_roles as $frm_role => $frm_role_description ) {
                if ( ! in_array($frm_role, array(
                        'frm_view_entries', 'frm_delete_entries', 'frm_view_forms', 'frm_edit_forms',
                        'frm_delete_forms', 'frm_change_settings',
                    )) ) {
                    $wp_roles->add_cap( 'administrator', $frm_role );
                }
            }
        }

        if ( ! $_GET || ! isset($_GET['page']) || $_GET['page'] != 'formidable-entries' ) {
            return;
        }

        wp_enqueue_script('jquery-ui-datepicker');

        if ( $frm_settings->accordion_js ) {
            wp_enqueue_script('jquery-ui-widget');
            wp_enqueue_script('jquery-ui-accordion');
        }

        $theme_css = FrmStylesController::get_style_val('theme_css');
        if ( $theme_css == -1 ) {
            return;
        }

        wp_enqueue_style($theme_css, FrmStylesHelper::jquery_css_url($theme_css));
    }

    public static function remove_fullscreen($init){
        if(isset($init['plugins'])){
            $init['plugins'] = str_replace('wpfullscreen,', '', $init['plugins']);
            $init['plugins'] = str_replace('fullscreen,', '', $init['plugins']);
        }
        return $init;
    }

    public static function register_scripts(){
        //if ( FrmAppHelper::is_admin() ) {
        //    return;
        //}

        global $wp_scripts;
        wp_register_script('jquery-frm-rating', FrmAppHelper::plugin_url() . '/pro/js/jquery.rating.min.js', array('jquery'), '4.11', true);
        wp_register_script('jquery-maskedinput', FrmAppHelper::plugin_url() . '/pro/js/jquery.maskedinput.min.js', array('jquery'), '1.3', true);

        wp_register_script('jquery-chosen', FrmAppHelper::plugin_url() .'/pro/js/chosen.jquery.min.js', array('jquery'), '1.2.0', true);
    }

    public static function add_js(){
        if ( FrmAppHelper::is_admin() ) {
            return;
        }

        $frm_settings = FrmAppHelper::get_settings();

        global $frm_vars;
        if ( $frm_settings->jquery_css ) {
            $frm_vars['datepicker_loaded'] = true;
        }

        if($frm_settings->accordion_js){
            wp_enqueue_script('jquery-ui-widget');
            wp_enqueue_script('jquery-ui-accordion');
        }
    }

    /*
    * Check if the form is loaded after the wp_footer hook.
    * If it is, we'll need to make sure the scripts are loaded.
    */
    public static function after_footer_loaded() {
        global $frm_vars;

        if ( ! isset($frm_vars['footer_loaded']) || ! $frm_vars['footer_loaded'] ) {
            return;
        }

        self::enqueue_footer_js();

    	print_late_styles();
    	print_footer_scripts();

        self::footer_js();
    }

    public static function enqueue_footer_js(){
        global $frm_vars, $frm_input_masks;

        if ( empty($frm_vars['forms_loaded']) ) {
            return;
        }

        self::register_scripts();

        if ( ! FrmAppHelper::doing_ajax() ) {
            wp_enqueue_script('formidable');
        }

        if ( isset($frm_vars['tinymce_loaded']) && $frm_vars['tinymce_loaded'] ) {
            _WP_Editors::enqueue_scripts();
        }

        if ( isset($frm_vars['datepicker_loaded']) && ! empty($frm_vars['datepicker_loaded']) ) {
            if ( is_array($frm_vars['datepicker_loaded']) ) {
                foreach ( $frm_vars['datepicker_loaded'] as $fid => $o ) {
                    if ( ! $o ) {
                        unset($frm_vars['datepicker_loaded'][$fid]);
                    }
                    unset($fid, $o);
                }
            }

            if ( ! empty($frm_vars['datepicker_loaded']) ) {
                wp_enqueue_script('jquery-ui-datepicker');
                FrmStylesHelper::enqueue_jquery_css();
            }
        }

        if ( isset($frm_vars['chosen_loaded']) && $frm_vars['chosen_loaded'] ) {
            wp_enqueue_script('jquery-chosen');
        }

        if ( isset($frm_vars['star_loaded']) && ! empty($frm_vars['star_loaded']) ) {
            wp_enqueue_script('jquery-frm-rating');
            wp_enqueue_style( 'dashicons' );

            FrmStylesController::enqueue_style();
        }

        $frm_input_masks = apply_filters('frm_input_masks', $frm_input_masks, $frm_vars['forms_loaded']);
        foreach ( (array) $frm_input_masks as $fid => $o ) {
            if ( ! $o ) {
                unset($frm_input_masks[$fid]);
            }
            unset($fid, $o);
        }

        if ( ! empty($frm_input_masks) ) {
            wp_enqueue_script('jquery-maskedinput');
        }
    }

    public static function footer_js(){
        global $frm_vars, $frm_input_masks;

        $frm_vars['footer_loaded'] = true;

        if ( empty($frm_vars['forms_loaded']) ) {
            return;
        }

        $trigger_form = ( ! FrmAppHelper::doing_ajax() && ! FrmAppHelper::is_admin_page('formidable-entries') );

        include(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-entries/footer_js.php');
    }

    public static function data_sort($options){
        natcasesort($options); //TODO: add sorting options
        return $options;
    }

    public static function register_widgets() {
        include_once(FrmAppHelper::plugin_path() .'/pro/classes/widgets/FrmListEntries.php');
        register_widget('FrmListEntries');
    }

    /* Back End CRUD */
    public static function show_comments($entry) {
        $id = $entry->id;
        $user_ID = get_current_user_id();

        if ( $_POST && isset($_POST['frm_comment']) && ! empty($_POST['frm_comment']) ) {
            FrmEntryMeta::add_entry_meta($_POST['item_id'], 0, '', array(
                'comment' => $_POST['frm_comment'], 'user_id' => $user_ID,
            ));
            //send email notifications
        }

        $comments = FrmEntryMeta::getAll("item_id=$id and field_id=0", ' ORDER BY it.created_at ASC', '', true);
        $to_emails = apply_filters('frm_to_email', array(), $entry, $entry->form_id);
        include(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-entries/show.php');
    }

    public static function add_duplicate_link($entry) {
        FrmProEntriesHelper::show_duplicate_link($entry);
    }

    public static function add_sidebar_links($entry) {
        include(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-entries/_sidebar-shared-pub.php');
    }

    public static function add_edit_link() {
        FrmProEntriesHelper::edit_button();
    }

    public static function add_new_entry_link($form) {
        FrmProEntriesHelper::show_new_entry_button($form);
    }

    public static function new_entry(){
        if ( $form_id = FrmAppHelper::get_param('form') ) {
            $form = FrmForm::getOne($form_id);
            self::get_new_vars('', $form);
        } else {
             include(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-entries/new-selection.php');
        }
    }

    public static function create(){
        if ( ! current_user_can('frm_create_entries') ) {
            return FrmEntriesController::display_list();
        }

        $params = FrmEntriesHelper::get_admin_params();
        $form = $record = false;
        if ( $params['form'] ) {
            $form = FrmForm::getOne($params['form']);
        }

        if ( ! $form ) {
            return;
        }

        $errors = FrmEntry::validate($_POST);

        if ( count($errors) > 0 ) {
            self::get_new_vars($errors, $form);
            return;
        }

        if ( ( isset($_POST['frm_page_order_'. $form->id]) || FrmProFormsHelper::going_to_prev($form->id) ) && ! FrmProFormsHelper::saving_draft() ) {
            self::get_new_vars('', $form);
            return;
        }

        $_SERVER['REQUEST_URI'] = str_replace('&frm_action=new', '', $_SERVER['REQUEST_URI']);

        global $frm_vars;
        if ( ! isset($frm_vars['created_entries'][ $form->id ]) || ! $frm_vars['created_entries'][ $form->id ] ) {
            $frm_vars['created_entries'][$form->id] = array();
        }

        if ( ! isset($frm_vars['created_entries'][ $_POST['form_id'] ]['entry_id']) ) {
            $record = $frm_vars['created_entries'][$form->id]['entry_id'] = FrmEntry::create( $_POST );
        }

        if ( $record ) {
            if ( FrmProFormsHelper::saving_draft() ) {
                $message = __('Draft was Successfully Created', 'formidable');
            } else {
                $message = __('Entry was Successfully Created', 'formidable');
            }

            self::get_edit_vars($record, $errors, $message);
        } else {
            self::get_new_vars($errors, $form);
        }
    }

    public static function edit(){
        $id = FrmAppHelper::get_param('id');

        if ( ! current_user_can('frm_edit_entries') ) {
            return FrmEntriesController::show($id);
        }

        return self::get_edit_vars($id);
    }

    public static function update(){
        $id = FrmAppHelper::get_param('id');

        if ( ! current_user_can('frm_edit_entries') ) {
            return FrmEntriesController::show($id);
        }

        $message = '';
        $errors = FrmEntry::validate($_POST);

        if( empty($errors) ){
            if ( isset($_POST['form_id']) && ( isset($_POST['frm_page_order_'. $_POST['form_id']]) || FrmProFormsHelper::going_to_prev($_POST['form_id']) ) && ! FrmProFormsHelper::saving_draft() ) {
                return self::get_edit_vars($id);
            }else{
                FrmEntry::update( $id, $_POST );
                if ( isset($_POST['form_id']) && FrmProFormsHelper::saving_draft() ) {
                    $message = __('Draft was Successfully Updated', 'formidable');
                } else {
                    $message = __('Entry was Successfully Updated', 'formidable');
                }

                $message .= '<br/> <a href="?page=formidable-entries&form='. $_POST['form_id'] .'">&larr; '. __('Back to Entries', 'formidable') .'</a>';
            }
        }

        return self::get_edit_vars($id, $errors, $message);
    }

    public static function duplicate(){
        $params = FrmEntriesHelper::get_admin_params();

        if ( ! current_user_can('frm_create_entries') ) {
            return FrmEntriesController::show($params['id']);
        }

        $message = $errors = '';

        $record = FrmEntry::duplicate( $params['id'] );
        if ($record)
            $message = __('Entry was Successfully Duplicated', 'formidable');
        else
            $errors = __('There was a problem duplicating that entry', 'formidable');

        if(!empty($errors))
            return FrmEntriesController::display_list($params, $errors);
        else
            return self::get_edit_vars($record, '', $message);
    }

    public static function bulk_actions($action='list-form'){
        $params = FrmEntriesHelper::get_admin_params();
        $errors = array();
        $bulkaction = '-1';

        if($action == 'list-form'){
            if($_REQUEST['bulkaction'] != '-1')
                $bulkaction = $_REQUEST['bulkaction'];
            else if($_POST['bulkaction2'] != '-1')
                $bulkaction = $_REQUEST['bulkaction2'];
        }else{
            $bulkaction = str_replace('bulk_', '', $action);
        }

        $items = FrmAppHelper::get_param('item-action', '');
        if (empty($items)){
            $errors[] = __('No entries were specified', 'formidable');
        }else{
            $frm_settings = FrmAppHelper::get_settings();

            if ( ! is_array($items) ) {
                $items = explode(',', $items);
            }

            if($bulkaction == 'delete'){
                if(!current_user_can('frm_delete_entries')){
                    $errors[] = $frm_settings->admin_permission;
                }else{
                    if ( is_array($items) ) {
                        foreach ( $items as $item_id ) {
                            FrmEntry::destroy($item_id);
                        }
                    }
                }
            }else if($bulkaction == 'export'){
                $controller = 'items';
                $ids = $items;
                $ids = implode(',', $ids);
                include(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-entries/xml.php');
            }else if($bulkaction == 'csv'){
                FrmAppHelper::permission_check('frm_view_entries');

                $form_id = $params['form'];
                if ( ! $form_id ) {
                    $form = FrmForm::getAll("is_template=0 AND (status is NULL OR status = '' OR status = 'published')", 'name', 1);
                    if ( ! empty($form) ) {
                        $form_id = $form->id;
                    } else {
                        $errors[] = __('No form was found', 'formidable');
                    }
                }

                if ( $form_id && is_array($items) ) {
                    echo '<script type="text/javascript">window.onload=function(){location.href="'. admin_url( 'admin-ajax.php' ) .'?form='. $form_id .'&action=frm_entries_csv&item_id='. implode(',', $items) .'";}</script>';
                }
            }
        }
        FrmEntriesController::display_list($params, '', $errors);
    }

    /* Front End CRUD */

    //Determine if this is a new entry or if we're editing an old one
    public static function maybe_editing($continue, $form_id, $action = 'new') {
        $form_submitted = FrmAppHelper::get_param('form_id');
        if ( $action == 'new' || $action == 'preview' ) {
            $continue = true;
        } else {
            $continue = ( is_numeric($form_submitted) && (int) $form_id != (int) $form_submitted ) ? true : false;
        }

        return $continue;
    }

    public static function check_draft_status($values, $id) {
        if ( FrmProEntriesHelper::get_field('is_draft', $id) || $values['is_draft'] ) {
            //remove update hooks if submitting for the first time or is still draft

        }

        //if entry was not previously draft or continues to be draft
        if ( !FrmProEntriesHelper::get_field('is_draft', $id) || $values['is_draft'] ) {
            return $values;
        }

        //add the create hooks since the entry is switching draft status
        add_action('frm_after_update_entry', 'FrmProEntriesController::add_published_hooks', 2, 2);

        //change created timestamp
        $values['created_at'] = $values['updated_at'];

        return $values;
    }

    public static function remove_draft_hooks($entry_id) {
        if ( ! FrmProEntriesHelper::get_field('is_draft', $entry_id) ) {
            return;
        }

        // don't let sub entries remove these hooks
        $entry = FrmEntry::getOne($entry_id);
        if ( $entry->parent_item_id ) {
            return;
        }

        //remove hooks if saving as draft
        remove_action('frm_after_create_entry', 'FrmProEntriesController::set_cookie', 20);
        remove_action('frm_after_create_entry', 'FrmFormActionsController::trigger_create_actions', 20);
    }

    //add the create hooks since the entry is switching draft status
    public static function add_published_hooks($entry_id, $form_id) {
        do_action('frm_after_create_entry', $entry_id, $form_id);
        do_action('frm_after_create_entry_'. $form_id, $entry_id);
        remove_action('frm_after_update_entry', 'FrmProEntriesController::add_published_hooks', 2);
    }

    public static function process_update_entry($params, $errors, $form, $args){
        global $frm_vars;

        if ( $params['action'] == 'update' && isset($frm_vars['saved_entries']) && in_array( (int) $params['id'], (array) $frm_vars['saved_entries'] ) ) {
            return;
        }

        if ( $params['action'] == 'create' && isset($frm_vars['created_entries'][$form->id]) && isset($frm_vars['created_entries'][$form->id]['entry_id']) && is_numeric($frm_vars['created_entries'][$form->id]['entry_id']) ) {
            $entry_id = $params['id'] = $frm_vars['created_entries'][$form->id]['entry_id'];

            self::set_cookie($entry_id, $form->id);

            $conf_method = apply_filters('frm_success_filter', 'message', $form, $form->options, $params['action']);
            if ($conf_method != 'redirect')
                return;

            $success_args = array('action' => $params['action']);

            if(isset($args['ajax']))
                $success_args['ajax'] = $args['ajax'];
            do_action('frm_success_action', $conf_method, $form, $form->options, $params['id'], $success_args);
        }else if ($params['action'] == 'update'){
            if ( isset($frm_vars['saved_entries']) && in_array((int) $params['id'], (array) $frm_vars['saved_entries']) ) {
                if ( isset($_POST['item_meta']) ) {
                    unset($_POST['item_meta']);
                }

                add_filter('frm_continue_to_new', '__return_false', 15);
                return;
            }

            //don't update if there are validation errors
            if (!empty($errors))
                return;

            //check if user is allowed to update
            if ( !FrmProEntriesHelper::user_can_edit( (int) $params['id'], $form ) ) {
                $frm_settings = FrmAppHelper::get_settings();
                wp_die(do_shortcode($frm_settings->login_msg));
            }

            //update, but don't check for confirmation if saving draft
            if ( FrmProFormsHelper::saving_draft() ) {
                FrmEntry::update( $params['id'], $_POST );
                return;
            }

            //don't update if going back
            if ( isset($_POST['frm_page_order_'. $form->id]) || FrmProFormsHelper::going_to_prev($form->id) ) {
                return;
            }

            FrmEntry::update( $params['id'], $_POST );


            $success_args = array('action' => $params['action']);
            if ( $params['action'] != 'create' && FrmProEntriesHelper::is_new_entry($params['id']) ) {
                $success_args['action'] = 'create';
            }

            //check confirmation method
            $conf_method = apply_filters('frm_success_filter', 'message', $form, $success_args['action']);

            if ($conf_method != 'redirect')
                return;

            if(isset($args['ajax']))
                $success_args['ajax'] = $args['ajax'];

            do_action('frm_success_action', $conf_method, $form, $form->options, $params['id'], $success_args);

        }else if ($params['action'] == 'destroy'){
            //if the user who created the entry is deleting it
            self::ajax_destroy($form->id, false, false);
        }
    }

    public static function edit_update_form($params, $fields, $form, $title, $description){
        global $frm_vars;

        $continue = true;

        if ( 'edit' == $params['action'] ) {
            self::front_edit_entry($form, $fields, $title, $description, $continue);
        } else if ( 'update' == $params['action'] && $params['posted_form_id'] == $form->id ) {
            self::front_update_entry($form, $fields, $title, $description, $continue, $params);
        } else if ( 'destroy' == $params['action'] ) {
            self::front_destroy_entry($form);
        } else if ( isset($frm_vars['editing_entry']) && $frm_vars['editing_entry'] ) {
            self::front_auto_edit_entry($form, $fields, $title, $description, $continue);
        } else {
            self::allow_front_create_entry($form, $continue);
        }

        remove_filter('frm_continue_to_new', '__return_'. ( $continue ? 'false' : 'true' ), 15); // remove the opposite filter
        add_filter('frm_continue_to_new', '__return_'. ($continue ? 'true' : 'false'), 15);
    }

    /*
    * Load form for editing
    */
    private static function front_edit_entry( $form, $fields, $title, $description, &$continue ) {
        global $wpdb;

        $entry_key = FrmAppHelper::get_param('entry');

        $where = $wpdb->prepare('it.form_id=%d', $form->id);

        if ( $entry_key ) {
            $where .= $wpdb->prepare(' AND (it.id=%d OR it.item_key=%s)', $entry_key, $entry_key);
            $in_form = $wpdb->get_var('SELECT id FROM '. $wpdb->prefix .'frm_items it WHERE '. $where);
            if ( ! $in_form ) {
                $entry_key = false;
                $where = $wpdb->prepare('it.form_id=%d', $form->id);
            }
            unset($in_form);
        }

        $entry_key = esc_sql($entry_key);
        $entry = FrmProEntriesHelper::user_can_edit($entry_key, $form);
        unset($entry_key);

        if ( $entry && ! is_array($entry) ) {
            $entry = FrmEntry::getAll( $where, '', 1, true);
        }

        if ( $entry && !empty($entry) ) {
            global $frm_vars;
            $entry = reset($entry);
            $frm_vars['editing_entry'] = $entry->id;
            self::show_responses($entry, $fields, $form, $title, $description);
            $continue = false;
        }
    }

    /*
    * Automatically load the form for editing when a draft exists
    * or the form is limited to one per user
    */
    private static function front_auto_edit_entry( $form, $fields, $title, $description, &$continue ) {
        global $frm_vars, $wpdb;

        $user_ID = get_current_user_id();

        if ( is_numeric($frm_vars['editing_entry']) ) {
            $entry_id = $frm_vars['editing_entry']; //get entry from shortcode
        } else {
            $entry_ids = $wpdb->get_col($wpdb->prepare('SELECT id FROM '. $wpdb->prefix .'frm_items WHERE user_id=%d and form_id=%d', $user_ID, $form->id));

            if ( empty($entry_ids) ) {
                return;
            }

            $where_options = $frm_vars['editing_entry'];
            if ( ! empty($where_options) ) {
                $where_options .= ' and ';
            }
            $where_options .= 'it.item_id in ('. implode(',', $entry_ids) .')';

            $get_meta = FrmEntryMeta::getAll($where_options, ' ORDER BY it.created_at DESC', ' LIMIT 1');
            $entry_id = $get_meta ? $get_meta->item_id : false;
        }

        if ( ! $entry_id ) {
            return;
        }

        if ( $form->editable && ( ( isset($form->options['open_editable']) && $form->options['open_editable'] ) || ! isset($form->options['open_editable']) ) && isset($form->options['open_editable_role']) && FrmAppHelper::user_has_permission($form->options['open_editable_role']) ) {
            $meta = true;
        } else {
            $meta = $wpdb->get_var($wpdb->prepare('SELECT id FROM '. $wpdb->prefix .'frm_items WHERE user_id=%d AND id=%d AND form_id=%d', $user_ID, $entry_id, $form->id ));
        }

        if ( ! $meta ) {
            return;
        }

        $frm_vars['editing_entry'] = $entry_id;
        self::show_responses($entry_id, $fields, $form, $title, $description);
        $continue = false;
    }

    private static function front_destroy_entry( $form ) {
        //if the user who created the entry is deleting it
        self::ajax_destroy($form->id, false);
    }

    private static function front_update_entry($form, $fields, $title, $description, &$continue, $params ) {
        global $frm_vars;

        $message = '';
        $errors = isset($frm_vars['created_entries'][$form->id]) ? $frm_vars['created_entries'][$form->id]['errors'] : false;

        if ( empty($errors) ) {
            $saving_draft = FrmProFormsHelper::saving_draft();
            if ( ( ! isset($_POST['frm_page_order_'. $form->id]) && ! FrmProFormsHelper::going_to_prev($form->id) ) || $saving_draft ) {
                $success_args = array('action' => $params['action']);
                if ( FrmProEntriesHelper::is_new_entry($params['id']) ) {
                    $success_args['action'] = 'create';
                }

                //check confirmation method
                $conf_method = apply_filters('frm_success_filter', 'message', $form, $success_args['action']);

                if ( $conf_method == 'message' ) {
                    $message = self::confirmation($conf_method, $form, $form->options, $params['id'], $success_args);
                } else {
                    do_action('frm_success_action', $conf_method, $form, $form->options, $params['id'], $success_args);
                    add_filter('frm_continue_to_new', '__return_false', 15);
                    return;
                }
            }
        }else{
            $fields = FrmFieldsHelper::get_form_fields($form->id, true);
        }

        self::show_responses($params['id'], $fields, $form, $title, $description, $message, $errors);
        $continue = false;
    }

    /*
    * check to see if user is allowed to create another entry
    */
    private static function allow_front_create_entry($form, &$continue) {
        if ( ! isset($form->options['single_entry']) || ! $form->options['single_entry'] ) {
            return;
        }

        global $frm_vars, $wpdb;

        $can_submit = true;
        $user_ID = get_current_user_id();

        if ( $form->options['single_entry_type'] == 'cookie' && isset($_COOKIE['frm_form'. $form->id . '_' . COOKIEHASH]) ) {
            $can_submit = false;
        } else if ( $form->options['single_entry_type'] == 'ip' ) {
            // check if this IP has an entry
            $prev_entry = FrmEntry::getAll( array( 'it.form_id' => $form->id, 'it.ip' => FrmAppHelper::get_ip_address() ), '', 1 );
            if ( $prev_entry ) {
                $can_submit = false;
            }
        } else if ( $form->options['single_entry_type'] == 'user' && ! $form->editable && $user_ID ) {
            $meta = $wpdb->get_var( $wpdb->prepare('SELECT id FROM '. $wpdb->prefix .'frm_items WHERE user_id = %d AND form_id = %d', $user_ID, $form->id) );
            if ( $meta ) {
                $can_submit = false;
            }
        } else if ( isset($form->options['save_draft']) && $form->options['save_draft'] == 1 && $user_ID ) {
            // check if user has a saved draft
            $where = $wpdb->prepare('user_id=%d AND form_id=%d', $user_ID, $form->id);
            if ( $form->options['single_entry_type'] != 'user' ) {
                $where .= $wpdb->prepare(' AND is_draft=%d', 1);
            }
            $meta = $wpdb->get_var('SELECT id FROM '. $wpdb->prefix .'frm_items WHERE '. $where);
            if ( $meta ) {
                $can_submit = false;
            }
        }

        if ( ! $can_submit ) {
            $frmpro_settings = new FrmProSettings();
            echo $frmpro_settings->already_submitted;//TODO: DO SOMETHING IF USER CANNOT RESUBMIT FORM
            $continue = false;
        }
    }

    public static function show_responses($id, $fields, $form, $title=false, $description=false, $message='', $errors=array()){
        global $frm_vars;

        if(is_object($id)){
            $item = $id;
            $id = $item->id;
        }else{
            $item = FrmEntry::getOne($id, true);
        }

        $frm_vars['editing_entry'] = $item->id;
        $values = FrmAppHelper::setup_edit_vars($item, 'entries', $fields);

        if ( $values['custom_style'] ) {
            $frm_vars['load_css'] = true;
        }
        $show_form = true;

        if ( $item->is_draft ) {
            if ( isset($values['submit_value']) ) {
                $edit_create = $values['submit_value'];
            } else {
                $frmpro_settings = new FrmProSettings();
                $edit_create = $frmpro_settings->submit_value;
            }
        } else {
            if ( isset($values['edit_value']) ) {
                $edit_create = $values['edit_value'];
            } else {
                $frmpro_settings = new FrmProSettings();
                $edit_create = $frmpro_settings->update_value;
            }
        }

        $submit = (isset($frm_vars['next_page'][$form->id])) ? $frm_vars['next_page'][$form->id] : $edit_create;
        unset($edit_create);

        if(is_object($submit))
            $submit = $submit->name;

        if ( ! isset($frm_vars['prev_page'][$form->id]) && isset($_POST['item_meta']) && empty($errors) && $form->id == FrmAppHelper::get_param('form_id') ) {
            $show_form = (isset($form->options['show_form'])) ? $form->options['show_form'] : true;
            if ( FrmProFormsHelper::saving_draft() || FrmProFormsHelper::going_to_prev($form->id) ) {
                $show_form = true;
            }else{
                $show_form = apply_filters('frm_show_form_after_edit', $show_form, $form);
                $success_args = array('action' => 'update');
                if ( FrmProEntriesHelper::is_new_entry($id) ) {
                    $success_args['action'] = 'create';
                }

                $conf_method = apply_filters('frm_success_filter', 'message', $form, $success_args['action']);

                if ( $conf_method != 'message' ) {
                    do_action('frm_success_action', $conf_method, $form, $form->options, $id, $success_args);
                }
            }
        } else if ( isset($frm_vars['prev_page'][$form->id]) || ! empty($errors) ) {
            $jump_to_form = true;
        }

        $user_ID = get_current_user_id();

        if ( isset($form->options['show_form']) && $form->options['show_form'] ) {
            //Do nothing because JavaScript is already loaded
        } else {
            //Load JavaScript here
            $frm_vars['forms_loaded'][] = true;
        }

        $frm_settings = FrmAppHelper::get_settings();
        require(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-entries/edit-front.php');
        add_filter('frm_continue_to_new', 'FrmProEntriesController::maybe_editing', 10, 3);
    }

    public static function ajax_submit_button() {
        global $frm_vars;

        if ( isset($frm_vars['novalidate']) && $frm_vars['novalidate'] ) {
            echo ' formnovalidate="formnovalidate"';
        }
    }

    public static function get_confirmation_method($method, $form, $action = 'create') {
        $opt = ( $action == 'update' ) ? 'edit_action' : 'success_action';
        $method = ( isset($form->options[$opt]) && !empty($form->options[$opt]) ) ? $form->options[$opt] : $method;
        if ( $method != 'message' && FrmProFormsHelper::saving_draft() ) {
            $method = 'message';
        }
        return $method;
    }

    public static function confirmation($method, $form, $form_options, $entry_id, $args=array()){
        $opt = ( ! isset($args['action']) || $args['action'] == 'create' ) ? 'success' : 'edit';
        if ( $method == 'page' && is_numeric($form_options[$opt .'_page_id']) ) {
            global $post;
            if ( ! $post || $form_options[$opt .'_page_id'] != $post->ID ) {
                $page = get_post($form_options[$opt .'_page_id']);
                $old_post = $post;
                $post = $page;
                $content = apply_filters('frm_content', $page->post_content, $form, $entry_id);
                echo apply_filters('the_content', $content);
                $post = $old_post;
            }
        }else if($method == 'redirect'){
            global $frm_vars;

            add_filter('frm_use_wpautop', '__return_false');
            $success_url = apply_filters('frm_content', trim($form_options[$opt .'_url']), $form, $entry_id);
            $success_msg = isset($form_options[$opt .'_msg']) ? $form_options[$opt .'_msg'] : __('Please wait while you are redirected.', 'formidable');

            $redirect_msg = '<div class="'. FrmFormsHelper::get_form_style_class($form) .'"><div class="frm-redirect-msg frm_message">'. $success_msg .'<br/>'.
                sprintf(__('%1$sClick here%2$s if you are not automatically redirected.', 'formidable'), '<a href="'. esc_url($success_url) .'">', '</a>') .
                '</div></div>';

            $redirect_msg = apply_filters('frm_redirect_msg', $redirect_msg, array(
                'entry_id' => $entry_id, 'form_id' => $form->id, 'form' => $form
            ));

            $args['id'] = $entry_id;
            add_filter('frm_redirect_url', 'FrmProEntriesController::redirect_url');
            //delete the entry on frm_redirect_url hook
            $success_url = apply_filters('frm_redirect_url', $success_url, $form, $args);
            $doing_ajax = FrmAppHelper::doing_ajax();

            if ( isset($args['ajax']) && $args['ajax'] && $doing_ajax ) {
                echo json_encode(array('redirect' => $success_url));
                die();
            } else if ( ! $doing_ajax && ! headers_sent() ) {
                wp_redirect( $success_url );
                die();
            }

            add_filter('frm_use_wpautop', '__return_true');

            $response = $redirect_msg;

            $response .= "<script type='text/javascript'>jQuery(document).ready(function(){ setTimeout(window.location='". $success_url ."', 8000); });</script>";

            if(headers_sent()){
                echo $response;
            }else{
                wp_redirect( $success_url );
                die();
            }
        } else {
            $frm_settings = FrmAppHelper::get_settings();
            $frmpro_settings = FrmProAppHelper::get_settings();

            $msg = ( $opt == 'edit' ) ? $frmpro_settings->edit_msg : $frm_settings->success_msg;
            $message = isset($form->options[$opt .'_msg']) ? $form->options[$opt .'_msg'] : $msg;

            // Filter shortcodes in success message
            $message = apply_filters('frm_content', $message, $form, $entry_id);
            $message = wpautop( $message );

            // Replace $message with save draft message if we are saving a draft
            FrmProFormsHelper::save_draft_msg( $message, $form );

            $message = '<div class="frm_message" id="message">'. $message .'</div>';
            return $message;
        }
    }

    public static function delete_entry($post_id){
        global $wpdb;
        $frmdb = new FrmDb();
        $entry = $frmdb->get_one_record($wpdb->prefix .'frm_items', array('post_id' => $post_id), 'id');
        self::maybe_delete_entry($entry);
    }

    public static function trashed_post($post_id){
        $form_id = get_post_meta($post_id, 'frm_form_id', true);

        $display = FrmProDisplay::get_auto_custom_display(array('form_id' => $form_id));
        if($display)
            update_post_meta($post_id, 'frm_display_id', $display->ID);
        else
            delete_post_meta($post_id, 'frm_display_id');
    }

    public static function create_entry_from_post_box($post_type, $post=false){
        if ( ! $post || ! isset($post->ID) || $post_type == 'attachment' || $post_type == 'link' ) {
            return;
        }

        global $wpdb, $frm_vars;

        //don't show the meta box if there is already an entry for this post
        $post_entry = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE post_id=". $post->ID);
        if($post_entry)
            return;

        //don't show meta box if no forms are set up to create this post type
        $forms = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}frm_forms where options LIKE '%s:9:\"post_type\";s:". strlen($post_type) .":\"". $post_type ."\";%' AND options LIKE '%s:11:\"create_post\";s:1:\"1\";%'");
        if ( ! $forms ) {
            return;
        }

        $frm_vars['post_forms'] = $forms;

        if(current_user_can('frm_create_entries'))
            add_meta_box('frm_create_entry', __('Create Entry in Form', 'formidable'), 'FrmProEntriesController::render_meta_box_content', null, 'side');
    }

    public static function render_meta_box_content($post){
        global $frm_vars;
        $i = 1;

        echo '<p>';
        foreach ( (array) $frm_vars['post_forms'] as $form ) {
            if($i != 1)
                echo ' | ';

            $i++;
            echo '<a href="javascript:frm_create_post_entry('. $form->id .','. $post->ID .')">'. FrmAppHelper::truncate($form->name, 15) .'</a>';
            unset($form);
        }
        unset($i);
        echo '</p>';


        echo "<script type='text/javascript'>function frm_create_post_entry(id,post_id){
jQuery('#frm_create_entry p').replaceWith('<img src=\"". FrmAppHelper::plugin_url() ."/images/wpspin_light.gif\" alt=\"". __('Loading&hellip;') ."\" />');
jQuery.ajax({type:'POST',url:'". admin_url('admin-ajax.php') ."',data:'action=frm_create_post_entry&id='+id+'&post_id='+post_id,
success:function(msg){jQuery('#frm_create_entry').fadeOut('slow');}
});
};</script>";
    }

    public static function create_post_entry($id=false, $post_id=false){
        if ( ! $id ) {
            $id = $_POST['id'];
        }

        if ( ! $post_id ) {
            $post_id = $_POST['post_id'];
        }

        if ( ! is_numeric($id) || ! is_numeric($post_id) ) {
            return;
        }

        $post = get_post($post_id);

        global $wpdb;
        $values = array(
            'description' => __('Copied from Post', 'formidable'),
            'form_id' => $id,
            'created_at' => $post->post_date_gmt,
            'name' => $post->post_title,
            'item_key' => FrmAppHelper::get_unique_key($post->post_name, $wpdb->prefix .'frm_items', 'item_key'),
            'user_id' => $post->post_author,
            'post_id' => $post->ID
        );

        $results = $wpdb->insert( $wpdb->prefix .'frm_items', $values );
        unset($values);

        if ( ! $results ) {
            die();
        }

        $entry_id = $wpdb->insert_id;
        $user_id_field = FrmField::get_all_types_in_form($id, 'user_id', 1);

        if ( $user_id_field ) {
            $new_values = array(
                'meta_value' => $post->post_author,
                'item_id' => $entry_id,
                'field_id' => $user_id_field->id,
                'created_at' => current_time('mysql', 1)
            );

            $wpdb->insert( $wpdb->prefix .'frm_item_metas', $new_values );
        }

        $display = FrmProDisplay::get_auto_custom_display(array('form_id' => $id, 'entry_id' => $entry_id));
        if ( $display ) {
            update_post_meta($post->ID, 'frm_display_id', $display->ID);
        }

        die();
    }



    /* Export to CSV */
    public static function csv($form_id=false, $search = '', $fid = ''){
        if ( ! $form_id ) {
            $form_id = FrmAppHelper::get_param('form');
            $search = FrmAppHelper::get_param(isset($_REQUEST['s']) ? 's' : 'search');
            $fid = FrmAppHelper::get_param('fid');
        }

        FrmAppHelper::permission_check('frm_view_entries');

        if ( ! ini_get('safe_mode') ) {
            set_time_limit(0); //Remove time limit to execute this function
            $mem_limit = str_replace('M', '', ini_get('memory_limit'));
            if ( (int) $mem_limit < 256 ) {
                ini_set('memory_limit', '256M');
            }
        }

        global $wpdb;

        $form = FrmForm::getOne($form_id);
        $form_id = $form->id;

        $csv_fields = apply_filters('frm_csv_field_ids', '', $form_id, array('form' => $form));
        $form_cols = FrmField::getAll("fi.type not in ('". implode("','", FrmFieldsHelper::no_save_fields() ) ."') " . ( $csv_fields ? 'and fi.id in (' . $csv_fields . ') ' : '' ) . 'and (fi.form_id='. $form->id .' OR fr.parent_form_id = '. $form->id .')', 'field_order');

        $item_id = FrmAppHelper::get_param('item_id', false);
        $where_clause = $wpdb->prepare("form_id=%d", $form_id);

        if($item_id){
            $where_clause .= ' and id in (';
            $item_ids = explode(',', $item_id);
            $where_clause .= implode(',', array_filter( $item_ids, 'is_numeric' ));
            unset($item_ids);

            $where_clause .= ')';
        }else if(!empty($search)){
            $where_clause = FrmProEntriesHelper::get_search_str($where_clause, $search, $form_id, $fid);
        }

        $where_clause = apply_filters('frm_csv_where', $where_clause, compact('form_id'));

        $entry_ids = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}frm_items it WHERE $where_clause");
        unset($where_clause);

        $comment_count = $wpdb->get_var('SELECT COUNT(*) FROM '. $wpdb->prefix .'frm_item_metas WHERE item_id in ('. implode($entry_ids, ',') .') and field_id=0 GROUP BY item_id ORDER BY count(*) DESC LIMIT 1');

        $form_name = sanitize_title_with_dashes($form->name);
        $filename = apply_filters('frm_csv_filename', date("ymdHis",time()) . '_' . $form_name . '_formidable_entries.csv', $form);
        $wp_date_format = apply_filters('frm_csv_date_format', 'Y-m-d H:i:s');
        $charset = get_option('blog_charset');

        $frmpro_settings = new FrmProSettings();
        $to_encoding = isset($_POST['csv_format']) ? $_POST['csv_format'] : $frmpro_settings->csv_format;
        $line_break = apply_filters('frm_csv_line_break', 'return');
        $sep = apply_filters('frm_csv_sep', ', ');
        $col_sep = (isset($_POST['csv_col_sep']) && !empty($_POST['csv_col_sep'])) ? $_POST['csv_col_sep'] : ',';
        $col_sep = apply_filters('frm_csv_column_sep', $col_sep);

        require(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-entries/csv.php');
        die();
    }

    public static function get_search_str($where_clause='', $search_str, $form_id=false, $fid=false) {
        _deprecated_function( __FUNCTION__, '2.0', 'FrmProEntriesHelper::get_search_str' );
        return FrmProEntriesHelper::get_search_str($where_clause, $search_str, $form_id, $fid);
    }

    public static function get_new_vars($errors = array(), $form = false, $message = ''){
        global $frm_vars;
        $description = true;
        $title = false;
        $form = apply_filters('frm_pre_display_form', $form);
        if ( ! $form ) {
            wp_die( __('You are trying to access an entry that does not exist.', 'formidable') );
            return;
        }

        $fields = FrmFieldsHelper::get_form_fields($form->id, !empty($errors));
        $values = $fields ? FrmEntriesHelper::setup_new_vars($fields, $form) : array();

        $frm_settings = FrmAppHelper::get_settings();
        $submit = (isset($frm_vars['next_page'][$form->id])) ? $frm_vars['next_page'][$form->id] : (isset($values['submit_value']) ? $values['submit_value'] : $frm_settings->submit_value);

        if(is_object($submit))
            $submit = $submit->name;
        require(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-entries/new.php');
    }

    private static function get_edit_vars($id, $errors = '', $message= ''){
        global $frm_vars;
        $description = true;
        $title = false;

        $record = FrmEntry::getOne( $id, true );
        if ( ! $record ) {
            wp_die( __('You are trying to access an entry that does not exist.', 'formidable') );
            return;
        }

        $frm_vars['editing_entry'] = $id;

        $form = FrmForm::getOne($record->form_id);
        $form = apply_filters('frm_pre_display_form', $form);

        $fields = FrmFieldsHelper::get_form_fields($form->id, !empty($errors));
        $values = FrmAppHelper::setup_edit_vars($record, 'entries', $fields);

        $frmpro_settings = new FrmProSettings();
        $edit_create = ($record->is_draft) ? (isset($values['submit_value']) ? $values['submit_value'] : $frmpro_settings->submit_value) : (isset($values['edit_value']) ? $values['edit_value'] : $frmpro_settings->update_value);
        $submit = (isset($frm_vars['next_page'][$form->id])) ? $frm_vars['next_page'][$form->id] : $edit_create;
        unset($edit_create);

        if(is_object($submit))
            $submit = $submit->name;
        require(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-entries/edit.php');
    }

    public static function &filter_shortcode_value($value, $tag, $atts){
        if ( isset($atts['striphtml']) && $atts['striphtml'] ) {
            $allowed_tags = apply_filters('frm_striphtml_allowed_tags', array(), $atts);
            $value = wp_kses($value, $allowed_tags);
        }

        if ( ! isset($atts['keepjs']) || ! $atts['keepjs'] ) {
            if ( is_array($value) ) {
                foreach ( $value as $k => $v ) {
                    $value[$k] = wp_kses_post($v);
                    unset($k, $v);
                }
            } else {
                $value = wp_kses_post($value);
            }
        }

        return $value;
    }

    public static function &filter_display_value($value, $field, $atts=array()){
        switch ( $atts['type'] ) {
            case 'user_id':
                $value = FrmProFieldsHelper::get_display_name($value);
            break;
            case 'date':
                $value = FrmProFieldsHelper::get_date($value);
            break;
            case 'file':
                $old_value = $value;
                if ( $atts['html'] ) {
                    $value = '<div class="frm_file_container">';
                } else {
                    $value = '';
                }

                foreach ( (array) $old_value as $mid ) {
                    if ( $atts['html'] ){
                        $img = FrmProFieldsHelper::get_file_icon($mid);
                        $value .= $img;
                        if ( $atts['show_filename'] && $img && preg_match("/wp-includes\/images\/crystal/", $img) ) {
                            //prevent two filenames
                            $atts['show_filename'] = $show_filename = false;
                        }

                        unset($img);

                        if ( $atts['html'] && $atts['show_filename'] ) {
                            $value .= '<br/>' . FrmProFieldsHelper::get_file_name($mid) . '<br/>';
                        }

                        if ( isset( $show_filename ) ) {
                            //if skipped filename, show it for the next file
                            $atts['show_filename'] = true;
                            unset($show_filename);
                        }
                    } else if ( $mid ) {
                        $value .= FrmProFieldsHelper::get_file_name($mid) . $atts['sep'];
                    }
                }

                $value = rtrim($value, $atts['sep']);
                if ( $atts['html'] ) {
                    $value .= '</div>';
                }
            break;

            case 'data':
                if ( ! is_numeric($value) ) {
                    if ( ! is_array($value) ) {
                        $value = explode($atts['sep'], $value);
                    }

                    if ( is_array($value) ) {
                        $new_value = '';
                        foreach ( $value as $entry_id ) {
                            if ( !empty($new_value) ) {
                                $new_value .= $atts['sep'];
                            }

                            if ( is_numeric($entry_id) ) {
                                $dval = FrmProFieldsHelper::get_data_value($entry_id, $field, $atts);
                                if ( is_array($dval) ) {
                                    $dval = implode($atts['sep'], $dval);
                                }
                                $new_value .= $dval;
                            } else {
                                $new_value .= $entry_id;
                            }
                        }
                        $value = $new_value;
                    }
                } else {
                    //replace item id with specified field
                    $new_value = FrmProFieldsHelper::get_data_value($value, $field, $atts);

                    if ( $field->field_options['data_type'] == 'data' || $field->field_options['data_type'] == '' ) {
                        $linked_field = FrmField::getOne($field->field_options['form_select']);
                        if ( $linked_field->type == 'file' ) {
                            $old_value = explode(', ', $new_value);
                            $new_value = '';
                            foreach ( $old_value as $v ) {
                                $new_value .= '<img src="'. $v .'" height="50px" alt="" />';
                                if ( $atts['show_filename'] ) {
                                    $new_value .= '<br/>'. $v;
                                }
                                unset($v);
                            }
                        } else {
                            $new_value = $value;
                        }
                    }

                    $value = $new_value;
                }
            break;

            case 'image':
                $value = '<img src="'. $value .'" height="50px" alt="" />';
            break;
        }

        if ( ! $atts['keepjs'] ) {
            $value = wp_kses_post($value);
        }

        return FrmEntriesController::filter_display_value($value, $field, $atts);
    }

    public static function route($action) {
        add_filter('frm_entry_stop_action_route', '__return_true');

        add_action('frm_load_form_hooks', 'FrmFormsController::trigger_load_form_hooks');
        FrmAppHelper::trigger_hook_load( 'form' );

        switch ( $action ) {
            case 'create':
                return self::create();
            case 'edit':
                return self::edit();
            case 'update':
                return self::update();
            case 'duplicate':
                return self::duplicate();

            case 'new':
                return self::new_entry();

            default:
                $action = FrmAppHelper::get_param('action');
                if ( $action == -1 ) {
                    $action = FrmAppHelper::get_param('action2');
                }

                if ( strpos($action, 'bulk_') === 0 ) {
                    FrmAppHelper::remove_get_action();
                    return self::bulk_actions($action);
                }

                return FrmEntriesController::display_list();
        }
    }

    /*
    * @return string The name of the entry listing class
    */
    public static function list_class(){
        return 'FrmProEntriesListHelper';
    }

    public static function manage_columns($columns){
        global $frm_vars;
        $form_id = FrmEntriesHelper::get_current_form_id();

        $columns = array('cb' => '<input type="checkbox" />') + $columns;
        $columns[$form_id .'_post_id'] = __('Post', 'formidable');
        $columns[$form_id .'_is_draft'] = __('Draft', 'formidable');

        $frm_vars['cols'] = $columns;

        return $columns;
    }

    public static function row_actions($actions, $item) {
        $edit_link = '?page=formidable-entries&frm_action=edit&id='. $item->id;
		if ( current_user_can('frm_edit_entries') ) {
		    $actions['edit'] = '<a href="' . esc_url( $edit_link ) .'">'. __('Edit') .'</a>';
		}

        if ( current_user_can('frm_create_entries') ) {
            $duplicate_link = '?page=formidable-entries&frm_action=duplicate&id='. $item->id .'&form='. $item->form_id;
            $actions['duplicate'] = '<a href="' . wp_nonce_url( $duplicate_link ) .'">'. __('Duplicate', 'formidable') .'</a>';
        }

        // move delete link to the end of the links
        if ( isset($actions['delete']) ) {
            $delete_link = $actions['delete'];
            unset($actions['delete']);
            $actions['delete'] = $delete_link;
        }

        return $actions;
    }

    public static function get_form_results($atts){
        $atts = shortcode_atts( array(
            'id' => false, 'cols' => 99, 'style' => true,
            'fields' => false, 'clickable' => false, 'user_id' => false,
            'google' => false, 'pagesize' => 20, 'sort' => true,
            'edit_link' => false, 'delete_link' => false, 'page_id' => false,
            'no_entries' => __('No Entries Found', 'formidable'),
            'confirm' =>  __('Are you sure you want to delete that entry?', 'formidable'),
			'drafts' => '0',
        ), $atts );
        if ( ! $atts['id'] ) {
            return;
        }

        $form = FrmForm::getOne($atts['id']);
        if ( ! $form ) {
            return;
        }

        $where = "fi.type not in ('". implode("','", FrmFieldsHelper::no_save_fields() ) ."') and fi.form_id=". (int) $form->id;
        if ( $atts['fields'] ) {
            $atts['fields'] = explode(',', $atts['fields']);
            $f_list = array_filter(array_filter($atts['fields'], 'trim'), 'esc_sql');

            if ( count($atts['fields']) != 1 || ! in_array('id', $atts['fields']) ) {
                //don't search fields if only field id
                $where .= " and (fi.id in ('". implode("','", array_filter($f_list, 'is_numeric'))  ."') or fi.field_key in ('". implode("','", $f_list) ."'))";
            }

        }
        $atts['fields'] = (array) $atts['fields'];

        $form_cols = FrmField::getAll($where, 'field_order', $atts['cols']);
        unset($where);

		//If delete_link is set and frm_action is set to destroy, check if entry should be deleted when page is loaded
		if ( $atts['delete_link'] && isset($_GET['frm_action']) && $_GET['frm_action'] == 'destroy' ) {
			$delete_message = self::ajax_destroy(false, false, false);
		}

		//Set up WHERE for getting entries. Get entries for the specified form and only get drafts if user includes drafts=1
		global $wpdb;
		$where = $wpdb->prepare('it.form_id=%d', $form->id);

		if ( $atts['drafts'] != 'both' ) {
		    $where .= $wpdb->prepare(' AND it.is_draft=%d', $atts['drafts']);
		}

        if ( $atts['user_id'] ) {
            $where .= $wpdb->prepare(' AND user_id=%d', (int) FrmAppHelper::get_user_id_param($atts['user_id']));
        }

        $s = FrmAppHelper::get_param('frm_search', false);
        if ( $s ) {
            $new_ids = FrmProEntriesHelper::get_search_ids($s, $form->id, array( 'is_draft' => $atts['drafts'] ));
            $where .= ' AND it.id in ('. implode(',', $new_ids) .')';
        }

        if ( isset($new_ids) && empty($new_ids) ) {
            $entries = false;
        } else {
            $entries = FrmEntry::getAll($where, '', '', true, false);
        }

        if ( $atts['edit_link'] ) {
            $anchor = '';
            if ( ! $atts['page_id'] ) {
                global $post;
                $atts['page_id'] = $post->ID;
                $anchor = '#form_'. $form->form_key;
            }
            if ( $atts['edit_link'] === '1' ) {
                $atts['edit_link'] = __('Edit', 'formidable');
			}
            $permalink = get_permalink($atts['page_id']);
        }

		//If delete_link is set, set the delete link text
		if ( $atts['delete_link'] === '1' ) {
			$atts['delete_link'] = __('Delete', 'formidable');
		}

        if ( $atts['style'] ) {
            global $frm_vars;
            $frm_vars['load_css'] = true;
        }

        $filename = 'table';
        if ( $atts['google'] ) {
            global $frm_google_chart;
            $filename = 'google_table';
            $options = array();

            if ( $atts['pagesize'] ) {
                $options = array('page' => 'enable', 'pageSize' => (int) $atts['pagesize']);
            }

            $options['allowHtml'] = true;
            $options['sort'] = $atts['sort'] ? 'enable' : 'disable';

            if ( $atts['style'] ) {
                $options['cssClassNames'] = array('oddTableRow' => 'frm_even');
            }
        }

        ob_start();
		if ( isset($delete_message) ) {
			echo '<div class="'. ( $atts['style'] ? FrmFormsHelper::get_form_style_class() : '' ) . '"><div class="frm_message">'. $delete_message .'</div></div>';
			unset ($delete_message);
		}
        include(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-entries/'. $filename .'.php');
        $contents = ob_get_contents();
        ob_end_clean();

        if ( ! $atts['google'] && $atts['clickable'] ) {
            $contents = make_clickable($contents);
        }

        return $contents;
    }

    public static function get_search($atts){
        $atts = shortcode_atts(array(
            'post_id' => '', 'label' => __('Search', 'formidable'),
            'style' => false,
        ), $atts);

        if ( $atts['post_id'] == '' ) {
            global $post;
            if($post)
                $atts['post_id'] = $post->ID;
        }

        if ( $atts['post_id'] != '' ) {
            $action_link = get_permalink($atts['post_id']);
        } else {
            $action_link = '';
        }

        if ( ! empty($atts['style']) ) {
            global $frm_vars;
            $frm_vars['forms_loaded'][] = true;

            if ( $atts['style'] == 1 || 'true' == $atts['style'] ) {
                $atts['style'] = FrmStylesController::get_form_style_class('with_frm_style', 'default');
            } else {
                $atts['style'] .= ' with_frm_style';
            }
        }

        ob_start();
        include(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-entries/search.php');
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }

    public static function entry_link_shortcode( $atts ) {
        $atts = shortcode_atts(array(
            'id' => false, 'field_key' => 'created_at', 'type' => 'list', 'logged_in' => true,
            'edit' => true, 'class' => '', 'link_type' => 'page', 'blank_label' => '',
            'param_name' => 'entry', 'param_value' => 'key', 'page_id' => false, 'show_delete' => false,
            'confirm' => __('Are you sure you want to delete that entry?', 'formidable'),
            'drafts' => false,
        ), $atts);

        $user_ID = get_current_user_id();
        if ( ! $atts['id'] || ( $atts['logged_in'] && ! $user_ID) ) {
            return;
        }

        $atts = self::fill_entry_links_atts($atts);

        $action = ( isset($_GET) && isset($_GET['frm_action']) ) ? 'frm_action' : 'action';
        $entry_action = FrmAppHelper::simple_get($action);
        $entry_key = FrmAppHelper::simple_get('entry');

        if ( $entry_action == 'destroy' ) {
            self::maybe_delete_entry($entry_key);
        }

        $entries = self::get_entry_link_entries( $atts );
        if ( empty($entries) ) {
            return;
        }

        $public_entries = array();
        $post_status_check = array();

        foreach ( $entries as $k => $entry ) {
            if ( $entry_action == 'destroy' && in_array($entry_key, array($entry->item_key, $entry->id)) ) {
                continue;
            }

            if ( $entry->post_id ) {
                $post_status_check[$entry->post_id] = $entry->id;
            }
            $public_entries[$entry->id] = $entry;
        }

        if ( ! empty($post_status_check) ) {
            global $wpdb;
            $query = $wpdb->prepare('SELECT ID FROM '. $wpdb->posts .' WHERE post_status != %s AND ID in ', 'publish') .'('. implode(',', array_keys($post_status_check)) .')';
            $remove_entries = FrmAppHelper::check_cache( 'private_posts_'. implode('-', $post_status_check), 'frm_entries', $query, 'get_col');
            unset($query);

            foreach ( $remove_entries as $entry_post_id ) {
                unset($public_entries[$post_status_check[$entry_post_id]]);
            }
            unset($remove_entries);
        }

        $entries = $public_entries;
        unset($public_entries);

        $content = array();
        switch ( $atts['type'] ) {
            case 'list':
                self::entry_link_list($entries, $atts, $content);
            break;
            case 'select':
                self::entry_link_select($entries, $atts, $content);
            break;
            case 'collapse':
                self::entry_link_collapse($entries, $atts, $content);
        }

        $content = implode('', $content);
        return $content;
    }

    private static function fill_entry_links_atts($atts) {
        $atts['id'] = (int) $atts['id'];
        if ( $atts['show_delete'] === 1 ) {
            $atts['show_delete'] = __('Delete');
        }
        $atts['label'] = $atts['show_delete'];

        $atts['field'] = false;
        if ( $atts['field_key'] != 'created_at' ) {
            $atts['field'] = FrmField::getOne($atts['field_key']);
            if ( ! $atts['field'] ) {
                $atts['field_key'] = 'created_at';
            }
        }

        if ( ! in_array($atts['type'], array('list', 'collapse', 'select') ) ) {
            $atts['type'] = 'select';
        }

        global $post;
        $atts['permalink'] = get_permalink( $atts['page_id'] ? $atts['page_id'] : $post->ID );

        return $atts;
    }

    private static function get_entry_link_entries($atts) {
        $s = FrmAppHelper::get_param('frm_search', false);

        // Convert logged_in parameter to user_id for other functions
        $atts['user_id'] = false;
        if ( $atts['logged_in'] ) {
            global $wpdb;
            $atts['user_id'] = get_current_user_id();
        }

        if ( $s ) {
            $entry_ids = FrmProEntriesHelper::get_search_ids( $s, $atts['id'], array( 'is_draft' => $atts['drafts'], 'user_id' =>  $atts['user_id'] ) );
        } else {
            $entry_ids = FrmEntryMeta::getEntryIds(array('fi.form_id' => (int) $atts['id']), '', '', true, array( 'is_draft' => $atts['drafts'], 'user_id' =>  $atts['user_id'] ) );
        }

        if ( empty($entry_ids) ) {
            return;
        }

        $id_list = implode(',', $entry_ids);
        $order = ( $atts['type'] == 'collapse' ) ? ' ORDER BY it.created_at DESC' : '';

        $where = 'it.id in ('. $id_list .')';

        $entries = FrmEntry::getAll($where, $order, '', true);
        return $entries;
    }

    private static function entry_link_list($entries, $atts, array &$content) {
        $content[] = '<ul class="frm_entry_ul '. $atts['class'] .'">'. "\n";

        foreach ( $entries as $entry ) {
            $value = self::entry_link_meta_value($entry, $atts);
            $link = self::entry_link_href($entry, $atts);

            $content[] = '<li><a href="'. $link .'">'. $value .'</a>';
            if ( $atts['show_delete'] && FrmProEntriesHelper::user_can_delete($entry) ) {
                $content[] = ' <a href="'. add_query_arg(array('frm_action' => 'destroy', 'entry' => $entry->id), $atts['permalink']) .'" class="frm_delete_list" onclick="return confirm(\''. $atts['confirm'] .'\')">'. $atts['show_delete'] .'</a>'. "\n";
            }
            $content[] = "</li>\n";
        }

        $content[] = "</ul>\n";
    }

    private static function entry_link_collapse($entries, $atts, array &$content) {
        FrmStylesHelper::enqueue_jquery_css();
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('formidable');

        $content[] = '<div class="frm_collapse">';
        $year = $month = '';
        $prev_year = $prev_month = false;

        foreach ( $entries as $entry ) {
            $value = self::entry_link_meta_value($entry, $atts);
            $link = self::entry_link_href($entry, $atts);

            $new_year = strftime('%G', strtotime($entry->created_at));
            $new_month = strftime('%B', strtotime($entry->created_at));
            if ( $new_year != $year ) {
                if ( $prev_year ) {
                    if ( $prev_month ) {
                        $content[] = '</ul></div>';
                    }
                    $content[] = '</div>';
                    $prev_month = false;
                }
                $style = $prev_year ? ' style="display:none"' : '';
                $triangle = $prev_year ? 'e' : 's';
                $content[] = "\n". '<div class="frm_year_heading frm_year_heading_'. $atts['id'] .'">
                    <span class="ui-icon ui-icon-triangle-1-'. $triangle .'"></span>' ."\n".
                    '<a>'. $new_year .'</a></div>'. "\n".
                    '<div class="frm_toggle_container" '. $style .'>'. "\n";
                $prev_year = true;
            }

            if ( $new_month != $month ) {
                if ( $prev_month ) {
                    $content[] = '</ul></div>';
                }
                $style = $prev_month ? ' style="display:none"' : '';
                $triangle = $prev_month ? 'e' : 's';
                $content[] = '<div class="frm_month_heading frm_month_heading_'. $atts['id'] .'">
                    <span class="ui-icon ui-icon-triangle-1-'. $triangle .'"></span>'. "\n".
                    '<a>'. $new_month .'</a>'. "\n" .'</div>' ."\n".
                    '<div class="frm_toggle_container frm_month_listing" '. $style .'><ul>'. "\n";
                $prev_month = true;
            }
            $content[] = '<li><a href="'. $link .'">'. $value .'</a>';

            if ( $atts['show_delete'] && FrmProEntriesHelper::user_can_delete($entry) ) {
                $content[] = ' <a href="'. add_query_arg(array('frm_action' => 'destroy', 'entry' => $entry->id), $atts['permalink']) .'" class="frm_delete_list" onclick="return confirm(\''. $atts['confirm'] .'\')">'. $atts['show_delete'] .'</a>'. "\n";
            }
            $content[] = "</li>\n";
            $year = $new_year;
            $month = $new_month;
        }

        if ( $prev_year ) {
            $content[] = '</div>';
        }
        if ( $prev_month ) {
            $content[] = '</ul></div>';
        }
        $content[] = '</div>';
    }

    private static function entry_link_select($entries, $atts, array &$content) {
        global $post;

        $content[] = '<select id="frm_select_form_'. $atts['id'] .'" name="frm_select_form_'. $atts['id'] .'" class="'. $atts['class'] .'" onchange="location=this.options[this.selectedIndex].value;">' ."\n";
        $content[] = '<option value="'. get_permalink($post->ID) .'">'. $atts['blank_label'] .'</option>'. "\n";
        $entry_param = FrmAppHelper::simple_get('entry');

        foreach ( $entries as $entry ) {
            $value = self::entry_link_meta_value($entry, $atts);
            $link = self::entry_link_href($entry, $atts);

            $current = ( $entry_param == $entry->item_key ) ? true : false;
            $selected = $current ? ' selected="selected"' : '';
            $content[] = '<option value="'. $link .'" '. $selected .'>' . esc_attr($value) . "</option>\n";
        }

        $content[] = "</select>\n";
        if ( $atts['show_delete'] && $entry_param ) {
            $content[] = " <a href='". add_query_arg(array('frm_action' => 'destroy', 'entry' => $entry_param), $atts['permalink']) ."' class='frm_delete_list' onclick='return confirm(\"". $atts['confirm'] ."\")'>". $atts['show_delete'] ."</a>\n";
        }
    }

    private static function entry_link_meta_value($entry, $atts) {
        $value = '';

        if ( $atts['field_key'] && $atts['field_key'] != 'created_at' ) {
            if ( $entry->post_id && ( ( $atts['field'] && $atts['field']->field_options['post_field'] ) || $atts['field']->type == 'tag' ) ) {
                $meta = false;
                $value = FrmProEntryMetaHelper::get_post_value(
                    $entry->post_id, $atts['field']->field_options['post_field'], $atts['field']->field_options['custom_field'],
                    array(
                        'type' => $atts['field']->type, 'form_id' => $atts['field']->form_id, 'field' => $atts['field']
                    )
                );
            } else {
                $meta = isset($entry->metas[$atts['field']->id]) ? $entry->metas[$atts['field']->id] : '';
            }
        } else {
            $meta = reset($entry->metas);
        }

        self::entry_link_value($entry, $atts, $meta, $value);

        return $value;
    }

    private static function entry_link_value($entry, $atts, $meta, &$value) {
        if ( 'created_at' != $atts['field_key'] && $meta ) {
            if ( is_object($meta) ) {
                $value = $meta->meta_value;
            } else {
                $value = $meta;
            }
        }

        if ( '' == $value ) {
            $value = date_i18n(get_option('date_format'), strtotime($entry->created_at));
            return;
        }

        $value = FrmEntriesHelper::display_value($value, $atts['field'], array(
            'type' => $atts['field']->type, 'show_filename' => false
        ));
    }

    private static function entry_link_href($entry, $atts) {
        $args = array(
            $atts['param_name'] => ( 'key' == $atts['param_value'] ) ? $entry->item_key : $entry->id,
        );

        if ( $atts['edit'] ) {
            $args['frm_action'] = 'edit';
        }

        if ( $atts['link_type'] == 'scroll' ) {
            $link = '#'. $entry->item_key;
        } else if ( $atts['link_type'] == 'admin' ) {
            $link = add_query_arg($args, $_SERVER['REQUEST_URI']);
        } else {
            $link = add_query_arg($args, $atts['permalink']);
        }

        return $link;
    }

    public static function entry_edit_link($atts){
        global $post, $frm_vars, $wpdb;
        $atts = shortcode_atts(array(
            'id' => (isset($frm_vars['editing_entry']) ? $frm_vars['editing_entry'] : false),
            'label' => __('Edit'), 'cancel' => __('Cancel', 'formidable'),
            'class' => '', 'page_id' => (($post) ? $post->ID : 0), 'html_id' => false,
            'prefix' => '', 'form_id' => false, 'title' => '',
        ), $atts);

        $link = '';
        $entry_id = ( $atts['id'] && is_numeric($atts['id']) ) ? $atts['id'] : FrmAppHelper::get_param('entry', false);

        if ( empty($entry_id) && $atts['id'] == 'current' ) {
            if ( isset($frm_vars['editing_entry']) && $frm_vars['editing_entry'] && is_numeric($frm_vars['editing_entry']) ) {
                $entry_id = $frm_vars['editing_entry'];
            } else if ( $post ) {
                $entry_id = $wpdb->get_var( $wpdb->prepare('SELECT id FROM '. $wpdb->prefix .'frm_items WHERE post_id = %d', $post->ID) );
            }
        }

        if ( ! $entry_id || empty($entry_id) ) {
            return '';
        }

        if ( ! $atts['form_id'] ) {
            $atts['form_id'] = (int) $wpdb->get_var( $wpdb->prepare('SELECT form_id FROM '. $wpdb->prefix .'frm_items WHERE id = %d', $entry_id) );
        }

        //if user is not allowed to edit, then don't show the link
        if ( ! FrmProEntriesHelper::user_can_edit($entry_id, $atts['form_id']) ) {
            return $link;
        }

        if ( empty($atts['prefix']) ) {
           $link = add_query_arg(array('frm_action' => 'edit', 'entry' => $entry_id), get_permalink($atts['page_id']));

           if ( $atts['label'] ) {
               $link = '<a href="'. $link .'" class="'. $atts['class'] .'">'. $atts['label'] .'</a>';
           }

           return $link;
        }



        $action = (isset($_POST) && isset($_POST['frm_action'])) ? 'frm_action' : 'action';
        if ( isset($_POST[$action]) && $_POST[$action] =='update' && isset($_POST['form_id']) && $_POST['form_id'] == $atts['form_id'] && isset($_POST['id']) && $_POST['id'] == $entry_id ) {
            $errors = ( isset($frm_vars['created_entries'][$atts['form_id']]) && isset($frm_vars['created_entries'][$atts['form_id']]['errors']) ) ? $frm_vars['created_entries'][$atts['form_id']]['errors'] : array();

            if ( ! empty($errors) ) {
                return FrmFormsController::get_form_shortcode(array('id' => $atts['form_id'], 'entry_id' => $entry_id));
            }

            $link .= "<script type='text/javascript'>window.onload= function(){var frm_pos=jQuery('#". $atts['prefix'] . $entry_id ."').offset();window.scrollTo(frm_pos.left,frm_pos.top);}</script>";
        }

        if ( empty($atts['title']) ) {
            $atts['title'] = $atts['label'];
        }

        if ( ! $atts['html_id'] ) {
            $atts['html_id'] = 'frm_edit_'. $entry_id;
        }

        $frm_vars['forms_loaded'][] = true;
        $link .= "<a href='javascript:frmEditEntry(". $entry_id .',"'. $atts['prefix'] .'",'. $atts['page_id'] .','. $atts['form_id'] .',"'. htmlspecialchars(str_replace("'", '\"', $atts['cancel'])) .'","'. $atts['class'] ."\")' class='frm_edit_link ". $atts['class'] ."' id='". esc_attr($atts['html_id']) ."' title='". esc_attr($atts['title']) ."'>". $atts['label'] ."</a>\n";

        return $link;
    }

    public static function entry_update_field($atts){
        global $frm_vars, $post, $frm_update_link, $wpdb;

        $atts = shortcode_atts(array(
            'id' => (isset($frm_vars['editing_entry']) ? $frm_vars['editing_entry'] : false),
            'field_id' => false, 'form_id' => false,
            'label' => __('Update', 'formidable'), 'class' => '', 'value' => '',
            'message' => '', 'title' => '',
        ), $atts);

        $entry_id = (int) ( $atts['id'] && is_numeric($atts['id'])) ? $atts['id'] : FrmAppHelper::get_param('entry', false);

        if ( ! $entry_id || empty($entry_id) ) {
            return;
        }

        if ( ! $atts['form_id'] ) {
            $frmdb = new FrmDb();
            $atts['form_id'] = (int) $frmdb->get_var($wpdb->prefix .'frm_items', array('id' => $entry_id), 'form_id');
        }

        if ( ! FrmProEntriesHelper::user_can_edit($entry_id, $atts['form_id']) ) {
            return;
        }

        $field = FrmField::getOne($atts['field_id']);
        if ( ! $field ) {
            return;
        }

        if ( ! is_numeric($atts['field_id']) ) {
            $atts['field_id'] = $field->id;
        }

        //check if current value is equal to new value
        $current_val = FrmProEntryMetaHelper::get_post_or_meta_value($entry_id, $field);
        if ( $current_val == $atts['value'] ) {
            return;
        }

        if ( ! $frm_update_link ) {
            $frm_update_link = array();
        }

        $num = isset($frm_update_link[$entry_id .'-'. $atts['field_id']]) ? $frm_update_link[$entry_id .'-'. $atts['field_id']] : 0;
        $num = (int) $num + 1;
        $frm_update_link[$entry_id .'-'. $atts['field_id']] = $num;

        if ( empty($atts['title']) ) {
            $atts['title'] = $atts['label'];
        }

        $link = '<a href="#" onclick="frmUpdateField('. $entry_id .','. $atts['field_id'] .',\''. $atts['value'] .'\',\''. htmlspecialchars(str_replace("'", '\"', $atts['message'])) .'\','. $num .');return false;" id="frm_update_field_'. $entry_id .'_'. $atts['field_id'] .'_'. $num .'" class="frm_update_field_link '. $atts['class'] .'" title="'. esc_attr($atts['title']) .'">'. $atts['label'] .'</a>';

        return $link;
    }

    public static function entry_delete_link($atts){
        global $post, $frm_vars;
        $atts = shortcode_atts(array(
            'id' => (isset($frm_vars['editing_entry']) ? $frm_vars['editing_entry'] : false), 'label' => __('Delete'),
            'confirm' => __('Are you sure you want to delete that entry?', 'formidable'),
            'class' => '', 'page_id' => (($post) ? $post->ID : 0), 'html_id' => false, 'prefix' => '',
            'title' => '',
        ), $atts);

        $entry_id = ( $atts['id'] && is_numeric($atts['id']) ) ? $atts['id'] : ( FrmAppHelper::is_admin() ? FrmAppHelper::get_param('id', false) : FrmAppHelper::get_param('entry', false) );

        if ( empty($entry_id) ) {
            return '';
        }

        // Check if user has permission to delete before showing link
        if ( ! FrmProEntriesHelper::user_can_delete($entry_id) ) {
            return '';
        }

        $frm_vars['forms_loaded'][] = true;

        if ( ! empty($atts['prefix']) ) {
            if ( ! $atts['html_id'] ) {
                $atts['html_id'] = 'frm_delete_'. $entry_id;
            }

            $link = "<a href='javascript:frmDeleteEntry(". $entry_id .",\"". $atts['prefix'] ."\")' class='frm_delete_link ". $atts['class'] ."' id='". $atts['html_id'] ."' onclick='return confirm(\"". $atts['confirm'] ."\")'>". $atts['label'] ."</a>\n";
            return $link;
        }

        $link = '';

        // Delete entry now
        $action = FrmAppHelper::get_param('frm_action');
        if ( $action == 'destroy' ) {
            $entry_key = FrmAppHelper::get_param('entry');
            if ( is_numeric($entry_key) && $entry_key == $entry_id ) {
                $link = self::ajax_destroy(false, false, false);
                if ( ! empty($link) ) {
                    $new_link = '<div class="frm_message">'. $link .'</div>';
                    if ( empty($atts['label']) ) {
                        return;
                    }

                    if ( $link == __('Your entry was successfully deleted', 'formidable') ) {
                        return $new_link;
                    } else {
                        $link = $new_link;
                    }

                    unset($new_link);
                }
            }
        }

        $delete_link = wp_nonce_url(admin_url('admin-ajax.php') . '?action=frm_entries_destroy&entry='. $entry_id .'&redirect='. $atts['page_id'], 'frm_ajax', 'nonce');
        if ( empty($atts['label']) ) {
            $link .= $delete_link;
        } else {
            if ( empty($atts['title']) ) {
                $atts['title'] = $atts['label'];
            }
            $link .= '<a href="'. esc_url($delete_link) .'" class="'. $atts['class'] .'" onclick="return confirm(\''. $atts['confirm'] .'\')" title="'. esc_attr($atts['title']) .'">'. $atts['label'] .'</a>'. "\n";
        }

        return $link;
    }

    public static function get_field_value_shortcode($atts){
        $atts = shortcode_atts(array(
            'entry_id' => false, 'field_id' => false, 'user_id' => false,
            'ip' => false, 'show' => '', 'format' => '',
        ), $atts);

        if ( ! $atts['field_id']  ) {
            return __('You are missing options in your shortcode. field_id is required.', 'formidable');
        }

        global $wpdb;

        $field = FrmField::getOne($atts['field_id']);
        if ( ! $field ) {
            return '';
        }

        $query = $wpdb->prepare('SELECT post_id, id FROM '. $wpdb->prefix .'frm_items WHERE form_id=%d', $field->form_id);
        if ( $atts['user_id'] ) {
            // make sure we are not getting entries for logged-out users
            $query .= $wpdb->prepare(' AND user_id=%d AND user_id > %d', (int) FrmAppHelper::get_user_id_param($atts['user_id']), 0);
        }

        if ( $atts['entry_id'] ) {
            if ( ! is_numeric($atts['entry_id']) ) {
                $atts['entry_id'] = isset($_GET[$atts['entry_id']]) ? $_GET[$atts['entry_id']] : $atts['entry_id'];
            }

            if ( (int) $atts['entry_id'] < 1 ) {
                // don't run the sql query if we know there will be no results
                return;
            }

            $query .= $wpdb->prepare(' AND (id=%d OR parent_item_id=%d)', $atts['entry_id'], $atts['entry_id']);
        }

        if ( $atts['ip'] ) {
            $query .= $wpdb->prepare(' AND ip=%s', ( $atts['ip'] == true ) ? FrmAppHelper::get_ip_address() : $atts['ip']);
        }
        $query .= ' ORDER BY created_at DESC LIMIT 1';
        $entry = $wpdb->get_row($query);
        if ( ! $entry ) {
            return;
        }

        $value = FrmProEntryMetaHelper::get_post_or_meta_value($entry, $field, $atts);
        $atts['type'] = $field->type;
        $atts['post_id'] = $entry->post_id;
        $atts['entry_id'] = $entry->id;
        if ( ! isset($atts['show_filename']) ) {
            $atts['show_filename'] = false;
        }

        if ( isset($atts['show']) && ! empty($atts['show']) ) {
            $value = FrmFieldsHelper::get_display_value($value, $field, $atts);
        } else {
            $value = FrmEntriesHelper::display_value( $value, $field, $atts);
        }

        return $value;
    }

    public static function show_entry_shortcode($atts){
        $content = FrmEntriesController::show_entry_shortcode($atts);
        return $content;
    }

	/*
    * Alternate Row Color for Default HTML
    * @return string
    */
	public static function change_row_color() {
		global $frm_email_col;

        $bg_color = 'bg_color';
		if ( $frm_email_col ) {
		    $bg_color .= '_active';
			$frm_email_col = false;
		} else {
			$frm_email_col = true;
		}

        $bg_color = FrmStylesController::get_style_val($bg_color);
        $alt_color = 'background-color:#'. $bg_color .';';
		return $alt_color;
	}

    public static function maybe_set_cookie($entry_id, $form_id) {
        if ( defined('WP_IMPORTING') || defined('DOING_AJAX') ) {
            return;
        }

        if ( isset($_POST) && isset($_POST['frm_skip_cookie']) ) {
            self::set_cookie($entry_id, $form_id);
            return;
        }

        include(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-entries/set_cookie.php');
    }

    /* AJAX */
    public static function ajax_set_cookie(){
        self::set_cookie();
        die();
    }

    public static function set_cookie($entry_id=false, $form_id=false){
        if ( headers_sent() ) {
            return;
        }

        if ( ! apply_filters('frm_create_cookies', true) ) {
            return;
        }

        if ( ! $entry_id ) {
            $entry_id = FrmAppHelper::get_param('entry_id');
        }

        if ( ! $form_id ) {
            $form_id = FrmAppHelper::get_param('form_id');
        }

        $form = FrmForm::getOne($form_id);
        $expiration = isset($form->options['cookie_expiration']) ? ( (float) $form->options['cookie_expiration'] *60*60 ) : 30000000;
        $expiration = apply_filters('frm_cookie_expiration', $expiration, $form_id, $entry_id);
        setcookie('frm_form'.$form_id.'_' . COOKIEHASH, current_time('mysql', 1), time() + $expiration, COOKIEPATH, COOKIE_DOMAIN);
    }

    public static function ajax_create(){
        $form = FrmForm::getOne($_POST['form_id']);
        if ( ! $form ) {
            echo false;
            die();
        }

        $no_ajax_fields = array('file');

        $errors = FrmEntry::validate($_POST, $no_ajax_fields);

        if(empty($errors)){
            global $wpdb;

            $where = $wpdb->prepare("form_id=%d", $form->id);
            if ( isset($_POST['frm_page_order_'. $form->id]) ) {
                $where .= $wpdb->prepare(" AND field_order < %d", $_POST['frm_page_order_'. $form->id]);
            }

            $ajax = (isset($form->options['ajax_submit'])) ? $form->options['ajax_submit'] : 0;
            //ajax submit if no file, rte, captcha
            if($ajax){
                $no_ajax = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_fields WHERE type in ('". implode("','", $no_ajax_fields) ."') AND {$where} LIMIT 1");
                if($no_ajax)
                    $ajax = false;
            }

            if($ajax){
                global $frm_vars;
                $frm_vars['ajax'] = true;
                $frm_vars['css_loaded'] = true;

                if ( ( ! isset($_POST['frm_page_order_'. $form->id]) && ! FrmProFormsHelper::going_to_prev($form->id) ) || FrmProFormsHelper::saving_draft() ) {
                    $processed = true;
                    FrmEntriesController::process_entry($errors, true);
                }

                echo FrmFormsController::show_form($form->id);

                // trigger the footer scripts if there is a form to show
                if ( $errors || ! isset( $form->options['show_form'] ) || $form->options['show_form'] || ! isset( $processed ) ) {
                    self::register_scripts();

                    self::enqueue_footer_js();

                    wp_deregister_script('formidable');

                    global $wp_scripts, $wp_styles;
                    foreach ( array('jquery', 'jquery-ui-core', 'jquery-migrate', 'thickbox') as $s ) {
                        if ( isset($wp_scripts->registered[$s]) ) {
                            $wp_scripts->done[] = $s;
                        }
                        unset($s);
                    }

                    $keep_styles = apply_filters('frm_ajax_load_styles', array('dashicons', 'jquery-theme'));
                    foreach ( $wp_styles->registered as $s => $info ) {
                        if ( ! is_array($keep_styles) || ! in_array($s, $keep_styles) ) {
                            $wp_styles->done[] = $s;
                        }

                        unset($s);
                    }

                    wp_print_footer_scripts();

                    self::footer_js();
                }
            } else {
                echo false;
            }
        }else{
            $errors = str_replace('"', '&quot;', $errors);
            $obj = array();
            foreach($errors as $field => $error){
                $field_id = str_replace('field', '', $field);
                $obj[$field_id] = $error;
            }
            echo json_encode($obj);
        }

        die();
    }

    public static function ajax_update(){
        return self::ajax_create();
    }

    public static function wp_ajax_destroy(){
        check_ajax_referer( 'frm_ajax', 'nonce' );

        $echo = true;
        if ( isset($_REQUEST['redirect']) ) {
            // don't echo if redirecting
            $echo = false;
        }
        self::ajax_destroy(false, true, $echo);

        if ( ! $echo ) {
            // redirect instead of loading a blank page
            wp_redirect(get_permalink($_REQUEST['redirect']));
        }

        die();
    }

    public static function ajax_destroy($form_id = false, $ajax = true, $echo = true) {
        global $wpdb, $frm_vars;

        $entry_key = FrmAppHelper::get_param('entry');
        if ( ! $form_id ) {
            $form_id = FrmAppHelper::get_param('form_id');
        }

        if ( ! $entry_key ) {
            return;
        }

        if ( isset( $frm_vars['deleted_entries'] ) && is_array( $frm_vars['deleted_entries'] ) && in_array( $entry_key, $frm_vars['deleted_entries'] ) ) {
            return;
        }

        if ( is_numeric( $entry_key ) ) {
            $where = $wpdb->prepare('id = %d', $entry_key);
        } else {
            $where = $wpdb->prepare('item_key = %s', $entry_key);
        }

        $entry = $wpdb->get_row("SELECT id, form_id, is_draft, user_id FROM {$wpdb->prefix}frm_items WHERE $where");
        unset( $where );

        if ( ! $entry || ( $form_id && $entry->form_id != (int) $form_id ) ) {
            return;
        }

        $message = self::maybe_delete_entry($entry);
        if ( $message && ! is_numeric($message) ) {
            if ( $echo ) {
                echo '<div class="frm_message">'. $message .'</div>';
            }
            return;
        }

        if ( ! isset( $frm_vars['deleted_entries'] ) || empty( $frm_vars['deleted_entries'] ) ) {
            $frm_vars['deleted_entries'] = array();
        }
        $frm_vars['deleted_entries'][] = $entry->id;

        if ( $ajax && $echo ) {
            echo $message = 'success';
        } else if ( ! $ajax ) {
			$message = apply_filters('frm_delete_message', __( 'Your entry was successfully deleted', 'formidable' ), $entry);

            if ( $echo ) {
                echo '<div class="frm_message">'. $message .'</div>';
            }
        } else {
            $message = '';
        }

        return $message;
    }

    public static function maybe_delete_entry($entry) {
        FrmEntriesHelper::maybe_get_entry( $entry );

        if ( ! $entry || ! FrmProEntriesHelper::user_can_delete($entry) ) {
            $message = __( 'There was an error deleting that entry', 'formidable' );
            return $message;
        }

        $result = FrmEntry::destroy( $entry->id );
        return $result;
    }

    public static function edit_entry_ajax(){
        $id = FrmAppHelper::get_param('id');
        $entry_id = FrmAppHelper::get_param('entry_id', 0);
        $post_id = FrmAppHelper::get_param('post_id', 0);

        global $frm_vars;
        $frm_vars['ajax_edit'] = ($entry_id) ? $entry_id : true;
        $_GET['entry'] = $entry_id;

        if ( $post_id && is_numeric($post_id) ) {
            global $post;
            if ( ! $post ) {
                $post = get_post($post_id);
            }
        }

        wp_enqueue_script('formidable');
        $frm_vars['footer_loaded'] = true;

        echo "<script type='text/javascript'>
/*<![CDATA[*/
jQuery(document).ready(function($){
$('#frm_form_". $id ."_container .frm-show-form').submit(window.frmOnSubmit);
});
/*]]>*/
</script>";
        echo FrmFormsController::get_form_shortcode(compact('id', 'entry_id'));

        $frm_vars['ajax_edit'] = false;

        echo self::after_footer_loaded();

        die();
    }

    public static function update_field_ajax(){
        $entry_id = FrmAppHelper::get_param('entry_id');
        $field_id = FrmAppHelper::get_param('field_id');
        $value = FrmAppHelper::get_param('value');

        global $wpdb;

        $entry_id = (int) $entry_id;

        if ( ! $entry_id ) {
            die();
        }

        $where = $wpdb->prepare( is_numeric($field_id) ? "fi.id=%d" : "field_key=%s", $field_id);

        $field = FrmField::getAll($where, '', ' LIMIT 1');

        if ( ! $field || ! FrmProEntriesHelper::user_can_edit( $entry_id, $field->form_id ) ) {
            die();
        }

        $post_id = false;
        if ( isset($field->field_options['post_field']) && ! empty($field->field_options['post_field']) ) {
            $post_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->prefix}frm_items WHERE id=%d", $entry_id ));
        }

        if ( ! $post_id ) {
            $updated = FrmEntryMeta::update_entry_meta($entry_id, $field_id, $meta_key = null, $value);

            if ( ! $updated ) {
                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}frm_item_metas WHERE item_id = %d and field_id = %d", $entry_id, $field_id));
                $updated = FrmEntryMeta::add_entry_meta($entry_id, $field_id, '', $value);
            }
            wp_cache_delete( $entry_id, 'frm_entry');
        }else{
            switch ( $field->field_options['post_field'] ) {
                case 'post_custom':
                    $updated = update_post_meta($post_id, $field->field_options['custom_field'], maybe_serialize($value));
                break;
                case 'post_category':
                    $taxonomy = ( isset($field->field_options['taxonomy']) && ! empty($field->field_options['taxonomy']) ) ? $field->field_options['taxonomy'] : 'category';
                    $updated = wp_set_post_terms( $post_id, $value, $taxonomy );
                break;
                default:
                    $post = get_post($post_id, ARRAY_A);
                    $post[$field->field_options['post_field']] = maybe_serialize($value);
                    $updated = wp_insert_post( $post );
                break;
            }
        }

        if ( $updated ) {
            // set updated_at time
            $wpdb->update( $wpdb->prefix .'frm_items',
                array('updated_at' => current_time('mysql', 1), 'updated_by' => get_current_user_id()),
                array('id' => $entry_id)
            );
        }

        do_action('frm_after_update_field', compact('entry_id', 'field_id', 'value'));
        die($updated);
    }

    public static function send_email(){
        if ( current_user_can('frm_view_forms') || current_user_can('frm_edit_forms') || current_user_can('frm_edit_entries') ) {
            $entry_id = FrmAppHelper::get_param('entry_id');
            $form_id = FrmAppHelper::get_param('form_id');

            printf(__('Resent to %s', 'formidable'), '');

            add_filter('frm_echo_emails', '__return_true');
            FrmFormActionsController::trigger_actions('create', $form_id, $entry_id, 'email');
        }else{
            _e('Resent to No one! You do not have permission', 'formidable');
        }
        die();
    }

    public static function redirect_url($url){
        $url = str_replace(array(' ', '[', ']', '|', '@'), array('%20', '%5B', '%5D', '%7C', '%40'), $url);
        return $url;
    }

    public static function setup_edit_vars($values) {
        if ( ! isset($values['edit_value']) ) {
            $values['edit_value'] = ($_POST && isset($_POST['options']['edit_value'])) ? $_POST['options']['edit_value'] : __('Update', 'formidable');
        }

        if ( ! isset($values['edit_msg']) ) {
            if ( $_POST && isset($_POST['options']['edit_msg']) ) {
                $values['edit_msg'] = $_POST['options']['edit_msg'];
            } else {
                $frmpro_settings = new FrmProSettings();
                $values['edit_msg'] = $frmpro_settings->edit_msg;
            }
        }

        return $values;
    }

    public static function allow_form_edit($action, $form) {
        _deprecated_function( __FUNCTION__, '2.0', 'FrmProEntriesHelper::allow_form_edit');
        return FrmProEntriesHelper::allow_form_edit($action, $form);
    }

    public static function email_value($value, $meta, $entry) {
        _deprecated_function( __FUNCTION__, '2.0', 'FrmProEntryMetaHelper::email_value');
        return FrmProEntryMetaHelper::email_value($value, $meta, $entry);
    }

    /* Trigger model actions */

    public static function create_post($entry_id, $form_id) {
        _deprecated_function( __FUNCTION__, '2.0', 'FrmProEntriesController::trigger_post');
        FrmProEntry::create_post($entry_id, $form_id);
    }

    public static function update_post($entry_id, $form_id) {
        _deprecated_function( __FUNCTION__, '2.0', 'FrmProEntriesController::trigger_post');
        FrmProEntry::update_post($entry_id, $form_id);
    }

}
