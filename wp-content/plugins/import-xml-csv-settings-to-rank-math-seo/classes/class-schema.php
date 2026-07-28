<?php


namespace Wpai_RankMath_Add_On;


class Schema {

	function __construct(){

	}

	public function map( $schema_data, $post_id ){
	    // "None" (the Schema Type field stores it as 'off') or an unmapped type means
	    // no schema should be written. Bail before building/saving so we don't create
	    // a bogus "rank_math_schema_Off" entry or emit undefined-key notices.
	    $schema_type = isset($schema_data['rank_math_schema_type']) ? trim($schema_data['rank_math_schema_type']) : '';
	    if ( $schema_type === '' || strtolower($schema_type) === 'off' ) {
	        return;
	    }

	    $schema_data['rank_math_schema_type'] = ucfirst($schema_type);

	    // Determine @type
        switch( $schema_data['rank_math_schema_type'] ){
            case 'Article':
                $type = ($schema_data['rank_math_schema_article_type'] ?? '');
                break;
            case 'Event':
                $type = ($schema_data['rank_math_schema_event_type'] ?? '');
                break;
            case 'Music':
                $type = ($schema_data['rank_math_schema_music_type'] ?? '');
                break;
            default:
                $type = $schema_data['rank_math_schema_type'];
        }

	    $base = array (
                'metadata' =>
                    array (
                        'title' => $schema_data['rank_math_schema_type'],
                        'type' => 'template',
                        'shortcode' => uniqid('s-'),
                        'isPrimary' => '1',
                    ),
                'image' =>
                    array (
                        '@type' => 'ImageObject',
                        'url' => '%post_thumbnail%',
                    ),

                '@type' => $type,

            );

        // WooCommerce Product
        if( $schema_data['rank_math_schema_type'] == 'WooCommerceProduct' ){
            $base['metadata']['title'] = 'WooCommerce Product';
            unset($base['image']);
        }

	    // Article
        if( $schema_data['rank_math_schema_type'] == 'Article') {
            $base = array_merge($base, ['datePublished' => '%date(Y-m-dTH:i:sP)%',
                                        'dateModified' => '%modified(Y-m-dTH:i:sP)%',
                                        'headline' => ($schema_data['rank_math_schema_headline'] ?? ''),
                                        'author' =>
                                            array (
                                                    '@type' => 'Person',
                                                    'name' => '%name%',
                                                ),
                                ]);

        }

        // Book, Course, Event, Product, Recipe, Software
	    if( in_array($schema_data['rank_math_schema_type'], ['Book', 'Course', 'Event', 'Product', 'Recipe', 'SoftwareApplication']) ) {
            $base['metadata']['reviewLocation'] = trim(($schema_data['rank_math_schema_location'] ?? ''));

            if( ($schema_data['rank_math_schema_location'] ?? '') == 'custom' ){
                $base['metadata']['reviewLocationShortcode'] = '[rank_math_rich_snippet]';
            }
        }

	    // Article, Book, Course, Event, Job, Music, Person, Product, Recipe, Restaurant, Service, Software, Video
        if( in_array($schema_data['rank_math_schema_type'], ['Book', 'Course', 'Event', 'Music', 'Person', 'Product', 'Recipe', 'Restaurant', 'Service', 'SoftwareApplication', 'VideoObject'])){
            $base['name'] = ($schema_data['rank_math_schema_headline'] ?? '');
        }

        // Article, Course, Event, Job, Music, Person, Product, Recipe, Restaurant, Service, Software, Video
        if( in_array($schema_data['rank_math_schema_type'], ['Article', 'Course', 'Event', 'JobPosting', 'Music', 'Person', 'Product', 'Recipe', 'Restaurant', 'Service', 'SoftwareApplication', 'VideoObject'])){
            $base['description'] = ($schema_data['rank_math_schema_description'] ?? '');
        }

        // Video
        if( $schema_data['rank_math_schema_type'] == 'VideoObject'){
            $base['metadata']['title'] = 'Video';

            $base = array_merge( $base,
                [
                    'uploadDate' => '%date(Y-m-dTH:i:sP)%',
                    'contentUrl' => ($schema_data['rank_math_schema_video_url'] ?? ''),
                    'embedUrl' => ($schema_data['rank_math_schema_video_embed_url'] ?? ''),
                    'duration' => ($schema_data['rank_math_schema_video_duration'] ?? ''),
                    'thumbnailUrl' => '%post_thumbnail%',
                ]);

            unset($base['image']);

        }

        // Software
        if( $schema_data['rank_math_schema_type'] == 'SoftwareApplication'){
            $base['metadata']['title'] = 'Software';

            $base = array_merge( $base,
                [
                    'operatingSystem' => ($schema_data['rank_math_schema_software_operating_system'] ?? ''),
                    'applicationCategory' => ($schema_data['rank_math_schema_software_application_category'] ?? ''),
                    'offers' =>
                        array (
                            '@type' => 'Offer',
                            'price' => ($schema_data['rank_math_schema_software_price'] ?? ''),
                            'priceCurrency' => ($schema_data['rank_math_schema_software_price_currency'] ?? ''),
                            'availability' => 'InStock',
                        ),
                    'review' =>
                        array (
                            '@type' => 'Review',
                            'datePublished' => '%date(Y-m-dTH:i:sP)%',
                            'dateModified' => '%modified(Y-m-dTH:i:sP)%',
                            'author' =>
                                array (
                                    '@type' => 'Person',
                                    'name' => '%name%',
                                ),
                            'reviewRating' =>
                                array (
                                    '@type' => 'Rating',
                                    'ratingValue' => ($schema_data['rank_math_schema_software_rating'] ?? ''),
                                    'worstRating' => ($schema_data['rank_math_schema_software_rating_min'] ?? ''),
                                    'bestRating' => ($schema_data['rank_math_schema_software_rating_max'] ?? ''),
                                ),
                        ),
                    'image' =>
                        array (
                            '@type' => 'ImageObject',
                            'url' => '%post_thumbnail%',
                        ),
                ]);
        }

        // Service
        if( $schema_data['rank_math_schema_type'] == 'Service' ){
            $base = array_merge( $base,
                [
                    'serviceType' => ($schema_data['rank_math_schema_service_type'] ?? ''),
                    'offers' =>
                        array (
                            '@type' => 'Offer',
                            'price' => ($schema_data['rank_math_schema_service_price'] ?? ''),
                            'priceCurrency' => ($schema_data['rank_math_schema_service_price_currency'] ?? ''),
                            'availability' => 'InStock',
                        ),
                    'image' =>
                        array (
                            '@type' => 'ImageObject',
                            'url' => '%post_thumbnail%',
                        ),
                ]);
        }

        // Restaurant
        if( $schema_data['rank_math_schema_type'] == 'Restaurant' ){
            $base = array_merge( $base, [
                'telephone' => ($schema_data['rank_math_schema_local_phone'] ?? ''),
                'priceRange' => ($schema_data['rank_math_schema_local_price_range'] ?? ''),
                'address' =>
                    array (
                        '@type' => 'PostalAddress',
                        'streetAddress' => ($schema_data['rank_math_schema_local_address_street'] ?? ''),
                        'addressLocality' => ($schema_data['rank_math_schema_local_address_locality'] ?? ''),
                        'addressRegion' => ($schema_data['rank_math_schema_local_address_region'] ?? ''),
                        'postalCode' => ($schema_data['rank_math_schema_local_address_postalcode'] ?? ''),
                        'addressCountry' => ($schema_data['rank_math_schema_local_address_country'] ?? ''),
                    ),
                'geo' =>
                    array (
                        '@type' => 'GeoCoordinates',
                        'latitude' => ($schema_data['rank_math_schema_local_lat'] ?? ''),
                        'longitude' => ($schema_data['rank_math_schema_local_long'] ?? ''),
                    ),
                'openingHoursSpecification' =>
                    array (
                        '@type' => 'OpeningHoursSpecification',
                        'dayOfWeek' =>
                            array (
                               // set below
                            ),
                        'opens' => ($schema_data['rank_math_schema_local_opens'] ?? ''),
                        'closes' => ($schema_data['rank_math_schema_local_closes'] ?? ''),
                    ),
                'servesCuisine' =>
                    array (
                        // set below
                    ),
                'hasMenu' => ($schema_data['rank_math_schema_restaurant_menu'] ?? ''),
                'image' =>
                    array (
                        '@type' => 'ImageObject',
                        'url' => '%post_thumbnail%',
                    ),
            ]);

            // Days of the week
            $base['openingHoursSpecification']['dayOfWeek'] = explode('|', ($schema_data['rank_math_schema_local_opendays'] ?? ''));

            // Cuisine served
            $base['servesCuisine'] = explode('|', ($schema_data['rank_math_schema_restaurant_serves_cuisine'] ?? ''));
        }

        // Recipe
        if( $schema_data['rank_math_schema_type'] == 'Recipe' ){
            $base = array_merge( $base, [
                'datePublished' => '%date(Y-m-dTH:i:sP)%',
                'author' =>
                    array (
                        '@type' => 'Person',
                        'name' => '%name%',
                    ),
                'prepTime' => ($schema_data['rank_math_schema_recipe_preptime'] ?? ''),
                'cookTime' => ($schema_data['rank_math_schema_recipe_cooktime'] ?? ''),
                'totalTime' => ($schema_data['rank_math_schema_recipe_totaltime'] ?? ''),
                'recipeCategory' => ($schema_data['rank_math_schema_recipe_type'] ?? ''),
                'recipeCuisine' => ($schema_data['rank_math_schema_recipe_cuisine'] ?? ''),
                'keywords' => ($schema_data['rank_math_schema_recipe_keywords'] ?? ''),
                'recipeYield' => ($schema_data['rank_math_schema_recipe_yield'] ?? ''),
                'nutrition' =>
                    array (
                        '@type' => 'NutritionInformation',
                        'calories' => ($schema_data['rank_math_schema_recipe_calories'] ?? ''),
                    ),
                'recipeIngredient' =>
                    array (
                        // populated below
                    ),
                'review' =>
                    array (
                        '@type' => 'Review',
                        'datePublished' => '%date(Y-m-dTH:i:sP)%',
                        'dateModified' => '%modified(Y-m-dTH:i:sP)%',
                        'author' =>
                            array (
                                '@type' => 'Person',
                                'name' => '%name%',
                            ),
                        'reviewRating' =>
                            array (
                                '@type' => 'Rating',
                                'ratingValue' => ($schema_data['rank_math_schema_recipe_rating'] ?? ''),
                                'worstRating' => ($schema_data['rank_math_schema_recipe_rating_min'] ?? ''),
                                'bestRating' => ($schema_data['rank_math_schema_recipe_rating_max'] ?? ''),
                            ),
                    ),
                'video' =>
                    array (
                        '@type' => 'VideoObject',
                        'name' => ($schema_data['rank_math_schema_recipe_video_name'] ?? ''),
                        'description' => ($schema_data['rank_math_schema_recipe_video_description'] ?? ''),
                        'embedUrl' => ($schema_data['rank_math_schema_recipe_video'] ?? ''),
                        'contentUrl' => ($schema_data['rank_math_schema_recipe_video_content_url'] ?? ''),
                        'thumbnailUrl' => ($schema_data['rank_math_schema_recipe_video_thumbnail'] ?? ''),
                        'uploadDate' => ($schema_data['rank_math_schema_recipe_video_date'] ?? ''),
                    ),
                'image' =>
                    array (
                        '@type' => 'ImageObject',
                        'url' => '%post_thumbnail%',
                    ),
                'recipeInstructions' =>
                    [
                        // handled below
                    ],
            ]);

            // Build recipe instructions section
            if( trim(($schema_data['rank_math_schema_recipe_instruction_type'] ?? '')) == 'SingleField'){
                $base['recipeInstructions'] = ($schema_data['rank_math_schema_recipe_single_instructions'] ?? '');
            }else{
                $instruction_names = explode("|", ($schema_data['rank_math_schema_recipe_instructions_name'] ?? ''));
                $instruction_steps = explode("|", ($schema_data['rank_math_schema_recipe_instructions_text'] ?? ''));

                foreach( $instruction_names as $key => $name ) {

                    $step_list = [];

                    if( isset( $instruction_steps[$key] ) ) {
                        $steps = explode('^', $instruction_steps[$key]);

                        foreach ($steps as $step) {
                            $step_list[] =
                                [
                                    '@type' => 'HowtoStep',
                                    'text' => $step,
                                ];
                        }
                    }

                    $base['recipeInstructions'][] =
                        [
                            '@type' => 'HowToSection',
                            'name' => $name,
                            'itemListElement' => $step_list,
                        ];

                }
            }

            // Recipe ingredients
            $base['recipeIngredient'] = explode('|', ($schema_data['rank_math_schema_recipe_ingredients'] ?? ''));
        }

        // Product
        if( $schema_data['rank_math_schema_type'] == 'Product' ){
            $base = array_merge($base, [
                'sku' => ($schema_data['rank_math_schema_product_sku'] ?? ''),
                'brand' =>
                    array (
                        '@type' => 'Brand',
                        'name' => ($schema_data['rank_math_schema_product_brand'] ?? ''),
                    ),
                'offers' =>
                    array (
                        '@type' => 'Offer',
                        'url' => '%url%',
                        'price' => ($schema_data['rank_math_schema_product_price'] ?? ''),
                        'priceCurrency' => ($schema_data['rank_math_schema_product_currency'] ?? ''),
                        'availability' => trim(($schema_data['rank_math_schema_product_instock'] ?? '')),
                        'priceValidUntil' => $this->format_date(($schema_data['rank_math_schema_product_price_valid'] ?? ''), 'Y-m-d'),
                    ),
                'review' =>
                    array (
                        '@type' => 'Review',
                        'datePublished' => '%date(Y-m-dTH:i:sP)%',
                        'dateModified' => '%modified(Y-m-dTH:i:sP)%',
                        'author' =>
                            array (
                                '@type' => 'Person',
                                'name' => '%name%',
                            ),
                        'reviewRating' =>
                            array (
                                '@type' => 'Rating',
                                'ratingValue' => ($schema_data['rank_math_schema_product_rating'] ?? ''),
                                'worstRating' => ($schema_data['rank_math_schema_product_rating_min'] ?? ''),
                                'bestRating' => ($schema_data['rank_math_schema_product_rating_max'] ?? ''),
                            ),
                    ),
            ]);
        }

        // Person
        if( $schema_data['rank_math_schema_type'] == 'Person' ){
            $base = array_merge( $base, [
                'email' => ($schema_data['rank_math_schema_person_email'] ?? ''),
                'address' =>
                    array (
                        '@type' => 'PostalAddress',
                        'streetAddress' => ($schema_data['rank_math_schema_person_address_street'] ?? ''),
                        'addressLocality' => ($schema_data['rank_math_schema_person_address_locality'] ?? ''),
                        'addressRegion' => ($schema_data['rank_math_schema_person_address_region'] ?? ''),
                        'postalCode' => ($schema_data['rank_math_schema_person_address_postalcode'] ?? ''),
                        'addressCountry' => ($schema_data['rank_math_schema_person_address_country'] ?? ''),
                    ),
                'gender' => ($schema_data['rank_math_schema_person_gender'] ?? ''),
                'jobTitle' => ($schema_data['rank_math_schema_person_job_title'] ?? ''),
            ]);

            unset($base['image']);
        }

        // Job
        if( $schema_data['rank_math_schema_type'] == 'JobPosting'){
            $base['metadata']['title'] = 'Job Posting';
            $base['metadata']['reviewLocationShortcode'] = '[rank_math_rich_snippet]';
            $base['metadata']['unpublish'] = ($schema_data['rank_math_schema_jobposting_unpublish'] ?? '');
            $base['title'] = ($schema_data['rank_math_schema_headline'] ?? '');

            $base = array_merge($base, [
                'baseSalary' =>
                    array (
                        '@type' => 'MonetaryAmount',
                        'currency' => ($schema_data['rank_math_schema_jobposting_currency'] ?? ''),
                        'value' =>
                            array (
                                '@type' => 'QuantitativeValue',
                                'value' => ($schema_data['rank_math_schema_jobposting_salary'] ?? ''),
                                'unitText' => ($schema_data['rank_math_schema_jobposting_payroll'] ?? ''),
                            ),
                    ),
                'datePosted' => $this->format_date(($schema_data['rank_math_schema_jobposting_startdate'] ?? ''), 'Y-m-d'),
                'validThrough' => $this->format_date(($schema_data['rank_math_schema_jobposting_expirydate'] ?? ''), 'Y-m-d'),
                'employmentType' =>
                    array (
                        // set separately below
                    ),
                'hiringOrganization' =>
                    array (
                        '@type' => 'Organization',
                        'name' => ($schema_data['rank_math_schema_jobposting_organization'] ?? ''),
                        'sameAs' => ($schema_data['rank_math_schema_jobposting_url'] ?? ''),
                        'logo' => ($schema_data['rank_math_schema_jobposting_logo'] ?? ''),
                    ),
                'id' => ($schema_data['rank_math_schema_jobposting_id'] ?? ''),
                'jobLocation' =>
                    array (
                        '@type' => 'Place',
                        'address' =>
                            array (
                                '@type' => 'PostalAddress',
                                'streetAddress' => ($schema_data['rank_math_schema_jobposting_address_street'] ?? ''),
                                'addressLocality' => ($schema_data['rank_math_schema_jobposting_address_locality'] ?? ''),
                                'addressRegion' => ($schema_data['rank_math_schema_jobposting_address_region'] ?? ''),
                                'postalCode' => ($schema_data['rank_math_schema_jobposting_address_postalcode'] ?? ''),
                                'addressCountry' => ($schema_data['rank_math_schema_jobposting_address_country'] ?? ''),
                            ),
                    ),
            ]);

            $base['employmentType'] = array_map('trim',explode("|", ($schema_data['rank_math_schema_jobposting_employment_type'] ?? '')));
        }

        // Event
        if( $schema_data['rank_math_schema_type'] == 'Event'){
            $base = array_merge($base, ['eventStatus' => ($schema_data['rank_math_schema_event_status'] ?? ''),
                'eventAttendanceMode' => ($schema_data['rank_math_schema_event_attendance_mode'] ?? ''),
                'location' =>
                    [
                    0 =>
                        [
                         'url' => ($schema_data['rank_math_schema_online_event_url'] ?? ''),
                         '@type' => 'VirtualLocation',
                        ],

                    1 =>
                        array (
                         '@type' => 'Place',
                         'name' => ($schema_data['rank_math_schema_event_venue'] ?? ''),
                         'url' => ($schema_data['rank_math_schema_event_venue_url'] ?? ''),
                         'address' =>
                            array (
                                '@type' => 'PostalAddress',
                                'streetAddress' => ($schema_data['rank_math_schema_event_address_street'] ?? ''),
                                'addressLocality' => ($schema_data['rank_math_schema_event_address_locality'] ?? ''),
                                'addressRegion' => ($schema_data['rank_math_schema_event_address_region'] ?? ''),
                                'postalCode' => ($schema_data['rank_math_schema_event_address_postalcode'] ?? ''),
                                'addressCountry' => ($schema_data['rank_math_schema_event_address_country'] ?? ''),
                            ),
                        )
                    ],
                'performer' =>
                    array (
                        '@type' => ($schema_data['rank_math_schema_event_performer_type'] ?? ''),
                        'name' => ($schema_data['rank_math_schema_event_performer'] ?? ''),
                        'sameAs' => ($schema_data['rank_math_schema_event_performer_url'] ?? ''),
                    ),
                'startDate' => $this->format_date(($schema_data['rank_math_schema_event_startdate'] ?? ''), 'Y-m-d\TH:i:s'),
                'endDate' => $this->format_date(($schema_data['rank_math_schema_event_enddate'] ?? ''), 'Y-m-d\TH:i:s'),
                'offers' =>
                    array (
                        '@type' => 'Offer',
                        'name' => 'General Admission',
                        'category' => 'primary',
                        'url' => ($schema_data['rank_math_schema_event_ticketurl'] ?? ''),
                        'price' => ($schema_data['rank_math_schema_event_price'] ?? ''),
                        'priceCurrency' => ($schema_data['rank_math_schema_event_currency'] ?? ''),
                        'availability' => ($schema_data['rank_math_schema_event_availability'] ?? ''),
                        'validFrom' => $this->format_date(($schema_data['rank_math_schema_event_availability_starts'] ?? ''), 'Y-m-d'),
                        'inventoryLevel' => ($schema_data['rank_math_schema_event_inventory'] ?? ''),
                    ),
                'review' =>
                    array (
                        '@type' => 'Review',
                        'datePublished' => '%date(Y-m-dTH:i:sP)%',
                        'dateModified' => '%modified(Y-m-dTH:i:sP)%',
                        'author' =>
                            array (
                                '@type' => 'Person',
                                'name' => '%name%',
                            ),
                        'reviewRating' =>
                            array (
                                '@type' => 'Rating',
                                'ratingValue' => ($schema_data['rank_math_schema_event_rating'] ?? ''),
                                'worstRating' => ($schema_data['rank_math_schema_event_rating_min'] ?? ''),
                                'bestRating' => ($schema_data['rank_math_schema_event_rating_max'] ?? ''),
                            ),
                    ),

                ]);

        }

        // Course
        if( $schema_data['rank_math_schema_type'] == 'Course') {
            $base['provider'] = array (
                '@type' => trim(($schema_data['rank_math_schema_course_provider_type'] ?? '')),
                'name' => ($schema_data['rank_math_schema_course_provider'] ?? ''),
                'sameAs' => ($schema_data['rank_math_schema_course_provider_url'] ?? ''),
            );

            $base['review'] =
                array (
                    '@type' => 'Review',
                    'datePublished' => '%date(Y-m-d\\TH:i:sP)%',
                    'dateModified' => '%modified(Y-m-d\\TH:i:sP)%',
                    'author' =>
                        array (
                            '@type' => 'Person',
                            'name' => '%name%',
                        ),
                    'reviewRating' =>
                        array (
                            '@type' => 'Rating',
                            'ratingValue' => ($schema_data['rank_math_schema_course_rating'] ?? ''),
                            'worstRating' => ($schema_data['rank_math_schema_course_rating_min'] ?? ''),
                            'bestRating' => ($schema_data['rank_math_schema_course_rating_max'] ?? ''),
                        ),
                );
        }

        // Book, Music
        if( in_array($schema_data['rank_math_schema_type'], ['Book', 'Music'])){
            $base['url'] = ($schema_data['rank_math_schema_url'] ?? '');
        }

        // Music, Person
        if( in_array($schema_data['rank_math_schema_type'], ['Music', 'Person']) ){
            $base['metadata']['reviewLocationShortcode'] = '[rank_math_rich_snippet]';
        }

        // Book
        if( $schema_data['rank_math_schema_type'] == 'Book'){

            $base['author']['name'] = ($schema_data['rank_math_schema_author'] ?? '');
            $base['review'] =
                array (
                    '@type' => 'Review',
                    'datePublished' => '%date(Y-m-d\\TH:i:sP)%',
                    'dateModified' => '%modified(Y-m-d\\TH:i:sP)%',
                    'author' =>
                        array (
                            '@type' => 'Person',
                            'name' => '%name%',
                        ),
                    'reviewRating' =>
                        array (
                            '@type' => 'Rating',
                            'ratingValue' => ($schema_data['rank_math_schema_book_rating'] ?? ''),
                            'worstRating' => ($schema_data['rank_math_schema_book_rating_min'] ?? ''),
                            'bestRating' => ($schema_data['rank_math_schema_book_rating_max'] ?? ''),
                        ),
                );

            // Editions
            $ed_titles = explode("|", ($schema_data['rank_math_schema_edition_title'] ?? ''));
            $ed_editions = explode("|", ($schema_data['rank_math_schema_edition_edition'] ?? ''));
            $ed_isbns = explode("|", ($schema_data['rank_math_schema_edition_isbn'] ?? ''));
            $ed_urls = explode("|", ($schema_data['rank_math_schema_edition_url'] ?? ''));
            $ed_authors = explode("|", ($schema_data['rank_math_schema_edition_author'] ?? ''));
            $ed_dates = explode("|", ($schema_data['rank_math_schema_edition_date'] ?? ''));
            $ed_formats = explode("|", ($schema_data['rank_math_schema_edition_format'] ?? ''));

            foreach( $ed_titles as $key => $title ){
                $base['hasPart'][] =  array (
                    '@type' => 'Book',
                    'name' => $title,
                    'bookEdition' => isset($ed_editions[$key]) ? $ed_editions[$key] : '',
                    'isbn' => isset($ed_isbns[$key]) ? $ed_isbns[$key] : '',
                    'url' => isset($ed_urls[$key]) ? $ed_urls[$key] : '',
                    'author' =>
                        array (
                            '@type' => 'Person',
                            'name' => isset($ed_authors[$key]) ? $ed_authors[$key] : '',
                        ),
                    'bookFormat' => isset($ed_formats[$key]) ? trim($ed_formats[$key]) : '',
                    'datePublished' => $this->format_date($ed_dates[$key] ?? '', 'Y-m-d'),
                );
            }

        }


        $base = $this->clean($base);
        $this->save($base, $post_id, $type);
    }

