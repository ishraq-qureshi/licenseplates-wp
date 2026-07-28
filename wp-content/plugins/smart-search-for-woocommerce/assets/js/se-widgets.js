/**
 * Searchanise widget
 *
 * @package Searchanise
 */

Searchanise = {};
Searchanise.host = searchanise_options.host;
Searchanise.api_key = searchanise_options.api_key;
Searchanise.SearchInput = searchanise_options.search_input;

Searchanise.AutoCmpParams = {};
Searchanise.AutoCmpParams.union = {};

if (searchanise_options.cur_label_for_usergroup != '') {
	Searchanise.AutoCmpParams.union.price = {};
	Searchanise.AutoCmpParams.union.price.min = searchanise_options.cur_label_for_usergroup;
}

if (searchanise_options.max_cur_label_for_usergroup != '') {
	Searchanise.AutoCmpParams.union.max_price = {};
	Searchanise.AutoCmpParams.union.max_price.min = searchanise_options.max_cur_label_for_usergroup;
}

if (searchanise_options.list_cur_label_for_usergroup != '') {
	Searchanise.AutoCmpParams.union.list_price = {};
	Searchanise.AutoCmpParams.union.list_price.min = searchanise_options.list_cur_label_for_usergroup;
}

Searchanise.AutoCmpParams.restrictBy = {};
Searchanise.AutoCmpParams.restrictBy.visibility = 'visible|catalog|search';
Searchanise.AutoCmpParams.restrictBy.status = 'publish';

if (searchanise_options.hide_out_of_stock_products == 'Y') {
	Searchanise.AutoCmpParams.restrictBy.is_in_stock = 'Y';
}

if (searchanise_options.usergroup_ids) {
	Searchanise.AutoCmpParams.restrictBy.usergroup_ids = searchanise_options.usergroup_ids;
}

Searchanise.AutoCmpParams.recentlyViewedProducts = searchanise_options.recentlyViewedProducts;

Searchanise.ResultsParams = {};
Searchanise.ResultsParams.union = {};

if (searchanise_options.cur_label_for_usergroup != '') {
	Searchanise.ResultsParams.union.price = {};
	Searchanise.ResultsParams.union.price.min = searchanise_options.cur_label_for_usergroup;
}

if (searchanise_options.max_cur_label_for_usergroup != '') {
	Searchanise.ResultsParams.union.max_price = {};
	Searchanise.ResultsParams.union.max_price.min = searchanise_options.max_cur_label_for_usergroup;
}

if (searchanise_options.list_cur_label_for_usergroup != '') {
	Searchanise.ResultsParams.union.list_price = {};
	Searchanise.ResultsParams.union.list_price.min = searchanise_options.list_cur_label_for_usergroup;
}

Searchanise.ResultsParams.restrictBy = {};
Searchanise.ResultsParams.restrictBy.visibility = 'visible|catalog|search';
Searchanise.ResultsParams.restrictBy.status = 'publish';

if (searchanise_options.hide_out_of_stock_products == 'Y') {
	Searchanise.ResultsParams.restrictBy.is_in_stock = 'Y';
}

if (searchanise_options.usergroup_ids) {
	Searchanise.ResultsParams.restrictBy.usergroup_ids = searchanise_options.usergroup_ids;
}

Searchanise.ResultsParams.recentlyViewedProducts = searchanise_options.recentlyViewedProducts;

Searchanise.RecommendationsParams = {};
Searchanise.RecommendationsParams.union = {};

if (searchanise_options.cur_label_for_usergroup != '') {
	Searchanise.RecommendationsParams.union.price = {};
	Searchanise.RecommendationsParams.union.price.min = searchanise_options.cur_label_for_usergroup;
}

if (searchanise_options.max_cur_label_for_usergroup != '') {
	Searchanise.RecommendationsParams.union.max_price = {};
	Searchanise.RecommendationsParams.union.max_price.min = searchanise_options.max_cur_label_for_usergroup;
}

if (searchanise_options.list_cur_label_for_usergroup != '') {
	Searchanise.RecommendationsParams.union.list_price = {};
	Searchanise.RecommendationsParams.union.list_price.min = searchanise_options.list_cur_label_for_usergroup;
}

Searchanise.RecommendationsParams.restrictBy = {};
Searchanise.RecommendationsParams.restrictBy.visibility = 'visible|catalog|search';
Searchanise.RecommendationsParams.restrictBy.status = 'publish';

if (searchanise_options.hide_out_of_stock_products == 'Y') {
	Searchanise.RecommendationsParams.restrictBy.is_in_stock = 'Y';
}

if (searchanise_options.usergroup_ids) {
	Searchanise.RecommendationsParams.restrictBy.usergroup_ids = searchanise_options.usergroup_ids;
}

Searchanise.RecommendationsParams.recentlyViewedProducts = searchanise_options.recentlyViewedProducts;

if (searchanise_options.use_wp_jquery) {
	Searchanise.forceUseExternalJQuery = true;
}

Searchanise.options = {};
Searchanise.options.ResultsDiv = '#snize_results';
Searchanise.options.ResultsFormPath = searchanise_options.results_form_path;
Searchanise.options.ResultsFallbackUrl = searchanise_options.results_fallback_url;
Searchanise.options.ResultsAddToCartUrl = searchanise_options.results_add_to_cart_url;

if (searchanise_options.hideEmptyPrice) {
	Searchanise.options.AutocompleteZeroPriceAction = "hide_zero_price";
	Searchanise.options.ResultsZeroPriceAction = "hide_zero_price";
}

Searchanise.options.facetBy = {};
Searchanise.options.facetBy.price = {};
Searchanise.options.facetBy.price.type = 'slider';

Searchanise.options.PriceFormat = {
	rate : searchanise_options.rate,
	symbol: searchanise_options.symbol,
	decimals: searchanise_options.decimals,
	decimals_separator: searchanise_options.decimals_separator,
	thousands_separator: searchanise_options.thousands_separator,
	after: searchanise_options.currency_position_after
};

(function() {
	var __se = document.createElement( 'script' );
	__se.src = searchanise_options.host + '/widgets/v1.0/init.js';
	__se.setAttribute( 'async', 'true' );
	var s = document.getElementsByTagName( 'script' )[0]; s.parentNode.insertBefore( __se, s );
})();
