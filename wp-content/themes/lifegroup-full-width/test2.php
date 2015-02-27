<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Full Content Template
 *
Template Name:  Test2 page
 *
 * @file           outcome-overview.php
 * @package        Life Group full width
 * @author         Sherilyn Villareal
 * @version        Release: 1.0
 */



get_header(); ?>

<?php 
	$x = 14;
	$y = 6;

function getOutcomeDiv() {
	global $x, $y;
	echo "X is ".$x."<br/>";
	$y = $x + $y;
}

getOutcomeDiv();

echo $y;


//Get user info
	$UserID = get_current_user_id();
//	$userObj = new WP_User($UserID);
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

//Get outcome info
	$trainingProgressIcon = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='843'");
	$heartProgressIcon = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='844'");
	$coreHideFieldID = array("823", "824", "825", "826", "827", "828");
	$coreFieldOrder = array("0", "2", "4", "6", "8", "10");
	wp_reset_postdata();
	$args = array(
		'posts_per_page' => -1,
		'post_type'  => 'spiritual_outcomes',
	);
	$query = new WP_Query($args);
	while($query->have_posts()) : $query->the_post();
		$postID = get_the_id();
		$entryID[$postID] = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE post_id='$postID'");
		$outcomeName[$postID] = get_the_title();
		$outcomeDefinition[$postID] = get_field('outcome_definition');
		$outcomeURL[$postID] = get_the_permalink();
		$imageID[$postID] = get_field('outcome_icon');
		$imageURL[$postID] = wp_get_attachment_url( $imageID[$postID] );
		//Check visibility status of Core Training sections
		$visibilityEntryID = $wpdb->get_var("SELECT item_id FROM {$wpdb->prefix}frm_item_metas WHERE meta_value='$entryID[$postID]' AND field_id='822'");
		for ($i = 0; $i <= 5; $i++) {
			$coreHide[$i] = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id='$visibilityEntryID' AND field_id='$coreHideFieldID[$i]'");
		}
		$coreHideArray[$postID] = $coreHide;
		//Get Core Training Form, Entry, Field ifno
		$formName = "Resource Checkboxes - ".$outcomeName[$postID];
		$formID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_forms WHERE name='$formName'");
		$coreEntryID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE user_id='$UserID' AND form_id='$formID' ORDER BY created_at DESC");
		for ($i = 0; $i <= 5; $i++) {
			$coreFieldID[$i] = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_fields WHERE form_id='$formID' AND field_order='$coreFieldOrder[$i]'");
		}
		//Get Core Training completion status for user
		$coreCheckedTot = 0;
		$coreCheckedTally = 0;
		for ($i = 0; $i <= 5; $i++) {
			$coreCheckValue[$i] = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='$coreFieldID[$i]' AND item_id='$coreEntryID'");	
			if ($coreHideArray[$postID][$i] == "1") {
				$coreCheckedTally = $coreCheckedTally + (int)$coreCheckValue[$i];
				$coreCheckedTot = $coreCheckedTot + 1;
			}
		}
		$coreCheckedPerc = ($coreCheckedTally/$coreCheckedTot)*100;
		$coreCheckedPercR[$postID] = round($coreCheckedPerc, 3);
		//Get Heart Check Content
		$heartCheckFieldName = $outcomeName[$postID]." heart score";
		$heartCheckFieldID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_fields WHERE name='$heartCheckFieldName'");
		$heartCheckFormID = $wpdb->get_var("SELECT form_id FROM {$wpdb->prefix}frm_fields WHERE id='$heartCheckFieldID'");
		$heartCheckEntryID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE form_id='$heartCheckFormID' AND user_id='$UserID' ORDER BY created_at DESC");
		$heartCheckScore[$postID] = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='$heartCheckFieldID' AND item_id='$heartCheckEntryID'");
		if ($heartCheckScore[$postID] == "") {
			$heartCheckScore[$postID] = 0;
			}
	endwhile;


