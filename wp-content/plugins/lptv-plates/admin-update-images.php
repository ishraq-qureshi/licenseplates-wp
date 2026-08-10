<?php
/**
 * Admin interface to update featured images
 * 
 * Add this to lptv-plates.php:
 * require_once 'admin-update-images.php';
 */

// Add admin menu
add_action('admin_menu', 'lptv_add_update_images_menu');
function lptv_add_update_images_menu() {
    add_submenu_page(
        'tools.php',
        'Update Product Images',
        'Update Product Images',
        'manage_options',
        'lptv-update-images',
        'lptv_update_images_page'
    );
}

// Admin page content
function lptv_update_images_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Access denied');
    }
    
    ?>
    <div class="wrap">
        <h1>Update LPTV Product Featured Images</h1>
        
        <div class="notice notice-info">
            <p><strong>Important:</strong> This tool will update featured images for all lptvplate products. It processes products in batches to avoid timeouts.</p>
        </div>
        
        <div id="lptv-image-updater">
            <div class="card" style="max-width: 800px;">
                <h2>Status</h2>
                <div id="lptv-status" style="margin: 20px 0;">
                    <p>Ready to start. Click the button below to begin processing.</p>
                </div>
                
                <div id="lptv-progress" style="display: none; margin: 20px 0;">
                    <div style="background: #f0f0f0; height: 30px; border-radius: 3px; overflow: hidden;">
                        <div id="lptv-progress-bar" style="background: #2271b1; height: 100%; width: 0%; transition: width 0.3s;"></div>
                    </div>
                    <p id="lptv-progress-text" style="margin-top: 10px;"></p>
                </div>
                
                <div id="lptv-stats" style="display: none; margin: 20px 0;">
                    <table class="widefat">
                        <tr>
                            <th>Total Products:</th>
                            <td id="stat-total">0</td>
                        </tr>
                        <tr>
                            <th>Processed:</th>
                            <td id="stat-processed">0</td>
                        </tr>
                        <tr>
                            <th style="color: green;">Success:</th>
                            <td id="stat-success" style="color: green;">0</td>
                        </tr>
                        <tr>
                            <th style="color: orange;">Skipped:</th>
                            <td id="stat-skipped" style="color: orange;">0</td>
                        </tr>
                        <tr>
                            <th style="color: red;">Errors:</th>
                            <td id="stat-errors" style="color: red;">0</td>
                        </tr>
                    </table>
                </div>
                
                <div id="lptv-errors" style="display: none; margin: 20px 0;">
                    <h3>Errors</h3>
                    <div id="lptv-error-list" style="max-height: 300px; overflow-y: auto; background: #fff; border: 1px solid #ddd; padding: 10px;"></div>
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="button" id="lptv-start-btn" class="button button-primary button-hero">Start Processing</button>
                    <button type="button" id="lptv-stop-btn" class="button button-secondary" style="display: none;">Stop</button>
                    <button type="button" id="lptv-check-missing-btn" class="button" style="margin-left: 10px;">Check Missing Files</button>
                </div>
                
                <div id="lptv-missing-files" style="display: none; margin-top: 20px;">
                    <h3>Missing Image Files</h3>
                    <div id="lptv-missing-list" style="max-height: 400px; overflow-y: auto; background: #fff; border: 1px solid #ddd; padding: 10px;"></div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        #lptv-progress-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        #lptv-error-list p {
            margin: 5px 0;
            padding: 5px;
            border-bottom: 1px solid #eee;
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        let processing = false;
        let shouldStop = false;
        let stats = {
            total: 0,
            processed: 0,
            success: 0,
            skipped: 0,
            errors: 0
        };
        let errors = [];
        
        $('#lptv-start-btn').on('click', function() {
            processing = true;
            shouldStop = false;
            stats = {total: 0, processed: 0, success: 0, skipped: 0, errors: 0};
            errors = [];
            
            $('#lptv-start-btn').hide();
            $('#lptv-stop-btn').show();
            $('#lptv-progress').show();
            $('#lptv-stats').show();
            $('#lptv-errors').hide();
            $('#lptv-missing-files').hide();
            
            processBatch(0);
        });
        
        $('#lptv-stop-btn').on('click', function() {
            shouldStop = true;
            $('#lptv-stop-btn').prop('disabled', true).text('Stopping...');
        });
        
        $('#lptv-check-missing-btn').on('click', function() {
            checkMissingFiles();
        });
        
        function processBatch(offset) {
            if (shouldStop) {
                finishProcessing('Stopped by user');
                return;
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'lptv_process_images',
                    offset: offset,
                    nonce: '<?php echo wp_create_nonce('lptv_update_images'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        let data = response.data;
                        
                        if (offset === 0) {
                            stats.total = data.total;
                            $('#stat-total').text(data.total);
                        }
                        
                        stats.processed += data.processed;
                        stats.success += data.success;
                        stats.skipped += data.skipped;
                        stats.errors += data.errors;
                        
                        if (data.error_messages) {
                            errors = errors.concat(data.error_messages);
                        }
                        
                        updateStats();
                        
                        let percent = Math.round((stats.processed / stats.total) * 100);
                        $('#lptv-progress-bar').css('width', percent + '%').text(percent + '%');
                        $('#lptv-progress-text').text('Processing: ' + stats.processed + ' / ' + stats.total);
                        
                        if (data.has_more && !shouldStop) {
                            processBatch(data.next_offset);
                        } else {
                            finishProcessing('Completed successfully!');
                        }
                    } else {
                        alert('Error: ' + response.data.message);
                        finishProcessing('Error occurred');
                    }
                },
                error: function() {
                    alert('AJAX error occurred');
                    finishProcessing('AJAX error');
                }
            });
        }
        
        function updateStats() {
            $('#stat-processed').text(stats.processed);
            $('#stat-success').text(stats.success);
            $('#stat-skipped').text(stats.skipped);
            $('#stat-errors').text(stats.errors);
            
            if (errors.length > 0) {
                $('#lptv-errors').show();
                let html = '';
                errors.forEach(function(err) {
                    html += '<p>' + err + '</p>';
                });
                $('#lptv-error-list').html(html);
            }
        }
        
        function finishProcessing(message) {
            processing = false;
            $('#lptv-start-btn').show();
            $('#lptv-stop-btn').hide().prop('disabled', false).text('Stop');
            $('#lptv-status').html('<p><strong>' + message + '</strong></p>');
            $('#lptv-progress-bar').css('width', '100%').text('100%');
        }
        
        function checkMissingFiles() {
            $('#lptv-check-missing-btn').prop('disabled', true).text('Checking...');
            $('#lptv-missing-files').hide();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'lptv_check_missing',
                    nonce: '<?php echo wp_create_nonce('lptv_check_missing'); ?>'
                },
                success: function(response) {
                    $('#lptv-check-missing-btn').prop('disabled', false).text('Check Missing Files');
                    
                    if (response.success) {
                        let data = response.data;
                        let html = '<p><strong>Total: ' + data.total + ' files</strong></p>';
                        
                        if (data.missing.length > 0) {
                            html += '<p style="color: red;">Missing ' + data.missing.length + ' files:</p><ul>';
                            data.missing.forEach(function(item) {
                                html += '<li>' + item.file + ' (used by ' + item.count + ' product' + (item.count > 1 ? 's' : '') + ')</li>';
                            });
                            html += '</ul>';
                        } else {
                            html += '<p style="color: green;">✓ All image files exist!</p>';
                        }
                        
                        $('#lptv-missing-list').html(html);
                        $('#lptv-missing-files').show();
                    }
                }
            });
        }
    });
    </script>
    <?php
}

