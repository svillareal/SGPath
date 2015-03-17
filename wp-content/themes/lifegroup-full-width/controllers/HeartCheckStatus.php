<?php 
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class HeartCheckStatus {

	//Attributes
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
	protected $scoreFieldID;
	protected $entryID;
	
	//Methods
	public function __construct($outcomePostID, $userID) {
		global $wpdb;
		$this->scoreFieldID = $this->scoreFieldIDArray[$outcomePostID];
		$formID = $wpdb->get_var($wpdb->prepare("SELECT form_id FROM {$wpdb->prefix}frm_fields WHERE id=%d", $this->scoreFieldID));
		$this->entryID = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}frm_items WHERE form_id=%d AND user_id=%d ORDER BY created_at DESC", $formID, $userID));
		$this->score = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id=%d AND item_id=%d", $this->scoreFieldID, $this->entryID));
		if ($this->score == "") {
			$this->score = 0;
		}
	}
	
}


?>