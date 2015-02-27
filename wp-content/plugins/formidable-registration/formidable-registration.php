<?php
/*
Plugin Name: Formidable Registration
Plugin URI: http://formidablepro.com/knowledgebase/formidable-registration/
Description: Register users through a Formidable form
Author: Strategy11
Author URI: http://strategy11.com
Version: 1.11.03
Text Domain: frmreg
*/

function frmreg_forms_autoloader($class_name) {
    // Only load FrmReg classes here
    if ( ! preg_match('/^FrmReg.+$/', $class_name) ) {
        return;
    }

    $filepath = dirname(__FILE__);

    if ( ! preg_match('/^.+Helper$/', $class_name) && ! preg_match('/^.+Controller$/', $class_name) ) {
        $filepath .= '/models';
    }

    $filepath .= '/'. $class_name .'.php';

    if ( file_exists($filepath) ) {
        include($filepath);
    }
}

// if __autoload is active, put it on the spl_autoload stack
if ( is_array(spl_autoload_functions()) && in_array('__autoload', spl_autoload_functions()) ) {
    spl_autoload_register('__autoload');
}

// Add the autoloader
spl_autoload_register('frmreg_forms_autoloader');

FrmRegAppController::load_hooks();
