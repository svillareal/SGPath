<?php 
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


//AJAX functions
function updateCoreTraining() {
  //check_ajax_referer( 'update_Core' )
  $resourceTag = $_POST["resourceTag"];
  $checkedValue = $_POST["checkedValue"];
  $outcomeTitle = $_POST["outcomeTitle"];
  $resourceID = $_POST["resourceID"];
  $checkedValue = (int)$checkedValue;
  if ($checkedValue == 1) { $resourceID = (int)$resourceID; }
  else { $resourceID = 0; }
  $version = "old";

  //get entryID and fieldID for checked resource
	global $wpdb;
	$UserID = get_current_user_id();
	$formName = "Resource Checkboxes - ".$outcomeTitle;
	$formID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_forms WHERE name='$formName'");
	$entryID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE user_id='$UserID' AND form_id='$formID' ORDER BY created_at DESC");
	$coreFieldOrder = array("0", "2", "4", "6", "8", "10");
  	$resFieldOrder = array("1", "3", "5", "7", "9", "11");
	$versionFieldOrder = array("12", "13", "14", "15", "16", "17");
	for ($i = 0; $i <= 5; $i++) {
		$coreFieldID[$i] = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_fields WHERE form_id='$formID' AND field_order='$coreFieldOrder[$i]'");
		$resFieldID[$i] = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_fields WHERE form_id='$formID' AND field_order='$resFieldOrder[$i]'");	
		$versionFieldID[$i] = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_fields WHERE form_id='$formID' AND field_order='$versionFieldOrder[$i]'");
	}
	if ($resourceTag == "BibleStudyCheck") {
		$fieldID = $coreFieldID[0];
		$fieldIDres = $resFieldID[0];
		$fieldIDversion = $versionFieldID[0];
	}
	else if ($resourceTag == "ReadingCheck") {
		$fieldID = $coreFieldID[1];
		$fieldIDres = $resFieldID[1];
		$fieldIDversion = $versionFieldID[1];
	}
	else if ($resourceTag == "ScriptureMemoryCheck") {
		$fieldID = $coreFieldID[2];
		$fieldIDres = $resFieldID[2];
		$fieldIDversion = $versionFieldID[2];
	}
	else if ($resourceTag == "ActivityCheck") {
		$fieldID = $coreFieldID[3];
		$fieldIDres = $resFieldID[3];
		$fieldIDversion = $versionFieldID[3];
	}
	else if ($resourceTag == "GroupDiscussionCheck") {
		$fieldID = $coreFieldID[4];
		$fieldIDres = $resFieldID[4];
		$fieldIDversion = $versionFieldID[4];
	}
	else if ($resourceTag == "OtherCheck") {
		$fieldID = $coreFieldID[5];
		$fieldIDres = $resFieldID[5];
		$fieldIDversion = $versionFieldID[5];
	}


  //update the database
  FrmEntryMeta::update_entry_meta($entryID, $fieldID, $meta_key = null, $checkedValue);
  FrmEntryMeta::update_entry_meta($entryID, $fieldIDres, $meta_key = null, $resourceID);  
  FrmEntryMeta::update_entry_meta($entryID, $fieldIDversion, $meta_key = null, $version);  

/**
  //send back a response  
	//get visibility status of core training resources
	$outcomeEntryID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE name='$outcomeTitle' AND form_id='59'");
	$visibilityEntryID = $wpdb->get_var("SELECT item_id FROM {$wpdb->prefix}frm_item_metas WHERE meta_value='$outcomeEntryID' AND field_id='822'");
	for ($i = 0; $i <= 5; $i++) {
		$coreHideFieldID = array("823", "824", "825", "826", "827", "828");
		$coreHide[$i] = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id='$visibilityEntryID' AND field_id='$coreHideFieldID[$i]'");
	}

	//get core training percentage comeplete
	$coreCheckedTot = 0;
	$coreCheckedTally = 0;
	for ($i = 0; $i <= 5; $i++) {
		$coreCheckValue[$i] = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='$coreFieldID[$i]' AND item_id='$entryID'");	
		if ($coreHide[$i] == "1") {
			$coreCheckedTally = $coreCheckedTally + (int)$coreCheckValue[$i];
			$coreCheckedTot = $coreCheckedTot + 1;
		}
	}
	$coreCheckedPerc = ($coreCheckedTally/$coreCheckedTot)*100;
	$coreCheckedPercR = round($coreCheckedPerc, 3);

  echo $coreCheckedPercR; **/
	die();
}

