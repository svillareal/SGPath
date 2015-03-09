<?php

class FrmListEntries extends WP_Widget {

	function __construct() {
		$widget_ops = array( 'description' => __( "Display a list of Formidable entries", 'formidable') );
		$this->WP_Widget('frm_list_items', __('Formidable Entries List', 'formidable'), $widget_ops);
	}

	function widget( $args, $instance ) {
        global $wpdb;

        $display = FrmProDisplay::getOne($instance['display_id'], false, true);

		$title = apply_filters('widget_title', (empty($instance['title']) and $display) ? $display->post_title : $instance['title']);
        $limit = empty($instance['limit']) ? ' LIMIT 100' : " LIMIT {$instance['limit']}";
        $post_id = ( ! $display || empty($display->frm_post_id)) ? $instance['post_id'] : $display->frm_post_id;
        $page_url = get_permalink($post_id);

        $order_by = '';
        $cat_field = false;

        if ( $display && is_numeric($display->frm_form_id) && ! empty($display->frm_form_id) ) {

				//Set up order for Entries List Widget
                if ( isset($display->frm_order_by) && ! empty($display->frm_order_by) ) {
					//Get only the first order field and order
					$order_field = reset($display->frm_order_by);
					$order = reset($display->frm_order);

                   	if ( $order_field == 'rand' ) {//If random is set, set the order to random
					    $order_by = ' RAND()';
                    } else if ( is_numeric($order_field) ) {//If ordering by a field

						//Get all post IDs for this form
						$posts = $wpdb->get_results($wpdb->prepare("SELECT id, post_id FROM {$wpdb->prefix}frm_items WHERE form_id=%d and post_id>%d AND is_draft=%d", $display->frm_form_id, 1, 0));
			            $linked_posts = array();
			           	foreach ( $posts as $post_meta ) {
			            	$linked_posts[$post_meta->post_id] = $post_meta->id;
			            }

						//Get all field information
						$o_field = FrmField::getOne($order_field);
                        $query = '';

						//create query with ordered values
						if ( isset($o_field->field_options['post_field']) and $o_field->field_options['post_field'] ) { //if field is some type of post field
							if ( $o_field->field_options['post_field'] == 'post_custom' && ! empty($linked_posts) ) { //if field is custom field
								$query = "SELECT m.id FROM {$wpdb->prefix}frm_items m INNER JOIN {$wpdb->postmeta} pm ON pm.post_id=m.post_id AND pm.meta_key='". $o_field->field_options['custom_field']."' WHERE pm.post_id in (". implode(',', array_keys($linked_posts)).") ORDER BY CASE when pm.meta_value IS NULL THEN 1 ELSE 0 END, pm.meta_value {$order}";
							} else if ( $o_field->field_options['post_field'] != 'post_category' && ! empty($linked_posts) ) {//if field is a non-category post field
								$query = "SELECT m.id FROM {$wpdb->prefix}frm_items m INNER JOIN {$wpdb->posts} p ON p.ID=m.post_id WHERE p.ID in (". implode(',', array_keys($linked_posts)).") ORDER BY CASE p.".$o_field->field_options['post_field']." WHEN '' THEN 1 ELSE 0 END, p.".$o_field->field_options['post_field']." {$order}";
							}
						} else {
						    //if field is a normal, non-post field
							$query = "SELECT m.id FROM {$wpdb->prefix}frm_items m INNER JOIN {$wpdb->prefix}frm_item_metas em ON em.item_id=m.id WHERE em.field_id=$o_field->id ORDER BY CASE when em.meta_value IS NULL THEN 1 ELSE 0 END, em.meta_value".($o_field->type == 'number' ? ' +0 ' : '')." {$order}";
						}

						//Get ordered values
						if ( ! empty($query) ) {
						    $metas = $wpdb->get_results($query);
						} else {
						    $metas = false;
						}
						unset($query);

                        if ( ! empty($metas) ) {
							$desc_order = ' DESC';
                            foreach ($metas as $meta)
                                $order_by .= $wpdb->prepare('it.id=%d'. $desc_order.', ', $meta->id);

                            $order_by = rtrim($order_by, ', ');
                        } else {
                            $order_by .= 'it.created_at '. $order;
						}
                    } else if ( !empty($order_field) ) { //If ordering by created_at or updated_at
						$order_by = 'it.'.$order_field.' '.$order;
					}

					if ( !empty($order_by) ) {
                        $order_by = ' ORDER BY '. $order_by;
                    }
                }

                if ( isset($instance['cat_list']) && (int) $instance['cat_list'] == 1 && is_numeric($instance['cat_id']) ) {
                    if ($cat_field = FrmField::getOne($instance['cat_id']))
                        $categories = maybe_unserialize($cat_field->options);
                }

        }

		echo $args['before_widget'];
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

        echo '<ul id="frm_entry_list'. ( $display ? $display->frm_form_id : '' ) .'">'. "\n";

		//if Listing entries by category
        if ( isset($instance['cat_list']) && (int) $instance['cat_list'] == 1 && isset($categories) && is_array($categories) ) {
            foreach ($categories as $cat_order => $cat){
                if ($cat == '') continue;
                echo '<li>';

                if ( isset($instance['cat_name']) && (int) $instance['cat_name'] == 1 && $cat_field ) {
                    echo '<a href="'. add_query_arg(array('frm_cat' => $cat_field->field_key, 'frm_cat_id' => $cat_order), $page_url) .'">';
                }

                echo $cat;

                if ( isset($instance['cat_count']) && (int) $instance['cat_count'] == 1 ) {
                    echo ' ('. FrmProFieldsHelper::get_field_stats($instance['cat_id'], 'count', false, $cat) .')';
                }

                if ( isset($instance['cat_name']) && (int) $instance['cat_name'] == 1 ) {
                    echo '</a>';
                }else{
                    $entry_ids = FrmEntryMeta::getEntryIds("meta_value LIKE '%$cat%' and fi.id=". $instance['cat_id']);
                    $items = false;
                    if ($entry_ids)
                        $items = FrmEntry::getAll("it.id in (". implode(',', $entry_ids) .") and it.form_id =". (int) $display->frm_form_id, $order_by, $limit);

                    if ($items){
                        echo '<ul>';
                        foreach ($items as $item){
                            $url_id = $display->frm_type == 'id' ? $item->id : $item->item_key;
                            $current = ( isset($_GET[$display->frm_param]) && $_GET[$display->frm_param] == $url_id ) ? ' class="current_page"' : '';

                            if($item->post_id)
                                $entry_link = get_permalink($item->post_id);
                            else
                                $entry_link = add_query_arg(array($display->frm_param => $url_id), $page_url);

                            echo '<li'. $current .'><a href="'. $entry_link .'">'. $item->name .'</a></li>'. "\n";
                        }
                        echo '</ul>';
                    }
                }
                echo '</li>';
             }
         }else{ // if not listing entries by category
             if($display)
                 $items = FrmEntry::getAll(array('it.form_id' => $display->frm_form_id, 'is_draft' => '0'), $order_by, $limit);
             else
                $items = array();

             foreach ($items as $item){
                  $url_id = $display->frm_type == 'id' ? $item->id : $item->item_key;

                  $current = (isset($_GET[$display->frm_param]) and $_GET[$display->frm_param] == $url_id) ? ' class="current_page"' : '';

                  echo "<li". $current ."><a href='".add_query_arg(array($display->frm_param => $url_id), $page_url)."'>". $item->name ."</a></li>\n";
              }
         }

         echo "</ul>\n";

	     echo $args['after_widget'];
	}

