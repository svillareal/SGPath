<h3 class="frm_first_h3">Global Pages</h3>
<table class="form-table">
    <tr class="form-field" valign="top">
        <td width="150px"><label <?php FrmRegAppHelper::maybe_add_tooltip('login_logout'); ?>><?php _e('Login/Logout URL', 'frmreg') ?></label></td>
    	<td>
            <?php FrmAppHelper::wp_pages_dropdown( 'frm_reg_login', $frm_reg_settings->settings->login ) ?>
				
    	</td>
    </tr>
    
    <tr class="form-field" style="display:none;" valign="top">
        <td><label><?php _e('Lost Password', 'frmreg') ?></label></td>
    	<td>
            <?php FrmAppHelper::wp_pages_dropdown( 'frm_reg_lostpass', $frm_reg_settings->settings->lostpass ) ?>
				
    	</td>
    </tr>
</table>