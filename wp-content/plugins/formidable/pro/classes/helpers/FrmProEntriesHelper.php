<?php

class FrmProEntriesHelper{

    // check if form should automatically be in edit mode (limited to one, has draft)
    public static function &allow_form_edit($action, $form) {
        if ( $action != 'new' ) {
            // make sure there is an entry id in the url if the action is being set in the url
            $entry_id = isset($_GET['entry']) ? $_GET['entry'] : 0;
            if ( empty($entry_id) && ( ! $_POST || !isset($_POST['frm_action']) ) ) {
                $action = 'new';
            }
        }

        $user_ID = get_current_user_id();
        if (!$form or !$user_ID)
            return $action;

        if(!$form->editable)
            $action = 'new';

        $is_draft = false;
        if($action == 'destroy')
            return $action;

        global $wpdb;
        if (($form->editable and (isset($form->options['single_entry']) and $form->options['single_entry'] and $form->options['single_entry_type'] == 'user') or (isset($form->options['save_draft']) and $form->options['save_draft']))){
            if($action == 'update' and ($form->id == FrmAppHelper::get_param('form_id'))){
                //don't change the action is this is the wrong form
            }else{
                $query = $wpdb->prepare('user_id = %d AND form_id = %d', $user_ID, $form->id);
                if ( isset($form->options['save_draft']) && $form->options['save_draft'] && ( ! $form->editable || ! isset($form->options['single_entry']) || ! $form->options['single_entry'] || $form->options['single_entry_type'] != 'user' ) ) {
                    $query .= ' AND is_draft = 1';
                }

                $meta = $wpdb->get_var('SELECT id FROM '. $wpdb->prefix .'frm_items WHERE '. $query);

                if ( $meta ) {
                    if ( isset($args['is_draft']) ) {
                        $is_draft = 1;
                    }

                    $action = 'edit';
                }
            }
        }

        //do not allow editing if user does not have permission
        if ( $action != 'edit' || $is_draft ) {
            return $action;
        }

        $entry = FrmAppHelper::get_param('entry', 0);

        if ( ! self::user_can_edit($entry, $form) ) {
            $action = 'new';
        }

        return $action;
    }

    public static function user_can_edit($entry, $form=false) {
        if ( empty($form) ) {
            FrmEntriesHelper::maybe_get_entry($entry);

            if ( is_object($entry) ) {
                $form = $entry->form_id;
            }
        }

        FrmFormsHelper::maybe_get_form($form);

        $allowed = self::user_can_edit_check($entry, $form);
        return apply_filters('frm_user_can_edit', $allowed, compact('entry', 'form'));
    }

    public static function user_can_edit_check($entry, $form) {
        global $wpdb;

        $user_ID = get_current_user_id();

        if ( ! $user_ID || empty($form) || ( is_object($entry) && $entry->form_id != $form->id ) ) {
            return false;
        }

        if ( is_object($entry) ) {
            if ( ( $entry->is_draft && $entry->user_id == $user_ID ) || self::user_can_edit_others( $form ) ) {
                //if editable and user can edit this entry
                return true;
            }
        }

        $where = $wpdb->prepare('fr.id=%d', $form->id);

        if ( self::user_can_only_edit_draft($form) ) {
            //only allow editing of drafts
            $where .= $wpdb->prepare(" and user_id=%d and is_draft=%d", $user_ID, 1);
        }

        if ( ! self::user_can_edit_others( $form ) ) {
            $where .= $wpdb->prepare(" and user_id=%d", $user_ID);

            if ( is_object($entry) && $entry->user_id != $user_ID ) {
                return false;
            }

            if ( $form->editable && !FrmAppHelper::user_has_permission($form->options['open_editable_role']) && !FrmAppHelper::user_has_permission($form->options['editable_role']) ) {
                // make sure user cannot edit their own entry, even if a higher user role can unless it's a draft
                if ( is_object($entry) && !$entry->is_draft ) {
                    return false;
                } else if ( !is_object($entry) ) {
                    $where .= ' and is_draft=1';
                }
            }
        } else if ( $form->editable && $user_ID && empty($entry) ) {
            // make sure user is editing their own draft by default, even if they have permission to edit others' entries
           $where .= $wpdb->prepare(" and user_id=%d", $user_ID);
        }

        if ( !$form->editable ) {
            $where .= ' and is_draft=1';

            if ( is_object($entry) && !$entry->is_draft ) {
                return false;
            }
        }

        // If entry object, and we made it this far, then don't do another db call
        if ( is_object($entry) ) {
            return true;
        }

        if ( !empty($entry) ) {
            $where .= $wpdb->prepare( is_numeric($entry) ? " and it.id=%d" : " and item_key=%s", $entry);
        }

        return FrmEntry::getAll( $where, ' ORDER BY created_at DESC', 1, true);
    }

