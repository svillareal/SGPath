<?php
class FrmProSettings extends FrmSettings{
    public $option_name = 'frmpro_options';

    // options
    public $edit_msg;
    public $update_value;
    public $already_submitted;
    public $rte_off;
    public $csv_format;
    public $cal_date_format;
    public $date_format;
    public $permalinks;

    /*
    * @return array
    */
    function default_options(){
        return array(
            'edit_msg'          => __('Your submission was successfully saved.', 'formidable'),
            'update_value'      => __('Update', 'formidable'),
            'already_submitted' => __('You have already submitted that form', 'formidable'),
            'rte_off'           => false,
            'csv_format'        => 'UTF-8',
        );
    }

    function set_default_options() {
        if ( ! isset($this->date_format) ) {
            $this->date_format = 'm/d/Y';
        }

        if ( !isset($this->cal_date_format) ) {
            $this->cal_date_format = 'mm/dd/yy';
        }

        //if(!isset($this->permalinks))
            $this->permalinks = false;

        $this->fill_with_defaults();
    }

    function update($params){
        $this->date_format = $params['frm_date_format'];
        $formats = array(
            'Y/m/d' => 'yy/mm/dd',
            'd/m/Y' => 'dd/mm/yy',
            'd.m.Y' => 'dd.mm.yy',
            'j/m/y' => 'd/mm/y',
            'Y-m-d' => 'yy-mm-dd',
            'j-m-Y' => 'd-mm-yy',
        );

        if ( isset($formats[$this->date_format]) ) {
            $this->cal_date_format = $formats[$this->date_format];
        } else {
            $this->cal_date_format = 'mm/dd/yy';
        }

        //$this->permalinks = isset($params['frm_permalinks']) ? $params['frm_permalinks'] : 0;

        $this->fill_with_defaults($params);
    }

    function store(){
        // Save the posted value in the database
        update_option( $this->option_name, $this);

        delete_transient($this->option_name);
        set_transient($this->option_name, $this);
    }
}