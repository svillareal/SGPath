<?php

class FrmProStatisticsController{

    public static function show() {
        remove_action('frm_form_action_reports', 'FrmStatisticsController::list_reports');
        add_filter('frm_form_stop_action_reports', '__return_true');

        global $wpdb;

        $form = false;
        if ( isset($_REQUEST['form'] ) ) {
            $form = FrmForm::getOne($_REQUEST['form']);
        }

        if ( ! $form ) {
            require(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-statistics/select.php');
            return;
        }

        $exclude_types = FrmFieldsHelper::no_save_fields();
        $exclude_types = array_merge($exclude_types, array(
            'rte', 'textarea', 'file', 'grid',
            'signature', 'form', 'table',
        ) );

        $fields = FrmField::getAll("fi.type not in ('". implode("','", $exclude_types) ."') and fi.form_id=". (int) $form->id, 'field_order');

        $js = '';
        $data = array();
        $colors = '#21759B,#EF8C08,#C6C6C6';

        $data['time'] = self::get_daily_entries($form, array(
            'is3d' => true, 'colors' => $colors, 'bg_color' => 'transparent',
        ));
        $data['month'] = self::get_daily_entries($form, array(
            'is3d' => true, 'colors' => $colors, 'bg_color' => 'transparent',
            'width' => '100%',
        ), 'MONTH');

        foreach ( $fields as $field ) {

			$this_data = self::graph_shortcode(array(
                'id' => $field->id, 'field' => $field, 'is3d' => true, 'min' => 0,
                'colors' => $colors, 'width' => 650, 'bg_color' => 'transparent',
            ));

            if ( strpos($this_data, 'frm_no_data_graph') === false ) {
                $data[$field->id] = $this_data;
            }

            unset($field, $this_data);
        }

        $entries = $wpdb->get_col($wpdb->prepare("SELECT created_at FROM {$wpdb->prefix}frm_items WHERE form_id=%d", $form->id));

        include(FrmAppHelper::plugin_path() .'/pro/classes/views/frmpro-statistics/show.php');
    }

    static function get_google_graph($field, $args){
        $defaults = array('allowed_col_types' => array('string', 'number'));

        $args = wp_parse_args($args, $defaults);
        $vals = self::get_graph_values($field, $args);

        if ( empty($vals) ) {
            return;
		}

        $pie = ( $args['type'] == 'default' ) ? $vals['pie'] : ( $args['type'] == 'pie' ? true : false );
        if ( $pie ) {
            $args['type'] = 'pie';

            $vals['cols'] = array('Field' => array('type' => 'string'), 'Entries' => array('type' => 'number')); //map each array position in rows array

            foreach ( $vals['values'] as $val_key => $val ) {
                if ( $val ) {
                    $vals['rows'][] = array($vals['labels'][$val_key], $val);
                }
            }
        } else {
            if ( ! isset($vals['options']['hAxis']) ) {
                $vals['options']['hAxis'] = array();
            }

            $vals['options']['vAxis'] = array('gridlines' => array('color' => $args['grid_color']));
            if ( $vals['combine_dates'] && ! strpos($args['width'], '%') && ( ( count($vals['labels']) * 50 ) > (int) $args['width'] ) ) {
                $vals['options']['hAxis']['showTextEvery'] = ceil(( count($vals['labels']) * 50 ) / (int) $args['width'] );
            }

            $vals['options']['hAxis']['slantedText'] = true;
            $vals['options']['hAxis']['slantedTextAngle'] = 20;

            $rn_order = array();
            foreach ( $vals['labels'] as $lkey => $l ) {
                if ( isset($x_field) && $x_field && $x_field->type == 'number' ) {
                    $l = (float) $l;
                    $rn_order[] = $l;
                }

                $row = array($l, $vals['values'][$lkey]);
                foreach ( $vals['f_values'] as $f_id => $f_vals ) {
                    $row[] = isset($f_vals[$lkey]) ? $f_vals[$lkey] : 0;
                }

                // add an untruncated tooltip
                if ( isset($vals['tooltips'][$lkey]) ) {
                    $row['tooltip'] = $vals['tooltips'][$lkey] .': '. $row[1];
                }

                $vals['rows'][] = $row;
                unset($lkey, $l);
            }

            if ( isset($args['max']) && $args['max'] != '' ) {
                $vals['options']['vAxis']['maxValue'] = $args['max'];
            }

            if ( $args['min'] != '' ) {
                $vals['options']['vAxis']['minValue'] = $args['min'];
            }

			if ( isset($args['y_title']) && ! empty($args['y_title']) ) {
                $vals['options']['vAxis']['title'] = $args['y_title'];
			}

			if ( isset($args['x_title']) && ! empty($args['x_title']) ) {
                $vals['options']['hAxis']['title'] = $args['x_title'];
			}
        }

        if ( isset($rn_order) && !empty($rn_order) ) {
            asort($rn_order);
            $sorted_rows = array();
            foreach ( $rn_order as $rk => $rv ) {
                $sorted_rows[] = $vals['rows'][$rk];
            }

            $vals['rows'] = $sorted_rows;
        }

        $vals['options']['backgroundColor'] = $args['bg_color'];
        $vals['options']['is3D'] = $args['is3d'] ? true : false;

        if ( in_array($args['type'], array('bar', 'bar_flat', 'bar_glass')) ) {
            $args['type'] = 'column';
        } else if ( $args['type'] == 'hbar' ) {
            $args['type'] = 'bar';
        }

        $allowed_types = array('pie', 'line', 'column', 'area', 'SteppedArea', 'geo', 'bar');
        if ( ! in_array($args['type'], $allowed_types) ) {
            $args['type'] = 'column';
        }

        $vals['options'] = apply_filters( 'frm_google_chart', $vals['options'], array(
            'rows' => $vals['rows'], 'cols' => $vals['cols'], 'type' => $args['type'], 'atts' => $args['atts'],
        ) );
        return self::convert_to_google($vals['rows'], $vals['cols'], $vals['options'], $args['type']);
    }

    static function get_graph_values( $field, $args ) {
        // These are variables that will be returned at the end
        $values = $labels = $tooltips = $f_values = $rows = $cols = $options = $x_inputs = array();
        $pie = $combine_dates = false;

        $args['user_id'] = (int) $args['user_id'];

        // Get fields and ids array
        $fields_and_ids = self::get_fields( $field, $args['ids'] );
        $args['ids'] = $fields_and_ids['ids'];
        $args['fields'] = $fields_and_ids['fields'];
        
        // Get x field
        self::get_x_field( $args );

        // Get columns
        self::get_graph_cols( $cols, $field, $args );
        
        // Get options
        self::get_graph_options( $options, $field, $args );

        // Get entry IDs
        self::get_entry_ids( $field, $args );
        
        // If there are no matching entry IDs for filtering values, end now
        if ( $args['atts'] && ! $args['entry_ids'] ) {
            return;
        }

        // Get values when x axis is set
        if ( $args['x_axis'] ) {
            self::get_x_axis_values( $values, $f_values, $labels, $tooltips, $x_inputs, $field, $args );

            self::combine_dates( $combine_dates, $values, $labels, $tooltips, $f_values, $args);

    		// Graph by month or quarter
            self::graph_by_period( $values, $f_values, $labels, $tooltips, $args );
        
        // Get values by field if no x axis is set and multiple fields are being graphed
        } else if ( $args['ids'] ) {
            self::get_multiple_id_values( $values, $labels, $tooltips, $args );

        // Get values for posts and non-posts
        } else {
            // TODO: Make sure this works with truncate_label
            self::get_count_values( $values, $labels, $tooltips, $pie, $field, $args );
        }

        // Reset keys for labels, values, and tooltips
        $labels = FrmProAppHelper::reset_keys($labels);
        $values = FrmProAppHelper::reset_keys($values);
        $tooltips = FrmProAppHelper::reset_keys($tooltips);

        // Filter hooks
		$values = apply_filters('frm_graph_values', $values, $args, $field);
        $labels = apply_filters('frm_graph_labels', $labels, $args, $field);
        $tooltips = apply_filters('frm_graph_tooltips', $tooltips, $args, $field);

        // Return values
        $return = array(
            'f_values' => $f_values,
            'labels' => $labels,
            'values' => $values,
            'pie' => $pie,
            'combine_dates' => $combine_dates,
            'ids' => $args['ids'],
            'cols' => $cols,
            'rows' => $rows,
            'options' => $options,
            'fields' => $args['fields'],
            'tooltips' => $tooltips,
            'x_inputs' => $x_inputs,
        );

        // Allow complete customization with this hook:
        $return = apply_filters( 'frm_final_graph_values', $return, $args, $field );

        return $return;
    }

    /*
    * Get values for x-axis graph
    *
    * Since 2.0
    *
    * @param $values - values array
    * @param $f_values - values array if multiple fields are graphed
    * @param $labels - labels array
    * @param $tooltipss - tooltips array
    * @param $x_inputs - array of inputs for x-axis
    * @param $field - field object
    * @param $args - arguments array
    */
    public static function get_x_axis_values( &$values, &$f_values, &$labels, &$tooltips, &$x_inputs, $field, $args ){
        // Get form posts. This will return empty if the form does not create posts.
        $form_posts = self::get_form_posts( $field, $args );

        // Get all inputs
        $inputs = $f_inputs = $x_inputs = $f_values = array();
        self::get_x_axis_inputs( $inputs, $f_inputs, $x_inputs, $field, $args );

        // Modify post inputs
        $field_options = '';
        if ( ! $args['atts'] ) {
            self::mod_post_inputs( $inputs, $field_options, $field, $form_posts, $args );
        }

        // Modify x inputs and set up f_values - TODO: what does this really dO?
        self::mod_x_inputs( $x_inputs, $inputs, $f_values, $args );

        // Format f_inputs
        self::format_f_inputs( $f_inputs, $f_values, $args );

        // Get final x_axis values
        self::get_final_x_axis_values( $values, $f_values, $labels, $tooltips, $inputs, $x_inputs, $f_inputs, $args );
    }

    /*
    * Get values for graph with only one field and no x-axis
    *
    * Since 2.0
    *
    * @param $values - values array
    * @param $labels - labels array
    * @param $tooltipss - tooltips array
    * @param $pie - boolean for pie graph
    * @param $field - field object
    * @param $args - arguments array
    */
    public static function get_count_values( &$values, &$labels, &$tooltips, &$pie, $field, $args ) {
        // Get all inputs for this field
        $inputs = self::get_generic_inputs( $field, $args );

        if ( ! $inputs ) {
            return;
        }

        // Get counts for each value
        $temp_values = array_count_values( array_map( 'strtolower', $inputs ) );

        // Get displayed values ( for DFE, separate values, or Other val )
        if ( $field->type == 'data' || $field->field_options['separate_value'] || ( isset( $field->field_options['other'] ) && $field->field_options['other'] ) ) {
            self::get_displayed_values( $temp_values, $field );
        } else if ( $field->type == 'user_id' ) {
            self::get_user_id_values($values, $labels, $tooltips, $pie, $temp_values, $field );
            return;
        }

        // Sort values by order of field options
        if ( $args['x_order'] == 'field_opts' && in_array( $field->type, array( 'radio', 'checkbox', 'select', 'data' ) ) ) {
            self::field_opt_order_vals( $temp_values, $field );

        // Sort by descending count if x_order is set to 'desc'
        } else if ( $args['x_order'] == 'desc' ) {
            arsort( $temp_values );

        // Sort alphabetically by default
        } else {
            ksort( $temp_values );
        }

        // Get slice of array
        if ( $args['limit'] ) {
            $temp_values = array_slice( $temp_values, 0, $args['limit'] );
        }

        // Capitalize the first letter of each value
        foreach ( $temp_values as $val => $count ) {
            $new_val = ucwords( $val );
            $labels[] = $new_val;
            $values[] = $count;
        }
    }

    /*
    * Get inputs for graph (when no x-axis is set and only one field is graphed)
    *
    * Since 2.0
    *
    * @param $field - field object
    * @param $args - arguments array
    * @return $inputs - array of all values for field
    */
    public static function get_generic_inputs( $field, $args ) {
        $meta_args = array('entry_ids', 'user_id', 'start_date', 'end_date');
        foreach ( $meta_args as $key => $arg ) {
            if ( $args[$arg] ) {
                $meta_args[$arg] = $args[$arg];
            }
            unset( $meta_args[$key], $key, $arg );
        }

        // Get the metas
        $inputs = FrmProEntryMeta::get_all_metas_for_field( $field, $meta_args );

        // Clean up multi-dimensional array
        self::clean_inputs( $inputs, $field, $args );

        return $inputs;
    }

    /*
    * Order values so they match the field options order
    *
    * Since 2.0
    *
    * @param $temp_values - array of values
    * @param $field - field object
    */
    public static function field_opt_order_vals( &$temp_values, $field ) {
        $reorder_vals = array();
        foreach ( $field->options as $opt ) {
            if ( ! $opt ) {
                continue;
            }
            if ( is_array( $opt ) ) {
                if ( ! isset( $opt['label'] ) || ! $opt['label'] ) {
                    continue;
                }
                $opt = strtolower( $opt['label'] );
            } else {
                $opt = strtolower( $opt );
            }
            if ( ! isset( $temp_values[$opt] ) ) {
                continue;
            }
            $reorder_vals[$opt] = $temp_values[$opt];
        }
        $temp_values = $reorder_vals;
    }

    /*
    * Get displayed values for separate values, data from entries, and other option.
    * Capitalizes first letter of each option
    *
    * Since 2.0
    *
    * @param $temp_values - values array
    * @param $field - field object
    */
    public static function get_displayed_values( &$temp_values, $field ) {
        $temp_array = array();

        // If data from entries field
        if ( $field->type == 'data' ) {

            // Get DFE text
            foreach ( $temp_values as $entry_id => $total ) {
                $linked_field = $field->field_options['form_select'];
                $text_val = FrmProEntriesController::get_field_value_shortcode(array('field_id' => $linked_field, 'entry_id' => $entry_id));
                $temp_array[$text_val] = $total;
                unset( $entry_id, $total, $linked_field, $text_val );
            }
        } else {
            $other_label = false;
            foreach ( $field->options as $opt_key => $opt ) {
                if ( ! $opt ) {
                    continue;
                }
                // If field option is "other" option
                if ( FrmAppHelper::is_other_opt( $opt_key ) ) {

                    // For radio button field, combine all extra counts/totals into one "Other" count/total
                    if ( $field->type == 'radio' || $field->type == 'select' ) {
                        $other_label = strtolower( $opt );
                        continue;

                    // For checkbox fields, set value and label
                    } else {
                        $opt_value = strtolower( $opt_key );
                        $opt_label = strtolower( $opt );
                    }

                // If using separate values
                } else if ( is_array( $opt) ) {
                    $opt_label = strtolower( $opt['label'] );
                    $opt_value = strtolower( $opt['value'] );
                    if ( ! $opt_value || ! $opt_label ) {
                        continue;
                    }
                } else {
                    $opt_label = $opt_value = strtolower( $opt );
                }

                // Set displayed value total in new array, unset original value in old array
                if ( isset( $temp_values[$opt_value] ) ) {
                    $temp_array[$opt_label] = $temp_values[$opt_value];
                    unset( $temp_values[$opt_value] );
                }
                unset( $opt_key, $opt, $opt_label, $opt_value );
            }
            // Applies to radio buttons only (with other option)
            // Combines all extra counts/totals into one "Other" count/total
            if ( $other_label ) {
                $temp_array[$other_label] = array_sum( $temp_values );
            }
        }

        // Copy new array
        $temp_values = $temp_array;
    }

    /*
    * Get options for graph
    *
    * Since 2.0
    *
    * @param $options - options array
    * @param $field - field object
    * @param $args - arguments array
    */
    public static function get_graph_options( &$options, $field, $args ) {
        // Set up defaults
        $options = array('width' => $args['width'], 'height' => $args['height'], 'legend' => 'none' );

        if ( $args['colors'] ) {
            $options['colors'] = $args['colors'];
        }

		if ( $args['title'] ) {
            $options['title'] = $args['title'];
        } else {
            $options['title'] = preg_replace("/&#?[a-z0-9]{2,8};/i", "", FrmAppHelper::truncate($field->name, $args['truncate'], 0));
        }

		if ( $args['title_size'] ) {
            $options['titleTextStyle']['fontSize'] = $args['title_size'];
        }

        if ( $args['title_font'] ) {
            $options['titleTextStyle']['fontName'] = $args['title_font'];
        }

        if ( $args['show_key'] ) {
            // Make sure show_key isn't too small
            if ( $args['show_key'] < 5 ) {
                $args['show_key'] = 10;
            }
            $options['legend'] = array('position' => 'right', 'textStyle' => array( 'fontSize' => $args['show_key'] ) );
        }

        if ( $args['x_field'] ) {
            $options['hAxis'] = array('title' => $args['x_field']->name);
        }
    }

    /*
    * Get graph columns
    *
    * Since 2.0
    *
    * @param $cols - cols array
    * @param $field - field object
    * @param $args - arguments array
    */
    public static function get_graph_cols( &$cols, $field, $args ) {
        // Set default x-axis type
        $cols['xaxis'] = array('type' => 'string');

        if ( $args['x_field'] ) {
            $cols['xaxis'] = array('type' => ( in_array( $args['x_field']->type, $args['allowed_col_types']) ? $args['x_field']->type : 'string' ), 'id' => $args['x_field']->id );
        }

        // If x axis is not set, only set up cols as if there were one field
        if ( ! $args['x_axis'] ) {
            $args['fields'] = array( $field->id => $field );
        }

        //add columns for each field
		$count = 0;
        $tooltip_label = $args['tooltip_label'];
        foreach ( $args['fields'] as $f_id => $f ) {
            $type = in_array( $f->type, $args['allowed_col_types'] ) ? $f->type : 'number';
            // If custom tooltip label is set, change the tooltip label to match user-defined text
			if ( isset( $tooltip_label[$count] ) && !empty( $tooltip_label[$count ]) ) {
				$cols[$tooltip_label[$count]] = array( 'type' => $type, 'id' => $f->id );
				$count++;

            // If tooltip label is not set by user, use the field name
			} else {
				$cols[$f->name] = array('type' => $type, 'id' => $f->id );
			}
            unset($f, $f_id);
        }
    }

    /*
    * Get fields and ids arrays
    *
    * Since 2.0
    *
    * @param $field - field object
    * @param $ids - array of additional field ids
    * @return $return - multidimensional array of fields and ids
    */
    public static function get_fields( $field, $ids ) {;
        $fields = array();
        $fields[$field->id] = $field;

        // If multiple fields are being graphed
        if ( $ids ) {
            $ids = explode(',', $ids);

            foreach ( $ids as $id_key => $f ) {
                $ids[$id_key] = $f = trim($f);
                if ( ! $f ) {
                    unset( $ids[$id_key] );
                    continue;
                }

                if ( $add_field = FrmField::getOne( $f ) ) {
                    $fields[$add_field->id] = $add_field;
                    $ids[$id_key] = $add_field->id;
                }
                unset($f, $id_key);
            }
        }
        $return = compact( 'fields', 'ids' );
        return $return;
    }

    /*
    * Get all posts for this form
    *
    * Since 2.0
    *
    * @param $field - field object
    * @param $args - arguments array
    * @return $form_posts - array of posts for form
    */
    public static function get_form_posts( $field, $args ) {
		global $wpdb;
        if ( $args['entry_ids'] && is_array( $args['entry_ids'] ) ) {
            $args['entry_ids'] = implode( ', ', $args['entry_ids'] );
        }

		$query = "SELECT id, post_id FROM {$wpdb->prefix}frm_items 
            WHERE 
            form_id = %d 
            AND post_id >= %d" . 
            ( $args['user_id'] ? " AND user_id=%d" : '') . 
            ( $args['entry_ids'] ? " AND id in ({$args['entry_ids']})" : '' );
		$temp_values = array_filter( array( $field->form_id, 1, $args['user_id'] ) );
		$form_posts = $wpdb->get_results( $wpdb->prepare( $query, $temp_values ) );

        return $form_posts;
    }

    /*
    * Get entry IDs array for graph - only when entry_id is set or filtering by another field
    *
    * Since 2.0
    *
    * @param $field - field object
    * @param $args - arguments array
    */
    public static function get_entry_ids( $field, &$args ) {
        if ( ! $args['entry_ids'] && ! $args['atts'] ) {
            return;
        }

        // Check if form creates posts
        $form_posts = self::get_form_posts( $field, $args );

        $entry_ids = array();

        // If entry ID is set in shortcode
        if ( $args['entry_ids'] ) {
            $entry_ids = explode( ',', $args['entry_ids'] );
        }

        //If filtering by other fields
        if ( $args['atts'] ) {
	        //Get the entry IDs for fields being used to filter graph data
            $after_where = false;
			foreach( $args['atts'] as $orig_f => $val ) {
				$entry_ids = FrmProFieldsHelper::get_field_matches(array(
					'entry_ids' => $entry_ids, 'orig_f' => $orig_f, 'val' => $val,
					'id' => $field->id, 'atts' => $args['atts'], 'field' => $field,
					'form_posts' => $form_posts, 'after_where' => $after_where ,
                    'drafts' => false,
				));
				$after_where = true;
			}
	    }
        $args['entry_ids'] = $entry_ids;
    }

    /*
    * Get x_field
    *
    * Since 2.0
    *
    * @param args (array)
    */
    public static function get_x_field( &$args ) {
        // Assume there is no x field
        $args['x_field'] = false;

        // If there is an x_axis and it is a field ID or key
        if ( $args['x_axis'] && ! in_array( $args['x_axis'], array( 'created_at', 'updated_at' ) ) ) {
			$args['x_field'] = FrmField::getOne( $args['x_axis'] );
		}
    }

    /*
    * Get inputs, x_inputs, and f_inputs for graph when x_axis is set
    * TODO: Clean this function
    *
    * Since 2.0
    *
    * @param $inputs - inputs array for main field
    * @param $f_inputs - multi-dimensional array for additional field inputs
    * @param $x_inputs - array for x-axis inputs
    * @param $field - field object
    * @param $args - arguments array
    */
    public static function get_x_axis_inputs( &$inputs, &$f_inputs, &$x_inputs, $field, $args ) {
        global $wpdb;

        $user_id = $args['user_id'];

        // Set up both queries
        $query = $x_query = 'SELECT em.meta_value, em.item_id FROM ' . $wpdb->prefix . 'frm_item_metas em';

        // Join regular query with items table
        $query .= ' LEFT JOIN '. $wpdb->prefix . 'frm_items e ON (e.id=em.item_id)';

        // If x axis is a field
        if ( $args['x_field'] ) {
            $x_query .= ' LEFT JOIN '. $wpdb->prefix . 'frm_items e ON (e.id=em.item_id)';
            $x_query .= " WHERE em.field_id=" . $args['x_field']->id;

        // If x-axis is created_at or updated_at
        } else {
            $x_query = 'SELECT id, '. $args['x_axis'] .' FROM '. $wpdb->prefix . 'frm_items e';
            $x_query .= " WHERE form_id=". $field->form_id;
        }

        // Will be used when graphing multiple fields
        $select_query = $query;
        $and_query = '';

        // Add where to regular query
        $query .= " WHERE em.field_id=". (int) $field->id;

        /// If start date is set
        if ( $args['start_date'] ) {
            $start_date = $wpdb->prepare('%s', date('Y-m-d', strtotime( $args['start_date'] ) ) );
            $query .= ' AND e.created_at>=' . $start_date;
            $and_query .= ' AND e.created_at>=' . $start_date;
            if ( $args['x_field'] ) {
                $x_query .= " and meta_value >= " . $start_date;
            } else {
                $x_query .= " and e." . $args['x_axis'] . ">= " . $start_date;
            }
        }

        // If end date is set
        if ( $args['end_date'] ) {
            $end_date = $wpdb->prepare('%s', date('Y-m-d 23:59:59', strtotime( $args['end_date'] )));
            $query .= ' AND e.created_at<=' . $end_date;
            $and_query .= ' AND e.created_at<=' . $end_date;
            if ( $args['x_field'] ) {
                $x_query .= " and meta_value <= " . $end_date;
            } else {
                $x_query .= " and e." . $args['x_axis'] . "<= " . $end_date;
            }
        }

        //If user_id is set
        if ( $user_id ) {
            $query .= $wpdb->prepare(' AND user_id=%d', $user_id);
            $x_query .= $wpdb->prepare(' AND user_id=%d', $user_id);
            $and_query .= $wpdb->prepare(' AND user_id=%d', $user_id);
        }

        //If entry_ids is set
        // TODO: Test if this works with atts
		if ( $args['entry_ids'] ) {
			$query .= " AND e.id in (" . implode( ',', $args['entry_ids'] ) . ")";
			$x_query .= " AND e.id in (" . implode( ',', $args['entry_ids'] ) . ")";
            $and_query .= " AND e.id in (" . implode( ',', $args['entry_ids'] ) . ")";
        }

        // Don't include drafts
        $query .= ' AND e.is_draft=0';
        $x_query .= ' AND e.is_draft=0';
        $and_query .= ' AND e.is_draft=0';

        // If graphing multiple fields, set up multiple queries
        $q = array();
        foreach ( $args['fields'] as $f_id => $f ) {
            if ( $f_id != $field->id ) {
                $q[$f_id] = $select_query . " WHERE em.field_id=". (int) $f_id . ( ( $user_id ) ? " AND user_id='$user_id'" : '');
                $q[$f_id] .= $and_query;
            }
            unset($f, $f_id);
        }
        if ( empty($q) ) {
            $f_inputs = array();
        }

		//Set up $x_query for data from entries fields.
		if ( $args['x_field'] && $args['x_field']->type == 'data' ) {
			$linked_field = $args['x_field']->field_options['form_select'];
			$x_query = str_replace('SELECT em.meta_value, em.item_id', 'SELECT dfe.meta_value, em.item_id', $x_query);
			$x_query = str_replace($wpdb->prefix . 'frm_item_metas em', $wpdb->prefix . 'frm_item_metas dfe, ' . $wpdb->prefix . 'frm_item_metas em', $x_query);
			$x_query = str_replace('WHERE', 'WHERE dfe.item_id=em.meta_value AND dfe.field_id=' . $linked_field . ' AND', $x_query);
		}

        // Get inputs
        $query = apply_filters('frm_graph_query', $query, $field, $args);
        $inputs = $wpdb->get_results($query, ARRAY_A);

        // Get x inputs
        $x_query = apply_filters('frm_graph_xquery', $x_query, $field, $args);
        $x_inputs = $wpdb->get_results($x_query, ARRAY_A);

        unset( $query, $x_query );

        //If there are multiple fields being graphed
        foreach ( $q as $f_id => $query ) {
            $f_inputs[$f_id] = $wpdb->get_results($query, ARRAY_A);
            unset($f_id, $query);
        }

        // There is no data, so don't graph
        if ( ! $inputs || ! $x_inputs ) {
            return array();// TODO: When do I want to return an array and when do I want to return false?
		}

        // Clean up inputs
        self::clean_inputs( $inputs, $field, $args );
        self::clean_inputs( $x_inputs, $field, $args);
    }

    /*
    * Strip slashes and get rid of multi-dimensional arrays in inputs
    *
    * Since 2.0
    *
    * @param $inputs - inputs array
    * @param $field - field object
    * @param $args - arguments array
    * @return $inputs - cleaned inputs array
    */
    public static function clean_inputs( &$inputs, $field, $args ) {
        if ( ! $inputs ) {
            return false;
        }

	    //Break out any inner arrays (for checkbox or multi-select fields) and add them to the end of the $inputs array
	    if ( ! $args['x_axis'] && FrmFieldsHelper::is_field_with_multiple_values( $field ) ) {
            $count = 0;
		    foreach ( $inputs as $k => $i ) {
			    $i = maybe_unserialize($i);
			    if ( ! is_array( $i ) ) {
				    unset($k, $i);
				    continue;
			    }

			    unset($inputs[$k]);
                $count++;
			    foreach ( $i as $i_key => $item ) {
                    // If this is an "other" option, keep key
                    if ( strpos( $i_key, 'other' ) !== false ) {
                        $inputs[] = $i_key;
                    } else {
                        $inputs[] = $item;
                    }
				    unset($item, $i_key);
			    }
			    unset($k, $i);
		    }
            unset($count);
	    }

	    //Strip slashes from inputs
		$inputs = stripslashes_deep($inputs);

        return $inputs;
    }

    /*
    * Modify post values (only applies to x-axis graphs)
    *
    * Since 2.0
    *
    * @param $inputs - inputs array
    * @param $field_options - field_options array
    * @param $field - field object
    * @param $form_posts - posts array
    * @param $args - arguments array
    */
    public static function mod_post_inputs( &$inputs, &$field_options, $field, $form_posts, $args ) {
        if ( ! $form_posts ) {
            return;
        }

		//Declare $field_options variable.
		$field_options = $field->options;

        //if ( $skip_posts_code ) {return}//TODO: Check if this is necessary

        // If field is not a post field, return
        if ( isset( $field->field_options['post_field']) && $field->field_options['post_field'] != '' ) {
            $post_field_type = $field->field_options['post_field'];
        } else {
            return;
        }
        global $wpdb;

        // If category field
        if ( $post_field_type == 'post_category' ) {
            $field_options = FrmProFieldsHelper::get_category_options( $field );

        // If field is a custom field
        } else if ( $post_field_type == 'post_custom' && $field->field_options['custom_field'] != '' ) {
            foreach ( $form_posts as $form_post ) {
                $meta_value = get_post_meta( $form_post->post_id, $field->field_options['custom_field'], true );
                if ( $meta_value) {
                    if ( $args['x_axis'] ) {
                        $inputs[] = array('meta_value' => $meta_value, 'item_id' => $form_post->id);
                    } else {
                        $inputs[] = $meta_value;
                    }
                }
            }
        // If regular post field
        } else{
            if ( $post_field_type == 'post_status') {
                $field_options = FrmProFieldsHelper::get_status_options( $field );
            } 
        	foreach( $form_posts as $form_post ) {
            	$post_value = $wpdb->get_var("SELECT " . $post_field_type . " FROM $wpdb->posts WHERE ID=" . $form_post->post_id);
            	if ( $post_value ) {
                	if ( $args['x_axis'] ) {
                    	$inputs[] = array('meta_value' => $post_value, 'item_id' => $form_post->id);
                	} else {
                    	$inputs[] = $post_value;
                    }
            	}
        	}
        }
    }

    /*
    * Modify inputs for x-axis
    * TODO: Clean this function
    *
    * Since 2.0
    *
    * @param $x_inputs - x inputs array
    * @param $inputs - inputs array
    * @param $f_values - array of additional field values
    * @param $args - arguments array
    */
    public static function mod_x_inputs( &$x_inputs, &$inputs, &$f_values, $args ) {
        if ( $x_inputs ) {
            $x_temp = array();
            foreach ( $x_inputs as $x_input ) {
                if ( ! $x_input ) {
                    continue;
                }

                if ( $args['x_field'] ) {
                    $x_temp[$x_input['item_id']] = $x_input['meta_value'];
                } else {
                    $x_temp[$x_input['id']] = $x_input[$args['x_axis']];
                }
            }
            $x_inputs = apply_filters('frm_graph_value', $x_temp, ($args['x_field'] ? $args['x_field'] : $args['x_axis']), $args);
            unset($x_temp, $x_input);
        }

        if ( $args['x_axis'] ){
            $y_temp = array();
            foreach ( $inputs as $input ) {
                $y_temp[$input['item_id']] = $input['meta_value'];
            }
            foreach ( $args['ids'] as $f_id ) {
                if ( ! isset( $f_values[ $f_id ] ) ) {
                    $f_values[$f_id] = array();
                }
                $f_values[$f_id][key($y_temp)] = 0;
                unset($f_id);
            }
            $inputs = $y_temp;
            unset($y_temp, $input);
        }
    }

    /*
    * Format additional field inputs
    *
    * Since 2.0
    *
    * @param $f_inputs array
    * @param $f_values array
    * @param $args array
    */
    public static function format_f_inputs( &$f_inputs, &$f_values, $args ) {
        if ( ! $f_inputs ) {
            return;
        }
        foreach ( $f_inputs as $f_id => $f ) {
            $temp = array();
            foreach ( $f as $input ) {
                if ( is_array( $input ) ){
                    $temp[$input['item_id']] = $input['meta_value'];

                    foreach ( $args['ids'] as $d ) {
                        if ( ! isset( $f_values[ $d ][ $input['item_id'] ] ) ) {
                            $f_values[$d][$input['item_id']] = 0;
                        }

                        unset($d);
                    }
                } else {
                    $temp[] = $input;
                }
                unset($input);
            }

            $f_inputs[$f_id] = apply_filters('frm_graph_value', $temp, $args['fields'][$f_id], $args);

            unset($temp, $input, $f);
        }
    }

    /*
    * Get values for user ID graph
    *
    * Since 2.0
    *
    * @param $values - values array
    * @param $labels - labels array
    * @param $tooltips - tooltips array
    * @param $pie - boolean for pie graph
    * @param $temp_values - temporary values array
    * @param $field - field object
    */
    public static function get_user_id_values( &$values, &$labels, &$tooltips, &$pie, $temp_values, $field ) {
        global $wpdb;

        // Get form options
        $form = $wpdb->get_row( $wpdb->prepare('SELECT * FROM '. $wpdb->prefix .'frm_forms WHERE id = %d', $field->form_id) );
        $form_options = maybe_unserialize( $form->options );

        // Remove deleted users from values and show display name instead of user ID number
        foreach ( $temp_values as $user_id => $count ) {
            $user_info = get_userdata( $user_id );
            if ( ! $user_info ) {
                unset( $temp_values[$user_id] );
                continue;
            }
            $labels[] = ($user_info) ? $user_info->display_name : __('Deleted User', 'formidable');
            $values[] = $count;
        }

        // If only one response per user, do a pie chart of users who have submitted the form
        if ( isset( $form_options['single_entry'] ) && $form_options['single_entry'] && isset( $form_options['single_entry_type'] ) && $form_options['single_entry_type'] == 'user' ) {

            // Get number of users on site
            $total_users = count( get_users() );

            // Get number of users that have completed entries
            $id_count = count( $values );
            
            // Get the difference
            $not_completed = (int) $total_users - (int) $id_count;
            $labels = array( __('Completed', 'formidable'), __('Not Completed', 'formidable') );
            $temp_values = array( $id_count, $not_completed );
            $pie = true;

        } else {
            if ( count( $labels ) < 10 ) {
                $pie = true;
            }
        }
        $values = $temp_values;
    }

    /*
    * Get final x-axis values
    * TODO: Clean this up, try to think of a cleaner way to get these values
    *
    * Since 2.0
    *
    * @param $values - values array
    * @param $f_values - array of additional field values
    * @param $labels - labels array
    * @param $tooltips - tooltips array
    * @param $inputs - inputs array
    * @param $x_inputs - x inputs array
    * @param $f_inputs - f inputs array
    * @param $args - arguments array
    */
    public static function get_final_x_axis_values( &$values, &$f_values, &$labels, &$tooltips, $inputs, $x_inputs, $f_inputs, $args ){
        if ( ! isset( $x_inputs ) || ! $x_inputs ) {
            return;
        }
        $calc_array = array();

        //TODO: CHECK IF other option works with x axis
        foreach ( $inputs as $entry_id => $in ) {
            $entry_id = (int) $entry_id;
            if ( ! isset( $values[$entry_id] ) ) {
                $values[$entry_id] = 0;
            }

            $labels[$entry_id] = ( isset( $x_inputs[$entry_id] ) ) ? $x_inputs[$entry_id] : '';

            if ( ! isset( $calc_array[ $entry_id ] ) ) {
                $calc_array[$entry_id] = array('count' => 0);
            }

            if ( $args['data_type'] == 'total' || $args['data_type'] == 'average' ) {
                $values[$entry_id] += (float) $in;
                $calc_array[$entry_id]['total'] = $values[$entry_id];
                $calc_array[$entry_id]['count']++;
            } else {
                $values[$entry_id]++;
            }

            unset($entry_id);
            unset($in);
        }

        //TODO: Does this even work?
        if ( $args['data_type'] == 'average' ) {
            foreach ( $calc_array as $entry_id => $calc ) {
                $values[$entry_id] = ($calc['total'] / $calc['count']);
                unset($entry_id);
                unset($calc);
            }
        }

        $calc_array = array();
        foreach ( $f_inputs as $f_id => $f ) {
            if ( ! isset( $calc_array[$f_id] ) ) {
                $calc_array[$f_id] = array();
            }

            foreach ( $f as $entry_id => $in ) {
                $entry_id = (int) $entry_id;
                if ( ! isset( $labels[ $entry_id ] ) ) {
                    $labels[$entry_id] = (isset($x_inputs[$entry_id])) ? $x_inputs[$entry_id] : '';
                    $values[$entry_id] = 0;
                }

                if ( ! isset( $calc_array[ $f_id ][ $entry_id ] ) ) {
                    $calc_array[$f_id][$entry_id] = array('count' => 0);
                }

                if ( ! isset( $f_values[ $f_id ][ $entry_id ] ) ) {
                    $f_values[$f_id][$entry_id] = 0;
                }

                if ( $args['data_type'] == 'total' || $args['data_type'] == 'average' ) {
                    $f_values[$f_id][$entry_id] += (float) $in;
                    $calc_array[$f_id][$entry_id]['total'] = $f_values[$f_id][$entry_id];
                    $calc_array[$f_id][$entry_id]['count']++;
                }else{
                    $f_values[$f_id][$entry_id]++;
                }

                unset($entry_id);
                unset($in);
            }

            unset($f_id);
            unset($f);
        }

        if($args['data_type'] == 'average'){
            foreach($calc_array as $f_id => $calc){
                foreach($calc as $entry_id => $c){
                    $f_values[$f_id][$entry_id] = ($c['total'] / $c['count']);
                    unset($entry_id);
                    unset($c);
                }
                unset($calc);
                unset($f_id);
            }
        }
        unset($calc_array);

        //TODO: Is this duplicate code?
        $used_vals = $calc_array = array();
        foreach($labels as $l_key => $label){
            if(empty($label) and (!empty($start_date) or !empty($end_date))){
                unset($values[$l_key]);
                unset($labels[$l_key]);
                if ( isset($tooltips[$l_key]) ) {
                    unset($tooltips[$l_key]);
                }
                continue;
            }

            if(in_array($args['x_axis'], array('created_at', 'updated_at'))){
                if ( $args['type'] == 'pie' ) {
                    $labels[$l_key] = $label = $inputs[$l_key];
                } else {
                    $labels[$l_key] = $label = date('Y-m-d', strtotime($label));
                }
            }

            if(isset($used_vals[$label])){
                $values[$l_key] += $values[$used_vals[$label]];
                unset($values[$used_vals[$label]]);

                foreach($args['ids'] as $f_id){
                    if ( ! isset($f_values[ $f_id ][ $l_key ]) ) {
                        $f_values[ $f_id ][ $l_key ] = 0;
                    }
                    if ( ! isset( $f_values[ $f_id ][ $used_vals[ $label ] ] ) ) {
                        $f_values[$f_id][$used_vals[$label]] = 0;
                    }

                    $f_values[$f_id][$l_key] += $f_values[$f_id][$used_vals[$label]];
                    unset($f_values[$f_id][$used_vals[$label]]);
                    unset($f_id);
                }

                unset($labels[$used_vals[$label]]);
            }
            $used_vals[$label] = $l_key;

            if ( $args['data_type'] == 'average' ) {
                if ( ! isset( $calc_array[ $label ] ) ) {
                    $calc_array[$label] = 0;
                }
                $calc_array[$label]++;
            }

            unset($label);
            unset($l_key);
        }

        if(!empty($calc_array)){
            foreach($calc_array as $label => $calc){
                if(isset($used_vals[$label])){
                    $values[$used_vals[$label]] = ($values[$used_vals[$label]] / $calc);

                    foreach($args['ids'] as $f_id){
                        $f_values[$f_id][$used_vals[$label]] = ($f_values[$f_id][$used_vals[$label]] / $calc);
                        unset($f_id);
                    }
                }

                unset($label);
                unset($calc);
            }
        }
        unset($used_vals);
    }

    /*
    * Combine dates when using created-at, updated-at, or date field on x-axis
    *
    * Since 2.0
    *
    * @param $combine_dates - boolean, will be true if combining dates
    * @param $values - values array
    * @param $labels - labels array
    * @param $tooltips - tooltips array
    * @param $f_values - array of additional field values
    * @param $args - arguments array
    */
    public static function combine_dates( &$combine_dates, &$values, &$labels, &$tooltips, &$f_values, $args ){
        if ( (isset( $args['x_field']) && $args['x_field'] && $args['x_field']->type == 'date') || in_array( $args['x_axis'], array( 'created_at', 'updated_at' ) ) ) {
            $combine_dates = apply_filters('frm_combine_dates', true, $args['x_field']);
        }
        if ( $combine_dates === false ) {
            return;
        }

        if ( $args['include_zero'] ) {
            $start_timestamp = empty( $args['start_date'] ) ? strtotime( '-1 month') : strtotime( $args['start_date'] );
            $end_timestamp = empty( $args['end_date'] ) ? time() : strtotime( $args['end_date'] );
            $dates_array = array();

            // Get the dates array
            for($e = $start_timestamp; $e <= $end_timestamp; $e += 60*60*24)
                $dates_array[] = date('Y-m-d', $e);

            unset($e);

            // Add the zero count days
            foreach($dates_array as $date_str){
                if ( ! in_array($date_str, $labels) ) {
                    $labels[$date_str] = $date_str;
                    $values[$date_str] = 0;
                    foreach($args['ids'] as $f_id){
                        if ( ! isset( $f_values[ $f_id ][ $date_str ] ) ) {
                            $f_values[$f_id][$date_str] = 0;
                        }
                    }
                }
            }

            unset($dates_array, $start_timestamp, $end_timestamp);
        }

        asort($labels);

        foreach($labels as $l_key => $l){
            if ( ( ( isset( $args['x_field'] ) && $args['x_field'] && $args['x_field']->type == 'date') || in_array( $args['x_axis'], array('created_at', 'updated_at') ) ) && ! $args['group_by'] ) {
                if ( $args['type'] != 'pie' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $l) ) {
                    $frmpro_settings = new FrmProSettings();
                    $labels[$l_key] = FrmProAppHelper::convert_date($l, 'Y-m-d', $frmpro_settings->date_format);
                }
            }
            unset($l_key);
            unset($l);
        }

        $values = FrmProAppHelper::sort_by_array($values, array_keys($labels));
        $tooltips = FrmProAppHelper::sort_by_array($tooltips, array_keys($labels));

        foreach($args['ids'] as $f_id){
            $f_values[$f_id] = FrmProAppHelper::sort_by_array($f_values[$f_id], array_keys($labels));
            $f_values[$f_id] = FrmProAppHelper::reset_keys($f_values[$f_id]);
            ksort($f_values[$f_id]);
            unset($f_id);
        }
    }

