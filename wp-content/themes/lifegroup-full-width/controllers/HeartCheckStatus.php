<?php 
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

include_once('Outcome.php');

class HeartCheckStatus {

	//Attributes
	public $statusCheck;
	protected $scoreFieldIDArray = array(
		'484' => '461',
		'485' => '176',
		'486' => '195',
		'649' => '304',
		'651' => '311',
		'667' => '318',
		'669' => '332',
		'671' => '339',
		'673' => '346',
		'675' => '353',
		'677' => '360',
		'679' => '367',
		'681' => '374',
		'683' => '383',
		'685' => '390',
		'687' => '397',
		'689' => '404',
		'691' => '411',
		'693' => '418',
		'695' => '425',
		'697' => '432',
		'699' => '468',
		'701' => '475',
		'704' => '482',
		'706' => '489',
		'708' => '497',
		'710' => '504',
		'712' => '511',
		'714' => '518',
			);
	public $score;
	public $entryDate;
	public $scoreFieldID;
	protected $entryID;
	
	//Methods
	public function __construct($outcomePostID, $userID) {
		global $wpdb;
		$outcome = new Outcome($outcomePostID);
		$user = new SgpUser($userID);
		if (($outcome->statusCheck == "good") && ($user->statusCheck == "good")) {
			$this->statusCheck = "good";
		} else {
			$this->statusCheck = "bad";
			return;
		}
		$this->scoreFieldID = $this->scoreFieldIDArray[$outcome->postID];
		$formID = $wpdb->get_var($wpdb->prepare("SELECT form_id FROM {$wpdb->prefix}frm_fields WHERE id=%d", $this->scoreFieldID));
		$this->entryID = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}frm_items WHERE form_id=%d AND user_id=%d ORDER BY created_at DESC", $formID, $user->userID));
		$this->score = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id=%d AND item_id=%d", $this->scoreFieldID, $this->entryID));
		if ($this->score == "") {
			$this->score = 0;
		}
		$this->entryDate = $wpdb->get_var($wpdb->prepare("SELECT created_at FROM {$wpdb->prefix}frm_items WHERE id=%d", $this->entryID));
	}

	public static function getOutcomeFromForm($formID, $userID) {
		global $wpdb;
		$user = new SgpUser($userID);
		if ($user->statusCheck == "bad") {
			$outcomePostID = "";
		} else {
			$entryID = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}frm_items WHERE form_id=%d AND user_id=%d ORDER BY created_at DESC", $formID, $user->userID));
			$outcomeFieldID = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}frm_fields WHERE form_id=%d AND field_order='0'", $formID));
			$outcomeName = stripslashes($wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id=%d and item_id=%d", $outcomeFieldID, $entryID)));
			$outcomePostID = Outcome::getOutcomeIdByName($outcomeName);
		}
		return $outcomePostID;
	}
}


?>