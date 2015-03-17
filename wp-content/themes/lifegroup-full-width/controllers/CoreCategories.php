<?php 
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class CoreCategories {

	//Attributes
	public static $coreCategories = array ("Bible Study", "Reading", "Scripture Memory", "Activity", "Group Discussion", "Other");
	//backgorund image	
	public static $coreImgFieldID = array("853", "854", "855", "856", "857", "858");
	//forground image
	public static $coreTrnImgSrc = array (
		"http://localhost/lg/wp-content/uploads/2015/02/Study.png",
		"http://localhost/lg/wp-content/uploads/2015/02/Read.png",
		"http://localhost/lg/wp-content/uploads/2015/02/Memorize.png",
		"http://localhost/lg/wp-content/uploads/2015/02/Experience.png",
		"http://localhost/lg/wp-content/uploads/2015/02/Discuss.png",
		"http://localhost/lg/wp-content/uploads/2015/02/OtherTxt.png"
		);
	public static $coreAddID = array("bsAddID", "rAddID", "smAddID", "aAddID", "gdAddID", "oAddID");
	public static $coreCatNoSpace;
	public static $coreDivID;
	public static $coreImgID;
	public static $coreImgURL;

	//Methods
	public static function numCoreCategories() {
		$count = count(self::$coreCategories);
		return $count;
	}

	public function __construct() {
		global $wpdb;
		for ($i = 0; $i <= (self::numCoreCategories()-1); $i++) {
			self::$coreCatNoSpace[$i] = str_replace(' ', '', self::$coreCategories[$i]);
			self::$coreDivID[$i] = "div".self::$coreCatNoSpace[$i];
			$fieldID = self::$coreImgFieldID[$i];
			self::$coreImgID[$i] = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id=%d", $fieldID));
			self::$coreImgURL[$i] = wp_get_attachment_url( self::$coreImgID[$i] );
		}
	}
	
}


?>