/**
 * My JavaScript file
 *
 * @package woocommerce-anti-fraud
 * @subpackage js
 * @since 1.0.0
 *
 * @textdomain woocommerce-anti-fraud
 */

jQuery(document).ready(function () {

    /* Geo Localion */
    function getUrlVars() {
        var vars = [], hash;
        var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
        for (var i = 0; i < hashes.length; i++) {
            hash = hashes[i].split('=');
            vars.push(hash[0]);
            vars[hash[0]] = hash[1];
        }
        return vars;
    }

    var section = getUrlVars()["section"];
    if (section == 'rules') {

        var on_save = jQuery('#on_save').text();
        var on_success = jQuery('#on_success').text();
        if (on_save) {

            jQuery('#root_error').hide();
        }
        if (on_success) {

            jQuery('#root_error').hide();
        }

    }

    jQuery("#on_save").click(function () {
        jQuery.ajax({
            method: 'POST',
            url: ajaxurl,
            data: {
                action: 'bigdatacloud_dismiss_notice_save',
                _wpnonce: (typeof wcAfAdmin !== 'undefined') ? wcAfAdmin.nonce : '',
            },
            success: function (result) {
                console.log(result);
            }
        });
    });

    var isChecked = jQuery('#wc_af_geolocation_order').is(':checked');
    if (isChecked) {

        jQuery('#bigdatacloud_api_key').attr('required', 'required');
        jQuery('#bigdatacloud_api_key').attr('disabled', false);

    } else {
        jQuery('#bigdatacloud_api_key').attr('required', false);
        jQuery('#bigdatacloud_api_key').attr('disabled', 'disabled');
    }

    jQuery('#wc_af_geolocation_order').change(function () {
        var isChecked = jQuery(this).is(':checked');
        if (!isChecked) {

            jQuery('#bigdatacloud_api_key').attr('required', false);
            jQuery('#bigdatacloud_api_key').attr('disabled', 'disabled');
        } else {

            jQuery('#bigdatacloud_api_key').attr('required', 'required');
            jQuery('#bigdatacloud_api_key').attr('disabled', false);

        }
    })
    /* Geo Localion End */

    var high_risk_score = jQuery('#wc_settings_anti_fraud_higher_risk_threshold').val();
    var low_risk_score = jQuery('#wc_settings_anti_fraud_low_risk_threshold').val();

    jQuery('#wc_settings_anti_fraud_low_risk_threshold').attr({
        "max": high_risk_score
    })
    jQuery('#wc_settings_anti_fraud_higher_risk_threshold').attr({
        "min": low_risk_score
    })

    var isPayWhitelistEnabled = jQuery('#wc_af_enable_whitelist_payment_method').is(':checked');
    if (!isPayWhitelistEnabled) {
        jQuery('#wc_settings_anti_fraud_whitelist_payment_method').attr('disabled', 'disabled');
    }

    var isPayWhitelistEnabled = jQuery('#wc_af_enable_api_keys_whitelist').is(':checked');
    if (!isPayWhitelistEnabled) {
        jQuery('#wc_settings_anti_fraud_whitelist_restapi').attr('disabled', 'disabled');
    }

    var isRoleWhitelistEnabled = jQuery('#wc_af_enable_whitelist_user_roles').is(':checked');
    if (!isRoleWhitelistEnabled) {
        jQuery('#wc_af_whitelist_user_roles').attr('disabled', 'disabled');
    }

    var isUpdateStatus = jQuery('#wc_af_fraud_update_state').is(':checked');
    if (!isUpdateStatus) {
        jQuery('#wc_settings_anti_fraud_cancel_score').attr('disabled', 'disabled');
        jQuery('#wc_settings_anti_fraud_hold_score').attr('disabled', 'disabled');
    }

    jQuery('.forminp-checkbox input[type="checkbox"]').each(function () {
        var isEnabled = jQuery(this).is(':checked');
        var ele = jQuery(this);
        disableScoreSlider(ele, isEnabled);
    })

    jQuery('.forminp-checkbox input[type="checkbox"]').change(function () {
        var isEnabled = jQuery(this).is(':checked');
        var ele = jQuery(this);
        disableScoreSlider(ele, isEnabled);
    })

    jQuery('.forminp-number input[type="number"], .forminp-select select').change(function () {
        var multi_handle = jQuery(this).parents('tr').find('.forminp-slider .score-slider').hasClass('multi-handle');
        if (!multi_handle) {

            if (!jQuery(this).val()) {
                var score = 0;
                jQuery(this).val(0)
            } else {
                var score = parseInt(jQuery(this).val());
            }
            var low_score = parseInt(jQuery(this).parents('tr').find('.forminp-slider .score-value').data('min-score'));
            var high_score = parseInt(jQuery(this).parents('tr').find('.forminp-slider .score-value').data('max-score'));

            var ele = jQuery(this).parents('tr');

            scoreSlider(low_score, high_score, score, ele);

        } else {

        }
    })

    function scoreSlider(low_score, high_score, score, ele) {

        if (score > 0 && score <= low_score) {
            jQuery(ele).find('.forminp-slider .score-value').css('border-color', 'rgba(90,198,125,1)');
        } else if (score >= low_score && score <= high_score) {
            jQuery(ele).find('.forminp-slider .score-value').css('border-color', 'rgba(205,119,57,1)');
        } else if (score >= high_score) {
            jQuery(ele).find('.forminp-slider .score-value').css('border-color', 'rgba(185,74,72,1)');
        } else {
            jQuery(ele).find('.forminp-slider .score-value').css('border-color', '#777777');
        }
        if (score == 0) {
            jQuery(ele).find('.forminp-slider .score-bar').css('background', '#777777');
        } else {

            jQuery(ele).find('.forminp-slider .score-bar').css('background', 'linear-gradient(90deg, rgba(90,198,125,1) ' + (low_score - 25) + '%, rgba(205,119,57,1) ' + (high_score) + '%, rgba(185,74,72,1) 100%)');
        }
        jQuery(ele).find('.forminp-slider .score-value').css('left', score + '%');
        jQuery(ele).find('.forminp-slider .score-value .score-text').html(score);
    }

    function disableScoreSlider($this, isChecked) {
        var nextField = jQuery($this).parents('td').nextAll();

        if (isChecked == false) {
            jQuery(nextField.get(0)).find('input').attr('disabled', 'disabled');
            if (jQuery(nextField.get(1)).hasClass('forminp-slider')) {
                var score = 0
                var low_score = parseInt(jQuery(nextField.get(1)).find('.score-value').data('min-score'));
                var high_score = parseInt(jQuery(nextField.get(1)).find('.score-value').data('max-score'));
                var ele = jQuery(nextField.get(1)).parents('tr');
                scoreSlider(low_score, high_score, score, ele);
            }
        } else {
            jQuery(nextField.get(0)).find('input').attr('disabled', false);
            if (jQuery(nextField.get(1)).hasClass('forminp-slider')) {
                var score = jQuery(nextField.get(0)).find('input').val();
                var low_score = parseInt(jQuery(nextField.get(1)).find('.score-value').data('min-score'));
                var high_score = parseInt(jQuery(nextField.get(1)).find('.score-value').data('max-score'));
                var ele = jQuery(nextField.get(1)).parents('tr');
                scoreSlider(low_score, high_score, score, ele);
            }
        }
    }

    jQuery('#wc_settings_anti_fraud_low_risk_threshold').change(function () {
        var minScore = jQuery(this).val();
        var maxScore = jQuery('#wc_settings_anti_fraud_higher_risk_threshold').val();

        var ele = jQuery(this).parents('tr');

        jQuery('#wc_settings_anti_fraud_higher_risk_threshold').attr({
            "min": minScore
        });

        if (minScore == 0 && maxScore == 0) {
            jQuery(ele).find('.forminp-slider .score-bar').css('background', '#777777');
        } else {
            jQuery(ele).find('.forminp-slider .score-bar').css('background', 'linear-gradient(90deg, rgba(90,198,125,1) ' + (minScore - 25) + '%, rgba(205,119,57,1) ' + (maxScore) + '%, rgba(185,74,72,1) 100%)');
        }

        if (minScore > 0) {
            jQuery(ele).find('.forminp-slider .score-value.min-score').css('border-color', 'rgba(90,198,125,1)');
        } else {
            jQuery(ele).find('.forminp-slider .score-value.min-score').css('border-color', '#777777');
        }
        jQuery(ele).find('.forminp-slider .score-value.min-score').css('left', minScore + '%');
        jQuery(ele).find('.forminp-slider .score-value.min-score .score-text.min-score').html(minScore);

    })

    jQuery('#wc_settings_anti_fraud_higher_risk_threshold').change(function () {
        var maxScore = jQuery(this).val();
        var minScore = jQuery('#wc_settings_anti_fraud_low_risk_threshold').val();

        var ele = jQuery(this).parents('tbody').find('.forminp-slider');
        jQuery('#wc_settings_anti_fraud_low_risk_threshold').attr({
            "max": maxScore
        });

        if (minScore == 0 && maxScore == 0) {
            jQuery(ele).find(' .score-bar').css('background', '#777777');
        } else {
            jQuery(ele).find(' .score-bar').css('background', 'linear-gradient(90deg, rgba(90,198,125,1) ' + (minScore - 25) + '%, rgba(205,119,57,1) ' + (maxScore) + '%, rgba(185,74,72,1) 100%)');
        }

        if (maxScore > 0) {
            jQuery(ele).find(' .score-value.max-score').css('border-color', 'rgba(205,119,57,1)');
        } else {
            jQuery(ele).find(' .score-value.max-score').css('border-color', '#777777');
        }
        jQuery(ele).find(' .score-value.max-score').css('left', maxScore + '%');
        jQuery(ele).find(' .score-value.max-score .score-text.max-score').html(maxScore);

    })

    jQuery('#wc_settings_anti_fraud_cancel_score, #wc_settings_anti_fraud_hold_score').change(function () {
        jQuery(this).parents('tr').find('.forminp-slider .score-slider');

        if (!jQuery(this).val()) {
            var score = 0;
            jQuery(this).val(0)
        } else {
            var score = parseInt(jQuery(this).val());
        }

        var low_score = parseInt(jQuery(this).parents('tr').find('.forminp-slider .score-value').data('min-score'));
        var high_score = parseInt(jQuery(this).parents('tr').find('.forminp-slider .score-value').data('max-score'));

        var ele = jQuery(this).parents('tr');

        if (score > 0 && score <= low_score) {
            jQuery(ele).find('.forminp-slider .score-bar-label').text(wp.i18n.__('Low Risk Order', 'woocommerce-anti-fraud'));
        } else if (score >= low_score && score <= high_score) {
            jQuery(ele).find('.forminp-slider .score-bar-label').text(wp.i18n.__('Medium Risk Order', 'woocommerce-anti-fraud'));
        } else if (score >= high_score) {
            jQuery(ele).find('.forminp-slider .score-bar-label').text(wp.i18n.__('High Risk Order', 'woocommerce-anti-fraud'));
        } else {
            jQuery(ele).find('.forminp-slider .score-bar-label').text(wp.i18n.__('Disabled', 'woocommerce-anti-fraud'));
        }


    })


    jQuery('#wc_af_enable_whitelist_payment_method').change(function () {
        var isChecked = jQuery(this).is(':checked');
        if (isChecked) {
            jQuery('#wc_settings_anti_fraud_whitelist_payment_method').attr('disabled', false);
        } else {
            jQuery('#wc_settings_anti_fraud_whitelist_payment_method').attr('disabled', 'disabled');
        }
    })

    jQuery('#wc_af_enable_api_keys_whitelist').change(function () {
        var isChecked = jQuery(this).is(':checked');
        if (isChecked) {
            jQuery('#wc_settings_anti_fraud_whitelist_restapi').attr('disabled', false);
        } else {
            jQuery('#wc_settings_anti_fraud_whitelist_restapi').attr('disabled', 'disabled');
        }
    })


    jQuery('#wc_af_enable_whitelist_user_roles').change(function () {
        var isChecked = jQuery(this).is(':checked');
        if (isChecked) {
            jQuery('#wc_af_whitelist_user_roles').attr('disabled', false);
        } else {
            jQuery('#wc_af_whitelist_user_roles').attr('disabled', 'disabled');
        }
    })

    jQuery('#wc_af_fraud_update_state').change(function () {
        var isChecked = jQuery(this).is(':checked');
        if (isChecked) {
            jQuery('#wc_settings_anti_fraud_cancel_score').attr('disabled', false);
            jQuery('#wc_settings_anti_fraud_hold_score').attr('disabled', false);
        } else {
            jQuery('#wc_settings_anti_fraud_cancel_score').attr('disabled', 'disabled');
            jQuery('#wc_settings_anti_fraud_hold_score').attr('disabled', 'disabled');
        }
    })

    /* Related to debug log */
    if (jQuery('#wc_af_enable_log_check').is(':checked')) {

        jQuery('#debug_log_tbl').show();
    } else {
        jQuery('#debug_log_tbl').hide();
    }

    jQuery('#wc_af_enable_log_check').click(function () {
        if (jQuery('#wc_af_enable_log_check').is(':checked')) {

            jQuery('#debug_log_tbl').show();
        } else {
            jQuery('#debug_log_tbl').hide();
        }

    });
    /* end to debug log */

    /* Related to reCaptcha */

    var url = new URL(document.URL).searchParams;
    var tabname = url.get('section');

    if (tabname == 'recaptcha_settings') {

        jQuery('#wc_af_enable_whitelist_payment_method').change(function () {
            var isChecked = jQuery(this).is(':checked');
            if (isChecked) {
                jQuery('#wc_settings_anti_fraud_whitelist_payment_method').attr('disabled', false);
            } else {
                jQuery('#wc_settings_anti_fraud_whitelist_payment_method').attr('disabled', 'disabled');
            }
        })

        jQuery('#wc_af_enable_whitelist_user_roles').change(function () {
            var isChecked = jQuery(this).is(':checked');
            if (isChecked) {
                jQuery('#wc_af_whitelist_user_roles').attr('disabled', false);
            } else {
                jQuery('#wc_af_whitelist_user_roles').attr('disabled', 'disabled');
            }
        })

        jQuery('#wc_af_fraud_update_state').change(function () {
            var isChecked = jQuery(this).is(':checked');
            if (isChecked) {
                jQuery('#wc_settings_anti_fraud_cancel_score').attr('disabled', false);
                jQuery('#wc_settings_anti_fraud_hold_score').attr('disabled', false);
            } else {
                jQuery('#wc_settings_anti_fraud_cancel_score').attr('disabled', 'disabled');
                jQuery('#wc_settings_anti_fraud_hold_score').attr('disabled', 'disabled');
            }
        })
    }
    /* Related to reCaptcha End */

    // PLUGINS-2675
    /* Related to card attack */
    var section = getUrlVars()["section"];
    if (section == 'card_attacks') {

        let active_not = jQuery('#active_not').text();
        if ('Not Active' === active_not) {
            jQuery('#wc_af_thresholds_settings').css('background', 'red');
            jQuery('.wc_af_sub-section-title span.woocommerce-help-tip').css('color', '#fff');

        } else {
            jQuery('#wc_af_thresholds_settings').css('background', 'green');
            jQuery('.wc_af_sub-section-title span.woocommerce-help-tip').css('color', '#fff');

        }

        let active_not_sec_2 = jQuery('#active_not_sec_2').text();
        if ('Not Active' === active_not_sec_2) {
            jQuery('#wc_af_order_attempts_rules_settings').css('background', 'red');
            jQuery('.wc_af_sub-section-title span.woocommerce-help-tip').css('color', '#fff');
        } else {
            jQuery('#wc_af_order_attempts_rules_settings').css('background', 'green');
            jQuery('.wc_af_sub-section-title span.woocommerce-help-tip').css('color', '#fff');
        }

        let active_not_sec_3 = jQuery('#active_not_sec_3').text();
        if ('Not Active' === active_not_sec_3) {
            jQuery('#wc_af_order_payment_attempts_settings').css('background', 'red');
            // jQuery('.wc_af_sub-section-title span.woocommerce-help-tip').css('color', '#fff');
        } else {
            jQuery('#wc_af_order_payment_attempts_settings').css('background', 'green');
            // jQuery('.wc_af_sub-section-title span.woocommerce-help-tip').css('color', '#fff');
        }

        let active_not_sec_4 = jQuery('#active_not_sec_4').text();
        if ('Not Active' === active_not_sec_4) {
            jQuery('#wc_af_order_between_times_settings').css('background', 'red');
            // jQuery('.wc_af_sub-section-title span.woocommerce-help-tip').css('color', '#fff');
        } else {
            jQuery('#wc_af_order_between_times_settings').css('background', 'green');
            // jQuery('.wc_af_sub-section-title span.woocommerce-help-tip').css('color', '#fff');
        }
    }


    // PLUGINS-2675
    /* Related to card attack */
    var section = getUrlVars()["section"];
    if (section == 'rules' || section == 'card_attacks') {

        if (jQuery('#wc_af_attempt_count_check').is(':checked')) {

            jQuery('#wc_settings_anti_fraud_attempt_time_span').prop('min', 1);
            jQuery('#wc_settings_anti_fraud_max_order_attempt_time_span').prop('min', 1);
            jQuery('span#many_astric_required').css('color', 'red');
        } else {
            jQuery('span#many_astric_required').css('color', 'black');
            jQuery('#wc_settings_anti_fraud_attempt_time_span').prop('min', 0);
            jQuery('#wc_settings_anti_fraud_max_order_attempt_time_span').prop('min', 0);

        }

        jQuery('#wc_af_attempt_count_check').change(function () {

            var isChecked = jQuery(this).is(':checked');
            if (isChecked) {
                jQuery('#wc_settings_anti_fraud_attempt_time_span').prop('min', 1);
                jQuery('#wc_settings_anti_fraud_max_order_attempt_time_span').prop('min', 1);
                jQuery('span#many_astric_required').css('color', 'red');
            } else {
                jQuery('#wc_settings_anti_fraud_attempt_time_span').prop('min', 0);
                jQuery('#wc_settings_anti_fraud_max_order_attempt_time_span').prop('min', 0);
                jQuery('span#many_astric_required').css('color', 'black');
            }
        })


        if (jQuery('#wc_af_order_payment_attempt_check').is(':checked')) {

            jQuery('#wc_settings_anti_fraud_max_order_payment_attempt').prop('min', 1);
            jQuery('span#payment_astric_required').css('color', 'red');
        } else {
            jQuery('span#payment_astric_required').css('color', 'black');
            jQuery('#wc_settings_anti_fraud_max_order_payment_attempt').prop('min', 0);

        }

        jQuery('#wc_af_order_payment_attempt_check').change(function () {

            var isChecked = jQuery(this).is(':checked');
            if (isChecked) {
                jQuery('#wc_settings_anti_fraud_max_order_payment_attempt').prop('min', 1);
                jQuery('span#payment_astric_required').css('color', 'red');
            } else {
                jQuery('#wc_settings_anti_fraud_max_order_payment_attempt').prop('min', 0);
                jQuery('span#payment_astric_required').css('color', 'black');
            }
        })


        if (jQuery('#wc_af_limit_order_count').is(':checked')) {

            jQuery('span#limit_astric_required').css('color', 'red');
            jQuery('#wc_af_limit_time_start').attr('required', 'required');
            jQuery('#wc_af_limit_time_end').attr('required', 'required');
            jQuery('#wc_af_allowed_order_limit').prop('min', 1);

        } else {
            jQuery('span#limit_astric_required').css('color', 'black');
            jQuery('#wc_af_limit_time_end').removeAttr('required');
            jQuery('#wc_af_limit_time_start').removeAttr('required');
            jQuery('#wc_af_allowed_order_limit').prop('min', 0);
        }

        jQuery('#wc_af_limit_order_count').change(function () {

            var isChecked = jQuery(this).is(':checked');
            if (isChecked) {
                jQuery('#wc_af_limit_time_start').attr('required', 'required');
                jQuery('#wc_af_limit_time_end').attr('required', 'required');
                jQuery('#wc_af_allowed_order_limit').prop('min', 1);

                jQuery('span#limit_astric_required').css('color', 'red');
            } else {
                jQuery('#wc_af_allowed_order_limit').prop('min', 0);
                jQuery('span#limit_astric_required').css('color', 'black');
                jQuery('#wc_af_limit_time_end').removeAttr('required');
                jQuery('#wc_af_limit_time_start').removeAttr('required');

            }
        })
    }


    /* Related to Order level fraud check */
    jQuery(".fraud_action").on("click", "#fraud_action", function () {

        var orderid = jQuery(this).attr('aria-label');
        var $this = jQuery(this);
        jQuery(".fraud_action").prop('disabled', true);
        $this.prop('disabled', true);
        $this.text(wp.i18n.__('Processing..', 'woocommerce-anti-fraud'));
        jQuery.ajax({
            method: 'POST',
            url: ajaxurl,
            data: {
                action: 'order_level_froud_check',
                orderid: orderid,
                _wpnonce: (typeof wcAfAdmin !== 'undefined') ? wcAfAdmin.nonce : '',
            },
            success: function (result) {
                console.log(result);
                window.location.reload();
            }
        });
    });

    jQuery('#wc_settings_anti_fraud_whitelist_tagsinput').on('blur keydown', function (event) {
        if (event.type === 'keydown' && (event.key === 'Enter' || event.key === 'Tab' || event.key === ' ')) {
            jQuery("span#warningmsg").html('');
            validateEmails(jQuery(this));
        } else if (event.type === 'blur') {
            validateEmails(jQuery(this));
        }
    });

    function validateEmails($textarea) {
        let i, emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            text = $textarea.val(),
            emails = text.split(/[\s,]+/),
            isValid = true;

        for (i = 0; i < emails.length; i++) {
            if (!emailPattern.test(emails[i]) && emails[i] !== '') {

                console.log(emails[i]);

                isValid = false;
                break;
            }
        }

        let textsss = '<span id="warningmsg" style="color:red;">Note: Please separate multiple emails with spaces or commas.</span>';

        if (!isValid || (emails.length === 1 && emails[0] === text)) {
            jQuery("#wc_settings_anti_fraud_whitelist_tagsinput").after(textsss);
        } else {
            jQuery("span#warningmsg").html('');
        }
    }

})

