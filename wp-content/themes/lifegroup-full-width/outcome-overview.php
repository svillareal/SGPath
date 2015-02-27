<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Full Content Template
 *
Template Name:  Spiritual Outcome Overview page
 *
 * @file           outcome-overview.php
 * @package        Life Group full width
 * @author         Sherilyn Villareal
 * @version        Release: 1.0
 */

get_header(); 

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

//Get outcome info
	$coreCatName = array("Bible Study", "Reading", "Scripture Memory", "Activity", "Group Discussion", "Other");
	$coreCatNameNoSpc = array("BibleStudy", "Reading", "ScriptureMemory", "Activity", "GroupDiscussion", "Other");	
	$trainingProgressIcon = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='843'");
	$heartProgressIcon = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='844'");
	$coreHideFieldID = array("823", "824", "825", "826", "827", "828");
	$coreFieldOrder = array("0", "2", "4", "6", "8", "10");
	$heartCheckFieldID = array(
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
		for ($i = 0; $i <= 5; $i++) {
			$coreID[$i] = $wpdb->get_var("SELECT resourceEntryID FROM {$wpdb->prefix}coremeta WHERE outcomeID='$entryID[$postID]' AND coreCategory='$coreCatName[$i]' ORDER BY created_at DESC");
		}
		$coreCheckedTot = 0;
		$coreCheckedTally = 0;
		for ($i = 0; $i <= 5; $i++) {
			$coreCheckValue[$i] = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='$coreFieldID[$i]' AND item_id='$coreEntryID'");	
			if (($coreHideArray[$postID][$i] == "1") && !(($coreID[$i] == NULL) || ($coreID[$i] == -1))) {
				$coreCheckedTally = $coreCheckedTally + (int)$coreCheckValue[$i];
				$coreCheckedTot = $coreCheckedTot + 1;
			}
		}
		$coreCheckedPerc = ($coreCheckedTally/$coreCheckedTot)*100;
		$coreCheckedPercR[$postID] = round($coreCheckedPerc, 3);
		//Get Heart Check Content
//		$heartCheckFieldName = $outcomeName[$postID]." heart score";
//		$heartCheckFieldID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_fields WHERE name='$heartCheckFieldName'");
		$heartCheckFieldIDtemp = $heartCheckFieldID[$postID];
		$heartCheckFormID = $wpdb->get_var("SELECT form_id FROM {$wpdb->prefix}frm_fields WHERE id='$heartCheckFieldIDtemp'");
		$heartCheckEntryID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE form_id='$heartCheckFormID' AND user_id='$UserID' ORDER BY created_at DESC");
		$heartCheckScore[$postID] = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='$heartCheckFieldIDtemp' AND item_id='$heartCheckEntryID'");
		if ($heartCheckScore[$postID] == "") {
			$heartCheckScore[$postID] = 0;
			}
	endwhile;


function getIcon($outcomeLink, $imageLink) {
        echo "<a href='".$outcomeLink."'><img src='".$imageLink."'></a>";
}

function getProgBars($trainingProgressIcon, $coreCheckedPerc, $heartProgressIcon, $heartCheckScore) {
	echo "<div class='prog-div'><!--Progress bars-->
	<div class='row' align='left'>
		<div class='column2 prog-bar-icon'><i class='fa ".$trainingProgressIcon."'></i></div>
		<div class='resources-progress-bar column7'>
			<span id='coreCheckedPerc' style='width: ".$coreCheckedPerc."%'></span>
		</div>
	</div> <!--resources progress bar row-->
	<div class='row' align='left'>
		<div class='column2 prog-bar-icon'><i class='fa ".$heartProgressIcon."'></i></div>
		<div class='resources-progress-bar red-prog-bar column7'>
			<span style='width: ".$heartCheckScore."%'></span>
		</div>
	</div> <!--heartcheck row-->        
	</div><!--end Progress bars-->";
}

function getOutcomeInfo($outcomeLink, $outcomeName, $outcomeDefinition) {
     echo "<a href='".$outcomeLink."'><h5 class='outcome-label-heading'>".$outcomeName."</h5></a>
        <p class='outcome-def'>".$outcomeDefinition."</p>";
}

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
			<a href="http://localhost/lg/sign-up/"><img src="http://localhost/lg/wp-content/uploads/2015/02/signup.jpg" style="width:100%"></a>
            <a href="http://localhost/lg/sign-up/"><h5>Sign Up for an Account</h5></a>
            <p>This section would only appear if someone was not currently logged in.  It would be a quick blurb about the benefits of getting a user account so that you can track progress, etc.</p>
            <a href="http://localhost/lg/sign-up/"><button class="btn btn-primary">Sing Up Now!</button></a>
        </div>
        <?php } else { ?>
        <div class="column4 start-here-column">
			<a href="http://localhost/lg/profile/"><img src="http://localhost/lg/wp-content/uploads/2015/02/profile.jpg" style="width:100%"></a>
            <a href="http://localhost/lg/profile/"><h5>Add Group Info / Update Profile</h5></a>
            <p>This section would only appear if someone was already signed up and currently logged in. It could give a user access to their profile settings where they could do things like add a group passcode, etc.</p>
            <a href="http://localhost/lg/profile/"><button class="btn btn-primary">Show My Profile</button></a>
        </div>        
        <?php } ?>
    </div><!--row-->