/**echo $trying;
function getOutcomeDiv($divClasses, $thisPostID) {
	global $trying;
	global $outcomeURL, $imageURL;
	echo $trying."hello";
	echo "<div class='".$divClasses."'>
        <a href='".$outcomeURL[$thisPostID]."'><img src='".$imageURL[$thisPostID]."'></a>";
/**        <?php if ($userView !== "non_member") { ?>
        <div class="prog-div"><!--Progress bars-->
        <div class="row" align="left">
            <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
            <div class="resources-progress-bar column7">
                <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[$thisPostID];?>%"></span>
            </div>
        </div> <!--resources progress bar row-->
        <div class="row" align="left">
            <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
            <div class="resources-progress-bar red-prog-bar column7">
                <span style="width: <?php echo $heartCheckScore[$thisPostID];?>%"></span>
            </div>
        </div> <!--heartcheck row-->        
        </div><!--end Progress bars-->
        <?php }?>
        <a href="<?php echo $outcomeURL[$thisPostID];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[$thisPostID];?></h5></a>
        <p class="outcome-def"><?php echo $outcomeDefinition[$thisPostID];?></p>
 ?>   </div>
<?php } **/

?>

<div id="content-full" class="grid col-940">
<div class="outcome-entry-title">Spiritual Growth Path</div>
<div id="overview-menu">
    <div class="row">
        <div class="column3" align="center"><a href="#start-here">Start Here</a></div>
        <div class="column3" align="center"><a href="#discover-section">Discover</a></div>
        <div class="column3" align="center"><a href="#develop-section">Develop</a></div>
        <div class="column3" align="center"><a href="#deepen-section">Deepen</a></div>
    </div>
</div><!--outcome-menu-->
    
<a name="start-here"></a>
<div id="start-here">
<h2 class="outcome-heading">Start Here</h2>
    <div class="row">
		<div class="column4 start-here-column">
			<img src="http://localhost/lg/wp-content/uploads/2015/02/videoStill.jpg" style="width:100%">
            <h5>Way of Christ</h5>
            <p>We could put a short blurb here that introduces what we mean by the way of Christ and brielfy explains Love God, Build Character, Love People, and Be the Body.  They could click-through to learn more.</p>
            <button class="btn btn-primary">Learn More</button>
        </div>    
		<div class="column4 start-here-column">
			<img src="http://localhost/lg/wp-content/uploads/2015/02/groupImg.jpg" style="width:100%">
            <h5>Life Groups</h5>
            <p>This would be some kind of blurb about how it is strongly recommended that you engage this Spiritual Growth Path with a Life Group.  It would also have some kind of click-through to the Life Groups page.</p>
            <button class="btn btn-primary">Find a Group</button>
        </div>
		<?php if ($userView == "non_member") { ?>
        <div class="column4 start-here-column">
			<img src="http://localhost/lg/wp-content/uploads/2015/02/signup.jpg" style="width:100%">
            <h5>Sign Up for an Account</h5>
            <p>This section would only appear if someone was not currently logged in.  It would be a quick blurb about the benefits of getting a user account so that you can track progress, etc.</p>
            <button class="btn btn-primary">Sing Up Now!</button>
        </div>
        <?php } else { ?>
        <div class="column4 start-here-column">
			<img src="http://localhost/lg/wp-content/uploads/2015/02/profile.jpg" style="width:100%">
            <h5>Change Account Details</h5>
            <p>This section would only appear if someone was already signed up and currently logged in. It could give a user access to their profile settings where they could do things like add a group passcode.</p>
            <button class="btn btn-primary">Show My Profile</button>
        </div>        
        <?php } ?>
    </div><!--row-->
</div><!--start here-->

<?php // getOutcomeDiv("outcome-icon-div column20perc centered", "484"); ?>

<a name="discover-section"></a>
<div id="discover-section">
<h2 class="outcome-heading">Discover</h2>
    <div class="row"><!--Trust Christ section-->
		<div class="centered" align="center">
            <div class="row outcome-row">
				<div class="outcome-icon-div column20perc centered">
					<a href="<?php echo $outcomeURL[484];?>"><img src="<?php echo $imageURL[484];?>"></a>
					<?php if ($userView !== "non_member") { ?>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[484];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[484];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->        
					</div><!--end Progress bars-->
                    <?php }?>
					<a href="<?php echo $outcomeURL[484];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[484];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[484];?></p>
                </div>
            </div>
        </div>
	</div>
</div><!--discover section-->

