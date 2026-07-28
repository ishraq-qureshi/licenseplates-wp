window.onload = function()
{
    if (window.jQuery)
    {
        jQuery(function ($) {

            // set custom property for tipsy tooltips
            $('.wpallimport-help').each(function(index, element){
                let title = ($(element).prop('title')) ? $(element).prop('title') : $(element).prop('original-title');
                $(element).attr('data', title);
            });

            // all fields used in the Schema section
            let targetFields = {
                Article: {
                    fields: ['rank_math_schema_headline', 'rank_math_schema_description', 'rank_math_schema_article_type'],
                    fieldsNeedParentParent: ['rank_math_schema_article_type_article']
                },
                Book: {
                    fields: ['rank_math_schema_location', 'rank_math_schema_headline', 'rank_math_schema_url', 'rank_math_schema_author', 'rank_math_schema_book_rating', 'rank_math_schema_book_rating_min', 'rank_math_schema_book_rating_max'],
                    fieldsNeedParentParent: ['rank_math_schema_location_top',]
                },
                Course: {
                    fields: ['rank_math_schema_course_provider_type', 'rank_math_schema_location', 'rank_math_schema_headline', 'rank_math_schema_description', 'rank_math_schema_course_provider', 'rank_math_schema_course_provider_url', 'rank_math_schema_course_rating', 'rank_math_schema_course_rating_min', 'rank_math_schema_course_rating_max'],
                    fieldsNeedParentParent: ['rank_math_schema_location_top', 'rank_math_schema_course_provider_type_person']
                },
                Event: {
                    fields: ['rank_math_schema_event_availability', 'rank_math_schema_event_type', 'rank_math_schema_event_attendance_mode', 'rank_math_schema_event_performer_type', 'rank_math_schema_description', 'rank_math_schema_location', 'rank_math_schema_headline', 'rank_math_schema_online_event_url', 'rank_math_schema_event_venue', 'rank_math_schema_event_venue_url', 'rank_math_schema_event_address_street', 'rank_math_schema_event_address_locality', 'rank_math_schema_event_address_region', 'rank_math_schema_event_address_postalcode', 'rank_math_schema_event_address_country', 'rank_math_schema_event_performer', 'rank_math_schema_event_performer_url', 'rank_math_schema_event_startdate', 'rank_math_schema_event_enddate', 'rank_math_schema_event_ticketurl', 'rank_math_schema_event_price', 'rank_math_schema_event_currency', 'rank_math_schema_event_availability_starts', 'rank_math_schema_event_inventory', 'rank_math_schema_event_rating', 'rank_math_schema_event_rating_min', 'rank_math_schema_event_rating_max', 'rank_math_schema_event_status',],
                    fieldsNeedParentParent: ['rank_math_schema_location_top', 'rank_math_schema_event_type_event', 'rank_math_schema_event_attendance_mode_offlineeventattendancemode', 'rank_math_schema_event_performer_type_person', 'rank_math_schema_event_availability_none', 'rank_math_schema_event_status_eventscheduled']
                },
                JobPosting: {
                    fields: ['rank_math_schema_headline', 'rank_math_schema_description','rank_math_schema_jobposting_salary', 'rank_math_schema_jobposting_currency', 'rank_math_schema_jobposting_payroll', 'rank_math_schema_jobposting_startdate', 'rank_math_schema_jobposting_expirydate', 'rank_math_schema_jobposting_unpublish', 'rank_math_schema_jobposting_employment_type', 'rank_math_schema_jobposting_organization', 'rank_math_schema_jobposting_id', 'rank_math_schema_jobposting_url', 'rank_math_schema_jobposting_logo', 'rank_math_schema_jobposting_address_street', 'rank_math_schema_jobposting_address_locality', 'rank_math_schema_jobposting_address_region', 'rank_math_schema_jobposting_address_postalcode', 'rank_math_schema_jobposting_address_country'],
                    fieldsNeedParentParent: ['rank_math_schema_jobposting_payroll_month', 'rank_math_schema_jobposting_unpublish_on', 'rank_math_schema_jobposting_employment_type_full_time',]
                },
                Music: {
                    fields: ['rank_math_schema_headline', 'rank_math_schema_description', 'rank_math_schema_url', 'rank_math_schema_music_type'],
                    fieldsNeedParentParent: ['rank_math_schema_music_type_musicgroup']
                },
                Person: {
                    fields: ['rank_math_schema_headline', 'rank_math_schema_description', 'rank_math_schema_person_email', 'rank_math_schema_person_address_street', 'rank_math_schema_person_address_locality', 'rank_math_schema_person_address_region', 'rank_math_schema_person_address_postalcode', 'rank_math_schema_person_address_country', 'rank_math_schema_person_gender', 'rank_math_schema_person_job_title'],
                    fieldsNeedParentParent: []
                },
                Product: {
                    fields: ['rank_math_schema_location', 'rank_math_schema_headline', 'rank_math_schema_description', 'rank_math_schema_product_instock', 'rank_math_schema_product_sku', 'rank_math_schema_product_brand', 'rank_math_schema_product_currency', 'rank_math_schema_product_price', 'rank_math_schema_product_price_valid', 'rank_math_schema_product_rating', 'rank_math_schema_product_rating_min', 'rank_math_schema_product_rating_max'],
                    fieldsNeedParentParent: ['rank_math_schema_location_top', 'rank_math_schema_product_instock_instock']
                },
                Recipe: {
                    fields: ['rank_math_schema_location', 'rank_math_schema_headline', 'rank_math_schema_description', 'rank_math_schema_recipe_ingredients', 'rank_math_schema_recipe_instruction_type', 'rank_math_schema_recipe_instructions_name', 'rank_math_schema_recipe_instructions_text', 'rank_math_schema_recipe_video_content_url', 'rank_math_schema_recipe_video_thumbnail', 'rank_math_schema_recipe_video_name', 'rank_math_schema_recipe_video_date', 'rank_math_schema_recipe_video_description', 'rank_math_schema_recipe_totaltime', 'rank_math_schema_recipe_rating', 'rank_math_schema_recipe_rating_min', 'rank_math_schema_recipe_rating_max', 'rank_math_schema_recipe_video', 'rank_math_schema_recipe_keywords', 'rank_math_schema_recipe_yield', 'rank_math_schema_recipe_calories', 'rank_math_schema_recipe_preptime', 'rank_math_schema_recipe_cooktime', 'rank_math_schema_recipe_type', 'rank_math_schema_recipe_cuisine', 'rank_math_schema_recipe_single_instructions','rank_math_schema_recipe_instruction_type'],
                    fieldsNeedParentParent: ['rank_math_schema_location_top', 'rank_math_schema_recipe_instruction_type_singlefield']
                },
                Restaurant: {
                    fields: ['rank_math_schema_headline', 'rank_math_schema_description', 'rank_math_schema_local_address_street', 'rank_math_schema_local_address_locality', 'rank_math_schema_local_address_region', 'rank_math_schema_local_address_postalcode', 'rank_math_schema_local_address_country', 'rank_math_schema_local_lat', 'rank_math_schema_local_long','rank_math_schema_local_phone', 'rank_math_schema_local_price_range', 'rank_math_schema_local_opens', 'rank_math_schema_local_closes', 'rank_math_schema_restaurant_serves_cuisine', 'rank_math_schema_restaurant_menu', 'rank_math_schema_local_opendays'],
                    fieldsNeedParentParent: []
                },
                Service: {
                    fields: ['rank_math_schema_headline', 'rank_math_schema_description', 'rank_math_schema_service_type', 'rank_math_schema_service_price', 'rank_math_schema_service_price_currency'],
                    fieldsNeedParentParent: []
                },
                SoftwareApplication: {
                    fields: ['rank_math_schema_location', 'rank_math_schema_headline', 'rank_math_schema_description', 'rank_math_schema_software_price', 'rank_math_schema_software_price_currency', 'rank_math_schema_software_operating_system', 'rank_math_schema_software_application_category', 'rank_math_schema_software_rating', 'rank_math_schema_software_rating_min', 'rank_math_schema_software_rating_max'],
                    fieldsNeedParentParent: ['rank_math_schema_location_top',]
                },
                VideoObject: {
                    fields: ['rank_math_schema_headline', 'rank_math_schema_description', 'rank_math_schema_video_url', 'rank_math_schema_video_embed_url', 'rank_math_schema_video_duration'],
                    fieldsNeedParentParent: []
                },
                WooCommerceProduct: {
                    fields: ['rank_math_schema_woocommerce_product'],
                    fieldsNeedParentParent: ['rank_math_schema_woocommerce_product_xpath']
                },
            }

            // initial value when the page loads
            let initialValue = $("input[name='rank_math_seo_addon[rank_math_schema_type]']:checked").val();

            // hide all of the fields unless the initial value is 'xpath'
            if (initialValue !== 'xpath') {
                hideAllFields(targetFields);

                // hide Book editions when not needed
                if (initialValue !== 'Book') {
                    $("h3:contains('Editions')").parent().parent().hide();
                }

                if (initialValue && initialValue !== 'off') {
                    showAllFields([targetFields[initialValue]]);
                }
            }

            // show only the fields appropriate for the initial value


            // display fields based on changes to radio option selected
            $("input[name='rank_math_seo_addon[rank_math_schema_type]']").on("click", function () {

                // show all fields for 'xpath'
                if ($(this).val() === 'xpath') {
                    showAllFields(targetFields);
                } else {
                    // hide the fields first to ensure we only show those needed
                    hideAllFields(targetFields);

                    // hide Book editions when not needed
                    if ($(this).val() !== 'Book') {
                        $("h3:contains('Editions')").parent().parent().hide();
                    } else {
                        $("h3:contains('Editions')").parent().parent().show();
                    }

                    if ($(this).val() && $(this).val() !== 'off') {
                        // pass the object with the fields as an array value
                        showAllFields([targetFields[$(this).val()]]);


                    }
                }

                // Process Recipe Instruction Type Fields
                // Check Schema Type.
                if ($(this).val() === 'xpath' || $(this).val() === 'Recipe') {

                    // check the recipe instruction type currently selected
                    let instruction_type = $("input[name='rank_math_seo_addon[rank_math_schema_recipe_instruction_type]']:checked");

                    // show all fields for 'xpath'
                    if (instruction_type.val() === 'xpath') {
                        showAllFields(recipe_target_fields);
                    } else {
                        // hide the fields first to ensure we only show those needed
                        hideAllFields(recipe_target_fields);

                        if (instruction_type.val() && instruction_type.val() !== 'off') {
                            // pass the object with the fields as an array value
                            showAllFields([recipe_target_fields[instruction_type.val()]]);

                        }

                    }

                } else {
                    // hide all recipe target fields if recipe or xpath isn't set as schema type
                    hideAllFields(recipe_target_fields);
                }
            });

            // Recipe Instruction Type
            let recipe_target_fields = {
                'SingleField': {
                    'fields': ['rank_math_schema_recipe_single_instructions'], 'fieldsNeedParentParent': []
                },
                /*'HowToStep': {
                    'fields': ['rank_math_schema_recipe_instruction_name','rank_math_schema_recipe_single_instructions'], 'fieldsNeedParentParent': []
                },*/
                'HowToStep': {
                    'fields': ['rank_math_schema_recipe_instructions_name','rank_math_schema_recipe_instructions_text',], 'fieldsNeedParentParent': []
                }
            }

            // bind to recipe instruction type initial value
            let recipe_card_initial = $("input[name='rank_math_seo_addon[rank_math_schema_recipe_instruction_type]']:checked").val();

            // hide all of the fields unless the initial value is 'xpath'
            if (recipe_card_initial !== 'xpath') {
                hideAllFields(recipe_target_fields);

                // Only show fields if Schema Type is set to 'xpath' or 'recipe'.
                if (recipe_card_initial && (initialValue === 'xpath' || initialValue === 'Recipe')) {
                    showAllFields([recipe_target_fields[recipe_card_initial]]);
                }
            }

            // display fields based on changes to radio option selected
            $("input[name='rank_math_seo_addon[rank_math_schema_recipe_instruction_type]']").on("click", function () {

                // show all fields for 'xpath'
                if ($(this).val() === 'xpath') {
                    showAllFields(recipe_target_fields);
                } else {
                    // hide the fields first to ensure we only show those needed
                    hideAllFields(recipe_target_fields);

                    if ($(this).val()) {
                        // pass the object with the fields as an array value
                        showAllFields([recipe_target_fields[$(this).val()]]);

                    }
                }
            });

            // Twitter Card Type
            let tw_target_fields = {
                'summary_large_image': {
                    'fields': ['rank_math_twitter_image'], 'fieldsNeedParentParent': ['rank_math_twitter_image_yes']
                },
                'summary_card': {
                    'fields': ['rank_math_twitter_image'], 'fieldsNeedParentParent': ['rank_math_twitter_image_yes']
                },
                'app': {
                    'fields': ['rank_math_twitter_app_description','rank_math_twitter_app_iphone_name','rank_math_twitter_app_iphone_id','rank_math_twitter_app_iphone_url','rank_math_twitter_app_ipad_name','rank_math_twitter_app_ipad_id','rank_math_twitter_app_ipad_url','rank_math_twitter_app_googleplay_name','rank_math_twitter_app_googleplay_id','rank_math_twitter_app_googleplay_url','rank_math_twitter_app_country'], 'fieldsNeedParentParent': []
                },
                'player': {
                    'fields': ['rank_math_twitter_player_url', 'rank_math_twitter_player_size', 'rank_math_twitter_player_stream','rank_math_twitter_player_stream_ctype'], 'fieldsNeedParentParent': []
                }
            }

            // bind to Twitter card type initial value
            let tw_card_initial = $("input[name='rank_math_seo_addon[rank_math_twitter_card_type]']:checked").val();

            // hide all of the fields unless the initial value is 'xpath'
            if (tw_card_initial !== 'xpath') {
                hideAllFields(tw_target_fields);

                if (tw_card_initial) {
                    showAllFields([tw_target_fields[tw_card_initial]]);
                }
            }

            // display fields based on changes to radio option selected
            $("input[name='rank_math_seo_addon[rank_math_twitter_card_type]']").on("click", function () {

                // show all fields for 'xpath'
                if ($(this).val() === 'xpath') {
                    showAllFields(tw_target_fields);
                } else {
                    // hide the fields first to ensure we only show those needed
                    hideAllFields(tw_target_fields);

                    if ($(this).val()) {
                        // pass the object with the fields as an array value
                        showAllFields([tw_target_fields[$(this).val()]]);

                    }
                }
            });

            function hideAllFields(targetFields){

                jQuery.each(targetFields, function(index, item) {
                    hideFields(item['fields']);
                    hideFields(item['fieldsNeedParentParent'], 'parentparent');
                });
            }

            function hideFields(fields, type = ''){
                if(type === 'parentparent'){
                    jQuery.each(fields, function(index, item) {
                        $('label[for=\'' + 'rank_math_seo_addon' + item +'\'],#rank_math_seo_addon' + item).parent().parent().hide();

                    });

                }else{
                    jQuery.each(fields, function(index, item) {
                        // Match the label whether WP All Import renders for="rank_math_seo_addon<field>"
                        // (Pro) or the bracketed for="rank_math_seo_addon[<field>]" (Free).
                        $('label[for=\'' + 'rank_math_seo_addon' + item +'\'],label[for=\'rank_math_seo_addon[' + item + ']\'],#rank_math_seo_addon' + item).hide();
                        // hide tooltips
                        $('a[data*=\''+ item +'\'], a[original-title*=\''+ item +'\'], a[title*=\''+ item +'\']').hide();
                    });
                }
            }

            function showAllFields(targetFields){

                jQuery.each(targetFields, function(index, item) {
                    showFields(item['fields']);
                    showFields(item['fieldsNeedParentParent'], 'parentparent');
                });
            }

            function showFields(fields, type = ''){
                if(type === 'parentparent'){
                    jQuery.each(fields, function(index, item) {

                        $('label[for=\'' + 'rank_math_seo_addon' + item +'\'],#rank_math_seo_addon' + item).parent().parent().show();

                        if(item === 'rank_math_schema_woocommerce_product_xpath') {
                            $('label[for=rank_math_seo_addonrank_math_schema_woocommerce_product_xpath],#rank_math_seo_addonrank_math_schema_woocommerce_product_xpath').hide();
                        }
                    });

                }else{
                    jQuery.each(fields, function(index, item) {
                        $('label[for=\'' + 'rank_math_seo_addon' + item +'\'],label[for=\'rank_math_seo_addon[' + item + ']\'],#rank_math_seo_addon' + item).show();
                        // show tooltips
                        $('a[data*=\''+ item +'\'], a[original-title*=\''+ item +'\'], a[title*=\''+ item +'\']').show();
                    });
                }
            }


        });

    }
}

