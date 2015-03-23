<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}
add_action( 'wp_enqueue_scripts', 'admin_reports_scripts' );

/**
 * The Template for displaying single Spiritual Outcome main pages
 *	(part of the customized theme files for Gateway's website - designed by Sherilyn Villareal)

 * @package WordPress
 * @subpackage Twenty_Fourteen
 * @since Twenty Fourteen 1.0
 */

get_header();
  
//Get required files
include_once(SgpAppHelpers::plugin_path().'/sgp-includes.php');

//User validation
	$currentSgpUser = new SgpUser(get_current_user_id());
	if ( $currentSgpUser->statusCheck == "bad" ) {
		echo "Sorry, you are not a valid user.  Please try logging out and back in again.";
		get_footer();
		exit;
	}


get_header();
?>
<div class="row">
	<div class="column5">
	<div class="ct-chart ct-perfect-fourth">Hello out there!</div>
    </div>
</div>

<?php

get_footer();
