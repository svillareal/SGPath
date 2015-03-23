// JavaScript Document

jQuery(document).ready(function() {


//used on Outcome page

		//triggered when a user checks or unchecks a Core Training resource from an Outcome page
		//makes AJAX request to update the database
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
				_ajax_nonce: my_ajax_obj.updateCoreCompletionStatusNonce,
				action: "updateCoreCompletionStatus",
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
		
		
		//triggered when a user (who has completed an older version of a Core Training resource)
		//clicks the "update" button for this resource; makes AJAX request to update database
		  jQuery(".updatebtn").click(function(){
			//define variables
			var outcomeTitle = jQuery("div.outcome-entry-title").html();
			var sectionID = jQuery(this).attr('id');
		
			//update database form
			jQuery.post(my_ajax_obj.ajax_url, {
				_ajax_nonce: my_ajax_obj.updateToNewCoreVersionNonce,
				action: "updateToNewCoreVersion",
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
		
		
		//triggered when a user (who has completed an older version of a Core Training resource but who has chosen to view the updated version)
		//clicks the "restore older version" option and thus chooses to view the older resource again instead;
		//makes AJAX request to update database
		  jQuery(".restore-old-version").click(function(){
			//define variables
			var outcomeTitle = jQuery("div.outcome-entry-title").html();
			var sectionID = jQuery(this).attr('id');
		
			//update database form
			jQuery.post(my_ajax_obj.ajax_url, {
				_ajax_nonce: my_ajax_obj.updateToOldCoreVersionNonce,
				action: "updateToOldCoreVersion",
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
		
		
		//triggered when a user clicks 'Learn More' in Intro section of an outcome page
		//reveals hidden div with more outcome info
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
		
		
		//triggered when a user clicks the "track scores over time option in the Heart Check section of an Outcome page
		//revelas hidden div containing graph of scores over time
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
		
		
		//triggered when an admin chooses to 'hide' a resource category div in the Core Training section of an Outcome page
		  jQuery(".sectionHide").click(function(){
			var sectionID = jQuery(this).parent().parent().attr('id');
			var outcomeTitle = jQuery("div.outcome-entry-title").html();
			//update database form
			jQuery.post(my_ajax_obj.ajax_url, {
				_ajax_nonce: my_ajax_obj.hideResourceCategoryNonce,
				action: "hideResourceCategory",
				sectionID: sectionID,
				outcomeTitle: outcomeTitle,
				showHideValue: 0
			})
			.done(function() {  
				alert("Any resources in 'hidden' Core Training sections will be added to the Extras section for that outcome. To delete them completely for an outcome, you will still need to 'remove' them from the extras section.");
				location.reload();
			})
			.fail(function() {  
				//show section on page
				alert("Sorry, your request cannot be completed because of a server error. Please try again later.");
			});
		  });
		  
		  
		//triggered when an admin chooses to 'add' a (currently hidden) resource category div in the Core Training section
		//of an Outcome page
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
				_ajax_nonce: my_ajax_obj.hideResourceCategoryNonce,
				action: "hideResourceCategory",
				sectionID: divID,
				outcomeTitle: outcomeTitle,
				showHideValue: 1
			})
			.done(function() {  
				location.reload();
			})
			.fail(function() {  
				//show section on page
				alert("Sorry, your request cannot be completed because of a server error. Please try again later.");
			});
		  });
	
		
		//triggered when an admin clicks the 'remove' button next to a resource in the Extras section of an Outcome page
		//updates the database by disassicating the selected resource from this Outcome and adds it the Deleted Resources for this Outcome
		  jQuery(".remove-from-extras").click(function(){
			var extraDivID = jQuery(this).attr('id');
			var extraPostID = extraDivID.substring(7);
			var outcomeTitle = jQuery("div.outcome-entry-title").html();
			
			//update coremeta database form
			jQuery.post(my_ajax_obj.ajax_url, {
				_ajax_nonce: my_ajax_obj.removeExtraNonce,
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

		
		//triggered when an admin clicks an up or down arrow next to a resource in the Extras section of an Outcome page
		//updates the database to reflect the new listing order preference for the associated resources
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
				_ajax_nonce: my_ajax_obj.bumpExtraNonce,
				action: "bumpExtra",
				postID: extraPostID,
				outcome: outcomeTitle,
				order: orderDir,
				top: topPostID,
				bottom: bottomPostID
			})
			.done(function(data) {  
				location.reload();
			})
			.fail(function() {  
				//show section on page
				alert("Sorry, your request cannot be completed because of a server error. Please try again later.");
			});
		  });
		

//used on the Update Core Resources page		
		//reveals the 'choose from existing resources' section for an admin in the Update Core Resources page
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
		
		
		//reveals the 'add a new resource' section for an admin in the Update Core Resources page
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
		
		
		//triggered when an admin clicks a 'this one' button beside a resource on the Update Core Resources page;
		//updates the database by assigning the selected resource as the new core resource for the associated resource category
		//of the associated outcome
		  jQuery(".this-one").click(function(){
			var extraDivID = jQuery(this).attr('id');
			var extraPostID = extraDivID.substring(7);
			var outcomeName = jQuery("#outcomeName").html();
			var resourceCategory = jQuery("#resourceCategory").html();
			
			//update coremeta database form
			jQuery.post(my_ajax_obj.ajax_url, {
				_ajax_nonce: my_ajax_obj.changeCoreResourceNonce,
				action: "changeCoreResource",
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
		
				
		//triggered when an admin clicks the 'remove' button next to the current resource
		//for a resource category on the Update Core Resources page
		  jQuery(".remove-from-core").click(function(){
			var outcomeName = jQuery("#outcomeName").html();
			var resourceCategory = jQuery("#resourceCategory").html();
			
			//update coremeta database form
			jQuery.post(my_ajax_obj.ajax_url, {
				_ajax_nonce: my_ajax_obj.removeCoreResourceNonce,
				action: "removeCoreResource",
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
		

//used on the View Deleted Resources section		
		//triggered when an admin clicks the 'this one' button next to a resource on the View Deleted Resources page for a given Outcome
		//updates the database to re-associate this resource with the Outcome and so re-adding the resource to the Extras section of that Outcome
		  jQuery(".restore-from-deleted").click(function(){
			var extraDivID = jQuery(this).attr('id');
			var extraPostID = extraDivID.substring(7);
			var outcomeTitle = jQuery("#outcomeName").html();
			
			//update coremeta database form
			jQuery.post(my_ajax_obj.ajax_url, {
				_ajax_nonce: my_ajax_obj.restoreExtraNonce,
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

});



