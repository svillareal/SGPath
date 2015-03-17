<?php

class FrmProAppController{

    public static function create_taxonomies() {
        register_taxonomy( 'frm_tag', 'formidable', array(
            'hierarchical' => false,
            'labels' => array(
                'name' => __( 'Formidable Tags', 'formidable' ),
                'singular_name' => __( 'Formidable Tag', 'formidable' ),
            ),
            'public' => true,
            'show_ui' => true,
        ) );
    }

    public static function drop_tables($tables) {
        global $wpdb;
        $tables[] = $wpdb->prefix .'frm_display';
        return $tables;
    }

    public static function set_get($atts){
		foreach ( $atts as $att => $val ) {
            $_GET[$att] = $val;
            unset($att, $val);
        }
    }

    public static function load_genesis(){
        //trigger Genesis hooks for integration
        FrmProAppHelper::load_genesis();
    }

}