<a name="develop-section"></a>
<div id="develop-section">
<h2 class="outcome-heading">Develop</h2>
    <div class="row"><!--Love God-->
		<div class="woc-section">
			<h4 class="woc-heading">Love God</h4>
            <div class="row outcome-row" align="center"><!--New outcome row-->
				<div class="outcome-icon-div column20perc float-to-left">
					<a href="<?php echo $outcomeURL[485];?>"><img src="<?php echo $imageURL[485];?>"></a>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[485];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[485];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->        
					</div><!--end progress bars-->
    				<a href="<?php echo $outcomeURL[485];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[485];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[485];?></p>
                </div>
				<div class="outcome-icon-div column20perc float-to-left">
					<a href="<?php echo $outcomeURL[486];?>"><img src="<?php echo $imageURL[486];?>"></a>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[486];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[486];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->
                    </div><!--Progress bars end-->
					<a href="<?php echo $outcomeURL[486];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[486];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[486];?></p>
                </div>
				<div class="outcome-icon-div column20perc float-to-left">
					<a href="<?php echo $outcomeURL[649];?>"><img src="<?php echo $imageURL[649];?>"></a>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[649];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[649];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->        
					</div><!--end progress bars-->
					<a href="<?php echo $outcomeURL[649];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[649];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[649];?></p>
                </div>
				<div class="outcome-icon-div column20perc float-to-left">
					<a href="<?php echo $outcomeURL[651];?>"><img src="<?php echo $imageURL[651];?>"></a>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[651];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[651];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->        
					</div><!--end Progress bars-->
					<a href="<?php echo $outcomeURL[651];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[651];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[651];?></p>
                </div>
				<div class="outcome-icon-div column20perc float-to-left">
					<a href="<?php echo $outcomeURL[667];?>"><img src="<?php echo $imageURL[667];?>"></a>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[667];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[667];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->        
					</div><!--end Progress bars-->
					<a href="<?php echo $outcomeURL[667];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[667];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[667];?></p>
                </div>

            </div><!--End outcome row-->

        </div>
	</div>
    <div class="row"><!--Build Character-->
		<div class="woc-section">
			<h4 class="woc-heading">Build Character</h4>
            <div class="row outcome-row" align="center"><!--New outcome row-->
				<div class="outcome-icon-div column20perc float-to-left">
					<a href="<?php echo $outcomeURL[669];?>"><img src="<?php echo $imageURL[669];?>"></a>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[669];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[669];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->        
					</div><!--end progress bars-->
    				<a href="<?php echo $outcomeURL[669];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[669];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[669];?></p>
                </div>
				<div class="outcome-icon-div column20perc float-to-left">
					<a href="<?php echo $outcomeURL[671];?>"><img src="<?php echo $imageURL[671];?>"></a>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[671];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[671];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->
                    </div><!--Progress bars end-->
					<a href="<?php echo $outcomeURL[671];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[671];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[671];?></p>
                </div>
				<div class="outcome-icon-div column20perc float-to-left">
					<a href="<?php echo $outcomeURL[673];?>"><img src="<?php echo $imageURL[673];?>"></a>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[673];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[673];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->        
					</div><!--end progress bars-->
					<a href="<?php echo $outcomeURL[673];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[673];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[673];?></p>
                </div>
				<div class="outcome-icon-div column20perc float-to-left">
					<a href="<?php echo $outcomeURL[675];?>"><img src="<?php echo $imageURL[675];?>"></a>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[675];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[675];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->        
					</div><!--end Progress bars-->
					<a href="<?php echo $outcomeURL[675];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[675];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[675];?></p>
                </div>
				<div class="outcome-icon-div column20perc float-to-left">
					<a href="<?php echo $outcomeURL[677];?>"><img src="<?php echo $imageURL[677];?>"></a>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[677];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[677];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->        
					</div><!--end Progress bars-->
					<a href="<?php echo $outcomeURL[677];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[677];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[677];?></p>
                </div>

            </div><!--End outcome row-->

        </div>
	</div>
    <div class="row"><!--Love People-->
		<div class="woc-section">
			<h4 class="woc-heading">Love People</h4>
            <div class="row outcome-row" align="center"><!--New outcome row-->
				<div class="outcome-icon-div column20perc float-to-left">
					<a href="<?php echo $outcomeURL[679];?>"><img src="<?php echo $imageURL[679];?>"></a>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[679];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[679];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->        
					</div><!--end progress bars-->
    				<a href="<?php echo $outcomeURL[679];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[679];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[679];?></p>
                </div>
				<div class="outcome-icon-div column20perc float-to-left">
					<a href="<?php echo $outcomeURL[681];?>"><img src="<?php echo $imageURL[681];?>"></a>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[681];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[681];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->
                    </div><!--Progress bars end-->
					<a href="<?php echo $outcomeURL[681];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[681];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[681];?></p>
                </div>
				<div class="outcome-icon-div column20perc float-to-left">
					<a href="<?php echo $outcomeURL[683];?>"><img src="<?php echo $imageURL[683];?>"></a>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[683];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[683];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->        
					</div><!--end progress bars-->
					<a href="<?php echo $outcomeURL[683];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[683];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[683];?></p>
                </div>
				<div class="outcome-icon-div column20perc float-to-left">
					<a href="<?php echo $outcomeURL[685];?>"><img src="<?php echo $imageURL[685];?>"></a>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[685];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[685];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->        
					</div><!--end Progress bars-->
					<a href="<?php echo $outcomeURL[685];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[685];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[685];?></p>
                </div>
				<div class="outcome-icon-div column20perc float-to-left">
					<a href="<?php echo $outcomeURL[687];?>"><img src="<?php echo $imageURL[687];?>"></a>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[687];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[687];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->        
					</div><!--end Progress bars-->
					<a href="<?php echo $outcomeURL[687];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[687];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[687];?></p>
                </div>

            </div><!--End outcome row-->

        </div>
	</div>
    <div class="row"><!--Be the Body-->
		<div class="woc-section">
			<h4 class="woc-heading">Be the Body</h4>
            <div class="row outcome-row" align="center"><!--New outcome row-->
				<div class="outcome-icon-div column20perc float-to-left">
					<a href="<?php echo $outcomeURL[689];?>"><img src="<?php echo $imageURL[689];?>"></a>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[689];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[689];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->        
					</div><!--end Progress bars-->
					<a href="<?php echo $outcomeURL[689];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[689];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[689];?></p>
                </div>
				<div class="outcome-icon-div column20perc float-to-left">
					<a href="<?php echo $outcomeURL[691];?>"><img src="<?php echo $imageURL[691];?>"></a>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[691];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[691];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->        
					</div><!--end progress bars-->
    				<a href="<?php echo $outcomeURL[691];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[691];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[691];?></p>
                </div>
				<div class="outcome-icon-div column20perc float-to-left">
					<a href="<?php echo $outcomeURL[693];?>"><img src="<?php echo $imageURL[693];?>"></a>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[693];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[693];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->
                    </div><!--Progress bars end-->
					<a href="<?php echo $outcomeURL[693];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[693];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[693];?></p>
                </div>
				<div class="outcome-icon-div column20perc float-to-left">
					<a href="<?php echo $outcomeURL[695];?>"><img src="<?php echo $imageURL[695];?>"></a>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[695];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[695];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->        
					</div><!--end progress bars-->
					<a href="<?php echo $outcomeURL[695];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[695];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[695];?></p>
                </div>
				<div class="outcome-icon-div column20perc float-to-left">
					<a href="<?php echo $outcomeURL[697];?>"><img src="<?php echo $imageURL[697];?>"></a>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[697];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[697];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->        
					</div><!--end Progress bars-->
					<a href="<?php echo $outcomeURL[697];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[697];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[697];?></p>
                </div>

            </div><!--End outcome row-->

        </div>
	</div>

