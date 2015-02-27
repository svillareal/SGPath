<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Full Content Template
 *
Template Name:  View Group Members page
 *
 * @file           group-members.php
 * @author         Sherilyn Villareal
 * @version        Release: 1.0
 */

 

get_header(); 

//functions
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

//Get user info
	$UserID = get_current_user_id();
	$userData = get_userdata( $UserID );
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

//Get group info
	$profileEntryID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE user_id='$UserID' AND form_id='110'");
	$repeatFieldEntryIDsArray = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id='$profileEntryID' AND field_id='1440'");
	$repeatFieldEntryIDs = unserialize($repeatFieldEntryIDsArray);
	if ($repeatFieldEntryIDs == false) { $repeatFieldEntryIDs = NULL; }
	foreach ($repeatFieldEntryIDs as $key => $value) {
		$groupPasscode[$key] = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id='$value' AND field_id='1444'");
		$groupName[$key] = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id='$value' AND field_id='1442'");
	}
	foreach ($groupPasscode as $key => $value) {
		$groupMemberEntryIDsObject = $wpdb->get_results("SELECT item_id FROM {$wpdb->prefix}frm_item_metas WHERE meta_value='$value' AND field_id='1437'");
		foreach ($groupMemberEntryIDsObject as $keyB => $valueB) {
			$groupMemberEntryIDs[$key][$keyB] = $groupMemberEntryIDsObject[$keyB]->{'item_id'};
		}
		foreach ($groupMemberEntryIDs[$key] as $keyB => $valueB) {
			$groupMemberInfo[$key][$keyB]['UserID'] = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id='$valueB' AND field_id='1426'");
			$groupMemberInfo[$key][$keyB]['first_name'] = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id='$valueB' AND field_id='1420'");
			$groupMemberInfo[$key][$keyB]['last_name'] = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id='$valueB' AND field_id='1421'");
		}
	}

//echo print_r($groupMemberInfo);
//check accurace of user ids
//consider whether or not to use usermeta data here instead of form data ??

?>
<div id="content-full" class="grid col-940">
<div class="outcome-entry-title">Life Group Members</div>
<div id="overview-menu">
    <div class="row">
        <div class="column3" align="center"><a href="#start-here">Start Here</a></div>
        <div class="column3" align="center"><a href="#discover-section">Discover</a></div>
        <div class="column3" align="center"><a href="#develop-section">Develop</a></div>
        <div class="column3" align="center"><a href="#deepen-section">Deepen</a></div>
    </div>
</div><!--outcome-menu-->
    

</div><!-- end of #content-full -->


<?php get_footer(); ?>
