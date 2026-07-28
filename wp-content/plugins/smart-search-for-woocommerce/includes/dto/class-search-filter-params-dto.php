<?php
/**
 * Searchanise filter params dto
 *
 * @package Searchanise/Search_Filter_Params_DTO
 */

namespace Searchanise\SmartWoocommerceSearch;

defined( 'ABSPATH' ) || exit;

/**
 * Search filter params data object
 */
class Search_Filter_Params_DTO {

	/**
	 * Restrict by params.
	 *
	 * @var array $restrict_by
	 */
	public $restrict_by = array();

	/**
	 * Union params.
	 *
	 * @var array $union
	 */
	public $union = array();

	/**
	 * Constructor
	 *
	 * @param array $restrict_by Restrict by params.
	 * @param array $union Union params.
	 */
	public function __construct( $restrict_by, $union ) {
		$this->restrict_by = $restrict_by;
		$this->union       = $union;
	}
}