    /*
    * Group entries by month or quarter
    *
    * Since 2.0
    *
    * @param $values - values array
    * @param $f_values - array of additional field values
    * @param $labels - labels array
    * @param $tooltips - tooltips array
    * @param $args - arguments array
    */
    public static function graph_by_period( &$values, &$f_values, &$labels, &$tooltips, $args ) {
        if ( ! isset( $args['group_by'] ) || ! in_array( $args['group_by'], array( 'month','quarter' ) ) ) {
            return;
        }

        $labels = FrmProAppHelper::reset_keys( $labels );
        $values = FrmProAppHelper::reset_keys( $values );

        // Loop through labels and change labels to month or quarter
		foreach ( $labels as $key => $label ) {
			if ( $args['group_by'] == 'month' ) {
				$labels[$key] = date( 'F Y', strtotime( $label ) );
			} else if ( $args['group_by'] == 'quarter' ) {
                //Convert date to Y-m-d format
				$label = date( 'Y-m-d', strtotime( $label ) );
				if ( preg_match('/-(01|02|03)-/', $label) ) {
					$labels[$key] = 'Q1 ' . date('Y', strtotime($label));
				} else if ( preg_match('/-(04|05|06)-/', $label) ) {
					$labels[$key] = 'Q2 ' . date('Y', strtotime($label));
				} else if ( preg_match('/-(07|08|09)-/', $label) ) {
					$labels[$key] = 'Q3 ' . date('Y', strtotime($label));
				} else if ( preg_match('/-(10|11|12)-/', $label) ) {
					$labels[$key] = 'Q4 ' . date('Y', strtotime($label));
				}
			}
		}

        // Combine identical labels and values
		$count = count( $labels ) - 1;
		for ( $i=0; $i<$count; $i++ ) {
			if ( $labels[$i] == $labels[$i+1] ) {
				unset($labels[$i]);
				$values[$i+1] = $values[$i] + $values[$i+1];
				unset($values[$i]);

                //Group additional field values
                foreach ( $args['ids'] as $field_id ) {
                    $f_values[$field_id][$i+1] = $f_values[$field_id][$i] + $f_values[$field_id][$i+1];
                    unset( $f_values[$field_id][$i], $field_id );
                }
			}
		}

        // Reset keys for additional field values
        foreach ( $args['ids'] as $field_id ) {
            $f_values[$field_id] = FrmProAppHelper::reset_keys( $f_values[$field_id] );
        }
    }

