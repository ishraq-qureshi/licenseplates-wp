<?php
defined( 'ABSPATH' ) || exit;

if ( isset( $_GET['page'] ) && 'cp-fdp' === $_GET['page'] ) {
	add_action(
		'admin_init',
		function() {
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'network_admin_notices' );
			remove_all_actions( 'all_admin_notices' );
			remove_all_actions( 'user_admin_notices' );
		}
	);
}
add_action( 'plugins_loaded', function() {
	add_action(
		'admin_menu',
		function() {
			// Add a hidden menu for the Code Profiler.
			add_submenu_page( 'code-profiler', esc_html__( 'Plugins', 'freesoul-deactivate-plugins' ), esc_html__( 'Plugins', 'freesoul-deactivate-plugins' ), apply_filters( 'eos_dp_settings_capabilityi', 'activate_plugins' ), 'cp-fdp', 'eos_dp_code_profiler_page', 10 );
		}
	);
} );

add_action( 'fdp_pro_loaded', function() {
	if( defined( 'FDP_PRO_ACTIVE' ) && FDP_PRO_ACTIVE ) {
		add_action( 'eos_dp_action_buttons', 'eos_dp_code_profiler_action_button', 20 );
		add_action( 'fdp_after_admin_footer_removed', 'eos_dp_code_profiler_inline_assets' );
	}
} );

function eos_dp_code_profiler_action_button() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	?>
	<a class="eos-dp-code-profiler-run fdp-has-tooltip eos-hidden" href="#" onclick="return window.eosDpCodeProfilerRun(this,event);" data-nonce="<?php echo esc_attr( wp_create_nonce( 'fdp_cp_profile_row' ) ); ?>">
		<span class="dashicons dashicons-chart-bar"></span>
		<div class="fdp-tooltip"><?php esc_html_e( 'Run Code Profiler on the URL for this row and highlight the 10 heaviest plugins.', 'freesoul-deactivate-plugins' ); ?></div>
	</a>
	<span class="fdp-cp-inline-msg eos-hidden" aria-live="polite"></span>
	<?php
}

