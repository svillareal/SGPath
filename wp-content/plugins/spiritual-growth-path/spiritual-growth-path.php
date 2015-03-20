<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
* Plugin Name: Spiritual Growth Path
* Description: This plugin is used for adding theme-independent funcitons to this website.
* Version: 1.0
* Author: Sherilyn Villareal
* Author URI: http://design.sherilynvillareal.com
*/

//Load files
include_once 'helpers/AppHelpers.php';
include_once 'views/PageTemplater.php';
require_once 'controllers/ajax-functions.php';

/**
 * Enqueue scripts and styles
 */
function sgp_scripts() {
  wp_register_script( 'spiritual-growth-path', plugins_url( '/js/extras_script.js', __FILE__ ), array( 'jquery'));
  wp_register_style( 'spiritual-growth-path', plugins_url( 'spiritual-growth-path/css/style.css' ) );
  $url = plugins_url();
  $plugin_path = array( 'plugin_path' =>  $url );
  wp_localize_script( 'spiritual-growth-path', 'plugin_info', $plugin_path );  
  wp_enqueue_script( 'spiritual-growth-path' );
  wp_enqueue_style( 'spiritual-growth-path' );
  $core_nonce = wp_create_nonce( 'updateCoreMeta' );
  wp_localize_script( 'spiritual-growth-path', 'my_ajax_obj', array(
       'ajax_url' => admin_url( 'admin-ajax.php' ),
       'nonce'    => $core_nonce,
    ) );
}
add_action( 'wp_enqueue_scripts', 'sgp_scripts' );

/*function theme_name_scripts() {
	wp_enqueue_style( 'style-name', get_stylesheet_uri() );
	wp_enqueue_script( 'script-name', get_template_directory_uri() . '/js/example.js', array(), '1.0.0', true );
}

add_action( 'wp_enqueue_scripts', 'theme_name_scripts' ); */

//Change page templates for custom post types
function get_custom_post_type_template($single_template) {
     global $post;
     if ($post->post_type == 'resource') {
          $single_template = SgpAppHelpers::plugin_path() . '/views/single-resource.php';
     } else if ($post->post_type == 'spiritual_outcomes') {
          $single_template = SgpAppHelpers::plugin_path() . '/views/single-spiritual_outcomes.php';
	 }
     return $single_template;
}
add_filter( 'single_template', 'get_custom_post_type_template' );


//Disable admin bar for all users except admin
function remove_admin_bar() {
	//************************once user types set, change this to only sgp-specific user types
	if (!current_user_can('administrator') && !is_admin()) {
	  show_admin_bar(false);
	}
}
add_action('after_setup_theme', 'remove_admin_bar');

//Re-direct all users except admin away from wp-admin panel
function restrict_admin_with_redirect() {
	//**************************change to sgp-specific user re-directs once user types are set
	if ( ! current_user_can( 'manage_options' ) && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
		wp_redirect( site_url() ); 
		exit;
	}
}
add_action( 'admin_init', 'restrict_admin_with_redirect', 1 );

//Add SGP database tables
	function coremeta_install() {
		global $wpdb;
	  
		$table_name = $wpdb->prefix . 'coremeta';
		
		$charset_collate = $wpdb->get_charset_collate();
	
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			outcomeID smallint,
			coreCategory varchar(50),
			resourceEntryID smallint,
			updated_by mediumint(9),
			created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			UNIQUE KEY id (id)
		) $charset_collate;";
	
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
	
	function extrasmeta_install() {
		global $wpdb;
	  
		$table_name = $wpdb->prefix . 'extrasmeta';
		
		$charset_collate = $wpdb->get_charset_collate();
	
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			resourceID smallint,
			outcomeID smallint,
			listingOrder smallint,
			created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			UNIQUE KEY id (id)
		) $charset_collate;";
	
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
register_activation_hook( __FILE__, 'coremeta_install' );
register_activation_hook( __FILE__, 'extrasmeta_install' );
register_activation_hook( __FILE__, 'add_sgp_pages' );

