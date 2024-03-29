<?php
/*
Plugin Name: Users Ultra Lite
Plugin URI: http://usersultra.com
Description: This is a powerful user profiles plugin for WordPress.
Version: 1.4.24
Author: Users Ultra
Author URI: http://usersultra.com
*/

define('xoousers_url',plugin_dir_url(__FILE__ ));
define('xoousers_path',plugin_dir_path(__FILE__ ));
define('xoousers_template','basic');
define('MY_PLUGIN_SETTINGS_URL',"?page=userultra&tab=main");

// Get plugin version from header
function xoousersultra_get_plugin_version()
{
    $default_headers = array( 'Version' => 'Version' );
    $plugin_data = get_file_data( __FILE__, $default_headers, 'plugin' );
    return $plugin_data['Version'];
}


$plugin = plugin_basename(__FILE__);

/* Loading Function */
require_once (xoousers_path . 'functions/functions.php');

/* Init */
define('uultraxoousers_pro_url','http://usersultra.com/');
/* Load plugin text domain (localization) */

function xoousers_load_textdomain() 
{
       $locale = apply_filters( 'plugin_locale', get_locale(), 'users-ultra' );	   
       $mofile = xoousers_path . "languages/xoousers-$locale.mo";
			
		// Global + Frontend Locale
		load_textdomain( 'xoousers', $mofile );
		load_plugin_textdomain( 'xoousers', false, dirname(plugin_basename(__FILE__)).'/languages/' );
}
	
add_action('init', 'xoousers_load_textdomain');
add_action('init', 'xoousers_output_buffer');
function xoousers_output_buffer() {
		ob_start();
}

/* Master Class  */
require_once (xoousers_path . 'xooclasses/xoo.userultra.class.php');

$xoouserultra = new XooUserUltra();
$xoouserultra->plugin_init();

/* load addons */
require_once xoousers_path . 'addons/photocategories/index.php';

register_activation_hook(__FILE__, 'uultra_my_plugin_activate');
add_action('admin_init', 'uultra_my_plugin_redirect');

function uultra_my_plugin_activate() 
{
    add_option('uultra_plugin_do_activation_redirect', true);
}

function uultra_my_plugin_redirect() 
{
    if (get_option('uultra_plugin_do_activation_redirect', false)) {
        delete_option('uultra_plugin_do_activation_redirect');
        wp_redirect(MY_PLUGIN_SETTINGS_URL);
    }
}