    /*
    * Get values, labels, and tooltips for graph when multiple fields are graphed and no x axis is set
    *
    * Since 2.0
    *
    * @param $values array
    * @param $labels array
    * @param $tooltips array
    * @param args array
    */
    public static function get_multiple_id_values( &$values, &$labels, &$tooltips, $args ) {
        $type = $args['data_type'] ? $args['data_type'] : 'count';

        // Set up arguments for stats shortcode
        $stats_args = array( 'type' => $type );
        if ( $args['start_date'] ) {
            if ( $args['end_date'] ) {
                $stats_args[] = $args['start_date'] . '<created_at<' . $args['end_date'];
            } else {
                $stats_args[] = 'created_at>' . $args['start_date'];
            }
        }
        if ( $args['user_id'] ) {
            $stats_args['user_id'] = $args['user_id'];
        }
        if ( $args['entry_ids'] ) {
            // frm-stats only accepts one entry ID at the moment
            $stats_args['entry_id'] = reset( $args['entry_ids'] );
        }

        //Get count/total for each field
        $count = 0;
        foreach ( $args['fields'] as $f_id => $f_data ) {
            $stats_args['id'] = $f_id;
            $values[] = self::stats_shortcode( $stats_args );
            $labels[] = isset( $args['tooltip_label'][$count] ) ? $args['tooltip_label'][$count] : $f_data->name;
			$count++;
            unset( $f_id, $f_data );
        }

        //Make tooltips match labels
        $tooltips = $labels;
    }

