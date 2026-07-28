<?php
/**
 * WooCommerce Authorize.Net Gateway
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Authorize.Net Gateway to newer
 * versions in the future. If you wish to customize WooCommerce Authorize.Net Gateway for your
 * needs please refer to http://docs.woocommerce.com/document/authorize-net-cim/
 *
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2025, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\Authorize_Net\Blocks;

use SkyVerge\WooCommerce\Authorize_Net\Blocks\Traits\Checkout_Block_Integration_Trait;
use SkyVerge\WooCommerce\PluginFramework\v6_1_2\Payment_Gateway\Blocks\Gateway_Checkout_Block_Integration;
use SkyVerge\WooCommerce\PluginFramework\v6_1_2\SV_WC_Payment_Gateway;
use SkyVerge\WooCommerce\PluginFramework\v6_1_2\SV_WC_Payment_Gateway_Plugin;
use WC_Gateway_Authorize_Net_CIM_Credit_Card;

/**
 * Checkout block integration for the {@see \WC_Gateway_Authorize_Net_CIM_Credit_Card} gateway.
 *
 * @since 3.10.0
 *
 * @property \WC_Gateway_Authorize_Net_CIM $gateway the gateway instance
 */
class Credit_Card_Checkout_Block_Integration extends Gateway_Checkout_Block_Integration {


	use Checkout_Block_Integration_Trait;


	/**
	 * Constructor.
	 *
	 * @since 3.10.0
	 *
	 * @param SV_WC_Payment_Gateway_Plugin $plugin
	 * @param \WC_Gateway_Authorize_Net_CIM_Credit_Card $gateway
	 */
	public function __construct( SV_WC_Payment_Gateway_Plugin $plugin, SV_WC_Payment_Gateway $gateway ) {

		parent::__construct( $plugin, $gateway );

		// add accept.js script as a dependency for inline payment forms
		// when using Lightbox, the script is loaded via the React block, as it cannot be loaded until after the block is rendered
		if (! $gateway->is_lightbox_payment_form_enabled()) {
			$this->add_main_script_dependency( 'wc-authorize-net-cim-accept-' . $gateway->get_form_type() );
		}
	}


	/**
	 * Adds payment method data.
	 *
	 * @internal
	 *
	 * @see Gateway_Checkout_Block_Integration::get_payment_method_data()
	 *
	 * @since 3.10.0
	 *
	 * @param array<string, mixed> $payment_method_data
	 * @param WC_Gateway_Authorize_Net_CIM_Credit_Card $gateway
	 * @return array<string, mixed>
	 */
	public function add_payment_method_data( array $payment_method_data, SV_WC_Payment_Gateway $gateway ) : array {

		$payment_method_data['gateway'] = array_merge( $payment_method_data['gateway'] ?: [], $this->getGatewayJsSettings() );
		$payment_method_data['flags']   = array_merge( $payment_method_data['flags'] ?: [], $this->get_authorize_net_configuration_flags() );

		return $payment_method_data;
	}

	/**
	 * Gets the settings for the gateway's Accept.js / AcceptUI.js script.
	 * 
	 * @since 3.10.13
	 */
	protected function getGatewayJsSettings() : array
	{
		return array_merge(
			$this->get_authorize_net_connection_settings(),
			[
				'external_script_url' => $this->gateway->getAcceptJsScriptRegistration()->url,
			]
		);
	}
}