jQuery(document).on('click', '.notice-dismiss', function () {
    let thisNotice = jQuery(this).closest('.is-dismissible'),
        notice_id = thisNotice.data('notice-id'),
        nonce = thisNotice.data('nonce');

    console.log(ajaxurl);

    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'dismiss_admin_notice',
            notice_id: notice_id,
            nonce: nonce
        }
    });
});

// jQuery('#wc_af_enable_sms_verification').on('change', function () {
//     let el_sms_fraudlabspro_api_key = jQuery('#wc_af_sms_fraudlabspro_api_key'),
//         el_sms_fraudlabspro_api_key_wrapper = el_sms_fraudlabspro_api_key.parent().parent();
//
//     if (jQuery(this).is(':checked')) {
//         el_sms_fraudlabspro_api_key_wrapper.show();
//     } else {
//         el_sms_fraudlabspro_api_key_wrapper.hide();
//     }
// });

jQuery(document).on('click', '.notice-dismiss', function () {
    let thisNotice = jQuery(this).closest('.is-dismissible'),
        notice_id = thisNotice.data('notice-id'),
        nonce = thisNotice.data('nonce');

    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'dismiss_admin_notice',
            notice_id: notice_id,
            nonce: nonce
        }
    });
});

;(function ($, window, doccument) {
    'use strict';

    $(doccument).on('change', '#wc_af_throttle_api_based_orders_check', function () {
        let el_orders_through_api_per_hour = $('#wc_af_max_orders_through_api_per_hour').parent().parent();

        if ($(this).is(':checked')) {
            el_orders_through_api_per_hour.css('display', 'revert');
        } else {
            el_orders_through_api_per_hour.css('display', 'none');
        }
    });

    $(doccument).on('change', '#wc_af_recaptcha_enable_captcha', function () {
        let el_recaptcha_type = $('#wc_af_recaptcha_type'),
            selected_value = el_recaptcha_type.find(":selected").val(),
            for_both = ['wc_af_recaptcha_type', 'wc_af_captcha_checkout_position', 'wc_af_recaptcha_site_key', 'wc_af_recaptcha_secret_key', 'wc_af_turnstile_site_key', 'wc_af_turnstile_secret_key'],
            for_google_recaptcha = ['wc_af_recaptcha_site_key', 'wc_af_recaptcha_secret_key'],
            for_cf_turnstile = ['wc_af_turnstile_site_key', 'wc_af_turnstile_secret_key'];

        for_both.forEach(function (item_key) {
            let el_item = $('#' + item_key).parent().parent();
            el_item.hide();
        });

        if ($(this).is(':checked')) {
            el_recaptcha_type.parent().parent().show();
            $('#wc_af_captcha_checkout_position').parent().parent().show();

            if (selected_value === 'google_recaptcha') {
                for_google_recaptcha.forEach(function (item_key) {
                    let el_item = $('#' + item_key).parent().parent();
                    el_item.show();
                });
            }

            if (selected_value === 'cf_turnstile') {
                for_cf_turnstile.forEach(function (item_key) {
                    let el_item = $('#' + item_key).parent().parent();
                    el_item.show();
                });
            }
        }
    });

    $(doccument).on('change', '#wc_af_recaptcha_type', function () {
        let selected_value = $(this).find(":selected").val(),
            for_both = ['wc_af_recaptcha_site_key', 'wc_af_recaptcha_secret_key', 'wc_af_turnstile_site_key', 'wc_af_turnstile_secret_key'],
            for_google_recaptcha = ['wc_af_recaptcha_site_key', 'wc_af_recaptcha_secret_key'],
            for_cf_turnstile = ['wc_af_turnstile_site_key', 'wc_af_turnstile_secret_key'];

        for_both.forEach(function (item_key) {
            let el_item = $('#' + item_key).parent().parent();
            el_item.hide();
        });

        if (selected_value === 'google_recaptcha') {
            for_google_recaptcha.forEach(function (item_key) {
                let el_item = $('#' + item_key).parent().parent();
                el_item.show();
            });
        }

        if (selected_value === 'cf_turnstile') {
            for_cf_turnstile.forEach(function (item_key) {
                let el_item = $('#' + item_key).parent().parent();
                el_item.show();
            });
        }
    });

    // Related set default protection level ANTIFRAUD-71
    // Related set default protection level ANTIFRAUD-71
    jQuery(document).ready(function($) {

        if (typeof shouldShowModal !== 'undefined' && shouldShowModal) {
            $('#protection-modal').fadeIn();
        }

        $('#open-protection-modal').on('click', function(e) {
            e.preventDefault();
            $('#protection-modal').fadeIn();
        });

        // Close button without selecting level
        $('#close-protection-modal').on('click', function() {
            $('#protection-modal').fadeOut();

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'set_protection_closed',
                    nonce: protectionNonce
                },
                success: function() {
                    location.reload();
                }
            });
        });

        // Select protection level
        $('.protection-btn').on('click', function() {
            var level = $(this).data('level');

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'set_protection_level',
                    level: level,
                    nonce: protectionNonce
                },
                success: function(response) {
                    if (response.success) {
                        $('.protection-buttons').hide();
                        $('#protection-modal-content h2').hide();
                        $('#thank-you-message').fadeIn();
                        setTimeout(function() {
                            $('#protection-modal').fadeOut();
                            window.location.href = settingsUrl;
                        }, 2000);
                    } else {
                        console.log('Something went wrong!');
                        location.reload();
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error: ' + error);
                }
            });
        });
    });
}(jQuery, window, document));

