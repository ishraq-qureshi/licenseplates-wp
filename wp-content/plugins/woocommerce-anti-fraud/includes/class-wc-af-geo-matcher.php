<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Utility class to evaluate geographic matches (Browser Geo and IP Geo).
 *
 * Provides a unified pass/fail mathematical model based on:
 * Country, State (abbreviation normalized), City, and Postcode.
 */
class WC_AF_Geo_Matcher {

	/**
	 * Match order location details against detected geo location details.
	 *
	 * @param array $order_data {
	 *     @type string $country
	 *     @type string $state
	 *     @type string $city
	 *     @type string $postcode
	 * }
	 * @param array $geo_data {
	 *     @type string $country
	 *     @type string $state
	 *     @type string $city
	 *     @type string $postcode
	 * }
	 * @param bool $is_ip_rule Whether this check is for IP geolocation (enforces strict pass/fail only on Country)
	 *
	 * @return array {
	 *     @type bool $is_risk (true if failed/risk, false if passed/legitimate)
	 *     @type array $reasons Array of reason strings
	 *     @type array $data_evaluated Array for the explanation formatting
	 * }
	 */
	public static function is_match( $order_data, $geo_data, $is_ip_rule = false ) {
		$reasons = array();
		$matches = array();
		$mismatches = array();

		// Normalize Order Data
		$order_country  = self::normalize_country_to_iso( self::safe_string( $order_data['country'] ) );
		$order_state    = self::normalize_state( $order_country, self::safe_string( $order_data['state'] ) );
		$order_city     = strtolower( trim( self::safe_string( $order_data['city'] ) ) );
		$order_postcode = self::normalize_postcode( $order_country, self::safe_string( $order_data['postcode'] ) );

		// Normalize Geo Data
		// Country: convert full names (e.g. "United States") to ISO codes (e.g. "US")
		$geo_country = self::normalize_country_to_iso( self::safe_string( $geo_data['country'] ) );

		// State: when geo country is missing, fall back to order country so that full-name states
		// (e.g. "California") can still be resolved to their abbreviation ("CA") correctly.
		$geo_state_hint_country = ! empty( $geo_country ) ? $geo_country : $order_country;
		$geo_state    = self::normalize_state( $geo_state_hint_country, self::safe_string( $geo_data['state'] ) );
		$geo_city     = strtolower( trim( self::safe_string( $geo_data['city'] ) ) );
		$geo_postcode = self::normalize_postcode( $geo_country, self::safe_string( $geo_data['postcode'] ) );

		Af_Logger::debug( sprintf(
			'GeoMatcher raw input — order: country=%s state=%s city=%s | geo: country=%s state=%s city=%s (hint_country=%s)',
			self::safe_string( $order_data['country'] ),
			self::safe_string( $order_data['state'] ),
			self::safe_string( $order_data['city'] ),
			self::safe_string( $geo_data['country'] ),
			self::safe_string( $geo_data['state'] ),
			self::safe_string( $geo_data['city'] ),
			$geo_state_hint_country
		) );
		Af_Logger::debug( sprintf(
			'GeoMatcher normalized — order: country=%s state=%s | geo: country=%s state=%s',
			$order_country, $order_state, $geo_country, $geo_state
		) );

		// Track data evaluated for the explanation UI
		$data_evaluated = array(
			'billing_city'          => ucwords( $order_city ),
			'billing_state'         => strtoupper( $order_state ),
			'billing_country'       => $order_country,
			'billing_postcode'      => $order_postcode,
			'detected_geo_city'     => ! empty( $geo_city ) ? ucwords( $geo_city ) : 'Not detected',
			'detected_geo_state'    => ! empty( $geo_state ) ? strtoupper( $geo_state ) : 'Not detected',
			'detected_geo_country'  => ! empty( $geo_country ) ? $geo_country : 'Not detected',
			'detected_geo_postcode' => ! empty( $geo_postcode ) ? $geo_postcode : 'Not detected',
		);

		// Fail early if Country explicitly mismatches
		if ( ! empty( $geo_country ) && ! empty( $order_country ) ) {
			// Specific handling for US territories
			$us_territories = array('UM', 'AS', 'GU', 'MP', 'PR', 'VI');
			$country_matched = ( $order_country === $geo_country ) || ( in_array( $order_country, $us_territories ) && 'US' === $geo_country );

			if ( ! $country_matched ) {
				$mismatches[] = sprintf( 'Country mismatch: Order "%s" vs Detected "%s"', $order_country, $geo_country );
				$reasons[] = implode( '; ', $mismatches );
				return array(
					'is_risk'        => true,
					'reasons'        => $reasons,
					'data_evaluated' => $data_evaluated
				);
			} else {
				$matches[] = 'Country matches';
			}
		} elseif ( empty( $geo_country ) ) {
			return;// No country data explicitly provided by API. Fallback and do not flag false positives purely on missing country.
		}

		// Check Sub-fields
		$state_matches = false;
		if ( ! empty( $order_state ) && ! empty( $geo_state ) ) {
			if ( $order_state === $geo_state ) {
				$state_matches = true;
				$matches[] = 'State matches';
			} else {
				$mismatches[] = sprintf( 'State mismatch: Order "%s" vs Detected "%s"', strtoupper( $order_state ), strtoupper( $geo_state ) );
			}
		}

		$city_matches = false;
		if ( ! empty( $order_city ) && ! empty( $geo_city ) ) {
			if ( $order_city === $geo_city ) {
				$city_matches = true;
				$matches[] = 'City matches';
			} else {
				$mismatches[] = sprintf( 'City mismatch: Order "%s" vs Detected "%s"', ucwords( $order_city ), ucwords( $geo_city ) );
			}
		}

		$postcode_matches = false;
		if ( ! empty( $order_postcode ) && ! empty( $geo_postcode ) ) {
			if ( $order_postcode === $geo_postcode ) {
				$postcode_matches = true;
				$matches[] = 'Postcode matches';
			} else {
				$mismatches[] = sprintf( 'Postcode mismatch: Order "%s" vs Detected "%s"', $order_postcode, $geo_postcode );
			}
		}

		// Compile reason string
		$reason_parts = array();
		if ( ! empty( $matches ) ) {
			$reason_parts[] = 'Matches: ' . implode( ', ', $matches );
		}
		if ( ! empty( $mismatches ) ) {
			$reason_parts[] = 'Mismatches: ' . implode( '; ', $mismatches );
		}
		
		$reasons[] = ! empty( $reason_parts ) ? implode( '. ', $reason_parts ) : 'Geographic location matched implicitly';

		// Evaluation Logic

		// IP Rule: country-level only (already flagged explicit country mismatch above).
		if ( $is_ip_rule ) {
			return array(
				'is_risk'        => false,
				'reasons'        => $reasons,
				'data_evaluated' => $data_evaluated
			);
		}

		// Browser Geo Rule — tiered evaluation model.
		//
		// Industry best practice (Stripe Radar, Signifyd, Riskified):
		//   • Country mismatch  → high risk   (handled above as early return)
		//   • State mismatch    → risk
		//   • Postcode mismatch → risk only when strictness is "Country + State + Postal"
		//   • City mismatch     → NEVER a risk factor; IP geolocation is not city-precise
		//
		// Strictness is controlled by the "Geo Matching Strictness" admin setting.
		$strictness = get_option( 'wc_af_geo_match_strictness', 'country_state' );
		$is_risk    = false;

		Af_Logger::debug( 'GeoMatcher strictness: ' . $strictness );

		switch ( $strictness ) {

			case 'country_only':
				// Country was already checked above; anything reaching here is safe.
				$is_risk = false;
				break;

			case 'country_state_postal':
				// State mismatch → risk
				if ( ! empty( $order_state ) && ! empty( $geo_state ) && ! $state_matches ) {
					$is_risk = true;
					break;
				}
				// State matched or absent → also check postcode
				if ( ! empty( $order_postcode ) && ! empty( $geo_postcode ) && ! $postcode_matches ) {
					$is_risk = true;
				}
				break;

			case 'country_state':
			default:
				// Only flag when both sides have state data and they explicitly disagree.
				// City mismatch alone never triggers risk.
				if ( ! empty( $order_state ) && ! empty( $geo_state ) && ! $state_matches ) {
					$is_risk = true;
				}
				break;
		}

		return array(
			'is_risk'        => $is_risk,
			'reasons'        => $reasons,
			'data_evaluated' => $data_evaluated
		);
	}

