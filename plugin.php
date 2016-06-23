<?php
/**
 * Plugin Name: WP Custom Category Images
 * Plugin URI: https://github.com/tottaz/WP-Custom-Category-Images
 * Description: Category and Taxonomy Image Plugin allow you to add image with category/taxonomy.
 * Version: 1.0.0
 * Author: Torbjorn Zetterlund
 * Author URI: https://torbjornzetterlund.com
 * License: GPLv2
 */


if (!defined('WP_CCI_PLUGIN_URL'))
	define('WP_CCI_PLUGIN_URL', untrailingslashit(plugins_url('', __FILE__)));

define('WP_CCI_IMAGE_PLACEHOLDER', WP_CCI_PLUGIN_URL."/img/placeholder.png");

$options = get_option('wp_cci_options');
$wp_cci_taxonomies = $options['wp_cci_checked_taxonomies'];

if(!empty($wp_cci_taxonomies)){
	
	foreach ($wp_cci_taxonomies as $wp_cci_taxonomy) {
		add_action($wp_cci_taxonomy.'_add_form_fields','addcategoryimage');
		add_action($wp_cci_taxonomy.'_edit_form_fields','editcategoryimage');
		add_filter( 'manage_edit-' . $wp_cci_taxonomy . '_columns', 'wp_cci_taxonomy_columns' );
		add_filter( 'manage_' . $wp_cci_taxonomy . '_custom_column', 'wp_cci_taxonomy_column', 10, 3 );
    }
}

//Function to add category/taxonomy image
function addcategoryimage($taxonomy){ ?>
    <div class="form-field">
	<label for="tag-image">Image</label>
	<input type="text" name="tag-image" id="tag-image" value="" />	
	<p class="description">Click on the text box to add category image.</p>
</div>

<?php wp_cci_script_css(); }


//Function to edit category/taxonomy image
function editcategoryimage($taxonomy){ ?>
<tr class="form-field">
	<th scope="row" valign="top"><label for="tag-image">Image</label></th>
	<td>
	<?php 
	if(get_option('_category_image'.$taxonomy->term_id) != ''){ ?>
		<img src="<?php echo get_option('_category_image'.$taxonomy->term_id); ?>" width="100"  height="100"/>
	<?php	
	}
	?><br />
	<input type="text" name="tag-image" id="tag-image" value="<?php echo get_option('_category_image'.$taxonomy->term_id); ?>" /><p class="description">Click on the text box to add category image.</p>
	</td>
</tr>              
<?php wp_cci_script_css(); }

function wp_cci_script_css(){ ?>
                
<script type="text/javascript" src="<?php echo plugins_url(); ?>/wp-custom-category-images/js/thickbox.js"></script>
<link rel='stylesheet' id='thickbox-css'  href='<?php echo includes_url(); ?>js/thickbox/thickbox.css' type='text/css' media='all' />
<script type="text/javascript">    
    jQuery(document).ready(function() {
	var fileInput = ''; 
	jQuery('#tag-image').live('click',
	function() {
		fileInput = jQuery('#tag-image');
		tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');
		return false;
	}); 
        window.original_send_to_editor = window.send_to_editor;
	window.send_to_editor = function(html) {
		if (fileInput) {
			fileurl = jQuery('img', html).attr('src');
			if (!fileurl) {
				fileurl = jQuery(html).attr('src');
			}
			jQuery(fileInput).val(fileurl);

			tb_remove();
		} else {
			window.original_send_to_editor(html);
		}
	};
    });
   
</script>
<?php }

//edit_$taxonomy
add_action('edit_term','save_taxonomy_image');
add_action('create_term','save_taxonomy_image');

// save our taxonomy image while edit or save term
function save_taxonomy_image($term_id){
    if(isset($_POST['tag-image'])){
        if(isset($_POST['tag-image']))
            update_option('_category_image'.$term_id,$_POST['tag-image'] );
    }
}

// New menu submenu for plugin options in Settings menu
add_action('admin_menu', 'wp_cci_options_menu');
function wp_cci_options_menu() {
	add_options_page('Category Image settings', 'Category Image', 'manage_options', 'aft-options', 'wp_cci_options');
	add_action('admin_init', 'wp_cci_register_settings');
}

