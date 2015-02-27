<?php wp_nonce_field('frm_save_display_nonce', 'frm_save_display'); ?>

<table class="form-table frm-no-margin">
    <tr class="limit_container <?php echo ( $post->frm_show_count == 'calendar' || $post->frm_show_count == 'one' ) ? 'frm_hidden' : ''; ?>">
        <td class="frm_left_label">
            <label><?php _e('Limit', 'formidable'); ?>
            <span class="frm_help frm_icon_font frm_tooltip_icon" title="<?php _e('If you don’t want all your entries displayed, you can insert the number limit here. Leave blank if you’d like all entries shown.', 'formidable') ?>" ></span></label>
        </td>
        <td>
            <input type="text" id="limit" name="options[limit]" value="<?php echo esc_attr($post->frm_limit) ?>" size="4" />
        </td>
    </tr>

    <tr class="limit_container <?php echo ( $post->frm_show_count == 'calendar' || $post->frm_show_count == 'one' ) ? 'frm_hidden' : ''; ?>">
        <td>
            <label><?php _e('Page Size', 'formidable'); ?>
            <span class="frm_help frm_icon_font frm_tooltip_icon" title="<?php _e('The number of entries to show per page. Leave blank to not use pagination.', 'formidable') ?>" ></span></label>
        </td>
        <td>
            <input type="text" id="limit" name="options[page_size]" value="<?php echo esc_attr($post->frm_page_size) ?>" size="4" />
        </td>
    </tr>
</table>

<h3><?php _e('Sort & Filter', 'formidable') ?></h3>
<table class="form-table frm-no-margin">
    <tr class="form-field" id="order_by_container">
        <td class="frm_left_label"><?php _e('Order', 'formidable'); ?></td>
        <td>
            <div id="frm_order_options" class="frm_add_remove" style="padding-bottom:8px;">
                <a href="javascript:void(0)" class="frm_add_order_row button" style="<?php echo empty($post->frm_order_by) ? '' : 'display:none'; ?>">+ <?php _e('Add', 'formidable') ?></a>
                <div class="frm_logic_rows">
            <?php
             foreach($post->frm_order_by as $order_key => $order_by_field){
             	if(isset($post->frm_order[$order_key]) and isset($post->frm_order_by[$order_key]))
                	FrmProDisplaysController::add_order_row($order_key, $post->frm_form_id, $order_by_field, $post->frm_order[$order_key]);
                }

            ?>
                </div>
            </div>
        </td>
    </tr>

    <tr class="form-field" id="where_container">
        <td><?php _e('Filter Entries', 'formidable'); ?>
            <span class="frm_help frm_icon_font frm_tooltip_icon" title="<?php _e('Narrow down which entries will be used.', 'formidable') ?>"></span>
        </td>
        <td>
            <div id="frm_where_options" class="frm_add_remove">
                <a href="javascript:void(0)" class="frm_add_where_row button" style="<?php echo empty($post->frm_where) ? '' : 'display:none'; ?>">+ <?php _e('Add', 'formidable') ?></a>
                <div class="frm_logic_rows">
            <?php
                foreach($post->frm_where as $where_key => $where_field){
                    if(isset($post->frm_where_is[$where_key]) and isset($post->frm_where_val[$where_key]))
                        FrmProDisplaysController::add_where_row($where_key, $post->frm_form_id, $where_field, $post->frm_where_is[$where_key], $post->frm_where_val[$where_key]);
                }
            ?>
                </div>
            </div>
        </td>
    </tr>

    <tr class="form-field">
        <td><?php _e('Message if nothing to display', 'formidable'); ?></td>
        <td>
            <textarea id="empty_msg" name="options[empty_msg]" style="width:98%"><?php echo FrmAppHelper::esc_textarea($post->frm_empty_msg) ?></textarea>
        </td>
    </tr>
</table>

