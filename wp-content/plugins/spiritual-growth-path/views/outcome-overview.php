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

//Get required functions
include_once(SgpAppHelpers::plugin_path().'/sgp-includes.php');

//User validation
	$currentSgpUser = new SgpUser(get_current_user_id());
	if ( $currentSgpUser->statusCheck == "bad" ) {
		echo "Sorry, you are not a valid user.  Please try logging out and back in again.";
		get_footer();
		exit;
	}

//Get page content
	$outcomeContent = new OutcomePage();
	$generalInfo = array(
		'trainingProgressIcon' => $outcomeContent->trainingProgressIcon,
		'heartProgressIcon' => $outcomeContent->heartProgressIcon,
		'userID' => $currentSgpUser->userID,
		'userView' => $currentSgpUser->userView
	);



//Content-generating functions
function getOutcomeDiv($generalInfo, $postID, $divWidth) {
	$outcome = new Outcome($postID);
	$userID = $generalInfo['userID'];
	$userView = $generalInfo['userView'];
	$coreTraining = new CoreTrainingStatus($postID, $userID);
	$heartCheck = new HeartCheckStatus($postID, $userID);
	$trainingProgressIcon = $generalInfo['trainingProgressIcon'];
	$heartProgressIcon = $generalInfo['heartProgressIcon'];
	echo "<div class='outcome-icon-div float-to-left' style='width:".$divWidth."%'>
		<a href='".$outcome->postPermalink."'><img src='".$outcome->iconSrc."'></a>";
		if ($userView !== "non_member") { 
	echo "<div class='prog-div'><!--Progress bars-->
		<div class='row' align='left'>
			<div class='column2 prog-bar-icon'><i class='fa ".$trainingProgressIcon."'></i></div>
			<div class='resources-progress-bar column7'>
				<span id='coreCheckedPerc' style='width: ".$coreTraining->coreCheckedScore."%'></span>
			</div>
		</div> <!--resources progress bar row-->
		<div class='row' align='left'>
			<div class='column2 prog-bar-icon'><i class='fa ".$heartProgressIcon."'></i></div>
			<div class='resources-progress-bar red-prog-bar column7'>
				<span style='width: ".$heartCheck->score."%'></span>
			</div>
		</div> <!--heartcheck row-->        
		</div><!--end Progress bars-->";
		}
     echo "<a href='".$outcome->postPermalink."'><h5 class='outcome-label-heading'>".$outcome->title."</h5></a>
        <p class='outcome-def'>".$outcome->definition."</p>
	</div>";
}

function getWOCsection($generalInfo, $season, $category="") {
	if ($season == 'Discover') {
		$categoryArray = OutcomePage::groupBySeason('Discover');
	} else {
		$categoryArray = OutcomePage::sortBySeasonAndCategory($season, $category);
	}
	$outcomeRow = array_chunk($categoryArray, 5);
	$numberOfRows = count($outcomeRow);
	$sizeOfLastChunk = count($outcomeRow[($numberOfRows - 1)]);
	for ($i = 0; $i < $numberOfRows; $i++) {
		if ($i == ($numberOfRows - 1)) {
			$divWidth = 100/$sizeOfLastChunk;
			$rowWidth = ($sizeOfLastChunk/5)*100;
			if ($season == 'Deepen') {
				$rowWidth = $rowWidth*2; //since deepen sections are doubled up because only 2 outcomes each
			}
		} else {
			$divWidth = 25;
			$rowWidth = 100;	
		}
			echo "<div class='row outcome-row centered' style='width:".$rowWidth."%;' align='center'>";
		$singleOutcome = $outcomeRow[$i];
		foreach ($singleOutcome as $data) {
			getOutcomeDiv($generalInfo, $data['postID'], $divWidth);
		}
		echo "</div><!--end outcome row-->";
	}
}


//Page Layout ?>
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
		<?php if ($currentSgpUser->userView == "non_member") { ?>
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
<?php if (($currentSgpUser->isGroupLeader()) || ($currentSgpUser->userView == "admin")) { ?>
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

<a name='discover-section'></a>
<div id='discover-section'>
    <h2 class='outcome-heading'>Discover</h2>
    <?php getWOCsection($generalInfo, 'Discover'); ?>
</div><!--end of Discover-->

<a name='develop-section'></a>
<div id='develop-section'>
    <div class='woc-section'>
        <h4>Love God</h4>
        <?php getWOCsection($generalInfo, 'Develop', 'Love God'); ?>
    </div>
    <div class='woc-section'>
        <h4>Build Character</h4>
        <?php getWOCsection($generalInfo, 'Develop', 'Build Character'); ?>
    </div>
    <div class='woc-section'>
        <h4>Love People</h4>
        <?php getWOCsection($generalInfo, 'Develop', 'Love People'); ?>
    </div>
    <div class='woc-section'>
        <h4>Be the Body</h4>
        <?php getWOCsection($generalInfo, 'Develop', 'Be the Body'); ?>
    </div>		
</div><!--end of Develop-->

<a name='deepen-section'></a>
<div id='deepen-section' style='overflow:hidden;'>
    <div class='woc-section float-to-left' style='width:50%'>
        <h4>Love God</h4>
        <?php getWOCsection($generalInfo, 'Deepen', 'Love God'); ?>
    </div>
    <div class='woc-section float-to-left' style='width:50%'>
        <h4>Build Character</h4>
        <?php getWOCsection($generalInfo, 'Deepen', 'Build Character'); ?>
    </div>
    <div class='woc-section float-to-left' style='width:50%'>
        <h4>Love People</h4>
        <?php getWOCsection($generalInfo, 'Deepen', 'Love People'); ?>
    </div>
    <div class='woc-section float-to-left' style='width:50%'>
        <h4>Be the Body</h4>
        <?php getWOCsection($generalInfo, 'Deepen', 'Be the Body'); ?>
    </div>	
</div><!--end of Deepen-->

</div><!-- end of #content-full -->


<?php get_footer(); ?>
