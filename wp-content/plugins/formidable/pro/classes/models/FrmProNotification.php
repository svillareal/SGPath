<?php
class FrmProNotification{

    public static function add_attachments($attachments, $form, $args) {
        $defaults = array(
            'entry'     => false,
            'email_key' => '',
        );
        $args = wp_parse_args($args, $defaults);
        $entry = $args['entry'];

        $file_fields = FrmField::get_all_types_in_form($form->id, 'file');

        foreach ( $file_fields as $file_field ) {
            $file_options = $file_field->field_options;

            //Only go through code if file is supposed to be attached to email
            if ( ! isset($file_options['attach']) || ! $file_options['attach'] ) {
                continue;
            }

            //Get attachment ID for uploaded files
            if ( ! isset($entry->metas[$file_field->id]) ) {
                if ( isset($file_field->field_options['post_field']) && !empty($file_field->field_options['post_field']) ) {
                    //get value from linked post
                    $file_ids = FrmProEntryMetaHelper::get_post_or_meta_value( $entry, $file_field );
                }
            } else {
                $file_ids = $entry->metas[$file_field->id];
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
