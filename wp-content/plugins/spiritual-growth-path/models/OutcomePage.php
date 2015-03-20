<?php 
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class OutcomePage {
	//Attributes
	public static $outcomeIDs;
	public $introTitle;
	public $descriptionTitle;
	public $evidenceTitle;
	public $trainingTitle;
	public $heartTitle;
	public $extrasTitle;
	public $trainingInstructions;
	public $heartInstructions;
	public $extrasInstructions;
	public $trainingProgressIcon;
	public $heartProgressIcon;
	public $coreImgURL;
	
	//Methods
	public function __construct() {
		global $wpdb;
		$this->introTitle = stripslashes_deep($wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='705'"));
		$this->descriptionTitle = stripslashes_deep($wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='838'"));
		$this->evidenceTitle = stripslashes_deep($wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='839'"));
		$this->trainingTitle = stripslashes_deep($wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='706'"));
		$this->heartTitle = stripslashes_deep($wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='708'"));	
		$this->extrasTitle = stripslashes_deep($wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='709'"));
		$this->trainingInstructions = stripslashes_deep($wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='860'"));
		$this->heartInstructions = stripslashes_deep($wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='861'"));
		$this->extrasInstructions = stripslashes_deep($wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='842'"));
		$this->trainingProgressIcon = stripslashes_deep($wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='843'"));
		$this->heartProgressIcon = stripslashes_deep($wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='844'"));
	
		//Get outcome post IDs and category info
		wp_reset_postdata();
		$args = array(
			'posts_per_page' => -1,
			'post_type'  => 'spiritual_outcomes',
		);
		$query = new WP_Query($args);
		while($query->have_posts()) : $query->the_post();
			$postID = get_the_ID();
			$seasonID = get_field('season');
			$season = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id=%d AND field_id='283'", $seasonID));
			$categoryID = get_field('woc_category');
			$category = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id=%d AND field_id='280'", $categoryID));
			$order = get_field('order');
			self::$outcomeIDs[] = array(
				'season' => $season,
				'category' => $category,
				'order' => $order,
				'postID' => get_the_ID(),
				'title' => get_the_title()
			);
		endwhile;
	}

	public static function groupBySeason($season) {
		foreach (self::$outcomeIDs as $subarray) {
			if ($subarray['season'] == $season) {
				$sortedArray[] = $subarray;
			}
		}
		return $sortedArray;
	}

	public static function groupByCategory($category) {
		foreach (self::$outcomeIDs as $subarray) {
			if ($subarray['category'] == $category) {
				$sortedArray[] = $subarray;
			}
		}
		return $sortedArray;	
	}

	public static function sortBySeasonAndCategory($season, $category) {
		foreach (self::$outcomeIDs as $subarray) {
			if (($subarray['season'] == $season) && ($subarray['category'] == $category)) {
				$sortedArray[] = $subarray;
			}
		}		
		usort($sortedArray, function($a, $b) {
    		return $a['order'] - $b['order'];
		});
		return $sortedArray;
	}

}

?>