	function update( $new_instance, $old_instance ) {
		return $new_instance;
	}

	function form( $instance ) {
        $pages = get_posts( array('post_type' => 'page', 'post_status' => 'publish', 'numberposts' => 999, 'order_by' => 'post_title', 'order' => 'ASC'));

        $displays = FrmProDisplay::getAll(array('meta_key' => 'frm_show_count', 'meta_value' => 'dynamic'));

        //Defaults
		$instance = wp_parse_args( (array) $instance, array('title' => false, 'display_id' => false, 'post_id' => false, 'title_id' => false, 'cat_list' => false, 'cat_name' => false, 'cat_count' => false, 'cat_id' => false, 'limit' => false) );

		if ($instance['display_id']){
		    $selected_display = FrmProDisplay::getOne($instance['display_id']);
		    if ( $selected_display ) {
		        $selected_form_id = get_post_meta($selected_display->ID, 'frm_form_id', true);

		        $title_opts = FrmField::getAll("fi.form_id=". (int) $selected_form_id ." and type not in ('". implode("','", FrmFieldsHelper::no_save_fields() ) ."')", 'field_order');
		        $instance['display_id'] = $selected_display->ID;
		    }
		}
?>
	<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'formidable') ?>:</label>
	<input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr( stripslashes($instance['title']) ); ?>" /></p>

	<p><label for="<?php echo $this->get_field_id('display_id'); ?>"><?php _e('Use Settings from View', 'formidable') ?>:</label>
	    <select name="<?php echo $this->get_field_name('display_id'); ?>" id="<?php echo $this->get_field_id('display_id'); ?>" class="widefat" onchange="frm_get_display_fields(this.value)">
	        <option value=""> </option>
            <?php foreach ($displays as $display)
                echo '<option value="'. $display->ID .'" '. selected( $instance['display_id'], $display->ID ) .'>' . $display->post_title . '</option>';
            ?>
        </select>
	</p>
	<p class="description"><?php _e('Views with a "Both (Dynamic)" format will show here.', 'formidable') ?></p>

	<p><label for="<?php echo $this->get_field_id('post_id'); ?>"><?php _e('Page if not specified in View settings', 'formidable') ?>:</label>
        <select name="<?php echo $this->get_field_name('post_id'); ?>" id="<?php echo $this->get_field_id('post_id'); ?>" class="widefat">
	        <option value=""> </option>
            <?php foreach ($pages as $page)
                echo '<option value="'. $page->ID .'" '. selected( $instance['post_id'], $page->ID ) .'>' . $page->post_title . '</option>';
            ?>
        </select>
    </p>