    static function convert_to_google($rows, $cols, $options, $type) {
        $gcontent = '';
        $num_col = array();

        if(!empty($cols)){
            $pos = 0;
            foreach ( (array) $cols as $col_name => $col ) {
                $gcontent .= "data.addColumn('". $col['type'] ."','". addslashes($col_name) ."');";

                // save the number cols so we can make sure they are formatted correctly below
                if ( 'number' == $col['type'] ) {
                    $num_col[] = $pos;
                }
                $pos++;

                unset($col_name, $col);
            }
        }

        if(!empty($rows)){
            if($type == 'table'){
                $last = end($rows);
                $count = $last[0]+1;
                $gcontent .= "data.addRows($count);\n";

                foreach($rows as $row){
                    $gcontent .= "data.setCell(". implode(',', $row). ");"; //data.setCell(0, 0, 'Mike');
                    unset($row);
                }
            }else{
                $row_one = reset($rows);
                if ( isset($row_one['tooltip']) ) {
                    $gcontent .= "data.addColumn({type:'string',role:'tooltip'});";

                    // remove the tooltip key from the array
                    foreach ( $rows as $row_k => $row ) {
                        $tooltip = $row['tooltip'];
                        unset($rows[$row_k]['tooltip']);
                        $rows[$row_k][] = $tooltip;
                        unset($tooltip, $row_k, $row);
                    }
                }

                // make sure number fields are displayed as numbers
                if ( ! empty($num_col) ) {
                    foreach ( $rows as $row_k => $row ) {
                        foreach ( $num_col as $k ) {
                            $rows[$row_k][$k] = (float) $rows[$row_k][$k];
                            unset($k);
                        }

                        unset($row_k, $row);
                    }
                }

                $gcontent .= "data.addRows(". json_encode($rows) .");\n";
            }
        }

        if(!empty($options))
            $gcontent .= "var options=". json_encode($options) ."\n";

        return compact('gcontent', 'type');
    }