jQuery(document).on('click', '#successpcap .notice-dismiss', function() {
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'pcap_dismiss_notice',
            _wpnonce: (typeof wcAfAdmin !== 'undefined') ? wcAfAdmin.nonce : '',
        },
        success: function(response) {
            console.log('Notice dismissed:', response);
        }
    });
});

jQuery(document).ready(function ($) {

    let params = new URLSearchParams(window.location.search);
    let section = params.get('section');
    let tab     = params.get('tab');

    // Check tab + section
    if (tab === 'wc_af' && section === 'chargeback_settings') {
        $('button.woocommerce-save-button').remove();
    }

    if (tab === 'wc_af' && section === '') {
        $('button.woocommerce-save-button').remove();
    }

});

jQuery(document).ready(function($) {

    $('#wc_af_cleanup_timeframe').on('change', function () {

        let selected = $(this).val();
        let $dropdown = $(this);

        // Disable the dropdown immediately
        $dropdown.prop('disabled', true);
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_failed_orders_by_timeframe',
                timeframe: selected,
                _wpnonce: (typeof wcAfAdmin !== 'undefined') ? wcAfAdmin.nonce : '',
            },

            beforeSend: function() {
                console.log('processing...');
            },

            success: function(response) {
                if (response.success) {
                    console.log(response.data.message + "\nTotal Failed Orders: " + response.data.total_failed_orders);
                    location.reload(); // 🔄 Reload page on success
                } else {
                    console.log("Error: " + response.data);
                }
            },

            error: function() {
                alert("AJAX request failed.");
            },
            complete: function () {
                // Re-enable the dropdown AFTER the AJAX is fully complete
                $dropdown.prop('disabled', false);
                
            }

        });
    });
});

