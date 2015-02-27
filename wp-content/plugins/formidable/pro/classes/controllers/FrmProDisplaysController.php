<?php

class FrmProDisplaysController{
    public static $post_type = 'frm_display';

    public static function trigger_load_view_hooks() {
        FrmHooksController::trigger_load_hook( 'load_view_hooks' );
    }

    public static function register_post_types(){
        register_post_type(self::$post_type, array(
            'label' => __('Views', 'formidable'),
            'description' => '',
            'public' => apply_filters('frm_public_views', true),
            'show_ui' => true,
            'exclude_from_search' => true,
            'show_in_nav_menus' => false,
            'show_in_menu' => false,
            'menu_icon' => admin_url('images/icons32.png'),
            'capability_type' => 'page',
            'supports' => array(
                'title', 'revisions', 'post-formats',
            ),
            'has_archive' => false,
            'labels' => array(
                'name' => __('Views', 'formidable'),
                'singular_name' => __('View', 'formidable'),
                'menu_name' => __('View', 'formidable'),
                'edit' => __('Edit'),
                'search_items' => __('Search Views', 'formidable'),
                'not_found' => __('No Views Found.', 'formidable'),
                'add_new_item' => __('Add New View', 'formidable'),
                'edit_item' => __('Edit View', 'formidable')
            )
        ) );
    }

    public static function menu(){
        remove_action('admin_menu', 'FrmStatisticsController::menu', 24);

        add_submenu_page('formidable', 'Formidable | '. __('Views', 'formidable'), __('Views', 'formidable'), 'frm_edit_displays', 'edit.php?post_type=frm_display');

        add_filter('manage_edit-frm_display_columns', 'FrmProDisplaysController::manage_columns');
        add_filter('manage_edit-frm_display_sortable_columns', 'FrmProDisplaysController::sortable_columns');
        add_filter('get_user_option_manageedit-frm_displaycolumnshidden', 'FrmProDisplaysController::hidden_columns');
        add_action('manage_frm_display_posts_custom_column', 'FrmProDisplaysController::manage_custom_columns', 10, 2);
    }

    public static function highlight_menu(){
        FrmAppHelper::maybe_highlight_menu(self::$post_type);
    }

    public static function switch_form_box(){
        global $post_type_object;
        if ( ! $post_type_object || $post_type_object->name != self::$post_type ) {
            return;
        }
        $form_id = (isset($_GET['form'])) ? $_GET['form'] : '';
        echo FrmFormsHelper::forms_dropdown( 'form', $form_id, array( 'blank' => __('View all forms', 'formidable')) );
    }

    public static function filter_forms($query){
        if ( ! FrmProDisplaysHelper::is_edit_view_page() ) {
            return $query;
        }

        if ( isset($_REQUEST['form']) && is_numeric($_REQUEST['form']) && isset($query->query_vars['post_type']) && self::$post_type == $query->query_vars['post_type'] ) {
            $query->query_vars['meta_key'] = 'frm_form_id';
            $query->query_vars['meta_value'] = (int) $_REQUEST['form'];
        }

        return $query;
    }

    public static function add_form_nav($views){
        if ( ! FrmProDisplaysHelper::is_edit_view_page() ) {
            return $views;
        }

        $form = (isset($_REQUEST['form']) && is_numeric($_REQUEST['form'])) ? $_REQUEST['form'] : false;
        if ( ! $form ) {
            return $views;
        }

        $form = FrmForm::getOne($form);
        if ( ! $form ) {
            return $views;
        }

        echo '<div id="poststuff">';
        echo '<div id="post-body" class="metabox-holder columns-2">';
        echo '<div id="post-body-content">';
		FrmAppController::get_form_nav($form, true, 'hide');
		echo '</div>';
		echo '<div class="clear"></div>';
		echo '</div>';
		echo '<div id="titlediv"><input id="title" type="text" value="'. esc_attr($form->name == '' ? __('(no title)') : $form->name) .'" readonly="readonly" disabled="disabled" /></div>';
        echo '</div>';

		echo '<style type="text/css">p.search-box{margin-top:-91px;}</style>';

        return $views;

    }

    public static function post_row_actions($actions, $post){
        if ( $post->post_type == self::$post_type ) {
            $actions['duplicate'] = '<a href="'. admin_url('post-new.php?post_type=frm_display&amp;copy_id='. $post->ID) .'" title="'. esc_attr( __( 'Duplicate', 'formidable' ) ) .'">'. __( 'Duplicate', 'formidable' ) .'</a>';
        }
        return $actions;
    }

    public static function create_from_template($path){
        $templates = glob($path."/*.php");

        for($i = count($templates) - 1; $i >= 0; $i--){
            $filename = str_replace('.php', '', str_replace($path.'/', '', $templates[$i]));
            $display = get_page_by_path($filename, OBJECT, self::$post_type);

            $values = FrmProDisplaysHelper::setup_new_vars();
            $values['display_key'] = $filename;

            include($templates[$i]);
        }
    }

    public static function manage_columns($columns){
        unset($columns['title']);
        unset($columns['date']);

        $columns['id'] = 'ID';
        $columns['title'] = __('View Title', 'formidable');
        $columns['description'] = __('Description');
        $columns['form_id'] = __('Form', 'formidable');
        $columns['show_count'] = __('Entry', 'formidable');
        $columns['post_id'] = __('Page', 'formidable');
        $columns['content'] = __('Content', 'formidable');
        $columns['dyncontent'] = __('Dynamic Content', 'formidable');
        $columns['date'] = __('Date', 'formidable');
        $columns['name'] = __('Key', 'formidable');
        $columns['old_id'] = __('Former ID', 'formidable');
        $columns['shortcode'] = __('Shortcode', 'formidable');

        return $columns;
    }

    public static function sortable_columns($columns) {
        $columns['name'] = 'name';
        $columns['shortcode'] = 'ID';

        //$columns['description'] = 'excerpt';
        //$columns['content'] = 'content';

        return $columns;
    }

    public static function hidden_columns($result){
        $return = false;
        foreach ( (array) $result as $r ) {
            if(!empty($r)){
                $return = true;
                break;
            }
        }

        if ( ! isset($_GET['mode']) || 'excerpt' != $_GET['mode'] ) {
            $result[] = 'description';
        }

        if($return)
            return $result;

        $result[] = 'post_id';
        $result[] = 'content';
        $result[] = 'dyncontent';
        $result[] = 'old_id';

        return $result;
    }

