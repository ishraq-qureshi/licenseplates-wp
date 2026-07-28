<?php
add_action('init', function() {
    $labels = array( 
        'name' => 'Reviews',
        'singular_name' => 'Review',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Review',
        'edit_item' => 'Edit Review',
        'new_item' => 'New Review',
        'view_item' => 'View Review',
        'search_items' => 'Search Reviews',
        'not_found' => 'No Reviews found',
        'not_found_in_trash' => 'No Reviews found in Trash',
        'parent_item_colon' => 'Parent Reviews:',
        'menu_name' => 'Reviews',
    );

    $args = array( 
        'labels' => $labels,
        'hierarchical' => true,
        'description' => 'Reviews',
        'supports' => array('title', 'thumbnail'),
		'taxonomies' => array('state'),
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => 'pyi-review-aggregator',
        'menu_position' => 50,
		'register_meta_box_cb' => 'pyi_review_aggregator_reviews_register_metabox',    
        'show_in_nav_menus' => true,
        'publicly_queryable' => true,
        'exclude_from_search' => false,
        'has_archive' => true,
        'query_var' => true,
        'can_export' => true,
        'rewrite' => true,
        'capability_type' => 'post'
    );

    register_post_type('reviews', $args);
});


add_filter('post_updated_messages', function($messages) {
	$post = get_post();
	$post_type = get_post_type($post);
	$post_type_object = get_post_type_object($post_type);

	$messages['reviews'] = array(
		0  => '',
		1  => 'Reviews updated.',
		2  => 'Custom field updated.',
		3  => 'Custom field deleted.',
		4  => 'Reviews updated.',
		5  => isset($_GET['revision']) ? sprintf('Reviews restored to revision from %s', wp_post_revision_title((int)$_GET['revision'], false)) : false,
		6  => 'Reviews published.',
		7  => 'Reviews saved.',
		8  => 'Reviews submitted.',
		9  => sprintf('Reviews scheduled for: <strong>%1$s</strong>.', date_i18n( 'M j, Y @ G:i', strtotime($post->post_date))),
		10 => 'Reviews draft updated.'
	);

	return $messages;
});

function pyi_review_aggregator_reviews_register_metabox() {
	add_meta_box('pyi_review_aggregator_reviews', 'Review Details', 'pyi_review_aggregator_reviews_content', 'reviews', 'normal', 'default');
}

function pyi_review_aggregator_reviews_content() {
	global $post;
	wp_nonce_field(plugin_basename(__FILE__), 'pyi_review_aggregator_reviews_nonce');
	$postMeta = get_post_meta($post->ID);
	if(isset($postMeta) && is_array($postMeta)) {
		$postMeta = array_combine(array_keys($postMeta), array_column($postMeta, '0'));
		foreach($postMeta as $key => $value) {
			if(strpos($key, 'pyi_review_aggregator_data_') !== false) {
				$actualKey = str_replace('pyi_review_aggregator_data_', '', $key);
				echo smartlogix_get_control('textarea', ucfirst(str_replace('_', ' ', $actualKey)), 'pyi_review_aggregator_reviews_options_'.$actualKey, 'pyi_review_aggregator_reviews_options['.$actualKey.']', ((isset($value))?$value:''));
			}
		}
	}
}

add_action('save_post', function($post_id) {
	if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
	if(isset($_POST['pyi_review_aggregator_reviews_nonce']) && !wp_verify_nonce($_POST['pyi_review_aggregator_reviews_nonce'], plugin_basename( __FILE__ ))) { return; }
	if(isset($_POST['post_type']) && ('reviews' == $_POST['post_type'])) {
		if(!current_user_can('edit_post', $post_id)) { return; }
	} else { return; }
	if(isset($_POST['pyi_review_aggregator_reviews_options']) && is_array($_POST['pyi_review_aggregator_reviews_options'])) {
		foreach($_POST['pyi_review_aggregator_reviews_options'] as $key => $value) {
			update_post_meta($post_id, 'pyi_review_aggregator_data_'.$key, $value);
		}
	}
});
?>