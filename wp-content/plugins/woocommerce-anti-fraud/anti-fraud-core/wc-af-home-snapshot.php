<?php
/**
 * Home / default section — control center data (read-only heuristics).
 *
 * @package WooCommerce_Anti_Fraud
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Build structured data for the Anti-Fraud settings Home control center.
 *
 * @return array{
 *   protection_level: string,
 *   protection_label: string,
 *   protection_summary: string,
 *   integrations: array<int, array{name:string,state:string,url:string,note?:string}>,
 *   recommended_next: array{title:string,description:string,url:string}|null,
 *   likely_card_attack_activity: bool,
 *   urls: array<string,string>,
 *   recaptcha_type: string,
 *   dashboard_enabled: bool,
 *   docs_url: string,
 *   support_url: string,
 *   reviews_url: string,
 *   recommended_setup: array{ steps: array, done_count: int, total_count: int, percent: int, is_complete: bool, optional_note: string, optional_links: array<int, array{label:string,url:string}> },
 *   integrations_footnote: array{text:string,url:string,label:string},
 *   protection_checks: array<int, array{label:string,value:string,context:string,url:string,level:string,cta:string}>  // level: critical|warning|healthy|neutral|info
 * }
 */
function wc_af_get_home_control_center_data() {
	$base = admin_url( 'admin.php?page=wc-settings&tab=wc_af' );

	$section_url = static function ( $slug ) use ( $base ) {
		return '' === $slug ? $base : add_query_arg( 'section', $slug, $base );
	};

	$recaptcha_on    = get_option( 'wc_af_recaptcha_enable_captcha', 'no' ) === 'yes';
	$recaptcha_type  = get_option( 'wc_af_recaptcha_type', 'google_recaptcha' );
	$paypal_detected = get_option( 'paypal_acp_plugindetected', 'no' ) === 'yes';
	$paypal_acp      = get_option( 'wc_af_paypal_acp_enabled', 'no' ) === 'yes';
	$marketplace_on  = get_option( 'wc_af_marketplace_detection_enabled', 'no' ) === 'yes';

	$minfraud_score    = get_option( 'wc_af_maxmind_type', 'no' ) === 'yes';
	$minfraud_insights = get_option( 'wc_af_maxmind_insights', 'no' ) === 'yes';
	$minfraud_factors  = get_option( 'wc_af_maxmind_factors', 'no' ) === 'yes';
	$trust_swiftly     = get_option( 'wc_af_trust_swiftly_type', 'no' ) === 'yes';
	$ai_on             = get_option( 'wc_af_ai_fraud_prevention_check', 'no' ) === 'yes';
	$email_alerts      = get_option( 'wc_af_email_notification', 'no' ) === 'yes';
	$dashboard_on      = get_option( 'wc_af_enable_dashboard', 'yes' ) === 'yes';

	$maxmind_auth = get_option( 'wc_af_maxmind_authentication', null );
	// After save, option is boolean true/false; treat only explicit false as a credential problem.
	$minfraud_cred_issue = $minfraud_score && false === $maxmind_auth;

	$recaptcha_type_labels = array(
		'google_recaptcha' => __( 'Google reCAPTCHA', 'woocommerce-anti-fraud' ),
		'cf_turnstile'     => __( 'Cloudflare Turnstile', 'woocommerce-anti-fraud' ),
	);
	$recaptcha_type_label = isset( $recaptcha_type_labels[ $recaptcha_type ] )
		? $recaptcha_type_labels[ $recaptcha_type ]
		: $recaptcha_type;

	// Overall protection headline (pragmatic heuristics).
	if ( ! $recaptcha_on ) {
		$level   = 'needs_attention';
		$label   = __( 'CAPTCHA not on yet', 'woocommerce-anti-fraud' );
		$summary = __( 'Turn on Checkout CAPTCHA first — it blocks most bots and card testers.', 'woocommerce-anti-fraud' );
	} elseif ( $paypal_detected && ! $paypal_acp ) {
		$level   = 'moderate';
		$label   = __( 'PayPal: one more step', 'woocommerce-anti-fraud' );
		$summary = __( 'CAPTCHA is on. Align PayPal so every checkout path is covered.', 'woocommerce-anti-fraud' );
	} elseif ( $paypal_detected && 'google_recaptcha' !== $recaptcha_type ) {
		$level   = 'moderate';
		$label   = __( 'Review PayPal + CAPTCHA', 'woocommerce-anti-fraud' );
		$summary = __( 'PayPal works best with Google reCAPTCHA — double-check your pairing.', 'woocommerce-anti-fraud' );
	} else {
		$level   = 'strong';
		$label   = __( 'Baseline looks good', 'woocommerce-anti-fraud' );
		$summary = __( 'Core defenses are on. Add rules or integrations when you are ready.', 'woocommerce-anti-fraud' );
	}

	$integrations = array(
		array(
			'name'  => __( 'Checkout CAPTCHA', 'woocommerce-anti-fraud' ),
			'state' => $recaptcha_on ? 'on' : 'off',
			'url'   => $section_url( 'recaptcha_settings' ),
			'note'  => $recaptcha_on ? $recaptcha_type_label : '',
		),
		array(
			'name'  => __( 'MaxMind minFraud · Score', 'woocommerce-anti-fraud' ),
			'state' => $minfraud_score ? 'on' : 'off',
			'url'   => $section_url( 'minfraud_settings' ),
			'note'  => $minfraud_cred_issue ? __( 'Check API credentials', 'woocommerce-anti-fraud' ) : '',
		),
		array(
			'name'  => __( 'MaxMind · Insights', 'woocommerce-anti-fraud' ),
			'state' => $minfraud_insights ? 'on' : 'off',
			'url'   => $section_url( 'minfraud_insights_settings' ),
		),
		array(
			'name'  => __( 'MaxMind · Factors', 'woocommerce-anti-fraud' ),
			'state' => $minfraud_factors ? 'on' : 'off',
			'url'   => $section_url( 'minfraud_factors_settings' ),
		),
		array(
			'name'  => __( 'Trust Swiftly', 'woocommerce-anti-fraud' ),
			'state' => $trust_swiftly ? 'on' : 'off',
			'url'   => $section_url( 'trust_swiftly_settings' ),
		),
		array(
			'name'  => __( 'AI fraud signals', 'woocommerce-anti-fraud' ),
			'state' => $ai_on ? 'on' : 'off',
			'url'   => $section_url( 'ai_fraud_prevention' ),
		),
	);
	$docs_url    = 'https://docs.woocommerce.com/document/woocommerce-anti-fraud/';
	$support_url = defined( 'WC_AF_SUPPORT_TICKET_URL' ) ? WC_AF_SUPPORT_TICKET_URL : 'https://woocommerce.com/my-account/create-a-ticket/';
	$reviews_url = 'https://woocommerce.com/products/woocommerce-anti-fraud/#reviews-start';

	// Checkout captcha “ready” (same heuristic as Card attacks incident panel).
	$captcha_ok     = get_option( 'wc_af_admin_recaptcha_verified', 'no' ) === 'yes';
	$captcha_active = $recaptcha_on && $captcha_ok;
	if ( $recaptcha_on && ! $captcha_active && 'cf_turnstile' === $recaptcha_type ) {
		$ts_site = (string) get_option( 'wc_af_turnstile_site_key', '' );
		if ( '' !== $ts_site ) {
			$captcha_active = true;
		}
	}

	$manual_save = true === get_option( 'wc_af_is_settings_saved_manually', false );

	$recommended_steps   = array();
	$recommended_steps[] = array(
		'id'          => 'captcha',
		'title'       => __( 'Turn on Checkout CAPTCHA', 'woocommerce-anti-fraud' ),
		'description' => __( 'Blocks scripted card testing and bots at checkout.', 'woocommerce-anti-fraud' ),
		'url'         => $section_url( 'recaptcha_settings' ),
		'done'        => $captcha_active,
		'required'    => true,
	);

	if ( $paypal_detected ) {
		$recommended_steps[] = array(
			'id'          => 'paypal_acp',
			'title'       => __( 'Align PayPal with Checkout CAPTCHA', 'woocommerce-anti-fraud' ),
			'description' => __( 'Keeps PayPal checkout consistent with your CAPTCHA.', 'woocommerce-anti-fraud' ),
			'url'         => $section_url( 'paypal_settings' ),
			'done'        => $paypal_acp,
			'required'    => true,
		);
	}

	$recommended_steps[] = array(
		'id'          => 'save_baseline',
		'title'       => __( 'Save your protection baseline', 'woocommerce-anti-fraud' ),
		'description' => __( 'Save Essential and Card testing once so limits and thresholds stick.', 'woocommerce-anti-fraud' ),
		'url'         => $section_url( 'general' ),
		'done'        => $manual_save,
		'required'    => true,
		'secondary_url' => $section_url( 'card_attacks' ),
		'secondary_label' => __( 'Card testing', 'woocommerce-anti-fraud' ),
	);

	$done_rs  = 0;
	$total_rs = 0;
	foreach ( $recommended_steps as $rs_step ) {
		if ( ! empty( $rs_step['required'] ) ) {
			++$total_rs;
			if ( ! empty( $rs_step['done'] ) ) {
				++$done_rs;
			}
		}
	}
	$percent_rs    = $total_rs > 0 ? (int) round( ( $done_rs / $total_rs ) * 100 ) : 0;
	$complete_rs   = $total_rs > 0 && $done_rs >= $total_rs;
	$recommended_next = null;
	foreach ( $recommended_steps as $rs_step ) {
		if ( ! empty( $rs_step['required'] ) && empty( $rs_step['done'] ) ) {
			$recommended_next = array(
				'title'       => isset( $rs_step['title'] ) ? $rs_step['title'] : '',
				'description' => isset( $rs_step['description'] ) ? $rs_step['description'] : '',
				'url'         => isset( $rs_step['url'] ) ? $rs_step['url'] : $section_url( '' ),
			);
			break;
		}
	}

	$failed_counts                 = get_transient( 'wc_af_preload_failed_counts' );
	$failed_24h_for_activity       = null;
	if ( is_array( $failed_counts ) && isset( $failed_counts['24_hour'] ) ) {
		$failed_24h_for_activity = (int) $failed_counts['24_hour'];
	}
	$likely_card_attack_activity = ( null !== $failed_24h_for_activity && $failed_24h_for_activity >= 15 );

	// Reuse cached dashboard stats if available to avoid heavy recomputation on settings pages.
	$high_risk_24          = null;
	$high_risk_hold_24     = null;
	$high_risk_cancel_24   = null;
	$blocked_emails_24     = null;
	$paypal_pending_24     = null;
	$date_range_pref       = function_exists( 'wc_af_get_date_range_preference' ) ? wc_af_get_date_range_preference( 'get' ) : 'last_30_days';
	$stats_transient_key   = 'wc_af_dashboard_stats_v3_' . md5( $date_range_pref );
	$cached_dashboard      = get_transient( $stats_transient_key );
	if ( is_array( $cached_dashboard ) ) {
		$high_risk_24        = isset( $cached_dashboard['high_risk_24'] ) ? (int) $cached_dashboard['high_risk_24'] : null;
		$high_risk_hold_24   = isset( $cached_dashboard['high_risk_hold_24'] ) ? (int) $cached_dashboard['high_risk_hold_24'] : null;
		$high_risk_cancel_24 = isset( $cached_dashboard['high_risk_cancelled_24'] ) ? (int) $cached_dashboard['high_risk_cancelled_24'] : null;
		$paypal_pending_24   = isset( $cached_dashboard['paypal_verification_24'] ) ? (int) $cached_dashboard['paypal_verification_24'] : null;
		if ( isset( $cached_dashboard['blocked_emails'] ) && is_array( $cached_dashboard['blocked_emails'] ) ) {
			$blocked_emails_24 = count( $cached_dashboard['blocked_emails'] );
		}
	}

	$limits_ok         = get_option( 'wc_af_attempt_count_check', 'yes' ) === 'yes' && get_option( 'wc_af_order_payment_attempt_check', 'yes' ) === 'yes';
	$fraud_before_ok   = get_option( 'wc_af_fraud_check_before_payment', 'no' ) === 'yes';

	$captcha_value   = __( 'Protecting checkout', 'woocommerce-anti-fraud' );
	$captcha_context = __( 'Checkout asks for a human check before orders complete.', 'woocommerce-anti-fraud' );
	$captcha_level   = 'healthy';
	$captcha_cta     = __( 'Review Checkout CAPTCHA', 'woocommerce-anti-fraud' );
	if ( ! $captcha_active ) {
		$captcha_level   = 'critical';
		$captcha_value   = $recaptcha_on
			? __( 'Setup not finished', 'woocommerce-anti-fraud' )
			: __( 'Not enabled', 'woocommerce-anti-fraud' );
		$captcha_context = __( 'If you do nothing, automated bots and card testers can reach your checkout.', 'woocommerce-anti-fraud' );
		$captcha_cta     = __( 'Set up Checkout CAPTCHA', 'woocommerce-anti-fraud' );
	}

	$limits_value   = __( 'Good — both limits on', 'woocommerce-anti-fraud' );
	$limits_context = __( 'Repeat order and payment attempts are capped to slow abuse.', 'woocommerce-anti-fraud' );
	$limits_level   = 'healthy';
	$limits_cta     = __( 'Review card testing protection', 'woocommerce-anti-fraud' );
	if ( ! $limits_ok ) {
		$limits_level   = 'warning';
		$limits_value   = __( 'Needs attention — one limit off', 'woocommerce-anti-fraud' );
		$limits_context = __( 'Turn both on with CAPTCHA or scripted testing can continue.', 'woocommerce-anti-fraud' );
		$limits_cta     = __( 'Turn on both limits', 'woocommerce-anti-fraud' );
	}

	$fraud_value   = __( 'On — screening before pay', 'woocommerce-anti-fraud' );
	$fraud_context = __( 'Orders are scored before money is taken.', 'woocommerce-anti-fraud' );
	$fraud_level   = 'healthy';
	$fraud_cta     = __( 'Review Essentials', 'woocommerce-anti-fraud' );
	if ( ! $fraud_before_ok ) {
		$fraud_level   = 'warning';
		$fraud_value   = __( 'Off — screening skipped', 'woocommerce-anti-fraud' );
		$fraud_context = __( 'Recommended: risky orders can pay before you review them.', 'woocommerce-anti-fraud' );
		$fraud_cta     = __( 'Turn on pre-payment checks', 'woocommerce-anti-fraud' );
	}

	$extras_integrations = array_slice( $integrations, 1 );
	$extras_total        = count( $extras_integrations );
	$extras_on           = 0;
	foreach ( $extras_integrations as $ei ) {
		if ( isset( $ei['state'] ) && 'on' === $ei['state'] ) {
			++$extras_on;
		}
	}

	$extras_value = sprintf(
		/* translators: 1: number of optional tools connected, 2: total optional tools */
		__( '%1$d of %2$d add-ons in use', 'woocommerce-anti-fraud' ),
		(int) $extras_on,
		(int) $extras_total
	);
	if ( $minfraud_cred_issue ) {
		$extras_level   = 'warning';
		$extras_context = __( 'MaxMind Score is on but credentials need fixing — risk signals may not load.', 'woocommerce-anti-fraud' );
		$extras_cta     = __( 'Fix MaxMind settings', 'woocommerce-anti-fraud' );
	} else {
		$extras_level   = 'neutral';
		$extras_context = __( 'Optional: add MaxMind, Trust Swiftly, AI, and more when you want deeper signals.', 'woocommerce-anti-fraud' );
		$extras_cta     = __( 'Browse extra tools', 'woocommerce-anti-fraud' );
	}

	$protection_checks = array(
		array(
			'label'   => __( 'Checkout CAPTCHA', 'woocommerce-anti-fraud' ),
			'value'   => $captcha_value,
			'context' => $captcha_context,
			'url'     => $section_url( 'recaptcha_settings' ),
			'level'   => $captcha_level,
			'cta'     => $captcha_cta,
		),
		array(
			'label'   => __( 'Card testing limits', 'woocommerce-anti-fraud' ),
			'value'   => $limits_value,
			'context' => $limits_context,
			'url'     => $section_url( 'card_attacks' ),
			'level'   => $limits_level,
			'cta'     => $limits_cta,
		),
		array(
			'label'   => __( 'Suspicious order checks', 'woocommerce-anti-fraud' ),
			'value'   => $fraud_value,
			'context' => $fraud_context,
			'url'     => $section_url( 'general' ),
			'level'   => $fraud_level,
			'cta'     => $fraud_cta,
		),
		array(
			'label'   => __( 'Extra protection tools', 'woocommerce-anti-fraud' ),
			'value'   => $extras_value,
			'context' => $extras_context,
			'url'     => $section_url( 'minfraud_settings' ),
			'level'   => $extras_level,
			'cta'     => $extras_cta,
		),
	);

	$integrations_footnote = array();

	$attention_items = array();
	if ( ! $captcha_active ) {
		$attention_items[] = array(
			'title' => __( 'Checkout CAPTCHA is not fully on', 'woocommerce-anti-fraud' ),
			'text'  => __( 'Finish setup so bots and card testers cannot breeze through checkout.', 'woocommerce-anti-fraud' ),
			'url'   => $section_url( 'recaptcha_settings' ),
			'cta'   => __( 'Set up CAPTCHA', 'woocommerce-anti-fraud' ),
			'level' => 'urgent',
		);
	}
	if ( get_option( 'wc_af_attempt_count_check', 'yes' ) !== 'yes' || get_option( 'wc_af_order_payment_attempt_check', 'yes' ) !== 'yes' ) {
		$attention_items[] = array(
			'title' => __( 'Card testing limits incomplete', 'woocommerce-anti-fraud' ),
			'text'  => __( 'Turn on both order and payment attempt limits alongside CAPTCHA.', 'woocommerce-anti-fraud' ),
			'url'   => $section_url( 'card_attacks' ),
			'cta'   => __( 'Open card testing', 'woocommerce-anti-fraud' ),
			'level' => 'high',
		);
	}
	if ( get_option( 'wc_af_fraud_check_before_payment', 'no' ) !== 'yes' ) {
		$attention_items[] = array(
			'title' => __( 'Pre-payment checks are off', 'woocommerce-anti-fraud' ),
			'text'  => __( 'Risky orders may reach payment before you see them.', 'woocommerce-anti-fraud' ),
			'url'   => $section_url( 'general' ),
			'cta'   => __( 'Turn on in Essentials', 'woocommerce-anti-fraud' ),
			'level' => 'high',
		);
	}
	if ( $paypal_detected && ! $paypal_acp ) {
		$attention_items[] = array(
			'title' => __( 'PayPal needs alignment', 'woocommerce-anti-fraud' ),
			'text'  => __( 'Match PayPal with your CAPTCHA so no path stays exposed.', 'woocommerce-anti-fraud' ),
			'url'   => $section_url( 'paypal_settings' ),
			'cta'   => __( 'Review PayPal', 'woocommerce-anti-fraud' ),
			'level' => 'medium',
		);
	}

	$risk_insights = array(
		array(
			'label' => __( 'Card attack pressure (failed orders, 24h)', 'woocommerce-anti-fraud' ),
			'value' => null !== $failed_24h_for_activity ? number_format_i18n( $failed_24h_for_activity ) : __( 'Not loaded', 'woocommerce-anti-fraud' ),
			'note'  => $likely_card_attack_activity ? __( 'Needs attention: activity is elevated', 'woocommerce-anti-fraud' ) : __( 'No unusual card attack pressure detected', 'woocommerce-anti-fraud' ),
		),
		array(
			'label' => __( 'High-risk orders (24h)', 'woocommerce-anti-fraud' ),
			'value' => null !== $high_risk_24 ? number_format_i18n( $high_risk_24 ) : __( 'Not available yet', 'woocommerce-anti-fraud' ),
			'note'  => __( 'Recent orders flagged as high risk', 'woocommerce-anti-fraud' ),
		),
		array(
			'label' => __( 'High-risk orders cancelled (24h)', 'woocommerce-anti-fraud' ),
			'value' => null !== $high_risk_cancel_24 ? number_format_i18n( $high_risk_cancel_24 ) : __( 'Not available yet', 'woocommerce-anti-fraud' ),
			'note'  => __( 'Shows how often risky orders were blocked', 'woocommerce-anti-fraud' ),
		),
	);

	$operational_impact = array(
		array(
			'label' => __( 'Failed-payment email noise', 'woocommerce-anti-fraud' ),
			'value' => get_option( 'wc_af_stop_send_mail_failed_status', 'no' ) === 'yes' ? __( 'Suppressed', 'woocommerce-anti-fraud' ) : __( 'Sending', 'woocommerce-anti-fraud' ),
			'note'  => __( 'Useful if your inbox gets flooded during attacks', 'woocommerce-anti-fraud' ),
		),
		array(
			'label' => __( 'Blocked emails in recent activity', 'woocommerce-anti-fraud' ),
			'value' => null !== $blocked_emails_24 ? number_format_i18n( $blocked_emails_24 ) : __( 'Not available yet', 'woocommerce-anti-fraud' ),
			'note'  => __( 'Helps spot repeated abuse patterns', 'woocommerce-anti-fraud' ),
		),
		array(
			'label' => __( 'PayPal verifications pending (24h)', 'woocommerce-anti-fraud' ),
			'value' => null !== $paypal_pending_24 ? number_format_i18n( $paypal_pending_24 ) : __( 'Not available yet', 'woocommerce-anti-fraud' ),
			'note'  => __( 'Higher values can indicate friction or attack traffic', 'woocommerce-anti-fraud' ),
		),
	);

	$optional_note = __( 'Optional integrations (MaxMind, Trust Swiftly, AI signals, and more) are available from the tabs above when you are ready.', 'woocommerce-anti-fraud' );
	$optional_links = array(
		array(
			'label' => __( 'Fraud rules', 'woocommerce-anti-fraud' ),
			'url'   => $section_url( 'rules' ),
		),
		array(
			'label' => __( 'Integrations', 'woocommerce-anti-fraud' ),
			'url'   => $section_url( 'minfraud_settings' ),
		),
		array(
			'label' => __( 'Email alerts', 'woocommerce-anti-fraud' ),
			'url'   => $section_url( 'email_alert' ),
		),
	);

	return array(
		'protection_level'              => $level,
		'protection_label'              => $label,
		'protection_summary'            => $summary,
		'integrations'                  => $integrations,
		'recommended_next'              => $recommended_next,
		'likely_card_attack_activity'   => $likely_card_attack_activity,
		'urls'                          => array(
			'home'               => $section_url( '' ),
			'general'            => $section_url( 'general' ),
			'rules'              => $section_url( 'rules' ),
			'card_attacks'       => $section_url( 'card_attacks' ),
			'recaptcha'          => $section_url( 'recaptcha_settings' ),
			'paypal'             => $section_url( 'paypal_settings' ),
			'marketplace'        => $section_url( 'marketplace_orders' ),
			'whitelist'          => $section_url( 'white_list' ),
			'blacklist'          => $section_url( 'black_list' ),
			'email_alerts'       => $section_url( 'email_alert' ),
			'cleanup'            => $section_url( 'cleanup' ),
			'dashboard'          => admin_url( 'admin.php?page=antifraud-dashboard' ),
			'need_support'       => $section_url( 'need_support' ),
		),
		'recaptcha_type'     => $recaptcha_type_label,
		'dashboard_enabled'  => $dashboard_on,
		'docs_url'           => $docs_url,
		'support_url'        => $support_url,
		'reviews_url'        => $reviews_url,
		'recommended_setup'  => array(
			'steps'         => $recommended_steps,
			'done_count'    => $done_rs,
			'total_count'   => $total_rs,
			'percent'       => $percent_rs,
			'is_complete'   => $complete_rs,
			'optional_note' => $optional_note,
			'optional_links'=> $optional_links,
		),
		'protection_checks'      => $protection_checks,
		'integrations_footnote'  => $integrations_footnote,
		'attention_items'        => $attention_items,
		'risk_insights'       => $risk_insights,
		'operational_impact'  => $operational_impact,
	);
}
