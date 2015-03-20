<?php 
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

//Helper functions
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

//Helper classes
class SgpAppHelpers {
	
	//Methods
    public static function plugin_path() {
        return dirname(dirname(__FILE__));
    }
}

?>