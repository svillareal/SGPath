<?php 

//These AJAX functions handle admin-initiated updates to the Extras resources associated with an Outcome.


		//called when an admin chooses to delete ('remove') a resource in the Extras section for an Outcome
		function removeExtra() {	
		  //check_ajax_referer
		  check_ajax_referer( 'removeExtra' );
		  //get posted data
		  $postID = $_POST["postID"];
		  $outcomeName = $_POST["outcomeName"];
		  $postID = (int)$postID;	 
		  //validate posted data
		  $resource = new Resource($postID);
		  if ($resource->statusCheck == "bad") {
		  	wp_die('bad resource');
		  }
		  $outcomePostID = Outcome::getOutcomeIdByName($outcomeName);
		  $outcome = new Outcome($outcomePostID);
		  if ($outcome->statusCheck == "bad") {
		  	wp_die('bad outcome');
		  }
		  $user = new SgpUser(get_current_user_id());
		  if ($user->statusCheck == "bad") {
			  wp_die('bad user');
		  }		  
		  //get new outcome associations
			global $wpdb;
			$outcomeArray = $resource->assocOutcomeEntryIDs;
			if ($outcomeArray[0] != NULL) {
				$key = array_search($outcome->entryID, $outcomeArray);
				unset($outcomeArray[$key]);
				$tempNewOutcomeArray = array_values($outcomeArray);
				$countArray = count($tempNewOutcomeArray);
				if ($countArray >= 2) { $newOutcomeArray = maybe_serialize($tempNewOutcomeArray); }
				else if ($countArray == 1) { $newOutcomeArray = $tempNewOutcomeArray[0]; }
				else { $newOutcomeArray = NULL; }
			}
			else { $newOutcomeArray = NULL; }	
		  //get info to add to Delete From... field
			$deletedArray = $resource->deletedOutcomeEntryIDs;
			if ($deletedArray[0] != NULL) {
				array_push($deletedArray, $outcome->entryID);
				$newDeletedArray = maybe_serialize($deletedArray);
			}
			else {
				$newDeletedArray = $outcome->entryID; 
			}
		  //delete associated outcomes from the database
			if ($newOutcomeArray == NULL) {
				$table_name = $wpdb->prefix . 'frm_item_metas';
				$wpdb->delete( 
					$table_name,
					array( 'item_id' => $resource->entryID,
						   'field_id' => '759' ),
					array( '%d', '%d' ) 
				);	
				$table_name = $wpdb->prefix . 'postmeta';
				$wpdb->delete( 
					$table_name,
					array( 'meta_key' => 'extrasOutcomeName',
						   'post_id' => $resource->postID ),
					array( '%s', '%d' ) 
				);
			} else {
				$table_name = $wpdb->prefix . 'frm_item_metas';
				$wpdb->update( 
					$table_name, 
					array( 'meta_value' => $newOutcomeArray ), 
					array( 'item_id' => $resource->entryID,
						   'field_id' => '759' ),
					array( '%s' ), 
					array( '%d', '%d' ) 
				);
				$table_name = $wpdb->prefix . 'postmeta';
				$wpdb->update( 
					$table_name, 
					array( 'meta_value' => $newOutcomeArray ), 
					array( 'meta_key' => 'extrasOutcomeName',
						   'post_id' => $resource->postID ),
					array( '%s' ), 
					array( '%s', '%d' ) 
				);
			}
		  //add outcome to deleted outcomes list
			//check if there are any other outcomes on the deleted list
			if ($deletedArray[0] == NULL) {
				$table_name = $wpdb->prefix . 'frm_item_metas';	
				$wpdb->insert( 
					$table_name, 
					array( 
						'field_id' => '835', 
						'item_id' => $resource->entryID,
						'meta_value' => $newDeletedArray,
						'created_at' => current_time( 'mysql' ) 
					), 
					array( '%d', '%d', '%s' )
				);
		
				$table_name = $wpdb->prefix . 'postmeta';	
				$wpdb->insert( 
					$table_name, 
					array( 
						'post_id' => $resource->postID, 
						'meta_key' => 'extrasPreviousOutcomes',
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
					array( 'item_id' => $resource->entryID,
						   'field_id' => '835' ),
					array( '%s' ), 
					array( '%d', '%d' ) 
				);
				$table_name = $wpdb->prefix . 'postmeta';
				$wpdb->update( 
					$table_name, 
					array( 'meta_value' => $newDeletedArray ), 
					array( 'meta_key' => 'extrasPreviousOutcomes',
						   'post_id' => $resource->postID ),
					array( '%s' ), 
					array( '%s', '%d' ) 
				);
			}  
		  //send back a response
			wp_reset_postdata();
				$spiritualOutcomes = new WP_Query(array(
					'post_type' => 'spiritual_outcomes'
				));
			$redirectURL = get_the_permalink($outcome->postID);
			echo $redirectURL;
			wp_die();
		}
		add_action('wp_ajax_removeExtra','removeExtra');
		
		
		//called when an admin chooses to 'restore' a previously deleted resource to the Extras section for an Outcome
		function restoreExtra() {
		  //check_ajax_referer
		  check_ajax_referer( 'restoreExtra' );
		  //get posted data
		  $postID = $_POST["postID"];
		  $outcomeName = $_POST["outcomeName"];
		  $postID = (int)$postID;
		  //validate posted data
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
		  //get info for restored outcome associations
			global $wpdb;
			$outcomeArray = $resource->assocOutcomeEntryIDs;
			if ($outcomeArray[0] != NULL) {
				array_push($outcomeArray, $outcome->entryID);
				$newOutcomeArray = maybe_serialize($outcomeArray);
			}
			else {
				$newOutcomeArray = $outcome->entryID; 
			}
		  //get info for associated outcome field
			$deletedArray = $resource->deletedOutcomeEntryIDs;
			if ($deletedArray[0] != NULL) {
				$key = array_search($outcome->entryID, $deletedArray);
				unset($deletedArray[$key]);
				$tempNewDeletedArray = array_values($deletedArray);
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
					array( 'item_id' => $resource->entryID,
						   'field_id' => '835' ),
					array( '%d', '%d' ) 
				);
		
				$table_name = $wpdb->prefix . 'postmeta';
				$wpdb->delete( 
					$table_name,
					array( 'meta_key' => 'extrasPreviousOutcomes',
						   'post_id' => $resource->postID ),
					array( '%s', '%d' ) 
				);
			} else {
				$table_name = $wpdb->prefix . 'frm_item_metas';
				$wpdb->update( 
					$table_name, 
					array( 'meta_value' => $newDeletedArray ), 
					array( 'item_id' => $resource->entryID,
						   'field_id' => '835' ),
					array( '%s' ), 
					array( '%d', '%d' ) 
				);
				$table_name = $wpdb->prefix . 'postmeta';
				$wpdb->update( 
					$table_name, 
					array( 'meta_value' => $newDeletedArray ), 
					array( 'meta_key' => 'extrasPreviousOutcomes',
						   'post_id' => $resource->postID ),
					array( '%s' ), 
					array( '%s', '%d' ) 
				);
			}
			//restore associated outcomes in the database
			//check if there are any other outcomes on the deleted list
			if ($outcomeArray[0] == NULL) {
				$table_name = $wpdb->prefix . 'frm_item_metas';	
				$wpdb->insert( 
					$table_name, 
					array( 
						'field_id' => '759', 
						'item_id' => $resource->entryID,
						'meta_value' => $newOutcomeArray,
						'created_at' => current_time( 'mysql' ) 
					), 
					array( '%d', '%d', '%s' )
				);
		
				$table_name = $wpdb->prefix . 'postmeta';	
				$wpdb->insert( 
					$table_name, 
					array( 
						'post_id' => $resource->postID, 
						'meta_key' => 'extrasOutcomeName',
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
					array( 'item_id' => $resource->entryID,
						   'field_id' => '759' ),
					array( '%s' ), 
					array( '%d', '%d' ) 
				);
				$table_name = $wpdb->prefix . 'postmeta';
				$wpdb->update( 
					$table_name, 
					array( 'meta_value' => $newOutcomeArray ), 
					array( 'meta_key' => 'extrasOutcomeName',
						   'post_id' => $resource->postID ),
					array( '%s' ), 
					array( '%s', '%d' ) 
				);
			} 
		  //send back a response
			wp_reset_postdata();
				$spiritualOutcomes = new WP_Query(array(
					'post_type' => 'spiritual_outcomes'
				));
			$redirectURL = get_the_permalink($outcome->postID);
			wp_die($redirectURL);
		}
		add_action('wp_ajax_restoreExtra','restoreExtra');
		
		
		//called when an admin chooses to send an Extras resource either higher or lower in the ordered list of Extras resources for an Outcome
		function bumpExtra() {
		  //check_ajax_referer
		  check_ajax_referer( 'bumpExtra' );
		  //get posted data
		  $postID = $_POST["postID"];
		  $outcomeName = $_POST["outcome"];
		  $orderDir = $_POST["order"];
		  $top = $_POST["top"];
		  $bottom = $_POST["bottom"];
		  $postID = (int)$postID;
		  $top = (int)$top;
		  $bottom = (int)$bottom;
		  //validate posted data
		  $outcomePostID = Outcome::getOutcomeIdByName($outcomeName);
		  $outcome = new Outcome($outcomePostID);
		  if ($outcome->statusCheck == "bad") {
		  	wp_die();
		  }
		  $user = new SgpUser(get_current_user_id());
		  if ($user->statusCheck == "bad") {
			  wp_die();
		  }
		  if (!(($orderDir == "up") || ($orderDir == "dn"))) {
		  	wp_die();
		  }
		  if ($orderDir == "up") {
			$topPostID = $top;
			$bottomPostID = $postID;
		  } else if ($orderDir == "dn") {
			$topPostID = $postID;
			$bottomPostID = $bottom;
		  }
		  $topResource = new Resource($topPostID);
		  if ($topResource->statusCheck == "bad") {
		  	wp_die();
		  }
		  $bottomResource = new Resource($bottomPostID);
		  if ($bottomResource->statusCheck == "bad") {
		  	wp_die();
		  }
		  //set other values
			global $wpdb;
		  //get listing order for all resources associated with this outcome
			$listedObjectArray = $wpdb->get_results($wpdb->prepare("SELECT resourceID FROM {$wpdb->prefix}extrasmeta WHERE outcomeID=%d ORDER BY listingOrder DESC", $outcome->entryID));
			if ($listedObjectArray == NULL) {
				$listedArray = array();
			} else {
				foreach ($listedObjectArray as $key => $object) {
					$listedArray[$key] = $object->resourceID;
				}
			}
		  //get listing order for affected resources
			$topListingOrder = $wpdb->get_var($wpdb->prepare("SELECT listingOrder FROM {$wpdb->prefix}extrasmeta WHERE outcomeID=%d AND resourceID=%d", $outcome->entryID, $topResource->entryID));
			$bottomListingOrder = $wpdb->get_var($wpdb->prepare("SELECT listingOrder FROM {$wpdb->prefix}extrasmeta WHERE outcomeID=%d AND resourceID=%d", $outcome->entryID, $bottomResource->entryID));	
		  //check case, prepare database, and get original order of all affected resources
			//case: both affected resources are unlisted in listingOrder
			if ($topListingOrder == NULL) {
				$unlistedCheck = 2;
				$latestDate = $wpdb->get_var($wpdb->prepare("SELECT created_at FROM {$wpdb->prefix}frm_items WHERE id=%d", $bottomResource->entryID));
				//get array for all of the resources from bottom date on
				$unlistedObjectArrayComplete = $wpdb->get_results($wpdb->prepare("SELECT item_id FROM {$wpdb->prefix}frm_item_metas WHERE field_id='759' AND meta_value=%d", $outcome->entryID));
				foreach ($unlistedObjectArrayComplete as $key => $object) {
					$unlistedArrayComplete[$key] = $object->item_id;
				}		
				rsort($unlistedArrayComplete);
				$marker = array_search($bottomEntryID, $unlistedArrayComplete) + 1;
				$unlistedArray = array_slice($unlistedArrayComplete, 0, $marker);
				//remove those resources that are already listed
				$unlistedArrayTruncated = array_diff($unlistedArray, $listedArray);
				$table_name = $wpdb->prefix . 'extrasmeta';
				foreach ($unlistedArrayTruncated as $resourceEntryID) {
					//add row for these resources in listing order database
					$wpdb->insert( 
						$table_name, 
						array( 
							'outcomeID' => $outcome->entryID, 
							'resourceID' => $resourceEntryID,
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
							'outcomeID' => $outcome->entryID, 
							'resourceID' => $bottomResource->entryID,
							'listingOrder' => '0',
							'created_at' => current_time( 'mysql' ) 
						), 
						array( '%d', '%d', '%d' )
					);		
			$oldOrder = $listedArray;
				$oldOrder[] = $bottomResource->entryID;		
			//case: both affected resources are listed
			} else {
				$unlistedCheck = 0;
				$oldOrder = $listedArray;
			}
		  //re-order according to new requirements
			//get numeric key for 2 top and bottom resources
			$topKey = array_search($topResource->entryID,$oldOrder);
			$bottomKey = array_search($bottomResource->entryID,$oldOrder);
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
			foreach ($newOrder as $key => $resourceEntryID) {
				$order = $key + 1;
				$wpdb->update( 
					$table_name, 
					array( 'listingOrder' => $order ), 
					array( 'resourceID' => $resourceEntryID,
						   'outcomeID' => $outcome->entryID ),
					array( '%d' ), 
					array( '%d', '%d' ) 
				); 
			}
			wp_die();
		}
		add_action('wp_ajax_bumpExtra','bumpExtra');
?>