    /*
    * check if this user can edit entry from another user
    * @return boolean True if user can edit
    */
    public static function user_can_edit_others( $form ) {
        if ( ! $form->editable || ! isset($form->options['open_editable_role']) || ! FrmAppHelper::user_has_permission($form->options['open_editable_role']) ) {
            return false;
        }

        return ( ! isset($form->options['open_editable']) || $form->options['open_editable'] );
    }

    /*
    * only allow editing of drafts
    * @return boolean
    */
    public static function user_can_only_edit_draft($form) {
        if ( ! $form->editable || empty($form->options['editable_role']) || FrmAppHelper::user_has_permission($form->options['editable_role']) ) {
            return false;
        }

        if ( isset($form->options['open_editable_role']) && $form->options['open_editable_role'] !=  '-1' ) {
            return false;
        }

        return ! self::user_can_edit_others( $form );
    }

    public static function user_can_delete($entry) {
        FrmEntriesHelper::maybe_get_entry($entry);
        if ( ! $entry ) {
            return false;
        }

        if ( current_user_can('frm_delete_entries') ) {
            $allowed = true;
        } else {
            $allowed = self::user_can_edit($entry);
            if ( !empty($allowed) ) {
                $allowed = true;
            }
        }

        return apply_filters('frm_allow_delete', $allowed, $entry);
    }

    public static function show_new_entry_button($form) {
        echo self::new_entry_button($form);
    }

    public static function new_entry_button($form) {
        if ( ! current_user_can('frm_create_entries') ) {
            return;
        }

        $link = '<a href="?page=formidable-entries&frm_action=new';
        if ( $form ) {
            $form_id = is_numeric($form) ? $form : $form->id;
            $link .= '&form='. $form_id;
        }
        $link .= '" class="add-new-h2">'. __('Add New', 'formidable') .'</a>';

        return $link;
    }

    public static function show_duplicate_link($entry) {
        echo self::duplicate_link($entry);
    }

    public static function duplicate_link($entry) {
        if ( current_user_can('frm_create_entries') ) {
            $link = '<a href="?page=formidable-entries&frm_action=duplicate&form='. $entry->form_id .'&id='. $entry->id .'" class="button-secondary alignright">'. __('Duplicate', 'formidable') .'</a>';
            return $link;
        }
    }

    public static function edit_button() {
        if ( ! current_user_can('frm_edit_entries') ) {
            return;
        }
?>
	    <div id="publishing-action">
	        <a href="<?php echo add_query_arg('frm_action', 'edit') ?>" class="button-primary"><?php _e('Edit') ?></a>
        </div>
<?php
    }

    public static function resend_email_links($entry_id, $form_id, $args = array()) {
        $defaults = array(
            'label' => __('Resend Email Notifications', 'formidable'),
            'echo' => true,
        );

        $args = wp_parse_args($args, $defaults);

        $link = '<a href="#" data-eid="'. $entry_id .'" data-fid="'. $form_id .'" id="frm_resend_email" title="'. esc_attr($args['label']) .'">'. $args['label'] .'</a>';
        if ( $args['echo'] ) {
            echo $link;
        }
        return $link;
    }

