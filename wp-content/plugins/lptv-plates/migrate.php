#!/usr/bin/php
<?php

$fonts = ['1', '1a', '2', '2a'];
$fields = ['font', 'minChar', 'maxChar', 'xPos', 'yPos', 'fontSize', 'fontColor'];
$extraFields = ["symbols", "edecal", "saftydecal", "statedecal"];

require_once dirname(__DIR__, 3) . '/wp-load.php';
require_once dirname(__DIR__, 3) . '/wp-includes/taxonomy.php';

// Database Connection
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$mysqli->set_charset('utf8mb4');

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

if ($args[0] == 'import-info') {
    importProductInfo($mysqli, $batchSize);
}

if ($args[0] == 'import-all') {
    importAllProducts($mysqli, $batchSize);
}

if ($args[0] == 'link-categories') {
    linkCategories($mysqli, $batchSize, $args[1]);
}

if ($args[0] == 'update-slugs') {
    updateSlugs($mysqli, $batchSize, $args[1]);
}

if ($args[0] == 'import-categories-images') {
    importCategoriesImages($mysqli);
}

// add some more custom fields
if ($args[0] == 'add-custom-fields') {
    addCustomFields($mysqli);
}

if ($args[0] == 'import-categories-descriptions') {
    importCategoriesDescriptions($mysqli);
}
// if ($args[0] == 'fix-slugs') {
//     fixSlugs($mysqli, $batchSize, $args[1]);
//     return;
// }

// if ($args[0] == 'fix-product-ids') {
//     fixProductIds($mysqli, $batchSize);
//     return;
// }

// if ($args[0] == 'categories') {
//     remapCategories($mysqli, $args[1]);
//     return;
// }

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
                    'description' => wp_kses_post($catDesc),
                ]);
                $termId = $existingCategory->term_id;
            } else {
                // Insert new WooCommerce product category
                $insertedTerm = wp_insert_term($catName, 'product_cat', [
                    'slug' => $catSlug,
                    // allow safe html
                    'description' => wp_kses_post($catDesc),
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
    // foreach ($categoryMap as $oldCatId => $wpCatId) {
    //     $parentId = $oldCategoryData[$oldCatId]['parent_id'] ?? 0;
    //     $wpParentId = $categoryMap[$parentId] ?? 0;
    //     wp_update_term($wpCatId, 'product_cat', ['parent' => $wpParentId]);
    // }


    echo "Migration completed.\n";
    flush();
}

/**
 * Import categories descriptions
 * php migrate.php import-categories-descriptions
 */
function importCategoriesDescriptions($mysqli)
{
    echo "Start importing categories descriptions....\n";
    // Fetch all categories from ZenCart
    $query = "SELECT c.categories_id, c.parent_id, c.categories_status, d.categories_name, d.categories_description, c.symbolname 
          FROM zen2_categories c 
          JOIN zen2_categories_description d ON c.categories_id = d.categories_id where c.categories_id=143";

    $result = $mysqli->query($query);

    global $wpdb;
    $categoryMap = []; // To store old-to-new category ID mapping
    $oldCategoryData = [];

    remove_all_filters('pre_term_description');
    remove_all_filters('term_description');

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

                echo "Variable type: " . gettype($catDesc) . "\n";
                echo "Variable length: " . strlen($catDesc) . "\n";
                echo "Variable encoding: " . mb_detect_encoding($catDesc) . "\n";
                echo "Raw variable content: " . bin2hex($catDesc) . "\n";

                // Update category if exists
                wp_update_term($existingCategory->term_id, 'product_cat', [
                    'description' => $catDesc,
                ]);
                clean_term_cache($existingCategory->term_id, 'product_cat');
                echo "Category updated in WordPress, id: $existingCategory->term_id \n";
                echo "Current database: " . $wpdb->dbname . "\n";
            } else {

                echo "Category not found in WordPress, source cat id: $oldCatId \n";
            }
        }
    }

    add_filter('pre_term_description', 'wp_filter_kses');
    add_filter('term_description', 'wp_kses_data');

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


function updateSlugs($mysqli, $batchSize, $prefix)
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

            echo "Product found in WP, sku: $sku, id: $productId \n";
            // update slug with native mysql update request
            $uri = @$data['uri'];
            // explode
            $parts = explode('/', $uri);
            $slug = end($parts);
            $slug = str_replace('.html', '', $slug);
            echo $slug . "\n";

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