function updateToNewCoreTraining() {
  //check_ajax_referer( 'update_Core' )
  $resourceTag = $_POST["resourceTag"];
  $outcomeTitle = $_POST["outcomeTitle"];

  //get entryID and fieldID for checked resource
	global $wpdb;
	$UserID = get_current_user_id();
	$formName = "Resource Checkboxes - ".$outcomeTitle;
	$formID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_forms WHERE name='$formName'");
	$entryID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE user_id='$UserID' AND form_id='$formID' ORDER BY created_at DESC");
	$versionFieldOrder = array("12", "13", "14", "15", "16", "17");
	$version = "new";
	for ($i = 0; $i <= 5; $i++) {
		$versionFieldID[$i] = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_fields WHERE form_id='$formID' AND field_order='$versionFieldOrder[$i]'");
	}
	if ($resourceTag == "BibleStudyUpdate") {
		$fieldID = $versionFieldID[0];
	}
	else if ($resourceTag == "ReadingUpdate") {
		$fieldID = $versionFieldID[1];
	}
	else if ($resourceTag == "ScriptureMemoryUpdate") {
		$fieldID = $versionFieldID[2];
	}
	else if ($resourceTag == "ActivityUpdate") {
		$fieldID = $versionFieldID[3];
	}
	else if ($resourceTag == "GroupDiscussionUpdate") {
		$fieldID = $versionFieldID[4];
	}
	else if ($resourceTag == "OtherUpdate") {
		$fieldID = $versionFieldID[5];
	}

  //update the database
  FrmEntryMeta::update_entry_meta($entryID, $fieldID, $meta_key = null, $version);
   
	die();
}

function updateToOldCoreTraining() {
  //check_ajax_referer( 'update_Core' )
  $resourceTag = $_POST["resourceTag"];
  $outcomeTitle = $_POST["outcomeTitle"];

  //get entryID and fieldID for checked resource
	global $wpdb;
	$UserID = get_current_user_id();
	$formName = "Resource Checkboxes - ".$outcomeTitle;
	$formID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_forms WHERE name='$formName'");
	$entryID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE user_id='$UserID' AND form_id='$formID' ORDER BY created_at DESC");
	$versionFieldOrder = array("12", "13", "14", "15", "16", "17");
	$version = "old";
	for ($i = 0; $i <= 5; $i++) {
		$versionFieldID[$i] = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_fields WHERE form_id='$formID' AND field_order='$versionFieldOrder[$i]'");
	}
	if ($resourceTag == "BibleStudyRestore") {
		$fieldID = $versionFieldID[0];
	}
	else if ($resourceTag == "ReadingRestore") {
		$fieldID = $versionFieldID[1];
	}
	else if ($resourceTag == "ScriptureMemoryRestore") {
		$fieldID = $versionFieldID[2];
	}
	else if ($resourceTag == "ActivityRestore") {
		$fieldID = $versionFieldID[3];
	}
	else if ($resourceTag == "GroupDiscussionRestore") {
		$fieldID = $versionFieldID[4];
	}
	else if ($resourceTag == "OtherRestore") {
		$fieldID = $versionFieldID[5];
	}

  //update the database
  FrmEntryMeta::update_entry_meta($entryID, $fieldID, $meta_key = null, $version);
   
	die();
}

