<?php
function pyi_reviews($reviewCount = 10, $randomize = 'false')
{
    echo do_shortcode('[pyireviews count="' . $reviewCount . '" random="' . $randomize . '"]');
}

add_shortcode('pyireviews', function ($atts) {
    $atts = shortcode_atts(array('count' => 10, 'random' => false, 'order'=> false), $atts, 'pyireviews');
    $settings = get_option('pyi_review_aggregator_settings');
    $query = array(
        'numberposts' => $atts['count'],
        'post_type' => 'reviews',
        'orderby' => (($atts['random'] == 'true') ? 'rand' : 'meta_value'),
        'order' => $atts['order'] ?: 'desc'
    );

    // sort by meta field
    if ($atts['random'] !== 'true') {
        $query['meta_key'] = 'pyi_review_aggregator_data_datePublished';
    }

    $reviews = get_posts($query);

    $output = '<div class="pyiReviewsMeta">
				<div class="pyiAverageReviews">
					<div class="text">
						<span>###avgRating###</span> 
						<label>Out of 5 Stars</label>
					</div>
					<div class="star">
						<div class="inner-star" style="width: calc(20% * ###avgRating###);">
							<i class="fa-solid fa-star"></i> 
							<i class="fa-solid fa-star"></i> 
							<i class="fa-solid fa-star"></i> 
							<i class="fa-solid fa-star"></i> 
							<i class="fa-solid fa-star"></i>
						</div>
					</div>
				</div>
				<div class="pyiTotalReviews">Overall rating of <span>###totalReviews###</span> 1st-party reviews</div>
			</div>';

    if (isset($reviews) && is_array($reviews) && isset($settings['template']) && ($settings['template'] != '')) {
        if (isset($settings['template_wrapper']) && ($settings['template_wrapper'] == 'div')) {
            $output .= '<div class="pyiReviews">';
        } else {
            $output .= '<ul class="pyiReviews">';
        }
        foreach ($reviews as $review) {
            $search = array();
            $replace = array();
            $reviewMeta = get_post_meta($review->ID);
            if (isset($reviewMeta) && is_array($reviewMeta)) {
                $reviewMeta = array_combine(array_keys($reviewMeta), array_column($reviewMeta, '0'));
                foreach ($reviewMeta as $key => $value) {
                    if (strpos($key, 'pyi_review_aggregator_data_') !== false) {
                        $actualKey = str_replace('pyi_review_aggregator_data_', '', $key);
                        $search[] = '###' . $actualKey . '###';
                        $replace[] = ((isset($value)) ? $value : '');
                    }
                }
            }
            if (isset($settings['template_wrapper']) && ($settings['template_wrapper'] == 'div')) {
                $output .= '<div class="pyiReview">';
            } else {
                $output .= '<li class="pyiReview">';
            }
            $output .= str_replace($search, $replace, $settings['template']);
            if (isset($settings['template_wrapper']) && ($settings['template_wrapper'] == 'div')) {
                $output .= '</div>';
            } else {
                $output .= '</li>';
            }
        }
        if (isset($settings['template_wrapper']) && ($settings['template_wrapper'] == 'div')) {
            $output .= '</div>';
        } else {
            $output .= '</ul>';
        }
    }

    // load data from cache
    $output = str_replace('###totalReviews###', @$settings['total_reviews'], $output);
    $output = str_replace('###avgRating###', @$settings['avg_rating'], $output);

    return $output;
});
?>