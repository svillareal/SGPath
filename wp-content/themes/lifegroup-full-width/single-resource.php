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

get_header();
  
	//Get required files
	include_once('spg-functions.php');

	//Get info
	$currentSgpUser = new SgpUser(get_current_user_id());
	$resource = new Resource(get_the_ID());

?>

<div id="primary" class="content-area">
	<div id="content" class="site-content test-content" role="main">


<?php 
//Pagelayout for Scripture Memory
	if ($resource->type == "Scripture Memory passages") { ?>
        <div class="row">
            <div class="column3">
                <img class="resource-image" src="<?php echo $resource->imageURL;?>">
            </div><!--column3-->
            <div class="column9 resource-blurb">
			<?php for ($i = 0; $i < $resource->numberOfPassages; $i++) { ?>
                    <div class="resource-entry-title"><?php echo $resource->scriptureReference[$i]; ?><?php echo $resource->scriptureVersion[$i]; ?></div>
                    <div class="resource-description"><p><?php echo $resource->scripturePassage[$i]; ?></p></div>
			<?php } 
				if ($resource->assocOutcomeEntryIDs !== NULL) {
					$outcome[0] = new Outcome(getPostID($resource->assocOutcomeEntryIDs[0])); ?>
                    <div class="resource-outcome-link"><em>passages associated with <a href="<?php echo $outcome[0]->postPermalink;?>"><?php echo $outcome[0]->title;?></a><?php
                        foreach ($resource->assocOutcomeEntryIDs as $key => $value) {
							$outcome[$key] = new Outcome(getPostID($resource->assocOutcomeEntryIDs[$key]));
                            if (!($key == 0)) {?>, <a href="<?php echo $outcome[$key]->postPermalink;?>"><?php echo $outcome[$key]->title;?></a><?php }
                        } ?>
                        </em>
                    </div>
                    <?php } ?>
             </div><!--column9-->
        </div><!--row-->		
	<?php }

//Pagelayout for Short Activity
	else if ($resource->type == "Short Activity - add content in description") { ?>
        <div class="row">
            <div class="column3">
                <img class="resource-image" src="<?php echo $resource->imageURL;?>">
            </div><!--column3-->
            <div class="column9 resource-blurb">
                <div class="resource-entry-title"><?php echo $resource->title ?></div>
                <?php if (!($resource->author == "")) { ?>
                    <div class="resource-author">by <?php echo $resource->author;?></div>
                <?php } ?>
                <div class="resource-description"><p><?php echo $resource->description ?></p></div>
				<?php if ($resource->assocOutcomeEntryIDs !== NULL) {
					$outcome[0] = new Outcome(getPostID($resource->assocOutcomeEntryIDs[0])); ?>
                    <div class="resource-outcome-link"><em>resource listed under <a href="<?php echo $outcome[0]->postPermalink;?>"><?php echo $outcome[0]->title;?></a><?php
                        foreach ($resource->assocOutcomeEntryIDs as $key => $value) {
							$outcome[$key] = new Outcome(getPostID($resource->assocOutcomeEntryIDs[$key]));
                            if (!($key == 0)) {?>, <a href="<?php echo $outcome[$key]->postPermalink;?>"><?php echo $outcome[$key]->title;?></a><?php }
                        } ?>
                	</em></div>
				<?php } ?>
            </div><!--column9-->
        </div><!--row-->		
	<?php }

//PageLayout for audio (podcast and music)
	else if (($resource->type == "Podcast") || ($resource->type == "Music")) { ?>
        <div class="row">
            <div class="column3">
                <a href="<?php echo $resource->externalURL; ?>" target="_blank"><img class="resource-image" src="<?php echo $resource->imageURL;?>"></a>
            </div><!--column3-->
            <div class="column9 resource-blurb">
                <div class="resource-entry-title"><a href="<?php echo $resource->externalURL; ?>" target="_blank"><?php echo $resource->title ?></a></div>
                <?php if (!($resource->author == "")) { ?>
                    <div class="resource-author">by <?php echo $resource->author;?></div>
                <?php } ?>
                <?php if (!($resource->dates == "")) { ?>
                    <div class="resource-date">by <?php echo $resource->dates;?></div>
                <?php } ?>
				<?php if ($resource->checkAudio() == "good") { ?>
                    <div class="resource-audio-embed">
                        <audio controls>
                          <source src="<?php echo $resource->externalURL; ?>" type="audio/ogg">
                          <source src="<?php echo $resource->externalURL; ?>" type="audio/mpeg">
                        </audio>
                    </div>
                <?php } ?>
                <div class="resource-description"><p><?php echo $resource->description ?></p></div>
				<?php if ($resource->assocOutcomeEntryIDs !== NULL) { ?>
					<?php $outcome[0] = new Outcome(getPostID($resource->assocOutcomeEntryIDs[0])); ?>
                    <div class="resource-outcome-link"><em>resource listed under <a href="<?php echo $outcome[0]->postPermalink;?>"><?php echo $outcome[0]->title;?></a><?php
                        foreach ($resource->assocOutcomeEntryIDs as $key => $value) {
							$outcome[$key] = new Outcome(getPostID($resource->assocOutcomeEntryIDs[$key]));
                            if (!($key == 0)) {?>, <a href="<?php echo $outcome[$key]->postPermalink;?>"><?php echo $outcome[$key]->title;?></a><?php }
                        } ?>
                	</em></div>
				<?php } ?>
            </div><!--column9-->
        </div><!--row-->		
	<?php }

