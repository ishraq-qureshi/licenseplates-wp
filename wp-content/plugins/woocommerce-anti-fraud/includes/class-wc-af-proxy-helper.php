<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared proxy/IP intelligence helper.
 *
 * Centralises calls to the proxycheck.io API and provides a single place to
 * classify IPs as "trusted privacy service" vs "suspicious proxy".  Results
 * are cached in a WordPress transient (24 h) and in a per-request static
 * array so the API is only ever called once per IP per request.
 *
 * Why we distinguish trusted vs suspicious proxies:
 *   iCloud Private Relay (Apple) and Cloudflare CDN share their IP pool
 *   across millions of legitimate users.  Flagging those IPs as fraud
 *   indicators causes high false-positive rates.  The proxycheck.io ASN
 *   field lets us identify them reliably.
 *
 * @since 7.2.3
 */
class WC_AF_Proxy_Helper {

	/**
	 * ASNs (without the "AS" prefix) that belong to known legitimate
	 * privacy / CDN services.  Traffic from these networks must not be
	 * treated as a fraud signal on its own.
	 *
	 * AS13335  – Cloudflare, Inc. (iCloud Private Relay egress, Cloudflare WARP, CDN)
	 * AS714    – Apple Inc. (additional iCloud infrastructure)
	 * AS209242 – Cloudflare WARP consumer VPN
	 * AS20940  – Akamai Technologies (major CDN)
	 * AS54113  – Fastly (major CDN)
	 * AS15169  – Google LLC (Google One VPN / Google services)
	 * AS16509  – Amazon.com / AWS CloudFront CDN
	 */
	const TRUSTED_ASNS = array(
		'13335',  // Cloudflare (iCloud Private Relay + WARP + CDN)
		'714',    // Apple Inc.
		'209242', // Cloudflare WARP
		'20940',  // Akamai Technologies
		'54113',  // Fastly
		'15169',  // Google LLC
		'16509',  // Amazon / AWS CloudFront
	);

	/**
	 * Proxycheck.io "type" values that indicate CDN / infrastructure rather than a user-operated proxy.
	 *
	 * @param WC_Order $order Order object.
	 * @return bool True if risk detected, false otherwise.
	 */
	const TRUSTED_PROXY_TYPES = array(
		'CDN',
	);

	/** Per-request runtime cache keyed by IP address. */
	private static $runtime_cache = array();

	/**
	 * Fetch raw IP data from proxycheck.io with two-layer caching.
	 *
	 * Layer 1 – static array (per PHP request)
	 * Layer 2 – WordPress transient (DAY_IN_SECONDS)
	 *
	 * @param string $ip IPv4 or IPv6 address.
	 * @return array|null Associative data array from proxycheck.io, or null on failure.
	 */
	public static function get_ip_data( $ip ) {
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return null;
		}

		// Layer 1: per-request cache.
		if ( array_key_exists( $ip, self::$runtime_cache ) ) {
			return self::$runtime_cache[ $ip ];
		}

		// Layer 2: persistent transient cache.
		$transient_key = 'wc_af_proxy_' . md5( $ip );
		$cached        = get_transient( $transient_key );
		if ( false !== $cached ) {
			self::$runtime_cache[ $ip ] = $cached;
			return $cached;
		}

		// Live API call – reuse the same key that detect_proxy rule uses.
		// wp_remote_get() is the WordPress-standard HTTP client; it returns a
		// WP_Error on failure instead of emitting a PHP warning, so no @ suppressor
		// is needed and error details are available for logging.
		$api_key  = get_option( 'wc_settings_anti_fraud_proxycheck_api_key', '913st5-6a024j-t43896-i0t35y' );
		$url      = 'http://proxycheck.io/v2/' . rawurlencode( $ip )
					. '?key=' . rawurlencode( $api_key )
					. '&vpn=1&asn=1'
					. '&tag=' . rawurlencode( (string) home_url() );

		$response = wp_remote_get( $url, array( 'timeout' => 10 ) );

		if ( is_wp_error( $response ) ) {
			Af_Logger::debug( 'WC_AF_Proxy_Helper: proxycheck.io API call failed for IP ' . $ip . ' – ' . $response->get_error_message() );
			return null;
		}

		$contents = wp_remote_retrieve_body( $response );

		if ( empty( $contents ) ) {
			Af_Logger::debug( 'WC_AF_Proxy_Helper: empty response from proxycheck.io for IP ' . $ip );
			return null;
		}

