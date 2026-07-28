<?php
/**
 * Searchanise Admin dashboard
 *
 * @package Searchanise/AdminDashboard
 */

namespace Searchanise\SmartWoocommerceSearch;

defined( 'ABSPATH' ) || exit;

/**
 * Admin dashboard class
 */
class Admin_Dashboard {

	const DEFAULT_PERIOD           = 'Y';
	const KEY_PERIOD               = 'se-dashboard-period';
	const KEY_LANGUAGE             = 'se-dashboard-language';
	const KEY_CHECKBOX             = 'se-dashboard-select-';
	const MAX_SEARCHES_STRINGS     = 5;
	const MAX_TEXT_SEARCHES_LENGTH = 40;

	/**
	 * Lang code
	 *
	 * @var string
	 */
	private $lang_code = '';

	/**
	 * Init analytics scripts. Called in wp_dashboard_setup
	 */
	public static function init() {
		if ( ! Api::get_instance()->is_show_analytics_on_dashboard() ) {
			return;
		}

		$se_dashboard_js_path  = SE_BASE_DIR . '/assets/js/se-dashboard.js';
		$se_dashboard_css_path = SE_BASE_DIR . '/assets/css/se-dashboard.css';

		$dashboard = new self();
		wp_enqueue_script( 'external-google-charts', 'https://www.gstatic.com/charts/loader.js', array(), SE_PLUGIN_VERSION, false );
		wp_enqueue_script( 'cookie-js', plugins_url( SE_BASE_DIR . '/assets/js/cookie.min.js' ), array( 'jquery' ), SE_PLUGIN_VERSION, false );
		wp_register_script( 'searchanise-dashboard', plugins_url( $se_dashboard_js_path ), array( 'jquery', 'external-google-charts', 'cookie-js' ), SE_PLUGIN_VERSION, true );
		wp_register_style( 'searchanise-dashboard', plugins_url( $se_dashboard_css_path ), array(), SE_PLUGIN_VERSION, false );

		wp_add_dashboard_widget( 'searchanise_analytics', __( 'Smart Search Analytics by <span class="se-logo">Searchanise</span>', 'smart-search-for-woocommerce' ), array( $dashboard, 'analytics_handler' ) );
	}

	/**
	 * Returns allow html tags in dashboard
	 *
	 * @return array
	 */
	public function get_allowed_html() {
		return array(
			'div'    => array(
				'class' => array(),
				'id'    => array(),
			),
			'h2'     => array(),
			'h3'     => array(),
			'p'      => array(),
			'ul'     => array(
				'class' => array(),
			),
			'span'   => array(
				'class' => array(),
			),
			'li'     => array(
				'class' => array(),
			),
			'select' => array(
				'name' => array(),
				'id'   => array(),
			),
			'option' => array(
				'value'    => array(),
				'selected' => array(),
			),
			'input'  => array(
				'type'    => array(),
				'id'      => array(),
				'name'    => array(),
				'value'   => array(),
				'checked' => array(),
			),
			'label'  => array(
				'for' => array(),
			),
			'a'      => array(
				'href'  => array(),
				'class' => array(),
			),
		);
	}

	/**
	 * Display analytics dashboard
	 */
	public function analytics_handler() {
		$this->lang_code = Api::get_instance()->get_locale();
		$translations    = $this->get_translations();

		$dashboard_options = array(
			'host'                   => is_ssl() ? str_replace( 'http://', 'https://', SE_SERVICE_URL ) : SE_SERVICE_URL,
			'url_path'               => '/getanalytics/woocommerce',
			'search_queries_limit'   => self::MAX_SEARCHES_STRINGS,
			'max_search_text_length' => self::MAX_TEXT_SEARCHES_LENGTH,
			'txt'                    => $translations,
			'engines'                => $this->get_dashboard_engines(),
			'chart_language'         => Api::get_instance()->get_iso_lang_name( $this->lang_code ),
		);

		wp_localize_script( 'searchanise-dashboard', 'searchanise_dashboard_options', $dashboard_options );
		wp_enqueue_style( 'searchanise-dashboard' );
		wp_enqueue_script( 'searchanise-dashboard' );

		require_once SE_TEMPLATES_PATH . 'searchanise_dashboard.php';
	}

	/**
	 * Generate language selector html code
	 *
	 * @param bool $output If true, selector content will be displayed otherwise return.
	 *
	 * @return string
	 */
	public function render_language_selector( $output = false ) {
		$html             = '';
		$engines_data     = $this->get_dashboard_engines();
		$current_language = $this->get_current_language();
		$allowed_html     = array(
			'div'    => array(
				'class' => array(),
				'id'    => array(),
			),
			'h3'     => array(),
			'select' => array(
				'name' => array(),
				'id'   => array(),
			),
			'option' => array(
				'value'    => array(),
				'selected' => array(),
			),
			'input'  => array(
				'type'    => array(),
				'id'      => array(),
				'name'    => array(),
				'value'   => array(),
				'checked' => array(),
			),
		);

		if ( count( $engines_data ) > 1 ) {
			$html  = '<div class="se-language-select-value">';
			$html .= '<select name="se_language" id="se-language">';
			foreach ( $engines_data as $e ) {
				$selected = $e['lang_code'] == $current_language ? ' selected="selected"' : '';
				$html    .= "<option value=\"{$e['lang_code']}\"{$selected}>{$e['language_name']}</option>";
			}
			$html .= '</select></div>';
			$html .= '<div class="se-language-select-title"><h3>Language</h3></div>';
		} elseif ( count( $engines_data ) == 1 ) {
			$e     = reset( $engines_data );
			$html .= "<input type=\"hidden\" name=\"se_language\" id=\"se-language\" value = \"{$e['lang_code']}\" />";
		}

		if ( $output ) {
			echo wp_kses( $html, $allowed_html );
		}

		return $html;
	}

