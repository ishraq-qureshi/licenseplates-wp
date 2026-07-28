<?php
/**
 * Searchanise filter params dto
 *
 * @package Searchanise/Search_Filter_Params_Factory
 */

namespace Searchanise\SmartWoocommerceSearch;

defined( 'ABSPATH' ) || exit;


/**
 * Factory for Search_Filter_Params_DTO
 */
class Search_Filter_Params_Factory {

	/**
	 * Create and returns Search_Filter_Params_DTO
	 *
	 * @param bool $review_enabled Review enabled flag.
	 *
	 * @return Search_Filter_Params_DTO
	 */
	public function create_from_request( $review_enabled = true ) {
		$restrict_by         = array();
		$union               = array();
		$min_price_clean     = isset( $_REQUEST['min_price'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['min_price'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$max_price_clean     = isset( $_REQUEST['max_price'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['max_price'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$rating_filter_clean = isset( $_REQUEST['rating_filter'] ) ? array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_REQUEST['rating_filter'] ) ) ) ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( '' !== $min_price_clean || '' !== $max_price_clean ) {
			$rate = Api::get_instance()->get_currency_rate();

			if ( ! empty( $rate ) && 1.0 != $rate ) {
				if ( null !== $min_price_clean ) {
					$min_price_clean *= $rate;
				}

				if ( null !== $max_price_clean ) {
					$max_price_clean *= $rate;
				}
			}

			$restrict_by['price'] = "{$min_price_clean},{$max_price_clean}";

			// Adds usergroup min price.
			$user_groups = Api::get_instance()->get_current_usergroup_ids();
			if ( ! empty( $user_groups ) ) {
				$_prices = array();

				foreach ( $user_groups as $usergroup_id ) {
					$_prices[] = Api::LABEL_FOR_PRICES_USERGROUP . $usergroup_id;
				}

				$union['price']['min'] = implode( '|', $_prices );
			}
		}

		// Prepare review filter.
		if ( ! empty( $rating_filter_clean ) && $review_enabled ) {
			$restrict_by['reviews_average_score'] = implode( '|', $rating_filter_clean );
		}

		// Preapre attributes filter.
		foreach ( $_REQUEST as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 0 === strpos( $key, 'filter_' ) ) {
				$attribute    = wc_sanitize_taxonomy_name( str_replace( 'filter_', '', $key ) );
				$taxonomy     = wc_attribute_taxonomy_name( $attribute );
				$filter_terms = ! empty( $value ) ? explode( ',', wp_unslash( $value ) ) : array();

				if ( empty( $filter_terms ) || ! taxonomy_exists( $taxonomy ) || ! wc_attribute_taxonomy_id_by_name( $attribute ) ) {
					// Invalid attribute filter.
					continue;
				}

				$filter_terms_clean = array_map( 'sanitize_title', $filter_terms );
				$query_type_clean   = ! empty( $_REQUEST[ 'query_type_' . $attribute ] ) && in_array( $_REQUEST[ 'query_type_' . $attribute ], array( 'and', 'or' ), true ) ? sanitize_text_field( wp_unslash( $_REQUEST[ 'query_type_' . $attribute ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$query_type_clean   = ! empty( $query_type_clean ) ? $query_type_clean : 'and';
				$attribute_id       = Async::get_taxonomy_id( $attribute );

				if ( 'and' == $query_type_clean ) {
					$restrict_by[ $attribute_id ] = implode( ',', $filter_terms_clean );
				} else {
					$restrict_by[ $attribute_id ] = implode( '|', $filter_terms_clean );
				}
			}
		}

		return new Search_Filter_Params_DTO( $restrict_by, $union );
	}
}
