<?php
/* Email Functions */

class FrmRegNotification {
    
    /*
    * Sends activation link to pending user
    *
    * @since 1.11
    *
    * @param integer $user_id
    * @param string $key
    * @return email
    */
	public static function new_user_activation_notification( $user_id, $key = '' ) {
		global $wpdb, $current_site;

        //Get user object
		$user = new WP_User( $user_id );

		$user_login = stripslashes( $user->user_login );
		$user_email = stripslashes( $user->user_email );

		if ( empty( $key ) ) {
			$key = $wpdb->get_var( $wpdb->prepare( "SELECT user_activation_key FROM $wpdb->users WHERE user_login = %s", $user_login ) );
			if ( empty( $key ) ) {
				$key = wp_generate_password( 20, false );
				$wpdb->update( $wpdb->users, array( 'user_activation_key' => $key ), array( 'user_login' => $user_login ) );
			}
		}

		if ( is_multisite() ) {
			$blogname = $current_site->site_name;
		} else {
			// The blogname option is escaped with esc_html on the way into the database in sanitize_option
			// we want to reverse this for the plain text arena of emails.
			$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		}

        // Create activation URL
        $params = array('action' => 'frm_activate_user', 'key' => $key, 'login' => rawurlencode( $user_login ) );
        $activation_url = FrmRegAppHelper::create_ajax_url( $params );

		$title    = sprintf( __( '[%s] Activate Your Account', 'frmreg' ), $blogname );
		$message  = sprintf( __( 'Thanks for registering at %s! To complete the activation of your account please click the following link: ', 'frmreg' ), $blogname ) . "\r\n\r\n";
		$message .=  $activation_url . "\r\n";

        // Hooks to customize subject and message
        $title   = apply_filters( 'user_activation_notification_title',   $title,   $user_id );
        $message = apply_filters( 'user_activation_notification_message', $message, $activation_url, $user_id );

		return wp_mail( $user_email, $title, $message );
	}

	/*
    * Send Formidable and Registration emails for users who have just confirmed their email address
    *
    * @since 1.11
    */
	public static function send_all_notifications( $user ) {
        if ( !is_object( $user ) ) {
            $user = new WP_User( $user );
        }
        //Get entry ID from user meta
        $entry = get_user_meta( $user->ID, 'frmreg_entry_id', 1 );
        $entry = FrmEntry::getOne($entry);

        //Get form and form's settings
        $form = FrmForm::getOne($entry->form_id);
        $settings = FrmRegAppHelper::get_registration_settings( $form );
        if ( empty( $settings ) ) {
            return;
        }

        // Send Formidable emails now
        if ( FrmRegAppHelper::is_below_2() ) {
            FrmProNotification::entry_created($entry->id, $form->id);
        } else {
            FrmFormActionsController::trigger_actions('create', $form->id, $entry->id, 'email');
        }

        // Send admin email
        wp_new_user_notification($user->ID, ''); // sending a blank password only sends notification to admin

		// Check for plaintext pass
        $user_pass = get_user_meta( $user->ID, 'frmreg_user_pass', 1 );
		if ( ! $user_pass ) {
			$user_pass = wp_generate_password();
			wp_set_password( $user_pass, $user->ID );
		}

        // Trigger registration email
        self::new_user_notification($user->ID, $user_pass, $form, $entry->id, $settings);
	}

    public static function new_user_notification( $user_id, $plaintext_pass, $form, $entry_id, $settings = false ) {
		$user = new WP_User( $user_id );

        if ( is_object($entry_id) ) {
            $entry = $entry_id;
            $entry_id = $entry->id;
        }
        
		$user_login = stripslashes( $user->user_login );
		$user_email = stripslashes( $user->user_email );
        $form->options = maybe_unserialize($form->options);
        
        if ( empty($settings) ) {
            $settings = $form->options;
        }

        //Add a filter so the email notification can be stopped
		if ( apply_filters( 'frm_send_new_user_notification', true, $form, $entry_id, compact('entry') ) !== true ) {
		    return;
		}
		
		if ( is_multisite() ) {
    		$blogname = $GLOBALS['current_site']->site_name;
    	} else {
    		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
    		// we want to reverse this for the plain text arena of emails.
    		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
    	}
		    
		if ( ! isset($settings['reg_email_msg']) || empty($settings['reg_email_msg']) ) {
		    $message  = sprintf(__('Username: %s', 'frmreg'), $user_login) . "\r\n";
        	$message .= sprintf(__('Password: %s', 'frmreg'), $plaintext_pass) . "\r\n";
        	$message .= wp_login_url() . "\r\n";
    	} else {
    	    $message = str_replace('[password]', $plaintext_pass, $settings['reg_email_msg'] );
    	    $message = str_replace('[username]', $user_login, $message );
    	    $message = str_replace('[sitename]', $blogname, $message );
    	    $message = apply_filters('frm_content', $message, $form, $entry_id);
    	}
        $message = apply_filters( 'frm_new_user_notification_message', $message, $plaintext_pass, $user_id );
            
        if ( ! isset($settings['reg_email_subject']) || empty($settings['reg_email_subject']) ) {
        	$title = sprintf( __( '[%s] Your username and password', 'frmreg' ), $blogname);
        } else {
        	$title = str_replace('[sitename]', $blogname, $settings['reg_email_subject'] );
        	$title = apply_filters('frm_content', $title, $form, $entry_id);
        }
		$title = apply_filters( 'frm_new_user_notification_title', $title, $user_id );
		
		$header = array();
		if ( isset($settings['reg_email_from']) && isset($settings['reg_email_sender']) ) {
		    $header[] = 'From: "'. $settings['reg_email_from'] .'" <'. $settings['reg_email_sender'] .'>';
		}
        
		wp_mail( $user_email, $title, $message, $header );
	}
	
	// This function is triggered from payment plugins
	public static function send_paid_user_notification($entry) {
	    if ( !is_object($entry) ) {
            $entry = FrmEntry::getOne($entry);
	    }

	    if ( empty($entry->user_id) ) {
	        return;
	    }

        $form = FrmForm::getOne($entry->form_id);

        $settings = FrmRegAppHelper::get_registration_settings($form);
        if ( empty($settings) ) {
            return;
        }

        if ( !isset($settings['reg_password']) || empty($settings['reg_password'])) {
            // if password was automatically generated, make a new one so it can be included in the email
            $password = wp_generate_password( 12, false );

            // Now update the user account with the new password
            if ( !function_exists('wp_insert_user') ) {
                require_once(ABSPATH . WPINC . '/registration.php');
            }

            wp_insert_user(array(
                'user_pass' => wp_hash_password($password),
                'ID'        => $entry->user_id,
            ));
        } else {
            $password = __('Created at signup', 'frmreg');
        }

	    self::new_user_notification($entry->user_id, $password, $form, $entry->id, $settings);
	}
}