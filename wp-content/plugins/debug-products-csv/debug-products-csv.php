<?php
/**
 * Plugin Name: Debug Products CSV Generator
 * Description: Simple debug plugin to generate CSV of all products with their category titles
 * Version: 1.0.0
 * Author: Debug Tool
 */

// prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Debug_Products_CSV {
    
    public function __construct() {
        // hook into init to check for the URL parameter
        add_action('init', array($this, 'check_for_csv_generation'));
    }
    
    /**
     * check if the URL parameter is present and generate CSV
     */
    public function check_for_csv_generation() {
        // only proceed if the parameter is present
        if (!isset($_GET['generateProductsCSV'])) {
            return;
        }
        
        // check if user has admin capabilities for security
        if (!current_user_can('manage_options')) {
            wp_die('Access denied. Admin privileges required.');
        }
        
        // generate and output the CSV
        $this->generate_products_csv();
    }
    
    /**
     * generate CSV with all products and their categories
     */
    private function generate_products_csv() {
        // set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="products-with-categories-' . date('Y-m-d-H-i-s') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // create output stream
        $output = fopen('php://output', 'w');
        
        // write CSV header
        fputcsv($output, array(
            'Product ID',
            'Product Name',
            'Product SKU',
            'Product Status',
            'Category Names',
            'Category IDs',
            'Has Categories'
        ));
        
        // get all products
        $args = array(
            'post_type' => 'product',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'orderby' => 'ID',
            'order' => 'ASC'
        );
        
        $products = get_posts($args);
        
        foreach ($products as $product) {
            // get product categories
            $categories = get_the_terms($product->ID, 'product_cat');
            
            $category_names = array();
            $category_ids = array();
            
            if ($categories && !is_wp_error($categories)) {
                foreach ($categories as $category) {
                    $category_names[] = $category->name;
                    $category_ids[] = $category->term_id;
                }
            }
            
            $has_categories = !empty($category_names) ? 'Yes' : 'No';
            
            // get product SKU if WooCommerce is active
            $sku = '';
            if (function_exists('wc_get_product')) {
                $wc_product = wc_get_product($product->ID);
                if ($wc_product) {
                    $sku = $wc_product->get_sku();
                }
            }
            
            // write product data to CSV
            fputcsv($output, array(
                $product->ID,
                $product->post_title,
                $sku,
                $product->post_status,
                implode(', ', $category_names),
                implode(', ', $category_ids),
                $has_categories
            ));
        }
        
        fclose($output);
        exit;
    }
}

// initialize the plugin
new Debug_Products_CSV(); 