    static function get_daily_entries($form, $opts=array(), $type="DATE"){
        global $wpdb;

        $options = array();
        if(isset($opts['colors']))
            $options['colors'] = explode(',', $opts['colors']);

        if(isset($opts['bg_color']))
            $options['backgroundColor'] = $opts['bg_color'];

        $type = strtoupper($type);

        //Chart for Entries Submitted
        if($type == 'HOUR'){
            $start_timestamp = strtotime('-48 hours');
            $end_timestamp = time();
            $title =  __('Hourly Entries', 'formidable');
        }else if($type == 'MONTH'){
            $start_timestamp = strtotime('-1 year');
            $end_timestamp = strtotime( '+1 month');
            $title =  __('Monthly Entries', 'formidable');
        }else if($type == 'YEAR'){
            $start_timestamp = strtotime('-10 years');
            $end_timestamp = time();
            $title =  __('Yearly Entries', 'formidable');
        }else{
            $start_timestamp = strtotime('-1 month');
            $end_timestamp = time();
            $title =  __('Daily Entries', 'formidable');
        }

        if($type == 'HOUR'){
            $query = $wpdb->prepare('SELECT en.created_at as endate,COUNT(*) as encount FROM '. $wpdb->prefix .'frm_items en WHERE en.created_at >= %s AND en.form_id=%d AND en.is_draft=%d GROUP BY endate', date('Y-n-j H', $start_timestamp) .':00:00', $form->id, 0);
        }else{
            $query = $wpdb->prepare('SELECT DATE(en.created_at) as endate,COUNT(*) as encount FROM '. $wpdb->prefix .'frm_items en WHERE en.created_at >= %s AND en.form_id = %d AND en.is_draft = %d GROUP BY '. $type.'(en.created_at)', date('Y-n-j', $start_timestamp) .' 00:00:00', $form->id, 0);
        }

        $entries_array = $wpdb->get_results($query);

        $temp_array = $counts_array = $dates_array = array();

        // Refactor Array for use later on
        foreach($entries_array as $e){
            $e_key = $e->endate;
            if($type == 'HOUR')
                $e_key = date('Y-m-d H', strtotime($e->endate)) .':00:00';
            else if($type == 'MONTH')
                $e_key = date('Y-m', strtotime($e->endate)) .'-01';
            else if($type == 'YEAR')
                $e_key = date('Y', strtotime($e->endate)) .'-01-01';
            $temp_array[$e_key] = $e->encount;
        }

        // Get the dates array
        if($type == 'HOUR'){
            for($e = $start_timestamp; $e <= $end_timestamp; $e += 60*60){
                if ( ! in_array(date('Y-m-d H', $e) .':00:00' , $dates_array) ) {
                    $dates_array[] = date('Y-m-d H', $e) .':00:00';
                }
            }

            $date_format = get_option('time_format');
        }else if($type == 'MONTH'){
            for($e = $start_timestamp; $e <= $end_timestamp; $e += 60*60*24*25){
                if ( ! in_array(date('Y-m', $e) .'-01', $dates_array) ) {
                    $dates_array[] = date('Y-m', $e) .'-01';
                }
            }

            $date_format = 'F Y';
        }else if($type == 'YEAR'){
            for($e = $start_timestamp; $e <= $end_timestamp; $e += 60*60*24*364){
                if ( ! in_array( date('Y', $e) .'-01-01', $dates_array ) ) {
                    $dates_array[] = date('Y', $e) .'-01-01';
                }
            }

            $date_format = 'Y';
        }else{
            for($e = $start_timestamp; $e <= $end_timestamp; $e += 60*60*24)
                $dates_array[] = date("Y-m-d", $e);

            $date_format = get_option('date_format');
        }

		if ( empty($dates_array) ) {
            return;
		}

        // Make sure counts array is in order and includes zero click days
        foreach($dates_array as $date_str){
          if(isset($temp_array[$date_str]))
              $counts_array[$date_str] = $temp_array[$date_str];
          else
              $counts_array[$date_str] = 0;
        }

        $rows = array();
        $max = 3;
        foreach ($counts_array as $date => $count){
            $rows[] = array( date_i18n($date_format, strtotime($date)), (int) $count );
            if ( (int) $count > $max ) {
                $max = $count + 1;
            }
            unset($date);
            unset($count);
        }

        $options['title'] = $title;
        $options['legend'] = 'none';
        $cols = array('xaxis' => array('type' => 'string'), __('Count', 'formidable') => array('type' => 'number'));

        $options['vAxis'] = array('maxValue' => $max, 'minValue' => 0);
        $options['hAxis'] = array('slantedText' => true, 'slantedTextAngle' => 20);

        $height = 400;
        $width = '100%';

        $options['height'] = $height;
        $options['width'] = $width;

        $graph = self::convert_to_google($rows, $cols, $options, 'line');

        $html = $js = $js_content2 = '';

        global $frm_google_chart;
        $js_content = '<script type="text/javascript">';
        if ( ! $frm_google_chart ) {
            $js_content = '<script type="text/javascript" src="https://www.google.com/jsapi"></script>';
            $js_content .= '<script type="text/javascript">';
            $js_content .= "google.load('visualization','1.0',{'packages':['corechart']});\n";
            $frm_google_chart = true;
        }

        $this_id = $form->id .'_'. strtolower($type);
        $html .= '<div id="chart_'. $this_id .'" style="height:'. $height .';width:'. $width .'"></div>';
        $js_content2 .= 'google.setOnLoadCallback(get_data_'. $this_id .');'."\n";
        $js_content2 .= 'function get_data_'. $this_id .'(){var data=new google.visualization.DataTable();';
        $js_content2 .= $graph['gcontent'];
        $js_content2 .= 'var chart=new google.visualization.'. ucfirst($graph['type']) ."Chart(document.getElementById('chart_". $this_id ."'));chart.draw(data, options);}";


        $js_content .= $js . $js_content2;
        $js_content .= '</script>';

        return $js_content . $html;
    }

