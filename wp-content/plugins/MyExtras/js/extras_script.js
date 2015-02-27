// JavaScript Document



jQuery(document).ready(function() {
  jQuery(".trainingCheckbox").change(function(){
	//define variables
	var resourceID = jQuery(this).attr('value');
	var outcomeTitle = jQuery("div.outcome-entry-title").html();
	var sectionID = jQuery(this).attr('id');
	var divIDforImage = jQuery(this).parent().parent().attr('id');  
	var fieldID = jQuery("#" + divIDforImage + " > div.fieldID").html();
	var checkedPercStyle = jQuery("#coreCheckedPerc").attr('style');
	var checkedPerc = checkedPercStyle.substring(7, -1);
	if (jQuery(this).is(':checked')) {
	  var checkedValue = 1;
	}
	else {
	  var checkedValue = 0;
	}

	//update database form
	jQuery.post(my_ajax_obj.ajax_url, {
		_ajax_nonce: my_ajax_obj.nonce,
	  	action: "updateCoreTraining",
		resourceTag: sectionID,
		outcomeTitle: outcomeTitle,
		checkedValue: checkedValue,
		resourceID: resourceID
	})
	.done(function() {  
		location.reload();
  	})
	.fail(function() {  
		//show section on page
		alert("Sorry, your request cannot be completed because of a server error. Please try again later.");
  });
});

  jQuery(".updatebtn").click(function(){
	//define variables
	var outcomeTitle = jQuery("div.outcome-entry-title").html();
	var sectionID = jQuery(this).attr('id');

	//update database form
	jQuery.post(my_ajax_obj.ajax_url, {
		_ajax_nonce: my_ajax_obj.nonce,
	  	action: "updateToNewCoreTraining",
		resourceTag: sectionID,
		outcomeTitle: outcomeTitle
	})
	.done(function() {  
		location.reload();
  	})
	.fail(function() {  
		//show section on page
		alert("Sorry, your request cannot be completed because of a server error. Please try again later.");
  });
});

  jQuery(".restore-old-version").click(function(){
	//define variables
	var outcomeTitle = jQuery("div.outcome-entry-title").html();
	var sectionID = jQuery(this).attr('id');

	//update database form
	jQuery.post(my_ajax_obj.ajax_url, {
		_ajax_nonce: my_ajax_obj.nonce,
	  	action: "updateToOldCoreTraining",
		resourceTag: sectionID,
		outcomeTitle: outcomeTitle
	})
	.done(function() {  
		location.reload();
  	})
	.fail(function() {  
		//show section on page
		alert("Sorry, your request cannot be completed because of a server error. Please try again later.");
  });
});

  jQuery("#learnMore").click(function(){
	if (jQuery("#learnMore").html() == "Learn More") {
  	//make hidden div appear and change link text
	jQuery("#outcome-introduction-more").slideDown('200');
	jQuery("#learnMore").html("Show Less");
	}
	else {
	//make div disappear and change link text
	jQuery("#outcome-introduction-more").slideUp('200');
	jQuery("#learnMore").html("Learn More");
	//... and easing transition
	}
  });

  jQuery("#heartScoresGraph").click(function(){
	if (jQuery("#heartScoresGraph").html() == "Track scores over time") {
  	//make hidden div appear and change link text
	jQuery("#outcome-heart-graph").slideDown('200');
	jQuery("#heartScoresGraph").html("Hide the graph");
	}
	else {
	//make div disappear and change link text
	jQuery("#outcome-heart-graph").slideUp('200');
	jQuery("#heartScoresGraph").html("Track scores over time");
	//... and easing transition
	}
  });

  jQuery(".sectionHide").click(function(){
	var sectionID = jQuery(this).parent().parent().attr('id');
	
	//update database form
	var outcomeTitle = jQuery("div.outcome-entry-title").html();
	jQuery.post(my_ajax_obj.ajax_url, {
		_ajax_nonce: my_ajax_obj.nonce,
	  	action: "updateOutcomeMain",
		sectionID: sectionID,
		outcomeTitle: outcomeTitle,
		showHideValue: 0
	})
/*	.done(function(data) {  */
	.done(function() {  
		alert("Any resources in 'hidden' Core Training sections will be added to the Extras section for that outcome. To delete them completely for an outcome, you will still need to 'remove' them from the extras section.");
		location.reload();

/*		if (data !== "check") { alert("Sorry, there was an error. Please try again later."); }
		else {
			//hide section on page
			jQuery("#" + sectionID).addClass("hidden");
			if (sectionID == "divBibleStudy") { var sectionTag = "bs";}
			else if (sectionID == "divReading") { var sectionTag = "r";}
			else if (sectionID == "divScriptureMemory") { var sectionTag = "sm";}
			else if (sectionID == "divActivity") { var sectionTag = "a";}
			else if (sectionID == "divGroupDiscussion") { var sectionTag = "gd";}
			else if (sectionID == "divOther") { var sectionTag = "o";}
			jQuery("#" + sectionTag + "AddID").parent().removeClass("hidden");	
		}*/
  	})
	.fail(function() {  
		//show section on page
		alert("Sorry, your request cannot be completed because of a server error. Please try again later.");
  	});
  });
  
  jQuery(".add_section").children().click(function(){
	var sectionID = jQuery(this).attr('id');
	if (sectionID == "bsAddID") { var divID = "divBibleStudy"; }
	else if (sectionID == "rAddID") { var divID = "divReading"; }
	else if (sectionID == "smAddID") { var divID = "divScriptureMemory"; }
	else if (sectionID == "aAddID") { var divID = "divActivity"; }
	else if (sectionID == "gdAddID") { var divID = "divGroupDiscussion"; }
	else if (sectionID == "oAddID") { var divID = "divOther"; }
	
	//update database form
	var outcomeTitle = jQuery("div.outcome-entry-title").html();
	jQuery.post(my_ajax_obj.ajax_url, {
		_ajax_nonce: my_ajax_obj.nonce,
	  	action: "updateOutcomeMain",
		sectionID: divID,
		outcomeTitle: outcomeTitle,
		showHideValue: 1
	})
	.done(function() {  
		location.reload();

/*	.done(function(data) {  
		if (data !== "check") { alert("Sorry, there was an error. Please try again later."); }
		else {
			//show section on page
			jQuery("#" + divID).removeClass("hidden");
			jQuery("#" + sectionID).parent().addClass("hidden");	
		} */
  	})
	.fail(function() {  
		//show section on page
		alert("Sorry, your request cannot be completed because of a server error. Please try again later.");
  	});
  });

  jQuery("#choose-core-link").click(function(){
	if (jQuery(this).html() == "+Choose from a list of existing resources:") {
  	//make hidden div appear and change link text
	jQuery("#choose-core-resource").slideDown('200');
	jQuery("#choose-core-link").html("-Hide this section");
	}
	else {
	//make div disappear and change link text
	jQuery("#choose-core-resource").slideUp('200');
	jQuery("#choose-core-link").html("+Choose from a list of existing resources:");
	//... and easing transition
	}
  });

  jQuery("#add-core-link").click(function(){
	if (jQuery(this).html() == "+Add a new resource:") {
  	//make hidden div appear and change link text
	jQuery("#add-core-resource").slideDown('200');
	jQuery("#add-core-link").html("-Hide this section");
	}
	else {
	//make div disappear and change link text
	jQuery("#add-core-resource").slideUp('200');
	jQuery("#add-core-link").html("+Add a new resource:");
	//... and easing transition
	}
  });

  jQuery(".this-one").click(function(){
	var extraDivID = jQuery(this).attr('id');
    var extraPostID = extraDivID.substring(7);
    var outcomeName = jQuery("#outcomeName").html();
    var resourceCategory = jQuery("#resourceCategory").html();
	
	//update coremeta database form
	jQuery.post(my_ajax_obj.ajax_url, {
		_ajax_nonce: my_ajax_obj.nonce,
	  	action: "updateCoreMeta",
		postID: extraPostID,
		outcomeName: outcomeName,
		resourceCategory: resourceCategory
	})
	.done(function(data) {  
		window.location = data;
  	})
	.fail(function() {  
		//show section on page
		alert("Sorry, your request cannot be completed because of a server error. Please try again later.");
  	});
  });

  jQuery(".remove-from-extras").click(function(){
	var extraDivID = jQuery(this).attr('id');
    var extraPostID = extraDivID.substring(7);
	var outcomeTitle = jQuery("div.outcome-entry-title").html();
	
	//update coremeta database form
	jQuery.post(my_ajax_obj.ajax_url, {
		_ajax_nonce: my_ajax_obj.nonce,
	  	action: "removeExtra",
		postID: extraPostID,
		outcomeName: outcomeTitle
	})
	.done(function() {  
		location.reload();
/*	.done(function(data) {  
		window.location = data; */
  	})
	.fail(function() {  
		//show section on page
		alert("Sorry, your request cannot be completed because of a server error. Please try again later.");
  	});
  });

  jQuery(".remove-from-core").click(function(){
    var outcomeName = jQuery("#outcomeName").html();
    var resourceCategory = jQuery("#resourceCategory").html();
	
	//update coremeta database form
	jQuery.post(my_ajax_obj.ajax_url, {
		_ajax_nonce: my_ajax_obj.nonce,
	  	action: "removeCore",
		outcomeName: outcomeName,
		resourceCat: resourceCategory
	})
	.done(function(data) {  
		alert("Please note that resources removed from a Core Resource category will NOT be deleted, but rather added to the Extras section. To remove it completely, you will still need to remove it from the Extras section for this outcome as well.");
		window.location = data;
  	})
	.fail(function() {  
		//show section on page
		alert("Sorry, your request cannot be completed because of a server error. Please try again later.");
  	});
  });


  jQuery(".restore-from-deleted").click(function(){
	var extraDivID = jQuery(this).attr('id');
    var extraPostID = extraDivID.substring(7);
	var outcomeTitle = jQuery("#outcomeName").html();
	
	//update coremeta database form
	jQuery.post(my_ajax_obj.ajax_url, {
		_ajax_nonce: my_ajax_obj.nonce,
	  	action: "restoreExtra",
		postID: extraPostID,
		outcomeName: outcomeTitle
	})
	.done(function(data) {  
		window.location = data;
  	})
	.fail(function() {  
		//show section on page
		alert("Sorry, your request cannot be completed because of a server error. Please try again later.");
  	});
  });

  jQuery(".bump-extras").click(function(){
	var extraDivID = jQuery(this).attr('id');
    var extraPostID = extraDivID.substring(9);
	var orderDir = extraDivID.substring(0,2);
	var outcomeTitle = jQuery("div.outcome-entry-title").html();

	//get info about neighbor resources
	var topNeighborDivID = jQuery("#" + extraDivID).parent().prevAll(".extras-controls").children(".bump-extras").first().attr('id');
	if (typeof topNeighborDivID === "undefined") {
		var topPostID = 'none';
	} else {
		var topPostID = topNeighborDivID.substring(9);
	}
	var bottomNeighborDivID = jQuery("#" + extraDivID).parent().nextAll(".extras-controls").children(".bump-extras").first().attr('id');	
	if (typeof bottomNeighborDivID === "undefined") {
		var bottomPostID = 'none';
	} else {
		var bottomPostID = bottomNeighborDivID.substring(9);
	}
	
	//update coremeta database form
	jQuery.post(my_ajax_obj.ajax_url, {
		_ajax_nonce: my_ajax_obj.nonce,
	  	action: "bumpExtra",
		postID: extraPostID,
		outcome: outcomeTitle,
		order: orderDir,
		top: topPostID,
		bottom: bottomPostID
	})
	.done(function() {  
		location.reload();
  	})
	.fail(function() {  
		//show section on page
		alert("Sorry, your request cannot be completed because of a server error. Please try again later.");
  	});
  });
  
});