    <table class="form-table">
        <tr>
            <td>
                <p><label for="options_registration"><input type="checkbox" name="options[registration]" value="1" id="options_registration" <?php checked($values['registration'], 1); ?> onclick="frm_show_div('hide_registration',this.checked,1,'.')"/> <?php _e('Register users who submit this form', 'frmreg') ?></label></p>
            </td>
        </tr>
        
        <tr class="hide_registration" <?php echo $hide_registration = ($values['registration']) ? '' : 'style="display:none;"' ?>>
            <td>
                <label for="options_login"><input type="checkbox" name="options[login]" value="1" id="options_login" <?php checked($values['login'], 1); ?> /> <?php _e('Automatically log in users who submit this form', 'frmreg') ?></label>
            </td>
        </tr>
        
        <tr class="hide_registration" <?php echo $hide_registration ?>>
            <td>
            <p><label class="frm_left_label"><?php _e('User Email', 'frmreg') ?> <span class="frm_required">*</span></label>
                <select name="options[reg_email]">
                <?php 
                $email_field = false;
                if(isset($fields) and is_array($fields)){
                    foreach($fields as $field){ 
                        if($field->type == 'email'){ 
                            $email_field = true; ?>
                    <option value="<?php echo $field->id ?>" <?php selected($values['reg_email'], $field->id) ?>><?php echo substr(esc_attr(stripslashes($field->name)), 0, 50);
                    unset($field); 
                    ?></option>
                    <?php 
                        }
                    }
                }
                
                if(!$email_field){ ?>
                <option value=""><?php _e('You need an "email" field type in your form', 'frmreg') ?></option>
                <?php    
                }
                ?>
                </select>
            </p>

            <p><label class="frm_left_label"><?php _e('Username') ?></label>
            <select name="options[reg_username]">
                <option value=""><?php _e('Automatically Generate from Email', 'frmreg') ?></option>
                <option value="-1" <?php selected($values['reg_username'], '-1') ?>><?php _e('Use Full Email Address', 'frmreg') ?></option>
                <?php 
                if(isset($fields) and is_array($fields)){
                    foreach($fields as $field){ 
                        if($field->type == 'text'){ ?>
                    <option value="<?php echo $field->id ?>" <?php selected($values['reg_username'], $field->id) ?>><?php echo substr(esc_attr(stripslashes($field->name)), 0, 50);
                    unset($field); 
                    ?></option>
                    <?php 
                        }
                    }
                }
                ?>
                </select>
            </p>

            <p><label class="frm_left_label"><?php _e('Password') ?></label>
                <select name="options[reg_password]">
                <option value=""><?php _e('Automatically Generate', 'frmreg') ?></option>
                <?php 
                if(isset($fields) and is_array($fields)){
                    foreach($fields as $field){ 
                        if(in_array($field->type, array('text', 'password'))){ ?>
                    <option value="<?php echo $field->id ?>" <?php selected($values['reg_password'], $field->id) ?>><?php echo substr(esc_attr(stripslashes($field->name)), 0, 50);
                    unset($field); 
                    ?></option>
                    <?php 
                        }
                    }
                }
                ?>
                </select>
            </p>
            
            <p><label class="frm_left_label"><?php _e('First Name') ?></label>
                <select name="options[reg_first_name]">
                <option value="">- <?php _e('None') ?> -</option>
                <?php 
                if(isset($fields) and is_array($fields)){
                    foreach($fields as $field){ 
                        if($field->type == 'text'){ ?>
                    <option value="<?php echo $field->id ?>" <?php selected($values['reg_first_name'], $field->id) ?>><?php echo substr(esc_attr(stripslashes($field->name)), 0, 50);
                    unset($field); 
                    ?></option>
                    <?php 
                        }
                    }
                }
                ?>
                </select>
            </p>    
            
            <p><label class="frm_left_label"><?php _e('Last Name') ?></label>
                <select name="options[reg_last_name]">
                    <option value="">- <?php _e('None') ?> -</option>
                    <?php 
                    if(isset($fields) and is_array($fields)){
                        foreach($fields as $field){ 
                            if($field->type == 'text'){ ?>
                        <option value="<?php echo $field->id ?>" <?php selected($values['reg_last_name'], $field->id) ?>><?php echo substr(esc_attr(stripslashes($field->name)), 0, 50);
                        unset($field); 
                        ?></option>
                        <?php 
                            }
                        }
                    }
                    ?>
                </select>
            </p>

            <p><label class="frm_left_label"><?php _e('Display Name', 'frmreg') ?></label>
                
                <select name="options[reg_display_name]">
                <option value=""><?php _e('Same as Username', 'frmreg') ?></option>
                <option value="display_firstlast" <?php selected($values['reg_display_name'], 'display_firstlast') ?>><?php _e('First Last (as selected above)', 'frmreg') ?></option>
                <option value="display_lastfirst" <?php selected($values['reg_display_name'], 'display_lastfirst') ?>><?php _e('Last First (as selected above)', 'frmreg') ?></option>
                <?php 
                if(isset($fields) and is_array($fields)){
                    foreach($fields as $field){ 
                        if($field->type == 'text'){ ?>
                    <option value="<?php echo $field->id ?>" <?php selected($values['reg_display_name'], $field->id) ?>><?php echo substr(esc_attr(stripslashes($field->name)), 0, 50);
                    ?></option>
                    <?php
                        }
                        unset($field);
                    }
                }
                ?>
                </select>
            </p>

            <p><label class="frm_left_label"><?php _e('User Role', 'frmreg') ?></label>
                <?php FrmAppHelper::wp_roles_dropdown('options[reg_role]', $values['reg_role']) ?>
            </p>
            
            <p><label class="frm_left_label"><?php _e('Avatar', 'frmreg') ?> <span class="frm_help frm_icon_font frm_tooltip_icon" title="<?php esc_attr_e('Only select an avatar if you have not set it in another form. Only file upload fields will show here.', 'frmreg') ?>" ></span></label>
                <select name="options[reg_avatar]">
                <option value="">- <?php _e('None') ?> -</option>
                <?php
                if ( isset($fields) && is_array($fields) ) {
                    foreach ( $fields as $field ) { 
                        if ( $field->type == 'file' ) { ?>
                    <option value="<?php echo $field->id ?>" <?php selected($values['reg_avatar'], $field->id) ?>><?php echo substr(esc_attr(stripslashes($field->name)), 0, 50);
                    ?></option>
                    <?php 
                        }
                        unset($field);
                    }
                }
                ?>
                </select>
            </p>
                
            <h4><?php _e('User Meta', 'frmreg') ?></h4>
                <div id="frm_usermeta_rows">
                <?php foreach($values['reg_usermeta'] as $meta_name => $field_id){
                    include(FrmRegAppHelper::path() .'/views/_usermeta_row.php');
                    unset($meta_name);
                    unset($field_id);
                } ?>
                </div>
                <p>
                    <a href="javascript:frm_add_usermeta_row();" class="button frm_add_meta_link" <?php echo (!isset($values['reg_usermeta']) || empty($values['reg_usermeta'])) ? '' : ' style="display:none"'; ?>>+ <?php _e('Add') ?></a></p>
                </p>

        <h4><?php _e('User Moderation', 'frmreg') ?></h4>
        <div class="frm_user_moderation">
            <div>
                <label class="frm_left_label" <?php FrmRegAppHelper::maybe_add_tooltip('mod_email'); ?>>
                    <input type="checkbox" name="options[reg_moderate][]" value="email" <?php FrmRegAppHelper::array_checked($values['reg_moderate'], 'email'); ?> /> <?php _e('Email confirmation', 'frmreg') ?>
                </label>
                <label <?php FrmRegAppHelper::maybe_add_tooltip('mod_redirect'); ?>><?php _e('Redirect to:', 'frmreg') ?></label>
                            <?php FrmAppHelper::wp_pages_dropdown( 'options[reg_redirect]', $values['reg_redirect'] ) ?>
            </div>
            <?php if ( class_exists( 'FrmPaymentsController' ) ) {?>
            <div style="display:none;">
                <label <?php FrmRegAppHelper::maybe_add_tooltip('mod_paypal'); ?>>
                    <input type="checkbox" name="options[reg_moderate][]" value="paypal" <?php FrmRegAppHelper::array_checked($values['reg_moderate'], 'paypal'); ?> /> <?php _e('Complete Paypal payment', 'frmreg') ?>
                </label>
            </div>
            <?php }//end if ?>
        </div>
                
        <h4><?php _e('Email Notification', 'frmreg') ?></h4>
        <div class="frm_email_reply_container">
        <label class="frm_left_label"><?php _e('From/Reply to', 'formidable') ?></label>
        <span class="howto"><?php _e('Name') ?></span> 
        <input type="text" name="options[reg_email_from]" id="reg_email_from" value="<?php echo esc_attr($values['reg_email_from']); ?>" class="frm_not_email_subject" style="width:150px;" />
        
        <span class="howto" ><?php _e('Email', 'formidable') ?></span>
        <input type="text" name="options[reg_email_sender]" id="reg_email_sender" value="<?php echo esc_attr($values['reg_email_sender']); ?>" class="frm_not_email_subject" style="width:150px;" />
        </div>
        
        <p><label><?php _e('Subject', 'formidable') ?></label>
        <input type="text" name="options[reg_email_subject]" id="reg_email_subject" value="<?php echo esc_attr($values['reg_email_subject']); ?>" class="frm_not_email_subject frm_long_input" /></p>
        
        <p><label><?php _e('Message', 'formidable') ?></label><br/>       
            <textarea name="options[reg_email_msg]" id="reg_email_msg" class="frm_not_email_message frm_long_input" rows="5"><?php echo esc_html($values['reg_email_msg']); ?></textarea><br/>
            <span class="howto">You can also use [username] and [password]</span>
        </p>
            </td>
        </tr>
        
    </table>

<script type="text/javascript">
function frm_add_usermeta_row(){
    jQuery.ajax({
        type:"POST",url:ajaxurl,
        data:"action=frm_add_usermeta_row&form_id=<?php echo $values['id'] ?>&meta_name="+jQuery('#frm_usermeta_rows > div').size(),
        success:function(html){
            jQuery('#frm_usermeta_rows').append(html);
            jQuery('.hide_registration .frm_add_meta_link').hide();
        }
    });
}
</script>