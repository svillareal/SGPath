<?php

class FrmProStylesController extends FrmStylesController{

    public static function load_pro_hooks() {
        if ( FrmAppHelper::is_admin_page('formidable-styles') ) {
            add_filter('frm_style_head', 'FrmProStylesController::maybe_new_style');
            add_filter('frm_style_action_route', 'FrmProStylesController::pro_route');
        }
    }

    public static function maybe_new_style($style) {
        $action = FrmAppHelper::get_param('frm_action');
    	if ( 'new_style' == $action ) {
            $style = self::new_style('style');
    	}
        return $style;
    }

    public static function new_style($return = '') {
        $frm_style = new FrmStyle();
        $style = $frm_style->get_new();

        if ( 'style' == $return ) {
            // return style object for header css link
            return $style;
        }

        self::load_styler($style);
    }

    public static function destroy() {
        $id = (int) $_GET['id'];

        $frm_style = new FrmStyle();
        $frm_style->destroy($id);

        $message = __( 'Your styling settings have been deleted.', 'formidable' );

        self::edit('default', $message);
    }

    public static function pro_route($action) {
        switch ( $action ) {
            case 'new_style':
            case 'duplicate':
            case 'destroy':
                add_filter('frm_style_stop_action_route', '__return_true');
                return self::$action();
        }
    }

}