// AJAX handler for processing images
add_action('wp_ajax_lptv_process_images', 'lptv_ajax_process_images');
function lptv_ajax_process_images() {
    check_ajax_referer('lptv_update_images', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Access denied']);
    }
    
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    
    $batch_size = 20; // Smaller batches for production
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    
    // Get products
    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => $batch_size,
        'offset'         => $offset,
        'tax_query'      => array(
            array(
                'taxonomy' => 'product_type',
                'field'    => 'slug',
                'terms'    => 'lptvplate',
            ),
        ),
    );
    
    $products = get_posts($args);
    
    // Get total count on first batch
    if ($offset === 0) {
        $total_args = $args;
        $total_args['posts_per_page'] = -1;
        $total_args['fields'] = 'ids';
        $total = count(get_posts($total_args));
    } else {
        $total = 0;
    }
    
    $success = 0;
    $skipped = 0;
    $errors = 0;
    $error_messages = array();
    
    foreach ($products as $post) {
        $product = wc_get_product($post->ID);
        
        if (!$product || $product->get_type() != 'lptvplate') {
            continue;
        }
        
        $product_id = $product->get_id();
        
        // Check if already has proper featured image
        $current_thumbnail_id = get_post_thumbnail_id($product_id);
        
        if ($current_thumbnail_id && is_numeric($current_thumbnail_id) && get_post($current_thumbnail_id)) {
            $skipped++;
            continue;
        }
        
        // Get the image path
        $template_id = $product->get_meta('_plate_template_id', true);
        $products_image = $product->get_meta('_plate_products_image', true);
        
        if (empty($template_id) && empty($products_image)) {
            $skipped++;
            continue;
        }
        
        // Build image path
        $image_filename = $products_image ? $products_image : strtolower($template_id) . '.gif';
        $image_path = ABSPATH . 'wp-content/plugins/lptv-plates/images/' . $image_filename;
        
        // Check if file exists
        if (!file_exists($image_path)) {
            $error_messages[] = "#{$product_id}: File not found - {$image_filename}";
            $errors++;
            continue;
        }
        
        // Upload the image
        try {
            global $wpdb;
            $existing_attachment = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'attachment' LIMIT 1",
                    pathinfo($image_filename, PATHINFO_FILENAME)
                )
            );
            
            if ($existing_attachment) {
                $attachment_id = $existing_attachment;
            } else {
                $wp_upload_dir = wp_upload_dir();
                $file_type = wp_check_filetype($image_filename);
                $new_filename = wp_unique_filename($wp_upload_dir['path'], $image_filename);
                $upload_file = $wp_upload_dir['path'] . '/' . $new_filename;
                
                if (!copy($image_path, $upload_file)) {
                    throw new Exception("Failed to copy file");
                }
                
                $attachment = array(
                    'guid'           => $wp_upload_dir['url'] . '/' . $new_filename,
                    'post_mime_type' => $file_type['type'],
                    'post_title'     => sanitize_file_name(pathinfo($image_filename, PATHINFO_FILENAME)),
                    'post_content'   => '',
                    'post_status'    => 'inherit'
                );
                
                $attachment_id = wp_insert_attachment($attachment, $upload_file, $product_id);
                
                if (is_wp_error($attachment_id)) {
                    throw new Exception($attachment_id->get_error_message());
                }
                
                $attach_data = wp_generate_attachment_metadata($attachment_id, $upload_file);
                wp_update_attachment_metadata($attachment_id, $attach_data);
                update_post_meta($attachment_id, '_wp_attachment_image_alt', $product->get_name());
            }
            
            set_post_thumbnail($product_id, $attachment_id);
            
            $old_thumbnail = get_post_meta($product_id, '_thumbnail_id', true);
            if ($old_thumbnail && strpos($old_thumbnail, '/wp-content/') !== false) {
                delete_post_meta($product_id, '_thumbnail_id', $old_thumbnail);
            }
            
            $success++;
            
        } catch (Exception $e) {
            $error_messages[] = "#{$product_id}: {$e->getMessage()}";
            $errors++;
        }
    }
    
    wp_send_json_success(array(
        'total' => $total,
        'processed' => count($products),
        'success' => $success,
        'skipped' => $skipped,
        'errors' => $errors,
        'error_messages' => $error_messages,
        'has_more' => count($products) === $batch_size,
        'next_offset' => $offset + $batch_size
    ));
}