function eos_dp_code_profiler_inline_assets() {
	if ( ! is_admin() || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	$user          = wp_get_current_user();
	$code_profiler = array(
		'ajaxurl'       => admin_url( 'admin-ajax.php' ),
		'cpNonce'       => wp_create_nonce( 'start_profiler_nonce' ),
		'currentUser'   => $user instanceof WP_User ? $user->user_login : '',
		'profileBaseUrl'=> admin_url(
			add_query_arg(
				array(
					'page'   => defined( 'CODE_PROFILER_PRO_MU_ON' ) ? 'code-profiler-pro' : 'code-profiler',
					'cptab'  => 'profiles_list',
					'action' => 'view_profile',
					'section'=> 1,
				),
				'admin.php'
			)
		),
		'strings'       => array(
			'profiling'       => esc_html__( 'Profiling...', 'freesoul-deactivate-plugins' ),
			'preparing'       => esc_html__( 'Preparing preview...', 'freesoul-deactivate-plugins' ),
			'noPreview'       => esc_html__( 'No preview URL is available for this row.', 'freesoul-deactivate-plugins' ),
			'requestFailed'   => esc_html__( 'The profiling request failed.', 'freesoul-deactivate-plugins' ),
			'resultsFailed'   => esc_html__( 'The profile was created, but FDP could not read the results.', 'freesoul-deactivate-plugins' ),
			'topPlugins'      => esc_html__( 'Top plugins:', 'freesoul-deactivate-plugins' ),
			'viewProfile'     => esc_html__( 'View profile', 'freesoul-deactivate-plugins' ),
			'noPluginsFound'  => esc_html__( 'No plugin data found in the generated profile.', 'freesoul-deactivate-plugins' ),
		),
	);
	?>
	<style id="fdp-code-profiler-inline">
	.eos-dp-actions .eos-dp-code-profiler-run{opacity:.72}
	.eos-dp-actions .eos-dp-code-profiler-run:hover{opacity:1}
	.eos-dp-actions .eos-dp-code-profiler-run.fdp-cp-loading{opacity:.45;pointer-events:none}
	.eos-dp-actions .eos-dp-code-profiler-run.fdp-cp-active{opacity:1}
	.eos-dp-actions .fdp-cp-inline-msg{display:inline-block;margin-left:6px;max-width:420px;font-size:11px;vertical-align:middle}
	.eos-dp-actions .fdp-cp-inline-msg a{margin-left:4px}
	#eos-dp-table-head .eos-dp-plugin-name.fdp-cp-highlight,
	tr .eos-dp-active.fdp-cp-highlight-row,
	tr td.fdp-cp-highlight-row{box-shadow:inset 0 0 0 2px rgba(215,160,27,.9)}
	#eos-dp-table-head .eos-dp-plugin-name.fdp-cp-highlight{border-radius:4px}
	#eos-dp-table-head .eos-dp-plugin-name.fdp-cp-highlight::after,
	tr td.fdp-cp-highlight-row::after{content:attr(data-fdp-cp-rank);position:absolute;top:2px;right:2px;min-width:18px;height:18px;padding:0 4px;border-radius:999px;background:#d79f1b;color:#111;line-height:18px;font-size:10px;font-weight:700;text-align:center;box-sizing:border-box}
	tr td.fdp-cp-highlight-row{position:relative}
	.fdp-cp-popup-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:100000}
	.fdp-cp-popup{display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);width:min(560px,calc(100vw - 32px));max-height:calc(100vh - 48px);padding:20px 20px 16px;background:#fff;border-radius:8px;box-shadow:0 18px 48px rgba(0,0,0,.3);z-index:99999999999;overflow:auto}
	.fdp-cp-popup h3{margin:0 32px 12px 0;font-size:18px;line-height:1.3}
	.fdp-cp-popup p{margin:0 0 10px}
	.fdp-cp-popup ol{margin:0 0 14px 20px}
	.fdp-cp-popup li{margin:0 0 8px}
	.fdp-cp-popup-close{position:absolute;top:10px;right:10px;border:0;background:transparent;cursor:pointer}
	.fdp-cp-popup-close .dashicons{font-size:20px;width:20px;height:20px}
	.fdp-cp-popup-meta{color:#50575e;font-size:12px}
	</style>
	<div class="fdp-cp-popup-overlay eos-hidden"></div>
	<div class="fdp-cp-popup eos-hidden" role="dialog" aria-modal="true" aria-labelledby="fdp-cp-popup-title">
		<button type="button" class="fdp-cp-popup-close" aria-label="<?php esc_attr_e( 'Close popup', 'freesoul-deactivate-plugins' ); ?>">
			<span class="dashicons dashicons-no-alt"></span>
		</button>
		<div class="fdp-cp-popup-content"></div>
	</div>
	<script>
	window.eosDpCodeProfilerRun = function(button, event) {
		if (event && typeof event.preventDefault === 'function') {
			event.preventDefault();
		}
		if (typeof window.eosDpCodeProfilerRunImpl === 'function') {
			return window.eosDpCodeProfilerRunImpl(button, event);
		}
		if (window.console && typeof window.console.error === 'function') {
			window.console.error('Code Profiler handler not initialized');
		}
		return false;
	};
	(function(factory){
		if (window.jQuery) {
			factory(window.jQuery);
			return;
		}
		document.addEventListener('DOMContentLoaded', function() {
			if (window.jQuery) {
				factory(window.jQuery);
			}
		});
	})(function($){
		'use strict';

		var fdpCpConfig = <?php echo wp_json_encode( $code_profiler ); ?>;

		function fdpCpGetPreviewLink($actions) {
			var $links = $actions.find('.eos-dp-preview').filter(function() {
				var href = this.getAttribute('href') || '';
				return !$(this).hasClass('eos-dp-psi-preview') && href.indexOf('show_files=true') === -1 && href.indexOf('js=off') === -1;
			});
			return $links.first();
		}

		function fdpCpEscapeAttr(value) {
			return String(value).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
		}

		function fdpCpGetErrorMessage(error, fallback) {
			if (typeof error === 'string' && error) {
				return error;
			}
			if (error && error.responseJSON) {
				if (error.responseJSON.data && error.responseJSON.data.message) {
					return error.responseJSON.data.message;
				}
				if (error.responseJSON.message) {
					return error.responseJSON.message;
				}
			}
			if (error && error.statusText && error.statusText !== 'error') {
				return error.statusText;
			}
			return fallback;
		}

		function fdpCpHidePopup() {
			$('.fdp-cp-popup, .fdp-cp-popup-overlay').addClass('eos-hidden').hide();
			$('.fdp-cp-popup-content').empty();
		}

		function fdpCpShowPopup(topPlugins, viewUrl) {
			var html = '<h3 id="fdp-cp-popup-title">' + fdpCpConfig.strings.topPlugins + '</h3>';
			if (topPlugins && topPlugins.length) {
				html += '<ol>';
				$.each(topPlugins, function(_, plugin) {
					html += '<li><strong>' + plugin.name + '</strong><div class="fdp-cp-popup-meta">' + plugin.time + ' s - ' + plugin.percent + '%</div></li>';
				});
				html += '</ol>';
			} else {
				html += '<p>' + fdpCpConfig.strings.noPluginsFound + '</p>';
			}
			if (viewUrl) {
				html += '<p><a class="button button-primary" href="' + viewUrl + '" target="_blank" rel="noopener">' + fdpCpConfig.strings.viewProfile + '</a></p>';
			}
			$('.fdp-cp-popup-content').html(html);
			$('.fdp-cp-popup-overlay, .fdp-cp-popup').removeClass('eos-hidden').show();
		}

		function fdpCpResetResults() {
			$('.fdp-cp-highlight').removeClass('fdp-cp-highlight').removeAttr('data-fdp-cp-rank').removeAttr('title');
			$('.fdp-cp-highlight-row').removeClass('fdp-cp-highlight-row').removeAttr('data-fdp-cp-rank').removeAttr('title');
			$('.fdp-cp-inline-msg').addClass('eos-hidden').empty();
			$('.eos-dp-code-profiler-run').removeClass('fdp-cp-active');
			fdpCpHidePopup();
		}

		function fdpCpSetMessage($actions, message, viewUrl) {
			var $msg = $actions.find('.fdp-cp-inline-msg').first();
			if (!$msg.length) {
				return;
			}
			$msg.text(message);
			if (viewUrl) {
				$msg.append(' ');
				$('<a/>', {
					href: viewUrl,
					target: '_blank',
					rel: 'noopener',
					text: fdpCpConfig.strings.viewProfile
				}).appendTo($msg);
			}
			$msg.removeClass('eos-hidden');
		}

		function fdpCpBuildProfileName($row) {
			var label = $.trim($row.find('.eos-dp-title').first().text()) || 'row';
			label = label.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').substring(0, 60);
			return 'fdp-' + (label || 'row') + '-' + Date.now();
		}

		function fdpCpPluginsRow($row) {
			var disabled = '',
				ignored = {
					'code-profiler/index.php': true,
					'code-profiler-pro/index.php': true
				};

			($row instanceof jQuery ? $row : $($row)).find('.eos-dp-td-chk-wrp input[type=checkbox]').each(function() {
				var $chk = $(this),
					$td = $chk.closest('td'),
					path = $td.attr('data-path');

				if ($td.hasClass('eos-dp-active') || $chk.hasClass('eos-dp-global-chk-row')) {
					disabled += ';pn:';
				} else if (typeof path !== 'undefined') {
					disabled += ignored[path] ? ';pn:' : ';pn:' + path;
				}
			});

			return disabled;
		}

		function fdpCpBuildProfileUrl(href, microtime, theme, isAdmin, $row) {
			var url;
			try {
				url = new URL(href, window.location.origin);
			} catch (error) {
				return href;
			}

			url.searchParams.set('test_id', microtime);

			if (isAdmin) {
				url.searchParams.set('backend_usage', 'true');
				url.searchParams.set('theme', $row.find('.eos-dp-row-theme').closest('td').hasClass('eos-dp-active'));
			} else if (theme && theme !== 'dummy_html') {
				url.searchParams.set('theme', theme);
			} else {
				url.searchParams.delete('theme');
			}

			return url.toString();
		}

		function fdpCpPreparePreview($actions) {
			var $link = fdpCpGetPreviewLink($actions),
				$row = $actions.closest('tr'),
				isAdmin = $row.hasClass('eos-dp-admin-row'),
				microtime = Date.now(),
				theme = typeof is_single_post !== 'undefined' && is_single_post ? $('.eos-dp-themes-list').val() : $actions.find('.eos-dp-themes-list').val(),
				payload = {
					action: 'eos_dp_preview',
					page_speed_insights: 'false',
					plugin_path: fdpCpPluginsRow($row),
					microtime: microtime
				},
				nonceSelector = '#eos_dp_arch_setts';

			if (!$link.length) {
				return $.Deferred().reject(fdpCpConfig.strings.noPreview).promise();
			}

			if (isAdmin) {
				nonceSelector = '#eos_dp_admin_setts';
				payload.admin_page = $row.find('.eos-dp-title').attr('href');
			} else if ($actions.attr('data-post-id')) {
				nonceSelector = '#eos_dp_setts';
				payload.post_id = $actions.attr('data-post-id');
			} else if (typeof eos_dp_js !== 'undefined' && eos_dp_js.page === 'eos_dp_by_archive' && $row.attr('data-post-type')) {
				payload.post_type = $row.attr('data-post-type');
			} else if (typeof eos_dp_js !== 'undefined' && eos_dp_js.page === 'eos_dp_by_term_archive') {
				payload.term_type = $row.attr('data-post-type');
				payload.tax = $row.attr('data-tax');
				payload.href = $row.attr('data-href');
			} else if ($row.attr('data-post-type')) {
				payload.post_type = $row.attr('data-post-type');
			}

			payload.nonce = $(nonceSelector).val();

			return $.ajax({
				type: 'POST',
				url: fdpCpConfig.ajaxurl,
				data: payload
			}).then(function(response) {
				var href;
				if (parseInt(response, 10) !== 1) {
					return $.Deferred().reject(fdpCpConfig.strings.requestFailed).promise();
				}
				href = $link.attr('href');
				href = fdpCpBuildProfileUrl(href, microtime, theme, isAdmin, $row);

				return {
					url: href,
					row: $row,
					isAdmin: isAdmin,
					profile: fdpCpBuildProfileName($row)
				};
			});
		}

		function fdpCpStartProfile(context) {
			var data = {
				action: 'codeprofiler_start_profiler',
				cp_nonce: fdpCpConfig.cpNonce,
				x_end: context.isAdmin ? 'backend' : 'custom',
				post: context.url,
				x_auth: context.isAdmin ? 'authenticated' : 'unauthenticated',
				user_agent: 'Firefox',
				profile: context.profile
			};
			if (context.isAdmin && fdpCpConfig.currentUser) {
				data.username = fdpCpConfig.currentUser;
			}
			return $.ajax({
				type: 'POST',
				url: fdpCpConfig.ajaxurl,
				dataType: 'json',
				data: data
			}).then(function(response) {
				if (!response || response.status !== 'success' || !response.microtime) {
					return $.Deferred().reject(response && response.message ? response.message : fdpCpConfig.strings.requestFailed).promise();
				}
				context.microtime = response.microtime;
				return context;
			});
		}

		function fdpCpPrepareProfile(context) {
			return $.ajax({
				type: 'POST',
				url: fdpCpConfig.ajaxurl,
				dataType: 'json',
				data: {
					action: 'codeprofiler_prepare_report',
					cp_nonce: fdpCpConfig.cpNonce,
					microtime: context.microtime,
					profile: context.profile
				}
			}).then(function(response) {
				if (!response || response.status !== 'success' || !response.cp_profile) {
					return $.Deferred().reject(response && response.message ? response.message : fdpCpConfig.strings.requestFailed).promise();
				}
				context.profileId = response.cp_profile;
				return context;
			});
		}

		function fdpCpFetchResults(context, nonce) {
			return $.ajax({
				type: 'POST',
				url: fdpCpConfig.ajaxurl,
				dataType: 'json',
				data: {
					action: 'eos_dp_code_profiler_results',
					nonce: nonce,
					profile_id: context.profileId
				}
			}).then(function(response) {
				if (!response || !response.success || !response.data) {
					return $.Deferred().reject(response && response.data && response.data.message ? response.data.message : fdpCpConfig.strings.resultsFailed).promise();
				}
				context.results = response.data;
				return context;
			});
		}

		function fdpCpApplyHighlights(context, $button) {
			var topPlugins = context.results.top_plugins || [],
				viewUrl = context.results.view_url || '';

			fdpCpResetResults();
			$button.removeClass('fdp-cp-loading').addClass('fdp-cp-active');

			if (!topPlugins.length) {
				fdpCpShowPopup([], viewUrl);
				return;
			}

			$.each(topPlugins, function(_, plugin) {
				var selector = '[data-path="' + fdpCpEscapeAttr(plugin.path) + '"]',
					title = plugin.name + ' - ' + plugin.time + ' s (' + plugin.percent + '%)';
				$('#eos-dp-table-head .eos-dp-plugin-name' + selector)
					.addClass('fdp-cp-highlight')
					.attr('data-fdp-cp-rank', plugin.rank)
					.attr('title', title);
				context.row.find('td' + selector)
					.addClass('fdp-cp-highlight-row')
					.attr('data-fdp-cp-rank', plugin.rank)
					.attr('title', title);
			});

			$button.closest('.eos-dp-actions').find('.fdp-cp-inline-msg').addClass('eos-hidden').empty();
			fdpCpShowPopup(topPlugins, viewUrl);
		}

		window.eosDpCodeProfilerRunImpl = function(button, event) {
			var $button = $(button),
				$actions = $button.closest('.eos-dp-actions'),
				nonce = $button.attr('data-nonce');
			if (event && typeof event.preventDefault === 'function') {
				event.preventDefault();
			}
			if ($button.hasClass('fdp-cp-loading')) {
				return false;
			}
			fdpCpResetResults();
			$button.addClass('fdp-cp-loading eos-dp-progress');
			fdpCpSetMessage($actions, fdpCpConfig.strings.preparing);

			fdpCpPreparePreview($actions)
				.then(fdpCpStartProfile)
				.then(fdpCpPrepareProfile)
				.then(function(context) {
					fdpCpSetMessage($actions, fdpCpConfig.strings.profiling);
					return fdpCpFetchResults(context, nonce);
				})
				.then(function(context) {
					fdpCpApplyHighlights(context, $button);
				})
				.fail(function(message) {
					$button.removeClass('fdp-cp-loading eos-dp-progress');
					fdpCpSetMessage($actions, fdpCpGetErrorMessage(message, fdpCpConfig.strings.requestFailed));
				})
				.always(function() {
					$button.removeClass('fdp-cp-loading eos-dp-progress');
				});

			return false;
		};

		$(function() {
			$(document).on('click', '.fdp-cp-popup-close, .fdp-cp-popup-overlay', function() {
				fdpCpHidePopup();
			});

			$('.eos-dp-actions').each(function() {
				var $actions = $(this),
					$button = $actions.find('.eos-dp-code-profiler-run');
				if ($button.length && fdpCpGetPreviewLink($actions).length) {
					$button.removeClass('eos-hidden');
				}
			});
		});
	});
	</script>
	<?php
}

function eos_dp_code_profiler_page() {
	$active_plugins = eos_dp_active_plugins();
	$plugins        = eos_dp_get_plugins();
	$opts           = eos_dp_get_option( 'fdp_code_profiler' );
	$cp             = isset( $opts['plugins'] ) ? $opts['plugins'] : array();
	wp_nonce_field( 'fdp-cp-nonce', 'fdp-cp-nonce' );
	?>
  <style id="fdp-cp">
	#wpbody-content>div{display:none !important;opacity:0 !important;height:0 !important;position:fixed !important;top:-99999px !important}
	.fdp-cp-plugins{opacity:<?php echo ! isset( $opts['fdp_cp'] ) || 'fdp' === $opts['fdp_cp'] ? '0.7' : ''; ?>}
	.fdp-cp-plugins>div:first-child p{margin-top:0}
	#fdp-code-profiler-save{background-repeat:no-repeat;background-size:32px 32px;background-position:-99999px -9999px;background-image:url(<?php echo esc_url( EOS_DP_PLUGIN_URL . '/admin/assets/img/ajax-loader.gif' ); ?>);}
	@media screen and (min-width:900px){
	  .fdp-cp-plugins{
		column-count:<?php echo esc_attr( max( 1, min( 3, absint( count( $active_plugins ) / 6 ) ) ) ); ?>
	  }
	}
  </style>
  <h1><?php esc_html_e( 'Code Profiler - Plugins', 'freesoul-deactivate-plugins' ); ?></h1>
  <h2><?php esc_html_e( 'Plugins active during the code profiling', 'freesoul-deactivate-plugins' ); ?></h2>
  <p><input id="fdp-radio-fdp" type="radio" name="fdp_cp_setts" value="fdp" <?php echo ! isset( $opts['fdp_cp'] ) || 'fdp' === $opts['fdp_cp'] ? 'checked' : ''; ?>/><span><?php esc_html_e( 'According to the FDP settings', 'freesoul-deactivate-plugins' ); ?></span></p>
  <p><input id="fdp-radio-cp" type="radio" name="fdp_cp_setts" value="cp" <?php echo isset( $opts['fdp_cp'] ) && 'cp' === $opts['fdp_cp'] ? 'checked' : ''; ?>/><span><?php esc_html_e( "According to this page's settings", 'freesoul-deactivate-plugins' ); ?></span></p>
	<section class="fdp-cp-plugins" style="margin-top:32px">
		<?php
		$n = 0;
		foreach ( $active_plugins as $p ) {
			if ( isset( $plugins[ $p ] ) ) {
				$plugin_name = strtoupper( str_replace( '-', ' ', dirname( $p ) ) );
				?>
				<div>
					<div class="eos-dp-cp-chk-col">
						<p class="fdp-cp-chk-wrp">
							<input id="fdp-cp-<?php echo esc_attr( $n + 1 ); ?>" class="eos-dp-cp" title="<?php 
							// translators: %s is the plugin name.
							printf( esc_attr__( 'Activate/deactivate %s during code profiling', 'freesoul-deactivate-plugins' ), esc_attr( $plugin_name ) ); ?>" data-path="<?php echo esc_attr( $p ); ?>" type="checkbox"<?php echo $cp && in_array( $p, $cp ) ? '' : ' checked'; ?> />
			  <span><?php echo esc_html( $plugin_name ); ?></span>
			</p>
					</div>
				</div>
				<?php
				++$n;
			}
		}
		?>
	</section>
  <p style="margin-top:32px">
	  <input type="button" class="button-primary" id="fdp-code-profiler-save" onclick="fdp_code_profiler_save_plugins(this);" value="<?php esc_html_e( 'Save settings', 'freesoul-deactivate-plugins' ); ?>" title="<?php esc_html_e( 'Save settings', 'freesoul-deactivate-plugins' ); ?>">
	<a type="button" class="button" title="<?php esc_html_e( 'Code Profiler', 'freesoul-deactivate-plugins' ); ?>" href="
															 <?php
																if ( defined( 'CODE_PROFILER_PRO_MU_ON' ) ) {
																	echo esc_url( admin_url( '?page=code-profiler-pro&cptab=profiler' ) );
																} else {
																	echo esc_url( admin_url( '?page=code-profiler&cptab=profiler' ) );
																}
																?>
	"><?php esc_html_e( 'Code Profiler', 'freesoul-deactivate-plugins' ); ?></a>
  </p>
  <p id="fdp-msg-succ" class="eos-dp-opts-msg eos-dp-opts-msg_success msg_response" style="display:none;padding:10px;margin:10px;border-left:4px solid #00a32a;background:#fff">
		<span><?php esc_html_e( 'Options saved', 'freesoul-deactivate-plugins' ); ?></span>
	</p>
  <p id="fdp-msg-fail" class="eos-dp-opts-msg_failed eos-dp-opts-msg eos-hidden msg_response" style="display:none;padding:10px;margin:10px;border-left:4px solid #d63638;background:#fff">
		<span><?php esc_html_e( 'Something went wrong, maybe you need to refresh the page and try again, but you will lose all your changes', 'freesoul-deactivate-plugins' ); ?></span>
	</p>
  <script>
  document.getElementById('fdp-radio-cp').addEventListener('click',function(){
	document.getElementsByClassName('fdp-cp-plugins')[0].style.opacity = '1';
  });
  document.getElementById('fdp-radio-fdp').addEventListener('click',function(){
	document.getElementsByClassName('fdp-cp-plugins')[0].style.opacity = '0.7';
  });
  function fdp_code_profiler_save_plugins(btn){
	var deactivated_plugins = [],xmlHttp = new XMLHttpRequest(),f = new FormData(),v = document.getElementsByClassName('eos-dp-cp'),msg_succ = document.getElementById('fdp-msg-succ'),msg_fail = document.getElementById('fdp-msg-fail');
	btn.style.backgroundPosition = 'center center';
	msg_succ.style.display = 'none';
	msg_fail.style.display = 'none';
	for(var i=0;i<v.length;i++){
	  if(!v[i].checked){
		deactivated_plugins[i] = v[i].dataset.path;
	  }
	}
	f.append('plugins',deactivated_plugins);
	f.append('nonce', document.getElementById('fdp-cp-nonce').value);
	f.append('fdp_cp',document.querySelector('input[name=fdp_cp_setts]:checked').value);
	xmlHttp.onreadystatechange = function(){
	  btn.style.backgroundPosition = '-9999px -9999px';
	  if(4 === xmlHttp.readyState && 200 === xmlHttp.status){
		if('1' === xmlHttp.responseText.trim()){
		  msg_succ.style.display = 'block';
		  return false;
		}
		else{
		  msg_fail.style.display = 'block';
		  return false;
		}
	  }
	}
	xmlHttp.open("POST","<?php echo esc_url( admin_url( 'admin-ajax.php?action=eos_dp_code_profiler_save', true ) ); ?>");
	xmlHttp.send(f);
	return false;
  }
  </script>
	<?php
}
