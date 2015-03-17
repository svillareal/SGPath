<?php

class FrmProEntriesListHelper extends FrmEntriesListHelper {

	function get_bulk_actions(){
        $actions = array( 'bulk_delete' => __( 'Delete'));

        if ( ! current_user_can('frm_delete_entries') ) {
            unset($actions['bulk_delete']);
        }

        //$actions['bulk_export'] = __( 'Export to XML', 'formidable' );

        $actions['bulk_csv'] = __( 'Export to CSV', 'formidable' );

        return $actions;
    }

    function extra_tablenav( $which ) {
        $footer = ($which == 'top') ? false : true;
        FrmProEntriesHelper::before_table($footer, $this->params['form']);
	}

}
