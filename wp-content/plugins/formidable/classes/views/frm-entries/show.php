<div id="form_show_entry_page" class="wrap">
    <div class="frmicon icon32"><br/></div>
    <h2><?php _e('View Entry', 'formidable') ?>
        <?php do_action('frm_entry_inside_h2', $entry->form_id); ?>
    </h2>

    <div class="frm_forms">

        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">
                <?php FrmAppController::get_form_nav($entry->form_id, true); ?>
                <div class="postbox">
                    <h3 class="hndle"><span><?php _e('Entry', 'formidable') ?></span></h3>
                    <div class="inside">
                        <table class="form-table"><tbody>
                        <?php
                        $first_h3 = 'frm_first_h3';
                        $embedded_field_id = 0;
                        foreach ( $fields as $field ) {
                            if ( in_array($field->type, array('divider', 'end_divider')) ) {
                                $embedded_field_id = 0;
                            }

                            if ( in_array($field->type, array('captcha', 'html', 'end_divider')) ) {
                                continue;
                            }

                            if ( in_array($field->type, array('form', 'divider')) && isset($field->field_options['form_select']) && ! empty($field->field_options['form_select']) ) {
                                $embedded_field_id = $field->type == 'form' ? '' : 'form';
                                $embedded_field_id .= $field->field_options['form_select'];
                            }

                            if ( in_array($field->type, array('break', 'divider') ) ) {
                            ?>
                        </tbody></table>
                        <br/><h3 class="<?php echo $first_h3 ?>"><?php echo $field->name ?></h3>
                        <table class="form-table"><tbody>
                        <?php
                                $first_h3 = '';
                            } else {
                        ?>
                        <tr>
                            <th scope="row"><?php echo $field->name ?>:</th>
                            <td>
                            <?php
                            $atts = array(
                                'type' => $field->type, 'post_id' => $entry->post_id,
                                'show_filename' => true, 'show_icon' => true, 'entry_id' => $entry->id,
                                'embedded_field_id' => $embedded_field_id,
                            );
                            echo $display_value = FrmEntriesHelper::prepare_display_value($entry, $field, $atts);

                            if ( is_email($display_value) && ! in_array($display_value, $to_emails) ) {
                                $to_emails[] = $display_value;
                            }
                            ?>
                            </td>
                        </tr>
                        <?php }
                        }

                        ?>

                        <?php if ( $entry->parent_item_id ) { ?>
                        <tr><th><?php _e('Parent Entry ID', 'formidable') ?>:</th>
                            <td><?php echo $entry->parent_item_id ?>
                        </td></tr>
                        <?php } ?>
                        </tbody></table>
                        <?php do_action('frm_show_entry', $entry); ?>
                    </div>
                </div>

                <?php do_action('frm_after_show_entry', $entry); ?>

            </div>
            <?php require(FrmAppHelper::plugin_path() .'/classes/views/frm-entries/sidebar-show.php'); ?>
            </div>
        </div>
    </div>
</div>
<br/>