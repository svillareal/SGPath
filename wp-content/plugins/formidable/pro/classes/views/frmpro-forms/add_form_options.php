<h3><?php _e( 'Permissions', 'formidable' ) ?>
	<span class="frm_help frm_icon_font frm_tooltip_icon" title="<?php esc_attr_e( 'Determine who can see, submit, and edit form entries.', 'formidable' ) ?>"></span>
</h3>
<table class="form-table">
<tr>
    <td style="width:300px;">
        <label for="logged_in">
            <input type="checkbox" name="logged_in" id="logged_in" value="1" <?php checked($values['logged_in'], 1); ?> /> <?php printf(__( 'Limit form visibility and submission %1$sto:%2$s', 'formidable' ), '<span class="hide_logged_in" '. ($values['logged_in'] ? '' : 'style="visibility:hidden;"') .'>', '</span>') ?>
        </label>
    </td>
    <td class="td_select_padding">
        <select name="options[logged_in_role]" id="logged_in_role" class="hide_logged_in" <?php echo $values['logged_in'] ? '' : 'style="visibility:hidden;"'; ?>>
            <option value=""><?php _e( 'Logged-in Users', 'formidable' ) ?></option>
            <?php FrmAppHelper::roles_options($values['logged_in_role']); ?>
        </select>
    </td>
</tr>

<tr>
    <td>
        <label for="single_entry"><input type="checkbox" name="options[single_entry]" id="single_entry" value="1" <?php checked($values['single_entry'], 1); ?> /> <?php printf(__( 'Limit number of form entries %1$sto one per:%2$s', 'formidable' ), '<span class="hide_single_entry"' . ( $values['single_entry'] ? '' : ' style="visibility:hidden;"' ) . '>', '</span>') ?></label>
    </td>
    <td class="td_select_padding">
        <select name="options[single_entry_type]" id="frm_single_entry_type" class="hide_single_entry" <?php echo $values['single_entry'] ? '' : 'style="visibility:hidden;"'; ?>>
            <option value="user" <?php selected($values['single_entry_type'], 'user') ?>><?php _e( 'Logged-in User', 'formidable' ) ?></option>
            <option value="ip" <?php selected($values['single_entry_type'], 'ip') ?>><?php _e( 'IP Address', 'formidable' ) ?></option>
            <option value="cookie" <?php selected($values['single_entry_type'], 'cookie') ?>><?php _e( 'Saved Cookie', 'formidable' ) ?></option>
        </select>
    </td>
</tr>
<tr id="frm_cookie_expiration" class="frm_short_tr <?php echo ($values['single_entry'] && $values['single_entry_type'] == 'cookie') ? '' : 'frm_hidden' ?>">
    <td colspan="2">
        <p class="frm_indent_opt">
            <label><?php _e( 'Cookie Expiration', 'formidable' ) ?></label>
            <input type="text" name="options[cookie_expiration]" value="<?php echo esc_attr($values['cookie_expiration']) ?>"/> <span class="howto"><?php _e( 'hours', 'formidable' ) ?></span>
        </p>
    </td>
</tr>

<tr>
    <td colspan="2">
        <label for="editable"><input type="checkbox" name="editable" id="editable" value="1"<?php echo ($values['editable']) ? ' checked="checked"' : ''; ?> /> <?php _e( 'Allow front-end editing of entries', 'formidable' ) ?></label>
    </td>
</tr>

<tr class="hide_editable frm_short_tr">
    <td>
        <p class="frm_indent_opt"><label for="editable_role"><?php _e( 'Role required to edit one\'s own entries:', 'formidable' ) ?></label></p>
    </td>
    <td>
        <select name="options[editable_role]" id="editable_role">
            <option value=""><?php _e( 'Logged-in Users', 'formidable' ) ?></option>
            <?php FrmAppHelper::roles_options($values['editable_role']); ?>
        </select>
    </td>
</tr>

<?php
if ( isset( $values['open_editable'] ) && empty( $values['open_editable'] ) ) {
    $values['open_editable_role'] = '-1';
}
?>
<tr class="hide_editable frm_short_tr">
    <td><p class="frm_indent_opt"><label for="open_editable_role"><?php _e( 'Role required to edit other users\' entries:', 'formidable' ) ?></label></p></td>
    <td>
        <select name="options[open_editable_role]" id="open_editable_role">
            <option value="-1"></option>
            <option value="" <?php selected($values['open_editable_role'], ''); ?>><?php _e( 'Logged-in Users', 'formidable' ) ?></option>
            <?php FrmAppHelper::roles_options($values['open_editable_role']); ?>
        </select>
    </td>
</tr>
<tr class="hide_editable frm_short_tr">
    <td colspan="2">
        <div class="frm_indent_opt"><label><?php _e( 'On Update:', 'formidable' ) ?></label></div>
        <span class="frm_indent_opt alignleft" style="width:200px;">
            <select name="options[edit_action]" id="edit_action">
                <option value="message" <?php selected($values['edit_action'], 'message') ?>><?php _e( 'Show Message', 'formidable' )?></option>
                <option value="redirect" <?php selected($values['edit_action'], 'redirect') ?>><?php _e( 'Redirect to URL') ?></option>
                <option value="page" <?php selected($values['edit_action'], 'page') ?>><?php _e( 'Show Page Content', 'formidable' )?></option>
            </select>
        </span>
        <span class="edit_action_redirect_box edit_action_box" <?php echo ($values['edit_action'] == 'redirect') ? '' : 'style="display:none;"'; ?>>
            <input type="text" name="options[edit_url]" id="edit_url" value="<?php if (isset($values['edit_url'])) echo esc_attr($values['edit_url']); ?>" style="width:61%" placeholder="http://example.com" />
        </span>

        <span class="edit_action_page_box edit_action_box" <?php echo ($values['edit_action'] == 'page') ? '' : 'style="display:none;"'; ?>>
            <label><?php _e( 'Use Content from Page', 'formidable' ) ?></label>
            <?php FrmAppHelper::wp_pages_dropdown( 'options[edit_page_id]', $values['edit_page_id'] ) ?>
        </span>
    </td>
</tr>

<tr>
    <td colspan="2"><label for="save_draft"><input type="checkbox" name="options[save_draft]" id="save_draft" value="1"<?php echo ($values['save_draft']) ? ' checked="checked"' : ''; ?> /> <?php _e( 'Allow logged-in users to save drafts', 'formidable' ) ?></label>
    </td>
</tr>
<?php if (is_multisite()){ ?>
    <?php if (is_super_admin()){ ?>
        <tr><td colspan="2">
        <label for="copy"><input type="checkbox" name="options[copy]" id="copy" value="1" <?php echo ($values['copy'])? ' checked="checked"' : ''; ?> /> <?php _e( 'Copy this form to other blogs when Formidable Forms is activated', 'formidable' ) ?></label></td></tr>
    <?php }else if ($values['copy']){ ?>
        <input type="hidden" name="options[copy]" id="copy" value="1" />
    <?php }
    }
