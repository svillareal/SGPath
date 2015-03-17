<?php 
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

//Object Classes
include_once('controllers/SgpUser.php');
include_once('controllers/Outcome.php');
include_once('controllers/OutcomePage.php');
include_once('controllers/CoreTrainingStatus.php');
include_once('controllers/CoreCategories.php');
include_once('controllers/HeartCheckStatus.php');
include_once('controllers/Resource.php');


//General functions

function getPostID($entryID) {
	global $wpdb;
	$postID = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->prefix}frm_items WHERE id=%d", $entryID));
	return $postID;
}

function getEntryID($postID) {
	global $wpdb;
	$entryID = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}frm_items WHERE post_id=%d", $postID));
	return $entryID;
}

?>