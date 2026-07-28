#!/usr/bin/php
<?php

/**
 * sync orders from legacy ZenCart system to WooCommerce HPOS
 * 
 * features:
 * - HPOS ONLY - uses wc_orders, wc_orders_meta, wc_order_product_lookup tables
 * - no legacy post/postmeta - fully optimized for HPOS
 * - sets new users role to "customer"
 * - updates existing orders and customers with latest data
 * - adds customers to wc_customer_lookup table
 * - maps order statuses: 1=pending, 2=processing, 3=completed, 4=update, 5=shipped, 6=cancel
 * - identifies existing orders using _legacy_order_id in wc_orders_meta
 * - maps products by _plate_products_id or _plate_template_id (case insensitive)
 * - syncs order items and notes
 * - requires WooCommerce HPOS to be enabled
 * 
 * usage:
 * php sync-orders.php [order_id] [options]
 * 
 * options:
 * --debug     enable debug mode
 * --limit=N   limit number of orders to process (default: 50)
 * --offset=N  offset for pagination (default: 0)
 * --all       process all orders until no more data
 * 
 * examples:
 * php sync-orders.php              # sync 50 orders
 * php sync-orders.php 140637       # sync only order 140637
 * php sync-orders.php --limit=100  # sync 100 orders
 * php sync-orders.php --all        # sync all orders
 * php sync-orders.php --all --offset=75000  # sync all orders starting from offset 75000
 * php sync-orders.php --debug      # sync with debug output
 */

require_once dirname(__DIR__, 3) . '/wp-load.php';

// database connection
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');

// enable mysqli error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// config
$token = '3vODTOl3JS8RKYujTw7H9LtVwQKhJTFCqvsZi27dWjfvgfTt8dw5DmS7zX9RasiT';
//$apiUrl = 'http://lptv.local/api/sync-api.php?secret=' . $token;
// Disabled locally: was 'https://www.licenseplates.tv/migration-api/sync-api.php?secret=' . $token;
// This script must never call the production migration API from a local/dev copy.
$apiUrl = 'http://disabled-locally.invalid/sync-api.php?secret=' . $token;
$debug = false;
$singleOrderId = null;
$limit = 50;
$offset = 0;
$processAll = false;

// parse cli arguments
$args = array_slice($argv, 1);
foreach ($args as $arg) {
    if ($arg === '--debug') {
        $debug = true;
    } elseif ($arg === '--all') {
        $processAll = true;
    } elseif (strpos($arg, '--limit=') === 0) {
        $limit = (int)substr($arg, 8);
    } elseif (strpos($arg, '--offset=') === 0) {
        $offset = (int)substr($arg, 9);
    } elseif (is_numeric($arg)) {
        $singleOrderId = (int)$arg;
        $debug = true; // auto enable debug for single order
    }
}

// disable buffering for real-time output
if (ob_get_level()) {
    while (ob_get_level()) {
        ob_end_flush();
    }
}

echo "=== Order Sync Script ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

if ($singleOrderId) {
    echo "Debug mode: Processing single order #{$singleOrderId}\n\n";
    syncSingleOrder($singleOrderId, $apiUrl, $mysqli, $debug);
} elseif ($processAll) {
    echo "Processing ALL orders starting from offset {$offset}...\n\n";
    syncAllOrders($limit, $offset, $apiUrl, $mysqli, $debug);
} else {
    echo "Processing orders (limit: {$limit}, offset: {$offset})\n\n";
    syncOrders($limit, $offset, $apiUrl, $mysqli, $debug);
}

echo "\n\nCompleted at: " . date('Y-m-d H:i:s') . "\n";
$mysqli->close();

/**
 * sync single order
 */
function syncSingleOrder($orderId, $apiUrl, $mysqli, $debug) {
    $url = $apiUrl . '&action=get_order&order_id=' . $orderId;
    
    if ($debug) {
        echo "Fetching order from API: {$url}\n";
    }
    
    // use cURL for better compatibility with HTTPS
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // disable SSL verification for local dev
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response === false || $httpCode !== 200) {
        echo "ERROR: Failed to fetch order from API\n";
        if ($curlError) {
            echo "cURL error: " . $curlError . "\n";
        }
        if ($httpCode !== 200) {
            echo "HTTP code: " . $httpCode . "\n";
        }
        return;
    }
    
    $data = json_decode($response, true);
    if (!$data || !$data['success']) {
        echo "ERROR: Invalid API response\n";
        return;
    }
    
    $order = $data['data'];
    
    try {
        $result = processOrder($order, $mysqli, $debug);
        
        if ($result['created']) {
            echo "\nOrder #{$orderId} successfully CREATED as WC Order #{$result['wc_order_id']}\n";
        } else {
            echo "\nOrder #{$orderId} successfully UPDATED as WC Order #{$result['wc_order_id']}\n";
        }
    } catch (Exception $e) {
        echo "\nERROR processing order #{$orderId}: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
}

/**
 * sync all orders (loop until no more data)
 */
function syncAllOrders($batchSize, $startOffset, $apiUrl, $mysqli, $debug) {
    $offset = $startOffset;
    $totalProcessed = 0;
    $batchNumber = 1;
    
    while (true) {
        echo "--- Batch #{$batchNumber} (offset: {$offset}, limit: {$batchSize}) ---\n";
        
        $url = $apiUrl . '&action=get_orders&limit=' . $batchSize . '&offset=' . $offset;
        
        if ($debug) {
            echo "Fetching orders from API: {$url}\n";
        }
        
        // use cURL for better compatibility with HTTPS
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // disable SSL verification for local dev
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false || $httpCode !== 200) {
            echo "ERROR: Failed to fetch orders from API\n";
            if ($curlError) {
                echo "cURL error: " . $curlError . "\n";
            }
            if ($httpCode !== 200) {
                echo "HTTP code: " . $httpCode . "\n";
            }
            break;
        }
        
        $data = json_decode($response, true);
        if (!$data || !$data['success']) {
            echo "ERROR: Invalid API response\n";
            break;
        }
        
        $orders = $data['data'];
        $count = count($orders);
        
        if ($count === 0) {
            echo "No more orders to process.\n";
            break;
        }
        
        echo "Processing {$count} orders...\n";
        
        foreach ($orders as $order) {
        try {
            $result = processOrder($order, $mysqli, $debug);
            $totalProcessed++;
            
            $action = $result['created'] ? 'CREATED' : 'UPDATED';
            $time = isset($result['execution_time']) ? " ({$result['execution_time']}s)" : '';
            echo "  #{$order['orders_id']} -> {$action} as WC Order #{$result['wc_order_id']}{$time}\n";
        } catch (Exception $e) {
            echo "  ERROR processing order #{$order['orders_id']}: " . $e->getMessage() . "\n";
        }
        }
        
        echo "Batch #{$batchNumber} completed. Total processed: {$totalProcessed}\n\n";
        
        // if we got less than the batch size, we're done
        if ($count < $batchSize) {
            echo "Reached end of orders (got {$count} < {$batchSize}).\n";
            break;
        }
        
        $offset += $batchSize;
        $batchNumber++;
        
        // small delay to avoid overwhelming the API
        sleep(1);
    }
    
    echo "\n=== Summary ===\n";
    echo "Total orders processed: {$totalProcessed}\n";
    echo "Total batches: {$batchNumber}\n";
}

/**
 * sync multiple orders
 */