		// json_decode() does not emit PHP warnings – @ was never needed here.
		// json_last_error() below is the correct way to detect decode failures.
		$res = json_decode( $contents, true );
		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $res ) ) {
			Af_Logger::debug( 'WC_AF_Proxy_Helper: invalid JSON from proxycheck.io for IP ' . $ip );
			return null;
		}

		$data = isset( $res[ $ip ] ) ? $res[ $ip ] : null;

		if ( null !== $data ) {
			set_transient( $transient_key, $data, DAY_IN_SECONDS );
			self::$runtime_cache[ $ip ] = $data;
			Af_Logger::debug( 'WC_AF_Proxy_Helper: cached proxycheck.io data for IP ' . $ip . ' – ' . wp_json_encode( $data ) );
		}

		return $data;
	}

	/**
	 * Determine whether an IP belongs to a known legitimate privacy or CDN
	 * service that shares IPs across many innocent users (e.g. iCloud Private
	 * Relay, Cloudflare CDN).
	 *
	 * When this returns true, fraud rules that rely on IP uniqueness (like
	 * "IP used with multiple addresses") should be skipped or down-weighted.
	 *
	 * @param string $ip IP address.
	 * @return bool True if the IP is from a trusted privacy/CDN service.
	 */
	public static function is_legitimate_proxy( $ip ) {
		$data = self::get_ip_data( $ip );

		if ( ! is_array( $data ) ) {
			return false;
		}

		// Check ASN against the trusted list.
		if ( ! empty( $data['asn'] ) ) {
			// Normalise: strip leading "AS" and whitespace.
			$asn = preg_replace( '/^AS/i', '', trim( (string) $data['asn'] ) );
			if ( in_array( $asn, self::TRUSTED_ASNS, true ) ) {
				Af_Logger::debug( sprintf(
					'WC_AF_Proxy_Helper: IP %s has trusted ASN AS%s – treating as legitimate privacy service',
					$ip,
					$asn
				) );
				return true;
			}
		}

		// Check proxy type for CDN traffic.
		if ( ! empty( $data['type'] ) && in_array( $data['type'], self::TRUSTED_PROXY_TYPES, true ) ) {
			Af_Logger::debug( sprintf(
				'WC_AF_Proxy_Helper: IP %s has trusted proxy type "%s" – treating as legitimate CDN',
				$ip,
				$data['type']
			) );
			return true;
		}

		return false;
	}

	/**
	 * Determine whether proxycheck.io considers the IP a proxy or VPN.
	 * This does NOT distinguish legitimate from suspicious – use
	 * is_suspicious_proxy() for fraud-scoring decisions.
	 *
	 * @param string $ip IP address.
	 * @return bool True if proxycheck.io reports proxy=yes.
	 */
	public static function is_proxy( $ip ) {
		$data = self::get_ip_data( $ip );

		if ( ! is_array( $data ) ) {
			return false;
		}

		return isset( $data['proxy'] ) && 'yes' === $data['proxy'];
	}

	/**
	 * Determine whether an IP is a SUSPICIOUS proxy (i.e. flagged as a proxy
	 * by proxycheck.io AND is NOT from a trusted privacy/CDN service).
	 *
	 * This is the correct method to call when making fraud-scoring decisions.
	 *
	 * @param string $ip IP address.
	 * @return bool True only when the IP is a proxy from an untrusted network.
	 */
	public static function is_suspicious_proxy( $ip ) {
		return self::is_proxy( $ip ) && ! self::is_legitimate_proxy( $ip );
	}

	/**
	 * Return a human-readable summary of what proxycheck.io knows about an IP,
	 * useful for adding to order explanation data.
	 *
	 * @param string $ip IP address.
	 * @return array Associative array with keys: asn, provider, type, proxy.
	 */
	public static function get_ip_summary( $ip ) {
		$data = self::get_ip_data( $ip );

		if ( ! is_array( $data ) ) {
			return array(
				'asn'      => 'unknown',
				'provider' => 'unknown',
				'type'     => 'unknown',
				'proxy'    => 'unknown',
			);
		}

		return array(
			'asn'      => isset( $data['asn'] ) ? $data['asn'] : 'unknown',
			'provider' => isset( $data['provider'] ) ? $data['provider'] : ( isset( $data['organisation'] ) ? $data['organisation'] : 'unknown' ),
			'type'     => isset( $data['type'] ) ? $data['type'] : 'unknown',
			'proxy'    => isset( $data['proxy'] ) ? $data['proxy'] : 'unknown',
		);
	}
}
