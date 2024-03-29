<div id="form_reports_page" class="wrap frm_charts">
    <div class="frmicon icon32"><br/></div>
    <h2><?php _e( 'Reports', 'formidable' ) ?></h2>

	<div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
        <div id="post-body-content">
        <?php
            FrmAppController::get_form_nav($form, true);
            $class = 'odd';
        ?>
        <div class="clear"></div>

        <?php
        if ( isset($data['time']) ) {
            echo $data['time'];
        }

        foreach ( $fields as $field ) {
            if ( ! isset($data[$field->id]) ) {
                continue;
            }

            $total = FrmProFieldsHelper::get_field_stats($field->id, 'count');
            if ( ! $total ) {
                continue;
            }
            ?>
            <div style="margin-top:25px;" class="pg_<?php echo $class ?>">
            <div class="alignleft"><?php echo $data[ $field->id ] ?></div>
            <div style="padding:10px; margin-top:40px;">
                <p><?php _e( 'Response Count', 'formidable' ) ?>: <?php echo FrmProFieldsHelper::get_field_stats($field->id, 'count'); ?></p>
            <?php if ( in_array( $field->type, array( 'number', 'hidden' ) ) ) { ?>
            <p><?php _e( 'Total', 'formidable' ) ?>: <?php echo $total; ?></p>
            <p><?php _e( 'Average', 'formidable' ) ?>: <?php echo FrmProFieldsHelper::get_field_stats($field->id, 'average'); ?></p>
            <p><?php _e( 'Median', 'formidable' ) ?>: <?php echo FrmProFieldsHelper::get_field_stats($field->id, 'median'); ?></p>
            <?php } else if ( $field->type == 'user_id' ) {
                $user_ids = FrmDb::get_col( $wpdb->users, array(), 'ID', 'display_name ASC' );
                $submitted_user_ids = FrmEntryMeta::get_entry_metas_for_field($field->id, '', '', array( 'unique' => true));
                $not_submitted = array_diff($user_ids, $submitted_user_ids); ?>
            <p><?php _e( 'Percent of users submitted', 'formidable' ) ?>: <?php echo round((count($submitted_user_ids) / count($user_ids)) *100, 2) ?>%</p>
            <form action="<?php echo admin_url('user-edit.php') ?>" method="get">
            <p><?php _e( 'Users with no entry:', 'formidable' ) ?><br/>
				<?php wp_dropdown_users( array( 'include' => $not_submitted, 'name' => 'user_id')) ?>
				<input type="submit" name="Go" value="<?php esc_attr_e( 'View Profile', 'formidable' ) ?>" class="button-secondary" />
			</p>
            </form>
            <?php } ?>
            </div>
            <div class="clear"></div>
            </div>
        <?php
            $class = ($class == 'odd') ? 'even' : 'odd';
            unset($field);
        }

        if ( isset($data['month']) ) {
            echo $data['month'];
        }
?>
        </div>
        <div id="postbox-container-1" class="postbox-container">
            <div class="postbox ">
            <div class="handlediv"><br/></div><h3 class="hndle"><span><?php _e( 'Statistics', 'formidable' ) ?></span></h3>
            <div class="inside">
                <div class="misc-pub-section">
                    <?php _e( 'Entries', 'formidable' ) ?>:
                    <b><?php echo count($entries); ?></b>
                    <a href="<?php echo admin_url('admin.php?page=formidable-entries&frm_action=list&form='. $form->id) ?>"><?php _e( 'Browse', 'formidable' ) ?></a>
                </div>
                <?php if (isset($submitted_user_ids) ) { ?>
                <div class="misc-pub-section">
                    <?php _e( 'Users Submitted', 'formidable' ) ?>: <b><?php echo count($submitted_user_ids) ?> (<?php echo round((count($submitted_user_ids) / count($user_ids)) *100, 2) ?>%)</b>
                </div>
                <?php } ?>
            </div>
            </div>
        </div>
        </div>
    </div>
</div>
