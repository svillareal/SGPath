<?php 
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

include_once('Outcome.php');

class Resource {

	//Attributes
	public $postID;
	public $entryID;
	public $type;
	public $title;
	public $author;
	public $externalURL;
	public $internalURL;
	protected $imageID;
	public $imageURL;
	public $description;
	public $assocOutcomeEntryIDs;
	public $deletedOutcomeEntryIDs;
	public $dates;
	protected $pdfUploadedYN;
	public $pdfUploadID;
	protected $webLink;
	public $embedAudio;
	public $embedVideo;
	public $numberOfPassages;
	public $scriptureReference;
	public $scriptureVersion;
	public $scripturePassage;
	public $truncate;
	public $excerpt;

	//Methods
	public function __construct($resourcePostID) {
		global $wpdb;
		$this->postID = $resourcePostID;
		$this->entryID = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}frm_items WHERE post_id=%d", $this->postID));
		$this->type = get_field('extrasType', $this->postID);
		$this->title = get_the_title($this->postID);
		$this->author = get_field('extrasAuthor', $this->postID);
		$this->description = get_field('extrasDescription', $this->postID);
		$this->internalURL = get_permalink($this->postID);
		$this->dates = get_field('extrasDate', $this->postID);
		$this->imageID = get_field('extrasImageID', $this->postID);
		$this->pdfUploadedYN = get_field('extrasNeedToUpload', $this->postID);
		$this->pdfUploadID = get_field('extrasUploadID', $this->postID);
		$this->webLink = get_field('extrasExternalURL', $this->postID);
		$this->embedAudio = get_field('extrasAudioEmbed', $this->postID);
		$this->embedVideo = get_field('extrasVideoEmbed', $this->postID);
		$this->numberOfPassages = get_field('extrasNumPassages', $this->postID);
		for ($i = 0; $i <= ($this->numberOfPassages - 1); $i++) {
			$b = $i + 1;
			$this->scriptureReference[$i] = get_field('extrasRef'.$b, $this->postID);
			$this->scriptureVersion[$i] = get_field('extrasVer'.$b, $this->postID);
			$this->scripturePassage[$i] = get_field('extrasPass'.$b, $this->postID);
		}
		//get image url
		if ($this->imageID == NULL) {
			//use default image
			$this->imageID = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id='863'");
		}
		$this->imageURL = wp_get_attachment_url( $this->imageID );		
		//get external url for resource
		if (($this->pdfUploadedYN == 0) || !($this->type == "PDF Download")) {
			$this->externalURL = get_field('extrasExternalURL', $this->postID);
		}
		else {
			$this->externalURL =  wp_get_attachment_url( $this->pdfUploadID );
		}
		//get excerpt if description needs to be truncated
		$this->truncate = 0;
		if (strlen($this->description) >= 260) {
			$this->truncate = 1;
			$this->excerpt = substr($this->description, 0, strpos($this->description, ' ', 260));
		}
		//get the associated and deleted outcome arrays
		$aOutcomes = get_field('extrasOutcomeName', $this->postID);
		$dOutcomes = get_field('extrasPreviousOutcomes', $this->postID);
		$aOutcomes = maybe_unserialize($aOutcomes);
		if (!(is_array($aOutcomes))) {
			$aOutcomes = array($aOutcomes);
		}
		if ($aOutcomes !== false) {
			$this->assocOutcomeEntryIDs = $aOutcomes;
		}
		else {
			$this->assocOutcomeEntryIDs = NULL;
		}
		$dOutcomes = maybe_unserialize($dOutcomes);
		if (!(is_array($dOutcomes))) {
			$dOutcomes = array($dOutcomes);
		}
		if ($dOutcomes !== false) {
			$this->deletedOutcomeEntryIDs = $dOutcomes;
		}
		else {
			$this->deletedOutcomeEntryIDs = NULL;
		}
	}	
	
	public function getVideoEmbedCode() {
		if (($this->embedVideo == "1") && ($this->type == "Video") && ($this->webLink !== "")) {
		$videoHost = parse_url($this->webLink, PHP_URL_HOST);
			if ($videoHost == "vimeo.com") {
				$videoNumber = substr($this->webLink,-9);
				$videoEmbedfront = "<iframe width='267' height='150' src='//player.vimeo.com/video/";
				$videoEmbedback = "?portrait=0' frameborder='0' webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>";
				$videoEmbed = $videoEmbedfront.$videoNumber.$videoEmbedback;	
			  }
			  else if ($videoHost == "www.youtube.com") {
				$videoNumber = substr($this->webLink,-11);
				$videoEmbedfront = "<iframe width='267' height='150' src='//www.youtube.com/embed/";
				$videoEmbedback = "' frameborder='0' allowfullscreen></iframe>";
				$videoEmbed = $videoEmbedfront.$videoNumber.$videoEmbedback;
			  }
			return $videoEmbed;
		} else {
			$errorMessage = "Sorry, there was a problem with your request.";
			return $errorMessage;
		}
	}
	
	public function checkAudio() {
		if (($this->embedAudio == "1") && (strpos($this->externalURL,'.mp3') !== false)) {
			$audioCheck = "good";
		}
		else {
			$audioCheck = "bad";
		}
		return $audioCheck;
	}

	public function olderVersionCompleted($userID, $outcomePostID, $coreIteration) {
		$coreTrainingStatus = new CoreTrainingStatus($userID);
		$outcome = new Outcome($outcomePostID);	
		if (($coreTrainingStatus->version[$coreIteration] == "previous") && !(($outcome->coreID[$coreIteration] == NULL) || ($outcome->coreID[$coreIteration] == -1))) {
			return true;
		} else {
			return false;
		}
	}
	
}


?>