	/**
	 * Normalize a country value to a 2-letter ISO code.
	 *
	 * Accepts ISO codes ("US"), full English names ("United States"), and mixed-case
	 * variants.  Returns an uppercase ISO code, or the original uppercased value when
	 * no match is found (so callers can still attempt a direct comparison).
	 */
	private static function normalize_country_to_iso( $country_string ) {
		if ( empty( $country_string ) ) {
			return '';
		}

		$upper = strtoupper( trim( $country_string ) );

		// Already a 2-letter ISO code
		if ( strlen( $upper ) === 2 ) {
			return $upper;
		}

		// Try to resolve full name via WooCommerce country list
		if ( function_exists( 'WC' ) && WC()->countries ) {
			$countries  = WC()->countries->get_countries();
			$search     = strtolower( trim( $country_string ) );
			foreach ( $countries as $iso => $name ) {
				if ( strtolower( trim( $name ) ) === $search ) {
					return strtoupper( $iso );
				}
			}
		}

		// Return whatever we have (already uppercased); downstream comparison may still work
		return $upper;
	}

	/**
	 * Normalize state to abbreviation using WooCommerce maps.
	 */
	private static function normalize_state( $country_code, $state_string ) {
		if ( empty( $state_string ) || empty( $country_code ) ) {
			return strtolower( trim( $state_string ) );
		}
		
		if ( ! function_exists('WC') ) {
			return strtolower( trim( $state_string ) );
		}

		$states = WC()->countries->get_states( $country_code );
		if ( empty( $states ) ) {
			return strtolower( trim( $state_string ) );
		}

		// Check if it's already an abbreviation (key in array)
		if ( array_key_exists( strtoupper( $state_string ), $states ) ) {
			return strtolower( trim( $state_string ) );
		}

		// Check if it matches a full name, return the abbreviation
		$search_state = strtolower( trim( $state_string ) );
		foreach ( $states as $abbr => $full_name ) {
			if ( strtolower( trim( $full_name ) ) === $search_state ) {
				return strtolower( trim( $abbr ) );
			}
		}

		return strtolower( trim( $state_string ) );
	}

	/**
	 * Normalize postcode based on country rules.
	 */
	private static function normalize_postcode( $country_code, $postcode ) {
		if ( empty( $postcode ) ) {
			return '';
		}

		// Strip everything except alphanumeric
		$normalized = preg_replace( '/[^0-9a-zA-Z]/i', '', $postcode );

		// For US, compare using the 5-digit ZIP code structure to rule out ZIP+4 discrepancies
		if ( 'US' === $country_code ) {
			return substr( $normalized, 0, 5 );
		}

		return $normalized;
	}

	private static function safe_string( $val ) {
		return isset( $val ) ? strval( $val ) : '';
	}

}
