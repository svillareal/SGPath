<?php

header('Content-Description: File Transfer');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Content-Type: text/csv; charset=' . $charset, true);
header('Expires: '. gmdate("D, d M Y H:i:s", mktime(date('H')+2, date('i'), date('s'), date('m'), date('d'), date('Y'))) .' GMT');
header('Last-Modified: '. gmdate('D, d M Y H:i:s') .' GMT');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

do_action('frm_csv_headers', array('form_id' => $form_id, 'fields' => $form_cols));

//if BOM
//echo chr(239) . chr(187) . chr(191);

foreach ($form_cols as $col){
    if ( isset($col->field_options['separate_value']) && $col->field_options['separate_value'] && ! in_array($col->type, array('user_id', 'file', 'data', 'date')) ) {
        echo '"'. str_replace('"', '""', FrmProEntriesHelper::encode_value(strip_tags($col->name .' '. __('(label)', 'formidable')), $charset, $to_encoding)) .'"'. $col_sep;
    }

    echo '"'. FrmProEntriesHelper::encode_value(strip_tags($col->name), $charset, $to_encoding) .'"'. $col_sep;
}

if($comment_count){
    for ($i=0; $i<$comment_count; $i++){
        echo '"'. FrmProEntriesHelper::encode_value(__('Comment', 'formidable'), $charset, $to_encoding) .'"'. $col_sep;
        echo '"'. FrmProEntriesHelper::encode_value(__('Comment User', 'formidable'), $charset, $to_encoding) .'"'. $col_sep;
        echo '"'. FrmProEntriesHelper::encode_value(__('Comment Date', 'formidable'), $charset, $to_encoding) .'"'. $col_sep;
    }
    unset($i);
}

echo '"'. __('Timestamp', 'formidable') .'"'. $col_sep .'"'. __('Last Updated', 'formidable') .'"'. $col_sep .'"'. __('Created By', 'formidable') .'"'. $col_sep .'"'. __('Updated By', 'formidable') .'"'. $col_sep .'"'. __('Draft', 'formidable') .'"'. $col_sep .'"IP"'. $col_sep .'"ID"'. $col_sep .'"Key"'."\n";

