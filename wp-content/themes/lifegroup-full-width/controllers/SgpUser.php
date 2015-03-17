<?php 
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class SgpUser {
	//Attributes
	public $userID;
	public $userView;

	//Methods
	public function __construct($userID) {
		$this->userID = $userID;
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
	}

	public function isGroupLeader() {
		$userData = get_userdata( $this->userID );
		if (in_array("group_leader", $userData->roles)) {
			return true;
		} else {
			return false;
		}
	}
}
?>