    /**
     * Format a date for schema output, returning '' (not a 1970-01-01 epoch date)
     * for empty or unparseable values so clean() can drop the field.
     */
    private function format_date($value, $format){
        $value = is_string($value) ? trim($value) : $value;
        if ( empty($value) ) {
            return '';
        }
        $ts = strtotime($value);
        return $ts ? date($format, $ts) : '';
    }

    private function save($base, $post_id, $type){

	    $meta_key = 'rank_math_schema_'. $type;

	    // Remove old schema meta values if WP All Import didn't already.
        $this->delete_old_meta($post_id);

        update_post_meta($post_id, $meta_key, $base);
    }

    /**
     * Remove any non-zero empty array elements so that Rank Math will use default values for those fields.
     * @param $base
     * @return mixed
     */
    private function clean($base){
	    foreach( $base as $key => $val ){
	        if( is_array( $val ) ){
	            // process nested arrays with recursion
	            $base[$key] = $this->clean($val);
            }elseif( $val != 0 && empty($val) ){
	            unset($base[$key]);
            }
        }

	    return $base;
    }

    private function delete_old_meta( $post_id ){

        if( empty($post_id )){
            error_log('No post ID provided, skipping old schema delete.');
            return false;
        }

        global $wpdb;

        $results = $wpdb->query(
            "
            DELETE
            FROM {$wpdb->prefix}postmeta
            WHERE meta_key like 'rank_math_schema_%' AND post_id = $post_id
            ",
            ARRAY_N
        );

        return $results;

    }

   }