</div><!--develop section-->

<a name="deepen-section"></a>
<div id="deepen-section">
<h2 class="outcome-heading">Deepen</h2>
    <div class="row"><!--Love God-->
		<div class="column6 woc-section">
			<h4 class="woc-heading">Love God</h4>
            <div class="row outcome-row" align="center"><!--New outcome row-->
				<div class="outcome-icon-div column5 float-to-left">
					<a href="<?php echo $outcomeURL[699];?>"><img src="<?php echo $imageURL[699];?>"></a>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[699];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[699];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->        
					</div><!--end progress bars-->
    				<a href="<?php echo $outcomeURL[699];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[699];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[699];?></p>
                </div>
				<div class="outcome-icon-div column5 float-to-left">
					<a href="<?php echo $outcomeURL[701];?>"><img src="<?php echo $imageURL[701];?>"></a>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[701];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[701];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->
                    </div><!--Progress bars end-->
					<a href="<?php echo $outcomeURL[701];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[701];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[701];?></p>
                </div>
            </div><!--End outcome row-->

        </div>
		<div class="column6 woc-section"><!--Build Character-->
			<h4 class="woc-heading">Build Character</h4>
            <div class="row outcome-row" align="center"><!--New outcome row-->
				<div class="outcome-icon-div column5 float-to-left">
					<a href="<?php echo $outcomeURL[704];?>"><img src="<?php echo $imageURL[704];?>"></a>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[704];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[704];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->        
					</div><!--end progress bars-->
    				<a href="<?php echo $outcomeURL[704];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[704];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[704];?></p>
                </div>
				<div class="outcome-icon-div column5 float-to-left">
					<a href="<?php echo $outcomeURL[706];?>"><img src="<?php echo $imageURL[706];?>"></a>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[706];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[706];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->
                    </div><!--Progress bars end-->
					<a href="<?php echo $outcomeURL[706];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[706];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[706];?></p>
                </div>

            </div><!--End outcome row-->

        </div>
	</div>
    <div class="row"><!--Love People-->
		<div class="column6 woc-section">
			<h4 class="woc-heading">Love People</h4>
            <div class="row outcome-row" align="center"><!--New outcome row-->
				<div class="outcome-icon-div column5 float-to-left">
					<a href="<?php echo $outcomeURL[708];?>"><img src="<?php echo $imageURL[708];?>"></a>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[708];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[708];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->        
					</div><!--end progress bars-->
    				<a href="<?php echo $outcomeURL[708];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[708];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[708];?></p>
                </div>
				<div class="outcome-icon-div column5 float-to-left">
					<a href="<?php echo $outcomeURL[710];?>"><img src="<?php echo $imageURL[710];?>"></a>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[710];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[710];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->
                    </div><!--Progress bars end-->
					<a href="<?php echo $outcomeURL[710];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[710];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[710];?></p>
                </div>
            </div><!--End outcome row-->

        </div>
		<div class="column6 woc-section"><!--Be the Body-->
			<h4 class="woc-heading">Be the Body</h4>
            <div class="row outcome-row" align="center"><!--New outcome row-->
				<div class="outcome-icon-div column5 float-to-left">
					<a href="<?php echo $outcomeURL[712];?>"><img src="<?php echo $imageURL[712];?>"></a>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[712];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[712];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->        
					</div><!--end Progress bars-->
					<a href="<?php echo $outcomeURL[712];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[712];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[712];?></p>
                </div>
				<div class="outcome-icon-div column5 float-to-left">
					<a href="<?php echo $outcomeURL[714];?>"><img src="<?php echo $imageURL[714];?>"></a>
					<div class="prog-div"><!--Progress bars-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $trainingProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar column7">
                            <span id="coreCheckedPerc" style="width: <?php echo $coreCheckedPercR[714];?>%"></span>
                        </div>
                    </div> <!--resources progress bar row-->
                    <div class="row" align="left">
                        <div class="column2 prog-bar-icon"><i class="fa <?php echo $heartProgressIcon;?>"></i></div>
                        <div class="resources-progress-bar red-prog-bar column7">
                            <span style="width: <?php echo $heartCheckScore[714];?>%"></span>
                        </div>
                    </div> <!--heartcheck row-->        
					</div><!--end progress bars-->
    				<a href="<?php echo $outcomeURL[714];?>"><h5 class="outcome-label-heading"><?php echo $outcomeName[714];?></h5></a>
                    <p class="outcome-def"><?php echo $outcomeDefinition[714];?></p>
                </div>
           </div><!--End outcome row-->

        </div>
	</div>
</div><!--deepen section-->

</div><!-- end of #content-full -->


<?php get_footer(); ?>
