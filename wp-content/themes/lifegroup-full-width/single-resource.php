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
/**Variables for individual resource page**/
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

	global $wpdb;
	$postid = get_the_ID();

	/**Get resource info**/
	$resourceTitle = get_the_title();
	$resourceAuthor = get_field('extrasAuthor');
	$resourceType = get_field('extrasType');
	$resourceDescription = get_field('extrasDescription');
	$resourceDate = get_field('extrasDate');
	$resourcePdfDirect = get_field('extrasPdfDirect');	
	$resourceNumPassages = get_field('extrasNumPassages');
	$resourceRef = array(
    	get_field('extrasRef1'),
    	get_field('extrasRef2'),
		get_field('extrasRef3'),
		get_field('extrasRef4'),
		get_field('extrasRef5'),
		);
	$resourceVar = array(
    	get_field('extrasVar1'),
    	get_field('extrasVar2'),
		get_field('extrasVar3'),
		get_field('extrasVar4'),
		get_field('extrasVar5'),
		);
	$resourcePass = array(
    	get_field('extrasPass1'),
    	get_field('extrasPass2'),
		get_field('extrasPass3'),
		get_field('extrasPass4'),
		get_field('extrasPass5'),
		);
	$needToUpload = get_field('extrasNeedToUpload');
	if (($needToUpload == 0) || !($resourceType == "PDF Download")) {
		$resourceLinkURL = get_field('extrasExternalURL');
	}
	else {
		$resourceLinkID = get_field('extrasUploadID');
		$resourceLinkURL =  wp_get_attachment_url( $resourceLinkID );
	}
	$resourceAudioEmbed = get_field('extrasAudioEmbed');
		//check for .mp3 extension in URL
		if (($resourceAudioEmbed == "1") && (strpos($resourceLinkURL,'.mp3') !== false)) {
			$audioCheck = "good";
		}
		else {
			$audioCheck = "bad";
		}

	/**Convert Video URLs for embedding**/
	$resourceVideoEmbed = get_field('extrasVideoEmbed');
	if (($resourceVideoEmbed == "1") && ($resourceType == "Video") && ($resourceLinkURL !== "")) {
		$videoHost = parse_url($resourceLinkURL, PHP_URL_HOST);
		if ($videoHost == "vimeo.com") {
			$videoNumber = substr($resourceLinkURL,-9);
			$videoEmbedfront = "<iframe width='267' height='150' src='//player.vimeo.com/video/";
			$videoEmbedback = "?portrait=0' frameborder='0' webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>";
			$videoEmbed = $videoEmbedfront.$videoNumber.$videoEmbedback;	
		  }
		  else if ($videoHost == "www.youtube.com") {
			$videoNumber = substr($resourceLinkURL,-11);
			$videoEmbedfront = "<iframe width='267' height='150' src='//www.youtube.com/embed/";
			$videoEmbedback = "' frameborder='0' allowfullscreen></iframe>";
			$videoEmbed = $videoEmbedfront.$videoNumber.$videoEmbedback;
		  }
	}
	
	/**Get Cover Image info**/
	$resourceImageID = get_field('extrasImageID');
	if ($resourceImageID == NULL) {
		$resourceImageID = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='863'");
	}
	$resourceImageURL = wp_get_attachment_url( $resourceImageID );

	/**Get associated outcome URL**/
	$outcomeID = get_field('extrasOutcomeName');
	if (!(is_array($outcomeID))) {$outcomeID = array($outcomeID);}
	foreach ($outcomeID as $key => $value) {
	$relatedOutcome[$key] = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}frm_items WHERE id='$value'");
	}
	wp_reset_postdata();
		$spiritualOutcomes = new WP_Query(array(
			'posts_per_page' => -1,			
			'post_type' => 'spiritual_outcomes'
		));
	while($spiritualOutcomes->have_posts()) : $spiritualOutcomes->the_post();	
		$title = get_the_title();
		foreach ($relatedOutcome as $key => $value) {
			if ($title == $value) {
				$outcomeURL[$key] = get_post_permalink();
			}
		}
	endwhile;

?>

	<div id="primary" class="content-area">
		<div id="content" class="site-content test-content" role="main">


<?php 
//Pagelayout for Scripture Memory
	if ($resourceType == "Scripture Memory passages") { ?>
        <div class="row">
            <div class="column3">
                <img class="resource-image" src="<?php echo $resourceImageURL;?>">
            </div><!--column3-->
            <div class="column9 resource-blurb">
			<?php for ($i = 0; $i < $resourceNumPassages; $i++) { ?>
                    <div class="resource-entry-title"><?php echo $resourceRef[$i]; ?><?php echo $resourceVar[$i]; ?></div>
                    <div class="resource-description"><p><?php echo $resourcePass[$i]; ?></p></div>
			<?php } ?>
                    <div class="resource-outcome-link"><em>passages associated with <a href="<?php echo $outcomeURL[0];?>"><?php echo $relatedOutcome[0];?></a><?php
                        foreach ($relatedOutcome as $key => $value) {
                            if (!($key == 0)) {?>, <a href="<?php echo $outcomeURL[$key];?>"><?php echo $relatedOutcome[$key];?></a><?php }
                        } ?>
                        </em>
                    </div>
             </div><!--column9-->
        </div><!--row-->		
	<?php }

