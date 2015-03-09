<?php
// Contains all the functions necessary to provide an update mechanism for FormidablePro!

class FrmUpdatesController{

    // Where all the vitals are defined for this plugin
    var $plugin_nicename        = 'formidable';
    var $plugin_name            = 'formidable/formidable.php';
    var $plugin_url             = 'http://formidablepro.com/formidable-wordpress-plugin';
    var $pro_mothership         = 'http://api.strategy11.com/plugin-updates/';
    var $pro_cred_store         = 'frmpro-credentials';
    var $pro_auth_store         = 'frmpro-authorized';
    var $pro_wpmu_store         = 'frmpro-wpmu-sitewide';
    var $pro_last_checked_store = 'frm_autoupdate';
    var $pro_check_interval     = 0; // Don't check. Pro updates will force over free updates
    var $timeout                = 10;

    // Don't modify these variables
    var $pro_license_str        = 'proplug-license';
    var $pro_mothership_xmlrpc_url = 'http://formidablepro.com/xmlrpc.php';
    var $pro_wpmu = false;

    var $pro_error_message_str;
    var $license        = '';
    var $pro_username   = '';
    var $pro_password   = '';

    function __construct(){
        // This line can be modifed too
        $this->pro_error_message_str = __('Your Formidable Pro License was Invalid', 'formidable');

        add_filter('site_transient_update_plugins', array( &$this, 'queue_update' ) );
        //add_action('wp_ajax_frm_activate_license', array( &$this, 'activate'));
        //add_action('wp_ajax_frm_deactivate_license', array( &$this, 'deactivate'));

        // Retrieve Pro Credentials
        if (is_multisite() && get_site_option($this->pro_wpmu_store)){
            $creds = get_site_option($this->pro_cred_store);
            $this->pro_wpmu = true;
        } else {
            $creds = get_option($this->pro_cred_store);
        }

        if ( $creds && is_array($creds) ) {
            $cred_array = array('license' => '', 'pro_username' => '', 'pro_password' => '');
            $creds = array_intersect_key($creds, $cred_array);
            foreach ( $creds as $k => $cred ) {
                $this->{$k} = $cred;
            }
        }


    }

    function pro_is_authorized($force_check=false){
        if ( empty($this->license) && empty($this->pro_username) && empty($this->pro_password) ) {
            return false;
        }

        if ( empty($this->license) ) {
            $license = $this->get_user_license();
            if ( empty($license) ) {
                return false;
            }
        }

        if ( is_multisite() && $this->pro_wpmu ) {
            $authorized = get_site_option($this->pro_auth_store);
        } else {
            $authorized = get_option($this->pro_auth_store);
        }

        if ( ! $force_check ) {
            return $authorized;
        }

        if ( ! empty($this->license) ) {
            $new_auth = $this->check_license();
            return $new_auth['auth'];
        }

        return false;
    }

    function pro_is_installed_and_authorized(){
        return $this->pro_is_authorized();
    }

    public function get_user_license(){
        include_once( ABSPATH . 'wp-includes/class-IXR.php' );

        $client = new IXR_Client( $this->pro_mothership_xmlrpc_url, false, 80, $this->timeout );

        if ( ! $client->query( 'proplug.get_license', $this->pro_username, $this->pro_password ) ) {
            return false;
        }

        $license = $client->getResponse();

        if($license && !empty($license))
            $this->_update_auth(array('license' => $license, 'wpmu' => $this->pro_wpmu));

        return $client->getResponse();
    }

