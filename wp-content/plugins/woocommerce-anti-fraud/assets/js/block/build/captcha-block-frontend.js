(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory();
	else if(typeof define === 'function' && define.amd)
		define([], factory);
	else if(typeof exports === 'object')
		exports["MyLibrary"] = factory();
	else
		root["MyLibrary"] = factory();
})(globalThis, () => {
return /******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

module.exports = window["React"];

/***/ }),

/***/ "@woocommerce/blocks-checkout":
/*!****************************************!*\
  !*** external ["wc","blocksCheckout"] ***!
  \****************************************/
/***/ ((module) => {

module.exports = window["wc"]["blocksCheckout"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

/***/ }),

/***/ "./src/block.json":
/*!************************!*\
  !*** ./src/block.json ***!
  \************************/
/***/ ((module) => {

module.exports = /*#__PURE__*/JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":2,"name":"captcha-block/captcha-block","version":"1.0.0","title":"Captcha Block","category":"woocommerce","parent":["woocommerce/checkout-totals-block"],"attributes":{"lock":{"type":"object","default":{"remove":true,"move":true}}},"textdomain":"checkout-block-example","editorScript":"file:./build/captcha-block-frontend.js"}');

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!*************************!*\
  !*** ./src/frontend.js ***!
  \*************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _block_json__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./block.json */ "./src/block.json");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @woocommerce/blocks-checkout */ "@woocommerce/blocks-checkout");
/* harmony import */ var _woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_4__);

// Module-level cookie helper — available to all callbacks without any closure
// dependency on React render cycles or useEffect scopes.
function setCookie(name, value, daysToExpire) {
  const date = new Date();
  date.setTime(date.getTime() + daysToExpire * 24 * 60 * 60 * 1000);
  const expires = 'expires=' + date.toUTCString();
  document.cookie = name + '=' + value + ';' + expires + ';path=/';
}

// Iframe lock helpers
function lockIframe(iframe) {
  iframe.style.pointerEvents = 'none';
  iframe.style.opacity = '0.5';
}
function unlockIframe(iframe) {
  iframe.style.pointerEvents = 'auto';
  iframe.style.opacity = '1';
}

// Only express-checkout iframes are locked until reCAPTCHA is solved.
const CAPTCHA_IFRAME_SELECTOR = '#recaptcha-container, #wc-af-recaptcha-checkout, .g-recaptcha, .cf-turnstile, .wc-af-captcha-checkout-wrapper';
const EXPRESS_PAYMENT_CONTAINER_SELECTORS = [
  '.wc-block-components-express-payment',
  '.wp-block-woocommerce-checkout-express-payment-block',
  '#wc-stripe-express-checkout-element',
  '#wcpay-express-checkout-element',
  '.wcpay-express-checkout',
];
const PAYMENT_METHOD_EXCLUDE_SELECTORS = [
  '.wc-block-checkout__payment-method',
  '.wc-block-components-payment-method',
  '.wc-block-checkout__payment-methods',
  '.wc-block-components-radio-control-accordion-option',
  '.payment_box',
  '#payment',
  '.woocommerce-checkout-payment',
];
function isCaptchaIframe(iframe) {
  return !!iframe.closest(CAPTCHA_IFRAME_SELECTOR);
}
function isInsidePaymentMethod(iframe) {
  return PAYMENT_METHOD_EXCLUDE_SELECTORS.some((selector) => iframe.closest(selector));
}
function isExpressPaymentIframe(iframe) {
  if (isCaptchaIframe(iframe) || isInsidePaymentMethod(iframe)) {
    return false;
  }
  return EXPRESS_PAYMENT_CONTAINER_SELECTORS.some((selector) => iframe.closest(selector));
}
function clearStalePaymentIframeLocks(root) {
  if (!root) {
    return;
  }
  PAYMENT_METHOD_EXCLUDE_SELECTORS.forEach((selector) => {
    root.querySelectorAll(`${selector} iframe`).forEach(unlockIframe);
  });
}
function getExpressPaymentIframes(root) {
  if (!root) {
    return [];
  }
  return Array.from(root.getElementsByTagName('iframe')).filter(isExpressPaymentIframe);
}
function applyExpressPaymentIframeLock(root, locked) {
  clearStalePaymentIframeLocks(root);
  const action = locked ? lockIframe : unlockIframe;
  getExpressPaymentIframes(root).forEach(action);
}

