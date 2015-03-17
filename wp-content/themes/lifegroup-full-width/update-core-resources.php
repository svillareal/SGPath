<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

//Variables
	$pageTitle = get_the_title();
	$outcomeName = $_GET["outcomeName"];
	$resourceCategory = $_GET["resourceCategory"];

//Check that URL was reached through the correct path
if (($outcomeName == NULL) || ($resourceCategory == NULL)) {
    header("HTTP/1.0 404 Not Found - Archive Empty");
    $wp_query->set_404();
    require TEMPLATEPATH.'/404.php';
    exit;
}

/**
 * Full Content Template
 *
Template Name:  Update Core Resources
 *
 * @file           update-core-resources.php
 * @package        Life Group full width
 * @author         Sherilyn Villareal
 * @version        Release: 1.0
 */



get_header();

//Get required functions
include_once('spg-functions.php');

//Get page content
	$currentSgpUser = new SgpUser(get_current_user_id());
	$outcome = new Outcome(Outcome::getOutcomeIdByName($outcomeName));
	$catIndex = CoreCategories::categoryIndex($resourceCategory);
	if ($outcome->coreID[$catIndex] != NULL) {
		$currentResource = new Resource(getPostID($outcome->coreID[$catIndex]));
	} else {
		$currentResource = "";
	}

?>

<div id="content-full" class="grid col-940">
<div class="hidden" id="outcomeName"><?php echo $outcome->title;?></div>
<div class="hidden" id="resourceCategory"><?php echo CoreCategories::$coreCategories[$catIndex];?></div>

	<h1><?php echo $pageTitle;?></h1>
	<div class="core-instructions">
	<?php if ($currentResource != NULL) { ?>
		<p>Currently, you have the following resource associated with the <?php echo CoreCategories::$coreCategories[$catIndex];?> category for the <?php echo $outcome->title;?> outcome.</p>
		<div class='column1 extras-controls'>
            <button class='remove-from-core' id='coreID<?php echo $currentResource->postID;?>' type='button'>Remove</button>
        </div><!--column1-->
		<?php $currentResource->displayResourceInList();
	} else { ?>
		<p>Currently, you don't have a resource associated with the <?php echo CoreCategories::$coreCategories[$catIndex];?> category for the <?php echo $outcome->title;?> outcome.</p>
	<?php }?>
	<p>Select from the options below to select a new <?php echo CoreCategories::$coreCategories[$catIndex];?> resource for the <?php echo $outcome->title;?> outcome.</p>
    </div>

	<div class="core-option-links">
    	<a id="choose-core-link">+Choose from a list of existing resources:</a>
    </div>

	<div style="display:none;" id="choose-core-resource">
		<?php 
			wp_reset_postdata();
			$args = array(
				'posts_per_page' => -1,
				'post_type'  => 'resource',
				'meta_query' => array(
					array(
						'key'     => 'extrasOutcomeName',
						'value'   => $outcome->entryID,
						'compare' => 'LIKE',
					)
				),
			);
			$query = new WP_Query($args);
			while($query->have_posts()) : $query->the_post();	
				if (!($resource->postID == $currentResource->postID)) {
					$resource = new Resource(get_the_ID()); ?>
					<div class="column1">
						<button class="this-one" id="extraID<?php echo $resource->postID;?>" type="button">This one!</button>
					</div><!--column1-->
					<?php $resource->displayResourceInList();
					} 
			endwhile;
		?>
        <div><p>Don't see your resource here? Add a New Resource, <strong>making sure to associate it with the <?php echo $outcome->title;?> outcome</strong>, and then select it from the list above.</p></div>

<?php /**
		<strong>To choose from resources associated with another outcome, select an outcome here:</strong><br/>
  		<?php 
		wp_reset_postdata();
		$query = new WP_Query(array('post_type'  => 'spiritual_outcomes'));
		while($query->have_posts()) : $query->the_post();
			$otherOutcomePostID = get_the_id();
			$availableOutcomes[$otherOutcomePostID] = get_the_title();
		endwhile; ?>
		 <select>
			<?php foreach ($availableOutcomes as $otherOutcomePostID => $otherOutcomeName) { ?>
	          <option value="<?php echo $otherOutcomePostID;?>"><?php echo $otherOutcomeName;?></option>
			<?php } ?>
        </select> 

        <div style="display:none;" id="choose-core-from-others">
            <?php 
			$otherOutcomeID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE form_id='59' AND post_id='$otherOutcomePostID'");
                wp_reset_postdata();
                $args = array(
                    'posts_per_page' => -1,
                    'post_type'  => 'resource',
                    'meta_query' => array(
                        array(
                            'key'     => 'extrasOutcomeName',
                            'value'   => $otherOutcomeID,
                            'compare' => 'LIKE',
                        )
                    ),
                );
                $query = new WP_Query($args);
                while($query->have_posts()) : $query->the_post();	
                    //Check Extras for 'not-current-core' match
                    $extraResourceType = get_field('extrasType');
                    $extraPostID = get_the_ID();
                        if ($extraPostID == $currentResourcePostID) { $postMatch = "no"; } else { $postMatch = "yes"; }
                    if ($postMatch == "yes") { 
            
                        //Get Extras Resource info
                        $extraPostID = get_the_ID();
                        $extraTitle = get_the_title();
                        $extraAuthor = get_field('extrasAuthor');
                        $extraDescription = get_field('extrasDescription');
                        $extraHide = get_field('extrasHide');
                        $extraListingOrder = get_field('extrasListingOrder');
                        $extraLinkURL = get_permalink();
            
                        //Get Cover Image info
                        $extraImageID = get_field('extrasImageID');
                        if ($extraImageID == NULL) {
                            $extraImageID = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='863'");
                        }
                        $extraImageURL = wp_get_attachment_url( $extraImageID );
            
                        //Display the list
                        ?>
                        <div class="column1">
                            <button class="this-one" id="extraID<?php echo $extraPostID;?>" type="button">This one!</button>
                        </div><!--column1-->
                        <?php if ($extraResourceType == "Scripture Memory passages") {?>
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
                endwhile;
            ?>
            <div><p>Don't see your resource here? Add a New Resource, <strong>making sure to associate it with the <?php echo $outcomeName;?> outcome</strong>, and then select it from the list above.</p></div>
        </div>**/?>


    </div>

    
	<div class="core-option-links">
    	<a id="add-core-link">+Add a new resource:</a>
    </div>

	<div style="display:none;" id="add-core-resource">
		<?php echo FrmFormsController::get_form_shortcode(array('id' => 72, 'title' => false, 'description' => false)); ?>
    </div>


</div><!-- end of #content-full -->


<?php get_footer(); ?>
