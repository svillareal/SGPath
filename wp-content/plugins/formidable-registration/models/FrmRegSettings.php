<?php
class FrmRegSettings{

    var $settings;

    function FrmRegSettings(){
        $this->set_default_options();
    }
    
    function default_options(){
        return array(
            'login'     => '',
            'lostpass'  => '',
        );
    }

    function set_default_options( $settings=false ) {
        $default_settings = $this->default_options();
        
        if ( $settings === true ) {
            $settings = new stdClass();
        } else if ( !$settings ) {
            $settings = $this->get_options();
        }
            
        if ( !isset($this->settings) ) {
            $this->settings = new stdClass();
        }
        
        foreach ( $default_settings as $setting => $default ) {
            if ( is_object($settings) && isset($settings->{$setting}) ) {
                $this->settings->{$setting} = $settings->{$setting};
            }
                
            if ( !isset($this->settings->{$setting}) ) {
                $this->settings->{$setting} = $default;
            }
        }
    }
    
    function get_options(){
        $settings = get_option('frm_reg_options');

        if ( !is_object($settings) ) {
            if ( $settings ) { //workaround for W3 total cache conflict
                $settings = unserialize( serialize( $settings ) );
            }else{
                // If unserializing didn't work
                if ( !is_object( $settings ) ) {
                    if ( $settings ) { //workaround for W3 total cache conflict
                        $settings = unserialize(serialize($settings));
                    } else {
                        $settings = $this->set_default_options(true);
                    }
                    $this->store();
                }
            }
        }else{
            $this->set_default_options($settings); 
        }
        
        return $this->settings;
    }

    function validate($params,$errors){
       // if ( empty($params[ 'frm_pay_business_email' ] ) or !is_email($params[ 'frm_pay_business_email' ]))
            //$errors[] = __('Please enter a valid email address', 'formidable');
        return $errors;
    }

    function update( $params ) {
        $settings = $this->default_options();
        
        foreach ( $settings as $setting => $default ) {
            if ( isset($params['frm_reg_'. $setting] ) ) {
                $this->settings->{$setting} = $params['frm_reg_'. $setting];
            }
        }
    }

    function store(){
        // Save the posted value in the database
        update_option( 'frm_reg_options', $this->settings );
    }
  
}
