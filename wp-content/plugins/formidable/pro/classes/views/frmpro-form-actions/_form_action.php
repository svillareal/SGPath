<?php if ( ! empty($form_fields) ) { ?>
    <h3 class="frm_add_logic_link <?php echo $show_logic ? ' frm_hidden' : ''; ?>" id="logic_link_<?php echo $action_key ?>" ><a href="javascript:void(0)" class="frm_add_form_logic" data-emailkey="<?php echo $action_key ?>" id="email_logic_<?php echo $action_key ?>" ><?php _e('Use Conditional Logic', 'formidable') ?></a></h3>
<?php } ?>

<div class="frm_logic_rows" <?php echo ($show_logic) ? '' : ' style="display:none"'; ?>>
    <h3><?php _e('Conditional Logic', 'formidable') ?></h3>
    <div id="frm_logic_row_<?php echo $action_key ?>">
        <p><select name="<?php echo $action_control->get_field_name('conditions') ?>[send_stop]">
            <option value="send" <?php selected($form_action->post_content['conditions']['send_stop'], 'send') ?>><?php echo $send ?></option>
            <option value="stop" <?php selected($form_action->post_content['conditions']['send_stop'], 'stop') ?>><?php echo $stop ?></option>
        </select>
        <?php echo $this_action_if ?>
        <select name="<?php echo $action_control->get_field_name('conditions') ?>[any_all]">
            <option value="any" <?php selected($form_action->post_content['conditions']['any_all'], 'any') ?>><?php _e('any', 'formidable') ?></option>
            <option value="all" <?php selected($form_action->post_content['conditions']['any_all'], 'all') ?>><?php _e('all', 'formidable') ?></option>
        </select>
        <?php _e('of the following match', 'formidable') ?>:
        </p>

<?php

foreach ( $form_action->post_content['conditions'] as $meta_name => $condition ) {
    if ( ! is_numeric($meta_name) ) {
        continue;
    }

    FrmProFormsController::include_logic_row( array(
        'meta_name' => $meta_name,
        'condition' => $condition,
        'key'       => $action_key,
        'form_id'   => $values['id'],
        'name'      => $action_control->get_field_name('conditions') .'['. $meta_name .']',
        'exclude_fields' => FrmFieldsHelper::no_save_fields(),
    ) );

    unset($meta_name, $condition);
}

?>
    </div>
</div>
