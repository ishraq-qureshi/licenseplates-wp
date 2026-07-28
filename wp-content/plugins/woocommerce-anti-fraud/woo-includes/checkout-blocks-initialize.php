<?php

add_action(
	'woocommerce_blocks_loaded',
	function () {
		require_once 'class-blocks-integration.php';

		// Register the captcha block's checkout extension so the Store API
		// accepts and forwards our token through the standard extensions channel.
		// Without this registration, WooCommerce strips the extension_data from
		// the request and $request->get_param('extensions')['checkout-captcha-block']
		// is always empty — making only the cookie fallback work.
		if ( function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
			woocommerce_store_api_register_endpoint_data(
				array(
					'endpoint'        => 'checkout',
					'namespace'       => 'checkout-captcha-block',
					'schema_callback' => function () {
						return array(
							'checkout_captcha' => array(
								'description' => __( 'reCAPTCHA / Turnstile token for Anti-Fraud verification.', 'woocommerce-anti-fraud' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => false,
							),
						);
					},
				)
			);
		}

		add_action(
			'woocommerce_blocks_checkout_block_registration',
			function( $integration_registry ) {
				$integration_registry->register( new Blocks_Integration() );
			}
		);
	}
);
