<?php


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

//Get posted data
	global $wpdb;
	$formID = $_GET["form"];
	$formID = (int)$formID;

//Get form data
	$entryID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE form_id='$formID' AND user_id='$UserID' ORDER BY created_at DESC");
	$outcomeFieldID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_fields WHERE form_id='$formID' AND field_order='0'");
	$scoreFieldID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_fields WHERE form_id='$formID' ORDER BY field_order DESC");
	$outcomeName = stripslashes($wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='$outcomeFieldID' and item_id='$entryID'"));
	$currentScore = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='$scoreFieldID' and item_id='$entryID'");
	$entryDate = $wpdb->get_var("SELECT created_at FROM {$wpdb->prefix}frm_items WHERE id='$entryID'");
//Get associated outcome URL
	wp_reset_postdata();
		$spiritualOutcomes = new WP_Query(array(
			'posts_per_page' => -1,			
			'post_type' => 'spiritual_outcomes'
		));
	while($spiritualOutcomes->have_posts()) : $spiritualOutcomes->the_post();	
		$title = get_the_title();
		if ($title == $outcomeName) {
			$outcomeURL = get_post_permalink();
		}
	endwhile;

?>
<div id="content-full" class="grid col-940 heart-check-analysis">
<div class="outcome-entry-title">Assessment Results</div>
	
	<p>Thanks for taking the <?php echo $outcomeName;?> heart check assessment. This is some kind of intro paragraph that we could use to give users a quick overview of how to use this results report. For those who are not logged in, we can encourage them to sign-up for an account to save their scores online. We can recommend for them to print the scores out to take a copy to their Life Groups, etc.</p>
	<div class="row">
			<?php if ($userView == "non_member") { ?>
        	<div class="column3 user-button">
            <button class="btn btn-primary">Sign-Up for an Account<br/>to save your results</button>
            </div>
            <?php  } ?>
            <div class="column4 user-button">
			<button class="btn btn-primary">Get a printable version<br/>of your results</button>
            </div>
	</div>
<div id="heart-check-results">
    <h3 class="outcome-heading">Results for <?php echo $outcomeName;?></h3>
	<div class="row centered graph-results">
        <div class="column6 current-score"><?php echo do_shortcode("[frm-graph id='$scoreFieldID' title='Your Score' type='bar' data_type='average' x_axis='created_at' x_start='$entryDate' x_end='$entryDate' min='0' max='100' user_id='$UserID' grid_color='green']")?></div>
        <?php if ($userView !== "non_member") { ?>
        <div class="column6 over-time-scores"><?php echo do_shortcode("[frm-graph id='$scoreFieldID' title='Scores Over Time' type='line' data_type='average' x_axis='created_at' user_id='$UserID' min='0' max='100' grid_color='green']")?></div>
		<?php } ?>
    </div>
	<h4>Understanding Your Score:</h4>
	<p>In this section, we could have a paragrap or two that verbally explains to a user how to interpret their scores above.  These paragraphs would be populated with different content for different score ranges.  So, if a person scored below 50%, we could recommend one thing, and it a person scored between 50-75%, we could recommend another, etc.  There really isn't a limit to how fine-grained we could be on this score interpretation.  It's just a matter of how much we want to write, and how much we think will be helpful to the end user.</p>
	<p>If a user is logged-in and has previous assessment attempts for this outcome, then we could also give them some verbal guidance about how their doing over time.  We could see notice if they're average is increasing or decreasing, for example, and make recommendations accordingly.</p>
	<h4>Recommended Next Steps:</h4>
	<p>Based on their score results and history, we could intelligently make recommendations for this user for this outcome. Again, there are a lot of possibilities here, it just depends on our goals.</p>
</div>

	<div class="row">
			<?php if ($userView == "non_member") { ?>
        	<div class="column3 user-button">
            <button class="btn btn-primary">Sign-Up for an Account<br/>to save your results</button>
            </div>
            <?php  } ?>
            <div class="column4 user-button">
			<button class="btn btn-primary">Get a printable version<br/>of your results</button>
            </div>
	</div>

<p>Back to <a href="<?php echo $outcomeURL;?>"><?php echo $outcomeName;?></a>, Back to <a href="http://localhost/lg">Spiritual Growth Path</a>

</div>


</div><!-- end of #content-full -->


<?php get_footer(); ?>
