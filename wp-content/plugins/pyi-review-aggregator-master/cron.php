<?php
add_action('wp', function() {
	if (!wp_next_scheduled('pyi_review_aggregator_scheduler_hook')) {
		$settings = get_option('pyi_review_aggregator_settings');
		if(isset($settings['frequency']) && ($settings['frequency'] != '')) {
			wp_schedule_event(time(), $settings['frequency'], 'pyi_review_aggregator_scheduler_hook');
		}
	}
});

add_action('pyi_review_aggregator_scheduler_hook', 'pyi_review_aggregator_scheduler_action' );
function pyi_review_aggregator_scheduler_action() {
	global $wpdb;
	$settings = get_option('pyi_review_aggregator_settings');
	if(isset($settings) && is_array($settings) && isset($settings['base_url']) && ($settings['base_url'] != '')) {
		$reviews = pyi_review_aggregator_import($settings['base_url']);
		if($reviews !== false) {
			foreach($reviews as $review) {
				if(isset($review) && is_object($review)) {
					$existingPost = get_posts(array(
						'numberposts' => 1,
						'post_type' => 'reviews',
						'meta_query' => array(
							array(
								'key' => 'pyi_review_aggregator_data_id',
								'value' => md5(json_encode($review))
							)
						)
					));
					if(isset($existingPost) && (count($existingPost) > 0)) {
						//Feed Already Imported SKIP.
					} else {
						$title = '';
						if(isset($review->reviewTitle) && ($review->reviewTitle != '')) {
							$title = $review->reviewTitle;
						} else {
							$title = ((isset($review->author))?$review->author:'').((isset($review->datePublished))?' - '.$review->datePublished:'');
						}					
					
						$postID = wp_insert_post(array(
							'post_status' => 'publish',
							'post_type' => 'reviews',
							'post_author' => 1,
							'post_title' => $title
						));
						update_post_meta($postID, 'pyi_review_aggregator_data_id', md5(json_encode($review)));
						foreach($review as $key => $value) {	
							update_post_meta($postID, 'pyi_review_aggregator_data_'.$key, $value);						
						}
					}
				}
			}

			 /**
             * Calc and save totals and average rating in options
             */
            $prefix = $wpdb->prefix;
            $posts = $prefix . 'posts';
            $postmeta = $prefix . 'postmeta';
            $sql = "SELECT SUM(meta.meta_value) AS sum, COUNT(*) AS total FROM $posts posts  INNER JOIN $postmeta meta ON  posts.ID=meta.post_id
    WHERE posts.post_type='reviews' AND meta.meta_key='pyi_review_aggregator_data_ratingValue'";
            $row = (array)$wpdb->get_row($sql);

            if (array_key_exists('sum', $row) && array_key_exists('total', $row)) {
                $settings['total_reviews'] = $row['total'];
                $settings['avg_rating'] = $row['total'] >0 ? number_format($row['sum'] / $row['total'], 2) : 0;

                update_option('pyi_review_aggregator_settings', $settings);
            }

		}		
	}
}

function pyi_review_aggregator_reset() {
	$posts = get_posts(array(
		'numberposts' => -1,
		'post_type' => 'reviews',
	));
	if(isset($posts) && is_array($posts)) {
		foreach($posts as $post) {
			wp_delete_post($post->ID, true);
		}
	}
	wp_redirect(admin_url('admin.php?page=pyi-review-aggregator-settings'));
	exit;
}
?>