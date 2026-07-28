#!/usr/bin/php
<?php

/**
 * update customer billing and shipping addresses from their orders
 * 
 * features:
 * - reads addresses from wc_order_addresses table
 * - updates user meta with billing/shipping fields
 * - processes customers in batches
 * - uses most recent order for each customer
 * 
 * usage:
 * php update-customer-addresses.php [options]
 * 
 * options:
 * --debug     enable debug mode
 * --limit=N   limit number of customers to process (default: 100)
 * --offset=N  offset for pagination (default: 0)
 * --all       process all customers until no more data
 * --user-id=N update only specific user ID
 * 
 * examples:
 * php update-customer-addresses.php              # update 100 customers
 * php update-customer-addresses.php --all        # update all customers
 * php update-customer-addresses.php --user-id=5  # update only user 5
 * php update-customer-addresses.php --debug      # update with debug output
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
$debug = false;
$limit = 100;
$offset = 0;
$processAll = false;
$singleUserId = null;

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
    } elseif (strpos($arg, '--user-id=') === 0) {
        $singleUserId = (int)substr($arg, 10);
        $debug = true; // auto enable debug for single user
    }
}

// disable buffering for real-time output
if (ob_get_level()) {
    while (ob_get_level()) {
        ob_end_flush();
    }
}

echo "=== Customer Address Update Script ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

if ($singleUserId) {
    echo "Debug mode: Processing single user #{$singleUserId}\n\n";
    updateSingleCustomer($singleUserId, $mysqli, $debug);
} elseif ($processAll) {
    echo "Processing ALL customers starting from offset {$offset}...\n\n";
    updateAllCustomers($limit, $offset, $mysqli, $debug);
} else {
    echo "Processing customers (limit: {$limit}, offset: {$offset})\n\n";
    updateCustomers($limit, $offset, $mysqli, $debug);
}

echo "\n\nCompleted at: " . date('Y-m-d H:i:s') . "\n";
$mysqli->close();

/**
 * update single customer
 */
function updateSingleCustomer($userId, $mysqli, $debug) {
    try {
        $result = processCustomer($userId, $mysqli, $debug);
        
        if ($result['updated']) {
            echo "\nUser #{$userId} successfully UPDATED\n";
            echo "Billing address updated: " . ($result['billing_updated'] ? 'YES' : 'NO') . "\n";
            echo "Shipping address updated: " . ($result['shipping_updated'] ? 'YES' : 'NO') . "\n";
        } else {
            echo "\nUser #{$userId} - No orders found\n";
        }
    } catch (Exception $e) {
        echo "\nERROR processing user #{$userId}: " . $e->getMessage() . "\n";
    }
}

/**
 * update all customers (loop until no more data)
 */
function updateAllCustomers($batchSize, $startOffset, $mysqli, $debug) {
    $offset = $startOffset;
    $totalProcessed = 0;
    $batchNumber = 1;
    
    while (true) {
        echo "--- Batch #{$batchNumber} (offset: {$offset}, limit: {$batchSize}) ---\n";
        
        $customers = getCustomerBatch($batchSize, $offset, $mysqli);
        $count = count($customers);
        
        if ($count === 0) {
            echo "No more customers to process.\n";
            break;
        }
        
        echo "Processing {$count} customers...\n";
        
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        
        foreach ($customers as $customer) {
            try {
                $result = processCustomer($customer['ID'], $mysqli, $debug);
                $totalProcessed++;
                
                if ($result['updated']) {
                    $updated++;
                    $billing = $result['billing_updated'] ? 'B' : '';
                    $shipping = $result['shipping_updated'] ? 'S' : '';
                    $flags = $billing || $shipping ? " [{$billing}{$shipping}]" : '';
                    echo "  User #{$customer['ID']} UPDATED{$flags}\n";
                } else {
                    $skipped++;
                    echo "  User #{$customer['ID']} SKIPPED (no orders)\n";
                }
            } catch (Exception $e) {
                $errors++;
                echo "  ERROR processing user #{$customer['ID']}: " . $e->getMessage() . "\n";
            }
        }
        
        echo "Batch #{$batchNumber} completed. Updated: {$updated}, Skipped: {$skipped}, Errors: {$errors}\n";
        echo "Total processed: {$totalProcessed}\n\n";
        
        // if we got less than the batch size, we're done
        if ($count < $batchSize) {
            echo "Reached end of customers (got {$count} < {$batchSize}).\n";
            break;
        }
        
        $offset += $batchSize;
        $batchNumber++;
    }
    
    echo "\n=== Summary ===\n";
    echo "Total customers processed: {$totalProcessed}\n";
    echo "Total batches: {$batchNumber}\n";
}