function updateOutcomeMain() {
  //check_ajax_referer
  $sectionID = $_POST["sectionID"];
  $showHideValue = $_POST["showHideValue"];
  $outcomeTitle = $_POST["outcomeTitle"];
  $showHideValue = (int)$showHideValue;

  //get entry and field data for  for checked resource
	global $wpdb;
	if ($sectionID == "divBibleStudy") { $fieldID = 823; }
	else if ($sectionID == "divReading") { $fieldID = 824; }
	else if ($sectionID == "divScriptureMemory") { $fieldID = 825; }
	else if ($sectionID == "divActivity") { $fieldID = 826; }
	else if ($sectionID == "divGroupDiscussion") { $fieldID = 827; }
	else if ($sectionID == "divOther") { $fieldID = 828; }
	$outcomeID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE form_id='59' AND name='$outcomeTitle'");
	$entryID = $wpdb->get_var("SELECT item_id FROM {$wpdb->prefix}frm_item_metas WHERE field_id='822' AND meta_value='$outcomeID' ORDER BY created_at DESC");

  //update the database
  FrmEntryMeta::update_entry_meta($entryID, $fieldID, $meta_key = null, $showHideValue);
  
  //send back a response
  echo "check";
	die();
}

function updateCoreMeta() {
  check_ajax_referer( 'updateCoreMeta' );

  //check_ajax_referer
  $postID = $_POST["postID"];
  $outcomeName = $_POST["outcomeName"];
  $resourceCategory = $_POST["resourceCategory"];
  $postID = (int)$postID;
 
  //get User info
  $userID = get_current_user_id();

  //get entry id for resource and outcome id
	global $wpdb;
	$entryID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE post_id='$postID' ORDER BY created_at DESC");
	$outcomeID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE form_id='59' AND name='$outcomeName' ORDER BY created_at DESC");
	$outcomepostID = $wpdb->get_var("SELECT post_id FROM {$wpdb->prefix}frm_items WHERE name='$outcomeName' ORDER BY created_at DESC");	

  //update the database
	$table_name = $wpdb->prefix . 'coremeta';

	$wpdb->insert( 
		$table_name, 
		array( 
			'created_at' => current_time( 'mysql' ), 
			'outcomeID' => $outcomeID, 
			'coreCategory' => $resourceCategory,
			'resourceEntryID' => $entryID,
			'updated_by' => $userID
		) 
	);

  
  //send back a response
  			wp_reset_postdata();
				$spiritualOutcomes = new WP_Query(array(
					'post_type' => 'spiritual_outcomes'
				));
  $redirectURL = get_the_permalink($outcomepostID);
  echo $redirectURL;
	die();
}