function add_sgp_pages() {
	$new_page_title = 'Did it work';
	$new_page_content = "Here's the page content!";
	$new_page_template = 'outcome-overview.php'; //ex. template-custom.php. Leave blank if you don't want a custom page template.
	$page_check = get_page_by_title($new_page_title);
	$new_page = array(
		'post_type' => 'page',
		'post_title' => $new_page_title,
		'post_content' => $new_page_content,
		'post_status' => 'publish',
		'post_author' => 1,
	);
	if(!isset($page_check->ID)){
		$new_page_id = wp_insert_post($new_page);
		if(!empty($new_page_template)){
			update_post_meta($new_page_id, '_wp_page_template', $new_page_template);
		}
	}
}

/*
//**************************this function may be deprecated
//Function for checking page url at time of form entry (to return to same page)
add_filter('frm_setup_new_fields_vars', 'frm_get_pageid', 20, 2);
add_filter('frm_setup_edit_fields_vars', 'frm_get_pageid', 20, 2);
function frm_get_pageid($values, $field){
	if($field->id == 108){
	   $values['value'] = get_permalink();
	}
	return $values;
} */

//Determines type of user role on user registration
function assign_role($role, $atts){
  extract($atts);
  if($form->id == 18){
    if($_POST['item_meta'][867] == 'Yes')
      $role = 'group_leader';
  }
  return $role;
}
add_filter('frmreg_new_role', 'assign_role', 10, 2);

/*
//********************this funciton may be deprecated
//formidable function allowing conditional viewing
function maybe_frm_value_func( $atts, $content = '' ) {
      $val = FrmProEntriesController::get_field_value_shortcode($atts);
      if($val == $atts['equals']){
        return $content;
      }else{
        return '';
      }
}
add_shortcode( 'maybe-frm-field-value', 'maybe_frm_value_func' );


//**********************this function may be deprecated
//changes teh way outcomes are sorted
function frm_my_data_sort($options, $atts){
        if($atts['field']->id == 227){ //change 227 to the ID of the linked field (not the data from entries field)
           ksort($options);//sorts options by entry ID
        }
        if($atts['field']->id == 283){ 
           ksort($options);//sorts options by entry ID
        }
        if($atts['field']->id == 280){ 
           ksort($options);//sorts options by entry ID
        }
        return $options;
	}
add_filter('frm_data_sort', 'frm_my_data_sort', 21, 2); */