// AJAX handler for checking missing files
add_action('wp_ajax_lptv_check_missing', 'lptv_ajax_check_missing');
function lptv_ajax_check_missing() {
    check_ajax_referer('lptv_check_missing', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Access denied']);
    }
    
    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'tax_query'      => array(
            array(
                'taxonomy' => 'product_type',
                'field'    => 'slug',
                'terms'    => 'lptvplate',
            ),
        ),
    );
    
    $product_ids = get_posts($args);
    $missing_files = array();
    
    foreach ($product_ids as $product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product || $product->get_type() != 'lptvplate') {
            continue;
        }
        
        $template_id = $product->get_meta('_plate_template_id', true);
        $products_image = $product->get_meta('_plate_products_image', true);

        if (empty($template_id) && empty($products_image)) {
            continue;
        }
        
        $image_filename = $products_image ? $products_image : strtolower($template_id) . '.gif';
        $image_path = ABSPATH . 'wp-content/plugins/lptv-plates/images/' . $image_filename;
        
        if (!file_exists($image_path)) {
            if (!isset($missing_files[$image_filename])) {
                $missing_files[$image_filename] = 0;
            }
            $missing_files[$image_filename]++;
        }
    }
    
    $missing_list = array();
    foreach ($missing_files as $file => $count) {
        $missing_list[] = array('file' => $file, 'count' => $count);
    }
    
    wp_send_json_success(array(
        'total' => count($product_ids),
        'missing' => $missing_list
    ));
}