function removeExtra() {
//  check_ajax_referer( 'removeExtra' );

  //check_ajax_referer
  $postID = $_POST["postID"];
  $outcomeName = $_POST["outcomeName"];
  $postID = (int)$postID;
 
  //get new outcome associations
	global $wpdb;
	$entryID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE post_id='$postID' ORDER BY created_at DESC");
	$outcomeID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE form_id='59' AND name='$outcomeName' ORDER BY created_at DESC");
	$relatedOutcomeArray = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id='$entryID' AND field_id='759'");	
	$tempArray = unserialize($relatedOutcomeArray);
	$oldArray = $tempArray;
	if ($tempArray !== false) {
		$key = array_search($outcomeID, $tempArray);
		unset($tempArray[$key]);
		$tempNewOutcomeArray = array_values($tempArray);
		$countArray = count($tempNewOutcomeArray);
		if ($countArray >= 2) { $newOutcomeArray = maybe_serialize($tempNewOutcomeArray); }
		else if ($countArray == 1) { $newOutcomeArray = $tempNewOutcomeArray[0]; }
		else { $newOutcomeArray = NULL; }
	}
	else { $newOutcomeArray = NULL; }

  //get info to add to Delete From... field
  	$deletedOutcomeArray = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id='$entryID' AND field_id='835'");	
	$temp2Array = unserialize($deletedOutcomeArray);
	$old2Array = $temp2Array;
	if ($temp2Array !== false) {
		array_push($temp2Array, $outcomeID);
		$newDeletedArray = maybe_serialize($temp2Array);
	}
	else if ($deletedOutcomeArray == NULL) {
		$newDeletedArray = $outcomeID; 
	}
	else {
		$temp3Array = array ($deletedOutcomeArray, $outcomeID);
		$newDeletedArray = maybe_serialize($temp3Array);
	}


  //delete associated outcomes from the database
	if ($newOutcomeArray == NULL) {
		$table_name = $wpdb->prefix . 'frm_item_metas';
	 	$wpdb->delete( 
			$table_name,
			array( 'item_id' => $entryID,
				   'field_id' => '759' ),
			array( '%d', '%d' ) 
		);

		$table_name = $wpdb->prefix . 'postmeta';
		$metakey = "extrasOutcomeName";
	 	$wpdb->delete( 
			$table_name,
			array( 'meta_key' => $metakey,
				   'post_id' => $postID ),
			array( '%s', '%d' ) 
		);
	} else {
		$table_name = $wpdb->prefix . 'frm_item_metas';
		$wpdb->update( 
			$table_name, 
			array( 'meta_value' => $newOutcomeArray ), 
			array( 'item_id' => $entryID,
				   'field_id' => '759' ),
			array( '%s' ), 
			array( '%d', '%d' ) 
		);
		$table_name = $wpdb->prefix . 'postmeta';
		$metakey = "extrasOutcomeName";
		$wpdb->update( 
			$table_name, 
			array( 'meta_value' => $newOutcomeArray ), 
			array( 'meta_key' => $metakey,
				   'post_id' => $postID ),
			array( '%s' ), 
			array( '%s', '%d' ) 
		);
	}

  //add outcome to deleted outcomes list
	//check if there are any other outcomes on the deleted list
	if ($deletedOutcomeArray == NULL) {
		$table_name = $wpdb->prefix . 'frm_item_metas';	
		$wpdb->insert( 
			$table_name, 
			array( 
				'field_id' => '835', 
				'item_id' => $entryID,
				'meta_value' => $newDeletedArray,
				'created_at' => current_time( 'mysql' ) 
			), 
			array( '%d', '%d', '%s' )
		);

		$table_name = $wpdb->prefix . 'postmeta';	
		$metakey = "extrasPreviousOutcomes";
		$wpdb->insert( 
			$table_name, 
			array( 
				'post_id' => $postID, 
				'meta_key' => $metakey,
				'meta_value' => $newDeletedArray
			), 
			array( '%d', '%s', '%s' )
		);
	}
	else {
		$table_name = $wpdb->prefix . 'frm_item_metas';
		$wpdb->update( 
			$table_name, 
			array( 'meta_value' => $newDeletedArray ), 
			array( 'item_id' => $entryID,
				   'field_id' => '835' ),
			array( '%s' ), 
			array( '%d', '%d' ) 
		);
		$table_name = $wpdb->prefix . 'postmeta';
		$metakey = "extrasPreviousOutcomes";
		$wpdb->update( 
			$table_name, 
			array( 'meta_value' => $newDeletedArray ), 
			array( 'meta_key' => $metakey,
				   'post_id' => $postID ),
			array( '%s' ), 
			array( '%s', '%d' ) 
		);
	}
  
  //send back a response
  			wp_reset_postdata();
				$spiritualOutcomes = new WP_Query(array(
					'post_type' => 'spiritual_outcomes'
				));
	$outcomePostID = $wpdb->get_var("SELECT post_id FROM {$wpdb->prefix}frm_items WHERE id='$outcomeID'");
  	$redirectURL = get_the_permalink($outcomePostID);
  	echo $redirectURL;
	die();
}

