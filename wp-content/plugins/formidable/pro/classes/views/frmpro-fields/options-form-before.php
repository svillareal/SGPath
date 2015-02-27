<?php
if ( 'data' != $field['type'] || ! $form_list ) {
    return;
} ?>
<div class="frm-show-click" style="margin:7px 0 5px;">
<?php _e('Import Options From', 'formidable') ?>:
<select name="frm_tax_entry_field_<?php echo $field['id'] ?>" id="frm_tax_entry_field_<?php echo $field['id'] ?>" class="frm_tax_form_select">
    <option value=""><?php _e('&mdash; Select &mdash;', 'formidable') ?></option>
    <option value="form" <?php echo ( is_object($selected_field) ) ? 'selected="selected"' : ''; ?>><?php _e('Entries from a form field', 'formidable') ?></option>
    <option value="taxonomy" <?php
        if ( !is_object($selected_field) ) {
            selected($selected_field, 'taxonomy');
        }
    ?>><?php _e('Category/Taxonomy', 'formidable') ?></option>
</select>

<span id="frm_show_selected_forms_<?php echo $field['id'] ?>" class="<?php echo is_object($selected_field) ? '' : 'frm_hidden' ?>">
<select class="frm_options_field_<?php echo $field['id'] ?> frm_get_field_selection" id="frm_options_field_<?php echo $field['id'] ?>">
    <option value="">&mdash; <?php _e('Select Form', 'formidable') ?> &mdash;</option>
    <?php foreach ( $form_list as $form_opts ) {
        $selected = (is_object($selected_field) && $form_opts->id == $selected_field->form_id) ? ' selected="selected"' : ''; ?>
    <option value="<?php echo $form_opts->id ?>"<?php echo $selected ?>><?php echo FrmAppHelper::truncate($form_opts->name, 30) ?></option>
    <?php } ?>
</select>
</span>

<span id="frm_show_selected_fields_<?php echo $field['id'] ?>">
    <?php
    if ( is_object($selected_field) ) {
        include(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-fields/field-selection.php');
    } else if ( $selected_field == 'taxonomy') { ?>
        <span class="howto"><?php _e('Select a taxonomy on the Post tab of the Form Settings page', 'formidable'); ?></span>
        <input type="hidden" name="field_options[form_select_<?php echo $current_field_id ?>]" value="taxonomy" />
    <?php
    }
    ?>
</span>
</div>