/**
 * update multiple customers
 */
function updateCustomers($limit, $offset, $mysqli, $debug) {
    $customers = getCustomerBatch($limit, $offset, $mysqli);
    $total = count($customers);
    
    echo "Found {$total} customers to process\n\n";
    
    $processed = 0;
    $updated = 0;
    $skipped = 0;
    $errors = 0;
    
    foreach ($customers as $customer) {
        $processed++;
        
        try {
            $result = processCustomer($customer['ID'], $mysqli, $debug);
            
            if ($result['updated']) {
                $updated++;
                $billing = $result['billing_updated'] ? 'B' : '';
                $shipping = $result['shipping_updated'] ? 'S' : '';
                $flags = $billing || $shipping ? " [{$billing}{$shipping}]" : '';
                echo "[{$processed}/{$total}] User #{$customer['ID']} UPDATED{$flags}\n";
            } else {
                $skipped++;
                echo "[{$processed}/{$total}] User #{$customer['ID']} SKIPPED (no orders)\n";
            }
        } catch (Exception $e) {
            $errors++;
            echo "[{$processed}/{$total}] User #{$customer['ID']} ERROR: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== Summary ===\n";
    echo "Total processed: {$processed}\n";
    echo "Updated: {$updated}\n";
    echo "Skipped (no orders): {$skipped}\n";
    echo "Errors: {$errors}\n";
}

/**
 * get batch of customers
 */
function getCustomerBatch($limit, $offset, $mysqli) {
    global $wpdb;
    
    // get users who have customer role
    $stmt = $mysqli->prepare("
        SELECT DISTINCT u.ID, u.user_email
        FROM {$wpdb->prefix}users u
        INNER JOIN {$wpdb->prefix}usermeta um ON u.ID = um.user_id
        WHERE um.meta_key = '{$wpdb->prefix}capabilities'
        AND um.meta_value LIKE '%customer%'
        ORDER BY u.ID ASC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $customers = [];
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
    
    $stmt->close();
    return $customers;
}

/**
 * process single customer
 */
function processCustomer($userId, $mysqli, $debug) {
    global $wpdb;
    
    if ($debug) {
        echo "\n--- Processing User #{$userId} ---\n";
    }
    
    // get most recent order for this customer
    $stmt = $mysqli->prepare("
        SELECT id 
        FROM {$wpdb->prefix}wc_orders 
        WHERE customer_id = ?
        ORDER BY date_created_gmt DESC
        LIMIT 1
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $orderRow = $result->fetch_assoc();
    $stmt->close();
    
    if (!$orderRow) {
        if ($debug) {
            echo "No orders found for user #{$userId}\n";
        }
        return [
            'updated' => false,
            'billing_updated' => false,
            'shipping_updated' => false
        ];
    }
    
    $orderId = $orderRow['id'];
    
    if ($debug) {
        echo "Found most recent order #{$orderId}\n";
    }
    
    // get addresses from order
    $billingUpdated = updateBillingAddress($userId, $orderId, $mysqli, $debug);
    $shippingUpdated = updateShippingAddress($userId, $orderId, $mysqli, $debug);
    
    // update wc_customer_lookup table (for HPOS)
    if ($billingUpdated) {
        updateCustomerLookup($userId, $orderId, $mysqli, $debug);
    }
    
    return [
        'updated' => $billingUpdated || $shippingUpdated,
        'billing_updated' => $billingUpdated,
        'shipping_updated' => $shippingUpdated
    ];
}

/**
 * update billing address from order
 */
function updateBillingAddress($userId, $orderId, $mysqli, $debug) {
    global $wpdb;
    
    // get billing address from order
    $stmt = $mysqli->prepare("
        SELECT first_name, last_name, company, address_1, address_2, 
               city, state, postcode, country, email, phone
        FROM {$wpdb->prefix}wc_order_addresses
        WHERE order_id = ? AND address_type = 'billing'
        LIMIT 1
    ");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $address = $result->fetch_assoc();
    $stmt->close();
    
    if (!$address) {
        if ($debug) {
            echo "No billing address found for order #{$orderId}\n";
        }
        return false;
    }
    
    if ($debug) {
        echo "Updating billing address for user #{$userId}...\n";
    }
    
    // update user meta
    $metaData = [
        'billing_first_name' => $address['first_name'] ?: '',
        'billing_last_name' => $address['last_name'] ?: '',
        'billing_company' => $address['company'] ?: '',
        'billing_address_1' => $address['address_1'] ?: '',
        'billing_address_2' => $address['address_2'] ?: '',
        'billing_city' => $address['city'] ?: '',
        'billing_state' => $address['state'] ?: '',
        'billing_postcode' => $address['postcode'] ?: '',
        'billing_country' => $address['country'] ?: '',
        'billing_email' => $address['email'] ?: '',
        'billing_phone' => $address['phone'] ?: '',
    ];
    
    foreach ($metaData as $key => $value) {
        updateUserMeta($userId, $key, $value, $mysqli);
    }
    
    if ($debug) {
        echo "Billing address updated\n";
    }
    
    return true;
}

/**
 * update shipping address from order
 */
function updateShippingAddress($userId, $orderId, $mysqli, $debug) {
    global $wpdb;
    
    // get shipping address from order
    $stmt = $mysqli->prepare("
        SELECT first_name, last_name, company, address_1, address_2, 
               city, state, postcode, country
        FROM {$wpdb->prefix}wc_order_addresses
        WHERE order_id = ? AND address_type = 'shipping'
        LIMIT 1
    ");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $address = $result->fetch_assoc();
    $stmt->close();
    
    if (!$address) {
        if ($debug) {
            echo "No shipping address found for order #{$orderId}\n";
        }
        return false;
    }
    
    if ($debug) {
        echo "Updating shipping address for user #{$userId}...\n";
    }
    
    // update user meta
    $metaData = [
        'shipping_first_name' => $address['first_name'] ?: '',
        'shipping_last_name' => $address['last_name'] ?: '',
        'shipping_company' => $address['company'] ?: '',
        'shipping_address_1' => $address['address_1'] ?: '',
        'shipping_address_2' => $address['address_2'] ?: '',
        'shipping_city' => $address['city'] ?: '',
        'shipping_state' => $address['state'] ?: '',
        'shipping_postcode' => $address['postcode'] ?: '',
        'shipping_country' => $address['country'] ?: '',
    ];
    
    foreach ($metaData as $key => $value) {
        updateUserMeta($userId, $key, $value, $mysqli);
    }
    
    if ($debug) {
        echo "Shipping address updated\n";
    }
    
    return true;
}

/**
 * update user meta (delete old, insert new)
 */
function updateUserMeta($userId, $metaKey, $metaValue, $mysqli) {
    global $wpdb;
    
    // delete old value
    $stmt = $mysqli->prepare("
        DELETE FROM {$wpdb->prefix}usermeta 
        WHERE user_id = ? AND meta_key = ?
    ");
    $stmt->bind_param('is', $userId, $metaKey);
    $stmt->execute();
    $stmt->close();
    
    // insert new value
    $stmt = $mysqli->prepare("
        INSERT INTO {$wpdb->prefix}usermeta (user_id, meta_key, meta_value) 
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param('iss', $userId, $metaKey, $metaValue);
    $stmt->execute();
    $stmt->close();
}

/**
 * update wc_customer_lookup table with billing address
 */
function updateCustomerLookup($userId, $orderId, $mysqli, $debug) {
    global $wpdb;
    
    // get billing address from order
    $stmt = $mysqli->prepare("
        SELECT city, state, postcode, country
        FROM {$wpdb->prefix}wc_order_addresses
        WHERE order_id = ? AND address_type = 'billing'
        LIMIT 1
    ");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $address = $result->fetch_assoc();
    $stmt->close();
    
    if (!$address) {
        return;
    }
    
    if ($debug) {
        echo "Updating wc_customer_lookup for user #{$userId}...\n";
    }
    
    // check if customer exists in lookup table
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
        if ($debug) {
            echo "Customer not found in wc_customer_lookup, skipping...\n";
        }
        return;
    }
    
    // update wc_customer_lookup
    $stmt = $mysqli->prepare("
        UPDATE {$wpdb->prefix}wc_customer_lookup 
        SET city = ?, state = ?, postcode = ?, country = ?
        WHERE customer_id = ?
    ");
    $stmt->bind_param('ssssi', 
        $address['city'], $address['state'], $address['postcode'], $address['country'], $userId
    );
    
    if (!$stmt->execute()) {
        if ($debug) {
            echo "Failed to update wc_customer_lookup: " . $stmt->error . "\n";
        }
    } else {
        if ($debug) {
            echo "wc_customer_lookup updated\n";
        }
    }
    
    $stmt->close();
}

