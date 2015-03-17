<div class="postbox" id="frm_comment_list">
    <h3 class="hndle"><span><?php _e( 'Comments/Notes', 'formidable' ) ?></span></h3>
    <div class="inside">
        <table class="form-table"><tbody>
        <?php foreach ( $comments as $comment ) {
            $meta = $comment->meta_value;
            if ( ! isset($meta['comment']) ) {
                continue;
            }
        ?>
            <tr class="frm_comment_block">
                <th scope="row"><p><strong><?php echo FrmProFieldsHelper::get_display_name($meta['user_id'], 'display_name', array( 'link' => true)) ?></strong><br/>
                    <?php echo FrmAppHelper::get_formatted_time($comment->created_at, $date_format, $time_format)  ?></p>
                </th>
                <td><div class="frm_comment"><?php echo wpautop(strip_tags($meta['comment'])) ?></div></td>
            </tr>
        <?php } ?>
        </table>
        <a href="javascript:void(0)" onclick="jQuery('#frm_comment_form').toggle('slow');" class="button-secondary alignright">+ <?php _e( 'Add Note/Comment', 'formidable' ) ?></a>
        <div class="clear"></div>

        <form name="frm_comment_form" id="frm_comment_form" method="post" style="display:none;">
            <input type="hidden" name="frm_action" value="show" />
            <input type="hidden" name="field_id" value="0" />
            <input type="hidden" name="item_id" value="<?php echo (int) $entry->id ?>" />
            <?php wp_nonce_field('add-option'); ?>

            <table class="form-table"><tbody>
                <tr>
                    <th scope="row"><?php _e( 'Comment/Note', 'formidable' ) ?>:</th>
                    <td><textarea name="frm_comment" id="frm_comment" cols="50" rows="5" style="width:98%"> </textarea>
                    <!--
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e( 'Send Emails to', 'formidable' ) ?>:</th>
                    <td>
                        <input type="text" name="frm_send_to[]" value="" class="frm_long_input"/><br/>
                        <?php foreach($to_emails as $to_email){ ?>
                        <input type="checkbox" name="frm_send_to[]" value="<?php echo esc_attr($to_email) ?>"/>  <?php echo esc_html( $to_email ) ?><br/>
                        <?php } ?>
                        -->
                        <p class="submit">
							<input class="button-primary" type="submit" value="<?php esc_attr_e( 'Submit', 'formidable' ) ?>" />
                        </p>
                    </td>
                </tr>

            </tbody></table>
        </form>
    </div>
</div>

<?php if ( $_POST && isset($_POST['frm_comment']) ) { ?>
<script type="text/javascript">frmFrontForm.scrollToID('frm_comment_list');</script>
<?php }