    public function pro_cred_form(){
        global $frm_vars;
        if(isset($_POST) && isset($_POST['process_cred_form']) && $_POST['process_cred_form'] == 'Y'){
            if ( ! isset($_POST['frm_cred']) || ! wp_verify_nonce( $_POST['frm_cred'], 'frm_cred_nonce' ) ) {
                $frm_settings = FrmAppHelper::get_settings();
                $response = array('response' => $frm_settings->admin_permission, 'auth' => false);
            }else{
                $response = $this->process_form();
            }

            if($response['auth']){
                $frm_vars['pro_is_authorized'] = true;
?>
<div id="message" class="updated"><strong><?php _e('Your Pro installation is now active. Enjoy!', 'formidable'); ?></strong></div>
<?php       }else{ ?>
<div class="error">
    <strong><?php _e('ERROR', 'formidable'); ?></strong>: <?php echo $response['response']; ?>
</div>
<?php
            }
        }
?>
<div style="float:left;width:55%">
    <?php $this->display_form();

    if ( ! $frm_vars['pro_is_authorized'] ) { ?>
    <p>Already signed up? <a href="https://formidablepro.com/account/?action=licenses" target="_blank"><?php _e('Click here', 'formidable') ?></a> to get your license number.</p>
    <?php } ?>
</div>

<?php if($frm_vars['pro_is_authorized']){ ?>
<div class="frm_pro_installed">
<div><strong class="alignleft" style="margin-right:10px;"><?php _e('Formidable Pro is Installed', 'formidable') ?></strong>
    <a href="javascript:void(0)" class="frm_show_auth_form button-secondary alignleft"><?php _e('Enter new license', 'formidable') ?></a>
    <a href="#" id="frm_deauthorize_link" class="button-secondary alignright"><?php _e('Deauthorize this site', 'formidable') ?></a>
    <div class="spinner"></div>
</div>
<div class="clear"></div>
</div>
<p class="frm_aff_link"><a href="https://formidablepro.com/account/?action=licenses" target="_blank"><?php _e('Account', 'formidable') ?></a></p>
<?php } ?>

<div class="clear"></div>

<?php
    }

    function display_form(){
        global $frm_vars;

        // this is the view for the license form
        ?>
<div id="pro_cred_form" <?php echo $frm_vars['pro_is_authorized'] ? 'class="frm_hidden"' : ''; ?>>
    <form name="cred_form" method="post" autocomplete="off">
    <input type="hidden" name="process_cred_form" value="Y" />
    <?php wp_nonce_field('frm_cred_nonce', 'frm_cred'); ?>

    <p><input type="text" name="<?php echo esc_attr( $this->pro_license_str ); ?>" value="" style="width:97%;" placeholder="<?php esc_attr_e( 'Enter your license number here', 'formidable' ) ?>"/>

    <?php if ( is_multisite() ) {
        $creds = $this->get_pro_cred_form_vals(); ?>
        <br/><label for="proplug-wpmu"><input type="checkbox" value="1" name="proplug-wpmu" id="proplug-wpmu" <?php checked($creds['wpmu'], 1) ?> />
        <?php _e('Use this license to enable Formidable Pro site-wide', 'formidable'); ?></label>
    <?php } ?>
    </p>
    <input class="button-secondary" type="submit" value="<?php _e('Save License', 'formidable'); ?>" />
    <?php if($frm_vars['pro_is_authorized']){
        _e('or', 'formidable');
    ?>
        <a href="javascript:void(0)" class="frm_show_auth_form button-secondary"><?php _e('Cancel', 'formidable'); ?></a>
    <?php } ?>
    </form>
</div>
<?php
    }

    function process_form(){
        $creds = $this->get_pro_cred_form_vals();
        $user_authorized = $this->check_license($creds['license']);

        if ( ! empty($user_authorized['auth']) && $user_authorized['auth'] ) {
            $this->_update_auth($creds);
        }

        return $user_authorized;
    }

    private function _update_auth($creds){
        if (is_multisite())
            update_site_option($this->pro_wpmu_store, $creds['wpmu']);

        if ($creds['wpmu']){
            update_site_option($this->pro_cred_store, $creds);
            update_site_option($this->pro_auth_store, true);
        }else{
            update_option($this->pro_cred_store, $creds);
            update_option($this->pro_auth_store, true);
        }

        $this->license = (isset($creds['license']) && !empty($creds['license'])) ? $creds['license'] : '';
    }

    function get_pro_cred_form_vals(){
        $license = (isset($_POST[$this->pro_license_str])) ? $_POST[$this->pro_license_str] : $this->license;
        $wpmu = (isset($_POST['proplug-wpmu'])) ? true : $this->pro_wpmu;

        return compact('license', 'wpmu');
    }

