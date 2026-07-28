#!/usr/bin/php
<?php

$fonts = ['1', '1a', '2', '2a'];
$fields = ['font', 'minChar', 'maxChar', 'xPos', 'yPos', 'fontSize', 'fontColor'];
$extraFields = ["symbols", "edecal", "saftydecal", "statedecal"];

// Load WordPress Core (modify path if needed)
require_once __DIR__ . '/wp-load.php';
require_once __DIR__ . '/wp-includes/taxonomy.php';

// Database Connection
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Config
$batchSize = 50;

// Disable buffering
if (ob_get_level()) {
    while (ob_get_level()) {
        ob_end_flush();
    }
}

// get php cli arguments
$args = array_slice($argv, 1);

header('X-Accel-Buffering: no');
header('Content-Encoding: none');
header('Cache-Control: no-cache, must-revalidate');
header('Connection: close');
flush();

if ($args[0] == 'products') {
    importProducts($mysqli, $batchSize);
}

if ($args[0] == 'slugs') {
    importSlugs($mysqli, $batchSize, $args[1]);
}

if ($args[0] == 'fix-slugs') {
    fixSlugs($mysqli, $batchSize, $args[1]);
    return;
}


if ($args[0] == 'categories') {
    remapCategories($mysqli, $args[1]);
    return;
}

function remapCategories($mysqli, $prefix)
{

    echo "Start syncing categories....\n";
    // Fetch all categories from ZenCart
    $query = "SELECT c.categories_id, c.parent_id, c.categories_status, d.categories_name, d.categories_description, c.symbolname 
          FROM zen2_categories c 
          JOIN zen2_categories_description d ON c.categories_id = d.categories_id";

    $result = $mysqli->query($query);

    $categoryMap = []; // To store old-to-new category ID mapping
    $oldCategoryData = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $oldCatId = $row['categories_id'];
            $parentId = $row['parent_id'];
            $catName = $row['categories_name'];
            $catDesc = $row['categories_description'];
            $catSlug = $row['symbolname'];
            $oldCategoryData[$oldCatId] = ['parent_id' => $parentId];

            // Check if category already exists in WordPress
            $existingCategory = get_term_by('slug', $catSlug, 'product_cat');

            if ($existingCategory) {
                // Update category if exists
                wp_update_term($existingCategory->term_id, 'product_cat', [
                    'description' => $catDesc,
                ]);
                $termId = $existingCategory->term_id;
            } else {
                // Insert new WooCommerce product category
                $insertedTerm = wp_insert_term($catName, 'product_cat', [
                    'slug' => $catSlug,
                    'description' => $catDesc,
                ]);

                if (!is_wp_error($insertedTerm)) {
                    $termId = $insertedTerm['term_id'];
                } else {
                    continue; // Skip on error
                }
            }

            $categoryMap[$oldCatId] = $termId;
        }
    }

    // Update parent-child relationships
    foreach ($categoryMap as $oldCatId => $wpCatId) {
        $parentId = $oldCategoryData[$oldCatId]['parent_id'] ?? 0;
        $wpParentId = $categoryMap[$parentId] ?? 0;
        wp_update_term($wpCatId, 'product_cat', ['parent' => $wpParentId]);
    }


    echo "Migration completed.\n";
    flush();
}

function getZenCategory($id)
{

    $query = "SELECT *FROM zen2_categories WHERE categories_id = $id";

    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    $result = $mysqli->query($query);
    $data = $result->fetch_assoc();

    return $data;
}


function fixSlugs($mysqli, $batchSize, $prefix)
{

    echo 'Start syncing slugs....\n';
    $offset = 0;
    $stat = ['created' => 0, 'updated' => 0];

    // Main loop to process batches
    do {
        $query = "SELECT p.products_model, p.symbolname, p.products_id, seo.uri, seo.associated_db_id from zen2_products as p JOIN zen2_ceon_uri_mappings AS seo ON p.products_id = seo.associated_db_id AND main_page=\"product_info\" LIMIT $batchSize OFFSET $offset";

        $rows = $mysqli->query($query);

        if (!$rows || $rows->num_rows === 0) {
            break;
        }

        echo ' >> Processing ' . $offset;

        while ($data = $rows->fetch_assoc()) {

            $sku = $data['products_model'];
            $productId = wc_get_product_id_by_sku($sku);

            if (!$productId) {
                echo "Product not found in WP, sku: $sku \n";
                continue;
            }

            // update slug with native mysql update request
            $uri = @$data['uri'];
            // explode
            $parts = explode('/', $uri);
            $slug = end($parts);

            if ($slug) {
                $updateQuery = 'UPDATE ' . $prefix . 'posts SET post_name = "' . $slug . '" WHERE ID = ' . $productId;
                $mysqli->query($updateQuery);
                $stat['updated']++;

                // update font choose meta
                update_post_meta($productId, '_plate_original_uri', $uri);
            }
        }

        $offset += $batchSize;
        echo ">> Processed $offset items...\n";
        flush();
    } while ($rows->num_rows === $batchSize);

    echo "Migration completed.\n";
    var_dump($stat);
    $mysqli->close();
}

