<?php 
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class SgpUser {
	//Attributes
	public $statusCheck;
	public $userID;
	public $userView;

	//Methods
	public function __construct($userID) {
		$this->userID = $userID;
		if (($userID == 0) || ($this->user_exists())) {
			$this->statusCheck = "good";
		} else {
			$this->statusCheck = "bad";
			return;
		}
		if ($this->userID != 0) {
			$userData = get_userdata( $userID );
			if (in_array("administrator", $userData->roles)) {
					$this->userView = "admin";
				} else if (in_array("grow_pastor", $userData->roles)) {
					$this->userView = "pastor";
				} else if (in_array("group_leader", $userData->roles)) {
					$this->userView = "leader";
				} else if (in_array("subscriber", $userData->roles)) {
					$this->userView = "member";
				} else {
					$this->userView = "non_member";
			}
		} else {
			$this->userView = "non_member";
		}
	}

	public function isGroupLeader() {
		if ($this->user_exists($this->userID)) {
			$userData = get_userdata( $this->userID );
			if (in_array("group_leader", $userData->roles)) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	public function user_exists() {		
		global $wpdb;	
		$count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->users WHERE ID=%d", $this->userID));	
		if($count == 1){
			return true;
		} else {
			return false;
		}
	}
}
?>