function linkCategories($mysqli, $batchSize)
{

    echo 'Start syncing categories....\n';
    $offset = 0;
    $stat = ['created' => 0, 'updated' => 0];


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
            if ($categories_id) {

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

                            $stat['updated']++;
                        }
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

function fixProductIds($mysqli, $batchSize)
{

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

            if ($productId) {
                $updateMeta = update_post_meta($productId, '_plate_template_id', $data['productId']);
                $stat['updated']++;
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

function importProductInfo($mysqli, $batchSize)
{

    $fonts = ['1', '1a', '2', '2a'];
    $fields = ['font', 'minChar', 'maxChar', 'xPos', 'yPos', 'fontSize', 'fontColor'];
    $extraFields = ["symbols", "edecal", "saftydecal", "statedecal"];

    echo "Starting import...\n";

    $offset = 0;
    $stat = ['created' => 0, 'updated' => 0];
    // Main loop to process batches
    do {
        $query = "SELECT info.*, products.*                       
              FROM zen2_lpgen_info AS info
              JOIN zen2_products AS products ON info.productId = products.products_model
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
                echo "Product not found in WP, sku: $sku \n";
                continue;
            }

            echo "- - Processing product id $productId \n";

            $updateMeta = update_post_meta($productId, '_plate_template_id', $data['productId']);

            $stat['updated']++;

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


/**
 * Import all poducts with descriptions
 */
function importAllProducts($mysqli, $batchSize)
{
    echo "Starting import all products...\n";

    $offset = 0;
    $stat = ['created' => 0, 'updated' => 0];

    do {
        //$query = "SELECT * FROM zen2_products LIMIT $batchSize OFFSET $offset";
        $query = "SELECT * FROM zen2_products p JOIN zen2_products_description d ON p.products_id=d.products_id LIMIT $batchSize OFFSET $offset";

        $rows = $mysqli->query($query);

        if (!$rows || $rows->num_rows === 0) {
            break;
        }

        echo ' >> Processing ' . $offset . '' . PHP_EOL;

        while ($data = $rows->fetch_assoc()) {

            $sku = $data['products_model'];
            $productId = wc_get_product_id_by_sku($sku);

            echo PHP_EOL . "Product sku: $sku";

            if (!$productId) {
                $product = new WC_Product_LPTVplate();
                // simple product
                $product->set_sku($sku);
                $stat['created']++;

                $product->set_name($data['products_name']);
                $product->set_regular_price($data['products_price']);
                $product->set_description($data['products_description']);

                // set slug
                $product->set_slug($data['symbolname']);

                try {
                    $product->save();
                    echo "+++";
                } catch (Exception $e) {
                    echo $e->getMessage();
                }

                $productId = $product->get_id();

                echo "Product id: $productId " . PHP_EOL;;
                $stat['created']++;
            } else {
                echo ' >>> ✅';
                $stat['updated']++;
            }

            update_post_meta($productId, '_plate_products_id', $data['products_id']);
            update_post_meta($productId, '_plate_products_image', $data['products_image']);
            update_post_meta($productId, '_plate_products_custom', $data['products_custom']);
            update_post_meta($productId, '_plate_products_type', $data['products_type']);
            update_post_meta($productId, '_plate_font_choose', $data['font_choose']);
        }

        $offset += $batchSize;
        echo ">> Processed $offset items...\n";
        flush();
    } while ($rows->num_rows === $batchSize);

    echo "Migration completed.\n";
    var_dump($stat);
    $mysqli->close();
}

function importCategoriesImages($mysqli)
{

    echo "Start syncing categories images....\n";
    // Fetch all categories from ZenCart
    $query = "SELECT c.categories_id, c.parent_id, c.categories_status, d.categories_name, d.categories_description, c.symbolname, c.* 
          FROM zen2_categories c 
          JOIN zen2_categories_description d ON c.categories_id = d.categories_id";

    $result = $mysqli->query($query);

    $categoryMap = []; // To store old-to-new category ID mapping
    $oldCategoryData = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {

            $catSlug = $row['symbolname'];
            $image = $row['categories_image'];
            $imageHeader = $row['categories_image_header'];
            $catDesc = $row['cat_desc'];

            // Check if category already exists in WordPress
            $existingCategory = get_term_by('slug', $catSlug, 'product_cat');

            if ($existingCategory) {
                $termId = $existingCategory->term_id;
                // update category image
                update_term_meta($termId, 'categories_image', $image);
                update_term_meta($termId, 'categories_image_header', $imageHeader);
                update_term_meta($termId, 'cat_desc', $catDesc);
            }
        }
    }

    // Update parent-child relationships
    echo "Migration completed.\n";
    flush();
}


// unversal plate options & custom instructions
function addCustomFields($mysqli)
{
    echo "Starting add custom fields...\n";

    $offset = 0;
    $stat = ['created' => 0, 'updated' => 0];
    $batchSize = 50;

    do {
        $query = "SELECT * FROM zen2_products_attributes  where options_id = 2 LIMIT $batchSize OFFSET $offset";

        $rows = $mysqli->query($query);
        echo "\nRows: " . $rows->num_rows;

        if (!$rows || $rows->num_rows === 0) {
            break;
        }

        echo "\n>> Processing " . $offset;

        while ($data = $rows->fetch_assoc()) {

            $id = $data['products_id'];
            $options_values_id = $data['options_values_id'];

            // search wooocommerce product by meta _plate_products_id
            $product = get_posts([
                'post_type' => 'product',
                'meta_key' => '_plate_products_id',
                'meta_value' => $id
            ]);

            if ($product) {
                $productId = $product[0]->ID;
                // add custom field do we need to add universal plate suggestion
                update_post_meta($productId, '_plate_options_2', $options_values_id);
            }
        }

        $offset += $batchSize;
        echo "\n>> Processed $offset items...\n";
        flush();
    } while ($rows->num_rows === $batchSize);

    echo "Attributes migration completed.\n";

    // add custom instructions
    $batchSize = 50;
    $offset = 0;

    do {
        $query = "SELECT * FROM zen2_products_description  WHERE products_instructions!=\"\" OR products_parts!=\"\" LIMIT $batchSize OFFSET $offset";

        $rows = $mysqli->query($query);
        echo "\nRows: " . $rows->num_rows;

        if (!$rows || $rows->num_rows === 0) {
            break;
        }

        echo "\n>> Processing " . $offset;

        while ($data = $rows->fetch_assoc()) {

            $id = $data['products_id'];
            $options_values_id = $data['options_values_id'];

            // search wooocommerce product by meta _plate_products_id
            $product = get_posts([
                'post_type' => 'product',
                'meta_key' => '_plate_products_id',
                'meta_value' => $id
            ]);

            if ($product) {
                $productId = $product[0]->ID;
                // add custom field do we need to add universal plate suggestion
                update_post_meta($productId, '_plate_products_instructions', $data['products_instructions']);
                update_post_meta($productId, '_plate_products_parts', $data['products_parts']);
            }
        }

        $offset += $batchSize;
        echo ">> Processed $offset items...\n";
        flush();
    } while ($rows->num_rows === $batchSize);

    echo "Custom instructions migration completed.\n";
    flush();
}


// add custom order for products
// php migrate.php import-custom-order-for-products
if ($args[0] == 'import-custom-order-for-products') {
    importCustomOrderForProducts($mysqli);
}

if ($args[0] == 'update-symbols-with-slash') {
    updateSymbolsWithSlash($mysqli, 50);
}
function importCustomOrderForProducts($mysqli)
{
    echo "Starting add custom order for products...\n";
    $batchSize = 50;
    $offset = 0;
    $updated = 0;

    do {
        $query = "SELECT * FROM zen2_products LIMIT $batchSize OFFSET $offset";

        $rows = $mysqli->query($query);
        echo "\nRows: " . $rows->num_rows;

        if (!$rows || $rows->num_rows === 0) {
            break;
        }

        echo "\n>> Processing " . $offset;

        while ($data = $rows->fetch_assoc()) {

            $id = $data['products_id'];
            $model = $data['products_model'];
            $sort_order = $data['products_sort_order'];

            // search wooocommerce product by meta _plate_products_id
            $product = get_posts([
                'post_type' => 'product',
                'meta_key' => '_plate_products_id',
                'meta_value' => $id
            ]);

            if ($product) {
                $productId = $product[0]->ID;
                // add custom field do we need to add universal plate suggestion
                update_post_meta($productId, '_plate_products_sort_order', $sort_order);
                $updated++;
            }
        }

        $offset += $batchSize;
        echo ">> Processed $offset items...\n";
        flush();
    } while ($rows->num_rows === $batchSize);

    echo "Custom order for products migration completed.\n";
    echo "Updated: $updated products\n";
    flush();
}

/**
 * Update products with symbols containing "/" character
 * php migrate.php update-symbols-with-slash
 */
function updateSymbolsWithSlash($mysqli, $batchSize)
{
    echo "Starting update symbols with slash...\n";
    $offset = 0;
    $updated = 0;

    do {
        // query records where symbols column contains "/" character
        $query = "SELECT * FROM zen2_lpgen_info WHERE symbols REGEXP '/' LIMIT $batchSize OFFSET $offset";

        $rows = $mysqli->query($query);
        echo "\nRows: " . $rows->num_rows;

        if (!$rows || $rows->num_rows === 0) {
            break;
        }

        echo "\n>> Processing " . $offset;

        while ($data = $rows->fetch_assoc()) {
            $productId = $data['productId'];
            $symbols = $data['symbols'];


            // find wooocommerce product by custom post meta _plate_products_id
            $product = get_posts([
                'post_type' => 'product',
                'meta_key' => '_plate_template_id',
                'meta_value' => $productId
            ]);

            if ($product) {
                $wpProductId = $product[0]->ID;
                // properly escape and save symbols to product meta
                // use addslashes to handle backslashes properly
                $escapedSymbols = addslashes($symbols);
                update_post_meta($wpProductId, '_plate_symbols', $escapedSymbols);
                $updated++;
                echo "\nUpdated product ID: $wpProductId with symbols: $symbols";
            } else {
                echo "\nProduct not found for productId: $productId";
            }
        }

        $offset += $batchSize;
        echo "\n>> Processed $offset items...\n";
        flush();
    } while ($rows->num_rows === $batchSize);

    echo "\nSymbols with slash migration completed.\n";
    echo "Updated: $updated products\n";
    flush();
}

