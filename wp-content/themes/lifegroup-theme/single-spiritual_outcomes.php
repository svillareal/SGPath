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

$outcomeName = get_the_title();
$outcomeDefinition = get_field('outcome_definition');
$bibleStudyCheckValue = FrmProEntriesController::get_field_value_shortcode(array('field_id' => 711, 'user_id' => 'current'));
$readingCheckValue = FrmProEntriesController::get_field_value_shortcode(array('field_id' => 712, 'user_id' => 'current'));
$scriptureMemoryCheckValue = FrmProEntriesController::get_field_value_shortcode(array('field_id' => 713, 'user_id' => 'current'));
$activityCheckValue = FrmProEntriesController::get_field_value_shortcode(array('field_id' => 714, 'user_id' => 'current'));
$groupDiscussionCheckValue = FrmProEntriesController::get_field_value_shortcode(array('field_id' => 715, 'user_id' => 'current'));
$otherCheckValue = FrmProEntriesController::get_field_value_shortcode(array('field_id' => 716, 'user_id' => 'current'));
if ($bibleStudyCheckValue == 1) { $bibleStudyChecked = "checked"; $bsImage = "desaturate"; $bsDiv = "style='color:lightGray'";}
else {$bibleStudyChecked = ""; $bsImage = ""; $bsDiv = "";};
if ($readingCheckValue == 1) { $readingChecked = "checked"; $rImage = "desaturate"; $rDiv = "style='color:lightGray'";}
else {$readingChecked = ""; $rImage = ""; $rDiv = "";};
if ($scriptureMemoryCheckValue == 1) { $scriptureMemoryChecked = "checked"; $smImage = "desaturate"; $smDiv = "style='color:lightGray'";}
else {$scriptureMemoryChecked = ""; $smImage = ""; $smDiv = "";};
if ($activityCheckValue == 1) { $activityChecked = "checked"; $aImage = "desaturate"; $aDiv = "style='color:lightGray'";}
else {$activityChecked = ""; $aImage = ""; $aDiv = "";};
if ($groupDiscussionCheckValue == 1) { $groupDiscussionChecked = "checked"; $gdImage = "desaturate"; $gdDiv = "style='color:lightGray'";}
else {$groupDiscussionChecked = ""; $gdImage = ""; $gdDiv = "";};
  
?>
	<div id="primary" class="content-area">
		<div id="content" class="site-content test-content" role="main">

		  <div class="entry-title"><?php echo $outcomeName; ?></div>
<a href="#" onclick="getOutcome()"> test </a>
<div id="output"><?php echo $bibleStudyCheckValue; echo ", "; echo $readingCheckValue; echo ", "; echo $scriptureMemoryCheckValue; echo ", ";?></div>
		  <div id="divTest"><?php echo FrmProDisplaysController::get_shortcode(array('id' => 350)) ?></div>		  
			  
		  
<div id="outcome-menu">
<div class="row">
<div class="col-lg-3" align="center"><a href="#outcome-introduction"><?php echo FrmProEntriesController::get_field_value_shortcode(array('field_id' => 705));?></a></div>
<div class="col-lg-3" align="center"><a href="#outcome-training"><?php echo FrmProEntriesController::get_field_value_shortcode(array('field_id' => 706));?></a></div>
<div class="col-lg-3" align="center"><a href="#outcome-heart-check"><?php echo FrmProEntriesController::get_field_value_shortcode(array('field_id' => 708));?></a></div>
<div class="col-lg-3" align="center"><a href="#outcome-extras"><?php echo FrmProEntriesController::get_field_value_shortcode(array('field_id' => 709));?></a></div>
</div>
</div><!--outcome-menu-->

<a name="outcome-introduction"></a>
<div id="outcome-introduction" align="center">
<h2 class="outcome-heading"><?php echo FrmProEntriesController::get_field_value_shortcode(array('field_id' => 705));?></h2>
  <p><?php echo $outcomeDefinition; ?></p>
<p><a href="">Read More</a></p>
</div><!--outcome introduction-->


<a name="#outcome-training"></a>
<div id="outcome-training">
<h2 class="outcome-heading"><?php echo FrmProEntriesController::get_field_value_shortcode(array('field_id' => 706));?></h2>
<div class="row" align="center">
<div class="col-lg-3" style="float:none">
<div class="row" align="left">
<div class="col-lg-3"><i class="fa fa-book"></i></div>
<div class="resources-progress-bar col-lg-9">
	<span style="width: <?php echo FrmProEntriesController::get_field_value_shortcode(array('field_id' => 114, 'user_id' => current));?>%"></span>
</div>
</div> <!--resources progress bar row-->
</div></div> <!--resources progress bar row-->