</div><!--start here-->

<?php // getOutcomeDiv("outcome-icon-div column20perc centered", "484"); ?>
<?php if (($userView == "leader") || ($userView == "admin") || ($userView == "pastor")) { ?>
<div id="leader-reports">
<h2 class="outcome-heading">Your Life Group Info</h2>
    <div class="row">
		<div class="column4 start-here-column">
            <a href="/lg/group-members/"><h4>See your Group Members</h4></a>
            <p>This could link to a clickable list of their life group members.  They could use this section to find out who has registered for an account and see how their group members are doing on the outcomes.</p>
        </div>    
		<div class="column4 start-here-column">
            <a href="/lg/group-stats/"><h4>Get your Group Stats</h4></a>
            <p>This could go to a list of reports that a Life Group leader can view to see stats specific to their Life Group.</p>
        </div>    
		<div class="column4 start-here-column">
            <a href="/lg/profile/"><h4>Edit your Group Info</h4></a>
            <p>This could lead to a page where Life Group leaders can update info related to their Life Groups.</p>
        </div>    
    </div><!--row-->
</div><!--start here-->

<?php } ?>


<a name="discover-section"></a>
<div id="discover-section">
<h2 class="outcome-heading">Discover</h2>
    <div class="row"><!--Trust Christ section-->
		<div class="centered" align="center">
            <div class="row outcome-row">
				<div class="outcome-icon-div column20perc centered">
					<?php
					$thisPostID = 484;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
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
					<?php
					$thisPostID = 485;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
				</div>
				<div class="outcome-icon-div column20perc float-to-left">
					<?php
					$thisPostID = 486;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
				</div>
				<div class="outcome-icon-div column20perc float-to-left">
					<?php
					$thisPostID = 649;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
				</div>
				<div class="outcome-icon-div column20perc float-to-left">
					<?php
					$thisPostID = 651;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
				</div>
				<div class="outcome-icon-div column20perc float-to-left">
					<?php
					$thisPostID = 667;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
				</div>
            </div><!--End outcome row-->
        </div>
	</div>
    <div class="row"><!--Build Character-->
		<div class="woc-section">
			<h4 class="woc-heading">Build Character</h4>
            <div class="row outcome-row" align="center"><!--New outcome row-->
				<div class="outcome-icon-div column20perc float-to-left">
					<?php
					$thisPostID = 669;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
				</div>
				<div class="outcome-icon-div column20perc float-to-left">
					<?php
					$thisPostID = 671;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
				</div>
				<div class="outcome-icon-div column20perc float-to-left">
					<?php
					$thisPostID = 673;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
				</div>
				<div class="outcome-icon-div column20perc float-to-left">
					<?php
					$thisPostID = 675;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
				</div>
				<div class="outcome-icon-div column20perc float-to-left">
					<?php
					$thisPostID = 677;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
				</div>
            </div><!--End outcome row-->

        </div>
	</div>
    <div class="row"><!--Love People-->
		<div class="woc-section">
			<h4 class="woc-heading">Love People</h4>
            <div class="row outcome-row" align="center"><!--New outcome row-->
				<div class="outcome-icon-div column20perc float-to-left">
					<?php
					$thisPostID = 679;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
				</div>
				<div class="outcome-icon-div column20perc float-to-left">
					<?php
					$thisPostID = 681;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
				</div>
				<div class="outcome-icon-div column20perc float-to-left">
					<?php
					$thisPostID = 683;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
				</div>
				<div class="outcome-icon-div column20perc float-to-left">
					<?php
					$thisPostID = 685;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
				</div>
				<div class="outcome-icon-div column20perc float-to-left">
					<?php
					$thisPostID = 687;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
				</div>
            </div><!--End outcome row-->

        </div>
	</div>
    <div class="row"><!--Be the Body-->
		<div class="woc-section">
			<h4 class="woc-heading">Be the Body</h4>
            <div class="row outcome-row" align="center"><!--New outcome row-->
				<div class="outcome-icon-div column20perc float-to-left">
					<?php
					$thisPostID = 689;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
				</div>
				<div class="outcome-icon-div column20perc float-to-left">
					<?php
					$thisPostID = 691;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
				</div>
				<div class="outcome-icon-div column20perc float-to-left">
					<?php
					$thisPostID = 693;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
				</div>
				<div class="outcome-icon-div column20perc float-to-left">
					<?php
					$thisPostID = 695;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
				</div>
				<div class="outcome-icon-div column20perc float-to-left">
					<?php
					$thisPostID = 697;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
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
					<?php
					$thisPostID = 699;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
				</div>
				<div class="outcome-icon-div column5 float-to-left">
					<?php
					$thisPostID = 701;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
				</div>
            </div><!--End outcome row-->

        </div>
		<div class="column6 woc-section"><!--Build Character-->
			<h4 class="woc-heading">Build Character</h4>
            <div class="row outcome-row" align="center"><!--New outcome row-->
				<div class="outcome-icon-div column5 float-to-left">
					<?php
					$thisPostID = 704;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
				</div>
				<div class="outcome-icon-div column5 float-to-left">
					<?php
					$thisPostID = 706;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
				</div>
            </div><!--End outcome row-->

        </div>
	</div>
    <div class="row"><!--Love People-->
		<div class="column6 woc-section">
			<h4 class="woc-heading">Love People</h4>
            <div class="row outcome-row" align="center"><!--New outcome row-->
				<div class="outcome-icon-div column5 float-to-left">
					<?php
					$thisPostID = 708;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
				</div>
				<div class="outcome-icon-div column5 float-to-left">
					<?php
					$thisPostID = 710;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
				</div>
            </div><!--End outcome row-->

        </div>
		<div class="column6 woc-section"><!--Be the Body-->
			<h4 class="woc-heading">Be the Body</h4>
            <div class="row outcome-row" align="center"><!--New outcome row-->
				<div class="outcome-icon-div column5 float-to-left">
					<?php
					$thisPostID = 712;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
				</div>
				<div class="outcome-icon-div column5 float-to-left">
					<?php
					$thisPostID = 714;
                    getIcon($outcomeURL[$thisPostID], $imageURL[$thisPostID]);
                    if ($userView !== "non_member") { 
						getProgBars($trainingProgressIcon, $coreCheckedPercR[$thisPostID], $heartProgressIcon, $heartCheckScore[$thisPostID]);
					}
					getOutcomeInfo($outcomeURL[$thisPostID], $outcomeName[$thisPostID], $outcomeDefinition[$thisPostID]);
					?>
				</div>
           </div><!--End outcome row-->

        </div>
	</div>
</div><!--deepen section-->

</div><!-- end of #content-full -->


<?php get_footer(); ?>
