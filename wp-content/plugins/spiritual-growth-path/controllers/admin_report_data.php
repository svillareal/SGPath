<?php

//These AJAX functions handle calculating data for admin reports.

include_once (SgpAppHelpers::plugin_path() . '/models/Outcome.php');
include_once (SgpAppHelpers::plugin_path() . '/models/HeartCheckStatus.php');

	//Sample Chart data
	function updateSampleChart() {
		//check_ajax_referer
		//check_ajax_referer( 'updateSampleChart' );
		//variables
		global $wpdb;
		//get data
		$wocLabels = $wpdb->get_results("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='280'");
		foreach ($wocLabels as $key => $value) {
			$wocLabels[$key] = $value->meta_value;
		}
		wp_reset_postdata();
		$args = array(
			'posts_per_page' => -1,
			'post_type'  => 'spiritual_outcomes'
			);
		$query = new WP_Query( $args );
		$count = $query->post_count;
		if ($query->have_posts()) {
			while($query->have_posts()) : $query->the_post();
				$outcome = new Outcome(get_the_ID());
				$heartCheckScores[$outcome->woc_category][$outcome->postID] = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id=%d", HeartCheckStatus::$scoreFieldIDArray[$outcome->postID]));
			endwhile;
		}
		foreach ($heartCheckScores as $wocKey => $wocArray) {
			foreach ($heartCheckScores[$wocKey] as $outcomePostID => $postArray) {
				$heartCheckScores[$wocKey][$outcomePostID] = $postArray[0]->meta_value;
			}
			$averages[$wocKey] = (int)round((array_sum($heartCheckScores[$wocKey])) / (count($heartCheckScores[$wocKey])));
		}
		$series = array();
		foreach ($wocLabels as $key => $value) {
			$series[$key] = $averages[$value];
		}
		$seriesArrays = array($series);
		//encode data as JSON object
		$post_data = array(
			'labels' => $wocLabels,
			'series' => $seriesArrays,
			);
		$post_data = json_encode(array('data' => $post_data));
		wp_die($post_data);
	}
	add_action('wp_ajax_updateSampleChart','updateSampleChart');

?>

