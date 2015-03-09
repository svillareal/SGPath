<?php
// Check if field is read only
$disabled = ( $field['read_only'] && ( ! isset($frm_vars['readonly']) || $frm_vars['readonly'] != 'disabled' ) && ! FrmAppHelper::is_admin() ) ?  ' disabled="disabled"' : '';

// Dynamic Dropdowns
if ($field['data_type'] == 'select'){
    if(!empty($field['options'])){ ?>
<select <?php echo $disabled ?> name="<?php echo esc_attr( $field_name ) ?>" id="<?php echo esc_attr( $html_id ) ?>" <?php do_action('frm_field_input_html', $field) ?>>
<?php
        if ($field['options']){
            foreach ($field['options'] as $opt_key => $opt){
$selected = ( $field['value'] == $opt_key || in_array($opt_key, (array) $field['value']) ) ? ' selected="selected"' : ''; ?>
<option value="<?php echo esc_attr( $opt_key ) ?>"<?php echo $selected ?>><?php echo ($opt == '') ? ' ' : esc_html( $opt ); ?></option>
<?php       }
        } ?>
</select>
<?php
    }

    if ( ( empty($field['options']) || !empty( $disabled ) ) && !empty($field['value']) ) {
        if(is_array($field['value'])){
            foreach($field['value'] as $v){ ?>
<input name="<?php echo esc_attr( $field_name ) ?>" id="<?php echo esc_attr( $html_id ) ?>" type="hidden" value="<?php echo esc_attr( $v ) ?>" />
<?php
                unset($v);
            }
        }else{ ?>
<input name="<?php echo esc_attr( $field_name ) ?>" id="<?php echo esc_attr( $html_id ) ?>" type="hidden" value="<?php echo esc_attr( $field['value'] ) ?>" />
<?php   }
    }
}else if ($field['data_type'] == 'data' && is_numeric($field['hide_opt']) && is_numeric($field['form_select'])){
    echo $value = FrmEntryMeta::get_entry_meta_by_field($field['hide_opt'], $field['form_select']); ?>
    <input type="hidden" value="<?php echo esc_attr( $value ) ?>" name="<?php echo esc_attr( $field_name ) ?>" />
<?php }else if ($field['data_type'] == 'data' && is_numeric($field['hide_field']) && is_numeric($field['form_select'])){
    if (isset($_POST) && isset($_POST['item_meta']))
        $observed_field_val = $_POST['item_meta'][$field['hide_field']];
    else if(isset($_GET) && isset($_GET['id']))
        $observed_field_val = FrmEntryMeta::get_entry_meta_by_field($_GET['id'], $field['hide_field']);

    if(isset($observed_field_val) and is_numeric($observed_field_val))
        $value = FrmEntryMeta::get_entry_meta_by_field($observed_field_val, $field['form_select']);
    else
        $value = '';
?>
<p><?php echo $value ?></p>
<input type="hidden" value="<?php echo esc_attr($value) ?>" name="<?php echo esc_attr( $field_name ) ?>" />
<?php } else if ( $field['data_type'] == 'data' && ! is_array($field['value']) ) { ?>
<p><?php echo $field['value']; ?></p>
<input type="hidden" value="<?php echo esc_attr( $field['value'] ) ?>" name="<?php echo esc_attr( $field_name ) ?>" />
<?php }else if ($field['data_type'] == 'text' && is_numeric($field['form_select'])){
    if (isset($_POST) && isset($_POST['item_meta']))
        $observed_field_val = $_POST['item_meta'][$field['hide_field']];
    else if(isset($_GET) && isset($_GET['id']))
        $observed_field_val = FrmEntryMeta::get_entry_meta_by_field($_GET['id'], $field['hide_field']);

    if(isset($observed_field_val) and is_numeric($observed_field_val))
        $value = FrmEntryMeta::get_entry_meta_by_field($observed_field_val, $field['form_select']);
    else
        $value = '';
?>
<input type="text" value="<?php echo esc_attr( $value ) ?>" name="<?php echo esc_attr( $field_name ) ?>" />

<?php
}else if ($field['data_type'] == 'checkbox'){
    $checked_values = $field['value'];

    if ( ! empty($field['options']) ) {
        foreach ($field['options'] as $opt_key => $opt){
            $checked = ( ( ! is_array($field['value']) && $field['value'] == $opt_key ) || ( is_array($field['value']) && in_array($opt_key, $field['value']) ) ) ? ' checked="true"' : ''; ?>
<div class="<?php echo apply_filters( 'frm_checkbox_class', 'frm_checkbox', $field, $opt_key ) ?>"><label for="<?php echo esc_attr( $html_id .'-'. $opt_key ) ?>"><input type="checkbox" name="<?php echo esc_attr( $field_name ) ?>[]"  id="<?php echo esc_attr( $html_id .'-'. $opt_key ) ?>" value="<?php echo esc_attr( $opt_key ) ?>" <?php
    echo $checked . $disabled .' ';
    do_action( 'frm_field_input_html', $field );
?> /> <?php echo $opt ?></label></div>
<?php   }
    }else if(!empty($field['value'])){
        foreach ( (array) $field['value'] as $v ) { ?>
<input name="<?php echo esc_attr( $field_name ) ?>[]" type="hidden" value="<?php echo esc_attr( $v ) ?>" />
<?php   }
    }//else echo 'There are no options';

}else if ($field['data_type'] == 'radio'){
    if ( ! empty($field['options']) ) {
        foreach ( $field['options'] as $opt_key => $opt ) {
            $checked = ($field['value'] == $opt_key) ? ' checked="checked"' : '';?>
<div class="<?php echo apply_filters('frm_radio_class', 'frm_radio', $field, $opt_key) ?>"><label for="<?php echo esc_attr( $html_id .'-'. $opt_key ) ?>"><input type="radio" name="<?php echo esc_attr( $field_name ) ?>" id="<?php echo esc_attr( $html_id .'-'. $opt_key ) ?>" value="<?php echo esc_attr( $opt_key ) ?>" <?php
    echo $checked . $disabled .' ';
    do_action( 'frm_field_input_html', $field )
?> /> <?php echo $opt ?></label></div>
<?php
        }
    }//else echo 'There are no options'; ?>
<?php
}