//Create & update user meta data on profile change
function update_user_meta_on_profile_change($entry_id, $form_id){
  if($form_id == 110){ 
    global $wpdb;
	//Get data from profile form
	$EntryID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE form_id='$form_id' ORDER BY created_at DESC");
	$user_id = $wpdb->get_var("SELECT user_id FROM {$wpdb->prefix}frm_items WHERE id='$entry_id'");
	$name = array('first' => $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id='$entry_id' AND field_id='1420'"), 'last' => $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id='$entry_id' AND field_id='1421'"));
	$memberCheck = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id='$entry_id' AND field_id='1422'");
	if ($memberCheck == 1) {
		$memberPasscode = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id='$entry_id' AND field_id='1437'");
	} else {
		$memberPasscode = NULL;
	}
	$leaderCheck = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id='$entry_id' AND field_id='1424'");
	if ($leaderCheck == 1) {
		$groupsEntries = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id='620' AND field_id='1440'");
		$unGroups = unserialize($groupsEntries);
		if ($unGroups !== false) {
			$groupsEntries = $unGroups;
		}
		else if ($groupsEntries !== NULL) { 
			$groupsEntries = array($groupsEntries);
		}
	} else {
		$groupsEntries = NULL;
	}  

	//update user meta
	update_user_meta( $user_id, 'first_name', $name['first']);
	update_user_meta( $user_id, 'last_name', $name['last']);
	update_user_meta( $user_id, 'member_passcode', $memberPasscode);
	$u = new WP_User( $user_id );
	if ($leaderCheck == 1) {
		// Add role
		$u->add_role( 'group_leader' );
	} else {
		// Remove role
		$u->remove_role( 'group_leader' );
	}
	$x = 0;
	if ($groupsEntries !== NULL) {
		foreach ($groupsEntries as $entryID) {
			$x++;
			$groupName = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id='$entryID' AND field_id='1442'");
			$groupGeo = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id='$entryID' AND field_id='1443'");
			$groupPasscode = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id='$entryID' AND field_id='1444'");
			$test = get_user_meta($user_id, 'group'.$x.'_name', $groupName);
//			if ($test == NULL) {
//				add_user_meta( $user_id, 'group'.$x.'_name', $groupName);
//			} else {
			update_user_meta( $user_id, 'group'.$x.'_name', $groupName);
//			}
			update_user_meta( $user_id, 'group'.$x.'_geo', $groupGeo);
			update_user_meta( $user_id, 'group'.$x.'_passcode', $groupPasscode);
		}
	}
	$key = true;
    while($key){
		$x++;
		$otherGroupsCheck = get_user_meta($user_id, 'group'.$x.'_passcode', true);
		if ($otherGroupsCheck !== "") {
			delete_user_meta( $user_id, 'group'.$x.'_name');
			delete_user_meta( $user_id, 'group'.$x.'_geo');
			delete_user_meta( $user_id, 'group'.$x.'_passcode');
		} else {
			$key = false;
		}
    } 
  }
}
add_action('frm_after_create_entry', 'update_user_meta_on_profile_change', 30, 2);
add_action('frm_after_update_entry', 'update_user_meta_on_profile_change', 10, 2);


//Create blank Resource Checkbox form entry for new users
function create_resource_checkbox_entries( $user_id ){
	$userData = get_userdata( $user_id );
	if (in_array("administrator", $userData->roles)) {
		$userView = "admin";
	} else if (in_array("grow_pastor", $userData->roles)) {
		$userView = "pastor";
	} else if (in_array("group_leader", $userData->roles)) {
		$userView = "leader";
	} else if (in_array("subscriber", $userData->roles)) {
		$userView = "member";
	} else {
		$userView = "non_member";
	}	

	if ($userView !== "non_member") {
	 global $wpdb, $frmdb;
	 
	 $checkboxForms = array(70, 71, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100, 101, 102, 103, 104, 105, 106);
	 //Get user and their total

	 foreach ($checkboxForms as $formID) {
		//Get field IDs
		for ($i = 0; $i <= 18; $i++) {
		$fieldID[$i] = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_fields WHERE form_id='$formID' AND field_order='$i'");
		}
		 //See if this user already has an entry in second form
		 $entry_id = $wpdb->get_var("Select id from $frmdb->entries where form_id='" . $formID . "' and user_id=". $user_id);
		 if ( $entry_id ) {
			return;
		 } else {
			 //create entry
			 global $frm_entry;
			 $frm_entry->create(array(
			   'form_id' => $formID,
			   'item_key' => $user_id . 'total', //change entry to a dynamic value if you would like
			   'frm_user_id' => $user_id,
			   'item_meta' => array(
				 $fieldID[0] => '0',
				 $fieldID[1] => '0',
				 $fieldID[2] => '0',
				 $fieldID[3] => '0',
				 $fieldID[4] => '0',
				 $fieldID[5] => '0',
				 $fieldID[6] => '0',
				 $fieldID[7] => '0',
				 $fieldID[8] => '0',
				 $fieldID[9] => '0',
				 $fieldID[10] => '0',
				 $fieldID[11] => '0',
				 $fieldID[12] => 'old',
				 $fieldID[13] => 'old',
				 $fieldID[14] => 'old',
				 $fieldID[15] => 'old',
				 $fieldID[16] => 'old',
				 $fieldID[17] => 'old',
				 $fieldID[18] => $user_id,
			   ),
			 ));
		 }
	 }
  }
}
add_action( 'user_register', 'create_resource_checkbox_entries', 10, 1 );

  ?>