function restoreExtra() {
//  check_ajax_referer( 'restoreExtra' );

  //check_ajax_referer
  $postID = $_POST["postID"];
  $outcomeName = $_POST["outcomeName"];
  $postID = (int)$postID;
 
  //get info for restored outcome associations
	global $wpdb;
	$entryID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE post_id='$postID' ORDER BY created_at DESC");
	$outcomeID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE form_id='59' AND name='$outcomeName' ORDER BY created_at DESC");
	$relatedOutcomeArray = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id='$entryID' AND field_id='759'");	
	$tempArray = unserialize($relatedOutcomeArray);
	$oldArray = $tempArray;
	if ($tempArray !== false) {
		array_push($tempArray, $outcomeID);
		$newOutcomeArray = maybe_serialize($tempArray);
	}
	else if ($relatedOutcomeArray == NULL) {
		$newOutcomeArray = $outcomeID; 
	}
	else {
		$tempArray = array ($relatedOutcomeArray, $outcomeID);
		$newOutcomeArray = maybe_serialize($tempArray);
	}

  //get info for associated outcome field
  	$deletedOutcomeArray = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id='$entryID' AND field_id='835'");	
	$temp2Array = unserialize($deletedOutcomeArray);
	$old2Array = $temp2Array;
	if ($temp2Array !== false) {
		$key = array_search($outcomeID, $temp2Array);
		unset($temp2Array[$key]);
		$tempNewDeletedArray = array_values($temp2Array);
		$countArray = count($tempNewDeletedArray);
		if ($countArray >= 2) { $newDeletedArray = maybe_serialize($tempNewDeletedArray); }
		else if ($countArray == 1) { $newDeletedArray = $tempNewDeletedArray[0]; }
		else { $newDeletedArray = NULL; }
	}
	else { $newDeletedArray = NULL; }

  //delte outcome to deleted outcomes list
	if ($newDeletedArray == NULL) {
		$table_name = $wpdb->prefix . 'frm_item_metas';
	 	$wpdb->delete( 
			$table_name,
			array( 'item_id' => $entryID,
				   'field_id' => '835' ),
			array( '%d', '%d' ) 
		);

		$table_name = $wpdb->prefix . 'postmeta';
		$metakey = "extrasPreviousOutcomes";
	 	$wpdb->delete( 
			$table_name,
			array( 'meta_key' => $metakey,
				   'post_id' => $postID ),
			array( '%s', '%d' ) 
		);
	} else {
		$table_name = $wpdb->prefix . 'frm_item_metas';
		$wpdb->update( 
			$table_name, 
			array( 'meta_value' => $newDeletedArray ), 
			array( 'item_id' => $entryID,
				   'field_id' => '835' ),
			array( '%s' ), 
			array( '%d', '%d' ) 
		);
		$table_name = $wpdb->prefix . 'postmeta';
		$metakey = "extrasPreviousOutcomes";
		$wpdb->update( 
			$table_name, 
			array( 'meta_value' => $newDeletedArray ), 
			array( 'meta_key' => $metakey,
				   'post_id' => $postID ),
			array( '%s' ), 
			array( '%s', '%d' ) 
		);
	}

  //restore associated outcomes in the database
	//check if there are any other outcomes on the deleted list
	if ($relatedOutcomeArray == NULL) {
		$table_name = $wpdb->prefix . 'frm_item_metas';	
		$wpdb->insert( 
			$table_name, 
			array( 
				'field_id' => '759', 
				'item_id' => $entryID,
				'meta_value' => $newOutcomeArray,
				'created_at' => current_time( 'mysql' ) 
			), 
			array( '%d', '%d', '%s' )
		);

		$table_name = $wpdb->prefix . 'postmeta';	
		$metakey = "extrasOutcomeName";
		$wpdb->insert( 
			$table_name, 
			array( 
				'post_id' => $postID, 
				'meta_key' => $metakey,
				'meta_value' => $newOutcomeArray
			), 
			array( '%d', '%s', '%s' )
		);
	}
	else {
		$table_name = $wpdb->prefix . 'frm_item_metas';
		$wpdb->update( 
			$table_name, 
			array( 'meta_value' => $newOutcomeArray ), 
			array( 'item_id' => $entryID,
				   'field_id' => '759' ),
			array( '%s' ), 
			array( '%d', '%d' ) 
		);
		$table_name = $wpdb->prefix . 'postmeta';
		$metakey = "extrasOutcomeName";
		$wpdb->update( 
			$table_name, 
			array( 'meta_value' => $newOutcomeArray ), 
			array( 'meta_key' => $metakey,
				   'post_id' => $postID ),
			array( '%s' ), 
			array( '%s', '%d' ) 
		);
	} 
  
  //send back a response
  			wp_reset_postdata();
				$spiritualOutcomes = new WP_Query(array(
					'post_type' => 'spiritual_outcomes'
				));
	$outcomePostID = $wpdb->get_var("SELECT post_id FROM {$wpdb->prefix}frm_items WHERE id='$outcomeID'");
  	$redirectURL = get_the_permalink($outcomePostID);
  	echo $redirectURL;
	die();
}