    public static function before_table($footer, $form_id=false){
        if ( $_GET['page'] != 'formidable-entries' ) {
            return;
        }

        if ( $footer ) {
            if ( apply_filters('frm_show_delete_all', current_user_can('frm_edit_entries'), $form_id) ) {
            ?><div class="frm_uninstall alignleft actions"><a href="?page=formidable-entries&amp;frm_action=destroy_all<?php echo $form_id ? '&amp;form='. $form_id : '' ?>" class="button" onclick="return confirm('<?php _e('Are you sure you want to permanently delete ALL the entries in this form?', 'formidable') ?>')"><?php _e('Delete ALL Entries', 'formidable') ?></a></div>
<?php
            }
            return;
        }

        $page_params = array('frm_action' => 0, 'action' => 'frm_entries_csv', 'form' => $form_id);

        if ( !empty( $_REQUEST['s'] ) )
            $page_params['s'] = $_REQUEST['s'];

        if ( !empty( $_REQUEST['search'] ) )
            $page_params['search'] = $_REQUEST['search'];

    	if ( !empty( $_REQUEST['fid'] ) )
    	    $page_params['fid'] = $_REQUEST['fid'];

        ?>
        <div class="alignleft actions"><a href="<?php echo esc_url(add_query_arg($page_params, admin_url( 'admin-ajax.php' ))) ?>" class="button"><?php _e('Download CSV', 'formidable'); ?></a></div>
        <?php
    }

    // check if entry being updated just switched draft status
    public static function is_new_entry($entry) {
        FrmEntriesHelper::maybe_get_entry( $entry );

        // this function will only be correct if the entry has already gone through FrmProEntriesController::check_draft_status
        return ( $entry->created_at == $entry->updated_at );
    }

    public static function get_field($field = 'is_draft', $id) {
        $entry = FrmAppHelper::check_cache( $id, 'frm_entry' );
        if ( $entry && isset($entry->$field) ) {
            return $entry->{$field};
        }

        global $wpdb;
        $query = $wpdb->prepare('SELECT '. $field .' FROM '. $wpdb->prefix .'frm_items WHERE id=%d', $id);
        $var = FrmAppHelper::check_cache($id .'_'. $field, 'frm_entry', $query, 'get_var');

        return $var;
    }

    public static function get_dfe_values($field, $entry, &$field_value) {
        if ( $field_value || $field->type != 'data' || $field->field_options['data_type'] != 'data' || ! isset($field->field_options['hide_field']) ) {
            return;
        }

        $field_value = array();
        foreach ( (array) $field->field_options['hide_field'] as $hfield ) {
            if ( isset($entry->metas[$hfield]) ) {
                $field_value[] = maybe_unserialize($entry->metas[$hfield]);
            }
        }
    }

