<?php

//These AJAX functions handle admin-initiated updates to the Core Training resources associated with an Outcome.


		//called when an admin chooses to 'hide' a resource category for an Outcome
		function hideResourceCategory() {
		  //check_ajax_referer
		  check_ajax_referer( 'hideResourceCategory' );
		  //get posted data
		  $sectionID = $_POST["sectionID"];
		  $showHideValue = $_POST["showHideValue"];
		  $outcomeTitle = $_POST["outcomeTitle"];
		  //validate posted data
		  $resourceCat = substr($sectionID, 3);
		  new CoreCategories();
		  if (!(in_array($resourceCat, CoreCategories::$coreCatNoSpace))) {
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
		  $catIndex = array_search($resourceCat, CoreCategories::$coreCatNoSpace);
		  $showHideValue = (int)$showHideValue;		
		  $coreStatus = new CoreTrainingStatus($outcome->postID, $user->userID);
		  $fieldID = CoreCategories::$coreHideFieldID[$catIndex];
		  //update the database
		  FrmEntryMeta::update_entry_meta($outcome->visibilityEntryID, $fieldID, $meta_key = null, $showHideValue);		  
		  wp_die();
		}
		add_action('wp_ajax_nopriv_hideResourceCategory','hideResourceCategory');
		add_action('wp_ajax_hideResourceCategory','hideResourceCategory');
		
		
		//called when an admin selects a new resource to assign to a particular resource category for an Outcome
		function changeCoreResource() {
		  //check_ajax_referer
		  check_ajax_referer( 'changeCoreResource' );
		  //get posted data	
		  $postID = $_POST["postID"];
		  $outcomeName = $_POST["outcomeName"];
		  $resourceCategory = $_POST["resourceCategory"];
		  //validate posted data
		  $postID = (int)$postID;
		  new CoreCategories();
		  if (!(in_array($resourceCategory, CoreCategories::$coreCategories))) {
		  	wp_die();
		  }
		  $resource = new Resource($postID);
		  if ($resource->statusCheck == "bad") {
		  	wp_die();
		  }
		  $outcomePostID = Outcome::getOutcomeIdByName($outcomeName);
		  $outcome = new Outcome($outcomePostID);
		  if ($outcome->statusCheck == "bad") {
		  	wp_die();
		  }
		  $user = new SgpUser(get_current_user_id());
		  if ($user->statusCheck == "bad") {
			  wp_die();
		  }
		  //update the database
		  global $wpdb;
		  $table_name = $wpdb->prefix . 'coremeta';
		  $wpdb->insert(
		  		$table_name,
				array (
					'outcomeID' => $outcome->entryID,
					'coreCategory' => $resourceCategory,
					'resourceEntryID' => $resource->entryID,
					'updated_by' => $user->userID,
					'created_at' => current_time( 'mysql' )
				),
				array( 
					'%d', 
					'%s',
					'%d',
					'%d'  
				)
		  );
		  //send back a response
		  wp_reset_postdata();
			$spiritualOutcomes = new WP_Query(array(
				'post_type' => 'spiritual_outcomes'
			));
		  $redirectURL = get_the_permalink($outcome->postID);
		  echo $redirectURL;
		  wp_die();
		}
		add_action('wp_ajax_changeCoreResource','changeCoreResource');
		

		//called when an admin chooses to 'remove' the currently assigned resource from a particular resource category for an Outcome
		function removeCoreResource() {
		  //check_ajax_referer
		  check_ajax_referer( 'removeCoreResource' );
		  //get posted data
		  $outcomeName = $_POST["outcomeName"];
		  $resourceCategory = $_POST["resourceCat"];	 
		  new CoreCategories();
		  if (!(in_array($resourceCategory, CoreCategories::$coreCategories))) {
		  	wp_die();
		  }
		  $outcomePostID = Outcome::getOutcomeIdByName($outcomeName);
		  $outcome = new Outcome($outcomePostID);
		  if ($outcome->statusCheck == "bad") {
		  	wp_die();
		  }
		  $user = new SgpUser(get_current_user_id());
		  if ($user->statusCheck == "bad") {
			  wp_die();
		  }
		  //update the database
			global $wpdb;
			$table_name = $wpdb->prefix . 'coremeta';
			$wpdb->insert( 
				$table_name, 
				array( 
					'outcomeID' => $outcome->entryID, 
					'coreCategory' => $resourceCategory,
					'resourceEntryID' => '-1',
					'updated_by' => $user->userID,
					'created_at' => current_time( 'mysql' ), 
				),
				array( 
					'%d',
					'%s',
					'%d',
					'%d'  
				)
			);
		  //send back a response
			wp_reset_postdata();
				$spiritualOutcomes = new WP_Query(array(
					'post_type' => 'spiritual_outcomes'
				));
		  $redirectURL = get_the_permalink($outcome->postID);
		  echo $redirectURL;
		  wp_die();
		}
		add_action('wp_ajax_removeCoreResource','removeCoreResource');
?>