	/**
	 * Generate period selector html
	 *
	 * @param bool $output If true, selector content will be displayed otherwise return.
	 *
	 * @return mixed
	 */
	public function render_periods_selector( $output = false ) {
		$selected_period   = $this->get_current_period();
		$available_periods = $this->get_available_periods();
		$allowed_html      = array(
			'select' => array(
				'name' => array(),
				'id'   => array(),
			),
			'option' => array(
				'value'    => array(),
				'selected' => array(),
			),
		);

		$html = '<select name="se_time_period" id="se-time-period">';

		foreach ( $available_periods as $period => $name ) {
			$selected = $period == $selected_period ? ' selected="selected"' : '';
			$html    .= "<option value=\"{$period}\"{$selected}>{$name}</option>";
		}
		$html .= '</select>';

		if ( $output ) {
			echo wp_kses( $html, $allowed_html );
			return true;
		} else {
			return $html;
		}
	}

	/**
	 * Returns period selector variants
	 *
	 * @return array
	 */
	public function get_available_periods() {
		return array(
			'W'  => __( 'This week', 'smart-search-for-woocommerce' ),
			'LW' => __( 'Last week', 'smart-search-for-woocommerce' ),
			'M'  => __( 'This month', 'smart-search-for-woocommerce' ),
			'LM' => __( 'Last month', 'smart-search-for-woocommerce' ),
			'Y'  => __( 'This year', 'smart-search-for-woocommerce' ),
			'LY' => __( 'Last year', 'smart-search-for-woocommerce' ),
		);
	}

	/**
	 * Returns current selected language
	 *
	 * @return string
	 */
	public function get_current_language() {
		$engines_data = $this->get_dashboard_engines();

		if ( ! empty( $_SESSION[ self::KEY_LANGUAGE ] ) ) {
			$lang_code = sanitize_option( 'blog_charset', wp_unslash( $_SESSION[ self::KEY_LANGUAGE ] ) );
		} elseif ( ! empty( $_COOKIE[ self::KEY_LANGUAGE ] ) ) {
			$lang_code = sanitize_option( 'blog_charset', wp_unslash( $_COOKIE[ self::KEY_LANGUAGE ] ) );
		}

		if ( ! empty( $lang_code ) && key_exists( $lang_code, $engines_data ) ) {
			return $lang_code;
		} else {
			return $this->lang_code;
		}
	}

	/**
	 * Retun current selected period
	 *
	 * @return string
	 */
	public function get_current_period() {
		$period            = self::DEFAULT_PERIOD;
		$available_periods = $this->get_available_periods();

		if ( ! empty( $_SESSION[ self::KEY_PERIOD ] ) ) {
			$period = strtoupper( sanitize_title( $_SESSION[ self::KEY_PERIOD ] ) );
		} elseif ( ! empty( $_COOKIE[ self::KEY_PERIOD ] ) ) {
			$period = strtoupper( sanitize_title( wp_unslash( $_COOKIE[ self::KEY_PERIOD ] ) ) );
		}

		return key_exists( $period, $available_periods ) ? $period : self::DEFAULT_PERIOD;
	}

	/**
	 * Returns checkbox states
	 */
	public function get_checkbox_states() {
		$states = array();
		$names  = array( 'search_data', 'categories_clicks', 'product_clicks', 'suggestions_clicks' );

		foreach ( $names as $name ) {
			$key   = self::KEY_CHECKBOX . $name;
			$value = 'true';

			if ( ! empty( $_SESSION[ $key ] ) ) {
				$value = sanitize_key( $_SESSION[ $key ] );
			} elseif ( ! empty( $_COOKIE[ $key ] ) ) {
				$value = sanitize_key( $_COOKIE[ $key ] );
			}

			$states[ $name ] = 'true' == $value ? 'checked="checked"' : '';
		}

		return $states;
	}

	/**
	 * Returns translations
	 *
	 * @return array
	 */
	public function get_translations() {
		return array(
			'date'                          => __( 'Date', 'smart-search-for-woocommerce' ),
			'total_searches'                => __( 'Total searches', 'smart-search-for-woocommerce' ),
			'product_clicks'                => __( 'Product Clicks', 'smart-search-for-woocommerce' ),
			'category_clicks'               => __( 'Category Clicks', 'smart-search-for-woocommerce' ),
			'suggestion_clicks'             => __( 'Suggestion Clicks', 'smart-search-for-woocommerce' ),
			'go_dashboard'                  => __( 'Go to Dashboard', 'smart-search-for-woocommerce' ),
			'no_results'                    => __( 'Sorry, nothing to report', 'smart-search-for-woocommerce' ),
			'top_search_queries'            => __( 'Top search queries', 'smart-search-for-woocommerce' ),
			'top_search_queries_no_results' => __( 'Top search with no results', 'smart-search-for-woocommerce' ),
			'chart_error_title'             => __( 'Something went wrong', 'smart-search-for-woocommerce' ),
			/* translators: %s: support email */
			'chart_error'                   => sprintf( __( 'We couldn’t get the data, please try to check it later or contact <a href="mailto:%s" target="blank">Searchanise support</a>', 'smart-search-for-woocommerce' ), SE_SUPPORT_EMAIL ),
		);
	}

	/**
	 * Returns engines available for dashboard statistic
	 *
	 * @return array
	 */
	public function get_dashboard_engines() {
		static $engines = array();

		if ( empty( $engines ) ) {
			$engines = Api::get_instance()->get_engines();
		}

		return $engines;
	}
}
