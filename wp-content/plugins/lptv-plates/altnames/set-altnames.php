<?php
/**
 * Script to update WordPress media library alt text based on CSV file
 * Reads images_missing_altnames.csv and updates alt text for images
 */

// prevent direct access
if (!defined('ABSPATH')) {
    // include WordPress
    require_once(dirname(__FILE__) . '/../../../../wp-config.php');
}

class AltNameUpdater {
    
    private $csv_file;
    private $processed_count = 0;
    private $updated_count = 0;
    private $not_found_count = 0;
    private $already_filled_count = 0;
    private $log = array();
    
    public function __construct() {
        $this->csv_file = dirname(__FILE__) . '/images_missing_altnames.csv';
    }
    
    /**
     * Main execution method
     */
    public function run() {
        echo "WordPress Media Alt Text Updater\n";
        echo "==================================\n";
        echo "Starting process...\n\n";
        
        if (!file_exists($this->csv_file)) {
            echo "ERROR: CSV file not found at: " . $this->csv_file . "\n";
            return;
        }
        
        $this->process_csv();
        $this->display_results();
    }
    
    /**
     * Process the CSV file
     */
    private function process_csv() {
        $handle = fopen($this->csv_file, 'r');
        
        if ($handle === false) {
            echo "ERROR: Could not open CSV file\n";
            return;
        }
        
        // skip header row
        fgetcsv($handle);
        
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 2 && !empty($row[1])) {
                $image_url = trim($row[1]);
                $this->process_image_url($image_url);
                $this->processed_count++;
            }
        }
        
        fclose($handle);
    }
    
    /**
     * Process a single image URL
     */
    private function process_image_url($image_url) {
        // extract filename from URL
        $filename = $this->extract_filename($image_url);
        
        if (empty($filename)) {
            $this->log[] = "Could not extract filename from: $image_url";
            return;
        }
        
        // find attachment in WordPress media library
        $attachment_id = $this->find_attachment_by_filename($filename);
        
        if (!$attachment_id) {
            $this->log[] = "Image not found in media library: $filename";
            $this->not_found_count++;
            return;
        }
        
        // check if alt text is already filled
        $existing_alt = $this->get_existing_alt_text($attachment_id);
        
        if (!empty($existing_alt)) {
            $this->log[] = "Alt text already exists for: $filename -> '$existing_alt'";
            $this->already_filled_count++;
            return;
        }
        
        // generate readable alt text from filename
        $alt_text = $this->generate_alt_text($filename);
        
        // update alt text
        if ($this->update_alt_text($attachment_id, $alt_text)) {
            $this->log[] = "Updated: $filename -> '$alt_text'";
            $this->updated_count++;
        } else {
            $this->log[] = "Failed to update: $filename";
        }
    }
    
    /**
     * Extract filename from URL
     */
    private function extract_filename($url) {
        $parsed_url = parse_url($url);
        if (!isset($parsed_url['path'])) {
            return '';
        }
        
        return basename($parsed_url['path']);
    }
    
    /**
     * Find WordPress attachment by filename
     */
    private function find_attachment_by_filename($filename) {
        global $wpdb;
        
        // first try exact match
        $attachment_id = $wpdb->get_var($wpdb->prepare("
            SELECT post_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_wp_attached_file' 
            AND meta_value LIKE %s
        ", '%' . $filename));
        
        if ($attachment_id) {
            return $attachment_id;
        }
        
        // try without file extension for scaled images
        $filename_without_ext = pathinfo($filename, PATHINFO_FILENAME);
        $attachment_id = $wpdb->get_var($wpdb->prepare("
            SELECT post_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_wp_attached_file' 
            AND meta_value LIKE %s
        ", '%' . $filename_without_ext . '%'));
        
        return $attachment_id;
    }
    
    /**
     * Get existing alt text for attachment
     */
    private function get_existing_alt_text($attachment_id) {
        return get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
    }
    
    /**
     * Generate readable alt text from filename
     */
    private function generate_alt_text($filename) {
        // remove file extension
        $alt_text = pathinfo($filename, PATHINFO_FILENAME);
        
        // replace common separators with spaces
        $alt_text = str_replace(['_', '-', '+', '.'], ' ', $alt_text);
        
        // remove special characters except spaces
        $alt_text = preg_replace('/[^a-zA-Z0-9\s]/', ' ', $alt_text);
        
        // normalize multiple spaces to single space
        $alt_text = preg_replace('/\s+/', ' ', $alt_text);
        
        // trim and convert to title case
        $alt_text = trim($alt_text);
        $alt_text = ucwords(strtolower($alt_text));
        
        return $alt_text;
    }
    
    /**
     * Update alt text for attachment
     */
    private function update_alt_text($attachment_id, $alt_text) {
        return update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
    }
    
    /**
     * Display results
     */
    private function display_results() {
        echo "\nProcessing Results\n";
        echo "==================\n";
        echo "Total processed: {$this->processed_count}\n";
        echo "Successfully updated: {$this->updated_count}\n";
        echo "Already had alt text: {$this->already_filled_count}\n";
        echo "Not found in media library: {$this->not_found_count}\n";
        
        if (!empty($this->log)) {
            echo "\nDetailed Log:\n";
            echo "-------------\n";
            foreach ($this->log as $entry) {
                echo $entry . "\n";
            }
        }
    }
}

// run the script
if (php_sapi_name() === 'cli' || (isset($_GET['run']) && $_GET['run'] === 'altnames')) {
    $updater = new AltNameUpdater();
    $updater->run();
} else {
    echo "To run this script, add ?run=altnames to the URL or run from command line.\n";
    echo "Example: php " . basename(__FILE__) . "\n";
}