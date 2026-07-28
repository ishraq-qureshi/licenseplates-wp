jQuery(document).ready(function ($) {
	if (typeof wcAFSettings === 'undefined') {
		return;
	}

	function applyPaypalHelpMessage($checkbox) {
		if (!$checkbox.length) {
			return;
		}

		$checkbox.prop('disabled', true);
		$checkbox.siblings('.wc-af-help-msg').remove();

		var $message = $('<span class="wc-af-help-msg"></span>');
		$checkbox.after($message);

		var detected = wcAFSettings.paypalpluginDetected === 'yes';
		var recaptchaOn = wcAFSettings.recaptchaEnable === 'yes';
		var recaptchaType = wcAFSettings.recaptchaType || 'google_recaptcha';

		if (detected && !recaptchaOn) {
			$message
				.text('It looks like you\u2019re using PayPal. When Captcha is enabled, it will be automatically applied as well.')
				.css('color', 'red');
		} else if (detected && recaptchaOn && recaptchaType === 'google_recaptcha') {
			$message
				.text('It looks like you\u2019re using PayPal. This setting helps prevent PayPal card attacks during checkout.')
				.css('color', 'green');
		} else if (detected && recaptchaOn && recaptchaType === 'cf_turnstile') {
			$message
				.text('It looks like you\u2019re using PayPal, and Captcha is enabled. However, the selected Captcha type is not Google reCAPTCHA, so it will not help prevent PayPal card attacks.')
				.css('color', 'red');
		} else if (!detected) {
			$message
				.text('It looks like you\u2019re not using PayPal, so this will not help prevent PayPal card attacks.')
				.css('color', 'red');
		}
	}

	applyPaypalHelpMessage($('#wc_af_paypal_acp_enabled'));
	applyPaypalHelpMessage($('#wc_af_paypal_acp_enabled_recaptcha'));
	applyPaypalHelpMessage($('#wc_af_paypal_acp_enabled_card_attack'));

	var $paypalCheckbox = $('#wc_af_paypal_acp_enabled');
	if ($paypalCheckbox.length && !$paypalCheckbox.parent().hasClass('wc-af-checkbox-wrapper')) {
		$paypalCheckbox.wrap('<span class="wc-af-checkbox-wrapper" style="position:relative; display:inline-block;"></span>');
		var $wrapper = $paypalCheckbox.parent();
		var $msg = $('<span class="wc-af-hover-msg" style="display:none; position:absolute; left:0; top:25px; background:#fff; border:1px solid #ccc; padding:5px; color:red; z-index:999; width:250px;">PayPal Card Attack Protection is automatically enabled whenever reCAPTCHA is activated.</span>');
		$wrapper.append($msg);
		$wrapper.hover(
			function () {
				if (!$paypalCheckbox.is(':checked')) {
					$msg.show();
				}
			},
			function () {
				$msg.hide();
			}
		);
	}

	// Advanced options disclosure panels.
	$(document).on('click', '.wc-af-advanced-toggle', function (e) {
		e.preventDefault();

		var $btn = $(this);
		var panelId = $btn.attr('aria-controls');
		var $panel = panelId ? $('#' + panelId) : $btn.closest('.wc-af-advanced-wrap').find('.wc-af-advanced-panel').first();
		var $wrap = $btn.closest('.wc-af-advanced-wrap');
		var isOpen = $btn.attr('aria-expanded') === 'true';

		if (isOpen) {
			$panel.prop('hidden', true);
			$btn.attr('aria-expanded', 'false');
			$wrap.removeClass('wc-af-advanced-wrap--open');
			$btn.text($btn.attr('data-wc-af-label-show') || 'Advanced options');
		} else {
			$panel.prop('hidden', false);
			$btn.attr('aria-expanded', 'true');
			$wrap.addClass('wc-af-advanced-wrap--open');
			$btn.text($btn.attr('data-wc-af-label-hide') || 'Hide advanced options');
		}
	});

	if (window.location.hash === '#wc-af-card-attacks-tuning') {
		var $panel = $('#wc-af-adv-card-attacks');
		if ($panel.length && $panel.prop('hidden')) {
			$panel.closest('.wc-af-advanced-wrap').find('.wc-af-advanced-toggle').first().trigger('click');
		}
		var anchor = document.getElementById('wc-af-card-attacks-tuning');
		if (anchor) {
			setTimeout(function () {
				anchor.scrollIntoView({ behavior: 'smooth', block: 'start' });
			}, 150);
		}
	}

	// Dashboard enable/disable auto-refresh after save.
	var $dashboardCheckbox = $('#wc_af_enable_dashboard');
	if ($dashboardCheckbox.length) {
		var originalState = $dashboardCheckbox.is(':checked') ? 'yes' : 'no';
		var stateKey = 'wc_af_dashboard_original_state';
		var urlParams = new URLSearchParams(window.location.search);

		if (urlParams.get('settings-updated') === 'true') {
			var storedOriginalState = sessionStorage.getItem(stateKey);
			var currentState = $dashboardCheckbox.is(':checked') ? 'yes' : 'no';

			if (storedOriginalState && storedOriginalState !== currentState) {
				var $notice = $('<div class="notice notice-success is-dismissible"><p><strong>' +
					(currentState === 'yes'
						? 'Dashboard enabled successfully. Refreshing page to update menu...'
						: 'Dashboard disabled successfully. Refreshing page to update menu...') +
					'</strong></p></div>');
				$('.wrap').first().prepend($notice);
				sessionStorage.removeItem(stateKey);
				setTimeout(function () {
					window.location.reload();
				}, 1500);
				return;
			}

			sessionStorage.removeItem(stateKey);
		} else if (!sessionStorage.getItem(stateKey)) {
			sessionStorage.setItem(stateKey, originalState);
		}
	}

	// Sync dashboard date range from server on load.
	var $dateRangeSelect = $('#wc_af_dashboard_date_range');
	if ($dateRangeSelect.length) {
		$.ajax({
			url: wcAFSettings.ajaxurl || (typeof ajaxurl !== 'undefined' ? ajaxurl : ''),
			type: 'POST',
			data: {
				action: 'wc_af_get_date_range',
				security: wcAFSettings.nonce || ''
			},
			success: function (response) {
				if (response && response.success && response.data && response.data.date_range) {
					var currentValue = response.data.date_range;
					if ($dateRangeSelect.val() !== currentValue) {
						$dateRangeSelect.val(currentValue);
					}
				}
			}
		});
	}

	// Sticky save bar for long settings pages.
	(function initStickySave() {
		var section = wcAFSettings.settingsSection || '';
		if (section === 'need_support' || section === 'license') {
			return;
		}

		var form = document.querySelector('form#mainform') || document.querySelector('form.woocommerce-settings') || document.querySelector('.woocommerce form');
		if (!form) {
			return;
		}

		var nativeSave = form.querySelector('button[name="save"], input[name="save"][type="submit"], .woocommerce-save-button');
		if (!nativeSave) {
			return;
		}

		var editableFields = form.querySelectorAll(
			'input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])'
		);
		if (!editableFields.length || document.documentElement.scrollHeight < (window.innerHeight * 1.2)) {
			return;
		}

		$('.wc-af-sticky-save').remove();

		var stickyBar = document.createElement('div');
		stickyBar.className = 'wc-af-sticky-save';
		stickyBar.innerHTML =
			'<div class="wc-af-sticky-save__inner">' +
				'<button type="button" class="button button-primary wc-af-sticky-save__btn">Save settings</button>' +
			'</div>';

		document.body.appendChild(stickyBar);
		document.body.classList.add('wc-af-has-sticky-save');

		var stickyBtn = stickyBar.querySelector('.wc-af-sticky-save__btn');
		if (stickyBtn) {
			stickyBtn.addEventListener('click', function () {
				if (nativeSave.disabled) {
					return;
				}
				if (typeof form.requestSubmit === 'function') {
					form.requestSubmit(nativeSave);
				} else {
					nativeSave.click();
				}
			});
		}

		if ('IntersectionObserver' in window) {
			var observer = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					stickyBar.classList.toggle('is-hidden', entry.isIntersecting);
				});
			}, { root: null, threshold: 0.1 });
			observer.observe(nativeSave);
		}

		form.addEventListener('submit', function () {
			stickyBar.classList.add('is-hidden');
			document.body.classList.remove('wc-af-has-sticky-save');
		});
	})();
});