const {
  registerCheckoutBlock
} = wc.blocksCheckout;
const Block = ({
  checkoutExtensionData
}) => {
  const [captchaCheck, setCaptchaCheck] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)('');
  const {
    setExtensionData
  } = checkoutExtensionData;

  // Ref so the MutationObserver (set up once on mount) can always read the
  // current captchaCheck value without needing to reconnect on every change.
  const captchaCheckRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useRef)('');

  // Forward token to Woo Blocks Store API
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(() => {
    setExtensionData('checkout-captcha-block', 'checkout_captcha', captchaCheck);
  }, [captchaCheck]);

  // Lock / unlock payment iframes when CAPTCHA state changes.
  // Google reCAPTCHA: only PayPal / express-checkout iframes.
  // Turnstile: preserve existing global iframe lock behaviour unchanged.
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(() => {
    captchaCheckRef.current = captchaCheck;

    const form = document.querySelector('.wc-block-checkout__form');
    if (!form) return;

    const captchaType = captcha_ajax.captcha_type;

    if (captchaType === 'cf_turnstile') {
      const iframes = Array.from(form.getElementsByTagName('iframe'));
      if (captchaCheck) {
        iframes.forEach(unlockIframe);
      } else {
        iframes.forEach(lockIframe);
      }
      return;
    }

    if (captchaType !== 'google_recaptcha') {
      return;
    }

    applyExpressPaymentIframeLock(form, !captchaCheck);
  }, [captchaCheck]);

  // Inline CSS for iframe fade transition
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(() => {
    const captchaType = captcha_ajax.captcha_type;
    let css = '';

    if (captchaType === 'google_recaptcha') {
      css = '.wc-block-checkout__form .wc-block-components-express-payment iframe,'
        + '.wc-block-checkout__form .wp-block-woocommerce-checkout-express-payment-block iframe'
        + ' { transition: opacity 0.3s ease; }';
    } else if (captchaType === 'cf_turnstile') {
      css = '.wc-block-checkout__form iframe { transition: opacity 0.3s ease; }';
    }

    if (!css) {
      return undefined;
    }

    const style = document.createElement('style');
    style.textContent = css;
    document.head.appendChild(style);
    return () => { document.head.removeChild(style); };
  }, []);

  // Mount the CAPTCHA widget — runs ONCE on mount (empty deps array).
  //
  // Root causes for Firefox (and intermittent Chrome) failures in the old code:
  //  1. useEffect([recaptchaRendered]) re-ran on every state change, running
  //     cleanup that deleted window.onCheckoutCaptchaSuccess/Expired. Between
  //     deletion and re-registration there was a gap where callbacks were
  //     undefined — Firefox's stricter task scheduler hit this gap.
  //  2. renderRecaptcha() was called immediately after appending the script tag,
  //     before the API loaded. A fixed setTimeout(500ms) for Turnstile is a
  //     timing guess that fails on slow connections.
  //  3. If grecaptcha.render() threw (element not yet in DOM), setRecaptchaRendered
  //     was never called — no retry path, widget never appeared.
  //  4. window.onRecaptchaLoad was never cleaned up; stale closures accumulated.
  //
  // Fix: model after the rcfwc plugin (reliable in all browsers):
  //   • wp.data.subscribe() fires on every WC store state change → tryRender()
  //     retries automatically until both the DOM element AND the API exist.
  //   • script.onload for Google API → no global onRecaptchaLoad needed.
  //   • Callbacks defined once, never deleted → Google widget can always call them.
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(() => {
    const captchaKey    = captcha_ajax.captcha_key;
    const captchaEnable = captcha_ajax.captcha_enable;
    const captchaType   = captcha_ajax.captcha_type;

    if (!captchaKey || captchaEnable !== 'yes') {
      return;
    }

    // Global callbacks — defined once and kept for the full page lifetime.
    // Google reCAPTCHA stores a reference to them by name string and calls them
    // after user interaction (which may be long after a React re-render cycle).
    // Deleting them in cleanup would silently break the widget.
    window.onCheckoutCaptchaSuccess = function (response) {
      setCaptchaCheck(response);
      // Cookie lets the PHP backend (Store API hook) read the token even when
      // the Woo Blocks extension_data channel is not forwarded correctly.
      setCookie('recaptcha_response', response, 1);
    };

    window.onCheckoutCaptchaExpired = function () {
      setCaptchaCheck('');
    };

    // ── Cloudflare Turnstile ──────────────────────────────────────────────
    if (captchaType === 'cf_turnstile') {
      const renderTurnstile = () => {
        const el = document.getElementById('recaptcha-container');
        if (!el || el.innerHTML.trim() !== '') return; // Already rendered.
        if (typeof turnstile === 'undefined' || typeof turnstile.render !== 'function') return;
        try {
          turnstile.render('#recaptcha-container', {
            sitekey: captcha_ajax.turnstile_key,
            callback: function (token) {
              setCookie('turnstile_response', token, 1);
              window.onCheckoutCaptchaSuccess(token);
            },
            'expired-callback': window.onCheckoutCaptchaExpired
          });
        } catch (e) {}
      };

      if (typeof turnstile !== 'undefined') {
        renderTurnstile();
      } else {
        const script = document.createElement('script');
        script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
        script.async = true;
        script.onload = renderTurnstile; // Use onload — no timing guesses.
        document.head.appendChild(script);
      }
      return;
    }

    // ── Google reCAPTCHA ──────────────────────────────────────────────────
    // tryRender() is safe to call multiple times: checks element existence and
    // existing innerHTML before doing anything.
    let unsubscribe;

    const tryRender = () => {
      const el = document.getElementById('recaptcha-container');
      if (!el) return; // Element not in DOM yet — wait for next store update.

      if (el.innerHTML && el.innerHTML.trim() !== '') {
        // Already rendered — stop polling.
        unsubscribe && unsubscribe();
        return;
      }

      if (typeof grecaptcha === 'undefined' || typeof grecaptcha.render !== 'function') {
        return; // API not ready yet — wait for next store update.
      }

      try {
        grecaptcha.render(el, {
          sitekey:            captchaKey,
          callback:           'onCheckoutCaptchaSuccess',
          'expired-callback': 'onCheckoutCaptchaExpired'
        });
        // Widget mounted — stop listening to store updates.
        unsubscribe && unsubscribe();
      } catch (e) {
        // grecaptcha.render throws if the widget is already attached.
        // Treat as already-rendered and stop polling.
        unsubscribe && unsubscribe();
      }
    };

    // Subscribe to WC store/cart state so tryRender() fires whenever the
    // checkout block updates its DOM.  Same strategy as the rcfwc plugin —
    // reliable in Chrome, Firefox, and Safari.
    if (typeof wp !== 'undefined' && wp.data && wp.data.subscribe) {
      unsubscribe = wp.data.subscribe(tryRender, 'wc/store/cart');
    }

    // Load Google API script (or call tryRender immediately if already loaded).
    if (typeof grecaptcha === 'undefined') {
      const script = document.createElement('script');
      script.src = 'https://www.google.com/recaptcha/api.js?render=explicit';
      script.async = true;
      // Use onload so tryRender fires exactly when the API is ready —
      // no global onRecaptchaLoad needed, no stale closure risks.
      script.onload = tryRender;
      document.head.appendChild(script);
    } else {
      // API already available (loaded by another plugin or from cache).
      tryRender();
    }

    // PayPal / payment iframe guard.
    // Payment iframes (e.g. PayPal Smart Button) are injected into the DOM
    // *after* the React component mounts — long after the captchaCheck
    // useEffect has already run and found no iframes.  We watch for newly
    // added iframes and immediately lock them when CAPTCHA is not yet solved.
    // captchaCheckRef always holds the current value without stale closures.
    const form = document.querySelector('.wc-block-checkout__form');
    const iframeObserver = form
      ? new MutationObserver(mutations => {
          mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
              if (!node.getElementsByTagName) return;
              const newIframes = node.nodeName === 'IFRAME'
                ? [node]
                : Array.from(node.getElementsByTagName('iframe'));
              if (newIframes.length && !captchaCheckRef.current) {
                newIframes.filter(isExpressPaymentIframe).forEach(lockIframe);
              }
            });
          });
        })
      : null;

    if (iframeObserver) {
      iframeObserver.observe(form, {childList: true, subtree: true});
    }

    return () => {
      // Unsubscribe from WC store updates.
      unsubscribe && unsubscribe();
      if (iframeObserver) iframeObserver.disconnect();
      // Do NOT delete window.onCheckoutCaptchaSuccess / Expired.
      // The rendered widget still holds references to them by name and will
      // call them when the user interacts — potentially after React has gone
      // through multiple re-render cycles.
    };
  }, []); // Empty deps — mount once, never re-run.

  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null,
    (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      id: "recaptcha-container",
      className: "g-recaptcha",
      style: {
        marginBottom: '-15px',
        marginTop: '15px'
      }
    }),
    (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_woocommerce_blocks_checkout__WEBPACK_IMPORTED_MODULE_4__.ValidatedTextInput, {
      id: "captcha_check",
      type: "text",
      required: true,
      value: captchaCheck ? '1' : '',
      onChange: v => setCaptchaCheck(v),
      className: "hidden-captcha-input",
      disabled: false,
      style: {display: 'none'},
      errorMessage: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Please complete the reCAPTCHA.', 'captcha-block')
    })
  );
};

registerCheckoutBlock({
  metadata:  _block_json__WEBPACK_IMPORTED_MODULE_1__,
  component: Block
});
})();

/******/ 	return __webpack_exports__;
/******/ })()
;
});
//# sourceMappingURL=captcha-block-frontend.js.map