<?php
/**
 * Anti-Fraud — WooCommerce settings "Home" control center markup.
 *
 * Variables: array $data from wc_af_get_home_control_center_data(), string $home_intro (optional HTML).
 *
 * @package WooCommerce_Anti_Fraud
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data       = isset( $data ) && is_array( $data ) ? $data : array();
$home_intro = isset( $home_intro ) ? $home_intro : '';

$parsed_urls = isset( $data['urls'] ) && is_array( $data['urls'] ) ? $data['urls'] : array();
$home_url      = isset( $parsed_urls['home'] ) ? $parsed_urls['home'] : admin_url( 'admin.php?page=wc-settings&tab=wc_af' );
$general_url   = isset( $parsed_urls['general'] ) ? $parsed_urls['general'] : $home_url;
$whitelist_url = isset( $parsed_urls['whitelist'] ) ? $parsed_urls['whitelist'] : add_query_arg( 'section', 'white_list', $home_url );
$blacklist_url = isset( $parsed_urls['blacklist'] ) ? $parsed_urls['blacklist'] : add_query_arg( 'section', 'black_list', $home_url );
$integrations_fn = isset( $data['integrations_footnote'] ) && is_array( $data['integrations_footnote'] ) ? $data['integrations_footnote'] : array();

?>
<section class="wc-af-control-center wc-af-cc-page wc-af-app-surface" aria-label="<?php echo esc_attr__( 'WooCommerce Anti-Fraud dashboard', 'woocommerce-anti-fraud' ); ?>">

	<div class="wc-af-dashboard-top" role="region" aria-label="<?php echo esc_attr__( 'Getting started', 'woocommerce-anti-fraud' ); ?>">
		<aside class="wc-af-ov2-start-card wc-af-ov2-card" aria-labelledby="wc-af-start-title">
			<h2 id="wc-af-start-title" class="wc-af-ov2-start-card__title"><?php esc_html_e( 'Start here', 'woocommerce-anti-fraud' ); ?></h2>
			<p class="wc-af-ov2-start-card__intro"><?php esc_html_e( 'Watch the walkthrough first, then adjust settings with confidence.', 'woocommerce-anti-fraud' ); ?></p>
			<div class="wc-af-start-here__grid wc-af-start-here__grid--compact wc-af-start-here__grid--start-card">
				<a class="wc-af-start-here__card wc-af-start-here__card--info" href="https://youtu.be/moc-P4kAA4I" target="_blank" rel="noopener noreferrer">
					<span class="wc-af-badge wc-af-badge--info"><?php esc_html_e( 'Video', 'woocommerce-anti-fraud' ); ?></span>
					<span class="wc-af-start-here__title"><?php esc_html_e( 'Watch walkthrough', 'woocommerce-anti-fraud' ); ?></span>
				</a>
				<a class="wc-af-start-here__card wc-af-start-here__card--neutral" href="<?php echo esc_url( isset( $data['docs_url'] ) ? $data['docs_url'] : 'https://docs.woocommerce.com/document/woocommerce-anti-fraud/' ); ?>" target="_blank" rel="noopener noreferrer">
					<span class="wc-af-badge wc-af-badge--neutral"><?php esc_html_e( 'Guide', 'woocommerce-anti-fraud' ); ?></span>
					<span class="wc-af-start-here__title"><?php esc_html_e( 'Setup guide', 'woocommerce-anti-fraud' ); ?></span>
				</a>
				<a class="wc-af-start-here__card wc-af-start-here__card--recommended" href="<?php echo esc_url( $general_url ); ?>">
					<span class="wc-af-badge wc-af-badge--recommended"><?php esc_html_e( 'Next', 'woocommerce-anti-fraud' ); ?></span>
					<span class="wc-af-start-here__title"><?php esc_html_e( 'Essential protection', 'woocommerce-anti-fraud' ); ?></span>
				</a>
				<a class="wc-af-start-here__card wc-af-start-here__card--neutral wc-af-start-here__card--with-desc" href="<?php echo esc_url( $whitelist_url ); ?>">
					<span class="wc-af-badge wc-af-badge--neutral"><?php esc_html_e( 'Trusted', 'woocommerce-anti-fraud' ); ?></span>
					<span class="wc-af-start-here__title"><?php esc_html_e( 'Trusted customers', 'woocommerce-anti-fraud' ); ?></span>
					<span class="wc-af-start-here__desc"><?php esc_html_e( 'Reduce false positives for known safe customers, IPs, roles, or payment methods.', 'woocommerce-anti-fraud' ); ?></span>
				</a>
				<a class="wc-af-start-here__card wc-af-start-here__card--warning wc-af-start-here__card--with-desc" href="<?php echo esc_url( $blacklist_url ); ?>">
					<span class="wc-af-badge wc-af-badge--warning"><?php esc_html_e( 'Blocked', 'woocommerce-anti-fraud' ); ?></span>
					<span class="wc-af-start-here__title"><?php esc_html_e( 'Blocked customers', 'woocommerce-anti-fraud' ); ?></span>
					<span class="wc-af-start-here__desc"><?php esc_html_e( 'Block or penalize known risky emails, domains, IPs, names, locations, or IP addresses.', 'woocommerce-anti-fraud' ); ?></span>
				</a>
			</div>
		</aside>
	</div>

	<?php if ( '' !== $home_intro ) : ?>
		<div class="wc-af-dashboard-home-intro wc-af-cc-muted">
			<?php echo wp_kses_post( $home_intro ); ?>
		</div>
	<?php endif; ?>

	<?php
	$protection_checks = isset( $data['protection_checks'] ) && is_array( $data['protection_checks'] ) ? $data['protection_checks'] : array();
	$attention_items   = isset( $data['attention_items'] ) && is_array( $data['attention_items'] ) ? $data['attention_items'] : array();
	?>

	<section class="wc-af-ov2-block">
		<div class="wc-af-ov2-block__head">
			<h3 class="wc-af-ov2-block__title"><?php esc_html_e( 'Protection status', 'woocommerce-anti-fraud' ); ?></h3>
			<p class="wc-af-ov2-block__lead"><?php esc_html_e( 'Scan color first, then read the one-line state. Each link opens the right settings.', 'woocommerce-anti-fraud' ); ?></p>
		</div>
		<div class="wc-af-status-key wc-af-status-key--overview" aria-labelledby="wc-af-overview-status-key-title">
			<h4 id="wc-af-overview-status-key-title" class="wc-af-status-key__title"><?php esc_html_e( 'Status key', 'woocommerce-anti-fraud' ); ?></h4>
			<ul class="wc-af-status-key__list wc-af-status-key__list--compact">
				<li class="wc-af-status-key__item">
					<span class="wc-af-status-key__visual" aria-hidden="true"><span class="wc-af-ov2-summary-card__badge wc-af-ov2-summary-card__badge--critical"><?php esc_html_e( 'Critical', 'woocommerce-anti-fraud' ); ?></span></span>
					<span class="wc-af-status-key__copy">
						<span class="wc-af-status-key__term"><?php esc_html_e( 'Critical', 'woocommerce-anti-fraud' ); ?></span>
						<span class="wc-af-status-key__desc"><?php esc_html_e( 'Needs action now because protection is missing or incomplete.', 'woocommerce-anti-fraud' ); ?></span>
					</span>
				</li>
				<li class="wc-af-status-key__item">
					<span class="wc-af-status-key__visual" aria-hidden="true"><span class="wc-af-ov2-summary-card__badge wc-af-ov2-summary-card__badge--warning"><?php esc_html_e( 'Attention', 'woocommerce-anti-fraud' ); ?></span></span>
					<span class="wc-af-status-key__copy">
						<span class="wc-af-status-key__term"><?php esc_html_e( 'Attention', 'woocommerce-anti-fraud' ); ?></span>
						<span class="wc-af-status-key__desc"><?php esc_html_e( 'Review soon because this setting could weaken protection or increase noise.', 'woocommerce-anti-fraud' ); ?></span>
					</span>
				</li>
				<li class="wc-af-status-key__item">
					<span class="wc-af-status-key__visual" aria-hidden="true"><span class="wc-af-ov2-summary-card__badge wc-af-ov2-summary-card__badge--healthy"><?php esc_html_e( 'Good', 'woocommerce-anti-fraud' ); ?></span></span>
					<span class="wc-af-status-key__copy">
						<span class="wc-af-status-key__term"><?php esc_html_e( 'Good', 'woocommerce-anti-fraud' ); ?></span>
						<span class="wc-af-status-key__desc"><?php esc_html_e( 'Working as expected for your current protection baseline.', 'woocommerce-anti-fraud' ); ?></span>
					</span>
				</li>
				<li class="wc-af-status-key__item">
					<span class="wc-af-status-key__visual" aria-hidden="true"><span class="wc-af-ov2-summary-card__badge wc-af-ov2-summary-card__badge--neutral"><?php esc_html_e( 'Optional', 'woocommerce-anti-fraud' ); ?></span></span>
					<span class="wc-af-status-key__copy">
						<span class="wc-af-status-key__term"><?php esc_html_e( 'Optional', 'woocommerce-anti-fraud' ); ?></span>
						<span class="wc-af-status-key__desc"><?php esc_html_e( 'Helpful extra protection or context, but not required to run the plugin.', 'woocommerce-anti-fraud' ); ?></span>
					</span>
				</li>
				<li class="wc-af-status-key__item">
					<span class="wc-af-status-key__visual" aria-hidden="true"><span class="wc-af-ov2-summary-card__badge wc-af-ov2-summary-card__badge--info"><?php esc_html_e( 'Info', 'woocommerce-anti-fraud' ); ?></span></span>
					<span class="wc-af-status-key__copy">
						<span class="wc-af-status-key__term"><?php esc_html_e( 'Info', 'woocommerce-anti-fraud' ); ?></span>
						<span class="wc-af-status-key__desc"><?php esc_html_e( 'Informational context to help you understand what the check is reporting.', 'woocommerce-anti-fraud' ); ?></span>
					</span>
				</li>
			</ul>
		</div>
		<div class="wc-af-ov2-summary-grid wc-af-ov2-summary-grid--insights wc-af-ov2-summary-grid--insights-4">
			<?php foreach ( $protection_checks as $check ) : ?>
				<?php
				$url   = isset( $check['url'] ) ? $check['url'] : '';
				$level = isset( $check['level'] ) ? (string) $check['level'] : 'warning';
				if ( ! in_array( $level, array( 'critical', 'warning', 'healthy', 'neutral', 'info' ), true ) ) {
					$level = 'warning';
				}
				$cta = isset( $check['cta'] ) ? (string) $check['cta'] : __( 'Open settings', 'woocommerce-anti-fraud' );
				$badge_text = array(
					'critical' => __( 'Critical', 'woocommerce-anti-fraud' ),
					'warning'  => __( 'Attention', 'woocommerce-anti-fraud' ),
					'healthy'  => __( 'Good', 'woocommerce-anti-fraud' ),
					'neutral'  => __( 'Optional', 'woocommerce-anti-fraud' ),
					'info'     => __( 'Info', 'woocommerce-anti-fraud' ),
				);
				$badge = isset( $badge_text[ $level ] ) ? $badge_text[ $level ] : $badge_text['warning'];
				?>
				<?php if ( ! empty( $url ) ) : ?>
					<a class="wc-af-ov2-summary-card wc-af-ov2-summary-card--insight wc-af-ov2-summary-card--level-<?php echo esc_attr( $level ); ?> wc-af-ov2-summary-card--clickable" href="<?php echo esc_url( $url ); ?>">
						<div class="wc-af-ov2-summary-card__headband" aria-hidden="true"></div>
						<div class="wc-af-ov2-summary-card__header">
							<div class="wc-af-ov2-summary-card__title-row">
								<p class="wc-af-ov2-summary-card__label"><?php echo esc_html( isset( $check['label'] ) ? $check['label'] : '' ); ?></p>
								<span class="wc-af-ov2-summary-card__badge wc-af-ov2-summary-card__badge--<?php echo esc_attr( $level ); ?>"><?php echo esc_html( $badge ); ?></span>
							</div>
						</div>
						<div class="wc-af-ov2-summary-card__body">
							<p class="wc-af-ov2-summary-card__value"><?php echo esc_html( isset( $check['value'] ) ? $check['value'] : '' ); ?></p>
							<p class="wc-af-ov2-summary-card__meta"><?php echo esc_html( isset( $check['context'] ) ? $check['context'] : '' ); ?></p>
							<span class="wc-af-ov2-summary-card__link"><?php echo esc_html( $cta ); ?> <span aria-hidden="true" class="wc-af-ov2-summary-card__chev">→</span></span>
						</div>
					</a>
				<?php else : ?>
					<div class="wc-af-ov2-summary-card wc-af-ov2-summary-card--insight wc-af-ov2-summary-card--level-<?php echo esc_attr( $level ); ?>">
						<div class="wc-af-ov2-summary-card__headband" aria-hidden="true"></div>
						<div class="wc-af-ov2-summary-card__header">
							<div class="wc-af-ov2-summary-card__title-row">
								<p class="wc-af-ov2-summary-card__label"><?php echo esc_html( isset( $check['label'] ) ? $check['label'] : '' ); ?></p>
								<span class="wc-af-ov2-summary-card__badge wc-af-ov2-summary-card__badge--<?php echo esc_attr( $level ); ?>"><?php echo esc_html( $badge ); ?></span>
							</div>
						</div>
						<div class="wc-af-ov2-summary-card__body">
							<p class="wc-af-ov2-summary-card__value"><?php echo esc_html( isset( $check['value'] ) ? $check['value'] : '' ); ?></p>
							<p class="wc-af-ov2-summary-card__meta"><?php echo esc_html( isset( $check['context'] ) ? $check['context'] : '' ); ?></p>
						</div>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
		<?php if ( ! empty( $integrations_fn['text'] ) && ! empty( $integrations_fn['url'] ) ) : ?>
			<p class="wc-af-ov2-integrations-footnote">
				<?php echo esc_html( $integrations_fn['text'] ); ?>
				<a href="<?php echo esc_url( $integrations_fn['url'] ); ?>"><?php echo esc_html( isset( $integrations_fn['label'] ) ? $integrations_fn['label'] : __( 'Manage integrations', 'woocommerce-anti-fraud' ) ); ?></a>
			</p>
		<?php endif; ?>
	</section>

	<section class="wc-af-ov2-block">
		<div class="wc-af-ov2-block__head">
			<h3 class="wc-af-ov2-block__title"><?php esc_html_e( 'Attention needed', 'woocommerce-anti-fraud' ); ?></h3>
			<p class="wc-af-ov2-block__lead"><?php esc_html_e( 'Fix these first when checkout noise or risk spikes.', 'woocommerce-anti-fraud' ); ?></p>
		</div>
		<div class="wc-af-ov2-attention">
			<?php if ( ! empty( $attention_items ) ) : ?>
				<?php foreach ( $attention_items as $item ) : ?>
					<div class="wc-af-ov2-attention__item wc-af-ov2-attention__item--<?php echo esc_attr( isset( $item['level'] ) ? $item['level'] : 'medium' ); ?>">
						<p class="wc-af-ov2-attention__title"><?php echo esc_html( isset( $item['title'] ) ? $item['title'] : '' ); ?></p>
						<p class="wc-af-ov2-attention__text"><?php echo esc_html( isset( $item['text'] ) ? $item['text'] : '' ); ?></p>
						<?php if ( ! empty( $item['url'] ) && ! empty( $item['cta'] ) ) : ?>
							<p class="wc-af-ov2-attention__action">
								<a class="button wc-af-btn wc-af-btn--secondary" href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['cta'] ); ?></a>
							</p>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="wc-af-ov2-attention__item wc-af-ov2-attention__item--ok">
					<p class="wc-af-ov2-attention__title"><?php esc_html_e( 'Nothing urgent right now', 'woocommerce-anti-fraud' ); ?></p>
					<p class="wc-af-ov2-attention__text"><?php esc_html_e( 'Core checks look good. Peek at Protection status when you change checkout or payments.', 'woocommerce-anti-fraud' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</section>

</section>