//Pagelayout for Short Activity
	else if ($resourceType == "Short Activity - add content in description") { ?>
        <div class="row">
            <div class="column3">
                <img class="resource-image" src="<?php echo $resourceImageURL;?>">
            </div><!--column3-->
            <div class="column9 resource-blurb">
                <div class="resource-entry-title"><?php echo $resourceTitle ?></div>
                <?php if (!($resourceAuthor == "")) { ?>
                    <div class="resource-author">by <?php echo $resourceAuthor;?></div>
                <?php } ?>
                <div class="resource-description"><p><?php echo $resourceDescription ?></p></div>
                <div class="resource-outcome-link"><em>resource listed under <a href="<?php echo $outcomeURL[0];?>"><?php echo $relatedOutcome[0];?></a><?php 
                	foreach ($relatedOutcome as $key => $value) {
						if (!($key == 0)) {?>, <a href="<?php echo $outcomeURL[$key];?>"><?php echo $relatedOutcome[$key];?></a><?php }
					} ?>
                	</em></div>
            </div><!--column9-->
        </div><!--row-->		
	<?php }

//PageLayout for audio (podcast and music)
	else if (($resourceType == "Podcast") || ($resourceType == "Music")) { ?>
        <div class="row">
            <div class="column3">
                <a href="<?php echo $resourceLinkURL ?>" target="_blank"><img class="resource-image" src="<?php echo $resourceImageURL;?>"></a>
            </div><!--column3-->
            <div class="column9 resource-blurb">
                <div class="resource-entry-title"><a href="<?php echo $resourceLinkURL ?>" target="_blank"><?php echo $resourceTitle ?></a></div>
                <?php if (!($resourceAuthor == "")) { ?>
                    <div class="resource-author">by <?php echo $resourceAuthor;?></div>
                <?php } ?>
                <?php if (!($resourceDate == "")) { ?>
                    <div class="resource-date">by <?php echo $resourceDate;?></div>
                <?php } ?>
				<?php if ($audioCheck == "good") { ?>
                    <div class="resource-audio-embed">
                        <audio controls>
                          <source src="<?php echo $resourceLinkURL; ?>" type="audio/ogg">
                          <source src="<?php echo $resourceLinkURL; ?>" type="audio/mpeg">
                        </audio>
                    </div>
                <?php } ?>
                <div class="resource-description"><p><?php echo $resourceDescription ?></p></div>
                <div class="resource-outcome-link"><em>resource listed under <a href="<?php echo $outcomeURL[0];?>"><?php echo $relatedOutcome[0];?></a><?php 
                	foreach ($relatedOutcome as $key => $value) {
						if (!($key == 0)) {?>, <a href="<?php echo $outcomeURL[$key];?>"><?php echo $relatedOutcome[$key];?></a><?php }
					} ?>
                	</em></div>
            </div><!--column9-->
        </div><!--row-->		
	<?php }

//PageLayout for video
	else if ($resourceType == "Video") { ?>
        <div class="row">
            <div class="column9 resource-blurb">
				<?php if ($resourceVideoEmbed == 1) {?>
					<div class="resource-video"><?php echo $videoEmbed;?></div>
                <?php } ?>
                <div class="resource-entry-title"><a href="<?php echo $resourceLinkURL ?>" target="_blank"><?php echo $resourceTitle ?></a></div>
                <?php if (!($resourceAuthor == "")) { ?>
                    <div class="resource-author">by <?php echo $resourceAuthor;?></div>
                <?php } ?>
                <?php if (!($resourceDate == "")) { ?>
                    <div class="resource-date">by <?php echo $resourceDate;?></div>
                <?php } ?>
                <div class="resource-description"><p><?php echo $resourceDescription ?></p></div>
                <div class="resource-outcome-link"><em>resource listed under <a href="<?php echo $outcomeURL[0];?>"><?php echo $relatedOutcome[0];?></a><?php
                	foreach ($relatedOutcome as $key => $value) {
						if (!($key == 0)) {?>, <a href="<?php echo $outcomeURL[$key];?>"><?php echo $relatedOutcome[$key];?></a><?php }
					} ?>
                	</em>
                </div>
            </div><!--column9-->
        </div><!--row-->		
	<?php }

//PageLayout Default (for all others)
	else { ?>
        <div class="row">
            <div class="column3">
                <a href="<?php echo $resourceLinkURL ?>" target="_blank"><img class="resource-image" src="<?php echo $resourceImageURL;?>"></a>
            </div><!--column3-->
            <div class="column9 resource-blurb">
                <div class="resource-entry-title"><a href="<?php echo $resourceLinkURL ?>" target="_blank"><?php echo $resourceTitle ?></a></div>
                <?php if (!($resourceAuthor == "")) { ?>
                    <div class="resource-author">by <?php echo $resourceAuthor;?></div>
                <?php } ?>
                <?php if (!($resourceDate == "")) { ?>
                    <div class="resource-date">by <?php echo $resourceDate;?></div>
                <?php } ?>
                <div class="resource-description"><p><?php echo $resourceDescription ?></p></div>
                <div class="resource-outcome-link"><em>resource listed under <a href="<?php echo $outcomeURL[0];?>"><?php echo $relatedOutcome[0];?></a><?php
                	foreach ($relatedOutcome as $key => $value) {
						if (!($key == 0)) {?>, <a href="<?php echo $outcomeURL[$key];?>"><?php echo $relatedOutcome[$key];?></a><?php }
					} ?>
                	</em></div>
            </div><!--column9-->
        </div><!--row-->		
	<?php } ?>

<div class="clear"></div>

<?php $EntryID = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}frm_items WHERE post_id='$postid'");
if (($userView == "admin") || ($userView == "pastor")) { ?>
	Edit this entry link:  <?php echo FrmProEntriesController::entry_edit_link(array('id' => $EntryID, 'label' => 'Edit', 'page_id' => 275)); ?>
<?php } ?>
		</div><!-- #content -->
	</div><!-- #primary -->

<?php

get_footer();