// Register plugin settings
function wp_cci_register_settings() {
	register_setting('wp_cci_options', 'wp_cci_options', 'wp_cci_options_validate');
	add_settings_section('wp_cci_settings', 'Category Image settings' , 'wp_cci_section_text', 'aft-options');
	add_settings_field('wp_cci_checked_taxonomies', 'Category Image settings' , 'wp_cci_checked_taxonomies', 'aft-options', 'wp_cci_settings');
}

// Settings section description
function wp_cci_section_text() {
	echo '<p>Please select the categories that you want a category image included</p>';
}

// Included checkboxs
function wp_cci_checked_taxonomies() {
	$options = get_option('wp_cci_options');
	
	$disabled_taxonomies = array('nav_menu', 'link_category', 'post_format');
	foreach (get_taxonomies() as $tax) : if (in_array($tax, $disabled_taxonomies)) continue; ?>
		<input type="checkbox" name="wp_cci_options[wp_cci_checked_taxonomies][<?php echo $tax ?>]" value="<?php echo $tax ?>" <?php checked(isset($options['wp_cci_checked_taxonomies'][$tax])); ?> /> <?php echo $tax ;?><br />
	<?php endforeach;
}

// Validating options
function wp_cci_options_validate($input) {
	return $input;
}

// Change 'insert into post' to 'use this image'
function wp_cci_change_insert_button_text($safe_text, $text) {
    return str_replace("Insert into Post", "Use this image", $text);
}

// Style the image in category list
if ( strpos( $_SERVER['SCRIPT_NAME'], 'edit-tags.php' ) > 0 ) {
	add_action( 'admin_head', 'wp_cci_add_style' );
	add_action('quick_edit_custom_box', 'wp_cci_quick_edit_custom_box', 10, 3);
	add_filter("attribute_escape", "wp_cci_change_insert_button_text", 10, 2);
}

// Plugin option page
function wp_cci_options() {
	if (!current_user_can('manage_options'))
		wp_die('You do not have sufficient permissions to access this page.');
		$options = get_option('wp_cci_options');
	?>
	<div class="wrap">
		
		<h2><?php echo 'Category Image'; ?></h2>
		<form method="post" action="options.php">
			<?php settings_fields('wp_cci_options'); ?>
			<?php do_settings_sections('aft-options'); ?>
			<?php submit_button(); ?>
		</form>
	</div>
<?php
}


/**
 * Thumbnail column added to category admin.
 *
 * @access public
 * @param mixed $columns
 * @return void
 */
function wp_cci_taxonomy_columns( $columns ) {
	$new_columns = array();
	$new_columns['cb'] = $columns['cb'];
	$new_columns['thumb'] = __('Image', 'categories-images');

	unset( $columns['cb'] );

	return array_merge( $new_columns, $columns );
}

/**
 * Thumbnail column value added to category admin.
 *
 * @access public
 * @param mixed $columns
 * @param mixed $column
 * @param mixed $id
 * @return void
 */
function wp_cci_taxonomy_column( $columns, $column, $id ) {
	if ( $column == 'thumb' )
		$columns = '<span><img src="' . wp_cci_taxonomy_image_url($id, 'thumbnail', TRUE) . '" alt="' . __('Thumbnail', 'categories-images') . '" class="wp-post-image" /></span>';
	
	return $columns;
}

// get attachment ID by image url
function wp_cci_get_attachment_id_by_url($image_src) {
    global $wpdb;

    $query = $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid = %s", $image_src);
    $id = $wpdb->get_var($query);
    return (!empty($id)) ? $id : NULL;
}

