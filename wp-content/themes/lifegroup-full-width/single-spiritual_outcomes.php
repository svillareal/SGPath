<?php
/**
 * The Template for displaying single Spiritual Outcome main pages
 *	(part of the customized theme files for Gateway's website - designed by Sherilyn Villareal)

 * @package WordPress
 * @subpackage Twenty_Fourteen
 * @since Twenty Fourteen 1.0
 */

get_header(); ?>



  
<?php
/**Variables for individual outcome page**/

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
	
	/**Get Outcome title and content**/
	$outcomeName = get_the_title();
	$outcomeDefinition = get_field('outcome_definition');
	$outcomeDescription = get_field('outcome_descriptionFrm');
	$evidenceFieldName = array("evidence1", "evidence2", "evidence3", "evidence4");
	for ($i = 0; $i <= 3; $i++) {
		$evidence[$i] = get_field($evidenceFieldName[$i]);
	}
	$heartCheckLinkURL = get_field('heartCheckLinkID');

	global $wpdb;

	/**Get Section Titles and general page info**/
	$introTitle = stripslashes_deep($wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='705'"));
	$descriptionTitle = stripslashes_deep($wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='838'"));
	$evidenceTitle = stripslashes_deep($wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='839'"));
	$trainingTitle = stripslashes_deep($wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='706'"));
	$heartTitle = stripslashes_deep($wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='708'"));	
	$extrasTitle = stripslashes_deep($wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='709'"));
	$trainingInstructions = stripslashes_deep($wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='860'"));
	$heartInstructions = stripslashes_deep($wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='861'"));
	$extrasInstructions = stripslashes_deep($wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='842'"));
	$trainingProgressIcon = stripslashes_deep($wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='843'"));
	$heartProgressIcon = stripslashes_deep($wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='844'"));
	$coreImgFieldID = array("853", "854", "855", "856", "857", "858");
	for ($i = 0; $i <= 5; $i++) {
		$coreImgID[$i] = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='$coreImgFieldID[$i]'");
		$coreImgURL[$i] = wp_get_attachment_url( $coreImgID[$i] );
	}

	/**Get Core Training Form, Entry, Field ifno**/  
	$formName = "Resource Checkboxes - ".$outcomeName;
	$formID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_forms WHERE name='$formName'");
	$entryID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE user_id='$UserID' AND form_id='$formID' ORDER BY created_at DESC");
	$coreFieldOrder = array("0", "2", "4", "6", "8", "10");
	$resFieldOrder = array("1", "3", "5", "7", "9", "11");
	$versionFieldOrder = array("12", "13", "14", "15", "16", "17");
	for ($i = 0; $i <= 5; $i++) {
		$coreFieldID[$i] = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_fields WHERE form_id='$formID' AND field_order='$coreFieldOrder[$i]'");
		$resFieldID[$i] = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_fields WHERE form_id='$formID' AND field_order='$resFieldOrder[$i]'");
		$versionFieldID[$i] = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_fields WHERE form_id='$formID' AND field_order='$versionFieldOrder[$i]'");
		$lastCheckedResourceID[$i] = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='$resFieldID[$i]' AND item_id='$entryID'");
		$coreVersion[$i] = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='$versionFieldID[$i]' AND item_id='$entryID'");				
	}
		
	/**Check visibility status of Core Training sections**/  
	$postID = get_the_ID();
	$outcomeEntryID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE post_id='$postID'");
	$visibilityEntryID = $wpdb->get_var("SELECT item_id FROM {$wpdb->prefix}frm_item_metas WHERE meta_value='$outcomeEntryID' AND field_id='822'");
	$coreHideFieldID = array("823", "824", "825", "826", "827", "828");
	for ($i = 0; $i <= 5; $i++) {
	$coreHide[$i] = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id='$visibilityEntryID' AND field_id='$coreHideFieldID[$i]'");
	if (($coreHide[$i] == NULL) || ($coreHide[$i] == "0")) {
		$coreHideClass[$i] = "hidden"; $coreAddClass[$i] = ""; }
		else { $coreHideClass[$i] = ""; $coreAddClass[$i] = "hidden";}
	}
	
	/**Get Core Training content**/
	$coreCatName = array("Bible Study", "Reading", "Scripture Memory", "Activity", "Group Discussion", "Other");
	$coreCatNameNoSpc = array("BibleStudy", "Reading", "ScriptureMemory", "Activity", "GroupDiscussion", "Other");	
	for ($i = 0; $i <= 5; $i++) {
		$coreID[$i] = $wpdb->get_var("SELECT resourceEntryID FROM {$wpdb->prefix}coremeta WHERE outcomeID='$outcomeEntryID' AND coreCategory='$coreCatName[$i]' ORDER BY created_at DESC");
		if ((($coreID[$i] == NULL) || ($coreID[$i] == -1)) && (!(($userView == "admin") || ($userView == "pastor")))) {
			$coreHideClass[$i] = "hidden";
		}
	}
	
	/**Get Core Training completion status for user**/
	if ($userView !== "non_member") {
		$coreCheckedTot = 0;
		$coreCheckedTally = 0;
		for ($i = 0; $i <= 5; $i++) {
			if ($coreVersion[$i] == "new") {
				$coreCheckedValue == 0;
			} else {
			$coreCheckValue[$i] = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='$coreFieldID[$i]' AND item_id='$entryID'");	
			}
			if ($coreCheckValue[$i] == 1) { $coreChecked[$i] = "checked"; $coreImage[$i] = "opacity50"; $coreDiv[$i] = "style='color:lightGray'";}
				else {$coreChecked[$i] = ""; $coreImage[$i] = ""; $coreDiv[$i] = "";}
			if ($coreHide[$i] == "1" && !(($coreID[$i] == NULL) || ($coreID[$i] == -1))) {
				$coreCheckedTally = $coreCheckedTally + (int)$coreCheckValue[$i];
				$coreCheckedTot = $coreCheckedTot + 1;
			}
		}
		$coreCheckedPerc = ($coreCheckedTally/$coreCheckedTot)*100;
		$coreCheckedPercR = round($coreCheckedPerc, 3);
	}

	//check to see if user has checked off an older version of the resource before & get resource info
	for ($i = 0; $i <=5; $i++) {
		if (($coreID[$i] !== NULL) || ($coreID[$i] !== -1)) {
			if (($lastCheckedResourceID[$i] != $coreID[$i]) && ($coreCheckValue[$i] == "1") && ($coreVersion[$i] == "old")) {
				$version[$i] = "previous";
				$displayCoreID[$i] = $lastCheckedResourceID[$i];
			} else {
				$version[$i] = "current";
				$displayCoreID[$i] = $coreID[$i];
			}
				$corePostID[$i] = $wpdb->get_var("SELECT post_id FROM {$wpdb->prefix}frm_items WHERE id='$displayCoreID[$i]'");

			wp_reset_postdata();
				$extraResources = new WP_Query(array(
					'post_type' => 'resource'
				));
				//**Get Extras Resource info**//
				$coreTitle[$i] = get_the_title($corePostID[$i]);
				$coreType[$i] = get_field('extrasType', $corePostID[$i]);
				$coreDescription[$i] = get_field('extrasDescription', $corePostID[$i]);
				$coreTruncate[$i] = 0;
				if (strlen($coreDescription[$i]) >= 260) {
					$coreTruncate[$i] = 1;
					$coreExcerpt[$i] = substr($coreDescription[$i], 0, strpos($coreDescription[$i], ' ', 260));
				}
				$coreLinkURL[$i] = get_permalink($corePostID[$i]);
				if ($coreType[$i] == "Scripture Memory passages") {
					$corePassNum = get_field('extrasNumPassages', $corePostID[$i]);
					for ($a = 0; $a <= ($corePassNum - 1); $a++) {
						$b = $a + 1;
						$coreRef[$i][$a] = get_field('extrasRef'.$b, $corePostID[$i]);
					}
				}
		}
	}
	$coreDivID = array("divBibleStudy", "divReading", "divScriptureMemory", "divActivity", "divGroupDiscussion", "divOther");

	/**Get Heart Check Content**/  
	$heartCheckFieldIDArray = array(
		'484' => '461',
		'485' => '176',
		'486' => '195',
		'649' => '304',
		'651' => '311',
		'667' => '318',
		'669' => '332',
		'671' => '339',
		'673' => '346',
		'675' => '353',
		'677' => '360',
		'679' => '367',
		'681' => '374',
		'683' => '383',
		'685' => '390',
		'687' => '397',
		'689' => '404',
		'691' => '411',
		'693' => '418',
		'695' => '425',
		'697' => '432',
		'699' => '468',
		'701' => '475',
		'704' => '482',
		'706' => '489',
		'708' => '497',
		'710' => '504',
		'712' => '511',
		'714' => '518',
			);
//	$heartCheckFieldName = $outcomeName . " heart score";
//	$heartCheckFieldID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_fields WHERE name='$heartCheckFieldName'");
	$heartCheckFieldID = $heartCheckFieldIDArray[$postID];
	$heartCheckFormID = $wpdb->get_var("SELECT form_id FROM {$wpdb->prefix}frm_fields WHERE id='$heartCheckFieldID'");
	$heartCheckEntryID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE form_id='$heartCheckFormID' AND user_id='$UserID' ORDER BY created_at DESC");
	$heartCheckScore = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='$heartCheckFieldID' AND item_id='$heartCheckEntryID'");
	if ($heartCheckScore == "") {
		$heartCheckScore = 0;
		}

	//Image sources for Core Training labels over header images
	$coreTrnImgSrc = array (
		"http://localhost/lg/wp-content/uploads/2015/02/Study.png",
		"http://localhost/lg/wp-content/uploads/2015/02/Read.png",
		"http://localhost/lg/wp-content/uploads/2015/02/Memorize.png",
		"http://localhost/lg/wp-content/uploads/2015/02/Experience.png",
		"http://localhost/lg/wp-content/uploads/2015/02/Discuss.png",
		"http://localhost/lg/wp-content/uploads/2015/02/OtherTxt.png"
		)
  

//Produce the page layout
?>	<div id="primary" class="content-area">
		<div id="content" class="site-content test-content" role="main">
		  <?php if ($userView == "admin") { ?>
		  	<div class="admin-edit"><a title="Edit for ALL outcomes" class="btn btn-primary" href="/lg/meta-info-for-spiritual-outcome-pages/?frm_action=edit&entry=136">Edit Section Titles and instructions for ALL Outcomes</a></div>
          <?php } ?>
		  <div class="outcome-entry-title"><?php echo $outcomeName; ?></div>
			  
            <div id="outcome-menu">
            <div class="row">
            <div class="column3" align="center"><a href="#outcome-introduction"><?php echo $introTitle;?></a></div>
            <div class="column3" align="center"><a href="#outcome-training"><?php echo $trainingTitle;?></a></div>
            <div class="column3" align="center"><a href="#outcome-heart-check"><?php echo $heartTitle;?></a></div>
            <div class="column3" align="center"><a href="#outcome-extras"><?php echo $extrasTitle;?></a></div>
            </div>
            </div><!--outcome-menu-->
            
            <a name="outcome-introduction"></a>
            <div id="outcome-introduction" align="center">
            <h2 class="outcome-heading"><?php echo $introTitle;?>
            	<?php if (($userView == "admin") || ($userView == "pastor")) { ?>
                	&nbsp;<a title="Edit definition, description and other content for <?php echo $outcomeName;?> outcome" class="lg-edit-icon" href="/lg/edit-spiritual-outcome-pages/?frm_action=edit&entry=<?php echo $outcomeEntryID;?>"><i class="fa fa-pencil"></i></a>
                <?php } ?>    
              	</h2>
              <p><?php echo $outcomeDefinition; ?></p>
            <p><a id="learnMore">Learn More</a></p>
            </div><!--outcome introduction-->
            <div id="outcome-introduction-more" style="display:none;" align="left">
                <img src="http://localhost/lg/wp-content/uploads/2015/01/downArrow.png" class="learn-more-arrow">
                <h4 style="margin-top:0px;"><?php echo $descriptionTitle?></h4>
                <p><?php echo $outcomeDescription; ?></p>
                <h4><?php echo $evidenceTitle; ?></h4>
                <ul>
                <?php for ($i = 0; $i <= 3; $i++) {
                    if (!($evidence[$i] == "")) { ?>
                        <li><?php echo $evidence[$i]; ?></li>
                     <?php } 
                }    ?>
                </ul>	
            </div><!--outcome introduction-more-->
            
            <a name="#outcome-training"></a>
            <div id="outcome-training" align="center">
            <h2 class="outcome-heading"><?php echo $trainingTitle;?></h2>
			<?php if ($userView !== "non_member") { ?>
                <div class="row" align="center">
                <div class="column3" style="float:none">
                <div class="row" align="left">
                <div class="column2"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                <div class="resources-progress-bar column9">
                    <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR;?>%"></span>
                </div>
                </div> <!--resources progress bar row-->
                </div></div> <!--resources progress bar row-->
			<?php }             
			if ($trainingInstructions !== NULL) {
            	echo "<p>".$trainingInstructions."</p>";
            } ?>            
            <div class="row">
                <?php for ($i = 0; $i <= 5; $i++) { ?>
                    <div class="coreTrainingColumn training-section-show <?php echo $coreHideClass[$i];?>" align="left" id="<?php echo $coreDivID[$i];?>" <?php echo $coreDiv[$i];?>>
                        <div style="background: #ffffff url('<?php echo $coreImgURL[$i]?>') no-repeat center top;" class="training-section-image <?php echo $coreImage[$i];?>"><a href="<?php echo $coreLinkURL[$i];?>"><img class="training-section-img" src="<?php echo $coreTrnImgSrc[$i]?>"></a></div>
						<?php if ($userView !== "non_member") { ?>	
							<?php if (($version[$i] == "previous") && !(($coreID[$i] == NULL) || ($coreID[$i] == -1))) { ?>
                            <div class="update">
                            	<p style="color:green;">A newer resource is now recommneded here.</p>
                            	<button class="btn btn-success updatebtn" id="<?php echo $coreCatNameNoSpc[$i];?>Update" >Update Now</button>
                            </div>
                            <?php } ?>
							<?php if ($coreVersion[$i] == "new") { ?>	
								<a class="restore-old-version" id="<?php echo $coreCatNameNoSpc[$i];?>Restore">Restore old version</a>
                            <?php } ?>
                            <div class="TrainingCheck">
                              <input type="checkbox" value="<?php echo $coreID[$i];?>" <?php echo $coreChecked[$i]; ?> id="<?php echo $coreCatNameNoSpc[$i];?>Check" class="trainingCheckbox" name="check"/>
                              <label for="<?php echo $coreCatNameNoSpc[$i];?>Check">Complete?</label>
                            </div>
                        <?php }
        				if (($coreID[$i] == NULL) || ($coreID[$i] == -1)) {
							echo "<p style='color:red;'>You still need to add a resource or hide this section (below).<p>";
						} else { ?>
							<a href="<?php echo $coreLinkURL[$i];?>"><h4><?php echo $coreTitle[$i];?></h4></a>
							<?php if ($coreType[$i] !== "Scripture Memory passages") { 
								if ($coreTruncate[$i] == 1) {
								echo "<p>".$coreExcerpt[$i]."... <a href='".$coreLinkURL[$i]."'>Read More</a></p>";
								} else {
								echo "<p>".$coreDescription[$i]."</p>";
								}
							} else {
								echo "<p>";
								for ($a = 0; $a <= ($corePassNum - 1); $a++) {
									echo "&nbsp;&nbsp;&nbsp;&#8226;<strong>".$coreRef[$i][$a]."</strong><br/>";
								} 
								echo "</p><p><a href='".$coreLinkURL[$i]."'>See Passages</a></p>";
							}
						}
						if (($userView == "admin") || ($userView == "pastor")) { ?>
                            <div class="core-edit-links">
                                <a class="sectionHide">Hide</a> / 
                                <?php if (($coreID[$i] == NULL) || ($coreID[$i] == -1)) { ?>
                                    <a href="/lg/update-core-training-resources?outcomeName=<?php echo $outcomeName;?>&resourceCategory=<?php echo $coreCatName[$i];?>" class="sectionChange">Add</a>
                                <?php } else {
                                    echo FrmProEntriesController::entry_edit_link(array('id' => $coreID[$i], 'label' => 'Edit', 'page_id' => 275));?> / 
                                    <a href="/lg/update-core-training-resources?outcomeName=<?php echo $outcomeName;?>&resourceCategory=<?php echo $coreCatName[$i];?>" class="sectionChange">Change</a>
                                <?php } ?>
                            </div>
                         <?php } ?>
                    </div><!--coreTrainingColumn-->
              <?php } ?>
                <div class="clear"></div>
                <?php
				if (($userView == "admin") || ($userView == "pastor")) {
					$coreAddID = array("bsAddID", "rAddID", "smAddID", "aAddID", "gdAddID", "oAddID");
					for ($i = 0; $i <= 5; $i++)  { ?>
						<div class="add_section <?php echo $coreAddClass[$i];?>"><a id="<?php echo $coreAddID[$i];?>">Add <?php echo $coreCatName[$i];?> section</a></div> 
					<?php }
				} ?>
            </div><!--row-->
              
            </div><!--outcome training-->
                  
            <a name="#outcome-heart-check"></a>
            <div id="outcome-heart-check" align="center">
            <h2 class="outcome-heading"><?php echo $heartTitle;?></h2>
			<?php if ($userView !== "non_member") { ?>
                <div class="row" align="center">
                <div class="column3" style="float:none">
                <div class="row" align="left">
                <div class="column2"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                <div class="resources-progress-bar red-prog-bar column9">
                    <span style="width: <?php echo $heartCheckScore;?>%"></span>
                </div>
                </div> <!--heartcheck row-->
                </div></div>
			<?php } 
			if ($heartInstructions !== NULL) {
            	echo "<p>".$heartInstructions."</p>";
            } ?>
            <div class="row" align="center">
            <div class="column6" class="heart-check-button" style="float:none; padding-top:10px;">
              <a href="<?php echo $heartCheckLinkURL;?>"><button class="btn btn-danger btn-lg">Take the Assessment</button></a>
              <?php if (($userView == "admin") || ($userView == "pastor")) {?>
              	&nbsp;<a title="Edit URL for <?php echo $outcomeName;?> heart check assessment here" class="lg-edit-icon" href="/lg/edit-spiritual-outcome-pages/?frm_action=edit&entry=<?php echo $outcomeEntryID;?>"><i class="fa fa-pencil"></i></a>
			  <?php } ?>
            </div><!--heart check assessment button-->
			<?php if ($userView !== "non_member") { ?>
                <p><a id="heartScoresGraph">Track scores over time</a></p>
			<?php } ?>
            </div>
            </div><!--outcome heart check-->
			<?php if ($userView !== "non_member") { ?>
                <div id="outcome-heart-graph" style="display:none;">
                    <img src="http://localhost/lg/wp-content/uploads/2015/01/downArrow.png" class="learn-more-arrow">
                    <div id="heart-graph" align="center"><?php //This graph is too slow to load right now... I will work on a faster way to build this graph and then put it back here.<br/>?><?php echo FrmProStatisticsController::graph_shortcode(array('id' => $heartCheckFieldID, 'title' => $outcomeName . ' heart check scores', 'type' => 'line', 'x_axis' => 'created_at', 'data_type' => 'average', 'user_id' => '$UserID', 'min' => '0', 'max' => '100', 'grid_color' => 'green'));?></div>
                </div><!--outcome heart graph-->
			<?php } ?>
            
            <a name="outcome-extras"></a>
            <div id="outcome-extras" align="center">
            <h2 class="outcome-heading"><?php echo $extrasTitle;?></h2>
			<?php if ($extrasInstructions !== NULL) {
            	echo "<p>".$extrasInstructions."</p>";
            } ?>
            <?php 	
				$listingArray = array();
                wp_reset_postdata();
				$args = array(
					'posts_per_page' => -1,
					'post_type'  => 'resource',
					//add order by parameter for listing order
					'meta_query' => array(
						array(
							'key'     => 'extrasOutcomeName',
							'value'   => $outcomeEntryID,
							'compare' => 'LIKE',
						)
					)
				);
				$query = new WP_Query( $args );
				$count = $query->post_count;
				$countNum = 0;
				if ($query->have_posts()) {
					while($query->have_posts()) : $query->the_post();
					$countNum++;			

					//Get resource IDs and listing order
					$extraPostID = get_the_ID();
					$extraEntryID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE post_id='$extraPostID'");
					$listingOrder = $wpdb->get_var("SELECT listingOrder FROM {$wpdb->prefix}extrasmeta WHERE resourceID='$extraEntryID' AND outcomeID='$outcomeEntryID'");
					$coreStatus = "not core";
					for ($i = 0; $i <= 5; $i++) {
						if (($extraPostID == $corePostID[$i]) && ($coreHide[$i] !== '0')) { $coreStatus = "is core"; break; }
					}
					if ($coreStatus == "not core") {
						if ($listingOrder !== NULL) {
							$listingArray[$extraEntryID] = $listingOrder;
						} else {
							$listingArray[$extraEntryID] = (1-($countNum/$count));
						}
					}
					
					endwhile;
				} else {
				echo "<p>Sorry, there are no Extras resources associated with the ".$outcomeName." outcome.</p>";
				}
				//Sort according to listing order
				arsort($listingArray, SORT_NUMERIC);

				$loopCount = (count($listingArray)-1);
				foreach ($listingArray as $resID => $order) {	
					//Get post id and array info
					$extraPostID = $wpdb->get_var("SELECT post_id FROM {$wpdb->prefix}frm_items WHERE id='$resID'");
					$key = array_search($resID,array_keys($listingArray));
//					if ($order < 1) {
//						$listingOrderCheck = "list-unordered";
//					} else { $listingOrderCheck = "list-ordered"; }
					
					//Get Extras Resource info
					$extraTitle = get_the_title($extraPostID);
					$extraAuthor = get_field('extrasAuthor', $extraPostID);
					$extraResourceType = get_field('extrasType', $extraPostID);
					$extraDescription = get_field('extrasDescription', $extraPostID);
					$extraHide = get_field('extrasHide', $extraPostID);
					$extraListingOrder = get_field('extrasListingOrder', $extraPostID);
					$extraLinkURL = get_permalink($extraPostID);
		
					//Get Cover Image info
					$extraImageID = get_field('extrasImageID', $extraPostID);
					if ($extraImageID == NULL) {
						$extraImageID = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='863'");
					}
					$extraImageURL = wp_get_attachment_url( $extraImageID );
		
					//Display the list
					
					if (($userView == "admin") || ($userView == "pastor")) { ?>
                        <div class="column1 extras-controls">
                            <button class="remove-from-extras" id="extraID<?php echo $extraPostID;?>" type="button">Remove</button>
                            <?php if ($key !== 0) { ?>
                                <button class="bump-extras" id="upExtraID<?php echo $extraPostID;?>" type="button"><i class="fa fa-arrow-up"></i></button>
                            <?php } ?>
                            <?php if ($key !== $loopCount) { ?>
                                <button class="bump-extras" id="dnExtraID<?php echo $extraPostID;?>" type="button"><i class="fa fa-arrow-down"></i></button>                                
                            <?php } ?>
                        </div><!--column1-->
                    <?php }
					if ($extraResourceType == "Scripture Memory passages") {?>
					<div class="row" align="left">
						<div class="column2 extras-img" align="right">
							<a href="<?php echo $extraLinkURL;?>"><img class="extras-image" src="<?php echo $extraImageURL;?>"></a>
						</div><!--column2-->
						<div class="column7 extras-blurb">
							<div class="extras-entry-title"><a href="<?php echo $extraLinkURL ?>"><?php echo $extraTitle ?></a></div>
							<div class="resource-description">Click here to check out the Scripture Memory passages associated with this outcome.</div>
						</div><!--column7-->
						</div><!--row-->
					<?php } 
					
					else {?>
					<div class="row" align="left">
						<div class="column2 extras-img" align="right">
							<a href="<?php echo $extraLinkURL;?>"><img class="extras-image" src="<?php echo $extraImageURL;?>"></a>
						</div><!--column2-->
						<div class="column7 extras-blurb">
							<div class="extras-entry-title"><a href="<?php echo $extraLinkURL ?>"><?php echo $extraTitle ?></a></div>
							<?php if (!($extraAuthor == "")) { ?>
								<div class="extras-author">by <?php echo $extraAuthor;?></div>
							<?php } ?>
							<div class="resource-description"><?php echo $extraDescription ?></div>
						</div><!--column7-->
						</div><!--row-->
					<?php }
			}
 
            
			if (($userView == "admin") || ($userView == "pastor")) { ?>
                <div align="left"></br><a href="/lg/add-new-resources/" class="btn btn-primary" type="button">+Add New Resource</a></div>
                <div align="left"><p><i class="fa fa-trash"></i><em> &nbsp;&nbsp;<a href="http://localhost/lg/view-deleted-resources?outcomeName=<?php echo $outcomeName;?>">Click here</a> to view a list of resources previously deleted from the <?php echo $outcomeName;?> outcome.</em></p></div>
			<?php } ?>
           </div><!--outcome extras-->



		</div><!-- #content -->
	</div><!-- #primary -->

<?php

get_footer();
