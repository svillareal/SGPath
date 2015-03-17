<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

//Variables
	$pageTitle = get_the_title();
	$outcomeName = $_GET["outcomeName"];

//Check that URL was reached through the correct path
if ($outcomeName == NULL) {
    header("HTTP/1.0 404 Not Found - Archive Empty");
    $wp_query->set_404();
    require TEMPLATEPATH.'/404.php';
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



get_header(); 

//Get required functions
include_once('spg-functions.php');

//Get page content
	$currentSgpUser = new SgpUser(get_current_user_id());
	$outcomePostID = Outcome::getOutcomeIdByName($outcomeName);
	$outcome = new Outcome($outcomePostID);

/**
//Get current outcome ID
$outcomeID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE form_id='59' AND name='$outcomeName'");
?> **/ ?>

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
		'meta_query' => array(
			array(
				'key'     => 'extrasPreviousOutcomes',
				'value'   => $outcomeID,
				'compare' => 'LIKE',
			)
		),
	);
	$query = new WP_Query( $args );
	if ($query->have_posts()) {
		while($query->have_posts()) : $query->the_post();	
			$resource = new Resource(get_the_ID());
			if (in_array($outcome->postID, $resource->assocOutcomeEntryIDs)) {
				$postCheck = 1;
	
/**	//check if currently active for this outcome
	$extraOutcomes = get_field('extrasOutcomeName');
	if (!(strpos($extraOutcomes, $outcomeID) !== false)) {
		$postCheck = 1;
				//Get Extras Resource info
				$extraTitle = get_the_title();
				$extraAuthor = get_field('extrasAuthor');
				$extraResourceType = get_field('extrasType');
				$extraDescription = get_field('extrasDescription');
				$extraHide = get_field('extrasHide');
				$extraListingOrder = get_field('extrasListingOrder');
				$extraLinkURL = get_permalink();
				$extraPostID = get_the_ID();
	
				//Get Cover Image info
				$extraImageID = get_field('extrasImageID');
				if ($extraImageID == NULL) {
					//get image info through other form... field connected to type of resource
					if ($extraResourceType == "Book") {
						$extraImageID = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='797'");
					}
					if ($extraResourceType == "PDF Download") {
						$extraImageID = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='798'");
					}
					if ($extraResourceType == "Video") {
						$extraImageID = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='799'");
					}
					if ($extraResourceType == "Podcast") {
						$extraImageID = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='800'");
					}
					if ($extraResourceType == "Music") {
						$extraImageID = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='801'");
					}
					if ($extraResourceType == "Web resource") {
						$extraImageID = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='802'");
					}
					if ($extraResourceType == "Scripture Memory passages") {
						$extraImageID = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='803'");
					}
					if ($extraResourceType == "Short Activity - add content in description") {
						$extraImageID = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='804'");
					}
				}
				$extraImageURL = wp_get_attachment_url( $extraImageID );
**/	
				//Display the list
				?>
				<div class="column1">
					<button class="restore-from-deleted" id="extraID<?php echo $resource->postID;?>" type="button">Restore</button>
				</div><!--column1-->
				<?php if ($resource->type == "Scripture Memory passages") {?>
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
		endwhile;
	}
	if ($postCheck == 0) {
		echo "<p><em>Sorry, there are no deleted Extras resources associated with the ".$outcome->title." outcome.</em></p>";
	}
?>
    

</div><!-- end of #content-full -->


<?php get_footer(); ?>