    public static function manage_custom_columns($column_name, $id){
        switch ( $column_name ) {
			case 'id':
			    $val = $id;
			    break;
			case 'old_id':
			    $old_id = get_post_meta($id, 'frm_old_id', true);
			    $val = ($old_id) ? $old_id : __('N/A', 'formidable');
			    break;
			case 'name':
			case 'content':
			    $post = get_post($id);
			    $val = FrmAppHelper::truncate(strip_tags($post->{"post_$column_name"}), 100);
			    break;
			case 'description':
			    $post = get_post($id);
			    $val = FrmAppHelper::truncate(strip_tags($post->post_excerpt), 100);
		        break;
			case 'show_count':
			    $val = ucwords(get_post_meta($id, 'frm_'. $column_name, true));
			    break;
			case 'dyncontent':
			    $val = FrmAppHelper::truncate(strip_tags(get_post_meta($id, 'frm_'. $column_name, true)), 100);
			    break;
			case 'form_id':
			    $form_id = get_post_meta($id, 'frm_'. $column_name, true);
			    $val = FrmFormsHelper::edit_form_link($form_id);
				break;
			case 'post_id':
			    $insert_loc = get_post_meta($id, 'frm_insert_loc', true);
			    if(!$insert_loc or $insert_loc == 'none'){
			        $val = '';
			        break;
			    }

			    $post_id = get_post_meta($id, 'frm_'. $column_name, true);
			    $val = FrmAppHelper::post_edit_link($post_id);
			    break;
			case 'shortcode':
			    $code = '[display-frm-data id='. $id .' filter=1]';

			    $val = '<input type="text" readonly="true" class="frm_select_box" value="'. esc_attr($code) .'" />';
		        break;
			default:
			    $val = $column_name;
			break;
		}

        echo $val;
    }

    public static function submitbox_actions(){
        global $post;
        if ( $post->post_type != self::$post_type ) {
            return;
        }

        include(FrmAppHelper::plugin_path() .'/pro/classes/views/displays/submitbox_actions.php');
    }

    public static function default_content($content, $post){
        if ( $post->post_type != self::$post_type || ! isset($_GET['copy_id']) ) {
            return $content;
        }

        global $copy_display;
        $copy_display = FrmProDisplay::getOne($_GET['copy_id'], false, false, array('check_post' => true));
        if ( $copy_display ) {
            $content = $copy_display->post_content;
        }

        return $content;
    }

    public static function default_title($title, $post){
        $copy_display = FrmProDisplaysHelper::get_current_view($post);
        if ( $copy_display ) {
            $title = $copy_display->post_title;
        }
        return $title;
    }

    public static function default_excerpt($excerpt, $post){
        $copy_display = FrmProDisplaysHelper::get_current_view($post);
        if ( $copy_display ) {
            $excerpt = $copy_display->post_excerpt;
        }
        return $excerpt;
    }

    public static function add_meta_boxes($post_type) {
        if ( $post_type != self::$post_type ) {
            return;
        }

        add_meta_box('frm_form_disp_type', __('Basic Settings', 'formidable'), 'FrmProDisplaysController::mb_form_disp_type', self::$post_type, 'normal', 'high');
        add_meta_box('frm_dyncontent', __('Content', 'formidable'), 'FrmProDisplaysController::mb_dyncontent', self::$post_type, 'normal', 'high');
        add_meta_box('frm_excerpt', __('Description'), 'FrmProDisplaysController::mb_excerpt', self::$post_type, 'normal', 'high');
        add_meta_box('frm_advanced', __('Settings', 'formidable'), 'FrmProDisplaysController::mb_advanced', self::$post_type, 'advanced');

        add_meta_box('frm_adv_info', __('Customization', 'formidable'), 'FrmProDisplaysController::mb_adv_info', self::$post_type, 'side', 'low');
    }

    public static function save_post($post_id){
        //Verify nonce
        if ( empty($_POST) || ( isset($_POST['frm_save_display']) && ! wp_verify_nonce($_POST['frm_save_display'], 'frm_save_display_nonce') ) || ! isset($_POST['post_type']) || $_POST['post_type'] != self::$post_type || ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) || ! current_user_can('edit_post', $post_id) ) {
            return;
        }

        $post = get_post($post_id);
        if($post->post_status == 'inherit')
            return;

