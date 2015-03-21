<?php 

//These AJAX functions update the database to refelct user-initiatied changes to his/her Core Training status - either completion tracking or version display settings.
include_once(SgpAppHelpers::plugin_path() . '/sgp-includes.php');

		//Updates the database to reflect user-initiated changes to completion tracking of Core Training status
		function updateCoreCompletionStatus() {
		  //check_ajax_referer
		  check_ajax_referer( 'updateCoreCompletionStatus' );
		  //get posted data
		  $resourceTag = $_POST["resourceTag"];
		  $checkedValue = $_POST["checkedValue"];
		  $outcomeTitle = $_POST["outcomeTitle"];
		  $resourceID = $_POST["resourceID"];
		  //validate posted data
		  $resourceTag = substr($resourceTag, 0, -5);	
		  new CoreCategories();
		  if (!(in_array($resourceTag, CoreCategories::$coreCatNoSpace))) {
		  	wp_die();
		  }
		  $resource = new Resource(getPostID($resourceID));
		  if ($resource->statusCheck == "bad") {
		  	wp_die();
		  }
		  $outcomePostID = Outcome::getOutcomeIdByName($outcomeTitle);
		  $outcome = new Outcome($outcomePostID);
		  if ($outcome->statusCheck == "bad") {
		  	wp_die();
		  }
		  $user = new SgpUser(get_current_user_id());
		  if ($user->statusCheck == "bad") {
			  wp_die();
		  }
		  //set values
		  $catIndex = array_search($resourceTag, CoreCategories::$coreCatNoSpace);
		  $checkedValue = (int)$checkedValue;
		  if ($checkedValue == 1) {
			  $resourceID = $resource->entryID;
		  } else {
			  $resourceID = 0;
		  }
		  $version = "old";
		  $coreStatus = new CoreTrainingStatus($outcome->postID, $user->userID);

		  //update the database
		  FrmEntryMeta::update_entry_meta($coreStatus->entryID, $coreStatus->coreFieldID[$catIndex], $meta_key = null, $checkedValue);
		  FrmEntryMeta::update_entry_meta($coreStatus->entryID, $coreStatus->resFieldID[$catIndex], $meta_key = null, $resourceID);  
		  FrmEntryMeta::update_entry_meta($coreStatus->entryID, $coreStatus->versionFieldID[$catIndex], $meta_key = null, $version);  
		  wp_die();
		}
		add_action('wp_ajax_nopriv_updateCoreCompletionStatus','updateCoreCompletionStatus');
		add_action('wp_ajax_updateCoreCompletionStatus','updateCoreCompletionStatus');
		

		//The following two functions update the database to reflect user-initiated version display settings for Core Training resources.
		function updateToNewCoreVersion() {
		  //check_ajax_referer
		  check_ajax_referer( 'updateToNewCoreVersion' );
		  //get posted data
		  $resourceTag = $_POST["resourceTag"];
		  $outcomeTitle = $_POST["outcomeTitle"];
		  //validate posted data
		  $resourceTag = substr($resourceTag, 0, -6);	
		  new CoreCategories();
		  if (!(in_array($resourceTag, CoreCategories::$coreCatNoSpace))) {
		  	wp_die();
		  }
		  $outcomePostID = Outcome::getOutcomeIdByName($outcomeTitle);
		  $outcome = new Outcome($outcomePostID);
		  if ($outcome->statusCheck == "bad") {
		  	wp_die();
		  }
		  $user = new SgpUser(get_current_user_id());
		  if ($user->statusCheck == "bad") {
			  wp_die();
		  }
		  //set values
		  $catIndex = array_search($resourceTag, CoreCategories::$coreCatNoSpace);
		  $coreStatus = new CoreTrainingStatus($outcome->postID, $user->userID);
		  $version = "new";
		  $fieldID = $coreStatus->versionFieldID[$catIndex];
		  //update the database
		  FrmEntryMeta::update_entry_meta($coreStatus->entryID, $fieldID, $meta_key = null, $version);
		  wp_die();
		}
		add_action('wp_ajax_nopriv_updateToNewCoreVersion','updateToNewCoreVersion');
		add_action('wp_ajax_updateToNewCoreVersion','updateToNewCoreVersion');

		
		function updateToOldCoreVersion() {
		  //check_ajax_referer
		  check_ajax_referer( 'updateToOldCoreVersion' );
		  //get posted data
		  $resourceTag = $_POST["resourceTag"];
		  $outcomeTitle = $_POST["outcomeTitle"];
		  //validate posted data
		  $resourceTag = substr($resourceTag, 0, -7);	
		  new CoreCategories();
		  if (!(in_array($resourceTag, CoreCategories::$coreCatNoSpace))) {
		  	wp_die();
		  }
		  $outcomePostID = Outcome::getOutcomeIdByName($outcomeTitle);
		  $outcome = new Outcome($outcomePostID);
		  if ($outcome->statusCheck == "bad") {
		  	wp_die();
		  }
		  $user = new SgpUser(get_current_user_id());
		  if ($user->statusCheck == "bad") {
			  wp_die();
		  }
		  //set values
		  $catIndex = array_search($resourceTag, CoreCategories::$coreCatNoSpace);
		  $coreStatus = new CoreTrainingStatus($outcome->postID, $user->userID);
		  $version = "old";
		  $fieldID = $coreStatus->versionFieldID[$catIndex];
		  //update the database
		  FrmEntryMeta::update_entry_meta($this->entryID, $fieldID, $meta_key = null, $version);
		  wp_die();
		}
		add_action('wp_ajax_nopriv_updateToOldCoreVersion','updateToOldCoreVersion');
		add_action('wp_ajax_updateToOldCoreVersion','updateToOldCoreVersion');
?>