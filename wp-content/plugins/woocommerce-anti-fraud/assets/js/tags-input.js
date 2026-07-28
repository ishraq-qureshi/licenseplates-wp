(function ($) {

    var delimiter = new Array();
    var tags_callbacks = new Array();
    $.fn.doAutosize = function (o) {
        var minWidth = $(this).data('minwidth'),
            maxWidth = $(this).data('maxwidth'),
            val = '',
            input = $(this),
            testSubject = $('#' + $(this).data('tester_id'));

        if (val === (val = input.val())) {
            return;
        }

        // Enter new content into testSubject
        var escaped = val.replace(/&/g, '&amp;').replace(/\s/g, ' ').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        testSubject.html(escaped);
        // Calculate new width + whether to change
        var testerWidth = testSubject.width(),
            newWidth = (testerWidth + o.comfortZone) >= minWidth ? testerWidth + o.comfortZone : minWidth,
            currentWidth = input.width(),
            isValidWidthChange = (newWidth < currentWidth && newWidth >= minWidth)
                || (newWidth > minWidth && newWidth < maxWidth);

        // Animate width
        if (isValidWidthChange) {
            input.width(newWidth);
        }


    };
    $.fn.resetAutosize = function (options) {
        // alert(JSON.stringify(options));
        var minWidth = $(this).data('minwidth') || options.minInputWidth || $(this).width(),
            maxWidth = $(this).data('maxwidth') || options.maxInputWidth || ($(this).closest('.tagsinput').width() - options.inputPadding),
            val = '',
            input = $(this),
            testSubject = $('<tester/>').css({
                position: 'absolute',
                top: -9999,
                left: -9999,
                width: 'auto',
                fontSize: input.css('fontSize'),
                fontFamily: input.css('fontFamily'),
                fontWeight: input.css('fontWeight'),
                letterSpacing: input.css('letterSpacing'),
                whiteSpace: 'nowrap'
            }),
            testerId = $(this).attr('id') + '_autosize_tester';
        if (!$('#' + testerId).length > 0) {
            testSubject.attr('id', testerId);
            testSubject.appendTo('body');
        }

        input.data('minwidth', minWidth);
        input.data('maxwidth', maxWidth);
        input.data('tester_id', testerId);
        input.css('width', minWidth);
    };

    $.fn.addTag = function (value, options) {
        options = jQuery.extend({focus: false, callback: true}, options);
        this.each(function () {
            var id = $(this).attr('id');

            var tagslist = $(this).val().split(delimiter[id]);
            if (tagslist[0] == '') {
                tagslist = new Array();
            }

            value = jQuery.trim(value);

            if (options.unique) {
                var skipTag = $(this).tagExist(value);
                if (skipTag == true) {
                    //Marks fake input as not_valid to let styling it
                    $('#' + id + '_tag').addClass('not_valid');
                }
            } else {
                var skipTag = false;
            }

            if (value != '' && skipTag != true) {
                /* $('<span>').addClass('tag').append(
                     $('<span>').text(value).append('&nbsp;&nbsp;'),
                     $('<a>', {
                         href  : '#',
                         title : 'Removing tag',
                         text  : 'x'
                     }).click(function () {
                         return $('#' + id).removeTag(escape(value));
                     })
                 ).insertBefore('#' + id + '_addTag');

        tagslist.push(value);*/
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                var ipRegex = /^(?:\d{1,3}\.){3}\d{1,3}$|^([a-fA-F0-9]{1,4}:){7}[a-fA-F0-9]{1,4}$/;

                if (id == 'wc_settings_anti_fraud_whitelist' || id == 'wc_settings_anti_fraudblacklist_emails') {
                    // Split by comma, space, or newline
                    var emails = value.split(/[\s,]+/);

                    var invalidEmails = [];
                    emails.forEach(function (email) {
                        email = email.trim(); // Remove leading/trailing spaces

                        if (emailRegex.test(email)) {
                            // Create and add tag for valid email
                            $('<span>').addClass('tag').append(
                                $('<span>').text(email).append('&nbsp;&nbsp;'),
                                $('<a>', {
                                    href: '#',
                                    title: 'Removing tag',
                                    text: 'x'
                                }).click(function () {
                                    return $('#' + id).removeTag(escape(email));
                                })
                            ).insertBefore('#' + id + '_addTag');

                            tagslist.push(email);
                        } else {
                            // Collect invalid emails
                            if (email !== '') {
                                invalidEmails.push(email);
                            }
                        }
                    });

                    // Show an alert for any invalid emails
                    if (invalidEmails.length > 0) {
                        alert('The following email(s) are invalid:\n' + invalidEmails.join(', '));
                    }


                } else if (id == 'wc_settings_anti_fraud_ips_whitelist' || id == 'wc_settings_anti_fraud_blacklist_ipaddress') {
                    if (ipRegex.test(value)) {
                        $(".ip-error").remove(); // clear error when valid

                        $('<span>').addClass('tag').append(
                            $('<span>').text(value).append('&nbsp;&nbsp;'),
                            $('<a>', {
                                href: '#',
                                title: 'Removing tag',
                                text: 'x'
                            }).click(function () {
                                return $('#' + id).removeTag(escape(value));
                            })
                        ).insertBefore('#' + id + '_addTag');

                        tagslist.push(value);

                    } else {
                        // Only show one error at a time
                        if ($("#wc_settings_anti_fraud_blacklist_ipaddress_tagsinput").next(".ip-error").length === 0) {
                            const errorSpan = $('<span class="ip-error" style="color:red; margin-left:5px;">Invalid IP address. Enter a valid IPv4 (e.g., 127.0.0.0) or IPv6 address (e.g., 2001:0db8:85a3:0000:0000:8a2e:0370:7334).</span>');
                            $("#wc_settings_anti_fraud_blacklist_ipaddress_tagsinput").after(errorSpan);

                            // Auto-remove after 30 seconds
                            setTimeout(function () {
                                errorSpan.fadeOut(3000, function () {
                                    $(this).remove();
                                });
                            }, 30000);
                        }
                    }
                } else {
                    $('<span>').addClass('tag').append(
                        $('<span>').text(value).append('&nbsp;&nbsp;'),
                        $('<a>', {
                            href: '#',
                            title: 'Removing tag',
                            text: 'x'
                        }).click(function () {
                            return $('#' + id).removeTag(escape(value));
                        })
                    ).insertBefore('#' + id + '_addTag');

                    tagslist.push(value);
                }

                $('#' + id + '_tag').val('');
                if (options.focus) {
                    $('#' + id + '_tag').focus();
                } else {
                    $('#' + id + '_tag').blur();
                }

                $.fn.tagsInput.updateTagsField(this, tagslist);

                if (options.callback && tags_callbacks[id] && tags_callbacks[id]['onAddTag']) {
                    var f = tags_callbacks[id]['onAddTag'];
                    f.call(this, value);
                }
                if (tags_callbacks[id] && tags_callbacks[id]['onChange']) {
                    var i = tagslist.length;
                    var f = tags_callbacks[id]['onChange'];
                    f.call(this, $(this), tagslist[i - 1]);
                }
            }

        });

        return false;
    };

    $.fn.removeTag = function (value) {
        value = unescape(value);
        this.each(function () {
            var id = $(this).attr('id');

            var old = $(this).val().split(delimiter[id]);

            $('#' + id + '_tagsinput .tag').remove();
            str = '';
            for (i = 0; i < old.length; i++) {
                if (old[i] != value) {
                    str = str + delimiter[id] + old[i];
                }
            }

            $.fn.tagsInput.importTags(this, str);

            if (tags_callbacks[id] && tags_callbacks[id]['onRemoveTag']) {
                var f = tags_callbacks[id]['onRemoveTag'];
                f.call(this, value);
            }
        });

        $('p.submit > .woocommerce-save-button').prop('disabled', false);

        return false;
    };

    $.fn.tagExist = function (val) {
        var id = $(this).attr('id');
        var tagslist = $(this).val().split(delimiter[id]);
        return (jQuery.inArray(val, tagslist) >= 0); //true when tag exists, false when not
    };

    // clear all existing tags and import new ones from a string
    $.fn.importTags = function (str) {
        var id = $(this).attr('id');
        $('#' + id + '_tagsinput .tag').remove();
        $.fn.tagsInput.importTags(this, str);
    }

    $.fn.tagsInput = function (options) {
        var settings = jQuery.extend({
            interactive: true,
            defaultText: '',
            minChars: 0,
            width: '100%',
            height: '100px',
            autocomplete: {selectFirst: false},
            hide: true,
            delimiter: ',',
            unique: true,
            removeWithBackspace: true,
            placeholderColor: '#666666',
            autosize: true,
            comfortZone: 20,
            inputPadding: 6 * 2
        }, options);

        var uniqueIdCounter = 0;

        this.each(function () {
            // If we have already initialized the field, do not do it again
            if (typeof $(this).attr('data-tagsinput-init') !== 'undefined') {
                return;
            }

            // Mark the field as having been initialized
            $(this).attr('data-tagsinput-init', true);

            if (settings.hide) {
                $(this).hide();
            }
            var id = $(this).attr('id');
            if (!id || delimiter[$(this).attr('id')]) {
                id = $(this).attr('id', 'tags' + new Date().getTime() + (uniqueIdCounter++)).attr('id');
            }

            var data = jQuery.extend({
                pid: id,
                real_input: '#' + id,
                holder: '#' + id + '_tagsinput',
                input_wrapper: '#' + id + '_addTag',
                fake_input: '#' + id + '_tag'
            }, settings);

            delimiter[id] = data.delimiter;

            if (settings.onAddTag || settings.onRemoveTag || settings.onChange) {
                tags_callbacks[id] = new Array();
                tags_callbacks[id]['onAddTag'] = settings.onAddTag;
                tags_callbacks[id]['onRemoveTag'] = settings.onRemoveTag;
                tags_callbacks[id]['onChange'] = settings.onChange;
            }

            var markup = '<div id="' + id + '_tagsinput" class="tagsinput"><div id="' + id + '_addTag">';

            if (settings.interactive) {
                markup = markup + '<input id="' + id + '_tag" value="" data-default="' + settings.defaultText + '" />';
            }

            markup = markup + '</div><div class="tags_clear">Clear All</div></div>';

            $(markup).insertAfter(this);

            $(data.holder).css('width', settings.width);
            $(data.holder).css('min-height', settings.height);
            $(data.holder).css('height', settings.height);

            if ($(data.real_input).val() != '') {
                $.fn.tagsInput.importTags($(data.real_input), $(data.real_input).val());
            }
            if (settings.interactive) {
                $(data.fake_input).val($(data.fake_input).attr('data-default'));
                $(data.fake_input).css('color', settings.placeholderColor);
                $(data.fake_input).resetAutosize(settings);

                $(data.holder).bind('click', data, function (event) {
                    $(event.data.fake_input).focus();
                });

                $(data.fake_input).bind('focus', data, function (event) {
                    if ($(event.data.fake_input).val() == $(event.data.fake_input).attr('data-default')) {
                        $(event.data.fake_input).val('');
                    }
                    $(event.data.fake_input).css('color', '#000000');
                });

                if (settings.autocomplete_url != undefined) {
                    autocomplete_options = {source: settings.autocomplete_url};
                    for (attrname in settings.autocomplete) {
                        autocomplete_options[attrname] = settings.autocomplete[attrname];
                    }

                    if (jQuery.Autocompleter !== undefined) {
                        $(data.fake_input).autocomplete(settings.autocomplete_url, settings.autocomplete);
                        $(data.fake_input).bind('result', data, function (event, data, formatted) {
                            if (data) {
                                $('#' + id).addTag(data[0] + "", {focus: true, unique: (settings.unique)});
                            }
                        });
                    } else if (jQuery.ui.autocomplete !== undefined) {
                        $(data.fake_input).autocomplete(autocomplete_options);
                        $(data.fake_input).bind('autocompleteselect', data, function (event, ui) {
                            $(event.data.real_input).addTag(ui.item.value, {focus: true, unique: (settings.unique)});
                            return false;
                        });
                    }


                } else {
                    // if a user tabs out of the field, create a new tag
                    // this is only available if autocomplete is not used.
                    $(data.fake_input).bind('blur', data, function (event) {
                        var d = $(this).attr('data-default');
                        if ($(event.data.fake_input).val() != '' && $(event.data.fake_input).val() != d) {
                            if ((event.data.minChars <= $(event.data.fake_input).val().length) && (!event.data.maxChars || (event.data.maxChars >= $(event.data.fake_input).val().length)))
                                $(event.data.real_input).addTag($(event.data.fake_input).val(), {focus: true, unique: (settings.unique)});
                        } else {
                            $(event.data.fake_input).val($(event.data.fake_input).attr('data-default'));
                            $(event.data.fake_input).css('color', settings.placeholderColor);
                        }
                        return false;
                    });

                }
                // if user types a default delimiter like comma,semicolon and then create a new tag
                $(data.fake_input).bind('keypress', data, function (event) {
                    if (_checkDelimiter(event)) {
                        event.preventDefault();
                        if ((event.data.minChars <= $(event.data.fake_input).val().length) && (!event.data.maxChars || (event.data.maxChars >= $(event.data.fake_input).val().length)))
                            $(event.data.real_input).addTag($(event.data.fake_input).val(), {focus: true, unique: (settings.unique)});
                        $(event.data.fake_input).resetAutosize(settings);
                        return false;
                    } else if (event.data.autosize) {
                        $(event.data.fake_input).doAutosize(settings);

                    }
                });
                //Delete last tag on backspace
                data.removeWithBackspace && $(data.fake_input).bind('keydown', function (event) {
                    if (event.keyCode == 8 && $(this).val() == '') {
                        event.preventDefault();
                        var last_tag = $(this).closest('.tagsinput').find('.tag:last').text();
                        var id = $(this).attr('id').replace(/_tag$/, '');
                        last_tag = last_tag.replace(/[\s]+x$/, '');
                        $('#' + id).removeTag(escape(last_tag));
                        $(this).trigger('focus');
                    }
                });
                $(data.fake_input).blur();

                //Removes the not_valid class when user changes the value of the fake input
                if (data.unique) {
                    $(data.fake_input).keydown(function (event) {
                        if (event.keyCode == 8 || String.fromCharCode(event.which).match(/\w+|[áéíóúÁÉÍÓÚñÑ,/]+/)) {
                            $(this).removeClass('not_valid');
                        }
                    });
                }
            } // if settings.interactive
        });

        return this;

    };

    $.fn.tagsInput.updateTagsField = function (obj, tagslist) {
        var id = $(obj).attr('id');
        $(obj).val(tagslist.join(delimiter[id]));
    };

    $.fn.tagsInput.importTags = function (obj, val) {
        $(obj).val('');
        var id = $(obj).attr('id');
        var tags = val.split(delimiter[id]);
        for (i = 0; i < tags.length; i++) {
            $(obj).addTag(tags[i], {focus: false, callback: false});
        }
        if (tags_callbacks[id] && tags_callbacks[id]['onChange']) {
            var f = tags_callbacks[id]['onChange'];
            f.call(obj, obj, tags[i]);
        }
    };

    /**
     * check delimiter Array
     * @param event
     * @returns {boolean}
     * @private
     */
    var _checkDelimiter = function (event) {
        var found = false;
        if (event.which == 13) {
            return true;
        }

        if (typeof event.data.delimiter === 'string') {
            if (event.which == event.data.delimiter.charCodeAt(0)) {
                found = true;
            }
        } else {
            $.each(event.data.delimiter, function (index, delimiter) {
                if (event.which == delimiter.charCodeAt(0)) {
                    found = true;
                }
            });
        }

        return found;
    }
    $(function () {
        //$(".wc_af_tags_input, input[data-role=tagsinput], select[multiple][data-role=tagsinput]").tagsinput();
        $(".wc_af_tags_input").tagsInput();

    });
})(jQuery);