function bumpExtra() {
//  check_ajax_referer( 'bumpExtra' );


  //check_ajax_referer
  $postID = $_POST["postID"];
  $outcomeName = $_POST["outcome"];
  $orderDir = $_POST["order"];
  $top = $_POST["top"];
  $bottom = $_POST["bottom"];
  $postID = (int)$postID;
  $top = (int)$top;
  $bottom = (int)$bottom;

  //assign post IDs for resource to be moved down and resource to be moved up
	if ($orderDir == "up") {
		$topPostID = $top;
  		$bottomPostID = $postID;
	} else if ($orderDir == "dn") {
		$topPostID = $postID;
  		$bottomPostID = $bottom;
	}

  //get entry ids for resource and outcome
	global $wpdb;
	$topEntryID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE post_id='$topPostID' ORDER BY created_at DESC");
	$bottomEntryID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE post_id='$bottomPostID' ORDER BY created_at DESC");	
	$outcomeID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE form_id='59' AND name='$outcomeName' ORDER BY created_at DESC");

  //get listing order for all resources associated with this outcome
	$listedObjectArray = $wpdb->get_results("SELECT resourceID FROM {$wpdb->prefix}extrasmeta WHERE outcomeID='$outcomeID' ORDER BY listingOrder DESC");
	if ($listedObjectArray == NULL) {
		$listedArray = array();
	} else {
		foreach ($listedObjectArray as $key => $object) {
			$listedArray[$key] = $object->resourceID;
		}
	}
  //get listing order for affected resources
	$topListingOrder = $wpdb->get_var("SELECT listingOrder FROM {$wpdb->prefix}extrasmeta WHERE outcomeID='$outcomeID' AND resourceID='$topEntryID'");
	$bottomListingOrder = $wpdb->get_var("SELECT listingOrder FROM {$wpdb->prefix}extrasmeta WHERE outcomeID='$outcomeID' AND resourceID='$bottomEntryID'");	

  //check case, prepare database, and get original order of all affected resources
	//case: both affected resources are unlisted in listingOrder
	if ($topListingOrder == NULL) {
		$unlistedCheck = 2;
		$latestDate = $wpdb->get_var("SELECT created_at FROM {$wpdb->prefix}frm_items WHERE id='$bottomEntryID'");
		//get array for all of the resources from bottom date on
	  	$unlistedObjectArrayComplete = $wpdb->get_results("SELECT item_id FROM {$wpdb->prefix}frm_item_metas WHERE field_id='759' AND meta_value='$outcomeID'");
		foreach ($unlistedObjectArrayComplete as $key => $object) {
			$unlistedArrayComplete[$key] = $object->item_id;
		}		
		rsort($unlistedArrayComplete);
		$marker = array_search($bottomEntryID, $unlistedArrayComplete) + 1;
		$unlistedArray = array_slice($unlistedArrayComplete, 0, $marker);
		//remove those resources that are already listed
		$unlistedArrayTruncated = array_diff($unlistedArray, $listedArray);
		$table_name = $wpdb->prefix . 'extrasmeta';
		foreach ($unlistedArrayTruncated as $resourceID) {
			//add row for these resources in listing order database
			$wpdb->insert( 
				$table_name, 
				array( 
					'outcomeID' => $outcomeID, 
					'resourceID' => $resourceID,
					'listingOrder' => '0',
					'created_at' => current_time( 'mysql' ) 
				), 
				array( '%d', '%d', '%d' )
			); 
		}
		//merge unlisted and listed arrays
		$oldOrder = array_merge($listedArray, $unlistedArrayTruncated);
	//case: only bottom affected resource is unlisted in listing Order
	} else if ($bottomListingOrder == NULL) {
		$unlistedCheck = 1;
		$table_name = $wpdb->prefix . 'extrasmeta';
	//add row for this resource in listing order database
			$wpdb->insert( 
				$table_name, 
				array( 
					'outcomeID' => $outcomeID, 
					'resourceID' => $bottomEntryID,
					'listingOrder' => '0',
					'created_at' => current_time( 'mysql' ) 
				), 
				array( '%d', '%d', '%d' )
			);		
	$oldOrder = $listedArray;
		$oldOrder[] = $bottomEntryID;		
	//case both affected resources are listed
	} else {
		$unlistedCheck = 0;
		$oldOrder = $listedArray;
	}

  //re-order according to new requirements
	//get numeric key for 2 top and bottom resources
	$topKey = array_search($topEntryID,$oldOrder);
	$bottomKey = array_search($bottomEntryID,$oldOrder);
	$changeLength = ($bottomKey - $topKey + 1);
	$belowStart = $bottomKey + 1;
	//separate into 3 arrays
	$belowChange = array_slice($oldOrder, $belowStart);
	$inChange = array_slice($oldOrder, $topKey, $changeLength);
	$aboveChange = array_slice($oldOrder, 0, $topKey);
	//re-order inChange array
	array_unshift( $inChange, array_pop( $inChange ) );
	//rebuild newly ordered array
	$newOrder = array_reverse(array_merge($aboveChange, $inChange, $belowChange));

  //update listingOrder database
	$table_name = $wpdb->prefix . 'extrasmeta';
	foreach ($newOrder as $key => $resourceID) {
		$order = $key + 1;
		$wpdb->update( 
			$table_name, 
			array( 'listingOrder' => $order ), 
			array( 'resourceID' => $resourceID,
				   'outcomeID' => $outcomeID ),
			array( '%d' ), 
			array( '%d', '%d' ) 
		); 
	}
	die();
}

