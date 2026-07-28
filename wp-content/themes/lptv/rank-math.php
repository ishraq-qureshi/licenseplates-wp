<?php



// helper function for getting category image

function get_category_opengraph_image($image) {

    $term = get_queried_object();



    if ($term->taxonomy == 'product_cat') {

        $image = get_term_meta($term->term_id, 'categories_image', true);

        $website_url = get_bloginfo('url');

        $image_url = $website_url . '/wp-content/resources/images/' . $image;



        $imagepath = __DIR__ . '/../../resources/images/resized_' . $image;



        // check if file exists

        if (file_exists($imagepath)) {

            $image_url = $website_url . '/wp-content/resources/images/resized_' . $image;

        } else if ($image) {

            $image_url = $website_url . '/wp-content/resources/images/' . $image;

        } else {

            $image_url = $website_url . '/wp-content/resources/images/default.jpg';

        }



        return $image_url;

    } else {

        if ($term instanceof WP_Post) {
            $product = wc_get_product($term->ID);
            if ($product) {
                $image_url = $product->get_lptv_image_url();
                if ($image_url) {
                    // generate and return OG image
                    $og_image_url = generate_product_og_image($term->ID, $image_url, $term->post_title);
                    return $og_image_url;
                }
            }
        }

    }



    return $image;

}



add_filter('rank_math/opengraph/facebook/image', 'get_category_opengraph_image');

add_filter('rank_math/opengraph/twitter/image', 'get_category_opengraph_image');



// change description

add_filter('rank_math/frontend/description', function ($description) {

    $term = get_queried_object();

    if ($term->taxonomy == 'product_cat') {

        // attach category title

        $meta_description = get_term_meta($term->term_id, 'meta_description', true);

        if ($meta_description) {

            $description = $meta_description;

        }

    }



    // if term is post

    if ($term instanceof WP_Post) {

        $meta_description = get_post_meta($term->ID, 'meta_description', true);

        if ($meta_description) {

            $description = $meta_description;

            return $description;

        } else {

            // try to get product with this id and get the price

            $product = wc_get_product($term->ID);

            if ($product) {

                $price = $product->get_price();

                $model = get_post_meta($term->ID, '_plate_template_id', true);

                $short = $product->get_description();

                // remove html tags

                $short = strip_tags($short);

                // limit to meta description length

                $short = substr($short, 0, 465);

                $text = 'Custom Front License Plates, Personalized Vanity Auto Plate - LICENSEPLATES.TV '. $term->post_title;

                $text = $text . ' [' . $model . '] - '.$short;

                return $text;

            }

        }

    }



    return $description;

});



// change title

add_filter('rank_math/frontend/title', function ($title) {

    $term = get_queried_object();



    if ($term->taxonomy == 'product_cat') {

        // attach category title

        $meta_title = get_term_meta($term->term_id, 'meta_title', true);

        if ($meta_title) {

            $title = $meta_title;

            return $title;

        }

    }



    // if term is post

    if ($term instanceof WP_Post) {

        $meta_title = get_post_meta($term->ID, 'meta_title', true);

        if ($meta_title) {

            return $meta_title . ' - LICENSEPLATES.TV';

        } else {

            // try to get product with this id and get the price

            $product = wc_get_product($term->ID);

            if ($product) {

                $price = $product->get_price();

                $model = get_post_meta($term->ID, '_plate_template_id', true);

                return $term->post_title . ' [' . $model . '] - ' . $price . ' - LICENSEPLATES.TV';

            }

        }

    }

    return $title;

});



// add keywords

add_filter('rank_math/frontend/keywords', function ($keywords) {

    $term = get_queried_object();

    if ($term->taxonomy == 'product_cat') {

        $keywords = get_term_meta($term->term_id, 'meta_keywords', true);

        if ($keywords) {

            $keywords = $keywords;

            return $keywords;

        }

    }



    if ($term instanceof WP_Post) {

        $meta_keywords = get_post_meta($term->ID, 'meta_keywords', true);

        if ($meta_keywords) {

            $keywords = $meta_keywords;

            return $keywords;

        }

    }



    return $keywords;

});

// customize schema logo
add_filter('rank_math/json_ld', function ($data, $jsonld) {
    $website_url = get_bloginfo('url');
    
    // update Product schema if it's a WooCommerce product page
    $term = get_queried_object();
    if ($term instanceof WP_Post && isset($data['richSnippet']) && $data['richSnippet']['@type'] === 'Product') {
        // get product image
        $product = wc_get_product($term->ID);
        if ($product) {
            $image_url = $product->get_lptv_image_url();
                if ($image_url) {
                    // add image field with direct URL following Google recommendations
                    // Google requires image as a URL string, not an ImageObject reference
                    $og_image_url = generate_product_og_image($term->ID, $image_url, $term->post_title);
                    $data['richSnippet']['image'] = $og_image_url;


                    // update mainEntityOfPage primaryImageOfPage fields
                    if (isset($data['richSnippet']['mainEntityOfPage'])) {
                        $data['richSnippet']['mainEntityOfPage']['primaryImageOfPage'] = [
                            '@id' => $og_image_url,
                            'url' => $og_image_url
                        ];
                    }
                    
                    // replace primaryImageOfPage at WebPage (ItemPage) level
                    // rank math stores ItemPage under the 'WebPage' key
                    if (isset($data['WebPage'])) {
                        $data['WebPage']['primaryImageOfPage'] = [
                            '@id' => $og_image_url,
                            'url' => $og_image_url
                        ];
                    }
                    
                    // replace the default ImageObject with the product image
                    foreach ($data as $key => $value) {
                        if (is_array($value) && isset($value['@type']) && $value['@type'] === 'ImageObject') {
                            $data[$key]['@id'] = $og_image_url;
                            $data[$key]['url'] = $og_image_url;
                            break;
                        }
                    }
                    
                   

                }
        }
    }
    
    
    return $data;
}, 999, 2);

