<?php

class FrmProSettingsController{

    public static function license_box(){
        global $frm_update;
        $a = isset($_GET['t']) ? $_GET['t'] : 'general_settings';
        remove_action('frm_before_settings', 'FrmSettingsController::license_box');
        include(FrmAppHelper::plugin_path() .'/pro/classes/views/settings/license_box.php');
    }

    public static function general_style_settings($frm_settings){
        include(FrmAppHelper::plugin_path() .'/pro/classes/views/settings/general_style.php');
    }

    public static function more_settings($frm_settings){
        $frmpro_settings = new FrmProSettings();
        require(FrmAppHelper::plugin_path() .'/pro/classes/views/settings/form.php');
    }

    public static function update($params){
        global $frmpro_settings;
        $frmpro_settings = new FrmProSettings();
        $frmpro_settings->update($params);
    }

    public static function store(){
        global $frmpro_settings;
        $frmpro_settings->store();
    }

}
