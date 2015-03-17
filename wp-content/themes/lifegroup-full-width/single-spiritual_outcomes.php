<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Template for displaying single Spiritual Outcome main pages
 *	(part of the customized theme files for Gateway's website - designed by Sherilyn Villareal)

 * @package WordPress
 * @subpackage Twenty_Fourteen
 * @since Twenty Fourteen 1.0
 */

get_header(); ?>

<?php
//Get required files
include_once('spg-functions.php');

//Get content
	
	//Outcome-specific content
	$outcome = new Outcome(get_the_ID());
	
	//Page content
	$outcomePage = new OutcomePage();
	$coreCategories = new CoreCategories();
	$numberOfCore = CoreCategories::numCoreCategories();
	
	//User-specific content
	$currentSgpUser = new SgpUser(get_current_user_id());
	list($coreHideClass, $coreAddClass) = $outcome->getCoreVisibility($currentSgpUser->userView);
	$coreTrainingStatus = new CoreTrainingStatus($outcome->postID, $currentSgpUser->userID);
	$heartCheck = new HeartCheckStatus($outcome->postID, $currentSgpUser->userID);
	$extrasDisplay = $coreTrainingStatus->getDisplayedExtras($outcome->postID);
	foreach ($coreTrainingStatus->corePostID as $key => $value) {
		$coreResource[$key] = new Resource($value);
	}

//Produce the page layout
?>	<div id="primary" class="content-area">
		<div id="content" class="site-content test-content" role="main">
		  <?php if ($currentSgpUser->userView == "admin") { ?>
		  	<div class="admin-edit"><a title="Edit for ALL outcomes" class="btn btn-primary" href="/lg/meta-info-for-spiritual-outcome-pages/?frm_action=edit&entry=136">Edit Section Titles and instructions for ALL Outcomes</a></div>
          <?php } ?>
		  <div class="outcome-entry-title"><?php echo $outcome->title; ?></div>
			  
            <div id="outcome-menu">
            <div class="row">
            <div class="column3" align="center"><a href="#outcome-introduction"><?php echo $outcomePage->introTitle;?></a></div>
            <div class="column3" align="center"><a href="#outcome-training"><?php echo $outcomePage->trainingTitle;?></a></div>
            <div class="column3" align="center"><a href="#outcome-heart-check"><?php echo $outcomePage->heartTitle;?></a></div>
            <div class="column3" align="center"><a href="#outcome-extras"><?php echo $outcomePage->extrasTitle;?></a></div>
            </div>
            </div><!--outcome-menu-->
            
            <a name="outcome-introduction"></a>
            <div id="outcome-introduction" align="center">
            <h2 class="outcome-heading"><?php echo $outcomePage->introTitle;?>
            	<?php if (($currentSgpUser->userView == "admin") || ($currentSgpUser->userView == "pastor")) { ?>
                	&nbsp;<a title="Edit definition, description and other content for <?php echo $outcome->title;?> outcome" class="lg-edit-icon" href="/lg/edit-spiritual-outcome-pages/?frm_action=edit&entry=<?php echo $outcome->entryID;?>"><i class="fa fa-pencil"></i></a>
                <?php } ?>    
              	</h2>
              <p><?php echo $outcome->definition; ?></p>
            <p><a id="learnMore">Learn More</a></p>
            </div><!--outcome introduction-->
            <div id="outcome-introduction-more" style="display:none;" align="left">
                <img src="http://localhost/lg/wp-content/uploads/2015/01/downArrow.png" class="learn-more-arrow">
                <h4 style="margin-top:0px;"><?php echo $outcomePage->descriptionTitle?></h4>
                <p><?php echo $outcome->description; ?></p>
                <h4><?php echo $outcomePage->evidenceTitle; ?></h4>
                <ul>
                <?php for ($i = 0; $i <= 3; $i++) {
                    if (!($outcome->evidence[$i] == "")) { ?>
                        <li><?php echo $outcome->evidence[$i]; ?></li>
                     <?php } 
                }    ?>
                </ul>	
            </div><!--outcome introduction-more-->
            
            <a name="#outcome-training"></a>
            <div id="outcome-training" align="center">
            <h2 class="outcome-heading"><?php echo $outcomePage->trainingTitle;?></h2>
			<?php if ($currentSgpUser->userView !== "non_member") { ?>
                <div class="row" align="center">
                <div class="column3" style="float:none">
                <div class="row" align="left">
                <div class="column2"><i class="fa <?php echo $outcomePage->trainingProgressIcon;?>"></i></div>
                <div class="resources-progress-bar column9">
                    <span id="coreCheckedPerc" style="width: <?php echo $coreTrainingStatus->coreCheckedScore;?>%"></span>
                </div>
                </div> <!--resources progress bar row-->
                </div></div> <!--resources progress bar row-->
			<?php }             
			if ($outcomePage->trainingInstructions !== NULL) {
            	echo "<p>".$outcomePage->trainingInstructions."</p>";
            } ?>            
            <div class="row">
                <?php for ($i = 0; $i <= ($numberOfCore-1); $i++) { ?>
                    
                    <div class="coreTrainingColumn training-section-show <?php echo $coreHideClass[$i];?>" align="left" id="<?php echo CoreCategories::$coreDivID[$i];?>" <?php echo $coreTrainingStatus->getCoreDiv($i);?>>
                        <div style="background: #ffffff url('<?php echo CoreCategories::$coreImgURL[$i];?>') no-repeat center top;" class="training-section-image <?php echo $coreTrainingStatus->getCoreImage($i);?>"><a href="http://localhost/lg/resource/intro-to-relate-to-god/<?php //echo $coreResource[$i]->internalURL;?>"><img class="training-section-img" src="<?php echo CoreCategories::$coreTrnImgSrc[$i];?>"></a></div>
						<?php if ($currentSgpUser->userView !== "non_member") { ?>	
    						<?php if (($coreTrainingStatus->version[$i] == "previous") && !(($outcome->coreID[$i] == NULL) || ($outcome->coreID[$i] == -1))) { ?>
                            <div class="update">
                            	<p style="color:green;">A newer resource is now recommneded here.</p>
                            	<button class="btn btn-success updatebtn" id="<?php echo CoreCategories::$coreCatNoSpace[$i];?>Update" >Update Now</button>
                            </div>
                            <?php } ?>
							<?php if ($coreTrainingStatus->coreVersion[$i] == "new") { ?>	
								<a class="restore-old-version" id="<?php echo CoreCategories::$coreCatNoSpace[$i];?>Restore">Restore old version</a>
                            <?php } ?>
                            <div class="TrainingCheck">
                              <input type="checkbox" value="<?php echo $outcome->coreID[$i];?>" <?php echo $coreTrainingStatus->getCoreChecked($i); ?> id="<?php echo CoreCategories::$coreCatNoSpace[$i];?>Check" class="trainingCheckbox" name="check"/>
                              <label for="<?php echo CoreCategories::$coreCatNoSpace[$i];?>Check">Complete?</label>
                            </div>
                        <?php }
        				if (($outcome->coreID[$i] == NULL) || ($outcome->coreID[$i] == -1)) {
							echo "<p style='color:red;'>You still need to add a resource or hide this section (below).<p>";
						} else { ?>
							<a href="<?php echo $coreResource[$i]->internalURL;?>"><h4><?php echo $coreResource[$i]->title;?></h4></a>
							<?php if ($coreResource[$i]->type !== "Scripture Memory passages") { 
								if ($coreResource[$i]->truncate == 1) {
								echo "<p>".$coreResource[$i]->excerpt."... <a href='".$coreResource[$i]->internalURL."'>Read More</a></p>";
								} else {
								echo "<p>".$coreResource[$i]->description."</p>";
								}
							} else {
								echo "<p>";
								for ($a = 0; $a <= ($coreResource[$i]->numberOfPassages - 1); $a++) {
									echo "&nbsp;&nbsp;&nbsp;&#8226;<strong>".$coreResource[$i]->scriptureReference[$a]."</strong><br/>";
								} 
								echo "</p><p><a href='".$coreResource[$i]->internalURL."'>See Passages</a></p>";
							}
						}
						if (($currentSgpUser->userView == "admin") || ($currentSgpUser->userView == "pastor")) { ?>
                            <div class="core-edit-links">
                                <a class="sectionHide">Hide</a> / 
                                <?php if (($outcome->coreID[$i] == NULL) || ($outcome->coreID[$i] == -1)) { ?>
                                    <a href="/lg/update-core-training-resources?outcomeName=<?php echo $outcome->title;?>&resourceCategory=<?php echo CoreCategories::$coreCategories[$i];?>" class="sectionChange">Add</a>
                                <?php } else {
                                    echo FrmProEntriesController::entry_edit_link(array('id' => $outcome->coreID[$i], 'label' => 'Edit', 'page_id' => 275));?> / 
                                    <a href="/lg/update-core-training-resources?outcomeName=<?php echo $outcome->title;?>&resourceCategory=<?php echo CoreCategories::$coreCategories[$i];?>" class="sectionChange">Change</a>
                                <?php } ?>
                            </div>
                         <?php } ?>
                    </div><!--coreTrainingColumn-->
              <?php } ?>
                <div class="clear"></div>
                <?php
				if (($currentSgpUser->userView == "admin") || ($currentSgpUser->userView == "pastor")) {
					for ($i = 0; $i <= ($numberOfCore-1); $i++)  { ?>
						<div class="add_section <?php echo $coreAddClass[$i];?>"><a id="<?php echo CoreCategories::$coreAddID[$i];?>">Add <?php echo CoreCategories::$coreCategories[$i];?> section</a></div> 
					<?php }
				} ?>
            </div><!--row-->
              
            </div><!--outcome training-->
                  
            <a name="#outcome-heart-check"></a>
            <div id="outcome-heart-check" align="center">
            <h2 class="outcome-heading"><?php echo $outcomePage->heartTitle;?></h2>
			<?php if ($currentSgpUser->userView !== "non_member") { ?>
                <div class="row" align="center">
                <div class="column3" style="float:none">
                <div class="row" align="left">
                <div class="column2"><i class="fa <?php echo $outcomePage->heartProgressIcon;?>"></i></div>
                <div class="resources-progress-bar red-prog-bar column9">
                    <span style="width: <?php echo $heartCheck->score;?>%"></span>
                </div>
                </div> <!--heartcheck row-->
                </div></div>
			<?php } 
			if ($outcomePage->heartInstructions !== NULL) {
            	echo "<p>".$outcomePage->heartInstructions."</p>";
            } ?>
            <div class="row" align="center">
            <div class="column6" class="heart-check-button" style="float:none; padding-top:10px;">
              <a href="<?php echo $outcome->heartCheckURL;?>"><button class="btn btn-danger btn-lg">Take the Assessment</button></a>
              <?php if (($currentSgpUser->userView == "admin") || ($currentSgpUser->userView == "pastor")) {?>
              	&nbsp;<a title="Edit URL for <?php echo $outcome->title;?> heart check assessment here" class="lg-edit-icon" href="/lg/edit-spiritual-outcome-pages/?frm_action=edit&entry=<?php echo $outcome->entryID;?>"><i class="fa fa-pencil"></i></a>
			  <?php } ?>
            </div><!--heart check assessment button-->
			<?php if ($currentSgpUser->userView !== "non_member") { ?>
                <p><a id="heartScoresGraph">Track scores over time</a></p>
			<?php } ?>
            </div>
            </div><!--outcome heart check-->
			<?php if ($currentSgpUser->userView !== "non_member") { ?>
                <div id="outcome-heart-graph" style="display:none;">
                    <img src="http://localhost/lg/wp-content/uploads/2015/01/downArrow.png" class="learn-more-arrow">
                    <div id="heart-graph" align="center"><?php echo "This graph is too slow to load right now... I will work on a faster way to build this graph and then put it back here.<br/>"?><?php //need to turn this into a function in HeartCheck Status... echo FrmProStatisticsController::graph_shortcode(array('id' => $heartCheckFieldID, 'title' => $outcome->title . ' heart check scores', 'type' => 'line', 'x_axis' => 'created_at', 'data_type' => 'average', 'user_id' => '$currentSgpUser->userID', 'min' => '0', 'max' => '100', 'grid_color' => 'green'));?></div>
                </div><!--outcome heart graph-->
			<?php } ?>
            
            <a name="outcome-extras"></a>
            <div id="outcome-extras" align="center">
            <h2 class="outcome-heading"><?php echo $outcomePage->extrasTitle;?></h2>
			<?php if ($outcomePage->extrasInstructions !== NULL) {
            	echo "<p>".$outcomePage->extrasInstructions."</p>";
            } ?>
            <?php 	

			//Display the extras list
				if ($extrasDisplay == NULL) {
					echo "<p>Sorry, there are no Extras resources associated with the ".$outcome->title." outcome.</p>";
				} else {
					$loopCount = (count($extrasDisplay)-1);				
					foreach ($extrasDisplay as $key => $entryID) {
						$resource = new Resource(getPostID($entryID));
						if (($currentSgpUser->userView == "admin") || ($currentSgpUser->userView == "pastor")) { ?>
							<div class="column1 extras-controls">
								<button class="remove-from-extras" id="extraID<?php echo $resource->postID;?>" type="button">Remove</button>
								<?php if ($key !== 0) { ?>
									<button class="bump-extras" id="upExtraID<?php echo $resource->postID;?>" type="button"><i class="fa fa-arrow-up"></i></button>
								<?php } ?>
								<?php if ($key !== $loopCount) { ?>
									<button class="bump-extras" id="dnExtraID<?php echo $resource->postID;?>" type="button"><i class="fa fa-arrow-down"></i></button>                                
								<?php } ?>
							</div><!--column1-->
						<?php }
						if ($resource->type == "Scripture Memory passages") {?>
						<div class="row" align="left">
							<div class="column2 extras-img" align="right">
								<a href="<?php echo $resource->internalURL;?>"><img class="extras-image" src="<?php echo $resource->imageURL;?>"></a>
							</div><!--column2-->
							<div class="column7 extras-blurb">
								<div class="extras-entry-title"><a href="<?php echo $resource->internalURL ?>"><?php echo $resource->title ?></a></div>
								<div class="resource-description">Click here to check out the Scripture Memory passages associated with this outcome.</div>
							</div><!--column7-->
							</div><!--row-->
						<?php } 
						
						else {?>
						<div class="row" align="left">
							<div class="column2 extras-img" align="right">
								<a href="<?php echo $resource->internalURL;?>"><img class="extras-image" src="<?php echo $resource->imageURL;?>"></a>
							</div><!--column2-->
							<div class="column7 extras-blurb">
								<div class="extras-entry-title"><a href="<?php echo $resource->internalURL ?>"><?php echo $resource->title ?></a></div>
								<?php if (!($resource->author == "")) { ?>
									<div class="extras-author">by <?php echo $resource->author;?></div>
								<?php } ?>
								<div class="resource-description"><?php echo $resource->description ?></div>
							</div><!--column7-->
							</div><!--row-->
						<?php }
					}
				} 
		
			if (($currentSgpUser->userView == "admin") || ($currentSgpUser->userView == "pastor")) { ?>
                <div align="left"></br><a href="/lg/add-new-resources/" class="btn btn-primary" type="button">+Add New Resource</a></div>
                <div align="left"><p><i class="fa fa-trash"></i><em> &nbsp;&nbsp;<a href="http://localhost/lg/view-deleted-resources?outcomeName=<?php echo $outcome->title;?>">Click here</a> to view a list of resources previously deleted from the <?php echo $outcome->title;?> outcome.</em></p></div>
			<?php } ?>
           </div><!--outcome extras-->



		</div><!-- #content -->
	</div><!-- #primary -->

<?php

get_footer();