jQuery(function ($) {
    $('#cleanup_order_btn').on('click', function () {
        var $this = jQuery(this);

        $this.text('Processing... (60 seconds+)');
        $this.prop('disabled', true);
        processCleanupBatch();
    });

    function processCleanupBatch() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'move_to_trash_action',
                _wpnonce: (typeof wcAfAdmin !== 'undefined') ? wcAfAdmin.nonce : '',
            },
            success: function (response) {
                if (response.success) {
                    console.log(response.data.message);

                    if (response.data.remaining > 0) {
                        // Continue until all batches processed
                        setTimeout(processCleanupBatch, 500);
                    } else {
                        //alert('✅ All failed orders moved to trash successfully!');
                        location.reload();
                    }
                } else {
                    console.error('❌ ' + response.data.message);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX error: ' + error);
            }
        });
    }
});


jQuery(function ($) {

    function wcafInitCaptcha() {

        if (typeof grecaptcha === 'undefined') {
            setTimeout(wcafInitCaptcha, 300);
            return;
        }

        let captcha = $('#wc-af-recaptcha-checkout');

        if (!captcha.length) {
            return;
        }

        if (captcha.attr('data-rendered') === 'true') {
            return;
        }

        grecaptcha.render('wc-af-recaptcha-checkout', {
            sitekey: captcha.data('sitekey'),
            callback: function (token) {
                $('#af_checkout_captcha_response').val(token);
            }
        });

        captcha.attr('data-rendered', 'true');
    }

    wcafInitCaptcha();

    $(document.body).on('updated_checkout', function () {
        $('#wc-af-recaptcha-checkout').removeAttr('data-rendered');
        wcafInitCaptcha();
    });

});