    /*
    function activate(){
        $message = '';
        $errors = array();

        if ( !isset($_POST[ $this->pro_license_str ]) || empty($_POST[ $this->pro_license_str ]) ) {
            $errors[] = __('Please enter a license number', 'formidable');
            include(FrmAppHelper::plugin_path() .'/classes/views/shared/errors.php');
            die();
        }

        $this->license = $_POST[ $this->pro_license_str ];
        $domain = home_url();
        $args = compact('domain');

        try{
            $act = $this->send_mothership_request($this->plugin_nicename .'/activate/'. $hlpdsk_settings->license, $args);

            if(!is_array($act)){
                $errors[] = $act;
            }else{
                $this->manually_queue_update();
                $hlpdsk_settings->store(false);
                $message = $act['message'];
            }
        }
        catch(Exception $e){
            $errors[] = $e->getMessage();
        }

        include(FrmAppHelper::plugin_path() .'/classes/views/shared/errors.php');
        die();
    }
    */

    function check_license($license=false){
        $save = true;
        if(empty($license)){
            $license = $this->license;
            $save = false;
        }

        if(empty($license))
            return array('auth' => false, 'response' => __('Please enter a license number', 'formidable'));

        $domain = home_url();
        $args = compact('domain');

        $act = $this->send_mothership_request($this->plugin_nicename .'/activate/'. $license, $args);

        if($save){
            $auth = false;
            if ( is_array($act) ) {
                $this->manually_queue_update();

                $auth = is_array($act) ? true : false;
                $wpmu = (isset($_POST) && isset($_POST['proplug-wpmu'])) ? true : $this->pro_wpmu;

                //save response
                if ( is_multisite() ) {
                    update_site_option($this->pro_wpmu_store, $wpmu);
                }

                if ($wpmu){
                    update_site_option($this->pro_cred_store, compact('license', 'wpmu'));
                    update_site_option($this->pro_auth_store, $auth);
                }else{
                    update_option($this->pro_cred_store, compact('license', 'wpmu'));
                    update_option($this->pro_auth_store, $auth);
                }

            }

            return array('auth' => $auth, 'response' => $act);
        }

        return array('auth' => false, 'response' => __('Please enter a license number', 'formidable'));
    }

    /*
    function deactivate(){
        delete_option($this->pro_cred_store);
        delete_option($this->pro_auth_store);

        if(empty($this->license))
            return;

        $domain = home_url();
        $args = compact('domain');
        $errors = array();

        try{
            $act = $this->send_mothership_request($this->plugin_nicename .'/deactivate/'. $this->license, $args);
            if ( ! is_array($act) ) {
                $errors[] = $act;
            } else {
                $message = $act['message'];
            }
        }
        catch(Exception $e){
            $errors[] = $e->getMessage();
        }

        include(FrmAppHelper::plugin_path() .'/classes/views/shared/errors.php');
        die();
    }
    */

    function queue_update($transient) {
        if ( ! is_object($transient) || ! $this->pro_is_authorized() ) {
            return $transient;
        }

        //make sure it doesn't show there is an update if plugin is up-to-date
        if ( ! empty( $transient->checked ) &&
            isset($transient->checked[ $this->plugin_name ]) &&
            ((isset($transient->response) && isset($transient->response[$this->plugin_name]) &&
            $transient->checked[ $this->plugin_name ] == $transient->response[$this->plugin_name]->new_version) or
            ( ! isset($transient->response)) || empty($transient->response)) ) {

            if(isset($transient->response[$this->plugin_name]))
                unset($transient->response[$this->plugin_name]);
            set_site_transient( $this->pro_last_checked_store, 'latest', $this->pro_check_interval );

        //always change the download path to the pro version
        }else if(isset($transient->response) && isset($transient->response[$this->plugin_name]) &&
            strpos($transient->response[$this->plugin_name]->url, 'wordpress.org')){

            $version_info = get_site_transient( $this->pro_last_checked_store );

            //don't force an api check if the transient has already been forced
            if($version_info && is_array($version_info) && strpos($transient->response[$this->plugin_name]->url, 'formidablepro.com') && isset($version_info['version']) && version_compare($version_info['version'], FrmAppHelper::plugin_version(), '>') && isset($version_info['url']) && $version_info['url'] == $transient->response[$this->plugin_name]->package) {
                $force = false;
            } else {
                $force = true;
            }

            $plugin = $this;
            $transient = $this->queue_addon_update($transient, $plugin, $force, false);
            unset($plugin);

            if ( strpos( $transient->response[$this->plugin_name]->url, 'wordpress.org' ) ) {
                // the pro checking failed, but we still don't want to update to the free version
                unset($transient->response[$this->plugin_name]);
            }

            remove_filter('frm_pro_update_msg', array(&$this, 'no_permission_msg'));
        } else if (isset($transient->response) && isset($transient->response[$this->plugin_name]) &&
            isset($transient->response[$this->plugin_name]->upgrade_notice) &&
            !empty($transient->response[$this->plugin_name]->upgrade_notice)) {
            add_filter('frm_pro_update_msg', array(&$this, 'no_permission_msg'));
        }

        return $transient;
    }

