<?php 
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

include_once('Outcome.php');

class CoreTrainingStatus {
	//Attributes
	public $statusCheck;
	public $entryID;
	public $formID;
	public $coreFieldID;
	public $resFieldID;
	public $versionFieldID;
	protected $lastCheckedResourceID;
	//this assignment only matters if they have completed an older version of the resource for a core section than the current one in the system; assigned 'old' if the user chooses to display his/her previously checked-off version, or 'new' if he/she chooses to display the updated version
	public $coreVersion;
	public $coreCheckedScore;
	protected $coreCheckValue;
	//assigned 'previous' if this user's has completed a previously associated resource for this section or 'current' if the user either hasn't completed a resource for this section or the version they have completed is the latest version
	public $version;
	//array of post IDs for all core resources that get displayed for a particular user
	public $corePostID;
	
	//Methods
	public function __construct($postID, $userID) {
		global $wpdb;
		$outcome = new Outcome($postID);
		$user = new SgpUser($userID);
		if (($outcome->statusCheck == "good") && ($user->statusCheck == "good")) {
			$this->statusCheck = "good";
		} else {
			$this->statusCheck = "bad";
			return;
		}
		$coreCategories = new CoreCategories();
		$numberOfCore = CoreCategories::numCoreCategories();
		$formName = "Resource Checkboxes - ".$outcome->title;
		$this->formID = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}frm_forms WHERE name=%s", $formName));
		$this->entryID = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}frm_items WHERE user_id=%d AND form_id=%d ORDER BY created_at DESC", $user->userID, $this->formID));
		for ($i = 0; $i <= ($numberOfCore-1); $i++) {
			$coreFieldOrd = CoreCategories::$coreFieldOrder[$i];
			$resFieldOrd = CoreCategories::$resFieldOrder[$i];
			$versionFieldOrd = CoreCategories::$versionFieldOrder[$i];
			$this->coreFieldID[$i] = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}frm_fields WHERE form_id=%d AND field_order=%d", $this->formID, $coreFieldOrd));
			$this->resFieldID[$i] = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}frm_fields WHERE form_id=%d AND field_order=%d", $this->formID, $resFieldOrd));
			$this->versionFieldID[$i] = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}frm_fields WHERE form_id=%d AND field_order=%d", $this->formID, $versionFieldOrd));
			$this->lastCheckedResourceID[$i] = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id=%d AND item_id=%d", $this->resFieldID[$i], $this->entryID));
			$this->coreVersion[$i] = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id=%d AND item_id=%d", $this->versionFieldID[$i], $this->entryID));
		}
		if ($user->userView !== "non_member") {
			$coreCheckedTot = 0;
			$coreCheckedTally = 0;
			for ($i = 0; $i <= ($numberOfCore-1); $i++) {
				if ($this->coreVersion[$i] == "new") {
					$this->coreCheckValue[$i] == 0;
				} else {
				$cFieldID = $this->coreFieldID[$i];
				$this->coreCheckValue[$i] = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id=%d AND item_id=%d", $cFieldID, $this->entryID));
				}
				if ($outcome->coreHide[$i] == "1" && !(($outcome->coreID[$i] == NULL) || ($outcome->coreID[$i] == -1))) {
					$coreCheckedTally = $coreCheckedTally + (int)$this->coreCheckValue[$i];
					$coreCheckedTot = $coreCheckedTot + 1;
				}
			}
			if ($coreCheckedTot == 0) {
				$coreCheckedPerc = 0;
			} else {
				$coreCheckedPerc = ($coreCheckedTally/$coreCheckedTot)*100;
			}
			$this->coreCheckedScore = round($coreCheckedPerc, 3);
	}
		//check to see if user has checked off an older version of the resource before & get resource info
		for ($i = 0; $i <= ($numberOfCore-1); $i++) {
			if (($outcome->coreID[$i] !== NULL) || ($outcome->coreID[$i] !== -1)) {
				if (($this->lastCheckedResourceID[$i] != $outcome->coreID[$i]) && ($this->coreCheckValue[$i] == "1") && ($this->coreVersion[$i] == "old")) {
					$this->version[$i] = "previous";
					$displayCoreID[$i] = $this->lastCheckedResourceID[$i];
				} else {
					$this->version[$i] = "current";
					$displayCoreID[$i] = $outcome->coreID[$i];
				}
					$this->corePostID[$i] = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->prefix}frm_items WHERE id=%d", $displayCoreID[$i]));
			}
		}
	}
	
	public function getCoreChecked($i) {
		if ($this->coreCheckValue[$i] == 1) {
			$coreChecked[$i] = "checked";
		} else {
			$coreChecked[$i] = "";
		}
		return $coreChecked[$i];
	}

	public function getCoreImage($i) {
		if ($this->coreCheckValue[$i] == 1) {
			$coreImage[$i] = "opacity50";
		} else {
			$coreImage[$i] = "";
		}
		return $coreImage[$i];
	}

	public function getCoreDiv($i) {
		if ($this->coreCheckValue[$i] == 1) {
			$coreDiv[$i] = "style='color:lightGray'";
		} else {
			$coreDiv[$i] = "";
		}
		return $coreDiv[$i];
	}

	public function getDisplayedExtras ($outcomePostID) {
		$outcome = new Outcome($outcomePostID);
		$extrasArray = $outcome->getExtras();
		foreach ($this->corePostID as $key => $value) {
			if (($outcome->coreHide[$key] == "1")) {
				$coreDisplay[$key] = getEntryID($value);
			}
		}
		$extrasDisplay = array_diff($extrasArray, $coreDisplay);
		$extrasDisplay = array_values($extrasDisplay);
		return $extrasDisplay;
	}

}


?>