// get taxonomy image url for the given term_id (Place holder image by default)
function wp_cci_taxonomy_image_url($term_id = NULL, $size = 'full', $return_placeholder = FALSE) {
	if (!$term_id) {
		if (is_category())
			$term_id = get_query_var('cat');
		elseif (is_tag())
			$term_id = get_query_var('tag_id');
		elseif (is_tax()) {
			$current_term = get_term_by('slug', get_query_var('term'), get_query_var('taxonomy'));
			$term_id = $current_term->term_id;
		}
	}
	
    $taxonomy_image_url = get_option('_category_image'.$term_id);

    if(!empty($taxonomy_image_url)) {
	    $attachment_id = wp_cci_get_attachment_id_by_url($taxonomy_image_url);
	    if(!empty($attachment_id)) {
	    	$taxonomy_image_url = wp_get_attachment_image_src($attachment_id, $size);
		    $taxonomy_image_url = $taxonomy_image_url[0];
	    }
	}

    if ($return_placeholder)
		return ($taxonomy_image_url != '') ? $taxonomy_image_url : WP_CCI_IMAGE_PLACEHOLDER;
	else
		return $taxonomy_image_url;
}

//get taxonomy/category image
function get_wp_term_image($term_id){
	
	return get_option('_category_image'.$term_id);	
}

//Alter the quick edit form for category image
function wp_cci_quick_edit_custom_box($column_name, $screen, $name) {
	if ($column_name == 'thumb') 
		echo '<fieldset>
		<div class="thumb inline-edit-col">
			<label>
				<span class="title"><img src="" alt="Thumbnail"/></span>
				<span class="input-text-wrap"><input type="text" name="taxonomy_image" value="" class="tax_list" /></span>
				<span class="input-text-wrap">
					<button class="z_upload_image_button button">' . __('Upload/Add image', 'categories-images') . '</button>
					<button class="z_remove_image_button button">' . __('Remove image', 'categories-images') . '</button>
				</span>
			</label>
		</div>
	</fieldset>';
}

// Style Sheet Details
function wp_cci_add_style() {
	echo '<style type="text/css" media="screen">
		th.column-thumb {width:60px;}
		.form-field img.taxonomy-image {border:1px solid #eee;max-width:300px;max-height:300px;}
		.inline-edit-row fieldset .thumb label span.title {width:48px;height:48px;border:1px solid #eee;display:inline-block;}
		.column-thumb span {width:48px;height:48px;border:1px solid #eee;display:inline-block;}
		.inline-edit-row fieldset .thumb img,.column-thumb img {width:48px;height:48px;}
	</style>';
}

// display taxonomy image for the given term_id
function wp_cci_taxonomy_image($term_id = NULL, $size = 'full', $attr = NULL, $echo = TRUE) {
	if (!$term_id) {
		if (is_category())
			$term_id = get_query_var('cat');
		elseif (is_tag())
			$term_id = get_query_var('tag_id');
		elseif (is_tax()) {
			$current_term = get_term_by('slug', get_query_var('term'), get_query_var('taxonomy'));
			$term_id = $current_term->term_id;
		}
	}
	
    $taxonomy_image_url = get_option('wp_cci_taxonomy_image'.$term_id);
    if(!empty($taxonomy_image_url)) {
	    $attachment_id = wp_cci_get_attachment_id_by_url($taxonomy_image_url);
	    if(!empty($attachment_id))
	    	$taxonomy_image = wp_get_attachment_image($attachment_id, $size, FALSE, $attr);
	    else {
	    	$image_attr = '';
	    	if(is_array($attr)) {
	    		if(!empty($attr['class']))
	    			$image_attr .= ' class="'.$attr['class'].'" ';
	    		if(!empty($attr['alt']))
	    			$image_attr .= ' alt="'.$attr['alt'].'" ';
	    		if(!empty($attr['width']))
	    			$image_attr .= ' width="'.$attr['width'].'" ';
	    		if(!empty($attr['height']))
	    			$image_attr .= ' height="'.$attr['height'].'" ';
	    		if(!empty($attr['title']))
	    			$image_attr .= ' title="'.$attr['title'].'" ';
	    	}
	    	$taxonomy_image = '<img src="'.$taxonomy_image_url.'" '.$image_attr.'/>';
	    }
	}

	if ($echo)
		echo $taxonomy_image;
	else
		return $taxonomy_image;
}
?>