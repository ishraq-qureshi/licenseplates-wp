<?php
add_action('init', function() {
	if(is_admin() && isset($_GET['page']) && ($_GET['page'] == 'pyi-review-aggregator-settings') && isset($_GET['action']) && ($_GET['action'] == 'reset')) {
		pyi_review_aggregator_reset();
	}
});

add_action('admin_menu', function() {
	register_setting('pyi_review_aggregator_settings', 'pyi_review_aggregator_settings');
	$page_hook = add_submenu_page('pyi-review-aggregator', 'Settings', 'Settings', 'manage_options', 'pyi-review-aggregator-settings', 'pyi_review_aggregator_settings_page');
	if(!empty($page_hook)) {
		add_action('admin_enqueue_scripts', 'pyi_review_aggregator_enqueue_scripts');
		add_filter('screen_layout_columns', 'pyi_review_aggregator_screen_layout_column', 10, 2);
	}
}, 100);

function pyi_review_aggregator_enqueue_scripts($hook) {
    if($hook == 'class-action_page_pyi-review-aggregator-settings') {
        wp_enqueue_script('common');
        wp_enqueue_script('wp-lists');
        wp_enqueue_script('postbox');
    }
}

function pyi_review_aggregator_screen_layout_column($columns, $screen) {
    if($screen == 'pyi-review-aggregator') {
        $columns['pyi-review-aggregator'] = 2;
	}
    return $columns;
}

function pyi_review_aggregator_settings_page() {	
	do_action('add_meta_boxes', 'pyi-review-aggregator', null);
	echo '<div class="wrap">';
        echo '<h2>Settings</h2>';
        settings_errors(); 
		pyi_review_aggregator_scheduler_action();
        echo '<div class="pyi_review_aggregator_settings_wrap">';
            echo '<form id="pyi_review_aggregator_form" method="post" action="options.php">';
				settings_fields('pyi_review_aggregator_settings');
                wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false);
                wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false);
                echo '<div id="poststuff">';
                     echo '<div id="post-body" class="metabox-holder columns-'.((1 == get_current_screen()->get_columns())?'1':'2').'">';
                        echo '<div id="postbox-container-1" class="postbox-container">';							
							echo '<div id="submitpost" class="submitbox">';
								echo '<input type="submit" name="submit" id="submit" class="button button-primary" value="Save" style="display: block;padding: 15px 15px;width: 100%;margin: 0 0 30px;font-size: 32px;line-height: 32px;">';
								echo '<a href="'.admin_url('admin.php?page=pyi-review-aggregator-settings&action=reset').'" class="button button-secondary" style="display: block;padding: 15px 15px;width: 100%;margin: 0 0 30px;font-size: 20px;line-height: 22px;text-align: center;">Remove Synced Data</a>';
							echo '</div>';
							do_meta_boxes('pyi-review-aggregator', 'side', null);
                        echo '</div>';
                        echo '<div id="postbox-container-2" class="postbox-container">';
							do_meta_boxes('pyi-review-aggregator', 'normal', null);
							do_meta_boxes('pyi-review-aggregator', 'advanced', null);
                        echo '</div>';
						echo '<br class="clear">';
                    echo '</div>';
                    echo '<br class="clear">';
                echo '</div>';
            echo '</form>';
        echo '</div>';
    echo '</div>';
}

add_action('add_meta_boxes', function() {
    add_meta_box('pyi_review_aggregator_configuration_meta_box', 'Configuration', 'pyi_review_aggregator_configuration_meta_box_content', 'pyi-review-aggregator', 'normal', 'default');
	add_meta_box('pyi_review_aggregator_template_meta_box', 'Output', 'pyi_review_aggregator_template_meta_box_content', 'pyi-review-aggregator', 'normal', 'default');

});

function pyi_review_aggregator_configuration_meta_box_content() {
	$settings = get_option('pyi_review_aggregator_settings');
	echo smartlogix_get_control('text', 'Review Feed URL', 'pyi_review_aggregator_settings_base_url', 'pyi_review_aggregator_settings[base_url]', ((isset($settings['base_url']))?$settings['base_url']:''));
	echo smartlogix_get_control('select', 'Sync Frequency', 'pyi_review_aggregator_settings_frequency', 'pyi_review_aggregator_settings[frequency]', ((isset($settings['frequency']))?$settings['frequency']:''), array(array('text' => 'Hourly', 'value' => 'hourly'), array('text' => 'Daily', 'value' => 'daily'), array('text' => 'Weekly', 'value' => 'weekly')));
		echo '<p>Total reviews: '.@$settings['total_reviews'].'</p>';
	echo '<p>Average Rating: '.@$settings['avg_rating'].'</p>';
}

function pyi_review_aggregator_template_meta_box_content() {
	$settings = get_option('pyi_review_aggregator_settings');
	echo smartlogix_get_control('select', 'Review Wrapper', 'pyi_review_aggregator_settings_template_wrapper', 'pyi_review_aggregator_settings[template_wrapper]', ((isset($settings['template_wrapper']))?$settings['template_wrapper']:''), array(array('text' => 'Div', 'value' => 'div'), array('text' => 'UL', 'value' => 'ul')));
	echo smartlogix_get_control('textarea-big', 'Review Item Template', 'pyi_review_aggregator_settings_template', 'pyi_review_aggregator_settings[template]', ((isset($settings['template']))?$settings['template']:''), null, 'Add "###" to the beginning and end of the Review Field Names to create its template tag<br />EG: "ratingValue" becomes "###ratingValue###"');
	echo '<p>';
		echo '<label>Shortcode</label><br />';
		echo '<code>[pyireviews count="10" random="false"]</code>';
	echo '<p>';
	echo '<p>';
		echo '<label>Template Tag</label><br />';
		echo '<code>if(function_exists("pyi_reviews")) { pyi_reviews(10, false); }</code>';
	echo '<p>';
}
?>