    public static function graph_shortcode($atts){

        $defaults = array(
            'id' => false,
            'id2' => false,
            'id3' => false,
            'id4' => false,
            'ids' => array(),
            'include_js' => true,
            'colors' => '',
            'grid_color' => '#CCC',
            'is3d' => false,
            'height' => 400,
            'width' => 400,
            'truncate_label' => 7,
            'bg_color' => '#FFFFFF',
            'truncate' => 40,
            'response_count' => 10,
            'user_id' => false,
            'entry_id' => false,
            'title'=> '',
            'type' => 'default',
            'x_axis' => false,
            'data_type' => 'count',
            'limit' => '',
            'show_key' => false,
            'min' => '',
            'max' => '',
            'y_title' => '',
            'x_title' => '',
            'include_zero' => false,
            'field' => false,
            'title_size' => '',
            'title_font' => '',
            'tooltip_label' => '',
            'start_date' => '',
            'end_date' => '',
            'x_start' => '',
            'x_end' => '',
            'group_by' => '',
            'x_order' => 'default',
            'atts'  => false,
        );

        // TODO: Remove limit from docs, add x_order='desc' and x_order='field_options'
        // Remove id from docs. Just use ids to simplify.
        // Remove either x start or start_date from docs
        // Remove either x_end or end_date from docs
        // Make sure x_order is set up to work with abc

        // If no id, stop now
        if ( ! $atts || ! $atts['id'] ) {
            echo __( 'You must include a field id or key in your graph shortcode.', 'formidable' );
            return;
        }

        if ( isset( $atts['type'] ) && $atts['type'] == 'geo'){
            $defaults['truncate_label'] = 100;
            $defaults['width'] = 600;
        }

        // Set up array for filtering fields
        // TODO: Ask about simpler way
        $temp_atts = $atts;
		foreach ( $defaults as $unset => $val ) {
            unset( $temp_atts[$unset], $unset, $val );
        }
        foreach ( $temp_atts as $unset => $val ) {
            unset( $atts[$unset] );
            $atts['atts'][$unset] = $val;
            unset( $unset, $val );
        }

        // User's values should override default values
        $atts = array_merge( $defaults, $atts );

        global $frm_google_chart, $wpdb;
        $html = $js = $js_content2 = '';

        $include_js = $atts['include_js'];
        unset( $atts['include_js'] );

        // Reverse compatibility for id2, id3, and id4
        if ( ! $atts['ids'] && ( $atts['id2'] || $atts['id3'] || $atts['id4'] ) ) {
            _deprecated_argument( __FUNCTION__, '1.07.05', __( 'id2, id3, and id4 are deprecated. Please use ids instead.', 'formidable' ) );
            $atts['ids'] = array( $atts['id2'], $atts['id3'], $atts['id4'] );
            $atts['ids'] = implode(',', $atts['ids']);
            unset( $atts['id2'], $atts['id3'], $atts['id4'] );
        }

        //x_start and start_date do the same thing
        // Reverse compatibility for x_start
        if ( $atts['start_date'] || $atts['x_start'] ) {
            if ( $atts['x_start'] ) {
                $atts['start_date'] = $atts['x_start'];
                unset( $atts['x_start'] );
            }
            $atts['start_date'] = FrmAppHelper::replace_quotes( $atts['start_date'] );
        }

        //x_end and end_date do the same thing
        // Reverse compatibility for x_end
        if ( $atts['end_date'] || $atts['x_end'] ) {
            if ( $atts['x_end'] ) {
                $atts['end_date'] = $atts['x_end'];
                unset( $atts['x_end'] );
            }
            $atts['end_date'] = FrmAppHelper::replace_quotes( $atts['end_date'] );
        }

        // Reverse compatibility for x_order=0
        if ( ! $atts['x_order'] ) {
            $atts['x_order'] = 'field_opts';
        }

        // Limit and response_count do the same thing?
        // Reverse compatibility for response_count
        if ( $atts['limit'] ) {
            $atts['x_order'] = 'desc';
        } if ( ! $atts['limit'] ) {
            $atts['limit'] = $atts['response_count'];
        }

        $atts['user_id'] = FrmAppHelper::get_user_id_param( $atts['user_id'] );

        if ( $atts['entry_id'] ) {
			$atts['entry_id'] = explode(',', $atts['entry_id']);

        	//make sure all values are numeric
            //TODO: Make this work with entry keys
        	$atts['entry_id'] = array_filter( $atts['entry_id'], 'is_numeric' );
        	if ( empty($atts['entry_id']) ) {
            	// don't continue if there are no entry ids
            	return;
        	}
        	$atts['entry_id'] = implode(',', $atts['entry_id']);
		}
        // Switch to entry_ids for easier reference
        $atts['entry_ids'] = $atts['entry_id'];
        unset( $atts['entry_id'] );

		//Convert $tooltip_label to array
		if ( $atts['tooltip_label'] ) {
		    $atts['tooltip_label'] = explode( ',' , $atts['tooltip_label'] );
		}

        // This will only be an object when coming from show()
        if ( is_object( $atts['field'] ) ){
            $fields = array($atts['field']);

        // If creating multiple graphs with one shortcode
        } else {
            $atts['id'] = explode( ',', $atts['id'] );

            foreach ( $atts['id'] as $key => $id ) {
                //If using field keys, retrieve the field IDs
        		if ( ! is_numeric( $id ) ) {
        			$atts['id'][$key] = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}frm_fields WHERE field_key=%s", $id ) );
        		}
                unset( $key, $id );
            }
            //make sure all field IDs are numeric - TODO: ask Steph if this is redundant
            $atts['id'] = array_filter( $atts['id'], 'is_numeric' );
            if ( empty($atts['id']) ) {
                // don't continue if there is nothing to graph
                return;
            }
            $atts['id'] = implode(',', $atts['id']);

            $fields = FrmField::getAll('fi.id in ('. $atts['id'] .')');

            // No longer needed
            unset( $atts['id']);
        }

