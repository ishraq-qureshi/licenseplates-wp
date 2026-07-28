<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! interface_exists( 'WC_AF_IRule' ) ) {
	interface WC_AF_IRule {

		/**
		 * Check if the current rule finds a risk in order. The method must return a boolean.
		 *
		 * @param WC_Order $order
		 *
		 * @since  1.0.0
		 *
		 * @return bool
		 */
		public function is_risk( WC_Order $order );
	}
}

if ( ! class_exists( 'WC_AF_Rule' ) ) {
	abstract class WC_AF_Rule implements WC_AF_IRule {

		private $id;
		private $label;
		private $risk_points;
		/**
		 * Detailed explanation of why this rule flagged the order
		 *
		 * @var array
		 * @since 7.2.0
		 */
		protected $detailed_explanation = array();

		/**
		 * Constructor
		 *
		 * @param $id
		 * @param $label
		 * @param $risk_points
		 *
		 * @since  1.0.0
		 */
		public function __construct( $id, $label, $risk_points ) {
			$this->id          = $id;
			$this->label       = $label;
			$this->risk_points = $risk_points;
			$this->detailed_explanation = array();
		}

		/**
		 * Get Id
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_id() {
			return $this->id;
		}

		/**
		 * Set Id
		 *
		 * @param $id
		 *
		 * @since  1.0.0
		 */
		public function set_id( $id ) {
			$this->id = $id;
		}

		/**
		 * Get Lable
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_label() {
			return $this->label;
		}

		/**
		 * Set lable
		 *
		 * @param $label
		 *
		 * @since  1.0.0
		 */
		public function set_label( $label ) {
			$this->label = $label;
		}

		/**
		 * Get risk point
		 *
		 * @since  1.0.0
		 *
		 * @return int
		 */
		public function get_risk_points() {
			return $this->risk_points;
		}

		/**
		 * Set risk point
		 *
		 * @param $risk_points
		 *
		 * @since  1.0.0
		 */
		public function set_risk_points( $risk_points ) {
			$this->risk_points = $risk_points;
		}

	/**
	 * Get detailed explanation
	 *
	 * @since  7.2.0
	 * @return array
	 */
		public function get_detailed_explanation() {
			return $this->detailed_explanation;
		}

	/**
	 * Set detailed explanation
	 *
	 * @param array $explanation
	 * @since  7.2.0
	 */
		public function set_detailed_explanation( $explanation ) {
			$this->detailed_explanation = $explanation;
		}

	/**
	 * Add an item to the detailed explanation
	 *
	 * @param string $key
	 * @param mixed $value
	 * @since  7.2.0
	 */
		public function add_explanation_item( $key, $value ) {
			$this->detailed_explanation[ $key ] = $value;
		}

	/**
	 * Create JSON object containing rule data
	 *
	 * @since  1.0.0
	 */
		public function to_json() {
			return json_encode(
			array(
				'id' => $this->get_id(),
				'label' => $this->get_label(),
				'risk_points' => $this->get_risk_points(),
				'detailed_explanation' => $this->get_detailed_explanation(),
			)
			);
		}

	}
}

