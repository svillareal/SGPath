<div id="frm_usermeta_<?php echo $meta_name ?>" class="frm_usermeta_row">
    <label class="frm_left_label" style="width:60px;min-width:0;"><?php _e('Name') ?></label>
    <input type="text" value="<?php echo (isset($echo) && $echo ) ? esc_attr($meta_name) : '' ?>" name="options[reg_usermeta][meta_name][<?php echo $meta_name ?>]"/>

    <?php _e('Form Field', 'formidable') ?>
    <select name="options[reg_usermeta][field_id][<?php echo $meta_name ?>]">
        <option value="">- <?php _e('Select Field', 'frmreg') ?> -</option>
            <?php
            if(isset($fields) and is_array($fields)){
                foreach($fields as $field){ ?>
                    <option value="<?php echo $field->id ?>" <?php selected($field_id, $field->id) ?>><?php echo substr(esc_attr(stripslashes($field->name)), 0, 50);
                    unset($field);
                    ?></option>
                <?php
                }
            }
            ?>
    </select>
    <a class="frm_remove_tag frm_icon_font" data-removeid="frm_usermeta_<?php echo $meta_name ?>" data-showlast=".hide_registration .frm_add_meta_link"></a>
    <a class="frm_add_tag frm_icon_font" href="javascript:frm_add_usermeta_row();"></a>
</div>