        if ( ! empty( $atts['colors'] ) ) {
            $atts['colors'] = explode( ',', $atts['colors'] );
        }

        $js_content = '<script type="text/javascript">';
        if ( $include_js && ! $frm_google_chart ) {
            $js_content = '<script type="text/javascript" src="https://www.google.com/jsapi"></script>';
            $js_content .= '<script type="text/javascript">';
            $js_content .= "google.load('visualization', '1.0', {'packages':['". ( $atts['type'] == 'geo' ? 'geochart' : 'corechart')."']});\n";
            if ( $atts['type'] != 'geo')
                $frm_google_chart = true;
        }else if ( $atts['type'] == 'geo'){
            $js_content .= "google.load('visualization', '1', {'packages': ['geochart']});\n";
        }

        global $frm_gr_count;
        if ( ! $frm_gr_count ) {
            $frm_gr_count = 0;
        }

        foreach ( $fields as $field ){
            $data = self::get_google_graph( $field, $atts );

			if ( empty( $data ) ) {
				$html .= '<div class="frm_no_data_graph">'. __('No Data', 'formidable') .'</div>';
				$html = apply_filters('frm_no_data_graph', $html);
				continue;
			}

            $frm_gr_count++;
            $this_id = $field->id .'_'. $frm_gr_count;
            $html .= '<div id="chart_' . $this_id . '" style="height:'. $atts['height'] .';width:' . $atts['width'] . '"></div>';
            $js_content2 .= 'google.setOnLoadCallback(get_data_'. $this_id .');'."\n";
            $js_content2 .= 'function get_data_'. $this_id .'(){var data=new google.visualization.DataTable();';
            $js_content2 .= $data['gcontent'];
            $js_content2 .= 'var chart=new google.visualization.'. ucfirst($data['type']) ."Chart(document.getElementById('chart_". $this_id ."'));chart.draw(data, options);}";
        }