function syncOrders($limit, $offset, $apiUrl, $mysqli, $debug) {
    $url = $apiUrl . '&action=get_orders&limit=' . $limit . '&offset=' . $offset;
    
    if ($debug) {
        echo "Fetching orders from API: {$url}\n";
    }
    
    // use cURL for better compatibility with HTTPS
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // disable SSL verification for local dev
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response === false || $httpCode !== 200) {
        echo "ERROR: Failed to fetch orders from API\n";
        if ($curlError) {
            echo "cURL error: " . $curlError . "\n";
        }
        if ($httpCode !== 200) {
            echo "HTTP code: " . $httpCode . "\n";
        }
        return;
    }
    
    $data = json_decode($response, true);
    if (!$data || !$data['success']) {
        echo "ERROR: Invalid API response\n";
        return;
    }
    
    $orders = $data['data'];
    $total = count($orders);
    
    echo "Found {$total} orders to process\n\n";
    
    $processed = 0;
    $created = 0;
    $skipped = 0;
    $errors = 0;
    
    foreach ($orders as $order) {
        $processed++;
        
        try {
            $result = processOrder($order, $mysqli, $debug);
            
            $time = isset($result['execution_time']) ? " ({$result['execution_time']}s)" : '';
            if ($result['created']) {
                $created++;
                echo "[{$processed}/{$total}] Order #{$order['orders_id']} -> #{$result['wc_order_id']} CREATED{$time}\n";
            } else {
                $skipped++;
                echo "[{$processed}/{$total}] Order #{$order['orders_id']} -> #{$result['wc_order_id']} UPDATED{$time}\n";
            }
        } catch (Exception $e) {
            $errors++;
            echo "[{$processed}/{$total}] Order #{$order['orders_id']} ERROR: " . $e->getMessage() . "\n";
        }
        
        if ($debug && $processed % 10 == 0) {
            echo "\nProgress: {$processed}/{$total} (Created: {$created}, Updated: {$skipped}, Errors: {$errors})\n\n";
        }
    }
    
    echo "\n=== Summary ===\n";
    echo "Total processed: {$processed}\n";
    echo "Created: {$created}\n";
    echo "Updated (already existed): {$skipped}\n";
    echo "Errors: {$errors}\n";
}

/**
 * process single order
 */
function processOrder($order, $mysqli, $debug) {
    global $wpdb;
    
    $startTime = microtime(true);
    $legacyOrderId = $order['orders_id'];
    
    if ($debug) {
        echo "\n--- Processing Order #{$legacyOrderId} ---\n";
    }
    
    // get or create customer
    if ($debug) {
        echo "Getting or creating customer...\n";
    }
    
    $customerId = getOrCreateCustomer($order['customer'], $mysqli, $debug);
    
    if ($debug) {
        echo "Customer ID: {$customerId}\n";
    }
    
    // check if order already exists in HPOS
    $existingOrderId = getExistingOrderHPOS($legacyOrderId, $mysqli);
    $isUpdate = false;
    
    if ($existingOrderId) {
        if ($debug) {
            echo "Order exists as WC Order #{$existingOrderId}, updating...\n";
        }
        $wcOrderId = $existingOrderId;
        $isUpdate = true;
    } else {
        if ($debug) {
            echo "Creating new order in HPOS...\n";
        }
        $wcOrderId = null; // will be created in HPOS sync
    }
    
    // sync to HPOS tables (wc_orders, wc_orders_meta, wc_order_product_lookup)
    if ($debug) {
        echo "Syncing to HPOS tables...\n";
    }
    $wcOrderId = syncToHPOSTables($wcOrderId, $legacyOrderId, $customerId, $order, $mysqli, $debug);
    
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 3);
    
    if ($debug) {
        $action = $isUpdate ? 'updated' : 'synced';
        echo "Order #{$legacyOrderId} successfully {$action} as #{$wcOrderId}\n";
        echo "Execution time: {$executionTime}s\n";
    }
    
    return [
        'created' => !$isUpdate, 
        'wc_order_id' => $wcOrderId,
        'execution_time' => $executionTime
    ];
}

/**
 * check if order already exists in HPOS
 */
