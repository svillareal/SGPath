<?php 
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

include_once ('CoreCategories.php');

class Outcome {
	//Attributes
	public static $coreHideFieldID = array("823", "824", "825", "826", "827", "828");
	public $title;
	public $definition;
	public $description;
	public $evidence;
	public $heartCheckURL;
	public $postID;
	public $entryID;
	public $postPermalink;
	public $coreHide;
	public $coreHideClass;
	public $coreAddClass;
	public $coreID;
	public $iconSrc;
	
	//Methods
	public function __construct($postID) {
		//general outcome info
		$this->postID = $postID;
		$this->postPermalink = get_post_permalink($this->postID);
		$this->title = get_the_title($this->postID);
		$this->definition = get_field('outcome_definition', $this->postID);
		$this->description = get_field('outcome_descriptionFrm', $this->postID);
		$evidenceFieldName = array("evidence1", "evidence2", "evidence3", "evidence4");
		for ($i = 0; $i <= 3; $i++) {
			$this->evidence[$i] = get_field($evidenceFieldName[$i], $this->postID);
		}
		$this->heartCheckURL = get_field('heartCheckLinkID', $this->postID);
		$imageID = get_field('outcome_icon', $this->postID);
		$this->iconSrc = wp_get_attachment_url( $imageID );

		//get Core Category info
		$coreCategories = new CoreCategories();
		$numberOfCore = CoreCategories::numCoreCategories();

		//Core Training info
		global $wpdb;
		$this->entryID = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}frm_items WHERE post_id=%d", $this->postID));
		$visibilityEntryID = $wpdb->get_var($wpdb->prepare("SELECT item_id FROM {$wpdb->prefix}frm_item_metas WHERE meta_value=%d AND field_id='822'", $this->entryID));
		for ($i = 0; $i <= ($numberOfCore-1); $i++) {
			$coreHideField = self::$coreHideFieldID[$i];
			$this->coreHide[$i] = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id=%d AND field_id=%d", $visibilityEntryID, $coreHideField));
			$coreCat = CoreCategories::$coreCategories[$i];
			$this->coreID[$i] = $wpdb->get_var($wpdb->prepare("SELECT resourceEntryID FROM {$wpdb->prefix}coremeta WHERE outcomeID=%d AND coreCategory=%s ORDER BY created_at DESC", $this->entryID, $coreCat));
		}
	}

	public function getCoreVisibility($userView) {
		//get Core Category info
		$coreCategories = new CoreCategories();
		$numberOfCore = CoreCategories::numCoreCategories();

		//get visibility status
		for ($i = 0; $i <= ($numberOfCore-1); $i++) {
			if (($this->coreHide[$i] == NULL) || ($this->coreHide[$i] == "0")) {
				$coreHideClass[$i] = "hidden"; $coreAddClass[$i] = ""; }
				else { $coreHideClass[$i] = ""; $coreAddClass[$i] = "hidden";}
			if ((($this->coreID[$i] == NULL) || ($this->coreID[$i] == -1)) && (!(($userView == "admin") || ($userView == "pastor")))) {
				$coreHideClass[$i] = "hidden";
			}
		}
		$results = array($coreHideClass, $coreAddClass);
		return $results;
	}	

	public function getExtras() {
		global $wpdb;
		$listingArray = array();		
		wp_reset_postdata();
		$args = array(
			'posts_per_page' => -1,
			'post_type'  => 'resource',
			//add order by parameter for listing order
			'meta_query' => array(
				array(
					'key'     => 'extrasOutcomeName',
					'value'   => $this->entryID,
					'compare' => 'LIKE',
				)
			)
		);
		$query = new WP_Query( $args );
		$count = $query->post_count;
		$countNum = 0;
		if ($query->have_posts()) {
			while($query->have_posts()) : $query->the_post();
			$countNum++;			
			//Get resource IDs and listing order
			$extraEntryID = getEntryID(get_the_ID());
			$listingOrder = $wpdb->get_var($wpdb->prepare("SELECT listingOrder FROM {$wpdb->prefix}extrasmeta WHERE resourceID=%d AND outcomeID=%d", $extraEntryID, $this->entryID));
				if ($listingOrder !== NULL) {
					$listingArray[$extraEntryID] = ($listingOrder+1000);
				} else {
					$listingArray[$extraEntryID] = (int)((1-($countNum/$count))*100);
				}			
			endwhile;
		}
		//Sort according to listing order and flip array

		arsort($listingArray, SORT_NUMERIC);
		if ($listingArray !== NULL) {
			$extrasArray = array_flip($listingArray);
		}
		else {
			$extrasArray = NULL;
		}
		$extrasArray = array_values($extrasArray);
		//Sends back a list of Extras for the entered Outcome, ordered by listing order
		return $extrasArray;		
	}
	
	public static function getOutcomeIdByName($outcomeName) {
		wp_reset_postdata();
			$spiritualOutcomes = new WP_Query(array(
				'posts_per_page' => -1,			
				'post_type' => 'spiritual_outcomes'
			));
		while($spiritualOutcomes->have_posts()) : $spiritualOutcomes->the_post();	
			$title = get_the_title();
			if ($title == $outcomeName) {
				$postID = get_the_ID();
				break;
			}
		endwhile;
		return $postID;
	}

}

?>