    <p><label for="<?php echo $this->get_field_id('title_id'); ?>"><?php _e('Title Field', 'formidable') ?>:</label>
        <select name="<?php echo $this->get_field_name('title_id'); ?>" id="<?php echo $this->get_field_id('title_id'); ?>" class="widefat">
	        <option value=""> </option>
            <?php
            if ( isset($title_opts) && $title_opts ) {
                foreach ( $title_opts as $title_opt ) {
                    if ( $title_opt->type != 'checkbox' ) { ?>
                        <option value="<?php echo $title_opt->id ?>" <?php selected( $instance['title_id'], $title_opt->id ) ?>><?php echo $title_opt->name ?></option>
                        <?php
                    }
                }
            }
            ?>
        </select>
	</p>

    <p><label for="<?php echo $this->get_field_id('cat_list'); ?>"><input class="checkbox" type="checkbox" <?php checked($instance['cat_list'], true) ?> id="<?php echo $this->get_field_id('cat_list'); ?>" name="<?php echo $this->get_field_name('cat_list'); ?>" value="1" onclick="frm_toggle_cat_opt(this.checked)"/>
	<?php _e('List Entries by Category', 'formidable') ?></label></p>

    <div id="<?php echo $this->get_field_id('hide_cat_opts'); ?>">
    <p><label for="<?php echo $this->get_field_id('cat_id'); ?>"><?php _e('Category Field', 'formidable') ?>:</label>
	    <select name="<?php echo $this->get_field_name('cat_id'); ?>" id="<?php echo $this->get_field_id('cat_id'); ?>" class="widefat">
	        <option value=""> </option>
	        <?php
            if ( isset($title_opts) && $title_opts ) {
                foreach ($title_opts as $title_opt){
                    if(in_array($title_opt->type, array('select', 'radio', 'checkbox')))
                    echo '<option value="'. $title_opt->id .'"'. selected( $instance['cat_id'], $title_opt->id ) . '>' . $title_opt->name . '</option>';
                }
            }
            ?>
        </select>
	</p>

	<p><label for="<?php echo $this->get_field_id('cat_count'); ?>"><input class="checkbox" type="checkbox" <?php checked($instance['cat_count'], true) ?> id="<?php echo $this->get_field_id('cat_count'); ?>" name="<?php echo $this->get_field_name('cat_count'); ?>" value="1" />
	<?php _e('Show Entry Counts', 'formidable') ?></label></p>

	<p><input class="checkbox" type="radio" <?php checked($instance['cat_name'], 1) ?> id="<?php echo $this->get_field_id('cat_name'); ?>" name="<?php echo $this->get_field_name('cat_name'); ?>" value="1" />
	<label for="<?php echo $this->get_field_id('cat_name'); ?>"><?php _e('Show Only Category Name', 'formidable') ?></label><br/>

	<input class="checkbox" type="radio" <?php checked($instance['cat_name'], 0) ?> id="<?php echo $this->get_field_id('cat_name'); ?>" name="<?php echo $this->get_field_name('cat_name'); ?>" value="0" />
	<label for="<?php echo $this->get_field_id('cat_name'); ?>"><?php _e('Show Entries Beneath Categories', 'formidable') ?></label></p>
	</div>

	<p><label for="<?php echo $this->get_field_id('limit'); ?>"><?php _e('Entry Limit (leave blank to list all)', 'formidable') ?>:</label>
	<input type="text" class="widefat" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" value="<?php echo esc_attr( $instance['limit'] ); ?>" /></p>

<script type="text/javascript">
jQuery(document).ready(function($){
var hideCatList = document.getElementById("<?php echo $this->get_field_id('hide_cat_opts') ?>");
hideCatList.style.display = 'none';
if(document.getElementById("<?php echo $this->get_field_id('cat_list'); ?>").checked)
    hideCatList.style.display = '';
});

function frm_toggle_cat_opt(checked){
    if (checked) jQuery("#<?php echo $this->get_field_id('hide_cat_opts') ?>").fadeIn('slow');
    else jQuery("#<?php echo $this->get_field_id('hide_cat_opts') ?>").fadeOut('slow');
}

function frm_get_display_fields(display_id){
    if (display_id != ''){
      jQuery.ajax({ type:"POST", url:"<?php echo admin_url('admin-ajax.php') ?>",
         data:"action=frm_get_cat_opts&display_id="+display_id,
         success:function(msg){jQuery("#<?php echo $this->get_field_id('cat_id'); ?>").html(msg);}
      });
      jQuery.ajax({ type:"POST", url:"<?php echo admin_url('admin-ajax.php') ?>",
           data:"action=frm_get_title_opts&display_id="+display_id,
           success:function(msg){jQuery("#<?php echo $this->get_field_id('title_id'); ?>").html(msg);}
       });
  }
}

</script>

<?php
	}
}
