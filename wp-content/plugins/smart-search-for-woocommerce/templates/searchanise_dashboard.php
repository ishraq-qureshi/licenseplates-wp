<?php
	namespace Searchanise\SmartWoocommerceSearch;

	defined('ABSPATH') || exit;
?>

<?php
	$se_allowed_html         = $this->get_allowed_html(); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$se_language_selector    = $this->render_language_selector(); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$se_translations         = $this->get_translations(); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$se_period_selector_html = $this->render_periods_selector(); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$se_checkbox_states      = $this->get_checkbox_states(); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$se_dashboard_link       = get_admin_url( null, '/admin.php?page=searchanise' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
?>

<div class="se-language-select">
	<?php echo wp_kses( $se_language_selector, $se_allowed_html ); ?>
</div>
<div class="se-dashboard-container">
	<div id="se-chart-error" class="se-hidden">
		<div class="se-error-contentainer">
			<div class="se-error-content">
				<h2><?php echo esc_attr($se_translations['chart_error_title']); ?></h2>
				<p><?php echo esc_html( $se_translations['chart_error'] ); ?></p>
			</div>
		</div>
	</div>
	<ul class="se-dashboard">
		<li class="se-analytics-select-wrapper">
			<div class="se-date-select">
				<?php echo wp_kses( $se_period_selector_html, $se_allowed_html); ?>
			</div>
			<div class="se-analytics-select">
				<ul class="se-analytics-select-list">
					<li><input type="checkbox" id="elm-total-searches" name="se_query[]" value="search_data" <?php echo esc_attr($se_checkbox_states['search_data']); ?> /><label for="elm-total-searches"><?php echo esc_html( $se_translations['total_searches'] ); ?></label></li>
					<li><input type="checkbox" id="elm-category-clicks" name="se_query[]" value="categories_clicks" <?php echo esc_attr($se_checkbox_states['categories_clicks']); ?> /><label for="elm-category-clicks"><?php echo esc_html( $se_translations['category_clicks'] ); ?></label></li>
					<li><input type="checkbox" id="elm-product-clicks" name="se_query[]" value="product_clicks" <?php echo esc_attr($se_checkbox_states['product_clicks']); ?> /><label for="elm-product-clicks"><?php echo esc_html( $se_translations['product_clicks'] ) ?></label></li>
					<li><input type="checkbox" id="elm-suggestion-clicks" name="se_query[]" value="suggestions_clicks" <?php echo esc_attr($se_checkbox_states['suggestions_clicks']); ?> /><label for="elm-suggestion-clicks"><?php echo esc_html( $se_translations['suggestion_clicks'] ); ?></label></li>
				</ul>
			</div>
			<div class="se-clear"></div>
		</li>
		<li class="se-graphs se-loading">
			<div id="se-chart"></div>
		</li>
		<li class="se-search-results-wrapper">
			<div class="se-top-search-queries">
				<h3><?php echo esc_html( $se_translations['top_search_queries'] ); ?></h3>
				<span class="se-no-results"><?php echo esc_html( $se_translations['no_results'] ); ?></span>
				<div class="se-results-content"></div>
			</div>
			<div class="se-top-search-no-result-queries">
				<h3><?php echo esc_html( $se_translations['top_search_queries_no_results'] ); ?></h3>
				<span class="se-no-results"><?php echo esc_html( $se_translations['no_results'] ); ?></span>
				<div class="se-results-content"></div>
			</div>
			<div class="se-clear"></div>
		</li>
	</ul>
</div>
<div class="se-go-dashboard">
	<a href="<?php echo esc_url( $se_dashboard_link ); ?>" class="button"><?php echo esc_html( $se_translations['go_dashboard'] ); ?></a>
</div>