jQuery(document).ready(function ($) {
    let bulkEmails = new Set();
    let isImporting = false; // Flag to prevent multiple imports

    // Initialize with existing emails
    $('#wc_af_email_list tbody tr').each(function () {
        let email = $(this).data('email');
        if (email) {
            bulkEmails.add(email);
        }
    });

    // BULK EMAIL PASTE HANDLER
    $('#wc_af_add_bulk_email_button').on('click', function () {
        let pastedEmails = $('#wc_af_bulk_email_paste').val().trim();

        if (pastedEmails === '') {
            alert('Please paste valid email addresses.');
            return;
        }

        let emailsArray = parseBulkEmails(pastedEmails);

        if (emailsArray.length === 0) {
            alert('No valid emails found. Please check the format.');
            return;
        }

        let duplicateEmails = [];
        let newEmails = [];

        emailsArray.forEach(function (email) {
            if (bulkEmails.has(email)) {
                duplicateEmails.push(email);
            } else {
                newEmails.push(email);
            }
        });

        if (duplicateEmails.length > 0) {
            alert(`The following emails are already added:\n${duplicateEmails.join('\n')}`);
        }

        if (newEmails.length > 0) {
            addEmailsToListBulk(newEmails);
            alert(`${newEmails.length} new emails added successfully!`);
        } else if (duplicateEmails.length === emailsArray.length) {
            alert('All pasted emails are already added.');
        }

        $('#wc_af_bulk_email_paste').val('');
    });

    // CSV IMPORT HANDLER
    $('#wc_af_import_csv_button').on('click', function (e) {
        e.preventDefault();

        if (isImporting) {
            alert('Import already in progress. Please wait...');
            return;
        }

        var fileInput = $('#wc_settings_anti_fraud_whitelist_csv')[0].files[0];

        if (!fileInput) {
            alert('Please select a CSV file.');
            return;
        }

        let reader = new FileReader();
        isImporting = true;
        $('#wc_af_import_csv_button').prop('disabled', true).text('Importing...');

        reader.onload = function (e) {
            let csvData = e.target.result;
            console.log('CSV Data:', csvData);

            // Split and validate emails
            let emails = csvData.split(/[\r\n,]+/).filter(function (email) {
                return email.trim() !== '' && validateEmail(email);
            });

            if (emails.length === 0) {
                alert('No valid emails found in the CSV file.');
                resetImportState();
                return;
            }

            const batchSize = 500;
            let start = 0;
            let duplicateEmails = [];

            function processBatch() {
                let batch = emails.slice(start, start + batchSize);
                let newEmails = [];

                batch.forEach(function (email) {
                    if (bulkEmails.has(email)) {
                        duplicateEmails.push(email);
                    } else {
                        newEmails.push(email);
                    }
                });

                if (newEmails.length > 0) {
                    addEmailsToListBulk(newEmails);
                }

                start += batchSize;

                if (start < emails.length) {
                    setTimeout(processBatch, 50);
                } else {
                    let successMessage = `${emails.length - duplicateEmails.length} emails imported successfully!`;

                    if (duplicateEmails.length > 0) {
                        successMessage += `\n\n The following emails were already added:\n${duplicateEmails.join('\n')}`;
                    }

                    alert(successMessage);
                    resetImportState(true, true);
                }
            }

            processBatch();
        };

        reader.onerror = function () {
            alert('Error reading the CSV file. Please try again.');
            resetImportState();
        };

        reader.readAsText(fileInput);
    });

    function resetImportState(clearFile = false, disableButton = false) {
        isImporting = false;

        if (disableButton) {
            $('#wc_af_import_csv_button').prop('disabled', true).text('Import Completed');
        } else {
            $('#wc_af_import_csv_button').prop('disabled', false).text('Import CSV');
        }

        if (clearFile) {
            $('#wc_settings_anti_fraud_whitelist_csv').val('');
        }
    }

    // Optional: Enable button again if a new file is selected
    $('#wc_settings_anti_fraud_whitelist_csv').on('change', function () {
        if (this.files.length > 0) {
            $('#wc_af_import_csv_button').prop('disabled', false).text('Import CSV');
        }
    });

    // REMOVE SINGLE EMAIL
    $('#wc_af_email_list').on('click', '.remove-email', function (e) {
        e.preventDefault();
        let email = $(this).data('email');
        removeEmailFromList(email);
    });

    // BULK DELETE HANDLER
    $('#wc_af_bulk_delete_button').on('click', function () {
        let selectedEmails = [];
        $('.wc_af_email_checkbox:checked').each(function () {
            selectedEmails.push($(this).data('email'));
        });

        if (selectedEmails.length === 0) {
            alert('Please select at least one email to delete.');
            return;
        }

        if (confirm(`Are you sure you want to delete ${selectedEmails.length} selected emails?`)) {
            bulkRemoveEmails(selectedEmails);
        }
    });

    // SELECT/DESELECT ALL
    $('#wc_af_select_all').on('change', function () {
        $('.wc_af_email_checkbox').prop('checked', $(this).prop('checked'));
    });

    // ADD SINGLE EMAIL TO LIST
    function addEmailToList(email) {
        if ($('#no-emails').length > 0) {
            $('#no-emails').remove();
        }

        bulkEmails.add(email);
        let rowHTML = `
      <tr data-email="${email}">
          <td style="text-align: center; padding: 8px;"><input type="checkbox" class="wc_af_email_checkbox" data-email="${email}" /></td>
          <td style="padding: 8px;">${email}</td>
          <td style="text-align: center; padding: 8px;"><a href="#" class="remove-email" data-email="${email}" style="color: #d9534f;">Remove</a></td>
      </tr>`;

        $('#wc_af_email_list tbody').append(rowHTML);
        updateHiddenEmailsField();
    }

    // REMOVE SINGLE EMAIL
    function removeEmailFromList(email) {
        bulkEmails.delete(email);
        $('#wc_af_email_list tbody tr[data-email="' + email + '"]').remove();
        updateHiddenEmailsField();

        if ($('#wc_af_email_list tbody tr').length === 0) {
            $('#wc_af_email_list tbody').html('<tr id="no-emails"><td colspan="3">No emails added yet.</td></tr>');
        }
    }

    function bulkRemoveEmails(emails) {
        let rowsToDelete = emails.map(email => `#wc_af_email_list tbody tr[data-email="${email}"]`);
        $(rowsToDelete.join(',')).remove();

        emails.forEach(email => bulkEmails.delete(email));
        updateHiddenEmailsField();

        if (bulkEmails.size === 0) {
            $('#wc_af_email_list tbody').html('<tr id="no-emails"><td colspan="3">No emails added yet.</td></tr>');
        }
    }

    // PARSE BULK PASTED EMAILS
    function parseBulkEmails(input) {
        let emailRegex = /[^\s,]+@[^\s,]+\.[^\s,]+/g;
        let emails = input.match(emailRegex) || [];
        return emails.map(email => email.trim()).filter(email => validateEmail(email));
    }

    // ADD MULTIPLE EMAILS (BULK or CSV)
    function addEmailsToListBulk(emails) {

        if ($('#no-emails').length > 0) {
            $('#no-emails').remove();
        }
        let emailRows = '';

        emails.forEach(email => {
            if (!bulkEmails.has(email)) {
                bulkEmails.add(email);
                emailRows += `
          <tr data-email="${email}">
            <td style="text-align: center; padding: 8px;"><input type="checkbox" class="wc_af_email_checkbox" data-email="${email}" /></td>
            <td style="padding: 8px;">${email}</td>
            <td style="text-align: center; padding: 8px;"><a href="#" class="remove-email" data-email="${email}" style="color: #d9534f;">Remove</a></td>
          </tr>`;
            }
        });

        if (emailRows !== '') {
            $('#wc_af_email_list tbody').append(emailRows);
            updateHiddenEmailsField();
        }
    }

    let updateEmailsTimeout;

    function updateHiddenEmailsField() {
        clearTimeout(updateEmailsTimeout);
        updateEmailsTimeout = setTimeout(function () {
            $('#wc_settings_anti_fraud_imported_emails').val(Array.from(bulkEmails).join(','));
        }, 300);
    }

    // VALIDATE EMAIL FORMAT
    function validateEmail(email) {
        let emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
});

jQuery(document).ready(function ($) {
    $('#show_csv_format_button').on('click', function () {
        $('#csv_format_modal, #csv_modal_overlay').fadeIn(200);
    });

    $('#close_csv_format_button, #csv_modal_overlay').on('click', function () {
        $('#csv_format_modal, #csv_modal_overlay').fadeOut(200);
    });

    $(document).on('click', '.tagsinput .tags_clear', function () {
        $(this).parent().find('.tag').remove();
        $(this).parent().parent().find('textarea').val('');
        $(this).parents('form').find('p.submit > .woocommerce-save-button').prop('disabled', false);
    });

    // ADDED: Allow only numbers in phone number tag field
    $('#wc_af_blacklisted_phone_numbers_tag').on('input', function () {
        this.value = this.value.replace(/[^\d]/g, ''); // Remove non-digit characters
    });

    // ADDED: Prevent pasting letters/symbols into phone number input
    $('#wc_af_blacklisted_phone_numbers_tag').on('paste', function (e) {
        let pastedData = (e.originalEvent || e).clipboardData.getData('text');
        if (/\D/.test(pastedData)) { // Contains any non-digit
            e.preventDefault();
            alert('Only numbers are allowed!');
        }
    });

    // ADDED: Allow only numbers in phone number tag field
    $('#wc_af_whitelist_phone_numbers_tag').on('input', function () {
        this.value = this.value.replace(/[^\d]/g, ''); // Remove non-digit characters
    });

    // ADDED: Prevent pasting letters/symbols into phone number input
    $('#wc_af_whitelist_phone_numbers_tag').on('paste', function (e) {
        let pastedData = (e.originalEvent || e).clipboardData.getData('text');
        if (/\D/.test(pastedData)) { // Contains any non-digit
            e.preventDefault();
            alert('Only numbers are allowed!');
        }
    });
});
