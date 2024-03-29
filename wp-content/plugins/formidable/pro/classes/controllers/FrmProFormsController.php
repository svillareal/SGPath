<?php

class FrmProFormsController{

    public static function add_form_options($values){
        global $frm_vars;

        $post_types = FrmProAppHelper::get_custom_post_types();

        require(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-forms/add_form_options.php');
    }

    public static function add_form_ajax_options($values){
        global $frm_vars;

        $post_types = FrmProAppHelper::get_custom_post_types();

        require(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-forms/add_form_ajax_options.php');
    }

    /**
     * Remove the noallow class on pro fields
     * @return string
     */
    public static function noallow_class() {
        return '';
    }

    public static function add_form_button_options($values){
        global $frm_vars;

        $page_field = FrmProFormsHelper::has_field('break', $values['id'], true);

        $post_types = FrmProAppHelper::get_custom_post_types();

        require(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-forms/add_form_button_options.php');
    }

    public static function add_form_msg_options($values){
        global $frm_vars;

        $post_types = FrmProAppHelper::get_custom_post_types();

        require(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-forms/add_form_msg_options.php');
    }

    public static function instruction_tabs(){
        include(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-forms/instruction_tabs.php');
    }

    public static function instructions(){
        $tags = array(
            'date' => __( 'Current Date', 'formidable' ),
            'time' => __( 'Current Time', 'formidable' ),
            'email' => __( 'Email', 'formidable' ),
            'login' => __( 'Login', 'formidable' ),
            'display_name' => __( 'Display Name', 'formidable' ),
            'first_name' => __( 'First Name', 'formidable' ),
            'last_name' => __( 'Last Name', 'formidable' ),
            'user_id' => __( 'User ID', 'formidable' ),
            'user_meta key=whatever' => __( 'User Meta', 'formidable' ),
            'post_id' => __( 'Post ID', 'formidable' ),
            'post_title' => __( 'Post Title', 'formidable' ),
            'post_author_email' => __( 'Author Email', 'formidable' ),
            'post_meta key=whatever' => __( 'Post Meta', 'formidable' ),
            'ip' => __( 'IP Address', 'formidable' ),
            'auto_id start=1' => __( 'Increment', 'formidable' ),
            'get param=whatever' => array( 'label' => __( 'GET/POST', 'formidable' ), 'title' => __( 'A variable from the URL or value posted from previous page.', 'formidable' ) .' '. __( 'Replace \'whatever\' with the parameter name. In url.com?product=form, the variable is \'product\'. You would use [get param=product] in your field.', 'formidable' )),
            'server param=whatever' => array( 'label' => __( 'SERVER', 'formidable' ), 'title' => __( 'A variable from the PHP SERVER array.', 'formidable' ) .' '. __( 'Replace \'whatever\' with the parameter name. To get the url of the current page, use [server param="REQUEST_URI"] in your field.', 'formidable' )),
        );
        include(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-forms/instructions.php');
    }

    public static function add_field_link($field_type) {
        return '<a href="#" class="frm_add_field">'. $field_type .'</a>';
    }

    public static function drag_field_class(){
        return ' class="field_type_list"';
    }

    public static function formidable_shortcode_atts($atts, $all_atts){
        global $frm_vars, $wpdb;

        // reset globals
        $frm_vars['readonly'] = $atts['readonly'];
        $frm_vars['editing_entry'] = false;
        $frm_vars['show_fields'] = array();
        $frm_vars['editing_entry'] = false;

        if ( ! is_array($atts['fields']) ) {
            $frm_vars['show_fields'] = explode(',', $atts['fields']);
        }

        if ( ! empty($atts['exclude_fields']) ) {
            if ( ! is_array( $atts['exclude_fields'] ) ) {
                $atts['exclude_fields'] = explode(',', $atts['exclude_fields']);
            }

            $query = array(
                'form_id' => (int) $atts['id'],
                'id NOT' => $atts['exclude_fields'],
                'field_key NOT' => $atts['exclude_fields'],
            );

            $frm_vars['show_fields'] = FrmDb::get_col($wpdb->prefix .'frm_fields', $query);
        }

        if ( $atts['entry_id'] == 'last' ) {
            $user_ID = get_current_user_id();
            if ( $user_ID ) {
                $frm_vars['editing_entry'] = FrmDb::get_var( $wpdb->prefix .'frm_items', array( 'form_id' => $atts['id'], 'user_id' => $user_ID), 'id', array( 'order_by' => 'created_at DESC') );
            }
        } else if ( $atts['entry_id'] ) {
            $frm_vars['editing_entry'] = $atts['entry_id'];
        }

        foreach ( $atts as $unset => $val ) {
            if ( is_array($all_atts) && isset($all_atts[$unset]) ) {
                unset($all_atts[$unset]);
            }
            unset($unset, $val);
        }

        if ( is_array($all_atts) ) {
            foreach ( $all_atts as $att => $val ) {
                $_GET[$att] = urlencode($val);
                unset($att, $val);
            }
        }
    }

    public static function form_fields_class($class){
        global $frm_page_num;
        if ( $frm_page_num ) {
            $class .= ' frm_page_num_'. $frm_page_num;
        }

        return $class;
    }

    public static function form_hidden_fields($form){
        if ( is_user_logged_in() && isset( $form->options['save_draft'] ) && $form->options['save_draft'] == 1 ) {
            echo '<input type="hidden" name="frm_saving_draft" class="frm_saving_draft" value="" />';
        }
    }

    public static function submit_button_label($submit, $form){
        global $frm_vars;
		if ( isset( $frm_vars['next_page'][ $form->id ] ) ) {
			$submit = $frm_vars['next_page'][ $form->id ];
			if ( is_object( $submit ) ) {
                $submit = $submit->name;
			}
        }
        return $submit;
    }

    public static function replace_shortcodes( $html, $form, $values = array() ) {
        preg_match_all("/\[(if )?(deletelink|back_label|back_hook|back_button|draft_label|save_draft|draft_hook)\b(.*?)(?:(\/))?\](?:(.+?)\[\/\2\])?/s", $html, $shortcodes, PREG_PATTERN_ORDER);

		if ( empty( $shortcodes[0] ) ) {
            return $html;
		}

		foreach ( $shortcodes[0] as $short_key => $tag ) {
            $replace_with = '';
            $atts = shortcode_parse_atts( $shortcodes[3][$short_key] );

			switch ( $shortcodes[2][ $short_key ] ) {
                case 'deletelink':
                    $replace_with = FrmProEntriesController::entry_delete_link($atts);
                break;
                case 'back_label':
                    $replace_with = isset($form->options['prev_value']) ? $form->options['prev_value'] : __( 'Previous', 'formidable' );
                break;
                case 'back_hook':
                    $replace_with = apply_filters('frm_back_button_action', '', $form);
                break;
                case 'back_button':
                    global $frm_vars;
                    if ( ! $frm_vars['prev_page'] || ! is_array($frm_vars['prev_page']) || ! isset($frm_vars['prev_page'][$form->id]) || empty($frm_vars['prev_page'][$form->id]) ) {
                        unset($replace_with);
                    } else {
                        $classes = apply_filters('frm_back_button_class', array(), $form);
                        if ( ! empty( $classes ) ) {
                            $html = str_replace('class="frm_prev_page', 'class="frm_prev_page '. implode(' ', $classes), $html);
                        }

                        $html = str_replace('[/if back_button]', '', $html);
                    }
                break;
                case 'draft_label':
                    $replace_with = __( 'Save Draft', 'formidable' );
                break;
                case 'save_draft':
                    if ( ! is_user_logged_in() || ! isset($form->options['save_draft']) || $form->options['save_draft'] != 1 || ( isset($values['is_draft']) && ! $values['is_draft'] ) ) {
                        //remove button if user is not logged in, drafts are not allowed, or editing an entry that is not a draft
                        unset($replace_with);
                    }else{
                        $html = str_replace('[/if save_draft]', '', $html);
                    }
                break;
                case 'draft_hook':
                    $replace_with = apply_filters('frm_draft_button_action', '', $form);
                break;
            }

			if ( isset( $replace_with ) ) {
				$html = str_replace( $shortcodes[0][ $short_key ], $replace_with, $html );
			}

            unset( $short_key, $tag, $replace_with );
        }

        return $html;
    }

    public static function replace_content_shortcodes($content, $entry, $shortcodes) {
        remove_filter('frm_replace_content_shortcodes', 'FrmFormsController::replace_content_shortcodes', 20);
        return FrmProFieldsHelper::replace_shortcodes($content, $entry, $shortcodes);
    }

    public static function conditional_options($options) {
        $cond_opts = array(
            'equals="something"' => __( 'Equals', 'formidable' ),
            'not_equal="something"' => __( 'Does Not Equal', 'formidable' ),
            'equals=""' => __( 'Is Blank', 'formidable' ),
            'not_equal=""' => __( 'Is Not Blank', 'formidable' ),
            'like="something"' => __( 'Is Like', 'formidable' ),
            'not_like="something"' => __( 'Is Not Like', 'formidable' ),
            'greater_than="3"' => __( 'Greater Than', 'formidable' ),
            'less_than="-1 month"' => __( 'Less Than', 'formidable' )
        );

        $options = array_merge($options, $cond_opts);
        return $options;
    }

    public static function advanced_options($options) {
        $adv_opts = array(
            'clickable=1' => __( 'Clickable Links', 'formidable' ),
            'links=0'   => array( 'label' => __( 'Remove Links', 'formidable' ), 'title' => __( 'Removes the automatic links to category pages', 'formidable' )),
            'sanitize=1' => array( 'label' => __( 'Sanitize', 'formidable' ), 'title' => __( 'Replaces spaces with dashes and lowercases all. Use if adding an HTML class or ID', 'formidable' )),
            'sanitize_url=1' => array( 'label' => __( 'Sanitize URL', 'formidable' ), 'title' =>  __( 'Replaces all HTML entities with a URL safe string.', 'formidable' )),
            'truncate=40' => array( 'label' => __( 'Truncate', 'formidable' ), 'title' => __( 'Truncate text with a link to view more. If using Both (dynamic), the link goes to the detail page. Otherwise, it will show in-place.', 'formidable' )),
            'truncate=100 more_text="More"' => __( 'More Text', 'formidable' ),
            'time_ago=1' => array( 'label' => __( 'Time Ago', 'formidable' ), 'title' => __( 'How long ago a date was in minutes, hours, days, months, or years.', 'formidable' )),
            'decimal=2 dec_point="." thousands_sep=","' => __( '# Format', 'formidable' ),
            'show="value"' => array( 'label' => __( 'Saved Value', 'formidable' ), 'title' => __( 'Show the saved value for fields with separate values.', 'formidable' ) ),
            'striphtml=1' => array( 'label' => __( 'Remove HTML', 'formidable' ), 'title' => __( 'Remove all HTML added into your form before display', 'formidable' )),
            'keepjs=1' => array( 'label' => __( 'Keep JS', 'formidable' ), 'title' => __( 'Javascript from your form entries are automatically removed. Add this option only if you trust those submitting entries.', 'formidable' )),
        );

        $options = array_merge($options, $adv_opts);
        return $options;
    }

    public static function user_options($options) {
        $user_fields = array(
            'ID'            => __( 'User ID', 'formidable' ),
            'first_name'    => __( 'First Name', 'formidable' ),
            'last_name'     => __( 'Last Name', 'formidable' ),
            'display_name'  => __( 'Display Name', 'formidable' ),
            'user_login'    => __( 'User Login', 'formidable' ),
            'user_email'    => __( 'Email', 'formidable' ),
            'avatar'        => __( 'Avatar', 'formidable' ),
        );

        $options = array_merge($options, $user_fields);
        return $options;
    }

    public static function include_logic_row($atts) {
        $defaults = array(
            'meta_name' => '',
            'condition' => array(
                'hide_field'        => '',
                'hide_field_cond'   => '==',
                'hide_opt'          => '',
            ),
            'key' => '', 'type' => 'form',
            'form_id' => 0, 'id' => '' ,
            'name' => '', 'names' => array(),
            'showlast' => '', 'onchange' => '',
            'exclude_fields' => array_merge( FrmFieldsHelper::no_save_fields(), array( 'file', 'rte', 'date') ),

        );

        $atts = wp_parse_args($atts, $defaults);
        extract($atts);

        if ( empty($id) ) {
            $id = 'frm_logic_'. $key .'_'. $meta_name;
        }

        if ( empty($name) ) {
            $name = 'frm_form_action['. $key .'][post_content][conditions]['. $meta_name .']';
        }

        if ( empty($names) ) {
            $names = array(
                'hide_field' => $name .'[hide_field]',
                'hide_field_cond' => $name .'[hide_field_cond]',
                'hide_opt' => $name .'[hide_opt]',
            );
        }

        if ( $onchange == '' ) {
            $onchange = "frmGetFieldValues(this.value,'$key','$meta_name','". (isset($field['type']) ? $field['type'] : '') ."','". $names['hide_opt'] ."')";
        }

        $form_fields = FrmField::get_all_for_form($form_id);

        include(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-forms/_logic_row.php');
    }

	public static function add_form_row() {
        check_ajax_referer( 'frm_ajax', 'nonce' );

	    $field_id = (int) $_POST['field_id'];
	    if ( ! $field_id ) {
	        wp_die();
	    }

	    $field = FrmField::getOne($field_id);

	    $args = array(
            'i'         => (int) $_POST['i'],
            'parent_field' => (int) $field->id,
            'form'      => (isset($field->field_options['form_select']) ? $field->field_options['form_select'] : 0),
            'repeat'    => 1,
        );
        $field_name = 'item_meta['. $args['parent_field'] .']';

	    FrmProFormsHelper::repeat_field_set($field_name, $args );

	    wp_die();
	}

	public static function setup_new_vars($values) {
	    return FrmProFormsHelper::setup_new_vars($values);
	}

	public static function setup_edit_vars($values) {
	    return FrmProFormsHelper::setup_edit_vars($values);
	}

	public static function popup_shortcodes($shortcodes) {
	    $shortcodes['display-frm-data'] = array( 'name' => __( 'View', 'formidable' ), 'label' => __( 'Insert a View', 'formidable' ));
	    $shortcodes['frm-graph'] = array( 'name' => __( 'Graph', 'formidable' ), 'label' => __( 'Insert a Graph', 'formidable' ));
        $shortcodes['frm-search'] = array( 'name' => __( 'Search', 'formidable' ), 'label' => __( 'Add a Search Form', 'formidable' ));
        $shortcodes['frm-show-entry'] = array( 'name' => __( 'Single Entry', 'formidable' ), 'label' => __( 'Display a Single Entry', 'formidable' ));

        return $shortcodes;
	}

	public static function sc_popup_opts($opts, $shortcode) {
	    $function_name = 'popup_opts_'. str_replace('-', '_', $shortcode);
	    self::$function_name($opts, $shortcode);
	    return $opts;
	}

    private static function popup_opts_formidable(array &$opts) {
        //'fields' => '', 'entry_id' => 'last' or #, 'exclude_fields' => '', GET => value
        $opts['readonly'] = array( 'val' => 'disabled', 'label' => __( 'Make read-only fields editable', 'formidable' ));
    }

    private static function popup_opts_display_frm_data(array &$opts, $shortcode) {
        //'entry_id' => '',  'user_id' => false, 'order' => '',
        $displays = FrmProDisplay::getAll( array(), 'post_title');

?>
        <h4 for="frmsc_<?php echo esc_attr( $shortcode ) ?>_id" class="frm_left_label"><?php _e( 'Select a view:', 'formidable' ) ?></h4>
        <select id="frmsc_<?php echo esc_attr( $shortcode ) ?>_id">
            <option value=""> </option>
            <?php foreach ( $displays as $display ) { ?>
            <option value="<?php echo esc_attr( $display->ID ) ?>"><?php echo esc_html( $display->post_title ) ?></option>
            <?php } ?>
        </select>
        <div class="frm_box_line"></div>
<?php
        $opts = array(
            'filter' => array( 'val' => 1, 'label' => __( 'Filter shortcodes within the view content', 'formidable' )),
            'drafts'    => array(
                'val'   => 0, 'label' => __( 'Entry type(s)', 'formidable' ), 'type'  => 'select',
                'opts' => array(
                   '0'  => __( 'Published', 'formidable' ),
                   '1'  => __( 'Drafts', 'formidable' ),
                   'both' => __( 'Published and drafts', 'formidable' ),
                )
            ),
            'limit' => array( 'val' => '', 'label' => __( 'Limit', 'formidable' ), 'type' => 'text'),
            'page_size' => array( 'val' => '', 'label' => __( 'Page size', 'formidable' ), 'type' => 'text'),
            'order_by'  => array(
                'val'   => '', 'label' => __( 'Entry order', 'formidable' ), 'type' => 'select',
                'opts'  => array(
                    ''      => __( 'Default', 'formidable' ),
                    'ASC'   => __( 'Ascending', 'formidable' ),
                    'DESC'  => __( 'Descending', 'formidable' ),
                ),
            ),
        );
    }

    private static function popup_opts_frm_search(array &$opts) {
        $opts = array(
            'style' => array( 'val' => 1, 'label' => __( 'Use Formidable styling', 'formidable' )), // or custom class?
            'label' => array(
                'val' => __( 'Search', 'formidable' ),
                'label' => __( 'Customize search button', 'formidable' ),
                'type' => 'text',
            ),
            'post_id' => array(
                'val' => '',
                'label' => __( 'The ID of the page with the search results', 'formidable' ),
                'type' => 'text',
            ),
        );
    }

    private static function popup_opts_frm_graph(array &$opts, $shortcode) {
        $form_list = FrmForm::getAll( array( 'status' => 'published', 'is_template' => 0), 'name');

    ?>
        <h4 class="frm_left_label"><?php _e( 'Select a field:', 'formidable' ) ?></h4>

        <select class="frm_get_field_selection" id="frm_form_frmsc_<?php echo esc_attr( $shortcode ) ?>_id">
            <option value="">&mdash; <?php _e( 'Select Form', 'formidable' ) ?> &mdash;</option>
            <?php foreach ( $form_list as $form_opts ) { ?>
            <option value="<?php echo esc_attr( $form_opts->id ) ?>"><?php echo '' == $form_opts->name ? __( '(no title)', 'formidable' ) : esc_html( FrmAppHelper::truncate($form_opts->name, 30) ) ?></option>
            <?php } ?>
        </select>

        <span id="frm_form_frmsc_<?php echo esc_attr( $shortcode ) ?>_id_fields">
        </span>

        <div class="frm_box_line"></div>
<?php

        $opts = array(
            'type'  => array(
                'val'   => 'default', 'label' => __( 'Graph Type', 'formidable' ), 'type' => 'select',
                'opts'  => array(
                    'default'   => __( 'Default', 'formidable' ),
                    'bar'       => __( 'Bar', 'formidable' ),
                    'column'    => __( 'Column', 'formidable' ),
                    'pie'       => __( 'Pie', 'formidable' ),
                    'line'      => __( 'Line', 'formidable' ),
                    'area'      => __( 'Area', 'formidable' ),
                    'SteppedArea' => __( 'Stepped Area', 'formidable' ),
                    'geo'       => __( 'Geolocation Map', 'formidable' ),
                ),
            ),
            'data_type' => array(
                'val'   => 'count', 'label' => __( 'Data Type', 'formidable' ), 'type' => 'select',
                'opts'  => array(
                    'count' => __( 'The number of entries', 'formidable' ),
                    'total' => __( 'Add the field values together', 'formidable' ),
                    'average' => __( 'Average the totaled field values', 'formidable' ),
                ),
            ),
            'height'    => array( 'val' => '', 'label' => __( 'Height', 'formidable' ), 'type' => 'text'),
            'width'     => array( 'val' => '', 'label' => __( 'Width', 'formidable' ), 'type' => 'text'),
            'bg_color'  => array( 'val' => '', 'label' => __( 'Background color', 'formidable' ), 'type' => 'text'),
            'truncate_label' => array( 'val' => '', 'label' => __( 'Truncate graph labels', 'formidable' ), 'type' => 'text'),
            'truncate'  => array( 'val' => '', 'label' => __( 'Truncate title', 'formidable' ), 'type' => 'text'),
            'title'     => array( 'val' => '', 'label' => __( 'Graph title', 'formidable' ), 'type' => 'text'),
            'title_size'=> array( 'val' => '', 'label' => __( 'Title font size', 'formidable' ), 'type' => 'text'),
            'title_font'=> array( 'val' => '', 'label' => __( 'Title font name', 'formidable' ), 'type' => 'text'),
            'is3d'      => array(
                'val'   => 1, 'label' => __( 'Turn your pie graph three-dimensional', 'formidable' ),
                'show'  => array( 'type' => 'pie'),
            ),
            'include_zero' => array( 'val' => 1, 'label' => __( 'When using dates for the x_axis parameter, you can also fill in dates with a zero value. This will also apply to dropdown, radio, and checkbox fields with no x_axis defined.', 'formidable' )),
            'show_key' => array( 'val' => 1, 'label' => __( 'Include the key with the graph', 'formidable' )),
        );

        /*
            'ids' => false,
            'colors' => '', 'grid_color' => '#CCC',
            'response_count' => 10, 'user_id' => false, 'entry_id' => false,
            'x_axis' => false, 'limit' => '',
            'x_start' => '', 'x_end' => '', 'min' => '', 'max' => '', 'y_title' => '', 'x_title' => '',
            'field' => false, 'tooltip_label' => '',
			'start_date' => '', 'end_date' => '', 'group_by' => '', 'x_order' => '1',
			any field id in this form => value
        */
    }

    private static function popup_opts_frm_show_entry(array &$opts, $shortcode) {

?>
    <h4 class="frm_left_label"><?php _e( 'Insert an entry ID/key:', 'formidable' ) ?></h4>

    <input type="text" value="" id="frmsc_<?php echo esc_attr( $shortcode ) ?>_id" />

    <div class="frm_box_line"></div>
<?php
        $opts = array(
            'user_info'     => array( 'val' => 1, 'label' => __( 'Include user info like browser and IP', 'formidable' )),
            'include_blank' => array( 'val' => 1, 'label' => __( 'Include rows for blank fields', 'formidable' )),
            'plain_text'    => array( 'val' => 1, 'label' => __( 'Do not include any HTML', 'formidable' )),
            'direction'     => array( 'val' => 'rtl', 'label' => __( 'Use RTL format', 'formidable' )),
            'font_size'     => array( 'val' => '', 'label' => __( 'Font size', 'formidable' ), 'type' => 'text'),
            'text_color'    => array( 'val' => '', 'label' => __( 'Text color', 'formidable' ), 'type' => 'text'),
            'border_width'  => array( 'val' => '', 'label' => __( 'Border width', 'formidable' ), 'type' => 'text'),
            'border_color'  => array( 'val' => '', 'label' => __( 'Border color', 'formidable' ), 'type' => 'text'),
            'bg_color'      => array( 'val' => '', 'label' => __( 'Background color', 'formidable' ), 'type' => 'text'),
            'alt_bg_color'  => array( 'val' => '', 'label' => __( 'Alternate background color', 'formidable' ), 'type' => 'text'),
        );
    }

	/* Trigger model actions */
	public static function update_options($options, $values){
        return FrmProForm::update_options($options, $values);
    }

    public static function save_wppost_actions($settings, $action) {
        return FrmProForm::save_wppost_actions($settings, $action);
    }

    public static function update_form_field_options($field_options, $field){
        return FrmProForm::update_form_field_options($field_options, $field);
    }

    public static function update($id, $values){
        FrmProForm::update($id, $values);
    }

    public static function after_duplicate($new_opts) {
        return FrmProForm::after_duplicate($new_opts);
    }

    public static function validate( $errors, $values ){
        return FrmProForm::validate( $errors, $values );
    }
}