    public static function get_search_str($where_clause='', $search_str, $form_id=false, $fid=false) {
        global $wpdb;

        $where_item = '';
        $join = ' (';
        if ( !is_array($search_str) ) {
            $search_str = explode(' ', $search_str);
        }

        foreach ( $search_str as $search_param ) {
            $no_esc_search = $search_param;
            $search_param = FrmAppHelper::esc_like( $search_param );

            if ( !is_numeric($fid) ) {
                $where_item .= (empty($where_item)) ? ' (' : ' OR';

                if ( in_array($fid, array('created_at', 'updated_at')) ) {
                    $where_item .= ' DATE_FORMAT(it.'. $fid .', "%Y-%m-%d %H:%i:%s")'. $wpdb->prepare(" like %s", '%'. $search_param .'%');
                } else if ( in_array($fid, array('user_id', 'id')) ) {
                    if ( $fid == 'user_id' && ! is_numeric($search_param) ) {
                        $search_param = FrmAppHelper::get_user_id_param($no_esc_search);
                    }

                    $where_item .= $wpdb->prepare(" it.{$fid} like %s", '%'. $search_param .'%');
                } else {
                    $where_item .= $wpdb->prepare(' it.name like %s OR it.item_key like %s OR it.description like %s', '%'. $search_param .'%', '%'. $search_param .'%', '%'. $search_param .'%');
                    $where_item .= ' OR DATE_FORMAT(it.created_at, "%Y-%m-%d %H:%i:%s") '. $wpdb->prepare('like %s', '%'. $search_param .'%');
                }
            }

            if ( empty($fid) || is_numeric($fid) ) {
                $where_entries = $wpdb->prepare('(meta_value LIKE %s', '%'. $search_param .'%');
                if ( $form_id && $data_fields = FrmProFormsHelper::has_field('data', $form_id, false) ) {
                    $df_form_ids = array();

                    //search the joined entry too
                    foreach ( (array) $data_fields as $df ) {
                        //don't check if a different field is selected
                        if ( is_numeric($fid) && (int) $fid != $df->id ) {
                            continue;
                        }

                        FrmProFieldsHelper::get_subform_ids($df_form_ids, $df);

                        unset($df);
                    }
                    unset($data_fields);

                    if ( !empty($df_form_ids) ) {
                        $data_form_ids = $wpdb->get_col("SELECT form_id FROM {$wpdb->prefix}frm_fields WHERE id in (". implode(',', array_filter($df_form_ids, 'is_numeric')) .")");

                        if ( $data_form_ids ) {
                            $data_entry_ids = FrmEntryMeta::getEntryIds("fi.form_id in (". implode(',', $data_form_ids).") " . $wpdb->prepare("and meta_value LIKE %s", '%'. $search_param .'%'));
                            if ( !empty($data_entry_ids) ) {
                                $where_entries .= " OR meta_value in (".implode(',', $data_entry_ids).")";
                            }
                        }
                        unset($data_form_ids);
                    }
                    unset($df_form_ids);

                }

                $where_entries .= ")";

                if ( is_numeric($fid) ) {
                    $where_entries .= $wpdb->prepare(' AND field_id=%d', $fid);
                }

                if ( FrmAppHelper::is_admin_page('formidable-entries') ) {
                    $include_drafts = true;
                } else {
                    $include_drafts = false;
                }

                $meta_ids = FrmEntryMeta::getEntryIds($where_entries, '', '', true, array('is_draft' => $include_drafts));

                if ( !empty($where_clause) ) {
                    $where_clause .= " AND" . $join;
                    if ( !empty($join) ){
                        $join = '';
                    }
                }

                if ( !empty($meta_ids) ) {
                    $where_clause .= " it.id in (".implode(',', $meta_ids).")";
                } else {
                    $where_clause .= " it.id=0";
                }
            }
        }

        if ( !empty($where_item) ) {
            $where_item .= ')';
            if ( !empty($where_clause) ) {
                $where_clause .= empty($fid) ? ' OR' : ' AND';
            }
            $where_clause .= $where_item;
        }

        if ( empty($join) ) {
            $where_clause .= ')';
        }

        return $where_clause;
    }