<div class="row">
  <div class="col-lg-2 training-section-show" id="divBibleStudy" <?php echo $bsDiv;?>>
	<img class="training-section-image <?php echo $bsImage;?>" src="http://lg.sherilynvillareal.com/wp-content/uploads/2015/01/Bible.jpg">
	<div class="TrainingCheck">
	  <input type="checkbox" value="YES" <?php echo $bibleStudyChecked; ?> id="BibleCheck" class="trainingCheckbox" name="check"/>
	  <label for="bibleCheck">Complete?</label>
	</div>
	<a href="#" target="_blank"><h4>Bible Study</h4></a>
	<p>Here is where the description of the intro bible study would go.  I probably need to put some kind of character limit on the form here so that the description doesn't get too long.</p>
  </div>
  <div class="col-lg-2 training-section-show" id="divReading" <?php echo $rDiv;?>>
	<img class="training-section-image <?php echo $rImage;?>" src="http://lg.sherilynvillareal.com/wp-content/uploads/2015/01/reading.jpg">
	<div class="TrainingCheck">
	  <input type="checkbox" value="YES" <?php echo $readingChecked; ?> id="ReadingCheck" class="trainingCheckbox" name="check"/>
	  <label for="ReadingCheck">Complete?</label>
	</div>
	<a href="#" target="_blank"><h4>Reading</h4></a>
	<p>Here is where the description of the intro bible study would go.  I probably need to put some kind of character limit on the form here so that the description doesn't get too long.</p>
  </div>
  <div class="col-lg-2 training-section-show" id="divScriptureMemory" <?php echo $smDiv;?>>
	<img class="training-section-image <?php echo $smImage;?>" src="http://lg.sherilynvillareal.com/wp-content/uploads/2015/01/Memory.jpg">
	<div class="TrainingCheck">
	  <input type="checkbox" value="YES" <?php echo $scriptureMemoryChecked; ?> id="ScriptureMemoryCheck" class="trainingCheckbox" name="check"/>
	  <label for="ScriptureMemoryCheck">Complete?</label>
	</div>
	<a href="#" target="_blank"><h4>Scripture Memory</h4></a>
	<p>Here is where the description of the intro bible study would go.  I probably need to put some kind of character limit on the form here so that the description doesn't get too long.</p>
  </div>
  <div class="col-lg-2 training-section-show" id="divActivity" <?php echo $aDiv;?>>
	<img class="training-section-image <?php echo $aImage;?>" src="http://lg.sherilynvillareal.com/wp-content/uploads/2015/01/Activity.jpg">
	<div class="TrainingCheck">
	  <input type="checkbox" value="YES" <?php echo $activityChecked; ?> id="ActivityCheck" class="trainingCheckbox" name="check"/>
	  <label for="ActivityCheck">Complete?</label>
	</div>
	<a href="#" target="_blank"><h4>Activity</h4></a>
	<p>Here is where the description of the intro bible study would go.  I probably need to put some kind of character limit on the form here so that the description doesn't get too long.</p>
  </div>
  <div class="col-lg-2 training-section-show" id="divGroupDiscussion" <?php echo $gdDiv;?>>
	<img class="training-section-image <?php echo $gdImage;?>" src="http://lg.sherilynvillareal.com/wp-content/uploads/2015/01/Group.jpg">
	<div class="TrainingCheck">
	  <input type="checkbox" value="YES" <?php echo $groupDiscussionChecked; ?> id="GroupDiscussionCheck" class="trainingCheckbox" name="check"/>
	  <label for="GroupDiscussionCheck">Complete?</label>
	</div>
	<a href="#" target="_blank"><h4>Group Discussion</h4></a>
	<p>Here is where the description of the intro bible study would go.  I probably need to put some kind of character limit on the form here so that the description doesn't get too long.</p>
  </div>
  
</div><!--row-->
  
</div><!--outcome training-->


<a name="#outcome-heart-check"></a>
<div id="outcome-heart-check">
<h2 class="outcome-heading"><?php echo FrmProEntriesController::get_field_value_shortcode(array('field_id' => 708));?></h2>
<div class="row" align="center">
<div class="col-lg-3" style="float:none">
<div class="row" align="left">
<div class="col-lg-3"><i class="fa fa-heart"></i></div>
<div class="resources-progress-bar red-prog-bar col-lg-9">
	<span style="width: [frm-field-value field_id=195 user_id=current]%"></span>
</div>
</div> <!--heartcheck row-->
</div></div>
<div class="row" align="center">
<div class="col-lg-6" class="heart-check-button" style="float:none; padding-top:10px;">
<a href="http://lg.sherilynvillareal.com/trust-god-heart-check-assessment/"><button class="btn btn-danger btn-lg">Take the Assessment</button></a>
</div><!--heart check assessment button-->
</div>
<a href="http://lg.sherilynvillareal.com/trust-god-heart-check-scores/" title="Trust God Heart Check Scores">Track scores over time</a>
</div><!--outcome heart check-->

<a name="outcome-extras"></a>
<div id="outcome-extras" align="center">
<h2 class="outcome-heading"><?php echo FrmProEntriesController::get_field_value_shortcode(array('field_id' => 709));?></h2>
<p>This section would have a list of all the additional resources that go with this outcome but are not core resources.</p>
</div><!--outcome extras-->



		</div><!-- #content -->
	</div><!-- #primary -->

<?php

get_footer();
