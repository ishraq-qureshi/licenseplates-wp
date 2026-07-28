import metadata from './block.json';
import {__} from '@wordpress/i18n';
import {useEffect, useRef, useState} from '@wordpress/element';
import {ValidatedTextInput} from '@woocommerce/blocks-checkout';

const {registerCheckoutBlock} = wc.blocksCheckout;

// ─── Cookie helper ────────────────────────────────────────────────────────────
// Module-level so it is available to all callbacks without any closure
// dependency on React render cycles or useEffect scopes.
function setCookie(name, value, daysToExpire) {
    const date = new Date();
    date.setTime(date.getTime() + daysToExpire * 24 * 60 * 60 * 1000);
    const expires = 'expires=' + date.toUTCString();
    document.cookie = name + '=' + value + ';' + expires + ';path=/';
}

// ─── Iframe lock helpers ──────────────────────────────────────────────────────
function lockIframe(iframe) {
    iframe.style.pointerEvents = 'none';
    iframe.style.opacity = '0.5';
}
function unlockIframe(iframe) {
    iframe.style.pointerEvents = 'auto';
    iframe.style.opacity = '1';
}

// Only express-checkout iframes are locked until reCAPTCHA is solved.
// Captcha widget iframes and standard payment-method iframes are never touched.
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

const Block = ({checkoutExtensionData}) => {
    const [captchaCheck, setCaptchaCheck] = useState('');
    const {setExtensionData} = checkoutExtensionData;

    // Ref so the MutationObserver (set up once on mount) can always read the
    // *current* captchaCheck value without needing to reconnect on every change.
    const captchaCheckRef = useRef('');

    // ── Forward token to Woo Blocks Store API ─────────────────────────────
    useEffect(() => {
        setExtensionData('checkout-captcha-block', 'checkout_captcha', captchaCheck);
    }, [captchaCheck]);

    // ── Lock / unlock payment iframes when CAPTCHA state changes ─────────
    // Google reCAPTCHA: only PayPal / express-checkout iframes.
    // Turnstile: preserve existing global iframe lock behaviour unchanged.
    // Also keeps captchaCheckRef in sync so the MutationObserver below can
    // read the latest value without a stale closure.
    useEffect(() => {
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

    // ── Inline CSS for iframe fade transition ─────────────────────────────
    useEffect(() => {
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

    // ── Mount the CAPTCHA widget ──────────────────────────────────────────
    // Runs ONCE on mount (empty deps array).
    //
    // Root causes for Firefox (and intermittent Chrome) failures in the old code:
    //
    //  1. useEffect([recaptchaRendered]) re-runs when recaptchaRendered changes,
    //     running cleanup which deleted window.onCheckoutCaptchaSuccess /Expired.
    //     Between the deletion and re-registration there is a window where the
    //     callbacks are undefined — Firefox's stricter task scheduler hits this gap.
    //
    //  2. renderRecaptcha() was called immediately after the script tag was appended,
    //     before the API had actually loaded (async). A fixed setTimeout(500ms) was
    //     used for Turnstile — a timing guess that fails on slow connections.
    //
    //  3. If grecaptcha.render() threw (element not yet in DOM), setRecaptchaRendered
    //     was never called, leaving no retry path. The widget never appeared.
    //
    //  4. window.onRecaptchaLoad was never cleaned up; on re-renders it silently
    //     accumulated stale closures that could capture wrong state.
    //
    // Fix: model after the rcfwc plugin (which works everywhere):
    //   • wp.data.subscribe() fires on every WC store state change → tryRender()
    //     is retried automatically until both the DOM element AND the API exist.
    //   • script.onload for Google API → no global onRecaptchaLoad needed.
    //   • Callbacks defined once, never deleted → Google widget can always call them.
    useEffect(() => {
        const captchaKey    = captcha_ajax.captcha_key;
        const captchaEnable = captcha_ajax.captcha_enable;
        const captchaType   = captcha_ajax.captcha_type;

        if (!captchaKey || captchaEnable !== 'yes') {
            return;
        }

        // Global callbacks — defined once and kept for the full page lifetime.
        // Google reCAPTCHA stores a reference to them by name string and calls
        // them after user interaction (which may be long after a React re-render).
        // Deleting them in cleanup would silently break the widget.
        window.onCheckoutCaptchaSuccess = function (response) {
            setCaptchaCheck(response);
            // Cookie lets the PHP backend (Store API hook) read the token even
            // when the Woo Blocks extension_data channel is not forwarded correctly.
            setCookie('recaptcha_response', response, 1);
        };

        window.onCheckoutCaptchaExpired = function () {
            setCaptchaCheck('');
        };

        // ── Cloudflare Turnstile ──────────────────────────────────────────
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
                        'expired-callback': window.onCheckoutCaptchaExpired,
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

        // ── Google reCAPTCHA ──────────────────────────────────────────────
        // tryRender() is safe to call multiple times: it checks for element
        // existence and existing innerHTML before doing anything.
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
                    'expired-callback': 'onCheckoutCaptchaExpired',
                });
                // Widget mounted — stop listening to store updates.
                unsubscribe && unsubscribe();
            } catch (e) {
                // grecaptcha.render throws if the widget is already attached to
                // this element.  Treat as already-rendered and stop polling.
                unsubscribe && unsubscribe();
            }
        };

        // Subscribe to WC store/cart state so tryRender() is called whenever
        // the checkout block updates its DOM.  This is the same strategy the
        // rcfwc plugin uses and is reliable in Chrome, Firefox, and Safari.
        if (typeof wp !== 'undefined' && wp.data && wp.data.subscribe) {
            unsubscribe = wp.data.subscribe(tryRender, 'wc/store/cart');
        }

        // Load the Google API script (or call tryRender immediately if already loaded).
        if (typeof grecaptcha === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://www.google.com/recaptcha/api.js?render=explicit';
            script.async = true;
            // Use onload so tryRender fires exactly when the API is ready —
            // no global onRecaptchaLoad needed, no stale closure risks.
            script.onload = tryRender;
            document.head.appendChild(script);
        } else {
            // API already available (e.g. loaded by another plugin or cached).
            tryRender();
        }

        // ── PayPal / payment iframe guard ─────────────────────────────────
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
            // The rendered widget still holds a reference to them by name and
            // will call them when the user interacts — potentially after React
            // has gone through multiple re-render cycles.
        };
    }, []); // Empty deps — mount once, never re-run.

    return (
        <div>
            <div
                id="recaptcha-container"
                className="g-recaptcha"
                style={{marginBottom: '-15px', marginTop: '15px'}}
            ></div>
            <ValidatedTextInput
                id="captcha_check"
                type="text"
                required={true}
                value={captchaCheck ? '1' : ''}
                onChange={v => setCaptchaCheck(v)}
                className="hidden-captcha-input"
                disabled={false}
                style={{display: 'none'}}
                errorMessage={__('Please complete the reCAPTCHA.', 'captcha-block')}
            />
        </div>
    );
};

registerCheckoutBlock({metadata, component: Block});
