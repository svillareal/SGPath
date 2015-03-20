<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

//Get required functions
include_once(SgpAppHelpers::plugin_path().'/sgp-includes.php');

//User validation
	$currentSgpUser = new SgpUser(get_current_user_id());
	if ( $currentSgpUser->statusCheck == "bad" ) {
		get_header();
		echo "Sorry, you are not a valid user.  Please try logging out and back in again.";
		get_footer();
		exit;
	}

//Validation
	$formID = $_GET["form"];
	$formID = (int)$formID;
	$outcomePostID = HeartCheckStatus::getOutcomeFromForm($formID, $currentSgpUser->userID);
	$outcome = new Outcome($outcomePostID);
	if ($outcome->statusCheck == "bad") {
		header("HTTP/1.0 404 Not Found - Archive Empty");
		$wp_query->set_404();
		require TEMPLATEPATH.'/404.php';
		get_footer();
		exit;
	}
	$heartCheck = new HeartCheckStatus($outcome->postID, $currentSgpUser->userID);
	if ($heartCheck->statusCheck == "bad") {
		header("HTTP/1.0 404 Not Found - Archive Empty");
		$wp_query->set_404();
		require TEMPLATEPATH.'/404.php';
		get_footer();
		exit;
	}


/**
 * Full Content Template
 *
Template Name:  Heart Check Analysis page
 *
 * @file           heart-check-analysis.php
 * @package        Life Group full width
 * @author         Sherilyn Villareal
 * @version        Release: 1.0
 */

get_header(); 

//Get page content
	$scoreFieldID = $heartCheck->scoreFieldID;
	$entryDate = $heartCheck->entryDate;
	$userID = $currentSgpUser->userID;


?>
<div id="content-full" class="grid col-940 heart-check-analysis">
<div class="outcome-entry-title">Assessment Results</div>
	
	<p>Thanks for taking the <?php echo $outcome->title;?> heart check assessment. This is some kind of intro paragraph that we could use to give users a quick overview of how to use this results report. For those who are not logged in, we can encourage them to sign-up for an account to save their scores online. We can recommend for them to print the scores out to take a copy to their Life Groups, etc.</p>
	<div class="row">
			<?php if ($currentSgpUser->userView == "non_member") { ?>
        	<div class="column3 user-button">
            <button class="btn btn-primary">Sign-Up for an Account<br/>to save your results</button>
            </div>
            <?php  } ?>
            <div class="column4 user-button">
			<button class="btn btn-primary">Get a printable version<br/>of your results</button>
            </div>
	</div>
<div id="heart-check-results">
    <h3 class="outcome-heading">Results for <?php echo $outcome->title;?></h3>
	<div class="row centered graph-results">
<?php //Need to edit that first graph below so that it shows the latest score only instead of the average for that particular day.?>
        <div class="column6 current-score"><?php echo do_shortcode("[frm-graph id='$scoreFieldID' title='Your Score' type='bar' data_type='average' x_axis='created_at' x_start='$entryDate' x_end='$entryDate' min='0' max='100' user_id='$userID' grid_color='green']")?></div>
        <?php if ($currentSgpUser->userView !== "non_member") { ?>
        <div class="column6 over-time-scores"><?php echo do_shortcode("[frm-graph id='$scoreFieldID' title='Scores Over Time' type='line' data_type='average' x_axis='created_at' user_id='$userID' min='0' max='100' grid_color='green']")?></div>
		<?php } ?>
    </div>
	<h4>Understanding Your Score:</h4>
	<p>In this section, we could have a paragrap or two that verbally explains to a user how to interpret their scores above.  These paragraphs would be populated with different content for different score ranges.  So, if a person scored below 50%, we could recommend one thing, and it a person scored between 50-75%, we could recommend another, etc.  There really isn't a limit to how fine-grained we could be on this score interpretation.  It's just a matter of how much we want to write, and how much we think will be helpful to the end user.</p>
	<p>If a user is logged-in and has previous assessment attempts for this outcome, then we could also give them some verbal guidance about how their doing over time.  We could see notice if they're average is increasing or decreasing, for example, and make recommendations accordingly.</p>
	<h4>Recommended Next Steps:</h4>
	<p>Based on their score results and history, we could intelligently make recommendations for this user for this outcome. Again, there are a lot of possibilities here, it just depends on our goals.</p>
</div>

	<div class="row">
			<?php if ($currentSgpUser->userView == "non_member") { ?>
        	<div class="column3 user-button">
            <button class="btn btn-primary">Sign-Up for an Account<br/>to save your results</button>
            </div>
            <?php  } ?>
            <div class="column4 user-button">
			<button class="btn btn-primary">Get a printable version<br/>of your results</button>
            </div>
	</div>

<p>Back to <a href="<?php echo $outcome->postPermalink;?>"><?php echo $outcome->title;?></a>, Back to <a href="http://localhost/lg">Spiritual Growth Path</a>

</div>


</div><!-- end of #content-full -->


<?php get_footer(); ?>
