<?php 
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

//Helper functions

		//Determines type of user role on based on form inputs at user registration
		function assign_role($role, $atts){
		  extract($atts);
		  if($form->id == 18){
			if($_POST['item_meta'][867] == 'Yes')
			  $role = 'group_leader';
		  }
		  return $role;
		}
		add_filter('frmreg_new_role', 'assign_role', 10, 2);

		//Converts Formidable entry ID to associated Wordpress Post ID
		function getPostID($entryID) {
			global $wpdb;
			$postID = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->prefix}frm_items WHERE id=%d", $entryID));
			return $postID;
		}

		//Converts Worpdress post ID to associated Formidable entry ID
		function getEntryID($postID) {
			global $wpdb;
			$entryID = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}frm_items WHERE post_id=%d", $postID));
			return $entryID;
		}
		
		//changes object to an array
		function objectToArray($d) {
			if (is_object($d)) {
				// Gets the properties of the given object
				// with get_object_vars function
				$d = get_object_vars($d);
			}
		
			if (is_array($d)) {
				/*
				* Return array converted to object
				* Using __FUNCTION__ (Magic constant)
				* for recursive call
				*/
				return array_map(__FUNCTION__, $d);
			}
			else {
				// Return array
				return $d;
			}
		}


		//Create & update user meta data on profile change
		function update_user_meta_on_profile_change($entry_id, $form_id){
		  if($form_id == 110){ 
			global $wpdb;
			//Get data from profile form
			$EntryID = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}frm_items WHERE form_id=%d ORDER BY created_at DESC", $form_id));
			$user_id = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$wpdb->prefix}frm_items WHERE id=%d", $entry_id));
			$name = array('first' => $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id=%d AND field_id='1420'", $entry_id)), 'last' => $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id=%d AND field_id='1421'", $entry_id)));
			$memberCheck = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id=%d AND field_id='1422'", $entry_id));
			if ($memberCheck == 1) {
				$memberPasscode = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id=%d AND field_id='1437'", $entry_id));
			} else {
				$memberPasscode = NULL;
			}
			$leaderCheck = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id=%d AND field_id='1424'", $entry_id));
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
					$groupName = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id=%d AND field_id='1442'", $entry_id));
					$groupGeo = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id=%d AND field_id='1443'", $entry_id));
					$groupPasscode = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id=%d AND field_id='1444'", $entry_id));
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
			$user = new SgpUser($user_id);
		
			if ($user->userView !== "non_member") {
			 global $wpdb, $frmdb;
			 
			 //Formidable Form IDs for all Resource Checkbox forms (for each outcome)
			 $checkboxForms = array(70, 71, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100, 101, 102, 103, 104, 105, 106);
			 
			 //Get user and their total		
			 foreach ($checkboxForms as $formID) {
				//Get field IDs
				for ($i = 0; $i <= 18; $i++) {
				$fieldID[$i] = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}frm_fields WHERE form_id=%d AND field_order=%d", $formID, $i));
				}
				 //See if this user already has an entry in second form
				 $entry_id = $wpdb->get_var($wpdb->prepare("Select id from $frmdb->entries WHERE form_id=%d AND user_id=%d", $formID, $user->userID));
				 if ( $entry_id ) {
					return;
				 } else {
					 //create entry
					 global $frm_entry;
					 $frm_entry->create(array(
					   'form_id' => $formID,
					   'item_key' => $user->userID . 'total', //change entry to a dynamic value if you would like
					   'frm_user_id' => $user->userID,
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
						 $fieldID[18] => $user->userID,
					   ),
					 ));
				 }
			 }
		  }
		}
		add_action( 'user_register', 'create_resource_checkbox_entries', 10, 1 );

		
//Helper classes
		class SgpAppHelpers {
			
			//Methods
			public static function plugin_path() {
				return dirname(dirname(__FILE__));
			}
		}

?>