<h3><?php _e('Advanced', 'formidable') ?></h3>
<table class="form-table frm-no-margin">
    <tr class="hide_dyncontent <?php echo in_array($post->frm_show_count, array('dynamic', 'calendar')) ? '' : 'frm_hidden'; ?>">
        <td><?php _e('Detail Page Slug', 'formidable'); ?> <span class="frm_help frm_icon_font frm_tooltip_icon" title="<?php printf(__('Example: If parameter name is \'contact\', the url would be like %1$s/selected-page?contact=2. If this entry is linked to a post, the post permalink will be used instead.', 'formidable'), FrmAppHelper::site_url()) ?>" ></span></td>
        <td>
            <?php if (false){ //( FrmProAppHelper::rewriting_on() && $frmpro_settings->permalinks){ ?>
                <select id="type" name="type">
                    <option value="id" <?php selected($post->frm_type, 'id') ?>><?php _e('ID', 'formidable'); ?></option>
                    <option value="display_key" <?php selected($post->frm_type, 'display_key') ?>><?php _e('Key', 'formidable'); ?></option>
                </select>
                <p class="description"><?php printf(__('Select the value that will be added onto the page URL. This will create a pretty URL like %1$s/selected-page/entry-key', 'formidable'), FrmAppHelper::site_url()); ?></p>
            <?php }else{ ?>
                <input type="text" id="param" name="param" value="<?php echo esc_attr($post->frm_param) ?>">

                <?php _e('Parameter Value', 'formidable'); ?>:
                <select id="type" name="type">
                    <option value="id" <?php selected($post->frm_type, 'id') ?>><?php _e('ID', 'formidable'); ?></option>
                    <option value="display_key" <?php selected($post->frm_type, 'display_key') ?>><?php _e('Key', 'formidable'); ?></option>
                </select>
            <?php } ?>
        </td>
    </tr>

    <tr>
        <td class="frm_left_label"><?php _e('Insert View', 'formidable'); ?></td>
        <td>
        <p>
            <select id="insert_loc" name="insert_loc">
                <option value="none" <?php selected($post->frm_insert_loc, 'none') ?>><?php _e("Don't insert automatically", 'formidable') ?></option>
                <option value="after" <?php selected($post->frm_insert_loc, 'after') ?>><?php _e('After page content', 'formidable') ?></option>
                <option value="before" <?php selected($post->frm_insert_loc, 'before') ?>><?php _e('Before page content', 'formidable') ?></option>
                <option value="replace" <?php selected($post->frm_insert_loc, 'replace') ?>><?php _e('Replace page content', 'formidable') ?></option>
            </select>


            <label for="insert_pos"><?php _e('Insert priority', 'formidable'); ?> <span class="frm_help frm_icon_font frm_tooltip_icon" title="<?php _e('If the view doesn\'t show automatically when it should, insert a higher number here.', 'formidable') ?>" ></span></label>
            <input type="number" id="insert_pos" name="options[insert_pos]" min="1" max="15" step="1" size="4" value="<?php echo esc_attr( $post->frm_insert_pos ); ?>" style="float:none;"/>
        </p>
        <span id="post_select_container" <?php echo ($post->frm_insert_loc == 'none') ? ' class="frm_hidden"' : ''; ?>>
            <?php _e('on page', 'formidable'); ?>
            <?php FrmAppHelper::wp_pages_dropdown( 'post_id', $post->frm_post_id, 35 ); ?>
            <span class="frm_help frm_icon_font frm_tooltip_icon" title="<?php _e('If you would like the content to be inserted automatically, you must then select the page in which to insert it.', 'formidable') ?>" ></span>
        </span>
        <?php if($post->frm_insert_loc != 'none' and is_numeric($post->frm_post_id)){ ?>
        <a href="<?php echo get_permalink($post->frm_post_id) ?>" target="_blank" class="button-secondary"><?php _e('View Post', 'formidable') ?></a>
        <?php } ?>
        </td>
    </tr>
    <?php if (is_multisite()){
        if (is_super_admin()){ ?>
        <tr class="form-field">
            <td><?php _e('Copy', 'formidable'); ?></td>
            <td>
                <label for="copy"><input type="checkbox" id="copy" name="options[copy]" value="1" <?php checked($post->frm_copy, 1) ?> />
                <?php _e('Copy these display settings to other blogs when Formidable Pro is activated. <br/>Note: Use only field keys in the content box(es) above.', 'formidable') ?></label>
            </td>
        </tr>
        <?php }else if ($post->frm_copy){ ?>
        <input type="hidden" id="copy" name="options[copy]" value="1" />
        <?php }
    } ?>

</table>
