<?php
/**
* Plugin Name: My Extras
* Description: This plugin is used for adding theme-independent funcitons to this website.
* Version: 1.0
* Author: Sherilyn Villareal
* Author URI: http://design.sherilynvillareal.com
*/


require 'php/extrasFunctions.php';


/**
 * Enqueue scripts and styles
 */
function my_extras_scripts() {
  wp_register_script( 'extras_script', plugins_url( '/js/extras_script.js', __FILE__ ), array( 'jquery'));
  $url = plugins_url();
  $plugin_path = array( 'plugin_path' =>  $url );
  wp_localize_script( 'extras_script', 'plugin_info', $plugin_path );  
  wp_enqueue_script( 'extras_script' );
  $core_nonce = wp_create_nonce( 'updateCoreMeta' );
  wp_localize_script( 'extras_script', 'my_ajax_obj', array(
       'ajax_url' => admin_url( 'admin-ajax.php' ),
       'nonce'    => $core_nonce,
    ) );
}

add_action( 'wp_enqueue_scripts', 'my_extras_scripts' );

//Disable admin bar for all users except admin
add_action('after_setup_theme', 'remove_admin_bar');
function remove_admin_bar() {
	if (!current_user_can('administrator') && !is_admin()) {
	  show_admin_bar(false);
	}
}

//Re-direct all users except admin away from wp-admin panel
function restrict_admin_with_redirect() {

	if ( ! current_user_can( 'manage_options' ) && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
		wp_redirect( site_url() ); 
		exit;
	}
}
add_action( 'admin_init', 'restrict_admin_with_redirect', 1 );

/**Function for checking page url at time of form entry (to return to same page) **/
add_filter('frm_setup_new_fields_vars', 'frm_get_pageid', 20, 2);
add_filter('frm_setup_edit_fields_vars', 'frm_get_pageid', 20, 2);
function frm_get_pageid($values, $field){
if($field->id == 108){
   $values['value'] = get_permalink();
}
return $values;
}

//Determines type of user role on user registration
add_filter('frmreg_new_role', 'assign_role', 10, 2);
function assign_role($role, $atts){
  extract($atts);
  if($form->id == 18){
    if($_POST['item_meta'][867] == 'Yes')
      $role = 'group_leader';
  }
  return $role;
}

function maybe_frm_value_func( $atts, $content = '' ) {
      $val = FrmProEntriesController::get_field_value_shortcode($atts);
      if($val == $atts['equals']){
        return $content;
      }else{
        return '';
      }
}
add_shortcode( 'maybe-frm-field-value', 'maybe_frm_value_func' );

add_filter('frm_data_sort', 'frm_my_data_sort', 21, 2);
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

/**
//Add User profile field in wordpress backend
add_action( 'show_user_profile', 'add_profile_fields' );
add_action( 'edit_user_profile', 'add_profile_fields' );

function add_profile_fields( $user )
{
    ?>
        <h3>Life Group membership</h3>

        <table class="form-table">
            <tr>
                <th><label for="member_passcode">Group Passcode</label></th>
                <td><input type="text" name="member_passcode" value="<?php echo get_the_author_meta( 'member_passcode', $user->ID ); ?>" class="regular-text" /></td>
            </tr>
        </table>


    <?php
}
**/

//Create / update user meta data on profile change
add_action('frm_after_create_entry', 'update_user_meta_on_profile_change', 30, 2);
add_action('frm_after_update_entry', 'update_user_meta_on_profile_change', 10, 2);
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

//Create blank Resource Checkbox form entry for new users
add_action( 'user_register', 'create_resource_checkbox_entries', 10, 1 );
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

function testdb_install() {
	global $wpdb;
  
	$table_name = $wpdb->prefix . 'testdb';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		name tinytext NOT NULL,
		text text NOT NULL,
		url varchar(55) DEFAULT '' NOT NULL,
		UNIQUE KEY id (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

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

register_activation_hook( __FILE__, 'testdb_install' );
register_activation_hook( __FILE__, 'coremeta_install' );
register_activation_hook( __FILE__, 'extrasmeta_install' );

  ?>