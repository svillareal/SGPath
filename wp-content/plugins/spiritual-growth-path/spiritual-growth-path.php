<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
* Plugin Name: Spiritual Growth Path
* Description: This plugin is used for adding theme-independent funcitons to this website.
* Version: 1.0
* Author: Sherilyn Villareal
* Author URI: http://design.sherilynvillareal.com
*/

//Load files
include_once 'helpers/AppHelpers.php';
include_once 'views/PageTemplater.php';
require_once 'controllers/ajax-includes.php';


//Page load

		//Enqueue scripts and styles
		function sgp_scripts() {
		  wp_register_script( 'spiritual-growth-path', plugins_url( '/js/sgp_script.js', __FILE__ ), array( 'jquery'));
		  wp_register_style( 'spiritual-growth-path', plugins_url( 'spiritual-growth-path/css/style.css' ) );
		  $url = plugins_url();
		  $plugin_path = array( 'plugin_path' =>  $url );
		  wp_localize_script( 'spiritual-growth-path', 'plugin_info', $plugin_path );  
		  wp_enqueue_script( 'spiritual-growth-path' );
		  wp_enqueue_style( 'spiritual-growth-path' );
		  $nonce_ccr = wp_create_nonce( 'changeCoreResource' );
		  $nonce_uccs = wp_create_nonce( 'updateCoreCompletionStatus' );
		  $nonce_utncv = wp_create_nonce( 'updateToNewCoreVersion' );
		  $nonce_utocv = wp_create_nonce( 'updateToOldCoreVersion' );
		  $nonce_hrc = wp_create_nonce( 'hideResourceCategory' );
		  $nonce_rme = wp_create_nonce( 'removeExtra' );
		  $nonce_bpe = wp_create_nonce( 'bumpExtra' );
		  $nonce_rcr = wp_create_nonce( 'removeCoreResource' );
		  $nonce_rse = wp_create_nonce( 'restoreExtra' );
		  wp_localize_script( 'spiritual-growth-path', 'my_ajax_obj', array(
			   'ajax_url' => admin_url( 'admin-ajax.php' ),
			   'changeCoreResourceNonce'    => $nonce_ccr,
			   'updateCoreCompletionStatusNonce'    => $nonce_uccs,
			   'updateToNewCoreVersionNonce'    => $nonce_utncv,
			   'updateToOldCoreVersionNonce'    => $nonce_utocv,
			   'hideResourceCategoryNonce'    => $nonce_hrc,
			   'removeExtraNonce'    => $nonce_rme,
			   'bumpExtraNonce'    => $nonce_bpe,
			   'removeCoreResourceNonce'    => $nonce_rcr,
			   'restoreExtraNonce'    => $nonce_rse,
			) );
		}
		add_action( 'wp_enqueue_scripts', 'sgp_scripts' );
		
		
		//Disable admin bar for all users except admin
		function remove_admin_bar() {
			//************************once user types set, change this to only sgp-specific user types
			if (!current_user_can('administrator') && !is_admin()) {
			  show_admin_bar(false);
			}
		}
		add_action('after_setup_theme', 'remove_admin_bar');
		
		
		//Re-direct all users except admin away from wp-admin panel
		function restrict_admin_with_redirect() {
			//**************************change to sgp-specific user re-directs once user types are set
			if ( ! current_user_can( 'manage_options' ) && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
				wp_redirect( site_url() ); 
				exit;
			}
		}
		add_action( 'admin_init', 'restrict_admin_with_redirect', 1 );

//Page display
		
		//Change page templates for custom post types
		function get_custom_post_type_template($single_template) {
			 global $post;
			 if ($post->post_type == 'resource') {
				  $single_template = SgpAppHelpers::plugin_path() . '/views/single-resource.php';
			 } else if ($post->post_type == 'spiritual_outcomes') {
				  $single_template = SgpAppHelpers::plugin_path() . '/views/single-spiritual_outcomes.php';
			 }
			 return $single_template;
		}
		add_filter( 'single_template', 'get_custom_post_type_template' );
		

//Plugin activation actions
		
		//Add SGP database tables on plugin activation
			function coremeta_install() {
				global $wpdb;
			  
				$table_name = $wpdb->prefix . 'coremeta';
				
				$charset_collate = $wpdb->get_charset_collate();
			
				$sql = "CREATE TABLE $table_name (
					id mediumint(9) NOT NULL AUTO_INCREMENT,
					outcomeID smallint,
					coreCategory varchar(50),
					resourceEntryID smallint,
					updated_by mediumint(9),
					created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
					UNIQUE KEY id (id)
				) $charset_collate;";
			
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta( $sql );
			}
			
			function extrasmeta_install() {
				global $wpdb;
			  
				$table_name = $wpdb->prefix . 'extrasmeta';
				
				$charset_collate = $wpdb->get_charset_collate();
			
				$sql = "CREATE TABLE $table_name (
					id mediumint(9) NOT NULL AUTO_INCREMENT,
					resourceID smallint,
					outcomeID smallint,
					listingOrder smallint,
					created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
					UNIQUE KEY id (id)
				) $charset_collate;";
			
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta( $sql );
			}
		register_activation_hook( __FILE__, 'coremeta_install' );
		register_activation_hook( __FILE__, 'extrasmeta_install' );
		
		
		//Add new custom pages on plugin activation
		//***************This function needs to be updated to reflect actual page additions.
		function add_sgp_pages() {
			$new_page_title = 'Did it work';
			$new_page_content = "Here's the page content!";
			$new_page_template = 'outcome-overview.php'; //ex. template-custom.php. Leave blank if you don't want a custom page template.
			$page_check = get_page_by_title($new_page_title);
			$new_page = array(
				'post_type' => 'page',
				'post_title' => $new_page_title,
				'post_content' => $new_page_content,
				'post_status' => 'publish',
				'post_author' => 1,
			);
			if(!isset($page_check->ID)){
				$new_page_id = wp_insert_post($new_page);
				if(!empty($new_page_template)){
					update_post_meta($new_page_id, '_wp_page_template', $new_page_template);
				}
			}
		}
		register_activation_hook( __FILE__, 'add_sgp_pages' );		

  ?>