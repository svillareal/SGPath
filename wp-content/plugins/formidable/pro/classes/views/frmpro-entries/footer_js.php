
<script type="text/javascript">
/*<![CDATA[*/
<?php
if ( isset($frm_vars['tinymce_loaded']) && $frm_vars['tinymce_loaded'] === true ) {
    echo 'var ajaxurl="'. admin_url( 'admin-ajax.php', 'relative' ) .'";'."\n";
}

if ( isset($frm_vars['rules']) && ! empty($frm_vars['rules']) ) {
    echo '__FRMRULES='. json_encode($frm_vars['rules']) .";\n";
}

if ( isset($frm_vars['google_graphs']) && ! empty($frm_vars['google_graphs']) ) {
    echo '__FRMTABLES='. json_encode($frm_vars['google_graphs']) .";\n";
    echo "google.load('visualization', '1.0', {'packages':". json_encode(array_keys($frm_vars['google_graphs'])) ."});\n";

    foreach ( $frm_vars['google_graphs'] as $k => $v ) {
        foreach ( $v as $num => $opts ) {
            echo "google.setOnLoadCallback(function(){frmFrontForm.generateGoogleTable(". (int) $num .", '". esc_attr($k) ."');});\n";
        }
        unset($k, $v);
    }
}

?>
jQuery(document).ready(function($){
<?php if ( $trigger_form ) { ?>
$(document).off('submit.formidable','.frm-show-form');$(document).on('submit.formidable','.frm-show-form',frmFrontForm.submitForm);
<?php }

FrmProFormsHelper::load_chosen_js($frm_vars);

FrmProFormsHelper::hide_conditional_fields($frm_vars);

$load_lang = array();
FrmProFormsHelper::load_datepicker_js($frm_vars, $load_lang);

FrmProFormsHelper::load_calc_js($frm_vars);

FrmProFormsHelper::load_input_mask_js($frm_input_masks);

?>
});
<?php if ( ! empty($load_lang) ) { ?>
<?php foreach ( $load_lang as $lang ) { ?>
document.write(unescape("%3Cscript src='<?php echo FrmAppHelper::jquery_ui_base_url() ?>/i18n/jquery.ui.datepicker-<?php echo esc_attr( $lang ) ?>.js' type='text/javascript'%3E%3C/script%3E"));
<?php }
} ?>
/*]]>*/
</script>
