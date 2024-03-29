<?php
class FrmProNotification{

    public static function add_attachments($attachments, $form, $args) {
        $defaults = array(
            'entry'     => false,
            'email_key' => '',
        );
        $args = wp_parse_args($args, $defaults);
        $entry = $args['entry'];

        // Used for getting the file ids for sub entries
        $atts = array(
            'entry'         => $entry,  'default_email' => false,
            'include_blank' => false,   'id'            => $entry->id,
            'plain_text'    => true,    'format'        => 'array',
            'filter'        => false,
        );

        $file_fields = FrmField::get_all_types_in_form($form->id, 'file', '', 'include');

        foreach ( $file_fields as $file_field ) {
            $file_options = $file_field->field_options;

            //Only go through code if file is supposed to be attached to email
            if ( ! isset($file_options['attach']) || ! $file_options['attach'] ) {
                continue;
            }

            $file_ids = array();
            //Get attachment ID for uploaded files
            if ( isset($entry->metas[$file_field->id]) ) {
                $file_ids = $entry->metas[$file_field->id];
            } else if ( $file_field->form_id != $form->id ) {
                // this is in a repeating or embedded field
                $values = array();

                FrmEntriesHelper::fill_entry_values($atts, $file_field, $values);
                if ( isset($values[$file_field->field_key]) ) {
                    $file_ids = $values[$file_field->field_key];
                }
            } else if ( isset($file_field->field_options['post_field']) && !empty($file_field->field_options['post_field']) ) {
                //get value from linked post
                $file_ids = FrmProEntryMetaHelper::get_post_or_meta_value( $entry, $file_field );
            }

            //Only proceed if there is actually an uploaded file
            if ( empty($file_ids) ) {
                continue;
            }

            // Get each file in this field
            foreach ( (array) $file_ids as $file_id ) {
                if ( empty($file_id) ) {
                    continue;
                }
                $file = get_post_meta( $file_id, '_wp_attached_file', true);
            	if ( $file ) {
            	    if ( ! isset($uploads) || ! isset($uploads['basedir']) ) {
            	        $uploads = wp_upload_dir();
                    }
            	    $attachments[] = $uploads['basedir'] . '/'. $file;
            	}
            }
        }

        return $attachments;
    }

    public static function entry_created($entry_id, $form_id) {
        FrmNotification::entry_created($entry_id, $form_id);
    }

    //send update email notification
    public static function entry_updated($entry_id, $form_id){
        _deprecated_function( __FUNCTION__, '2.0', 'FrmFormActionsController::trigger_actions("update", '. $form_id .', '. $entry_id .', "email")');
        FrmFormActionsController::trigger_actions('update', $form_id, $entry_id, 'email');
    }

}