//PageLayout for video
	else if ($resource->type == "Video") { ?>
        <div class="row">
            <div class="column9 resource-blurb">
				<?php if ($resource->embedVideo == 1) { ?>
					<div class="resource-video"><?php echo $resource->getVideoEmbedCode();?></div>
                <?php } ?>
                <div class="resource-entry-title"><a href="<?php echo $resource->externalURL ?>" target="_blank"><?php echo $resource->title ?></a></div>
                <?php if (!($resource->author == "")) { ?>
                    <div class="resource-author">by <?php echo $resource->author;?></div>
                <?php } ?>
                <?php if (!($resource->dates == "")) { ?>
                    <div class="resource-date">by <?php echo $resource->dates;?></div>
                <?php } ?>
                <div class="resource-description"><p><?php echo $resource->description ?></p></div>
				<?php if ($resource->assocOutcomeEntryIDs !== NULL) { ?>
					<?php $outcome[0] = new Outcome(getPostID($resource->assocOutcomeEntryIDs[0])); ?>
                    <div class="resource-outcome-link"><em>resource listed under <a href="<?php echo $outcome[0]->postPermalink;?>"><?php echo $outcome[0]->title;?></a><?php
                        foreach ($resource->assocOutcomeEntryIDs as $key => $value) {
							$outcome[$key] = new Outcome(getPostID($resource->assocOutcomeEntryIDs[$key]));
                            if (!($key == 0)) {?>, <a href="<?php echo $outcome[$key]->postPermalink;?>"><?php echo $outcome[$key]->title;?></a><?php }
                        } ?>
                	</em>
                </div>
                <?php } ?>
            </div><!--column9-->
        </div><!--row-->		
	<?php }

//PageLayout Default (for all others)
	else { ?>
        <div class="row">
            <div class="column3">
                <a href="<?php echo $resource->externalURL; ?>" target="_blank"><img class="resource-image" src="<?php echo $resource->imageURL;?>"></a>
            </div><!--column3-->
            <div class="column9 resource-blurb">
                <div class="resource-entry-title"><a href="<?php echo $resource->externalURL; ?>" target="_blank"><?php echo $resource->title ?></a></div>
                <?php if (!($resource->author == "")) { ?>
                    <div class="resource-author">by <?php echo $resource->author;?></div>
                <?php } ?>
                <?php if (!($resource->dates == "")) { ?>
                    <div class="resource-date">by <?php echo $resource->dates;?></div>
                <?php } ?>
                <div class="resource-description"><p><?php echo $resource->description ?></p></div>
				<?php if ($resource->assocOutcomeEntryIDs !== NULL) { ?>
					<?php $outcome[0] = new Outcome(getPostID($resource->assocOutcomeEntryIDs[0])); ?>
                    <div class="resource-outcome-link"><em>resource listed under <a href="<?php echo $outcome[0]->postPermalink;?>"><?php echo $outcome[0]->title;?></a><?php
                        foreach ($resource->assocOutcomeEntryIDs as $key => $value) {
							$outcome[$key] = new Outcome(getPostID($resource->assocOutcomeEntryIDs[$key]));
                            if (!($key == 0)) {?>, <a href="<?php echo $outcome[$key]->postPermalink;?>"><?php echo $outcome[$key]->title;?></a><?php }
                        } ?>
                	</em></div>
                <?php } ?>
            </div><!--column9-->
        </div><!--row-->		
	<?php } ?>

<div class="clear"></div>

<?php $entryID = getEntryID(get_the_ID());
if (($currentSgpUser->userView == "admin") || ($currentSgpUser->userView == "pastor")) { ?>
	Edit this entry link:  <?php echo FrmProEntriesController::entry_edit_link(array('id' => $entryID, 'label' => 'Edit', 'page_id' => 275)); ?>
<?php } ?>
		</div><!-- #content -->
	</div><!-- #primary -->

<?php

get_footer();