// fetch 20 posts at a time rather than loading the entire table into memory
while ( $next_set = array_splice( $entry_ids, 0, 20 ) ) {
    $where = 'id IN (' . join( ',', $next_set ) . ') OR parent_item_id IN (' . join( ',', $next_set ) . ')';
    // order by parent_item_id so children will be first
    $entries = FrmEntry::getAll($where, ' ORDER BY parent_item_id DESC', '', true, false);

foreach($entries as $entry){
    if ( $entry->form_id != $form_id ) {
        if ( isset($entry->metas) ) {
            // add child entries to the parent
            foreach ( $entry->metas as $meta_id => $meta_value ) {
                if ( $meta_value == '' ){
                    continue;
                }
                if ( ! isset($entries[$entry->parent_item_id]->metas[$meta_id]) ) {
                    $entries[$entry->parent_item_id]->metas[$meta_id] = array();
                }
                //add the repeated values
                $entries[$entry->parent_item_id]->metas[$meta_id][] = $meta_value;
            }
            $entries[$entry->parent_item_id]->metas += $entry->metas;
        }

        // add the embedded form id
        if ( ! isset($entries[$entry->parent_item_id]->embedded_fields) ) {
            $entries[$entry->parent_item_id]->embedded_fields = array();
        }
        $entries[$entry->parent_item_id]->embedded_fields[$entry->id] = $entry->form_id;

        continue;
    }

    foreach ($form_cols as $col){
        $field_value = isset($entry->metas[$col->id]) ? $entry->metas[$col->id] : false;

        if ( ! $field_value && $entry->post_id ) {
            if ( $col->type == 'tag' || (isset($col->field_options['post_field']) && $col->field_options['post_field']) ) {
                $field_value = FrmProEntryMetaHelper::get_post_value(
                    $entry->post_id,
                    $col->field_options['post_field'],
                    $col->field_options['custom_field'],
                    array(
                        'truncate' => (($col->field_options['post_field'] == 'post_category') ? true : false),
                        'form_id' => $entry->form_id, 'field' => $col, 'type' => $col->type,
                        'exclude_cat' => (isset($col->field_options['exclude_cat']) ? $col->field_options['exclude_cat'] : 0),
                        'sep' => $sep,
                    )
                );
            }
        }

        if (in_array($col->type, array('user_id', 'file', 'date', 'data'))){
            $field_value = FrmProFieldsHelper::get_export_val($field_value, $col);
        }else{
            if ( isset($col->field_options['separate_value']) && $col->field_options['separate_value'] ) {
                $sep_value = FrmEntriesHelper::display_value($field_value, $col, array(
                    'type' => $col->type, 'post_id' => $entry->post_id, 'show_icon' => false,
                    'entry_id' => $entry->id, 'sep' => $sep,
                    'embedded_field_id' => ( isset($entry->embedded_fields) && isset($entry->embedded_fields[$entry->id]) ) ? 'form'. $entry->embedded_fields[$entry->id] : 0,
                ));
                if ( is_array($sep_value) ) {
                    $sep_value = implode($sep, $sep_value);
                }

                $sep_value = FrmProEntriesHelper::encode_value($sep_value, $charset, $to_encoding);
                $sep_value = str_replace('"', '""', $sep_value); //escape for CSV files.
                if ( $line_break != 'return' ) {
                    $sep_value = str_replace(array("\r\n", "\r", "\n"), $line_break, $sep_value);
                }
                echo '"'. $sep_value .'"'. $col_sep;;
                unset($sep_value);
            }

            $checked_values = maybe_unserialize($field_value);
            $checked_values = apply_filters('frm_csv_value', $checked_values, array('field' => $col));

            if (is_array($checked_values)){
                $field_value = implode($sep, $checked_values);
            }else{
                $field_value = $checked_values;
            }
        }

        if ( is_array($field_value) ) {
            // implode the repeated field values
            $field_value = implode($sep, $field_value);
        }
        $field_value = FrmProEntriesHelper::encode_value($field_value, $charset, $to_encoding);
        $field_value = str_replace('"', '""', $field_value); //escape for CSV files.
        if ( $line_break != 'return' ) {
            $field_value = str_replace(array("\r\n", "\r", "\n"), $line_break, $field_value);
        }

        echo '"'. $field_value .'"'. $col_sep;

        unset($col);
        unset($field_value);
    }

    $comments = FrmEntryMeta::getAll("item_id=". (int) $entry->id ." and field_id=0", ' ORDER BY it.created_at ASC');
    $place_holder = $comment_count;
    if($comments){
        foreach($comments as $comment){
            $c = maybe_unserialize($comment->meta_value);
            if ( ! isset($c['comment']) ) {
                continue;
            }

            $place_holder--;
            $co = FrmProEntriesHelper::encode_value($c['comment'], $charset, $to_encoding);
            echo '"'. $co .'"'. $col_sep;
            unset($co);

            $v = FrmProEntriesHelper::encode_value(FrmProFieldsHelper::get_display_name($c['user_id'], 'user_login'), $charset, $to_encoding);
            unset($c);
            echo '"'. $v .'"'. $col_sep;

            $v = FrmProEntriesHelper::encode_value(FrmAppHelper::get_formatted_time($comment->created_at, $wp_date_format, ' '), $charset, $to_encoding);
            echo '"'. $v .'"'. $col_sep;
            unset($v);
        }
    }

    if ( $place_holder ) {
        for ( $i=0; $i<$place_holder; $i++ ) {
            echo '""'. $col_sep .'""'. $col_sep .'""'. $col_sep;
        }
        unset($i);
    }
    unset($place_holder);

    $formatted_date = FrmAppHelper::get_formatted_time($entry->created_at, $wp_date_format, ' ');
    echo '"'. $formatted_date .'"'. $col_sep;

    $formatted_date = FrmAppHelper::get_formatted_time($entry->updated_at, $wp_date_format, ' ');
    echo '"'. $formatted_date .'"'. $col_sep;
    unset($formatted_date);

    echo '"'. FrmProEntriesHelper::encode_value(FrmProFieldsHelper::get_display_name($entry->user_id, 'user_login'), $charset, $to_encoding) .'"'. $col_sep;
    echo '"'. FrmProEntriesHelper::encode_value(FrmProFieldsHelper::get_display_name($entry->updated_by, 'user_login'), $charset, $to_encoding) .'"'. $col_sep;

    echo '"'. ( $entry->is_draft ? '1' : '0' ) .'"'. $col_sep;
    echo '"'. $entry->ip .'"'. $col_sep;
    echo '"'. $entry->id .'"'. $col_sep;
    echo '"'. $entry->item_key .'"'. "\n";
    unset($entry);

}
unset($entries);
}