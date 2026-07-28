/**
 * Classic checkout: block express payment buttons until reCAPTCHA is solved.
 * Uses transparent overlays so Stripe Link / PayPal Smart Buttons cannot be clicked.
 */
(function ($) {
	'use strict';

	var EXPRESS_CONTAINER_SELECTORS = [
		'#wc-stripe-express-checkout-element',
		'#wcpay-express-checkout-element',
		'.wcpay-express-checkout',
		'.ppc-button-wrapper',
		'[id^="ppc-button-"]',
		'#paypal-button-container',
	];

	// Card field areas — never block these.
	var CARD_FIELD_EXCLUDE_SELECTORS = [
		'#stripe-payment-data',
		'#wc-stripe-upe-form',
		'.wc-stripe-upe-element',
		'#ppcp-hosted-fields',
		'.PaymentElement',
		'[class*="PrivateStripeElement"]',
	];

	var CAPTCHA_FIELD_SELECTOR = '#af_checkout_captcha_response';
	var solved        = false;
	var observer      = null;
	var retryTimer    = null;
	var observerTimer = null; // debounce handle for observer callback
	var locking       = false; // re-entrancy guard
	var stylesInjected = false;

	// -----------------------------------------------------------------
	// Root element
	// -----------------------------------------------------------------
	function getRoot() {
		// The checkout form parent contains both the form and the
		// .ppc-button-wrapper that sits outside <form> as a sibling of #payment.
		var form = document.querySelector('form.woocommerce-checkout');
		return (form && form.parentNode) || document.body;
	}

	// -----------------------------------------------------------------
	// Styles
	// -----------------------------------------------------------------
	function injectStyles() {
		if (stylesInjected) {
			return;
		}
		stylesInjected = true;
		var style = document.createElement('style');
		style.id = 'wc-af-express-guard-style';
		style.textContent =
			'.wc-af-express-locked { opacity: 0.5 !important; }' +
			'.wc-af-express-block-overlay {' +
			'  position: absolute !important;' +
			'  top: 0 !important; left: 0 !important;' +
			'  right: 0 !important; bottom: 0 !important;' +
			'  z-index: 2147483647 !important;' +
			'  cursor: not-allowed !important;' +
			'  background: transparent !important;' +
			'  display: block !important;' +
			'}';
		document.head.appendChild(style);
	}

	// -----------------------------------------------------------------
	// Container discovery
	// -----------------------------------------------------------------
	function isCardFieldArea(el) {
		for (var i = 0; i < CARD_FIELD_EXCLUDE_SELECTORS.length; i++) {
			if (el.closest(CARD_FIELD_EXCLUDE_SELECTORS[i])) {
				return true;
			}
		}
		return false;
	}

	function getExpressContainers() {
		var root = getRoot();
		var containers = [];
		var seen = [];
		var selectors = EXPRESS_CONTAINER_SELECTORS.slice();

		// Dynamically add PayPal button wrapper if available.
		if (window.PayPalCommerceGateway &&
			PayPalCommerceGateway.button &&
			PayPalCommerceGateway.button.wrapper &&
			selectors.indexOf(PayPalCommerceGateway.button.wrapper) === -1) {
			selectors.push(PayPalCommerceGateway.button.wrapper);
		}

		for (var i = 0; i < selectors.length; i++) {
			var nodes = root.querySelectorAll(selectors[i]);
			for (var j = 0; j < nodes.length; j++) {
				var node = nodes[j];
				if (seen.indexOf(node) !== -1 || isCardFieldArea(node)) {
					continue;
				}
				seen.push(node);
				containers.push(node);
			}
		}

		// Remove any container that is a descendant of another container in the
		// list. This prevents double-locking nested elements (e.g. #ppc-button-ppcp-gateway
		// inside .ppc-button-wrapper), which would compound opacity to near-invisible.
		return containers.filter(function (node) {
			for (var i = 0; i < containers.length; i++) {
				if (containers[i] !== node && containers[i].contains(node)) {
					return false;
				}
			}
			return true;
		});
	}

	// -----------------------------------------------------------------
	// Lock / unlock helpers
	// -----------------------------------------------------------------
	function findOverlay(container) {
		for (var i = 0; i < container.children.length; i++) {
			if (container.children[i].classList &&
				container.children[i].classList.contains('wc-af-express-block-overlay')) {
				return container.children[i];
			}
		}
		return null;
	}

	function lockContainer(container) {
		// Ensure the container can host an absolutely-positioned overlay.
		var cs = window.getComputedStyle(container);
		if (cs.position === 'static') {
			container.style.position = 'relative';
		}

		// Create overlay only if it doesn't exist yet.
		var overlay = findOverlay(container);
		if (!overlay) {
			overlay = document.createElement('div');
			overlay.className = 'wc-af-express-block-overlay';
			overlay.setAttribute('aria-hidden', 'true');
			container.appendChild(overlay);
		}

		container.classList.add('wc-af-express-locked');

		// Also set pointer-events on any iframes inside.
		var iframes = container.querySelectorAll('iframe');
		for (var i = 0; i < iframes.length; i++) {
			iframes[i].style.pointerEvents = 'none';
		}
	}

	function unlockContainer(container) {
		container.classList.remove('wc-af-express-locked');
		container.style.opacity = '';

		var overlay = findOverlay(container);
		if (overlay && overlay.parentNode) {
			overlay.parentNode.removeChild(overlay);
		}

		var iframes = container.querySelectorAll('iframe');
		for (var i = 0; i < iframes.length; i++) {
			iframes[i].style.pointerEvents = '';
			iframes[i].style.opacity = '';
		}
	}

	function applyLock(locked) {
		// Re-entrancy guard: DOM mutations caused by adding/removing the
		// overlay or classes must not re-trigger this function through the
		// MutationObserver, or the page will freeze.
		if (locking) {
			return;
		}
		locking = true;
		try {
			var containers = getExpressContainers();
			for (var i = 0; i < containers.length; i++) {
				if (locked) {
					lockContainer(containers[i]);
				} else {
					unlockContainer(containers[i]);
				}
			}
		} finally {
			locking = false;
		}
	}

	// -----------------------------------------------------------------
	// CAPTCHA state
	// -----------------------------------------------------------------
	function syncSolvedFromField() {
		var field = document.querySelector(CAPTCHA_FIELD_SELECTOR);
		return !!(field && field.value);
	}

	// -----------------------------------------------------------------
	// Retry polling (backup for very late renders)
	// -----------------------------------------------------------------
	function stopRetries() {
		if (retryTimer) {
			clearInterval(retryTimer);
			retryTimer = null;
		}
	}

	function scheduleRetries() {
		var attempts = 0;
		stopRetries();
		retryTimer = setInterval(function () {
			if (solved) {
				stopRetries();
				return;
			}
			applyLock(true);
			if (++attempts >= 30) { // 15 seconds max; observer takes over after that
				stopRetries();
			}
		}, 500);
	}

	// -----------------------------------------------------------------
	// MutationObserver — watches only for new child nodes (no attribute
	// watching, which would cause a self-triggering loop when we add the
	// overlay or set classes).
	// -----------------------------------------------------------------
	function setupObserver() {
		var root = getRoot();
		if (!root) {
			return;
		}
		if (observer) {
			observer.disconnect();
		}
		observer = new MutationObserver(function () {
			if (solved || locking) {
				return;
			}
			// Debounce: coalesce rapid mutation bursts into a single call.
			if (observerTimer) {
				clearTimeout(observerTimer);
			}
			observerTimer = setTimeout(function () {
				observerTimer = null;
				if (!solved) {
					applyLock(true);
				}
			}, 150);
		});
		// childList only — attribute watching would react to our own overlay
		// additions and create an infinite loop.
		observer.observe(root, { childList: true, subtree: true });
	}

	// -----------------------------------------------------------------
	// Public refresh
	// -----------------------------------------------------------------
	function refresh() {
		injectStyles();
		solved = syncSolvedFromField();
		applyLock(!solved);
		if (solved) {
			stopRetries();
		} else {
			scheduleRetries();
		}
		setupObserver();
	}

	// -----------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------
	window.wcAfClassicExpressGuard = {
		setSolved: function (isSolved) {
			solved = !!isSolved;
			applyLock(!solved);
			if (solved) {
				stopRetries();
			}
		},
		refresh: refresh,
	};

	$(function () {
		refresh();
	});

	$(window).on('load.wcAfExpressGuard', function () {
		// Re-apply after all third-party scripts have run (PayPal, Stripe, etc.).
		if (!solved) {
			applyLock(true);
		}
	});

	$(document.body).on('updated_checkout.wcAfExpressGuard', refresh);

})(jQuery);
