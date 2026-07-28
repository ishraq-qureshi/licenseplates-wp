<?php

add_action('parse_request', function ($request) {

    if ($request->request !== 'lptv/migrate') {
        return;
    }

    // set time limit 0
    set_time_limit(0);

    global $fonts, $fields, $extraFields, $wpdb;

    $batchSize = 10; // Process 50 items at a time
    $offset = 0;
    $stat = ['created' => 0, 'updated' => 0];

    ob_implicit_flush(true);
    @ob_end_flush();

    header('X-Accel-Buffering: no');
    header('Content-Encoding: none'); 
    header('Cache-Control: no-cache, must-revalidate');
    header('Connection: close');
    flush();
    
    do {
        
        echo "Processing{$offset} items...<br/>";
        flush();
        ob_flush();

        $query = "SELECT info.*, products.products_id, products.products_price, products.products_model, 
                         details.*, cat.categories_id, catdetails.categories_name 
                  FROM zen2_lpgen_info AS info
                  JOIN zen2_products AS products ON info.productId = products.products_model
                  JOIN zen2_products_description AS details ON products.products_id = details.products_id
                  JOIN zen2_products_to_categories AS cat ON products.products_id = cat.products_id
                  JOIN zen2_categories_description AS catdetails ON catdetails.categories_id = cat.categories_id
                  LIMIT $batchSize OFFSET $offset";

        $rows = $wpdb->get_results($query, ARRAY_A);

        if (!$rows) {
            break;
        }

        foreach ($rows as $data) {
            $sku = $data['products_model'];
            $productId = wc_get_product_id_by_sku($sku);

            if (!$productId) {
                $product = new WC_Product_LPTVplate();
                $product->set_sku($sku);
                $stat['created']++;
            } else {
                $product = wc_get_product($productId);
                $stat['updated']++;
            }

            $product->set_name($data['products_name']);
            $product->set_regular_price($data['products_price']);
            $product->set_description($data['products_description']);

            // Handle category
            $category = get_term_by('name', $data['categories_name'], 'product_cat');
            if (!$category) {
                $term = wp_insert_term($data['categories_name'], 'product_cat');
                $category_id = is_wp_error($term) ? 0 : $term['term_id'];
            } else {
                $category_id = $category->term_id;
            }

            if ($category_id) {
                $product->set_category_ids([$category_id]);
            }

            // Set metadata
            $product->update_meta_data('_categories_name', $data['categories_name']);
            $product->update_meta_data('_plate_template_id', $data['products_model']);

            // Set font options
            if (!empty($fonts) && !empty($fields)) {
                foreach ($fonts as $font) {
                    foreach ($fields as $field) {
                        $key = $field . $font;
                        if (isset($data[$key])) {
                            $product->update_meta_data('_plate_' . $key, $data[$key]);
                        }
                    }
                }
            }

            // Import decals and symbols
            if (!empty($extraFields)) {
                foreach ($extraFields as $key) {
                    if (isset($data[$key])) {
                        $product->update_meta_data('_plate_' . $key, $data[$key]);
                    }
                }
            }

            $product->save();
        }

        $offset += $batchSize;

        echo "Processed {$offset} items...<br/>";
        flush();
        ob_flush();
    } while (count($rows) === $batchSize);

    echo 'Migration completed.';
    var_dump($stat);
    die;
});
