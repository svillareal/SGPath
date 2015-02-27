<div class="frm_registration_settings">
    <table class="form-table">
        <tr>
            <th>
                <label><?php _e('User Email', 'frmreg') ?> <span class="frm_required">*</span></label>
            </th>
            <td>
                <select name="<?php echo $this->get_field_name('reg_email') ?>">
                <?php 
                $email_field = false;
                if(isset($fields) and is_array($fields)){
                    foreach($fields as $field){ 
                        if($field->type == 'email'){ 
                            $email_field = true; ?>
                    <option value="<?php echo $field->id ?>" <?php selected($form_action->post_content['reg_email'], $field->id) ?>><?php echo substr(esc_attr(stripslashes($field->name)), 0, 50);
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
            </td>
        </tr>

        <tr>
            <th>
                <label><?php _e('Username') ?></label>
            </th>
            <td>
                <select name="<?php echo $this->get_field_name('reg_username') ?>">
                    <option value=""><?php _e('Automatically Generate from Email', 'frmreg') ?></option>
                    <option value="-1" <?php selected($form_action->post_content['reg_username'], '-1') ?>><?php _e('Use Full Email Address', 'frmreg') ?></option>
                    <?php 
                    if(isset($fields) and is_array($fields)){
                        foreach($fields as $field){ 
                            if($field->type == 'text'){ ?>
                        <option value="<?php echo $field->id ?>" <?php selected($form_action->post_content['reg_username'], $field->id) ?>><?php echo substr(esc_attr(stripslashes($field->name)), 0, 50);
                        unset($field); 
                        ?></option>
                        <?php 
                            }
                        }
                    }
                    ?>
                </select>
            </td>
        </tr>

        <tr>
            <th>
                <label><?php _e('Password') ?></label>
            </th>
            <td>
                <select name="<?php echo $this->get_field_name('reg_password') ?>">
                <option value=""><?php _e('Automatically Generate', 'frmreg') ?></option>
                <?php 
                if(isset($fields) and is_array($fields)){
                    foreach($fields as $field){ 
                        if(in_array($field->type, array('text', 'password'))){ ?>
                    <option value="<?php echo $field->id ?>" <?php selected($form_action->post_content['reg_password'], $field->id) ?>><?php echo substr(esc_attr(stripslashes($field->name)), 0, 50);
                    unset($field); 
                    ?></option>
                    <?php 
                        }
                    }
                }
                ?>
                </select>
            </td>
        </tr>

        <tr>
            <th>
                <label><?php _e('First Name') ?></label>
            </th>
            <td>
                <select name="<?php echo $this->get_field_name('reg_first_name') ?>">
                <option value="">- <?php _e('None') ?> -</option>
                <?php 
                if(isset($fields) and is_array($fields)){
                    foreach($fields as $field){ 
                        if($field->type == 'text'){ ?>
                    <option value="<?php echo $field->id ?>" <?php selected($form_action->post_content['reg_first_name'], $field->id) ?>><?php echo substr(esc_attr(stripslashes($field->name)), 0, 50);
                    unset($field); 
                    ?></option>
                    <?php 
                        }
                    }
                }
                ?>
                </select>
            </td>
        </tr>
        
        <tr>   
           <th>
               <label><?php _e('Last Name') ?></label>
           </th>
           <td>
                <select name="<?php echo $this->get_field_name('reg_last_name') ?>">
                    <option value="">- <?php _e('None') ?> -</option>
                    <?php 
                    if(isset($fields) and is_array($fields)){
                        foreach($fields as $field){ 
                            if($field->type == 'text'){ ?>
                        <option value="<?php echo $field->id ?>" <?php selected($form_action->post_content['reg_last_name'], $field->id) ?>><?php echo substr(esc_attr(stripslashes($field->name)), 0, 50);
                        unset($field); 
                        ?></option>
                        <?php 
                            }
                        }
                    }
                    ?>
                </select>
            </td>
        </tr>
        
        <tr>
            <th>
                <label><?php _e('Display Name', 'frmreg') ?></label>
            </th>
            <td>
                <select name="<?php echo $this->get_field_name('reg_display_name') ?>">
                <option value=""><?php _e('Same as Username', 'frmreg') ?></option>
                <option value="display_firstlast" <?php selected($form_action->post_content['reg_display_name'], 'display_firstlast') ?>><?php _e('First Last (as selected above)', 'frmreg') ?></option>
                <option value="display_lastfirst" <?php selected($form_action->post_content['reg_display_name'], 'display_lastfirst') ?>><?php _e('Last First (as selected above)', 'frmreg') ?></option>
                <?php 
                if(isset($fields) and is_array($fields)){
                    foreach($fields as $field){ 
                        if($field->type == 'text'){ ?>
                    <option value="<?php echo $field->id ?>" <?php selected($form_action->post_content['reg_display_name'], $field->id) ?>><?php echo substr(esc_attr(stripslashes($field->name)), 0, 50);
                    ?></option>
                    <?php
                        }
                        unset($field);
                    }
                }
                ?>
                </select>
            </td>
        </tr>
        
        <tr>
            <th>
                <label><?php _e('User Role', 'frmreg') ?></label>
            </th>
            <td>
                <?php FrmAppHelper::wp_roles_dropdown($this->get_field_name('reg_role'), $form_action->post_content['reg_role']); ?>
            </td>
        </tr>
            
        <tr>
            <th>
                <label><?php _e('Avatar', 'frmreg') ?> <span class="frm_help frm_icon_font frm_tooltip_icon" title="<?php esc_attr_e('Only select an avatar if you have not set it in another form. Only file upload fields will show here.', 'frmreg') ?>" ></span></label>
            </th>
            <td>
                <select name="<?php echo $this->get_field_name('reg_avatar') ?>">
                <option value="">- <?php _e('None') ?> -</option>
                <?php
                if ( isset($fields) && is_array($fields) ) {
                    foreach ( $fields as $field ) { 
                        if ( $field->type == 'file' ) { ?>
                    <option value="<?php echo $field->id ?>" <?php selected($form_action->post_content['reg_avatar'], $field->id) ?>><?php echo substr(esc_attr(stripslashes($field->name)), 0, 50);
                    ?></option>
                    <?php 
                        }
                        unset($field);
                    }
                }
                ?>
                </select>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <label for="options_login"><input type="checkbox" name="<?php echo $this->get_field_name('login') ?>" value="1" id="options_login" <?php checked($form_action->post_content['login'], 1); ?> /> <?php _e('Automatically log in users who submit this form', 'frmreg') ?></label>
            </td>
        </tr>
    </table>

        <!--User Meta-->
        <h3><?php _e('User Meta', 'frmreg') ?></h3>
        <table class="form-table" id="frm_usermeta_rows">
            <?php foreach ( $form_action->post_content['reg_usermeta'] as $meta_key => $usermeta_vars ) {
                $meta_name = $usermeta_vars['meta_name'];
                $field_id = $usermeta_vars['field_id'];
                $echo = true;
                $action_control = $this;
                include(FrmRegAppHelper::path() .'/views/new_usermeta_row.php');
                unset( $meta_name, $field_id, $meta_key );
            } ?>
            <tr class="frm_add_meta_link"><td>
                <a href="javascript:frm_add_usermeta_row();" class="button" <?php echo (!isset($form_action->post_content['reg_usermeta']) || empty($form_action->post_content['reg_usermeta'])) ? '' : ' style="display:none"'; ?>>+ <?php _e('Add') ?></a>
            </td></tr>
        </table>

        <!--User Moderation-->
        <h3><?php _e('User Moderation', 'frmreg') ?></h3>
        <table class="form-table">
            <tr><td width="250px">
                <label <?php FrmRegAppHelper::maybe_add_tooltip('mod_email'); ?>>
                    <input type="checkbox" name="<?php echo $this->get_field_name('reg_moderate') ?>[]" value="email" <?php FrmRegAppHelper::array_checked($form_action->post_content['reg_moderate'], 'email'); ?> /> <?php _e('Email confirmation', 'frmreg') ?>
                </label>
            </td>
            <td style="padding-top:0;">
                <label <?php FrmRegAppHelper::maybe_add_tooltip('mod_redirect'); ?>><?php _e('Redirect to:', 'frmreg') ?></label>
                            <?php FrmAppHelper::wp_pages_dropdown( $this->get_field_name('reg_redirect'), $form_action->post_content['reg_redirect'] ) ?>
            </td></tr>
            <tr class="frm_hidden">
                <td colspan="2">
                <label <?php FrmRegAppHelper::maybe_add_tooltip('mod_admin'); ?>>
                    <input type="checkbox" name="<?php echo $this->get_field_name('reg_moderate') ?>[]" value="admin" <?php FrmRegAppHelper::array_checked($form_action->post_content['reg_moderate'], 'admin'); ?> /> <?php _e('Admin approval', 'frmreg') ?>
                </label>
                </td>
            </tr>
            <?php if ( class_exists( 'FrmPaymentsController' ) ) {?>
            <tr class="frm_hidden">
                <td colspan="2">
                <label <?php FrmRegAppHelper::maybe_add_tooltip('mod_paypal'); ?>>
                    <input type="checkbox" name="<?php echo $this->get_field_name('reg_moderate') ?>[]" value="paypal" <?php FrmRegAppHelper::array_checked($form_action->post_content['reg_moderate'], 'paypal'); ?> /> <?php _e('Complete Paypal payment', 'frmreg') ?>
                </label>
                </td>
            </tr>
            <?php }//end if ?>
        </table>

        <!--Email Notification-->
        <h3><?php _e('Email Notification', 'frmreg') ?></h3>
        <table class="form-table">
            <tr class="frm_email_reply_container">
                <th>
                    <label><?php _e('From/Reply to', 'formidable') ?></label>
                </th>
                <td>
                    <span class="howto"><?php _e('Name') ?></span>
                    <input type="text" name="<?php echo $this->get_field_name('reg_email_from') ?>" id="reg_email_from" value="<?php echo esc_attr($form_action->post_content['reg_email_from']); ?>" class="frm_not_email_subject" style="width:150px;" />
        
                    <span class="howto" ><?php _e('Email', 'formidable') ?></span>
                    <input type="text" name="<?php echo $this->get_field_name('reg_email_sender') ?>" id="reg_email_sender" value="<?php echo esc_attr($form_action->post_content['reg_email_sender']); ?>" class="frm_not_email_subject" style="width:150px;" />
                </td>
            </tr>
        
            <tr>
                <td colspan="2">
                    <label><?php _e('Subject', 'formidable') ?></label>
                    <input type="text" name="<?php echo $this->get_field_name('reg_email_subject') ?>" id="reg_email_subject" value="<?php echo esc_attr($form_action->post_content['reg_email_subject']); ?>" class="frm_not_email_subject frm_long_input" />
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <label><?php _e('Message', 'formidable') ?></label><br/>     
                    <textarea name="<?php echo $this->get_field_name('reg_email_msg') ?>" id="reg_email_msg" class="frm_not_email_message frm_long_input" rows="5"><?php echo esc_html($form_action->post_content['reg_email_msg']); ?></textarea><br/>
                    <span class="howto">You can also use [username] and [password]</span>
                </td>
            </tr>
        </table>

<script type="text/javascript">
function frm_add_usermeta_row(){
    var key = jQuery('.frm_single_register_settings').data('actionkey');
    var meta_name = 0;
    if(jQuery('#frm_usermeta_rows .frm_usermeta_row').length > 0){
        meta_name = 1 + parseInt(jQuery('#frm_usermeta_rows .frm_usermeta_row:last').attr('id').replace('frm_usermeta_', ''));
    }
    jQuery.ajax({
        type:"POST",url:ajaxurl,
        data:"action=frm_add_usermeta_row&form_id=<?php echo $form->id ?>&action_key="+key+"&meta_name="+meta_name,
        success:function(html){
            jQuery('#frm_usermeta_rows').append(html);
            jQuery('.frm_registration_settings .frm_add_meta_link').hide();
        }
    });
}
</script>