    function queue_addon_update($transient, $plugin, $force=false, $checked=false){
        if ( $force !== true ) {
            // make sure another plugin isn't inserting other data
            $force = false;
        }

        if ( ! $this->pro_is_authorized() || ! is_object($transient) || $checked || ( empty($transient->checked) && ! $force ) ) {
            return $transient;
        }

        // check if we have already checked this page load
        global $frm_vars;
        if ( ! isset($frm_vars['plugins_checked']) ) {
            $frm_vars['plugins_checked'] = array();
        } else if ( isset($frm_vars['plugins_checked'][$plugin->plugin_name]) ) {
            if ( $frm_vars['plugins_checked'][$plugin->plugin_name] != 'latest' ) {
                $transient->response[$plugin->plugin_name] = $frm_vars['plugins_checked'][$plugin->plugin_name];
            }
            return $transient;
        }

        $installed_version = (empty($transient->checked) || ! isset($transient->checked[$plugin->plugin_name]) ) ? '1' : $transient->checked[$plugin->plugin_name];
        $version_info = $this->get_version($force, $plugin);

        if ( $version_info && isset($version_info['version']) && ( $force || version_compare($version_info['version'], $installed_version, '>') ) ) {
            if ( isset($transient->response[$plugin->plugin_name]) && $transient->response[$plugin->plugin_name]->new_version == $version_info['version'] ) {
                $frm_vars['plugins_checked'][$plugin->plugin_name] = $transient->response[$plugin->plugin_name];
                return $transient;
            }

            $transient->response[$plugin->plugin_name] = new stdClass();
            $transient->response[$plugin->plugin_name]->id = 0;
            $transient->response[$plugin->plugin_name]->slug = $plugin->plugin_nicename;
            $transient->response[$plugin->plugin_name]->new_version = $version_info['version'];
            $transient->response[$plugin->plugin_name]->url = 'http://formidablepro.com/';

            if(isset($version_info['url'])){
                $transient->response[$plugin->plugin_name]->package = $version_info['url'];
            }else{
                //new version available, but no permission
                $expired = isset($version_info['expired']) ? __('expired', 'formidable') : __('invalid', 'formidable');
                $transient->response[$plugin->plugin_name]->upgrade_notice = sprintf(__('An update is available, but your license is %s.', 'formidable'), $expired);
                add_filter('frm_pro_update_msg', array(&$this, 'no_permission_msg'));
            }

            // add this plugin to the checked array to prevent multiple checks per page load
            $frm_vars['plugins_checked'][$plugin->plugin_name] = $transient->response[$plugin->plugin_name];

            set_site_transient('update_plugins', $transient);
        } else {
            $frm_vars['plugins_checked'][$plugin->plugin_name] = 'latest';

            if ( ! $version_info && isset($transient->response[$plugin->plugin_name]) ) {
                unset( $transient->response[$plugin->plugin_name] );

                // check again in 1 hour if there was an error to prevent timeout loops
                set_site_transient( $plugin->pro_last_checked_store, 'latest', 60*60 );
            }
        }

        return $transient;
    }

