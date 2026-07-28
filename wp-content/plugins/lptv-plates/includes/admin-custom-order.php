<?php

// Add customer selector before billing section
add_action('woocommerce_checkout_before_customer_details', function () {
    if (!current_user_can('manage_woocommerce')) return;

    echo '<div class="admin-customer-selector" style="margin-bottom: 30px;border: 2px solid #2271b1;padding: 15px;border-radius: 5px;background-color: #f0f6fc;box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    echo '<h3 style="margin-top: 0;color: #2271b1;font-size: 20px;font-weight: 600;margin-bottom: 5px;">Select a Customer</h3>';
    echo '<p style="margin: 0 0 0px 0;color: #50575e;font-size: 15px;padding:0 0 10px 0;">Choose "Guest Checkout" to create a new order for a guest customer, or search and select an existing customer to create an order for them.</p>';
    echo '<select id="admin_customer_selector" name="admin_customer_id" class="wc-customer-search" style="max-width: 350px;margin:0;padding:0" required>';
    echo '<option value="">– Select Customer –</option>';
    echo '</select>';
    echo '</div>';

    // Enqueue Select2
    wp_enqueue_style('select2');
    wp_enqueue_script('select2');
    wp_enqueue_script('wc-enhanced-select');

    echo '<script>
        jQuery(document).ready(function($) {
            $("#admin_customer_selector").select2({
                placeholder: "Search for a customer...",
                allowClear: true,
                ajax: {
                    url: "' . admin_url('admin-ajax.php') . '",
                    dataType: "json",
                    delay: 250,
                    data: function(params) {
                        return {
                            action: "woocommerce_json_search_customers",
                            term: params.term,
                            page: params.page || 1,
                            security: "' . wp_create_nonce('search-customers') . '"
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;
                        
                        // Convert WooCommerce response format to Select2 format
                        let results = [];
                        
                        // Always add Guest Checkout option
                        results.push({
                            id: "guest",
                            text: "Guest Checkout",
                            selected: false
                        });
                        
                        // Add customer results
                        for (let id in data) {
                            results.push({
                                id: id,
                                text: data[id].replace(\'&ndash;\',\'-\'),
                                selected: false
                            });
                        }
                        
                        return {
                            results: results,
                            pagination: {
                                more: false // WooCommerce API doesn\'t return pagination info
                            }
                        };
                    },
                    cache: true
                },
                query: function(options) {
                    // If no search term, show only Guest Checkout option
                    if (!options.term) {
                        options.callback({
                            results: [{
                                id: "guest",
                                text: "Guest Checkout",
                                selected: false
                            }],
                            pagination: {
                                more: false
                            }
                        });
                    } else {
                        // If there is a search term, proceed with normal AJAX search
                        $.ajax({
                            url: "' . admin_url('admin-ajax.php') . '",
                            dataType: "json",
                            data: {
                                action: "woocommerce_json_search_customers",
                                term: options.term,
                                page: options.page || 1,
                                security: "' . wp_create_nonce('search-customers') . '"
                            },
                            success: function(data) {
                                let results = [];
                                
                                // Always add Guest Checkout option
                                results.push({
                                    id: "guest",
                                    text: "Guest Checkout",
                                    selected: false
                                });
                                
                                // Add customer results
                                for (let id in data) {
                                    results.push({
                                        id: id,
                                        text: data[id].replace(\'&ndash;\',\'-\'),
                                        selected: false
                                    });
                                }
                                
                                options.callback({
                                    results: results,
                                    pagination: {
                                        more: false
                                    }
                                });
                            }
                        });
                    }
                }
            }).on("select2:select", function(e) {
                let customerId = e.params.data.id;
                if (customerId && customerId !== "guest") {
                    fetch("' . admin_url('admin-ajax.php?action=get_customer_details&customer_id=') . '" + customerId + "&security=' . wp_create_nonce('get-customer-details') . '")
                        .then(response => response.json())
                        .then(data => {
                            for (let field in data) {
                                let input = document.querySelector("[name=\'" + field + "\']");
                                if (input) input.value = data[field];
                            }
                        });
                } else if (customerId === "guest") {
                    // Clear all customer fields for guest checkout
                    let fields = ["billing_first_name", "billing_last_name", "billing_address_1", "billing_address_2", 
                                "billing_city", "billing_state", "billing_postcode", "billing_country", "billing_phone", 
                                "billing_email", "shipping_first_name", "shipping_last_name", "shipping_address_1", 
                                "shipping_address_2", "shipping_city", "shipping_state", "shipping_postcode", "shipping_country"];
                    fields.forEach(field => {
                        let input = document.querySelector("[name=\'" + field + "\']");
                        if (input) input.value = "";
                    });
                }
            });
        });
    </script>';
});

// Get customer details via AJAX
add_action('wp_ajax_get_customer_details', function () {
    if (!current_user_can('manage_woocommerce')) wp_die();
    
    // Verify nonce
    if (!check_ajax_referer('get-customer-details', 'security', false)) {
        wp_send_json_error('Invalid security token');
    }

    $customer_id = intval($_GET['customer_id']);
    $customer = new WC_Customer($customer_id);

    if ($customer) {
        wp_send_json([
            'billing_first_name' => $customer->get_billing_first_name(),
            'billing_last_name' => $customer->get_billing_last_name(),
            'billing_address_1' => $customer->get_billing_address_1(),
            'billing_address_2' => $customer->get_billing_address_2(),
            'billing_city' => $customer->get_billing_city(),
            'billing_state' => $customer->get_billing_state(),
            'billing_postcode' => $customer->get_billing_postcode(),
            'billing_country' => $customer->get_billing_country(),
            'billing_phone' => $customer->get_billing_phone(),
            'billing_email' => $customer->get_email(),
            'shipping_first_name' => $customer->get_shipping_first_name(),
            'shipping_last_name' => $customer->get_shipping_last_name(),
            'shipping_address_1' => $customer->get_shipping_address_1(),
            'shipping_address_2' => $customer->get_shipping_address_2(),
            'shipping_city' => $customer->get_shipping_city(),
            'shipping_state' => $customer->get_shipping_state(),
            'shipping_postcode' => $customer->get_shipping_postcode(),
            'shipping_country' => $customer->get_shipping_country(),
        ]);
    }
    wp_die();
});

// Store selected customer ID in session when form is submitted
add_action('woocommerce_checkout_process', function () {
    if (current_user_can('manage_woocommerce') && WC()->session) {
        if (isset($_POST['admin_customer_id'])) {
            $customer_id = $_POST['admin_customer_id'];
            // Only convert to integer if it's not 'guest'
            if ($customer_id !== 'guest') {
                $customer_id = intval($customer_id);
            }
            WC()->session->set('admin_customer_id', $customer_id);
        }
    }
});

// Set customer ID for the order
add_filter('woocommerce_checkout_customer_id', function ($customer_id) {
    if (current_user_can('manage_woocommerce') && WC()->session) {
        $admin_customer_id = WC()->session->get('admin_customer_id');
        
        if ($admin_customer_id === 'guest') {
          //  error_log('Setting customer ID to 0 for guest checkout');
            return 0;
        } elseif ($admin_customer_id) {
        //    error_log('Setting customer ID to: ' . $admin_customer_id);
            return $admin_customer_id;
        }
    }
    return $customer_id;
});

// Add dummy payment gateway for admin orders
add_filter('woocommerce_available_payment_gateways', function ($gateways) {
    if (current_user_can('manage_woocommerce')) {

        // add admin order payment gateway
        $gateways['admin_order'] = new class extends WC_Payment_Gateway {
            public function __construct() {
                $this->id = 'admin_order';
                $this->title = 'Admin Order';
                $this->description = 'Payment method for admin-created orders';
                $this->has_fields = false;
            }
            public function process_payment($order_id) {
                $order = wc_get_order($order_id);
               
                // Clear the session variable to ensure next checkout creates a new order
                if (WC()->session) {
                    WC()->session->set('order_awaiting_payment', false);
                }
                
                // Empty cart
                WC()->cart->empty_cart();
                
                return array(
                    'result' => 'success',
                    'redirect' => admin_url('post.php?post=' . $order_id . '&action=edit')
                );
            }
        };
        
    }
    
    return $gateways;
});