function removeCore() {
  //check_ajax_referer( 'updateCoreMeta' );

  //check_ajax_referer
  $outcomeName = $_POST["outcomeName"];
  $resourceCategory = $_POST["resourceCat"];
 
  //get User info
  $userID = get_current_user_id();

  //get entry id for resource and outcome id
	global $wpdb;
	$outcomeID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE form_id='59' AND name='$outcomeName' ORDER BY created_at DESC");
	$outcomepostID = $wpdb->get_var("SELECT post_id FROM {$wpdb->prefix}frm_items WHERE name='$outcomeName' ORDER BY created_at DESC");	

  //update the database
	$table_name = $wpdb->prefix . 'coremeta';
	$wpdb->insert( 
		$table_name, 
		array( 
			'created_at' => current_time( 'mysql' ), 
			'outcomeID' => $outcomeID, 
			'coreCategory' => $resourceCategory,
			'resourceEntryID' => '-1',
			'updated_by' => $userID
		) 
	);

  //send back a response
  			wp_reset_postdata();
				$spiritualOutcomes = new WP_Query(array(
					'post_type' => 'spiritual_outcomes'
				));
  $redirectURL = get_the_permalink($outcomepostID);
  echo $redirectURL;
	die();
}
add_action('wp_ajax_nopriv_updateCoreTraining','updateCoreTraining');
add_action('wp_ajax_updateCoreTraining','updateCoreTraining');
add_action('wp_ajax_nopriv_updateToNewCoreTraining','updateToNewCoreTraining');
add_action('wp_ajax_updateToNewCoreTraining','updateToNewCoreTraining');
add_action('wp_ajax_nopriv_updateToOldCoreTraining','updateToOldCoreTraining');
add_action('wp_ajax_updateToOldCoreTraining','updateToOldCoreTraining');
add_action('wp_ajax_nopriv_updateOutcomeMain','updateOutcomeMain');
add_action('wp_ajax_updateOutcomeMain','updateOutcomeMain');
add_action('wp_ajax_updateCoreMeta','updateCoreMeta');
add_action('wp_ajax_removeExtra','removeExtra');
add_action('wp_ajax_restoreExtra','restoreExtra');
add_action('wp_ajax_bumpExtra','bumpExtra');
add_action('wp_ajax_removeCore','removeCore');

?>