    function get_version($force = false, $plugin = false) {
        global $frm_vars;
        if ( $plugin && $plugin->plugin_nicename != $this->plugin_nicename ) {
            //don't check for update if pro is not installed
            if ( ! $frm_vars['pro_is_authorized'] ) {
                return false;
            }
        }

        if ( ! $force ) {
            // if not forced, allow version_info to be equal to 'latest'
            $version_info = get_site_transient( $plugin->pro_last_checked_store );
        } else if ( isset($frm_vars['forced']) && $frm_vars['forced'] ) {
            $version_info = $frm_vars['forced'];
            if ( ! is_array($frm_vars['forced']) ) {
                return false;
            }
        }

        if ( isset($version_info) && $version_info && $version_info != 'latest' && ! is_array($version_info) ) {
            $version_info = false;
        }

        if ( ! isset($version_info) || ! $version_info ) {
            $errors = false;
            if ( empty($this->license) && ! empty($this->pro_username) && ! empty($this->pro_password) ) {
                //get license from credentials
                $this->get_user_license();
            }

            if ( ! empty($this->license) ) {
                $domain = home_url();
                $args = compact('domain');

                $version_info = $this->send_mothership_request($plugin->plugin_nicename .'/info/'. $this->license, $args);
                if ( ! is_array($version_info) ) {
                    $errors = true;
                }
            }

            if ( ! isset($version_info) || $errors ) {
                // query for the current version
                $version_info = $this->send_mothership_request($plugin->plugin_nicename .'/latest');
                $errors = ! is_array($version_info) ? true : false;
            }

            //don't force again on same page
            $frm_vars['forced'] = $version_info;

            if($errors)
                return false;

            // store in transient for 24 hours
            set_site_transient( $plugin->pro_last_checked_store, $version_info, $plugin->pro_check_interval );
        }

        return (array) $version_info;
    }

    function manually_queue_update(){
        $transient = get_site_transient('update_plugins');
        set_site_transient('update_plugins', $this->queue_update($transient));
    }

    function queue_button(){ ?>
<a href="<?php echo admin_url('admin.php?page=helpdesk-options&action=queue&_wpnonce=' . wp_create_nonce( $this->manually_queue_update() )); ?>" class="button"><?php _e('Check for Update', 'formidable')?></a>
<?php
    }

    function send_mothership_request( $endpoint, $args = array(), $domain='' ) {
        if ( empty($domain) ) {
            $domain = $this->pro_mothership;
        }
        $uri = trailingslashit($domain . $endpoint);

        $arg_array = array(
            'body'      => $args,
            'timeout'   => $this->timeout,
            'sslverify' => false,
            'user-agent' => 'Formidable/'. FrmAppHelper::plugin_version() .'; '. get_bloginfo( 'url' )
        );

        $resp = wp_remote_post($uri, $arg_array);
        $body = wp_remote_retrieve_body( $resp );

        if(is_wp_error($resp)){
            $message = sprintf(__('You had an error communicating with Strategy11\'s API. %1$sClick here%2$s for more information.', 'formidable'), '<a href="http://formidablepro.com/knowledgebase/why-cant-i-activate-formidable-pro/" target="_blank">', '</a>');
            if(is_wp_error($resp))
                $message .= ' '. $resp->get_error_message();
            return $message;
        }else if($body == 'error' || is_wp_error($body)){
            return __('You had an HTTP error connecting to Strategy11\'s API', 'formidable');
        }else{
            $json_res = json_decode($body, true);
            if ( null !== $json_res ) {
                if ( is_array($json_res) && isset($json_res['error']) ) {
                    return $json_res['error'];
                } else {
                    return $json_res;
                }
            }else if(isset($resp['response']) && isset($resp['response']['code'])){
                return sprintf(__('There was a %1$s error: %2$s', 'formidable'), $resp['response']['code'], $resp['response']['message'] .' '. $resp['body']);
            }
        }

        return __( 'Your License Key was invalid', 'formidable');
    }

    function no_permission_msg(){
        return __('A Formidable Forms update is available, but your license is invalid.', 'formidable');
    }

}