// generate custom OG image with product title and scaled image
function generate_product_og_image($product_id, $source_image_url, $product_title) {
    // define OG image dimensions
    $og_width = 1200;
    $og_height = 630;
    
    // create cache directory if it doesn't exist
    $cache_dir = __DIR__ . '/../../resources/images/og-cache';
    if (!file_exists($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }
    
    // generate cache filename based on product ID
    $cache_filename = 'og-product-' . $product_id . '.jpg';
    $cache_path = $cache_dir . '/' . $cache_filename;
    $website_url = get_bloginfo('url');
    $cache_url = $website_url . '/wp-content/resources/images/og-cache/' . $cache_filename;
    
    // check if cached image exists and is recent (less than 24 hours old)
    if (file_exists($cache_path) && (time() - filemtime($cache_path)) < 86400) {
        return $cache_url;
    }
    
    // create base canvas
    $canvas = imagecreatetruecolor($og_width, $og_height);
    
    // set white background
    $white = imagecolorallocate($canvas, 255, 255, 255);
    imagefill($canvas, 0, 0, $white);
    
    // load source image
    $source_path = str_replace($website_url . '/wp-content/', __DIR__ . '/../../', $source_image_url);
    
    // try to load the image based on extension
    $image_info = @getimagesize($source_path);
    if ($image_info === false) {
        // if local file doesn't exist, try to load from URL
        $image_info = @getimagesize($source_image_url);
        $source_path = $source_image_url;
    }
    
    if ($image_info !== false) {
        $mime_type = $image_info['mime'];
        $source_image = null;
        
        switch ($mime_type) {
            case 'image/jpeg':
                $source_image = @imagecreatefromjpeg($source_path);
                break;
            case 'image/png':
                $source_image = @imagecreatefrompng($source_path);
                break;
            case 'image/gif':
                $source_image = @imagecreatefromgif($source_path);
                break;
            case 'image/webp':
                $source_image = @imagecreatefromwebp($source_path);
                break;
        }
        
        if ($source_image) {
            // get source dimensions
            $source_width = imagesx($source_image);
            $source_height = imagesy($source_image);
            
            // calculate scaled dimensions to fit within canvas while maintaining aspect ratio
            $max_image_width = $og_width - 100; // leave 50px padding on each side
            $max_image_height = $og_height - 150; // leave space for title at bottom
            
            $scale = min($max_image_width / $source_width, $max_image_height / $source_height);
            $new_width = (int)($source_width * $scale);
            $new_height = (int)($source_height * $scale);
            
            // center the image horizontally
            $dest_x = (int)(($og_width - $new_width) / 2);
            $dest_y = 50; // top padding
            
            // copy and resize image onto canvas
            imagecopyresampled($canvas, $source_image, $dest_x, $dest_y, 0, 0, $new_width, $new_height, $source_width, $source_height);
            imagedestroy($source_image);
        }
    }
    
    // add product title text at the bottom
    $text_color = imagecolorallocate($canvas, 0, 0, 0);
    
    // use built-in font or try to load TrueType font
    $font_path = __DIR__ . '/fonts/HelveticaNeueLTStd-Bd.ttf';
    $font_size = 32;
    
    // wrap text if too long
    $max_chars_per_line = 50;
    $title_wrapped = wordwrap($product_title, $max_chars_per_line, "\n", true);
    $title_lines = explode("\n", $title_wrapped);
    
    // limit to 2 lines
    if (count($title_lines) > 2) {
        $title_lines = array_slice($title_lines, 0, 2);
        $title_lines[1] = substr($title_lines[1], 0, $max_chars_per_line - 3) . '...';
    }
    
    $text_y = $og_height - 120;
    
    foreach ($title_lines as $line) {
        if (file_exists($font_path)) {
            // calculate text bounding box to center it
            $bbox = imagettfbbox($font_size, 0, $font_path, $line);
            $text_width = $bbox[2] - $bbox[0];
            $text_x = (int)(($og_width - $text_width) / 2);
            imagettftext($canvas, $font_size, 0, $text_x, $text_y, $text_color, $font_path, $line);
        } else {
            // fallback to built-in font (font 5 is the largest built-in)
            $text_width = imagefontwidth(5) * strlen($line);
            $text_x = (int)(($og_width - $text_width) / 2);
            imagestring($canvas, 5, $text_x, $text_y - 10, $line, $text_color);
        }
        $text_y += 45;
    }
    
    // save the image
    imagejpeg($canvas, $cache_path, 90);
    imagedestroy($canvas);
    
    return $cache_url;
}