        $js_content .= $js . $js_content2;
        $js_content .= '</script>';

        return $js_content . $html;
    }


    /**
	 * Returns stats requested through the [frm-stats] shortcode
	 *
	 * @param array $atts
	 */
    public static function stats_shortcode($atts){
        $defaults = array(
            'id' => false, //the ID of the field to show stats for
            'type' => 'total', //total, count, average, median, deviation, star, minimum, maximum, unique
            'user_id' => false, //limit the stat to a specific user id or "current"
            'value' => false, //only count entries with a specific value
            'round' => 100, //how many digits to round to
            'limit' => '', //limit the number of entries used in this calculation
            'drafts' => false, //don't include drafts by default
            //any other field ID in the form => the value it should be equal to
            //'entry_id' => show only for a specific entry ie if you want to show a star rating for a single entry
            //'thousands_sep' => set thousands separator

        );

        $sc_atts = shortcode_atts($defaults, $atts);
        // Combine arrays - DO NOT use array_merge here because numeric keys are renumbered
        $atts = $atts + $sc_atts;

        if ( ! $atts['id'] ) {
            return;
        }

        $atts['user_id'] = FrmAppHelper::get_user_id_param($atts['user_id']);

        $new_atts = $atts;
        foreach ( $defaults as $unset => $val ) {
            unset($new_atts[$unset]);
        }

        return FrmProFieldsHelper::get_field_stats(
            $atts['id'], $atts['type'], $atts['user_id'], $atts['value'],
            $atts['round'], $atts['limit'], $new_atts, $atts['drafts']
        );
    }

}
