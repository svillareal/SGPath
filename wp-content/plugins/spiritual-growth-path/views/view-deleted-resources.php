<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

//Get required functions
include_once(SgpAppHelpers::plugin_path().'/sgp-includes.php');

//Variables
	$outcomeName = $_GET["outcomeName"];
	$outcome = new Outcome(Outcome::getOutcomeIdByName($outcomeName));
	if ($outcome->statusCheck == "bad") {
		header("HTTP/1.0 404 Not Found - Archive Empty");
		$wp_query->set_404();
		require TEMPLATEPATH.'/404.php';
		exit;
	} 

get_header(); 

//Validation
	$currentSgpUser = new SgpUser(get_current_user_id());
	if ( ($currentSgpUser->statusCheck == "bad") || (!($currentSgpUser->userView == "admin") && !($currentSgpUser->userView == "pastor"))) {
		echo "Sorry, you do not have permission to view this page.";
		get_footer();
		exit;
	}
	

/**
 * Full Content Template
 *
Template Name:  View Deleted Resources
 *
 * @file           view-deleted-resources.php
 * @author         Sherilyn Villareal
 */




//Get page content
//	$currentSgpUser = new SgpUser(get_current_user_id());
	$pageTitle = get_the_title();
//	$outcomePostID = Outcome::getOutcomeIdByName($outcomeName);
//	$outcome = new Outcome($outcomePostID);

?>

<div id="content-full" class="grid col-940">
<div class="hidden" id="outcomeName"><?php echo $outcome->title;?></div>
<h1><?php echo $pageTitle;?></h1>
<p>The following resources have been deleted from the <?php echo $outcome->title;?> outcome:</p>
<?php	
	$postCheck = 0;
	wp_reset_postdata();
	$args = array(
		'posts_per_page' => -1,
		'post_type'  => 'resource',
		'orderby' => 'title',
		'order' => 'ASC',
	);
	$query = new WP_Query( $args );
	if ($query->have_posts()) {
		while($query->have_posts()) : $query->the_post();	
			$resource = new Resource(get_the_ID());
			if (!(in_array($outcome->entryID, $resource->assocOutcomeEntryIDs)) && (in_array($outcome->entryID, $resource->deletedOutcomeEntryIDs))) {
				$postCheck = 1;
	
				//Display the list
				?>
				<div class="column1">
					<button class="restore-from-deleted" id="extraID<?php echo $resource->postID;?>" type="button">Restore</button>
				</div><!--column1-->
				<?php $resource->displayResourceInList();
		}
		endwhile;
	} 
	if ($postCheck == 0) {
		echo "<p><em>Sorry, there are no deleted Extras resources associated with the ".$outcome->title." outcome.</em></p>";
	}
?>
    

</div><!-- end of #content-full -->


<?php get_footer(); ?>