        FrmProDisplay::update( $post_id, $_POST );
        do_action('frm_create_display', $post_id, $_POST);
    }

    public static function before_delete_post($post_id){
        $post = get_post($post_id);
        if ( $post->post_type != self::$post_type ) {
            return;
        }

        global $wpdb;

        do_action('frm_destroy_display', $post_id);

        $query = $wpdb->prepare('SELECT post_ID FROM '. $wpdb->postmeta .' WHERE meta_key=%s AND meta_value=%d', 'frm_display_id', $post_id);
        $used_by = FrmAppHelper::check_cache('used_by_'. $post_id, 'frm_display', $query, 'get_col');

        if(!$used_by)
            return;

        $form_id = get_post_meta($post_id, 'frm_form_id', true);

        $next_display = FrmProDisplay::get_auto_custom_display(compact('form_id'));
        if($next_display and $next_display->ID){
            $wpdb->update($wpdb->postmeta,
                array('meta_value' => $next_display->ID),
                array('meta_key' => 'frm_display_id',  'meta_value' => $post_id)
            );
        }else{
            $wpdb->delete($wpdb->postmeta, array('meta_key' => 'frm_display_id', 'meta_value' => $post_id));
        }
    }

    /* META BOXES */
    public static function mb_dyncontent($post){
        FrmProDisplaysHelper::prepare_duplicate_view($post);

        $editor_args = array();
        if ( $post->frm_no_rt ){
            $editor_args['teeny'] = true;
            $editor_args['tinymce'] = false;
        }

        include(FrmAppHelper::plugin_path() .'/pro/classes/views/displays/mb_dyncontent.php');
    }

    public static function mb_excerpt($post){
        include(FrmAppHelper::plugin_path() .'/pro/classes/views/displays/mb_excerpt.php');

        //add form nav via javascript
        $form = get_post_meta($post->ID, 'frm_form_id', true);
        if($form){
            echo '<div id="frm_nav_container" style="display:none;margin-top:-10px">';
            FrmAppController::get_form_nav($form, true, 'hide');
			echo '<div class="clear"></div>';
            echo '</div>';
        }
    }

    public static function mb_form_disp_type($post){
        FrmProDisplaysHelper::prepare_duplicate_view($post);
        include(FrmAppHelper::plugin_path() .'/pro/classes/views/displays/mb_form_disp_type.php');
    }

    public static function mb_advanced($post){
        FrmProDisplaysHelper::prepare_duplicate_view($post);
        include(FrmAppHelper::plugin_path() .'/pro/classes/views/displays/mb_advanced.php');
    }

    public static function mb_adv_info($post){
        FrmProDisplaysHelper::prepare_duplicate_view($post);
        FrmFormsController::mb_tags_box($post->frm_form_id);
    }

    public static function get_tags_box(){
        FrmFormsController::mb_tags_box($_POST['form_id']);
        die();
    }

    /* FRONT END */

    public static function get_content($content){
        global $post;
        if ( ! $post ) {
            return $content;
        }

        $entry_id = false;
        $filter = apply_filters('frm_filter_auto_content', true);

        if ( $post->post_type == self::$post_type && in_the_loop() ) {
            global $frm_displayed;
            if(!$frm_displayed)
                $frm_displayed = array();

            if(in_array($post->ID, $frm_displayed))
                return $content;

            $frm_displayed[] = $post->ID;

            return self::get_display_data($post, $content, false, compact('filter'));
        }

        if ( is_singular() && post_password_required() ) {
            return $content;
        }

        $display_id = get_post_meta($post->ID, 'frm_display_id', true);

        if ( ! $display_id || ( ! is_single() && ! is_page() ) ) {
            return $content;
        }

        $display = FrmProDisplay::getOne($display_id);
        if ( ! $display ) {
            return $content;
        }

        global $frm_displayed, $frm_display_position;

        if ( $post->post_type != self::$post_type ) {
            $display = FrmProDisplaysHelper::setup_edit_vars($display, false);
        }

        if ( ! isset($display->frm_insert_pos) ) {
            $display->frm_insert_pos = 1;
        }

        if ( ! $frm_displayed ) {
            $frm_displayed = array();
        }

        if ( ! $frm_display_position ) {
            $frm_display_position = array();
        }

        if ( ! isset($frm_display_position[$display->ID]) ) {
            $frm_display_position[$display->ID] = 0;
        }

        $frm_display_position[$display->ID]++;

        //make sure this isn't loaded multiple times but still works with themes and plugins that call the_content multiple times
        if ( ! in_the_loop() || in_array($display->ID, (array) $frm_displayed) || $frm_display_position[$display->ID] < (int) $display->frm_insert_pos ) {
            return $content;
        }

        global $wpdb;

        //get the entry linked to this post
        if ( ( is_single() || is_page() ) && $post->post_type != self::$post_type && ( $display->frm_insert_loc == 'none' || ( $display->frm_insert_loc != 'none' && $display->frm_post_id != $post->ID ) ) ) {

            $query = $wpdb->prepare('SELECT id, item_key FROM '. $wpdb->prefix .'frm_items WHERE post_id=%d', $post->ID);
            $entry = FrmAppHelper::check_cache('post_id_'. $post->ID, 'frm_entry', $query, 'get_row');

            if(!$entry)
                return $content;

            $entry_id = $entry->id;

            if ( in_array($display->frm_show_count, array('dynamic', 'calendar')) && $display->frm_type == 'display_key' ) {
                $entry_id = $entry->item_key;
            }
        }

        $frm_displayed[] = $display->ID;
        $content = self::get_display_data($display, $content, $entry_id, array(
            'filter' => $filter, 'auto_id' => $entry_id,
        ) );

        return $content;
    }

	public static function get_order_row(){
        self::add_order_row($_POST['order_key'], $_POST['form_id']);
        die();
    }

    public static function add_order_row($order_key='', $form_id='', $order_by='', $order=''){
        $order_key = (int) $order_key;
        require(FrmAppHelper::plugin_path() .'/pro/classes/views/displays/order_row.php');
    }

    public static function get_where_row(){
        self::add_where_row($_POST['where_key'], $_POST['form_id']);
        die();
    }

    public static function add_where_row($where_key='', $form_id='', $where_field='', $where_is='', $where_val=''){
        $where_key = (int) $where_key;
        require(FrmAppHelper::plugin_path() .'/pro/classes/views/displays/where_row.php');
    }

    public static function get_where_options(){
        self::add_where_options($_POST['field_id'],$_POST['where_key']);
        die();
    }

    public static function add_where_options($field_id, $where_key, $where_val=''){
        if ( is_numeric($field_id) ) {
            $field = FrmField::getOne($field_id);
        }

        require(FrmAppHelper::plugin_path() .'/pro/classes/views/displays/where_options.php');
    }

    public static function calendar_header($content, $display, $show = 'one'){
        if ( $display->frm_show_count != 'calendar' || $show == 'one' ) {
            return $content;
        }

        global $frm_vars, $wp_locale;
        $frm_vars['load_css'] = true;

        $year = FrmAppHelper::get_param('frmcal-year', date_i18n('Y')); //4 digit year
        $month = FrmAppHelper::get_param('frmcal-month', date_i18n('m')); //Numeric month with leading zeros

        $month_names = $wp_locale->month;

        $this_time = strtotime($year .'-'. $month .'-01');
        $prev_month = date('m', strtotime('-1 month', $this_time));
        $prev_year = date('Y', strtotime('-1 month', $this_time));

        $next_month = date('m', strtotime('+1 month', $this_time));
        $next_year = date('Y', strtotime('+1 month', $this_time));

        ob_start();
        include(FrmAppHelper::plugin_path() .'/pro/classes/views/displays/calendar-header.php');
        $content .= ob_get_contents();
        ob_end_clean();
        return $content;
    }

    /*
    * @return string
    */
    public static function build_calendar($new_content, $entries, $shortcodes, $display, $show = 'one'){
        if ( ! $display || $display->frm_show_count != 'calendar' || $show == 'one') {
            return $new_content;
        }

        global $wp_locale;

        $current_year = date_i18n('Y');
        $current_month = date_i18n('m');

        $year = FrmAppHelper::get_param('frmcal-year', date('Y')); //4 digit year
        $month = FrmAppHelper::get_param('frmcal-month', $current_month); //Numeric month with leading zeros

        $timestamp = mktime(0, 0, 0, $month, 1, $year);
        $maxday = date('t', $timestamp); //Number of days in the given month
        $this_month = getdate($timestamp);
        $startday = $this_month['wday'];
        unset($this_month);

        // week_begins = 0 stands for Sunday
    	$week_begins = apply_filters('frm_cal_week_begins', intval(get_option('start_of_week')), $display);
    	if ( $week_begins > $startday ) {
            $startday = $startday + 7;
        }

        $week_ends = 6 + (int) $week_begins;
        if ( $week_ends > 6 ) {
            $week_ends = (int) $week_ends - 7;
        }

        $efield = $field = false;
        if ( is_numeric($display->frm_date_field_id) ) {
            $field = FrmField::getOne($display->frm_date_field_id);
        }

        if ( is_numeric($display->frm_edate_field_id) ) {
            $efield = FrmField::getOne($display->frm_edate_field_id);
        }

        $daily_entries = array();
        foreach ( $entries as $entry ) {
            self::calendar_daily_entries($entry, $display, compact('startday', 'maxday', 'year', 'month', 'field', 'efield'), $daily_entries);
        }

        $day_names = FrmProAppHelper::reset_keys($wp_locale->weekday_abbrev); //switch keys to order

        if ( $week_begins ) {
            for ( $i = $week_begins; $i < ( $week_begins + 7 ); $i++ ) {
                if ( ! isset($day_names[$i]) ) {
                    $day_names[$i] = $day_names[$i - 7];
                }
            }
            unset($i);
        }

        if ( $current_year == $year && $current_month == $month ) {
            $today = date_i18n('j');
        }

        $used_entries = array();

        ob_start();
        include(FrmAppHelper::plugin_path() .'/pro/classes/views/displays/calendar.php');
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    private static function calendar_daily_entries($entry, $display, $args, array &$daily_entries) {
        $i18n = false;

        if ( is_numeric($display->frm_date_field_id) ) {
            $date = FrmAppHelper::get_meta_value($display->frm_date_field_id, $entry);

            if ( $entry->post_id && !$date && $args['field'] &&
                isset($args['field']->field_options['post_field']) && $args['field']->field_options['post_field'] ) {

                $date = FrmProEntryMetaHelper::get_post_value($entry->post_id, $args['field']->field_options['post_field'], $args['field']->field_options['custom_field'], array(
                    'form_id' => $display->frm_form_id, 'type' => $args['field']->type,
                    'field' => $args['field']
                ) );

            }
        } else if ( $display->frm_date_field_id == 'updated_at' ) {
            $date = $entry->updated_at;
            $i18n = true;
        } else {
            $date = $entry->created_at;
            $i18n = true;
        }

        if ( empty($date) ) {
            return;
        }

        if ( $i18n ) {
            $date = get_date_from_gmt($date);
            $date = date_i18n('Y-m-d', strtotime($date));
        } else {
            $date = date('Y-m-d', strtotime($date));
        }

        unset($i18n);
        $dates = array($date);

        if ( ! empty($display->frm_edate_field_id) ) {
            if ( is_numeric($display->frm_edate_field_id) && $args['efield'] ) {
                $edate = FrmProEntryMetaHelper::get_post_or_meta_value($entry, $args['efield']);

                if ( $args['efield'] && $args['efield']->type == 'number' && is_numeric($edate) ) {
                    $edate = date('Y-m-d', strtotime('+'. ($edate - 1) .' days', strtotime($date)));
                }
            } else if ( $display->frm_edate_field_id == 'updated_at' ) {
                $edate = get_date_from_gmt($entry->updated_at);
                $edate = date_i18n('Y-m-d', strtotime($edate));
            } else {
                $edate = get_date_from_gmt($entry->created_at);
                $edate = date_i18n('Y-m-d', strtotime($edate));
            }

            if ( $edate && ! empty($edate) ) {
                $from_date = strtotime($date);
                $to_date = strtotime($edate);

                if ( ! empty($from_date) && $from_date < $to_date ) {
                    for ( $current_ts = $from_date; $current_ts <= $to_date; $current_ts += (60*60*24) ) {
                        $dates[] = date('Y-m-d', $current_ts);
                    }
                    unset($current_ts);
                }

                unset($from_date, $to_date);
            }
            unset($edate);
        }
        unset($date);

        self::get_repeating_dates($entry, $display, $args, $dates);

        $dates = apply_filters('frm_show_entry_dates', $dates, $entry);

        for ( $i=0; $i < ( $args['maxday'] + $args['startday'] ); $i++ ) {
            $day = $i - $args['startday'] + 1;

            if ( in_array(date('Y-m-d', strtotime($args['year'] .'-'. $args['month'] .'-'. $day)), $dates) ) {
                $daily_entries[$i][] = $entry;
            }

            unset($day);
        }
    }

    private static function get_repeating_dates($entry, $display, $args, array &$dates) {
        if ( ! is_numeric($display->frm_repeat_event_field_id) ) {
            return;
        }

        //Get meta values for repeat field and end repeat field
        if ( isset($entry->metas[$display->frm_repeat_event_field_id]) ) {
            $repeat_period = $entry->metas[$display->frm_repeat_event_field_id];
        } else {
            $repeat_field = FrmField::getOne($display->frm_repeat_event_field_id);
            $repeat_period = FrmProEntryMetaHelper::get_post_or_meta_value($entry->id, $repeat_field);
            unset($repeat_field);
        }

        if ( isset($entry->metas[$display->frm_repeat_edate_field_id]) ) {
            $stop_repeat = $entry->metas[$display->frm_repeat_edate_field_id];
        } else {
            $stop_field = FrmField::getOne($display->frm_repeat_edate_field_id);
            $stop_repeat = FrmProEntryMetaHelper::get_post_or_meta_value($entry->id, $stop_field);
            unset($stop_field);
        }

		//If site is not set to English, convert day(s), week(s), month(s), and year(s) (in repeat_period string) to English
		//Check for a few common repeat periods like daily, weekly, monthly, and yearly as well
		$t_strings = array(__('day', 'formidable'), __('days', 'formidable'), __('daily', 'formidable'),__('week', 'formidable'), __('weeks', 'formidable'), __('weekly', 'formidable'), __('month', 'formidable'), __('months', 'formidable'), __('monthly', 'formidable'), __('year', 'formidable'), __('years', 'formidable'), __('yearly', 'formidable'));
		$t_strings = apply_filters('frm_recurring_strings', $t_strings, $display);
		$e_strings = array('day', 'days', '1 day', 'week', 'weeks', '1 week', 'month', 'months', '1 month', 'year', 'years', '1 year');
		if ( $t_strings != $e_strings ) {
			$repeat_period = str_ireplace($t_strings, $e_strings, $repeat_period);
		}
		unset($t_strings, $e_strings);

		//Switch [frmcal-date] for current calendar date (for use in "Third Wednesday of [frmcal-date]")
		$repeat_period = str_replace('[frmcal-date]', $args['year'] . '-' . $args['month'] . '-01', $repeat_period);

		//Filter for repeat_period
		$repeat_period = apply_filters('frm_repeat_period', $repeat_period, $display);

		//If repeat period is set and is valid
		if ( empty($repeat_period) || ! is_numeric(strtotime($repeat_period)) ) {
		    return;
		}

		//Set up end date to minimize dates array - allow for no end repeat field set, nothing selected for end, or any date

		if ( ! empty($stop_repeat) ) {
		    //If field is selected for recurring end date and the date is not empty
			$maybe_stop_repeat = strtotime($stop_repeat);
		}

		//Repeat until next viewable month
		$cal_date = $args['year'] . '-' . $args['month'] . '-01';
		$stop_repeat = strtotime('+1 month', strtotime($cal_date));

		//If the repeat should end before $stop_repeat (+1 month), use $maybe_stop_repeat
		if ( isset($maybe_stop_repeat) && $maybe_stop_repeat < $stop_repeat ) {
		    $stop_repeat = $maybe_stop_repeat;
		    unset($maybe_stop_repeat);
		}

		$temp_dates = array();

		foreach ( $dates as $d ) {
			$last_i = 0;
			for ($i = strtotime($d); $i <= $stop_repeat; $i = strtotime($repeat_period, $i)) {
				//Break endless loop
				if ( $i == $last_i ) {
					break;
				}
				$last_i = $i;

				//Add to dates array
				$temp_dates[] = date('Y-m-d', $i);
			}
			unset($last_i, $d);
		}
		$dates = $temp_dates;
    }

    public static function calendar_footer($content, $display, $show='one'){
        if($display->frm_show_count != 'calendar' or $show == 'one') return $content;

        ob_start();
        include(FrmAppHelper::plugin_path() .'/pro/classes/views/displays/calendar-footer.php');
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    public static function get_date_field_select(){
		if ( is_numeric($_POST['form_id']) ) {
		    $post = new stdClass();
		    $post->frm_form_id = (int) $_POST['form_id'];
		    $post->frm_edate_field_id = $post->frm_date_field_id = '';
		    $post->frm_repeat_event_field_id = $post->frm_repeat_edate_field_id = '';
		    include(FrmAppHelper::plugin_path() .'/pro/classes/views/displays/_calendar_options.php');
		}

        die();
    }

    /* Shortcodes */
    public static function get_shortcode($atts){
        $defaults = array(
            'id' => '', 'entry_id' => '', 'filter' => false,
            'user_id' => false, 'limit' => '', 'page_size' => '',
            'order_by' => '', 'order' => '', 'get' => '', 'get_value' => '',
            'drafts' => false,
        );

        $sc_atts = shortcode_atts($defaults, $atts);
        $atts = array_merge($atts, $sc_atts);

        $display = FrmProDisplay::getOne($atts['id'], false, true);
        $user_id = FrmAppHelper::get_user_id_param($atts['user_id']);

        if ( ! empty($atts['get']) ) {
            $_GET[$atts['get']] = urlencode($atts['get_value']);
        }

        $get_atts = $atts;
        foreach ( $defaults as $unset => $val ) {
            unset($get_atts[$unset], $unset, $val);
        }

        foreach ( $get_atts as $att => $val ) {
            $_GET[$att] = urlencode($val);
            unset($att, $val);
        }

        if ( ! $display ) {
            return __('There are no views with that ID', 'formidable');
        }

        return self::get_display_data($display, '', $atts['entry_id'], array(
            'filter' => $atts['filter'], 'user_id' => $user_id,
            'limit' => $atts['limit'], 'page_size' => $atts['page_size'],
            'order_by' => $atts['order_by'], 'order' => $atts['order'],
            'drafts' => $atts['drafts'],
        ) );
    }

    public static function custom_display($id){
        if ($display = FrmProDisplay::getOne($id, false, false, array('check_post' => true)))
            return self::get_display_data($display);
    }

    public static function get_display_data($display, $content='', $entry_id=false, $extra_atts=array()) {
        add_action('frm_load_view_hooks', 'FrmProDisplaysController::trigger_load_view_hooks');
        FrmAppHelper::trigger_hook_load( 'view', $display );

        global $frm_vars, $post;

        $frm_vars['forms_loaded'][] = true;

        if ( ! isset($display->frm_empty_msg) ) {
            $display = FrmProDisplaysHelper::setup_edit_vars($display, false);
        }

        if(!isset($display->frm_form_id) or empty($display->frm_form_id))
            return $content;

        // check if entry needs to be deleted before loading entries
        if ( FrmAppHelper::get_param('frm_action') == 'destroy' && isset( $_GET['entry'] ) ) {
            $deleted = FrmProEntriesController::ajax_destroy( $display->frm_form_id, false, false );
            if ( !empty($deleted) ) {
                $message = '<div class="'. FrmFormsHelper::get_form_style_class($display->frm_form_id) .'"><div class="frm_message">'. $deleted .'</div></div>';
            }
            unset( $_GET['entry'] );
        }

        //for backwards compatability
        $display->id = $display->frm_old_id;
        $display->display_key = $display->post_name;

        $defaults = array(
        	'filter' => false, 'user_id' => '', 'limit' => '',
        	'page_size' => '', 'order_by' => '', 'order' => '',
        	'drafts' => false, 'auto_id' => '',
        );

        $extra_atts = wp_parse_args( $extra_atts, $defaults );
        extract($extra_atts);

        //if (FrmProAppHelper::rewriting_on() && $frmpro_settings->permalinks )
        //    self::parse_pretty_entry_url();

        if ( $display->frm_show_count == 'one' && is_numeric($display->frm_entry_id) && $display->frm_entry_id > 0 && ! $entry_id ) {
            $entry_id = $display->frm_entry_id;
        }

        $entry = false;
        $show = 'all';

        global $wpdb;

        $where = $wpdb->prepare('it.form_id=%d', $display->frm_form_id);

        if (in_array($display->frm_show_count, array('dynamic', 'calendar', 'one'))){
			$one_param = (isset($_GET['entry'])) ? $_GET['entry'] : $extra_atts['auto_id'];
			$get_param = (isset($_GET[$display->frm_param])) ? $_GET[$display->frm_param] : (($display->frm_show_count == 'one') ? $one_param : $extra_atts['auto_id']);
			unset($one_param);

            if ($get_param){
                if(($display->frm_type == 'id' or $display->frm_show_count == 'one') and is_numeric($get_param))
                    $where .= $wpdb->prepare(' AND it.id=%d', $get_param);
                else
                    $where .= $wpdb->prepare(' AND it.item_key=%s', $get_param);

                $entry = FrmEntry::getAll($where, '', 1, 0);
                if($entry)
                    $entry = reset($entry);

                if($entry and $entry->post_id){
                    //redirect to single post page if this entry is a post
                    if(in_the_loop() and $display->frm_show_count != 'one' and !is_single($entry->post_id) and $post->ID != $entry->post_id){
                        $this_post = get_post($entry->post_id);
                        if(in_array($this_post->post_status, array('publish', 'private')))
                            die(FrmAppHelper::js_redirect(get_permalink($entry->post_id)));
                    }
                }
            }
            unset($get_param);
        }

        if($entry and in_array($display->frm_show_count, array('dynamic', 'calendar'))){
            $new_content = $display->frm_dyncontent;
            $show = 'one';
        }else{
            $new_content = $display->post_content;
        }

        $show = ($display->frm_show_count == 'one') ? 'one' : $show;
        $shortcodes = FrmProDisplaysHelper::get_shortcodes($new_content, $display->frm_form_id);

        //don't let page size and limit override single entry displays
        if($display->frm_show_count == 'one')
            $display->frm_page_size = $display->frm_limit = '';

        //don't keep current content if post type is frm_display
        if ( $post && $post->post_type == self::$post_type ) {
            $display->frm_insert_loc = '';
        }

        $pagination = '';
        $is_draft = empty($extra_atts['drafts']) ? 0 : 1;

        $form_query = $wpdb->prepare("SELECT id, post_id FROM {$wpdb->prefix}frm_items WHERE form_id=%d and post_id>%d", $display->frm_form_id, 1);

        if ( $extra_atts['drafts'] != 'both' ) {
		    $form_query .= $wpdb->prepare(' AND is_draft=%d', $is_draft);
		}

		$cache_key = 'form_posts_'. sanitize_title_with_dashes($form_query);
        if ( $entry && $entry->form_id == $display->frm_form_id ) {
            $form_query .= $wpdb->prepare(' AND id=%d', $entry->id);
            $cache_key .= '-id='. $entry->id;
        }

        $form_posts = FrmAppHelper::check_cache($cache_key, 'frm_entry', $form_query, 'get_results');
    	unset($form_query, $cache_key);

        if ( $entry && $entry->form_id == $display->frm_form_id ) {
            $entry_ids = array($entry->id);
        } else if ( ( isset( $display->frm_where ) && !empty( $display->frm_where ) && ( !$entry || !$post || empty( $extra_atts['auto_id'] ) ) ) || isset($_GET['frm_search']) ) {
            //Only get $entry_ids if filters are set or if frm_search parameter is set
            $entry_query = $wpdb->prepare('SELECT id FROM '. $wpdb->prefix .'frm_items WHERE form_id=%d', $display->frm_form_id);
            $cache_key = 'entry_id_form_'. $display->frm_form_id;
            if ( $extra_atts['drafts'] != 'both' ) {
                $cache_key .= '_draft_'. $is_draft;
                $entry_query .= $wpdb->prepare(' AND is_draft=%d', $is_draft);
            }

            $entry_ids = FrmAppHelper::check_cache($cache_key, 'frm_entry', $entry_query, 'get_col');
            unset($cache_key, $entry_query);
        }

		$empty_msg = ( isset($display->frm_empty_msg) && ! empty($display->frm_empty_msg) ) ? '<div class="frm_no_entries">' . FrmProFieldsHelper::get_default_value($display->frm_empty_msg, false) . '</div>' : '';

        if ( isset( $message ) ) {
            // if an entry was deleted above, show a message
            $empty_msg = $message . $empty_msg;
        }

        $after_where = false;

        $user_id = $extra_atts['user_id'];
        if ( ! empty($user_id) ) {
            $user_id = FrmAppHelper::get_user_id_param($user_id);
            $uid_used = false;
        }

		if ( isset( $display->frm_where ) && !empty( $display->frm_where ) && ( !$entry || !$post || empty( $extra_atts['auto_id'] ) ) ) {
                $display->frm_where = apply_filters('frm_custom_where_opt', $display->frm_where, array('display' => $display, 'entry' => $entry));
                $continue = false;
                foreach($display->frm_where as $where_key => $where_opt){
                    $where_val = isset($display->frm_where_val[$where_key]) ? $display->frm_where_val[$where_key] : '';

                    if (preg_match("/\[(get|get-(.?))\b(.*?)(?:(\/))?\]/s", $where_val)){
                        $where_val = FrmProFieldsHelper::get_default_value($where_val, false, true, true);
                        //if this param doesn't exist, then don't include it
                        if($where_val == '') {
                            if(!$after_where)
                                $continue = true;

                            continue;
                        }
                    }else{
                        $where_val = FrmProFieldsHelper::get_default_value($where_val, false, true, true);
                    }

                    $continue = false;

                    if($where_val == 'current_user'){
                        if($user_id and is_numeric($user_id)){
                            $where_val = $user_id;
                            $uid_used = true;
                        }else{
                            $where_val = get_current_user_id();
                        }
                    }

                    $where_val = do_shortcode($where_val);

                    if ( in_array($where_opt, array('id', 'item_key', 'post_id')) && !is_array($where_val) && strpos($where_val, ',') ) {
                        $where_val = explode(',', $where_val);
                    }

                    if(is_array($where_val) and !empty($where_val)){
                        $new_where = '(';
                        if(strpos($display->frm_where_is[$where_key], 'LIKE') !== false){
                            foreach($where_val as $w){
                                if($new_where != '(')
                                    $new_where .= ',';
                                $new_where .= $wpdb->prepare('%s', '%'. FrmAppHelper::esc_like($w) . '%');
                                unset($w);
                            }
                        }else{
                            foreach($where_val as $w){
                                if ( $new_where != '(' ) {
                                    $new_where .= ',';
                                }
                                $new_where .= $wpdb->prepare('%s', $w);
                                unset($w);
                            }
                        }
                        $new_where .= ')';
                        $where_val = $new_where;
                        unset($new_where);

                        if ( strpos($display->frm_where_is[$where_key], '!') === false && strpos($display->frm_where_is[$where_key], 'not') === false ) {
                            $display->frm_where_is[$where_key] = ' in ';
                        } else {
                            $display->frm_where_is[$where_key] = ' not in ';
                        }
                    }

                    if(is_numeric($where_opt)){
                        $filter_opts = apply_filters('frm_display_filter_opt', array(
                            'where_opt' => $where_opt, 'where_is' => $display->frm_where_is[$where_key],
                            'where_val' => $where_val, 'form_id' => $display->frm_form_id, 'form_posts' => $form_posts,
                            'after_where' => $after_where, 'display' => $display, 'drafts' => $is_draft
						));
						$entry_ids = FrmProAppHelper::filter_where($entry_ids, $filter_opts);

                        unset($filter_opts);
                        $after_where = true;
                        $continue = false;

                        if(empty($entry_ids))
                            break;
                    }else if($where_opt == 'created_at' or $where_opt == 'updated_at'){
                        if ( $where_val == 'NOW' ) {
                            $where_val = current_time('mysql', 1);
                        }

                        if ( strpos($display->frm_where_is[$where_key], 'LIKE') === false ) {
                            $where_val = date('Y-m-d H:i:s', strtotime($where_val));
                            if ( date('H:i:s', strtotime($where_val) ) == '00:00:00' ) {
                                // if there is no time, then adjust it for the WP timezone setting
                                $where_val = get_date_from_gmt($where_val);
                            }
                        }

                        $where .= $wpdb->prepare(' and it.'. $where_opt .' '. $display->frm_where_is[$where_key] .'%s', '');
                        if ( strpos($display->frm_where_is[$where_key], 'in') ) {
                            $where .= ' '. $where_val;
                        } else if ( strpos($display->frm_where_is[$where_key], 'LIKE') !== false ) {
                            $where .= $wpdb->prepare(' %s', '%'. FrmAppHelper::esc_like($where_val) .'%');
                        } else {
                            $where .= $wpdb->prepare(' %s', $where_val);
                        }

                        $continue = true;
                    } else if ( in_array($where_opt, array('id', 'item_key', 'post_id')) ) {
                        $where .= " and it.{$where_opt} ". $display->frm_where_is[$where_key];
                        if ( strpos($display->frm_where_is[$where_key], 'in') ) {
                            $where .= " $where_val";
                        } else {
                            $where .= $wpdb->prepare(" %s", $where_val);
                        }

                        $continue = true;
                    }

                }

                if(!$continue and empty($entry_ids)){
                    if ($display->frm_insert_loc == 'after'){
                        $content .=  $empty_msg;
                    }else if ($display->frm_insert_loc == 'before'){
                        $content = $empty_msg . $content;
                    }else{
                        if ($filter)
                            $empty_msg = apply_filters('the_content', $empty_msg);

                        if ( $post->post_type == self::$post_type && in_the_loop() ) {
                            $content = '';
                        }

                        $content .= $empty_msg;
                    }

                    return $content;
                }
            }

            if ( $user_id && is_numeric($user_id) && !$uid_used ) {
                $where .= $wpdb->prepare(" AND it.user_id=%d", $user_id);
            }

            $s = FrmAppHelper::get_param('frm_search', false);
            if ($s){
                $new_ids = FrmProEntriesHelper::get_search_ids( $s, $display->frm_form_id, array( 'is_draft' => $extra_atts['drafts'] ) );

                if($after_where and isset($entry_ids) and !empty($entry_ids))
                    $entry_ids = array_intersect($new_ids, $entry_ids);
                else
                    $entry_ids = $new_ids;

                if(empty($entry_ids)){
                    if ( $post->post_type == self::$post_type && in_the_loop() ) {
                        $content = '';
                    }

                    return $content . ' '. $empty_msg;
                }
            }

            if (isset($entry_ids) && !empty($entry_ids) ) {
                $where .= ' and it.id in ('. implode(',', array_filter($entry_ids, 'is_numeric')) .')';
            }

			if ( $entry_id ) {
				$entry_id_array = explode(',', $entry_id);

				//Get IDs (if there are any)
				$numeric_entry_ids = array_filter($entry_id_array, 'is_numeric');

				//If there are entry keys, use esc_sql
				if ( empty($numeric_entry_ids) ) {
					$entry_id_array = array_filter($entry_id_array, 'esc_sql');
				}

				$where .= ( !empty($numeric_entry_ids) ? " and it.id in ('". implode ("','", $numeric_entry_ids) ."')" : " and it.item_key in ('". implode ("','", $entry_id_array) ."')" );
			}

            if ( $extra_atts['drafts'] != 'both' ) {
    		    $where .= $wpdb->prepare(' AND is_draft=%d', $is_draft);
    		}
    		unset($is_draft);

            if($show == 'one'){
                $limit = ' LIMIT 1';
            }else if (isset($_GET['frm_cat']) and isset($_GET['frm_cat_id'])){
                //Get fields with specified field value 'frm_cat' = field key/id, 'frm_cat_id' = order position of selected option
                if ($cat_field = FrmField::getOne($_GET['frm_cat'])){
                    $categories = maybe_unserialize($cat_field->options);

                    if (isset($categories[$_GET['frm_cat_id']])){
                        $cat_entry_ids = FrmEntryMeta::getEntryIds(array('meta_value' => $categories[$_GET['frm_cat_id']], 'fi.field_key' => $_GET['frm_cat']));
                        if ($cat_entry_ids)
                            $where .= " and it.id in (". implode(',', $cat_entry_ids) .")";
                        else
                            $where .= " and it.id=0";
                    }
                }
            }

			if ( ! empty($limit) && is_numeric($limit) ) {
                $display->frm_limit = (int) $limit;
            }

			if ( is_numeric($display->frm_limit) ) {
                $num_limit = (int) $display->frm_limit;
                $limit = ' LIMIT '. $display->frm_limit;
			}

			if (!empty($order_by)){
            	$display->frm_order_by = explode(',', $order_by);
			}

            if (!empty($order)){
                $display->frm_order = explode(',', $order);
			}
			unset($order);


            if ( !empty($page_size) && is_numeric($page_size) ) {
                $display->frm_page_size = (int) $page_size;
            }

            // if limit is lower than page size, ignore the page size
            if ( isset($num_limit) && $display->frm_page_size > $num_limit ) {
                $display->frm_page_size = '';
            }

            if ( isset($display->frm_page_size) && is_numeric($display->frm_page_size) ) {
                $page_param = ( $_GET && isset($_GET['frm-page-'. $display->ID]) ) ? 'frm-page-'. $display->ID : 'frm-page';
                $current_page = (int) FrmAppHelper::get_param($page_param, 1);
                $record_where = ($where == $wpdb->prepare('it.form_id=%d', $display->frm_form_id)) ? $display->frm_form_id : $where;
                $record_count = FrmEntry::getRecordCount($record_where);
                if ( isset($num_limit) && ( $record_count > (int) $num_limit ) ) {
                    $record_count = (int) $num_limit;
                }

                $page_count = FrmEntry::getPageCount($display->frm_page_size, $record_count);

				//Get a page of entries
				$entries = FrmProEntry::get_view_page($current_page, $display->frm_page_size, $where, array(
					'order_by_array' => $display->frm_order_by, 'order_array' => $display->frm_order,
					'posts' => $form_posts, 'display' => $display,
				));

                $page_last_record = FrmAppHelper::getLastRecordNum($record_count, $current_page, $display->frm_page_size);
                $page_first_record = FrmAppHelper::getFirstRecordNum($record_count, $current_page, $display->frm_page_size);
                if($page_count > 1){
                    $page_param = 'frm-page-'. $display->ID;
                    $pagination = FrmAppHelper::get_file_contents(FrmAppHelper::plugin_path() .'/pro/classes/views/displays/pagination.php', compact('current_page', 'record_count', 'page_count', 'page_last_record', 'page_first_record', 'page_param'));
                }
            }else{
				//Get all entries
				$entries = FrmProEntry::get_view_results($where, array(
					'order_by_array' => $display->frm_order_by, 'order_array' => $display->frm_order,
					'limit' => $limit, 'posts' => $form_posts, 'display' => $display,
				));
            }

            $total_count = count($entries);
            $sc_atts = array();
            if(isset($record_count))
                $sc_atts['record_count'] = $record_count;
            else
                $sc_atts['record_count'] = $total_count;

            $display_content = '';
            if ( isset( $message ) ) {
                // if an entry was deleted above, show a message
                $display_content .= $message;
            }

            if($show == 'all')
                $display_content .= isset($display->frm_before_content) ? $display->frm_before_content : '';

            if ( !isset($entry_ids) || empty($entry_ids) ) {
                $entry_ids = array_keys($entries);
            }

            add_filter('frm_before_display_content', 'FrmProDisplaysController::calendar_header', 10, 3);
            add_filter('frm_before_display_content', 'FrmProDisplaysController::filter_after_content', 10, 4);

            $display_content = apply_filters('frm_before_display_content', $display_content, $display, $show, array('total_count' => $total_count, 'record_count' => $sc_atts['record_count'], 'entry_ids' => $entry_ids));

            add_filter('frm_display_entries_content', 'FrmProDisplaysController::build_calendar', 10, 5);
            $filtered_content = apply_filters('frm_display_entries_content', $new_content, $entries, $shortcodes, $display, $show, $sc_atts);

            if($filtered_content != $new_content){
                $display_content .= $filtered_content;
            }else{
                $odd = 'odd';
                $count = 0;
                if(!empty($entries)){
                    foreach ($entries as $entry){
                        $count++; //TODO: use the count with conditionals
                        $display_content .= apply_filters('frm_display_entry_content', $new_content, $entry, $shortcodes, $display, $show, $odd, array('count' => $count, 'total_count' => $total_count, 'record_count' => $sc_atts['record_count'], 'pagination' => $pagination, 'entry_ids' => $entry_ids));
                        $odd = ($odd == 'odd') ? 'even' : 'odd';
                        unset($entry);
                    }
                    unset($count);
                }else{
                    if ( $post->post_type == self::$post_type && in_the_loop() ) {
                        $display_content = '';
                    }

                    if ( !isset($message) || FrmAppHelper::get_param('frm_action') != 'destroy' ) {
                        $display_content .= $empty_msg;
                    }
                }
            }

        if ( isset( $message ) ) {
            unset( $message );
        }

        if ( $show == 'all' && isset($display->frm_after_content) ) {
            add_filter('frm_after_content', 'FrmProDisplaysController::filter_after_content', 10, 4);
            $display_content .= apply_filters('frm_after_content', $display->frm_after_content, $display, $show, array('total_count' => $total_count, 'record_count' => $sc_atts['record_count'], 'entry_ids' => $entry_ids));
        }

        if(!isset($sc_atts))
            $sc_atts = array('record_count' => 0);

        if(!isset($total_count))
            $total_count = 0;

        $pagination = self::calendar_footer($pagination, $display, $show);
        $display_content .= apply_filters('frm_after_display_content', $pagination, $display, $show, array('total_count' => $total_count, 'record_count' => $sc_atts['record_count'], 'entry_ids' => $entry_ids ));
        unset($sc_atts);
        $display_content = FrmProFieldsHelper::get_default_value($display_content, false, true, true);

        if ($display->frm_insert_loc == 'after'){
            $content .= $display_content;
        }else if ($display->frm_insert_loc == 'before'){
            $content = $display_content . $content;
        }else{
            if ($filter)
                $display_content = apply_filters('the_content', $display_content);
            $content = $display_content;
        }

        // load the styling for css classes and pagination
        FrmStylesController::enqueue_style();

        return $content;
    }

    public static function parse_pretty_entry_url(){
        global $wpdb, $post;

        $post_url = get_permalink($post->ID);
        $request_uri = FrmProAppHelper::current_url();

        $match_str = '#^'.$post_url.'(.*?)([\?/].*?)?$#';

        if(preg_match($match_str, $request_uri, $match_val)){
            // match short slugs (most common)
            if(isset($match_val[1]) and !empty($match_val[1]) and FrmEntry::exists($match_val[1])){
                // Artificially set the GET variable
                $_GET['entry'] = $match_val[1];
            }
        }
    }

    public static function get_pagination_file($filename, $atts){
        _deprecated_function( __FUNCTION__, '2.0', 'FrmAppHelper::get_file_contents' );
        return FrmAppHelper::get_file_contents($filename, $atts);
    }

    public static function filter_after_content($content, $display, $show, $atts){
        $content = str_replace('[entry_count]', $atts['record_count'], $content);
        return $content;
    }

    public static function get_post_content() {
        $id = (int) $_POST['id'];

        $display = FrmProDisplay::getOne( $id, false, true );
        if ( 'one' == $display->frm_show_count ) {
            echo $display->post_content;
        } else {
            echo $display->frm_dyncontent;
        }

        die();
    }
}