function importSlugs($mysqli, $batchSize, $prefix)
{

    echo 'Start syncing slugs....\n';
    $offset = 0;
    $stat = ['created' => 0, 'updated' => 0];
    $needCategories = false;
    $needSlug = false;
    $needMeta = true;

    // Main loop to process batches
    do {
        $query = "SELECT * from zen2_products as products JOIN zen2_products_to_categories AS cat ON products.products_id = cat.products_id LIMIT $batchSize OFFSET $offset";

        $rows = $mysqli->query($query);

        if (!$rows || $rows->num_rows === 0) {
            break;
        }

        echo ' >> Processing ' . $offset;

        while ($data = $rows->fetch_assoc()) {

            $sku = $data['products_model'];
            $productId = wc_get_product_id_by_sku($sku);

            // update slug with native mysql update request
            $slug = @$data['symbolname'];
            $categories_id = @$data['categories_id'];
            $fontChoose  = @$data['font_choose'];
            $productsImage = @$data['products_image'];

            if (!$productId) {
                echo "Product not found in WP, sku: $sku \n";
                continue;
            }

            // get slug for this category
            if ($categories_id && $needCategories) {

                $zenCat = getZenCategory($categories_id);

                // check if zencat is null
                if (!$zenCat) {
                    echo "Test category id: $categories_id not found \n";
                } else {

                    $symbolName = $zenCat['symbolname'];

                    if ($symbolName) {
                        // find category in wordpres sby slug
                        $term = get_term_by('slug', $symbolName, 'product_cat');

                        if ($term) {
                            // set term to product
                            // get product terms
                            $terms = wp_get_object_terms($productId, 'product_cat');
                            $term_ids = [];
                            // process multiple categories
                            if (count($terms) > 0) {
                                foreach ($terms as $item) {
                                    $term_ids[] = $item->term_id;
                                }
                            }

                            $term_ids[] = $term->term_id;
                            // make array unique
                            $term_ids = array_unique($term_ids);

                            wp_set_object_terms($productId,  $term_ids, 'product_cat');
                        }
                    }
                }
            }

            if ($slug && $needSlug) {
                $updateQuery = 'UPDATE ' . $prefix . 'posts SET post_name = "' . $slug . '" WHERE ID = ' . $productId;
                $mysqli->query($updateQuery);
                $stat['updated']++;

                // update font choose meta
                $updateMeta = update_post_meta($productId, '_plate_font_choose', $fontChoose);
            }

            // update products image
            if ($productsImage && $needMeta) {
                $updateMeta = update_post_meta($productId, '_plate_products_image', $productsImage);
            }
        }

        $offset += $batchSize;
        echo ">> Processed $offset items...\n";
        flush();
    } while ($rows->num_rows === $batchSize);

    echo "Migration completed.\n";
    var_dump($stat);
    $mysqli->close();
}


function importProducts($mysqli, $batchSize)
{

    $fonts = ['1', '1a', '2', '2a'];
    $fields = ['font', 'minChar', 'maxChar', 'xPos', 'yPos', 'fontSize', 'fontColor'];
    $extraFields = ["symbols", "edecal", "saftydecal", "statedecal"];

    echo "Starting import...\n";

    $offset = 0;
    $stat = ['created' => 0, 'updated' => 0];
    // Main loop to process batches
    do {
        $query = "SELECT info.*, products.products_id, products.products_price, products.products_model, 
                     details.*, cat.categories_id, catdetails.categories_name, products.font_choose, products.symbolname  
              FROM zen2_lpgen_info AS info
              JOIN zen2_products AS products ON info.productId = products.products_model
              JOIN zen2_products_description AS details ON products.products_id = details.products_id
              JOIN zen2_products_to_categories AS cat ON products.products_id = cat.products_id
              JOIN zen2_categories_description AS catdetails ON catdetails.categories_id = cat.categories_id
              LIMIT $batchSize OFFSET $offset";

        $rows = $mysqli->query($query);

        if (!$rows || $rows->num_rows === 0) {
            break;
        }

        echo ' >> Processing ' . $offset;

        while ($data = $rows->fetch_assoc()) {
            $sku = $data['products_model'];
            $productId = wc_get_product_id_by_sku($sku);

            if (!$productId) {
                $product = new WC_Product_LPTVplate();
                $product->set_sku($sku);
                $stat['created']++;

                $product->set_name($data['products_name']);
                $product->set_regular_price($data['products_price']);
                $product->set_description($data['products_description']);

                $product->save();

                $productId = $product->get_id();
            } else {
                //$product = wc_get_product($productId);
                $stat['updated']++;
            }

            echo "- - Processing product id $productId \n";

            $fontChoose  = @$data['font_choose'];

            $updateMeta = update_post_meta($productId, '_plate_font_choose', $fontChoose);
            $updateMeta = update_post_meta($productId, '_plate_template_id', $data['products_model']);
            $updateMeta = update_post_meta($productId, '_plate_products_id', $data['products_id']);
            $updateMeta = update_post_meta($productId, '_plate_products_image', $data['products_image']);

            if (!empty($fonts) && !empty($fields)) {
                foreach ($fonts as $font) {
                    foreach ($fields as $field) {
                        $key = $field . $font;
                        if (isset($data[$key])) {
                            //$product->update_meta_data('_plate_' . $key, $data[$key]);
                            $updateMeta = update_post_meta($productId, '_plate_' . $key, $data[$key]);
                        }
                    }
                }
            }

            if (!empty($extraFields)) {
                foreach ($extraFields as $key) {
                    if (isset($data[$key])) {
                        //$product->update_meta_data('_plate_' . $key, $data[$key]);
                        $updateMeta = update_post_meta($productId, '_plate_' . $key, $data[$key]);
                    }
                }
            }
        }

        $offset += $batchSize;
        echo ">> Processed $offset items...\n";
        flush();
    } while ($rows->num_rows === $batchSize);

    echo "Migration completed.\n";
    var_dump($stat);
    $mysqli->close();
}