function getExistingOrderHPOS($legacyOrderId, $mysqli) {
    global $wpdb;
    
    // check in wc_orders_meta for legacy order ID
    $stmt = $mysqli->prepare("
        SELECT order_id 
        FROM {$wpdb->prefix}wc_orders_meta 
        WHERE meta_key = '_legacy_order_id' 
        AND meta_value = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $legacyOrderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row ? $row['order_id'] : null;
}

/**
 * get or create customer
 */
function getOrCreateCustomer($customer, $mysqli, $debug) {
    global $wpdb;
    
    if (!$customer) {
        return 0; // guest order
    }
    
    $email = $customer['customers_email_address'];
    $legacyCustomerId = $customer['customers_id'];
    
    // check if customer already exists by email
    $stmt = $mysqli->prepare("
        SELECT ID 
        FROM {$wpdb->prefix}users 
        WHERE user_email = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row) {
        if ($debug) {
            echo "Customer exists: {$email} (User ID: {$row['ID']}), updating...\n";
        }
        // update existing customer
        updateCustomer($row['ID'], $customer, $mysqli, $debug);
        if($debug) {
            echo "Customer updated: {$email} (User ID: {$row['ID']})\n";
        }
        if($debug) {
            echo "Updating customer lookup...\n";
        }
        updateCustomerLookup($row['ID'], $customer, $mysqli);
        if($debug) {
            echo "Customer lookup updated\n";
        }
        return $row['ID'];
    }
    
    // create new customer
    if ($debug) {
        echo "Creating new customer: {$email}\n";
    }
    
    $firstName = $customer['customers_firstname'] ?: '';
    $lastName = $customer['customers_lastname'] ?: '';
    $username = generateUsername($email, $mysqli);
    
    // insert user
    $stmt = $mysqli->prepare("
        INSERT INTO {$wpdb->prefix}users 
        (user_login, user_email, user_registered, display_name, user_nicename) 
        VALUES (?, ?, NOW(), ?, ?)
    ");
    $displayName = trim($firstName . ' ' . $lastName);
    $nicename = sanitize_title($displayName ?: $username);
    $stmt->bind_param('ssss', $username, $email, $displayName, $nicename);
    $stmt->execute();
    $userId = $stmt->insert_id;
    $stmt->close();
    
    // add user meta
    $metaData = [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'nickname' => $username,
        '_legacy_customer_id' => $legacyCustomerId,
        'billing_first_name' => $firstName,
        'billing_last_name' => $lastName,
        'billing_email' => $email,
        'billing_phone' => $customer['customers_telephone'] ?: '',
    ];
    
    foreach ($metaData as $key => $value) {
        $stmt = $mysqli->prepare("
            INSERT INTO {$wpdb->prefix}usermeta (user_id, meta_key, meta_value) 
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param('iss', $userId, $key, $value);
        $stmt->execute();
        $stmt->close();
    }
    
    // set user role to customer
    $capabilities = serialize(['customer' => true]);
    $capabilitiesKey = $wpdb->prefix . 'capabilities';
    $userLevelKey = $wpdb->prefix . 'user_level';
    
    if ($debug) {
        echo "Setting user role capabilities: " . $capabilities . "\n";
        echo "Capabilities key: {$capabilitiesKey}\n";
    }
    
    $stmt = $mysqli->prepare("
        INSERT INTO {$wpdb->prefix}usermeta (user_id, meta_key, meta_value) 
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param('iss', $userId, $capabilitiesKey, $capabilities);
    if (!$stmt->execute()) {
        throw new Exception("Failed to set user capabilities: " . $stmt->error);
    }
    $stmt->close();
    
    // set user level (legacy, but still needed)
    $stmt = $mysqli->prepare("
        INSERT INTO {$wpdb->prefix}usermeta (user_id, meta_key, meta_value) 
        VALUES (?, ?, '0')
    ");
    $stmt->bind_param('is', $userId, $userLevelKey);
    if (!$stmt->execute()) {
        throw new Exception("Failed to set user level: " . $stmt->error);
    }
    $stmt->close();
    
    // add to wc_customer_lookup table
    addCustomerToLookup($userId, $customer, $mysqli);
    
    if ($debug) {
        echo "Created customer User ID: {$userId} with role 'customer'\n";
    }
    
    return $userId;
}

/**
 * update existing customer
 */
function updateCustomer($userId, $customer, $mysqli, $debug) {
    global $wpdb;
    
    if ($debug) {
        echo "Updating customer: {$userId}\n";
    }
    $firstName = $customer['customers_firstname'] ?: '';
    $lastName = $customer['customers_lastname'] ?: '';
    $email = $customer['customers_email_address'];
    $legacyCustomerId = $customer['customers_id'];
    
    // update user table
    $displayName = trim($firstName . ' ' . $lastName);
    $stmt = $mysqli->prepare("
        UPDATE {$wpdb->prefix}users 
        SET display_name = ?, user_email = ?
        WHERE ID = ?
    ");
    $stmt->bind_param('ssi', $displayName, $email, $userId);
    $stmt->execute();
    $stmt->close();
    
    if ($debug) {
        echo "Updating user meta...\n";
    }

    // update user meta
    $metaData = [
        'first_name' => $firstName,
        'last_name' => $lastName,
        '_legacy_customer_id' => $legacyCustomerId,
        'billing_first_name' => $firstName,
        'billing_last_name' => $lastName,
        'billing_email' => $email,
        'billing_phone' => $customer['customers_telephone'] ?: '',
    ];
    
    foreach ($metaData as $key => $value) {
        if ($debug) {
            echo "Updating user meta: {$key} = {$value}\n";
        }
        // delete old value
        $stmt = $mysqli->prepare("
            DELETE FROM {$wpdb->prefix}usermeta 
            WHERE user_id = ? AND meta_key = ?
        ");
        $stmt->bind_param('is', $userId, $key);
        $stmt->execute();
        $stmt->close();
        
        // insert new value
        $stmt = $mysqli->prepare("
            INSERT INTO {$wpdb->prefix}usermeta (user_id, meta_key, meta_value) 
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param('iss', $userId, $key, $value);
        $stmt->execute();
        $stmt->close();
    }
    
    // ensure customer role is set (in case it was missing)
    if ($debug) {
        echo "Ensuring customer role is set...\n";
    }
    $capabilities = serialize(['customer' => true]);
    $capabilitiesKey = $wpdb->prefix . 'capabilities';
    
    if ($debug) {
        echo "Capabilities key: {$capabilitiesKey}\n";
    }
    
    // delete old capabilities
    $stmt = $mysqli->prepare("
        DELETE FROM {$wpdb->prefix}usermeta 
        WHERE user_id = ? AND meta_key = ?
    ");
    $stmt->bind_param('is', $userId, $capabilitiesKey);
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete old capabilities: " . $stmt->error);
    }
    $stmt->close();
    
    if ($debug) {
        echo "Inserting new capabilities...\n";
    }
    // insert new capabilities
    $stmt = $mysqli->prepare("
        INSERT INTO {$wpdb->prefix}usermeta (user_id, meta_key, meta_value) 
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param('iss', $userId, $capabilitiesKey, $capabilities);
    if (!$stmt->execute()) {
        throw new Exception("Failed to insert new capabilities: " . $stmt->error);
    }
    $stmt->close();
    
    if ($debug) {
        echo "Capabilities updated successfully\n";
    }
    
    // update wc_customer_lookup
    updateCustomerLookup($userId, $customer, $mysqli);
    
    if ($debug) {
        echo "Updated customer User ID: {$userId} with customer role\n";
    }
}

/**
 * generate unique username
 */
function generateUsername($email, $mysqli) {
    global $wpdb;
    
    $base = strstr($email, '@', true);
    $base = sanitize_user($base, true);
    $username = $base;
    $counter = 1;
    
    while (true) {
        $stmt = $mysqli->prepare("
            SELECT ID 
            FROM {$wpdb->prefix}users 
            WHERE user_login = ?
            LIMIT 1
        ");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        if (!$exists) {
            break;
        }
        
        $username = $base . $counter;
        $counter++;
    }
    
    return $username;
}

/**
 * add customer to wc_customer_lookup table
 */
function addCustomerToLookup($userId, $customer, $mysqli) {
    global $wpdb;
    
    // check if already exists
    $stmt = $mysqli->prepare("
        SELECT customer_id 
        FROM {$wpdb->prefix}wc_customer_lookup 
        WHERE customer_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    
    if ($exists) {
        return; // already in lookup table
    }
    
    $username = '';
    $stmt = $mysqli->prepare("
        SELECT user_login 
        FROM {$wpdb->prefix}users 
        WHERE ID = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if ($row) {
        $username = $row['user_login'];
    }
    $stmt->close();
    
    $firstName = $customer['customers_firstname'] ?: '';
    $lastName = $customer['customers_lastname'] ?: '';
    $email = $customer['customers_email_address'];
    
    // handle invalid dates from legacy system
    $dateRegistered = $customer['created_at'] ?? null;
    if (!$dateRegistered || $dateRegistered == '0000-00-00 00:00:00' || strtotime($dateRegistered) === false) {
        // use account creation date from info table as fallback
        $dateRegistered = $customer['customers_info_date_account_created'] ?? null;
        if (!$dateRegistered || $dateRegistered == '0000-00-00 00:00:00' || strtotime($dateRegistered) === false) {
            // last resort: use current date
            $dateRegistered = date('Y-m-d H:i:s');
        }
    }
    
    $dateLastActive = $customer['customers_info_date_of_last_logon'] ?? null;
    if ($dateLastActive == '0000-00-00 00:00:00' || strtotime($dateLastActive) === false) {
        $dateLastActive = null;
    }
    
    $country = ''; // will be updated from orders
    $postcode = '';
    $city = '';
    $state = '';
    
    // insert into wc_customer_lookup (only basic fields)
    $stmt = $mysqli->prepare("
        INSERT INTO {$wpdb->prefix}wc_customer_lookup 
        (customer_id, user_id, username, first_name, last_name, email, 
         date_registered, date_last_active, country, postcode, city, state) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('iissssssssss', 
        $userId, $userId, $username, $firstName, $lastName, $email,
        $dateRegistered, $dateLastActive, $country, $postcode, $city, $state
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to insert customer lookup: " . $stmt->error);
    }
    
    $stmt->close();
}

/**
 * update customer in wc_customer_lookup table
 */
function updateCustomerLookup($userId, $customer, $mysqli) {
    global $wpdb;
    
    // check if customer exists in lookup table first
    $checkStmt = $mysqli->prepare("
        SELECT customer_id FROM {$wpdb->prefix}wc_customer_lookup 
        WHERE customer_id = ?
    ");
    $checkStmt->bind_param('i', $userId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $exists = $result->num_rows > 0;
    $checkStmt->close();
    
    if (!$exists) {
        // if not exists, create it
        addCustomerToLookup($userId, $customer, $mysqli);
        return;
    }
    
    $firstName = $customer['customers_firstname'] ?: '';
    $lastName = $customer['customers_lastname'] ?: '';
    $email = $customer['customers_email_address'];
    
    // handle invalid dates
    $dateLastActive = $customer['customers_info_date_of_last_logon'] ?? null;
    if ($dateLastActive == '0000-00-00 00:00:00' || strtotime($dateLastActive) === false) {
        $dateLastActive = null;
    }
    
    // update wc_customer_lookup (only update basic fields, not counts)
    $stmt = $mysqli->prepare("
        UPDATE {$wpdb->prefix}wc_customer_lookup 
        SET first_name = ?, last_name = ?, email = ?, date_last_active = ?
        WHERE customer_id = ?
    ");
    $stmt->bind_param('ssssi', 
        $firstName, $lastName, $email, $dateLastActive, $userId
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update customer lookup: " . $stmt->error);
    }
    
    $stmt->close();
}

/**
 * create order
 */
function createOrder($order, $customerId, $mysqli, $debug) {
    global $wpdb;
    
    $postDate = $order['date_purchased'] ?: date('Y-m-d H:i:s');
    $postStatus = mapOrderStatus($order['orders_status']);
    
    // insert post (using 1 as default post_author, will use meta for customer)
    $stmt = $mysqli->prepare("
        INSERT INTO {$wpdb->prefix}posts 
        (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, 
         post_status, comment_status, ping_status, post_password, post_name, to_ping, 
         pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, 
         guid, menu_order, post_type, post_mime_type, comment_count) 
        VALUES 
        (1, ?, ?, '', ?, '', ?, 'open', 'closed', '', ?, '', '', ?, ?, '', 0, '', 0, 'shop_order_placehold', '', 0)
    ");
    
    $postDateGmt = get_gmt_from_date($postDate);
    $postTitle = 'Order &ndash; ' . date('F j, Y @ h:i A', strtotime($postDate));
    $postName = 'wc-order-' . uniqid();
    
    if ($debug) {
        echo "Order post details:\n";
        echo "  Date: {$postDate} / {$postDateGmt}\n";
        echo "  Title: {$postTitle}\n";
        echo "  Status: {$postStatus}\n";
        echo "  Name: {$postName}\n";
    }
    
    $stmt->bind_param('sssssss', 
        $postDate, $postDateGmt, $postTitle, 
        $postStatus, $postName, $postDate, $postDateGmt
    );
    
    if ($debug) {
        echo "Executing INSERT query...\n";
    }
    
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $errorNo = $stmt->errno;
        $stmt->close();
        throw new Exception("Failed to insert order post (Error #{$errorNo}): " . $error);
    }
    
    $orderId = $stmt->insert_id;
    
    if ($debug) {
        echo "INSERT executed, insert_id: {$orderId}\n";
    }
    
    $stmt->close();
    
    if (!$orderId || $orderId == 0) {
        throw new Exception("Order post insert returned 0 or null ID");
    }
    
    // verify the order was actually created
    $verifyStmt = $mysqli->prepare("SELECT ID FROM {$wpdb->prefix}posts WHERE ID = ?");
    $verifyStmt->bind_param('i', $orderId);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result();
    $exists = $verifyResult->num_rows > 0;
    $verifyStmt->close();
    
    if (!$exists) {
        throw new Exception("Order post #{$orderId} was not found after insert - possible database issue");
    }
    
    if ($debug) {
        echo "Order post created and verified with ID: {$orderId}\n";
    }
    
    return $orderId;
}

/**
 * update existing order
 */
function updateOrder($orderId, $order, $customerId, $mysqli, $debug) {
    global $wpdb;
    
    $postDate = $order['date_purchased'] ?: date('Y-m-d H:i:s');
    $postDateGmt = get_gmt_from_date($postDate);
    $postStatus = mapOrderStatus($order['orders_status']);
    $postTitle = 'Order &ndash; ' . date('F j, Y @ h:i A', strtotime($postDate));
    
    // update post
    $stmt = $mysqli->prepare("
        UPDATE {$wpdb->prefix}posts 
        SET post_date = ?, post_date_gmt = ?, post_title = ?, 
            post_status = ?, post_modified = ?, post_modified_gmt = ?
        WHERE ID = ?
    ");
    $stmt->bind_param('ssssssi', 
        $postDate, $postDateGmt, $postTitle, 
        $postStatus, $postDate, $postDateGmt, $orderId
    );
    $stmt->execute();
    $stmt->close();
    
    if ($debug) {
        echo "Updated order post #{$orderId}\n";
    }
}

/**
 * delete order meta
 */
function deleteOrderMeta($orderId, $mysqli) {
    global $wpdb;
    
    $stmt = $mysqli->prepare("
        DELETE FROM {$wpdb->prefix}postmeta 
        WHERE post_id = ?
    ");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $stmt->close();
}

/**
 * delete order items
 */
function deleteOrderItems($orderId, $mysqli) {
    global $wpdb;
    
    // get all order item IDs
    $stmt = $mysqli->prepare("
        SELECT order_item_id 
        FROM {$wpdb->prefix}woocommerce_order_items 
        WHERE order_id = ?
    ");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $itemIds = [];
    while ($row = $result->fetch_assoc()) {
        $itemIds[] = $row['order_item_id'];
    }
    $stmt->close();
    
    // delete order item meta
    if (!empty($itemIds)) {
        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $stmt = $mysqli->prepare("
            DELETE FROM {$wpdb->prefix}woocommerce_order_itemmeta 
            WHERE order_item_id IN ($placeholders)
        ");
        $types = str_repeat('i', count($itemIds));
        $stmt->bind_param($types, ...$itemIds);
        $stmt->execute();
        $stmt->close();
    }
    
    // delete order items
    $stmt = $mysqli->prepare("
        DELETE FROM {$wpdb->prefix}woocommerce_order_items 
        WHERE order_id = ?
    ");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $stmt->close();
}

/**
 * delete order totals
 */
function deleteOrderTotals($orderId, $mysqli) {
    global $wpdb;
    
    // get all total item IDs
    $stmt = $mysqli->prepare("
        SELECT order_item_id 
        FROM {$wpdb->prefix}woocommerce_order_items 
        WHERE order_id = ? AND order_item_type IN ('shipping', 'tax', 'coupon', 'fee')
    ");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $itemIds = [];
    while ($row = $result->fetch_assoc()) {
        $itemIds[] = $row['order_item_id'];
    }
    $stmt->close();
    
    // delete item meta
    if (!empty($itemIds)) {
        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $stmt = $mysqli->prepare("
            DELETE FROM {$wpdb->prefix}woocommerce_order_itemmeta 
            WHERE order_item_id IN ($placeholders)
        ");
        $types = str_repeat('i', count($itemIds));
        $stmt->bind_param($types, ...$itemIds);
        $stmt->execute();
        $stmt->close();
    }
    
    // delete items
    $stmt = $mysqli->prepare("
        DELETE FROM {$wpdb->prefix}woocommerce_order_items 
        WHERE order_id = ? AND order_item_type IN ('shipping', 'tax', 'coupon', 'fee')
    ");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $stmt->close();
}

/**
 * delete order notes
 */
function deleteOrderNotes($orderId, $mysqli) {
    global $wpdb;
    
    $stmt = $mysqli->prepare("
        DELETE FROM {$wpdb->prefix}comments 
        WHERE comment_post_ID = ? AND comment_type = 'order_note'
    ");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $stmt->close();
}

/**
 * add order meta
 */
function addOrderMeta($orderId, $legacyOrderId, $customerId, $order, $mysqli) {
    global $wpdb;
    
    $meta = [
        '_legacy_order_id' => $legacyOrderId,
        '_legacy_customer_id' => $order['customers_id'],
        '_customer_user' => $customerId,
        '_order_key' => 'wc_order_' . uniqid(),
        '_order_currency' => $order['currency'] ?: 'USD',
        '_prices_include_tax' => 'no',
        '_customer_ip_address' => extractIpAddress($order['ip_address']),
        '_customer_user_agent' => '',
        
        // billing
        '_billing_first_name' => $order['customer']['customers_firstname'] ?? '',
        '_billing_last_name' => $order['customer']['customers_lastname'] ?? '',
        '_billing_company' => $order['billing_company'] ?? '',
        '_billing_address_1' => $order['billing_street_address'] ?? '',
        '_billing_address_2' => $order['billing_suburb'] ?? '',
        '_billing_city' => $order['billing_city'] ?? '',
        '_billing_state' => $order['billing_state'] ?? '',
        '_billing_postcode' => $order['billing_postcode'] ?? '',
        '_billing_country' => mapCountry($order['billing_country'] ?? ''),
        '_billing_email' => $order['customers_email_address'] ?? '',
        '_billing_phone' => $order['customers_telephone'] ?? '',
        
        // shipping
        '_shipping_first_name' => extractFirstName($order['delivery_name'] ?? ''),
        '_shipping_last_name' => extractLastName($order['delivery_name'] ?? ''),
        '_shipping_company' => $order['delivery_company'] ?? '',
        '_shipping_address_1' => $order['delivery_street_address'] ?? '',
        '_shipping_address_2' => $order['delivery_suburb'] ?? '',
        '_shipping_city' => $order['delivery_city'] ?? '',
        '_shipping_state' => $order['delivery_state'] ?? '',
        '_shipping_postcode' => $order['delivery_postcode'] ?? '',
        '_shipping_country' => mapCountry($order['delivery_country'] ?? ''),
        
        // payment
        '_payment_method' => mapPaymentMethod($order['payment_method']),
        '_payment_method_title' => $order['payment_method'],
        
        // shipping method
        '_shipping_method' => $order['shipping_method'],
        
        // totals
        '_order_total' => $order['order_total'] ?: '0.00',
        '_order_tax' => $order['order_tax'] ?: '0.00',
        '_order_shipping' => '0.00',
        '_order_discount' => '0.00',
        '_cart_discount' => '0.00',
        '_cart_discount_tax' => '0.00',
        '_order_shipping_tax' => '0.00',
        
        // other
        '_coupon_code' => $order['coupon_code'] ?? '',
        '_date_completed' => $order['orders_date_finished'] ?? '',
        '_date_paid' => $order['date_purchased'] ?? '',
    ];
    
    // calculate shipping and discount from totals
    if (!empty($order['totals'])) {
        foreach ($order['totals'] as $total) {
            if ($total['class'] === 'ot_shipping') {
                $meta['_order_shipping'] = $total['value'];
            } elseif ($total['class'] === 'ot_coupon') {
                $meta['_order_discount'] = $total['value'];
                $meta['_cart_discount'] = $total['value'];
            }
        }
    }
    
    foreach ($meta as $key => $value) {
        $stmt = $mysqli->prepare("
            INSERT INTO {$wpdb->prefix}postmeta (post_id, meta_key, meta_value) 
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param('iss', $orderId, $key, $value);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * add order items (products)
 */
function addOrderItems($orderId, $products, $mysqli, $debug) {
    global $wpdb;
    
    $orderItemIds = [];
    
    if (empty($products)) {
        return $orderItemIds;
    }
    
    foreach ($products as $product) {
        // find product in woocommerce
        $wcProductId = findWcProduct($product, $mysqli, $debug);
        
        if (!$wcProductId && $debug) {
            echo "WARNING: Product not found for legacy product #{$product['products_id']} (model: {$product['products_model']})\n";
        }
        
        // insert order item
        $stmt = $mysqli->prepare("
            INSERT INTO {$wpdb->prefix}woocommerce_order_items 
            (order_item_name, order_item_type, order_id) 
            VALUES (?, 'line_item', ?)
        ");
        $productName = $product['products_name'];
        $stmt->bind_param('si', $productName, $orderId);
        $stmt->execute();
        $orderItemId = $stmt->insert_id;
        $stmt->close();
        
        // add order item meta
        $itemMeta = [
            '_product_id' => $wcProductId ?: 0,
            '_variation_id' => 0,
            '_qty' => $product['products_quantity'],
            '_tax_class' => '',
            '_line_subtotal' => $product['products_price'],
            '_line_subtotal_tax' => '0.00',
            '_line_total' => $product['final_price'],
            '_line_tax' => $product['products_tax'],
            '_line_tax_data' => serialize([]),
            '_legacy_product_id' => $product['products_id'],
            '_legacy_product_model' => $product['products_model'],
        ];
        
        // add customization data if exists
        if (!empty($product['text1'])) {
            $itemMeta['_plate_text1'] = $product['text1'];
        }
        if (!empty($product['text2'])) {
            $itemMeta['_plate_text2'] = $product['text2'];
        }
        if (!empty($product['font'])) {
            $itemMeta['_plate_font'] = $product['font'];
        }
        if (!empty($product['customization_details'])) {
            $itemMeta['_plate_customization'] = $product['customization_details'];
        }
        
        foreach ($itemMeta as $key => $value) {
            $stmt = $mysqli->prepare("
                INSERT INTO {$wpdb->prefix}woocommerce_order_itemmeta 
                (order_item_id, meta_key, meta_value) 
                VALUES (?, ?, ?)
            ");
            $metaValue = is_array($value) ? serialize($value) : (string)$value;
            $stmt->bind_param('iss', $orderItemId, $key, $metaValue);
            $stmt->execute();
            $stmt->close();
        }
        
        // store the order item ID with product info for later use
        $orderItemIds[] = [
            'order_item_id' => $orderItemId,
            'product_id' => $wcProductId ?: 0,
            'product_qty' => $product['products_quantity'],
            'product_net_revenue' => $product['final_price'],
        ];
    }
    
    return $orderItemIds;
}

/**
 * find woocommerce product
 */
function findWcProduct($product, $mysqli, $debug) {
    global $wpdb;
    
    $legacyProductId = $product['products_id'];
    $legacyProductModel = $product['products_model'];
    
    // try to find by _plate_products_id
    $stmt = $mysqli->prepare("
        SELECT post_id 
        FROM {$wpdb->prefix}postmeta 
        WHERE meta_key = '_plate_products_id' 
        AND meta_value = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $legacyProductId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row) {
        if ($debug) {
            echo "Product found by _plate_products_id: {$row['post_id']}\n";
        }
        return $row['post_id'];
    }
    
    // try to find by _plate_template_id (case insensitive)
    $stmt = $mysqli->prepare("
        SELECT post_id 
        FROM {$wpdb->prefix}postmeta 
        WHERE meta_key = '_plate_template_id' 
        AND LOWER(meta_value) = LOWER(?)
        LIMIT 1
    ");
    $stmt->bind_param('s', $legacyProductModel);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row) {
        if ($debug) {
            echo "Product found by _plate_template_id: {$row['post_id']}\n";
        }
        return $row['post_id'];
    }
    
    return null;
}

/**
 * add order totals
 */
function addOrderTotals($orderId, $order, $mysqli) {
    global $wpdb;
    
    if (empty($order['totals'])) {
        return;
    }
    
    foreach ($order['totals'] as $total) {
        $type = mapTotalType($total['class']);
        
        if (!$type) {
            continue;
        }
        
        // insert order item
        $stmt = $mysqli->prepare("
            INSERT INTO {$wpdb->prefix}woocommerce_order_items 
            (order_item_name, order_item_type, order_id) 
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param('ssi', $total['title'], $type, $orderId);
        $stmt->execute();
        $orderItemId = $stmt->insert_id;
        $stmt->close();
        
        // add meta
        $stmt = $mysqli->prepare("
            INSERT INTO {$wpdb->prefix}woocommerce_order_itemmeta 
            (order_item_id, meta_key, meta_value) 
            VALUES (?, 'cost', ?)
        ");
        $stmt->bind_param('is', $orderItemId, $total['value']);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * add order notes
 */
function addOrderNotes($orderId, $statusHistory, $mysqli, $debug) {
    global $wpdb;
    
    if (empty($statusHistory)) {
        return;
    }
    
    foreach ($statusHistory as $history) {
        if (empty($history['comments'])) {
            continue;
        }
        
        $content = $history['comments'];
        $dateAdded = $history['date_added'];
        $dateAddedGmt = get_gmt_from_date($dateAdded);
        
        $stmt = $mysqli->prepare("
            INSERT INTO {$wpdb->prefix}comments 
            (comment_post_ID, comment_author, comment_author_email, comment_author_url, 
             comment_author_IP, comment_date, comment_date_gmt, comment_content, 
             comment_karma, comment_approved, comment_agent, comment_type, comment_parent, user_id) 
            VALUES 
            (?, 'WooCommerce', '', '', '', ?, ?, ?, 0, 1, '', 'order_note', 0, 0)
        ");
        $stmt->bind_param('isss', $orderId, $dateAdded, $dateAddedGmt, $content);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * sync order to HPOS tables (wc_orders, wc_orders_meta, wc_order_product_lookup)
 */
function syncToHPOSTables($wcOrderId, $legacyOrderId, $customerId, $order, $mysqli, $debug) {
    global $wpdb;
    
    // check if wc_orders table exists
    $tableCheck = $mysqli->query("SHOW TABLES LIKE '{$wpdb->prefix}wc_orders'");
    if ($tableCheck->num_rows == 0) {
        if ($debug) {
            echo "HPOS tables not found, cannot create order\n";
        }
        throw new Exception("HPOS tables not found - WooCommerce HPOS must be enabled");
    }
    
    // prepare order data
    $postDate = $order['date_purchased'] ?: date('Y-m-d H:i:s');
    $postDateGmt = get_gmt_from_date($postDate);
    $status = mapOrderStatus($order['orders_status']); // Keep wc- prefix for wc_orders table
    $currency = $order['currency'] ?: 'USD';
    $total = $order['order_total'] ?: '0.00';
    $tax = $order['order_tax'] ?: '0.00';
    
    // calculate shipping and discount from totals
    $shipping = '0.00';
    $discount = '0.00';
    if (!empty($order['totals'])) {
        foreach ($order['totals'] as $total_item) {
            if ($total_item['class'] === 'ot_shipping') {
                $shipping = $total_item['value'];
            } elseif ($total_item['class'] === 'ot_coupon') {
                $discount = $total_item['value'];
            }
        }
    }
    
    $billingEmail = $order['customers_email_address'] ?? '';
    $billingFirstName = $order['customer']['customers_firstname'] ?? '';
    $billingLastName = $order['customer']['customers_lastname'] ?? '';
    
    if ($debug) {
        echo "Syncing to wc_orders table...\n";
    }
    
    if ($wcOrderId) {
        // update existing HPOS order
        if ($debug) {
            echo "Updating existing order #{$wcOrderId}...\n";
            echo "  Status: {$status}\n";
            echo "  Currency: {$currency}\n";
            echo "  Total: {$total}, Tax: {$tax}\n";
            flush();
        }
        
        $stmt = $mysqli->prepare("
            UPDATE {$wpdb->prefix}wc_orders 
            SET status = ?, currency = ?, date_created_gmt = ?, date_updated_gmt = ?,
                customer_id = ?, billing_email = ?,
                total_amount = ?, tax_amount = ?
            WHERE id = ?
        ");
        
        if ($debug) {
            echo "Binding update parameters (sssssissi - 9 params)...\n";
            flush();
        }
        
        if (!$stmt->bind_param('sssssissi', 
            $status, $currency, $postDateGmt, $postDateGmt,
            $customerId, $billingEmail,
            $total, $tax,
            $wcOrderId
        )) {
            throw new Exception("Failed to bind update parameters: " . $stmt->error);
        }
        
        if ($debug) {
            echo "Executing UPDATE...\n";
            flush();
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update wc_orders: " . $stmt->error);
        }
        $stmt->close();
        
        if ($debug) {
            echo "Updated order #{$wcOrderId} in wc_orders\n";
        }
    } else {
        // insert new HPOS order
        // Note: wc_orders.id is NOT auto_increment, use legacy order ID
        $wcOrderId = $legacyOrderId;
        
        if ($debug) {
            echo "Preparing INSERT statement...\n";
            echo "  Using Legacy Order ID: {$wcOrderId}\n";
            echo "  Status: {$status}\n";
            echo "  Currency: {$currency}\n";
            echo "  Date: {$postDateGmt}\n";
            echo "  Customer ID: {$customerId}\n";
            echo "  Email: {$billingEmail}\n";
            echo "  Total: {$total}\n";
            echo "  Tax: {$tax}\n";
        }
        
        $stmt = $mysqli->prepare("
            INSERT INTO {$wpdb->prefix}wc_orders 
            (id, status, currency, type, date_created_gmt, date_updated_gmt, 
             customer_id, billing_email, total_amount, tax_amount)
            VALUES (?, ?, ?, 'shop_order', ?, ?, ?, ?, ?, ?)
        ");
        
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $mysqli->error);
        }
        
        if ($debug) {
            echo "Statement prepared, binding parameters...\n";
            echo "  Param types: isssssisss (9 params)\n";
            echo "  Variable details:\n";
            echo "    wcOrderId: " . var_export($wcOrderId, true) . " (" . gettype($wcOrderId) . ")\n";
            echo "    status: " . var_export($status, true) . " (" . gettype($status) . ")\n";
            echo "    currency: " . var_export($currency, true) . " (" . gettype($currency) . ")\n";
            echo "    postDateGmt: " . var_export($postDateGmt, true) . " (" . gettype($postDateGmt) . ")\n";
            echo "    customerId: " . var_export($customerId, true) . " (" . gettype($customerId) . ")\n";
            echo "    billingEmail: " . var_export($billingEmail, true) . " (" . gettype($billingEmail) . ")\n";
            echo "    total: " . var_export($total, true) . " (" . gettype($total) . ")\n";
            echo "    tax: " . var_export($tax, true) . " (" . gettype($tax) . ")\n";
            echo "  Calling bind_param...\n";
            flush();
        }
        
        // Check parameter count matches
        $paramCount = substr_count('isssssisss', 'i') + substr_count('isssssisss', 's') + substr_count('isssssisss', 'd');
        if ($debug) {
            echo "  Type string has {$paramCount} parameters\n";
            flush();
        }
        
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        });
        
        try {
            if ($debug) {
                echo "  About to call bind_param...\n";
                flush();
            }
            
            $bindResult = $stmt->bind_param('issssisss', 
                $wcOrderId, $status, $currency, $postDateGmt, $postDateGmt,
                $customerId, $billingEmail, $total, $tax
            );
            
            if ($debug) {
                echo "  bind_param call completed\n";
                flush();
            }
        } catch (Exception $e) {
            restore_error_handler();
            throw new Exception("Exception during bind_param: " . $e->getMessage());
        } catch (ErrorException $e) {
            restore_error_handler();
            throw new Exception("Error during bind_param: " . $e->getMessage() . " at line " . $e->getLine());
        }
        
        restore_error_handler();
        
        if ($debug) {
            echo "  bind_param returned: " . var_export($bindResult, true) . "\n";
            flush();
        }
        
        if (!$bindResult) {
            $error = $stmt->error ?: "Unknown bind error";
            throw new Exception("Failed to bind parameters: " . $error);
        }
        
        if ($debug) {
            echo "Parameters bound, executing...\n";
        }
        
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $errno = $stmt->errno;
            $stmt->close();
            throw new Exception("Failed to insert into wc_orders (Error #{$errno}): " . $error);
        }
        
        if ($debug) {
            echo "Execute successful!\n";
        }
        
        $stmt->close();
        
        if ($debug) {
            echo "Created new order #{$wcOrderId} in wc_orders\n";
        }
    }
    
    // sync operational data (shipping, discount, etc.)
    if ($debug) {
        echo "Syncing to wc_order_operational_data...\n";
    }
    syncOperationalData($wcOrderId, $order, $shipping, $discount, $mysqli);
    
    // sync addresses
    if ($debug) {
        echo "Syncing addresses to wc_order_addresses...\n";
    }
    syncOrderAddresses($wcOrderId, $order, $mysqli);
    
    if ($debug) {
        echo "Adding order metadata to wc_orders_meta...\n";
    }
    
    // add order meta to wc_orders_meta
    addOrderMetaToHPOS($wcOrderId, $legacyOrderId, $customerId, $order, $mysqli);
    
    // add order items FIRST (we need the item IDs for product lookup)
    if ($debug) {
        echo "Adding order items...\n";
    }
    $orderItemIds = addOrderItems($wcOrderId, $order['products'], $mysqli, $debug);
    
    if ($debug) {
        echo "Adding products to wc_order_product_lookup...\n";
    }
    
    // sync products to wc_order_product_lookup (uses order item IDs)
    syncProductsToLookup($wcOrderId, $order, $orderItemIds, $mysqli, $debug);
    
    // add order notes
    if ($debug) {
        echo "Adding order notes...\n";
    }
    addOrderNotes($wcOrderId, $order['status_history'], $mysqli, $debug);
    
    // sync to wc_order_stats (required for orders to show in admin)
    if ($debug) {
        echo "Syncing to wc_order_stats...\n";
    }
    syncOrderStats($wcOrderId, $order, $customerId, $mysqli, $debug);
    
    if ($debug) {
        echo "HPOS sync completed\n";
    }
    
    return $wcOrderId;
}

/**
 * sync order addresses to wc_order_addresses
 */
function syncOrderAddresses($orderId, $order, $mysqli) {
    global $wpdb;
    
    // delete existing addresses
    $stmt = $mysqli->prepare("DELETE FROM {$wpdb->prefix}wc_order_addresses WHERE order_id = ?");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $stmt->close();
    
    // billing address
    $billingFirstName = $order['customer']['customers_firstname'] ?? '';
    $billingLastName = $order['customer']['customers_lastname'] ?? '';
    $billingCompany = $order['billing_company'] ?? '';
    $billingAddress1 = $order['billing_street_address'] ?? '';
    $billingAddress2 = $order['billing_suburb'] ?? '';
    $billingCity = $order['billing_city'] ?? '';
    $billingState = $order['billing_state'] ?? '';
    $billingPostcode = $order['billing_postcode'] ?? '';
    $billingCountry = mapCountry($order['billing_country'] ?? '');
    $billingEmail = $order['customers_email_address'] ?? '';
    $billingPhone = $order['customers_telephone'] ?? '';
    
    $stmt = $mysqli->prepare("
        INSERT INTO {$wpdb->prefix}wc_order_addresses 
        (order_id, address_type, first_name, last_name, company, address_1, address_2, 
         city, state, postcode, country, email, phone)
        VALUES (?, 'billing', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('isssssssssss',
        $orderId, $billingFirstName, $billingLastName, $billingCompany,
        $billingAddress1, $billingAddress2, $billingCity, $billingState,
        $billingPostcode, $billingCountry, $billingEmail, $billingPhone
    );
    $stmt->execute();
    $stmt->close();
    
    // shipping address
    $shippingFirstName = extractFirstName($order['delivery_name'] ?? '');
    $shippingLastName = extractLastName($order['delivery_name'] ?? '');
    $shippingCompany = $order['delivery_company'] ?? '';
    $shippingAddress1 = $order['delivery_street_address'] ?? '';
    $shippingAddress2 = $order['delivery_suburb'] ?? '';
    $shippingCity = $order['delivery_city'] ?? '';
    $shippingState = $order['delivery_state'] ?? '';
    $shippingPostcode = $order['delivery_postcode'] ?? '';
    $shippingCountry = mapCountry($order['delivery_country'] ?? '');
    
    $stmt = $mysqli->prepare("
        INSERT INTO {$wpdb->prefix}wc_order_addresses 
        (order_id, address_type, first_name, last_name, company, address_1, address_2, 
         city, state, postcode, country, email, phone)
        VALUES (?, 'shipping', ?, ?, ?, ?, ?, ?, ?, ?, ?, '', '')
    ");
    $stmt->bind_param('isssssssss',
        $orderId, $shippingFirstName, $shippingLastName, $shippingCompany,
        $shippingAddress1, $shippingAddress2, $shippingCity, $shippingState,
        $shippingPostcode, $shippingCountry
    );
    $stmt->execute();
    $stmt->close();
}

/**
 * sync order operational data to wc_order_operational_data
 */
function syncOperationalData($orderId, $order, $shipping, $discount, $mysqli) {
    global $wpdb;
    
    // check if entry exists
    $checkStmt = $mysqli->prepare("SELECT id FROM {$wpdb->prefix}wc_order_operational_data WHERE order_id = ?");
    $checkStmt->bind_param('i', $orderId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $exists = $result->num_rows > 0;
    $checkStmt->close();
    
    $orderKey = 'wc_order_' . uniqid();
    $shippingTotal = $shipping ?: '0.00';
    $discountTotal = $discount ?: '0.00';
    
    if ($exists) {
        // update existing operational data
        $stmt = $mysqli->prepare("
            UPDATE {$wpdb->prefix}wc_order_operational_data 
            SET shipping_total_amount = ?, discount_total_amount = ?
            WHERE order_id = ?
        ");
        $stmt->bind_param('ssi', $shippingTotal, $discountTotal, $orderId);
    } else {
        // insert new operational data
        $stmt = $mysqli->prepare("
            INSERT INTO {$wpdb->prefix}wc_order_operational_data 
            (order_id, order_key, shipping_total_amount, discount_total_amount, created_via)
            VALUES (?, ?, ?, ?, 'sync')
        ");
        $stmt->bind_param('isss', $orderId, $orderKey, $shippingTotal, $discountTotal);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to sync operational data: " . $stmt->error);
    }
    $stmt->close();
}

/**
 * add order meta to wc_orders_meta
 */
function addOrderMetaToHPOS($orderId, $legacyOrderId, $customerId, $order, $mysqli) {
    global $wpdb;
    
    // delete existing meta
    $stmt = $mysqli->prepare("DELETE FROM {$wpdb->prefix}wc_orders_meta WHERE order_id = ?");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $stmt->close();
    
    $meta = [
        '_legacy_order_id' => $legacyOrderId,
        '_legacy_customer_id' => $order['customers_id'],
        '_customer_user' => $customerId,
        '_order_key' => 'wc_order_' . uniqid(),
        '_order_currency' => $order['currency'] ?: 'USD',
        '_prices_include_tax' => 'no',
        '_customer_ip_address' => extractIpAddress($order['ip_address']),
        '_customer_user_agent' => '',
        
        // billing
        '_billing_first_name' => $order['customer']['customers_firstname'] ?? '',
        '_billing_last_name' => $order['customer']['customers_lastname'] ?? '',
        '_billing_company' => $order['billing_company'] ?? '',
        '_billing_address_1' => $order['billing_street_address'] ?? '',
        '_billing_address_2' => $order['billing_suburb'] ?? '',
        '_billing_city' => $order['billing_city'] ?? '',
        '_billing_state' => $order['billing_state'] ?? '',
        '_billing_postcode' => $order['billing_postcode'] ?? '',
        '_billing_country' => mapCountry($order['billing_country'] ?? ''),
        '_billing_email' => $order['customers_email_address'] ?? '',
        '_billing_phone' => $order['customers_telephone'] ?? '',
        
        // shipping
        '_shipping_first_name' => extractFirstName($order['delivery_name'] ?? ''),
        '_shipping_last_name' => extractLastName($order['delivery_name'] ?? ''),
        '_shipping_company' => $order['delivery_company'] ?? '',
        '_shipping_address_1' => $order['delivery_street_address'] ?? '',
        '_shipping_address_2' => $order['delivery_suburb'] ?? '',
        '_shipping_city' => $order['delivery_city'] ?? '',
        '_shipping_state' => $order['delivery_state'] ?? '',
        '_shipping_postcode' => $order['delivery_postcode'] ?? '',
        '_shipping_country' => mapCountry($order['delivery_country'] ?? ''),
        
        // payment
        '_payment_method' => mapPaymentMethod($order['payment_method']),
        '_payment_method_title' => $order['payment_method'],
        
        // shipping method
        '_shipping_method' => $order['shipping_method'],
        
        // totals
        '_order_total' => $order['order_total'] ?: '0.00',
        '_order_tax' => $order['order_tax'] ?: '0.00',
        '_order_shipping' => '0.00',
        '_order_discount' => '0.00',
        '_cart_discount' => '0.00',
        '_cart_discount_tax' => '0.00',
        '_order_shipping_tax' => '0.00',
        
        // other
        '_coupon_code' => $order['coupon_code'] ?? '',
        '_date_completed' => $order['orders_date_finished'] ?? '',
        '_date_paid' => $order['date_purchased'] ?? '',
    ];
    
    // calculate shipping and discount from totals
    if (!empty($order['totals'])) {
        foreach ($order['totals'] as $total) {
            if ($total['class'] === 'ot_shipping') {
                $meta['_order_shipping'] = $total['value'];
            } elseif ($total['class'] === 'ot_coupon') {
                $meta['_order_discount'] = $total['value'];
                $meta['_cart_discount'] = $total['value'];
            }
        }
    }
    
    foreach ($meta as $key => $value) {
        $stmt = $mysqli->prepare("
            INSERT INTO {$wpdb->prefix}wc_orders_meta (order_id, meta_key, meta_value) 
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param('iss', $orderId, $key, $value);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * sync order stats to wc_order_stats (required for admin orders list)
 */
function syncOrderStats($orderId, $order, $customerId, $mysqli, $debug) {
    global $wpdb;
    
    // check if table exists
    $tableCheck = $mysqli->query("SHOW TABLES LIKE '{$wpdb->prefix}wc_order_stats'");
    if ($tableCheck->num_rows == 0) {
        if ($debug) {
            echo "  wc_order_stats table not found, skipping...\n";
        }
        return;
    }
    
    // delete existing entry
    $stmt = $mysqli->prepare("DELETE FROM {$wpdb->prefix}wc_order_stats WHERE order_id = ?");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $stmt->close();
    
    $dateCreated = $order['date_purchased'] ?: date('Y-m-d H:i:s');
    $dateCreatedGmt = get_gmt_from_date($dateCreated);
    $datePaid = $order['date_purchased'] ?: null;
    $dateCompleted = $order['orders_date_finished'] ?: null;
    $status = mapOrderStatus($order['orders_status']); // Keep wc- prefix
    
    // calculate totals
    $totalSales = $order['order_total'] ?: 0;
    $taxTotal = $order['order_tax'] ?: 0;
    $shippingTotal = 0;
    
    if (!empty($order['totals'])) {
        foreach ($order['totals'] as $total) {
            if ($total['class'] === 'ot_shipping') {
                $shippingTotal = $total['value'];
            }
        }
    }
    
    $netTotal = $totalSales - $taxTotal;
    $numItems = count($order['products'] ?? []);
    
    $stmt = $mysqli->prepare("
        INSERT INTO {$wpdb->prefix}wc_order_stats 
        (order_id, parent_id, date_created, date_created_gmt, date_paid, date_completed,
         num_items_sold, total_sales, tax_total, shipping_total, net_total, 
         returning_customer, status, customer_id)
        VALUES (?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?)
    ");
    
    $stmt->bind_param('issssidddisi',
        $orderId, $dateCreated, $dateCreatedGmt, $datePaid, $dateCompleted,
        $numItems, $totalSales, $taxTotal, $shippingTotal, $netTotal,
        $status, $customerId
    );
    
    if (!$stmt->execute()) {
        if ($debug) {
            echo "  Warning: Failed to sync order stats: " . $stmt->error . "\n";
        }
    }
    
    $stmt->close();
}

/**
 * sync products to wc_order_product_lookup
 */
function syncProductsToLookup($orderId, $order, $orderItemIds, $mysqli, $debug) {
    global $wpdb;
    
    // check if table exists
    $tableCheck = $mysqli->query("SHOW TABLES LIKE '{$wpdb->prefix}wc_order_product_lookup'");
    if ($tableCheck->num_rows == 0) {
        return;
    }
    
    // delete existing entries
    $stmt = $mysqli->prepare("DELETE FROM {$wpdb->prefix}wc_order_product_lookup WHERE order_id = ?");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $stmt->close();
    
    if (empty($orderItemIds)) {
        return;
    }
    
    $orderDate = $order['date_purchased'] ?: date('Y-m-d H:i:s');
    $customerId = $order['customers_id'] ?: 0;
    
    foreach ($orderItemIds as $itemData) {
        $orderItemId = $itemData['order_item_id'];
        $productId = $itemData['product_id'];
        $quantity = $itemData['product_qty'] ?: 1;
        $netRevenue = $itemData['product_net_revenue'] ?: 0;
        
        $stmt = $mysqli->prepare("
            INSERT INTO {$wpdb->prefix}wc_order_product_lookup 
            (order_item_id, order_id, product_id, variation_id, customer_id, date_created, product_qty, product_net_revenue, product_gross_revenue)
            VALUES (?, ?, ?, 0, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('iiiisiss', 
            $orderItemId, $orderId, $productId, $customerId, $orderDate,
            $quantity, $netRevenue, $netRevenue
        );
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * helper functions
 */

function mapOrderStatus($status) {
    $statusMap = [
        1 => 'wc-pending',
        2 => 'wc-processing',
        3 => 'wc-completed',
        4 => 'wc-processing',
        5 => 'wc-completed',
        6 => 'wc-cancelled',
    ];
    
    return $statusMap[$status] ?? 'wc-processing';
}

function mapPaymentMethod($method) {
    $methodMap = [
        'Credit Card' => 'authorizenet',
        'PayPal' => 'paypal',
        'Check/Money Order' => 'cheque',
        'Bank Transfer' => 'bacs',
    ];
    
    foreach ($methodMap as $key => $value) {
        if (stripos($method, $key) !== false) {
            return $value;
        }
    }
    
    return 'other';
}

function mapCountry($country) {
    if ($country === 'United States') {
        return 'US';
    }
    if ($country === 'Canada') {
        return 'CA';
    }
    
    // return first 2 chars as fallback
    return strtoupper(substr($country, 0, 2));
}

function extractFirstName($fullName) {
    $parts = explode(' ', $fullName, 2);
    return $parts[0] ?? '';
}

function extractLastName($fullName) {
    $parts = explode(' ', $fullName, 2);
    return $parts[1] ?? '';
}

function extractIpAddress($ipString) {
    $parts = explode(' - ', $ipString);
    return $parts[0] ?? $ipString;
}

function mapTotalType($class) {
    $typeMap = [
        'ot_shipping' => 'shipping',
        'ot_tax' => 'tax',
        'ot_coupon' => 'coupon',
        'ot_fee' => 'fee',
    ];
    
    return $typeMap[$class] ?? null;
}

