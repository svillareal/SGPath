// JavaScript Document

jQuery(document).ready(function() {

	//Chart 1
	var data = {
	  // A labels array that can contain any sort of values
	  labels: ['Relate to God', 'Trust God', 'Obey God', 'Worship God', 'Study God', 'Trust Christ',],
	  // Our series array that contains series objects or in this case series data arrays
	  series: [
		[75, 48, 79, 53, 21, 93]
	  ]
	};
	
	var options = {
		low: 0,
		high: 100,
		axisY: { scaleMinSpace: 40 }
	};
	// Create a new line chart object where as first parameter we pass in a selector
	// that is resolving to our chart container element. The Second parameter
	// is the actual data object.
	new Chartist.Bar('.chart1', data, options);
	

	//Chart 2
	var data = {
	  // A labels array that can contain any sort of values
	  labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
	  // Our series array that contains series objects or in this case series data arrays
	  series: [
		[315, 200, 145, 233, 10]
	  ]
	};
	
	// Create a new line chart object where as first parameter we pass in a selector
	// that is resolving to our chart container element. The Second parameter
	// is the actual data object.
	new Chartist.Line('.chart2', data);


	//Chart 3
	var data = {
	  // A labels array that can contain any sort of values
	  labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
	  // Our series array that contains series objects or in this case series data arrays
	  series: [
		[15, 20, 44, 52, 30]
	  ]
	};
	
	// Create a new line chart object where as first parameter we pass in a selector
	// that is resolving to our chart container element. The Second parameter
	// is the actual data object.
	new Chartist.Line('.chart3', data);


	//Chart 4
	var data = {
	  // A labels array that can contain any sort of values
	  labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
	  // Our series array that contains series objects or in this case series data arrays
	  series: [
		[50, 12, 44, 22, 10]
	  ]
	};
	
	// Create a new line chart object where as first parameter we pass in a selector
	// that is resolving to our chart container element. The Second parameter
	// is the actual data object.
	new Chartist.Line('.chart4', data);

	jQuery(".chart1").click(function(){

		//get JSON data for chart
		jQuery.post(my_ajax_obj.ajax_url, {
			//_ajax_nonce: my_ajax_obj.updateToNewCoreVersionNonce,
			action: "updateSampleChart",
			dataType: "json",
		})
		.done(function(chartData) {  
	var random3 = {
	  // A labels array that can contain any sort of values
	  labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
	  // Our series array that contains series objects or in this case series data arrays
	  series: [
		[50, 12, 44, 22, 10]
	  ]
	};
	console.debug(random3);

			var info = JSON.parse(chartData);
			var info = info.data;
			console.debug(info);
			new Chartist.Bar('.chart1', info);
			//alert(data);
			//location.reload();
		})
		.fail(function() {  
			//show section on page
			alert("Sorry, your request cannot be completed because of a server error. Please try again later.");
	  });
	});


});