    public static function get_search_ids($s, $form_id, $args = array() ){
        global $wpdb;

        if(empty($s)) return false;

		preg_match_all('/".*?("|$)|((?<=[\\s",+])|^)[^\\s",+]+/', $s, $matches);
		$search_terms = array_map('trim', $matches[0]);
		$n = '%'; //!empty($q['exact']) ? '' : '%';

        $p_search = $search = '';
        $search_or = '';
        $e_ids = array();

        $data_field = FrmProFormsHelper::has_field('data', $form_id, false);

		foreach( (array) $search_terms as $term ) {
			$term = FrmAppHelper::esc_like( $term );
			$p_search .= $wpdb->prepare(" AND (($wpdb->posts.post_title LIKE %s) OR ($wpdb->posts.post_content LIKE %s))", $n . $term . $n, $n . $term . $n);

			$search .= $wpdb->prepare($search_or .'meta_value LIKE %s', $n . $term . $n);
            $search_or = ' OR ';
            if(is_numeric($term))
                $e_ids[] = (int) $term;

            if($data_field){
                $df_form_ids = array();

                //search the joined entry too
                foreach ( (array) $data_field as $df ) {
                    FrmProFieldsHelper::get_subform_ids($df_form_ids, $df);

                    unset($df);
                }

                $data_form_ids = $wpdb->get_col("SELECT form_id FROM {$wpdb->prefix}frm_fields WHERE id in (". implode(',', $df_form_ids).")");
                unset($df_form_ids);

                if($data_form_ids){
                    $data_entry_ids = FrmEntryMeta::getEntryIds("fi.form_id in (". implode(',', $data_form_ids).")". $wpdb->prepare(' AND meta_value LIKE %s', '%'. $term .'%'));
                    if($data_entry_ids)
                        $search .= "{$search_or}meta_value in (".implode(',', $data_entry_ids).")";
                }

                unset($data_form_ids);
            }
		}

		$p_ids = '';
		$matching_posts = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE 1=1 $p_search");
		if($matching_posts){
		    $p_ids = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}frm_items WHERE post_id in (". implode(',', $matching_posts) .") AND form_id=". (int) $form_id);
		    $p_ids = ($p_ids) ? " OR item_id in (". implode(',', $p_ids) .")" : '';
		}

		if(!empty($e_ids))
		    $p_ids .= " OR item_id in (". implode(',', $e_ids) .")";

        return FrmEntryMeta::getEntryIds('(('. $search .')'. $p_ids .') and fi.form_id='. (int) $form_id, '', '', true, $args );
    }

    public static function encode_value($line, $from_encoding, $to_encoding){
        $convmap = false;

        switch($to_encoding){
            case 'macintosh':
            // this map was derived from the differences between the MacRoman and UTF-8 Charsets
            // Reference:
            //   - http://www.alanwood.net/demos/macroman.html
                $convmap = array(
                    256, 304, 0, 0xffff,
                    306, 337, 0, 0xffff,
                    340, 375, 0, 0xffff,
                    377, 401, 0, 0xffff,
                    403, 709, 0, 0xffff,
                    712, 727, 0, 0xffff,
                    734, 936, 0, 0xffff,
                    938, 959, 0, 0xffff,
                    961, 8210, 0, 0xffff,
                    8213, 8215, 0, 0xffff,
                    8219, 8219, 0, 0xffff,
                    8227, 8229, 0, 0xffff,
                    8231, 8239, 0, 0xffff,
                    8241, 8248, 0, 0xffff,
                    8251, 8259, 0, 0xffff,
                    8261, 8363, 0, 0xffff,
                    8365, 8481, 0, 0xffff,
                    8483, 8705, 0, 0xffff,
                    8707, 8709, 0, 0xffff,
                    8711, 8718, 0, 0xffff,
                    8720, 8720, 0, 0xffff,
                    8722, 8729, 0, 0xffff,
                    8731, 8733, 0, 0xffff,
                    8735, 8746, 0, 0xffff,
                    8748, 8775, 0, 0xffff,
                    8777, 8799, 0, 0xffff,
                    8801, 8803, 0, 0xffff,
                    8806, 9673, 0, 0xffff,
                    9675, 63742, 0, 0xffff,
                    63744, 64256, 0, 0xffff,
                );
            break;
            case 'ISO-8859-1':
                $convmap = array(256, 10000, 0, 0xffff);
            break;
        }

        if (is_array($convmap))
            $line = mb_encode_numericentity($line, $convmap, $from_encoding);

        if ($to_encoding != $from_encoding)
            return iconv($from_encoding, $to_encoding.'//IGNORE', $line);
        else
            return $line;
    }
}
