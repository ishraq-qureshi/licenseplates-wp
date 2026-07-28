<?php



if( !class_exists('WPAI_RankMath_SEO_Field_Factory')) {
    class WPAI_RankMath_SEO_Field_Factory
    {
        protected $add_on;
        protected $post_type;
        protected $taxonomy_type;

        public function __construct( RapidAddon $addon_obj)
        {
            $this->add_on = $addon_obj;

            $helpers = new WPAI_RankMath_SEO_Helpers();
            $this->post_type = $helpers->get_post_type();
            $this->taxonomy_type = $helpers->get_taxonomy_type();

        }

        public function generate()
        {

            $this->add_on->add_field( 'rank_math_focus_keyword', 'Focus Keywords', 'text', null, 'Insert keywords you want to rank for, separated by commas.' );
            $this->add_on->add_field( 'rank_math_title', 'SEO Title', 'text' );
            $this->add_on->add_field( 'rank_math_description', 'Meta Description', 'text' );
            // Don't display pillar content field for taxonomies
            if(!in_array($this->post_type, ['taxonomies', 'import_users', 'shop_customer'])) {

                $this->add_on->add_field(
                    'rank_math_pillar_content',
                    'This post is Pillar Content',
                    'radio',
                    array(
                        '' => 'No',
                        'on' => 'Yes',
                    ),
                    'Select one or more Pillar Content posts for each post tag or category to show them in the Link Suggestions meta box. <br/><br/>Possible \'Set with XPath\' values: \'on\'. <br/>Leave blank for \'No\'.'
                );
            }

            // Rank Math supports a single primary taxonomy per post type, configured
            // under Titles & Meta (titles.pt_{post_type}_primary_taxonomy) and stored
            // as rank_math_primary_{taxonomy}. Offer one field for exactly that
            // taxonomy so the import matches what Rank Math actually honors.
            if( $this->post_type && function_exists('post_type_exists') && post_type_exists($this->post_type) && !in_array($this->post_type, ['taxonomies', 'import_users', 'shop_customer']) ){
                $primary_tax = $this->get_primary_taxonomy();
                if( $primary_tax && taxonomy_exists($primary_tax) ){
                    $tax = get_taxonomy($primary_tax);
                    $singular = !empty($tax->labels->singular_name) ? $tax->labels->singular_name : $tax->label;
                    $this->add_on->add_field(
                        'rank_math_primary_' . $primary_tax,
                        'Primary ' . $singular,
                        'text',
                        null,
                        'Provide the name, slug, or ID of a single ' . $singular . ' to set it as the primary term. The term must also be assigned to the imported item (in the "Taxonomies, Categories & Tags" section), since Rank Math only uses the primary term if it is one of the item\'s terms. Uses the Primary Taxonomy configured for this post type in Rank Math.'
                    );
                }
            }

            // Build the field array for Facebook Options
            $fb_options = [
                $this->add_on->add_field( 'rank_math_facebook_description', 'Description', 'text', null, "If you don't want to use the meta description for sharing the post on Facebook but want another description there, write it here." ),
                $this->add_on->add_field( 'rank_math_facebook_image', 'Image', 'image', null, "If you want to override the image used on Facebook for this post, import one here. The recommended image size for Facebook is 1200 x 628px."),
                $this->add_on->add_field('rank_math_facebook_image_overlay', 'Add icon overlay to thumbnail','radio', ['' => 'None', 'play' => 'Play icon', 'gif' => 'GIF icon'], 'Possible \'Set with XPath\' values: \'play\', \'gif\'. Leave blank for \'None\'.')
            ];

            if(in_array($this->post_type, ['import_users', 'shop_customer'])) {
                $fb_options[] = $this->add_on->add_field('rank_math_facebook_author', 'Facebook Author URL', 'text');
            }

            $this->add_on->add_options(
                $this->add_on->add_field( 'rank_math_facebook_title', 'Facebook Title', 'text', null, "If you don't want to use the post title for sharing the post on Facebook but instead want another title there, import it here." ),
                'Facebook Options',
                $fb_options
            );

            // Build Twitter Options.
            $tw_options = [
                $this->add_on->add_field(
                    'rank_math_twitter_card_type',
                    'Card Type',
                    'radio',
                    array(
                        'summary_large_image' => 'Summary Card with Large Image',
                        'summary_card' => 'Summary Card',
                        'app' => 'App Card',
                        'player' => 'Player Card',
                    ), 'Possible \'Set with XPath\' values: \'summary_large_image\', \'summary_card\', \'app\', \'player\'.'),
                $this->add_on->add_field('rank_math_twitter_app_description', 'App Description', 'text'),
                $this->add_on->add_field('rank_math_twitter_app_iphone_name', 'App iPhone Name', 'text'),
                $this->add_on->add_field('rank_math_twitter_app_iphone_id', 'iPhone App ID', 'text'),
                $this->add_on->add_field('rank_math_twitter_app_iphone_url', 'iPhone App URL', 'text'),
                $this->add_on->add_field('rank_math_twitter_app_ipad_name', 'iPad App Name', 'text'),
                $this->add_on->add_field('rank_math_twitter_app_ipad_id', 'iPad App ID', 'text'),
                $this->add_on->add_field('rank_math_twitter_app_ipad_url', 'iPad App URL', 'text'),
                $this->add_on->add_field('rank_math_twitter_app_googleplay_name', 'Google Play App Name', 'text'),
                $this->add_on->add_field('rank_math_twitter_app_googleplay_id', 'Google Play App ID', 'text'),
                $this->add_on->add_field('rank_math_twitter_app_googleplay_url', 'Google Play App URL', 'text'),
                $this->add_on->add_field('rank_math_twitter_app_country', 'App Country', 'text'),
                $this->add_on->add_field('rank_math_twitter_player_url', 'Player URL', 'text'),
                $this->add_on->add_field('rank_math_twitter_player_size', 'Player Size', 'text'),
                $this->add_on->add_field('rank_math_twitter_player_stream', 'Stream URL', 'text'),
                $this->add_on->add_field('rank_math_twitter_player_stream_ctype', 'Stream Content Type', 'text'),
                $this->add_on->add_field(
                    'rank_math_twitter_use_facebook',
                    'Twitter Data',
                    'radio',
                    array(
                        'on' => 'Use Data from Facebook Tab',
                        'off' =>

                            array(
                                'Set Separate Twitter Values',
                                $this->add_on->add_field( 'rank_math_twitter_title', 'Twitter Title', 'text', null, "If you don't want to use the post title for sharing the post on Twitter but instead want another title there, import it here." ),
                                $this->add_on->add_field( 'rank_math_twitter_description', 'Description', 'text', null, "If you don't want to use the meta description for sharing the post on Twitter but want another description there, import it here." ),
                                $this->add_on->add_field( 'rank_math_twitter_image', 'Image', 'image', null, "<!--rank_math_twitter_image_yes-->If you want to override the image used on Twitter for this post, import one here. The recommended image size for Twitter is 1024 x 512px."),
                                $this->add_on->add_field('rank_math_twitter_image_overlay', 'Add icon overlay to thumbnail','radio', ['' => 'None', 'play' => 'Play icon', 'gif' => 'GIF icon'], 'Possible \'Set with XPath\' values: \'play\', \'gif\'. Leave blank for \'None\'.'),

                            ),

                    )
                ),
            ];

            if(in_array($this->post_type, ['import_users', 'shop_customer'])) {
                $tw_options[] = $this->add_on->add_field('rank_math_twitter_author', 'Twitter Author URL', 'text');
            }


            $this->add_on->add_options(null,
                'Twitter Options',
                $tw_options
            );

            $this->add_on->add_options(null,
                'Advanced',
                array(
                    // Default to noindex for users, customers, post_tags, product_tags, .
                    (in_array($this->post_type, ['import_users', 'shop_customer']) || in_array($this->taxonomy_type, ['post_tag', 'product_tag']))?$this->add_on->add_field( 'rank_math_wpseo_meta-robots-noindex', 'Meta Robots Index', 'radio', ['noindex' => 'No Index', 'index' => 'Index',] ) : $this->add_on->add_field( 'rank_math_wpseo_meta-robots-noindex', 'Meta Robots Index', 'radio', ['index' => 'Index', 'noindex' => 'No Index'], 'Possible \'Set with XPath\' values: \'index\', \'noindex\'' ),

                    $this->add_on->add_field( 'rank_math_wpseo_meta-robots-nofollow', 'Meta Robots Nofollow', 'radio', ['' => 'Default', 'nofollow' => 'Nofollow'], 'Possible \'Set with XPath\' values: \'nofollow\'. Leave blank for \'Default\'.' ),
                    $this->add_on->add_field( 'rank_math_wpseo_meta-robots-noarchive', 'Meta Robots No Archive', 'radio', ['' => 'Default', 'noarchive' => 'No Archive'], 'Possible \'Set with XPath\' values: \'noarchive\'. Leave blank for \'Default\'.' ),
                    $this->add_on->add_field( 'rank_math_wpseo_meta-robots-noimageindex', 'Meta Robots No Image Index', 'radio', ['' => 'Default', 'noimageindex' => 'No Image Index'], 'Possible \'Set with XPath\' values: \'noimageindex\'. Leave blank for \'Default\'.' ),
                    $this->add_on->add_field( 'rank_math_wpseo_meta-robots-nosnippet', 'Meta Robots No Snippet', 'radio', ['' => 'Default', 'nosnippet' => 'No Snippet'], 'Possible \'Set with XPath\' values: \'nosnippet\'. Leave blank for \'Default\'.' ),
                    $this->add_on->add_field( 'rank_math_wpseo_meta_robots_max-snippet', 'Max Snippet', 'text', null, 'Integers only. Limit disabled if value is 0 or empty.' ),
                    $this->add_on->add_field( 'rank_math_wpseo_meta_robots_max-video-preview', 'Max Video Preview','text', null, 'Integers only. Limit disabled if value is 0 or empty.' ),
                    $this->add_on->add_field( 'rank_math_wpseo_meta_robots_max-image-preview', 'Max Image Preview', 'radio', ['large'=>'Large','standard'=>'Standard','none'=>'None',''=>'Limit Disabled'], 'Possible \'Set with XPath\' values: \'large\', \'standard\', \'none\'. Limit disabled if value is 0 or empty.' ),
                    $this->add_on->add_field( 'rank_math_canonical_url', 'Canonical URL', 'text'),
                ));

            if( in_array($this->post_type, ['import_users', 'shop_customer', 'taxonomies'] )){
                // No schema options for users, customers, taxonomies.
            }else{

                $schema_types = ['off' => 'None', 'Article' => 'Article', 'Book' => 'Book', 'Course' => 'Course', 'Event' => 'Event', 'JobPosting' => 'Job Posting', 'Music' => 'Music', 'Person' => 'Person', 'Product' => 'Product', 'Recipe' => 'Recipe', 'Restaurant' => 'Restaurant', 'Service' => 'Service', 'SoftwareApplication' => 'Software Application', 'VideoObject' => 'Video'];
                $schema_types_tooltip = 'Review RankMath\'s Rich Snippets documentation for additional information. <br/><br/>Possible \'Set with XPath\' values: \'off\', \'Article\', \'Book\', \'Course\', \'Event\', \'JobPosting\', \'Music\', \'Person\', \'Product\', \'Recipe\', \'Restaurant\', \'Service\', \'SoftwareApplication\', \'VideoObject\'';

                if( in_array($this->post_type, ['product']) ){
                    $schema_types['WooCommerceProduct'] = 'WooCommerce Product';
                    $schema_types_tooltip .= ', \'WooCommerceProduct\'';
                }

                $schema_types_tooltip .= '.';

                $this->add_on->add_options(null,
                    'Schema',
                    array(
                        $this->add_on->add_field('rank_math_schema_type', 'Schema Type', 'radio',
                            $schema_types,
                            $schema_types_tooltip
                        ),

                        // WooCommerce Product
                        $this->add_on->add_field(
                            'rank_math_schema_woocommerce_product',
                            'No configurable options for WooCommerce Product schema.',
                            'radio'
                        ),

                        // Book, Course, Event, Product, Recipe, Software
                        $this->add_on->add_field('rank_math_schema_location', 'Review Location', 'radio',
                            ['top' => 'Above Content', 'bottom' => 'Below Content', 'both' => 'Above & Below Content', 'custom' => 'Custom'], '<!-- rank_math_schema_location -->Possible \'Set with XPath\' values: \'top\', \'bottom\', \'both\', or \'custom\''),

                        // Article, Book, Course, Event, Job, Music, Person, Product, Recipe, Restaurant, Service, Software, Video
                        $this->add_on->add_field('rank_math_schema_headline', 'Headline', 'text'),

                        // Article, Course, Event, Job, Music, Person, Product, Recipe, Restaurant, Service, Software, Video
                        $this->add_on->add_field('rank_math_schema_description', 'Description', 'textarea'),

                        // Video
                        $this->add_on->add_field('rank_math_schema_video_url', 'Video Content URL', 'text'),
                        $this->add_on->add_field('rank_math_schema_video_embed_url', 'Video Embed URL', 'text'),
                        $this->add_on->add_field('rank_math_schema_video_duration', 'Video Duration', 'text', null, '<!--rank_math_schema_video_duration-->ISO 8601 duration format. Example: 1H30M'),

                        // Software
                        $this->add_on->add_field('rank_math_schema_software_price', 'Software Price', 'text'),
                        $this->add_on->add_field('rank_math_schema_software_price_currency', 'Software Price Currency', 'text'),
                        $this->add_on->add_field('rank_math_schema_software_operating_system', 'Software Operating System', 'text'),
                        $this->add_on->add_field('rank_math_schema_software_application_category', 'Software Application Category', 'text'),
                        $this->add_on->add_field('rank_math_schema_software_rating', 'Software Rating', 'text'),
                        $this->add_on->add_field('rank_math_schema_software_rating_min', 'Software Rating Minimum', 'text'),
                        $this->add_on->add_field('rank_math_schema_software_rating_max', 'Software Rating Maximum', 'text'),

                        // Service
                        $this->add_on->add_field('rank_math_schema_service_type', 'Service Type', 'text'),
                        $this->add_on->add_field('rank_math_schema_service_price', 'Service Price', 'text'),
                        $this->add_on->add_field('rank_math_schema_service_price_currency', 'Service Price Currency', 'text', null, '<!--rank_math_schema_service_price_currency-->ISO 4217 Currency code. Example: EUR'),

                        // Restaurant
                        $this->add_on->add_field('rank_math_schema_local_address_street', 'Restaurant Street', 'text'),
                        $this->add_on->add_field('rank_math_schema_local_address_locality', 'Restaurant Locality', 'text'),
                        $this->add_on->add_field('rank_math_schema_local_address_region', 'Restaurant Region', 'text'),
                        $this->add_on->add_field('rank_math_schema_local_address_postalcode', 'Restaurant Postal Code', 'text'),
                        $this->add_on->add_field('rank_math_schema_local_address_country', 'Restaurant Country', 'text'),
                        $this->add_on->add_field('rank_math_schema_local_lat', 'Restaurant Geo Coordinates - Latitude', 'text'),
                        $this->add_on->add_field('rank_math_schema_local_long', 'Restaurant Geo Coordinates - Longitude', 'text'),
                        $this->add_on->add_field('rank_math_schema_local_phone', 'Restaurant Phone Number', 'text'),
                        $this->add_on->add_field('rank_math_schema_local_price_range', 'Restaurant Price Range', 'text'),
                        $this->add_on->add_field('rank_math_schema_local_opens', 'Restaurant Opening Time', 'text'),
                        $this->add_on->add_field('rank_math_schema_local_closes', 'Restaurant Closing Time', 'text'),
                        $this->add_on->add_field('rank_math_schema_local_opendays', 'Open Days', 'text', null, '<!--rank_math_schema_local_opendays-->Possible values: \'monday\', \'tuesday\', \'wednesday\', \'thursday\', \'friday\', \'saturday\', \'sunday\'. Separate multiple values with pipes: |'),
                        $this->add_on->add_field('rank_math_schema_restaurant_serves_cuisine', 'Restaurant Serves Cuisine', 'text', null, '<!--rank_math_schema_restaurant_serves_cuisine-->The type of cuisine we serve. Separated by pipes: |.'),
                        $this->add_on->add_field('rank_math_schema_restaurant_menu', 'Restaurant Menu URL', 'text'),

                        // Recipe
                        $this->add_on->add_field('rank_math_schema_recipe_type', 'Recipe Type', 'text'),
                        $this->add_on->add_field('rank_math_schema_recipe_cuisine', 'Recipe Cuisine', 'text'),
                        $this->add_on->add_field('rank_math_schema_recipe_keywords', 'Recipe Keywords', 'text'),
                        $this->add_on->add_field('rank_math_schema_recipe_yield', 'Recipe Yield', 'text'),
                        $this->add_on->add_field('rank_math_schema_recipe_calories', 'Recipe Calories', 'text'),
                        $this->add_on->add_field('rank_math_schema_recipe_preptime', 'Recipe Preparation Time', 'text', null, '<!--rank_math_schema_recipe_preptime-->ISO 8601 duration format. Example: PT1H30M'),
                        $this->add_on->add_field('rank_math_schema_recipe_cooktime', 'Recipe Cooking Time', 'text', null, '<!--rank_math_schema_recipe_cooktime-->ISO 8601 duration format. Example: PT1H30M'),
                        $this->add_on->add_field('rank_math_schema_recipe_totaltime', 'Recipe Total Time', 'text', null, '<!--rank_math_schema_recipe_totaltime-->ISO 8601 duration format. Example: PT1H30M'),
                        $this->add_on->add_field('rank_math_schema_recipe_rating', 'Recipe Rating', 'text'),
                        $this->add_on->add_field('rank_math_schema_recipe_rating_min', 'Recipe Rating Minimum', 'text'),
                        $this->add_on->add_field('rank_math_schema_recipe_rating_max', 'Recipe Rating Maximum', 'text'),
                        $this->add_on->add_field('rank_math_schema_recipe_video', 'Recipe Video', 'text'),
                        $this->add_on->add_field('rank_math_schema_recipe_video_content_url', 'Recipe Video Content URL', 'text'),
                        $this->add_on->add_field('rank_math_schema_recipe_video_thumbnail', 'Recipe Video Thumbnail', 'text'),
                        $this->add_on->add_field('rank_math_schema_recipe_video_name', 'Recipe Video Name', 'text'),
                        $this->add_on->add_field('rank_math_schema_recipe_video_date', 'Recipe Video Upload Date', 'text'),
                        $this->add_on->add_field('rank_math_schema_recipe_video_description', 'Recipe Video Description', 'textarea'),
                        $this->add_on->add_field('rank_math_schema_recipe_ingredients', 'Recipe Ingredients', 'textarea', null, '<!--rank_math_schema_recipe_ingredients-->Separate multiple ingredients with pipes: |.'),
                        $this->add_on->add_field('rank_math_schema_recipe_instruction_type', 'Recipe Instruction Type', 'radio', ['SingleField' => 'Single Field', 'HowToStep' => 'How To Step'], '<!--rank_math_schema_recipe_instruction_type-->Possible \'Set with XPath\' values: \'SingleField\', \'HowToStep\'.'),
                        $this->add_on->add_field('rank_math_schema_recipe_single_instructions', 'Recipe Instructions', 'textarea'),
                        $this->add_on->add_field('rank_math_schema_recipe_instructions_name', 'Recipe Instructions Name', 'text', null, '<!--rank_math_schema_recipe_instructions_name-->Instruction name of the recipe. Multiple names should be separated by pipes: |'),
                        $this->add_on->add_field('rank_math_schema_recipe_instructions_text', 'Recipe Instructions Text', 'text', null, '<!--rank_math_schema_recipe_instructions_text-->Steps to take, separate each instruction with a caret: ^. Multiple instruction sets should be separated by pipes: |. <br/>e.g. step 1^step2|next step 1^next step 2'),


                        // Product
                        $this->add_on->add_field('rank_math_schema_product_sku', 'Product SKU', 'text'),
                        $this->add_on->add_field('rank_math_schema_product_brand', 'Product Brand', 'text'),
                        $this->add_on->add_field('rank_math_schema_product_currency', 'Product Currency', 'text'),
                        $this->add_on->add_field('rank_math_schema_product_price', 'Product Price', 'text'),
                        $this->add_on->add_field('rank_math_schema_product_price_valid', 'Product Price Valid Until', 'text'),
                        $this->add_on->add_field('rank_math_schema_product_instock', 'Product Availability', 'radio', ['' => 'None', 'InStock' => 'In Stock', 'SoldOut' => 'Sold Out', 'PreOrder' => 'Preorder'], '<!--rank_math_schema_product_instock-->Possible \'Set with XPath\' values: \'\', \'InStock\', \'SoldOut\', \'PreOrder\'.'),
                        $this->add_on->add_field('rank_math_schema_product_rating', 'Product Rating', 'text'),
                        $this->add_on->add_field('rank_math_schema_product_rating_min', 'Product Rating Minimum', 'text'),
                        $this->add_on->add_field('rank_math_schema_product_rating_max', 'Product Rating Maximum', 'text'),

                        // Person
                        $this->add_on->add_field('rank_math_schema_person_email', 'Person Email', 'text'),
                        $this->add_on->add_field('rank_math_schema_person_address_street', 'Person Street', 'text'),
                        $this->add_on->add_field('rank_math_schema_person_address_locality', 'Person Locality', 'text'),
                        $this->add_on->add_field('rank_math_schema_person_address_region', 'Person Region', 'text'),
                        $this->add_on->add_field('rank_math_schema_person_address_postalcode', 'Person Postal Code', 'text'),
                        $this->add_on->add_field('rank_math_schema_person_address_country', 'Person Country', 'text'),
                        $this->add_on->add_field('rank_math_schema_person_gender', 'Person Gender', 'text'),
                        $this->add_on->add_field('rank_math_schema_person_job_title', 'Person Job Title', 'text'),

                        // Job
                        $this->add_on->add_field('rank_math_schema_jobposting_salary', 'Job Salary', 'text'),
                        $this->add_on->add_field('rank_math_schema_jobposting_currency', 'Job Currency', 'text', null, '<!--rank_math_schema_jobposting_currency-->ISO 4217 Currency code. Example: EUR'),
                        $this->add_on->add_field('rank_math_schema_jobposting_payroll', 'Job Payroll', 'radio', ['' => 'None', 'YEAR' => 'Yearly', 'MONTH' => 'Monthly', 'WEEK' => 'Weekly', 'DAY' => 'Daily', 'HOUR' => 'Hourly'], '<!--rank_math_schema_jobposting_payroll-->Salary amount is for. Possible \'Set with XPath\' values: \'YEAR\', \'MONTH\', \'WEEK\', \'DAY\', \'HOUR\'. Leave blank for \'None\'.'),
                        $this->add_on->add_field('rank_math_schema_jobposting_startdate', 'Job Date Posted', 'text'),
                        $this->add_on->add_field('rank_math_schema_jobposting_expirydate', 'Job Expiry Posted', 'text'),
                        $this->add_on->add_field('rank_math_schema_jobposting_unpublish', 'Job Unpublish when expired', 'radio', ['on' => 'On', 'off' => 'Off'], '<!--rank_math_schema_jobposting_unpublish-->Possible \'Set with XPath\' values: \'on\', \'off\''),
                        $this->add_on->add_field('rank_math_schema_jobposting_employment_type', 'Job Employment Type', 'radio', ['' => 'None', 'FULL_TIME' => 'Full Time', 'PART_TIME' => 'Part Time', 'CONTRACTOR' => 'Contractor', 'TEMPORARY' => 'Temporary', 'INTERN' => 'Intern', 'VOLUNTEER' => 'Volunteer', 'PER_DIEM' => 'Per Diem', 'OTHER' => 'Other'], '<!--rank_math_schema_jobposting_employment_type-->Possible \'Set with XPath\' values: \'\', \'FULL_TIME\', \'PART_TIME\', \'CONTRACTOR\', \'TEMPORARY\', \'INTERN\', \'VOLUNTEER\', \'PER_DIEM\', \'OTHER\'. <br/><br/>Leave blank for \'None\'. <br/><br/>Separate multiple values with pipes: |'),
                        $this->add_on->add_field('rank_math_schema_jobposting_organization', 'Job Hiring Organization', 'text'),
                        $this->add_on->add_field('rank_math_schema_jobposting_id', 'Job Posting ID', 'text'),
                        $this->add_on->add_field('rank_math_schema_jobposting_url', 'Job Organization URL', 'text'),
                        $this->add_on->add_field('rank_math_schema_jobposting_logo', 'Job Organization Logo', 'text'),
                        $this->add_on->add_field('rank_math_schema_jobposting_address_street', 'Job Organization Street', 'text'),
                        $this->add_on->add_field('rank_math_schema_jobposting_address_locality', 'Job Organization Locality', 'text'),
                        $this->add_on->add_field('rank_math_schema_jobposting_address_region', 'Job Organization Region', 'text'),
                        $this->add_on->add_field('rank_math_schema_jobposting_address_postalcode', 'Job Organization Postal Code', 'text'),
                        $this->add_on->add_field('rank_math_schema_jobposting_address_country', 'Job Organization Country', 'text'),

                        // Event
                        $this->add_on->add_field('rank_math_schema_event_type', 'Event Type', 'radio',
                            ['Event' => 'Event', 'BusinessEvent' => 'Business Event', 'ChildrensEvent' => 'Children\'s Event', 'ComedyEvent' => 'Comedy Event', 'DanceEvent' => 'Dance Event', 'DeliveryEvent' => 'Delivery Event', 'EducationEvent' => 'Education Event', 'ExhibitionEvent' => 'Exhibition Event', 'Festival' => 'Festival', 'FoodEvent' => 'Food Event', 'LiteraryEvent' => 'Literary Event', 'MusicEvent' => 'Music Event', 'PublicationEvent' => 'Publication Event', 'SaleEvent' => 'Sale Event', 'ScreeningEvent' => 'Screening Event', 'SocialEvent' => 'Social Event', 'SportsEvent' => 'Sports Event', 'TheaterEvent' => 'Theater Event', 'VisualArtsEvent' => 'Visual Arts Event'], '<!--rank_math_schema_event_type-->Possible \'Set with XPath\' values: \'Event\', \'BusinessEvent\', \'ChildrensEvent\', \'ComedyEvent\', \'DanceEvent\', \'DeliveryEvent\', \'EducationEvent\', \'ExhibitionEvent\', \'Festival\', \'FoodEvent\', \'LiteraryEvent\', \'MusicEvent\', \'PublicationEvent\', \'SaleEvent\', \'ScreeningEvent\', \'SocialEvent\', \'SportsEvent\', \'TheaterEvent\', \'VisualArtsEvent\''),
                        $this->add_on->add_field('rank_math_schema_event_status', 'Event Status', 'radio', ['' => 'None', 'EventScheduled' => 'Scheduled', 'EventCancelled' => 'Cancelled', 'EventPostponed' => 'Postponed', 'EventRescheduled' => 'Rescheduled', 'EventMovedOnline' => 'Moved Online',], '<!--rank_math_schema_event_status-->Possible \'Set with XPath\' values: \'EventScheduled\', \'EventCancelled\', \'EventPostponed\', \'EventRescheduled\', \'EventMovedOnline\'. Leave blank for \'None\'.'),
                        $this->add_on->add_field('rank_math_schema_event_attendance_mode', 'Event Attendance Mode', 'radio', ['OfflineEventAttendanceMode' => 'Offline', 'OnlineEventAttendanceMode' => 'Online', 'MixedEventAttendanceMode' => 'Online + Offline'], '<!--rank_math_schema_event_attendance_mode-->Possible \'Set with XPath\' values: \'OfflineEventAttendanceMode\', \'OnlineEventAttendanceMode\', \'MixedEventAttendanceMode\'.'),
                        $this->add_on->add_field('rank_math_schema_online_event_url', 'Event Online Event URL', 'text'),
                        $this->add_on->add_field('rank_math_schema_event_venue', 'Event Venue Name', 'text'),
                        $this->add_on->add_field('rank_math_schema_event_venue_url', 'Event Venue URL', 'text'),
                        $this->add_on->add_field('rank_math_schema_event_address_street', 'Event Street Address', 'text'),
                        $this->add_on->add_field('rank_math_schema_event_address_locality', 'Event Locality', 'text'),
                        $this->add_on->add_field('rank_math_schema_event_address_region', 'Event Region', 'text'),
                        $this->add_on->add_field('rank_math_schema_event_address_postalcode', 'Event Postal Code', 'text'),
                        $this->add_on->add_field('rank_math_schema_event_address_country', 'Event Country', 'text'),
                        $this->add_on->add_field('rank_math_schema_event_performer_type', 'Event Performer', 'radio', ['Person' => 'Person', 'Organization' => 'Organization'], '<!--rank_math_schema_event_performer_type-->Possible \'Set with XPath\' values: \'Person\', \'Organization\'.'),
                        $this->add_on->add_field('rank_math_schema_event_performer', 'Event Performer Name', 'text'),
                        $this->add_on->add_field('rank_math_schema_event_performer_url', 'Event Performer URL', 'text'),
                        $this->add_on->add_field('rank_math_schema_event_startdate', 'Event Start Date', 'text'),
                        $this->add_on->add_field('rank_math_schema_event_enddate', 'Event End Date', 'text'),
                        $this->add_on->add_field('rank_math_schema_event_ticketurl', 'Event Ticket URL', 'text'),
                        $this->add_on->add_field('rank_math_schema_event_price', 'Event Entry Price', 'text'),
                        $this->add_on->add_field('rank_math_schema_event_currency', 'Event Currency', 'text'),
                        $this->add_on->add_field('rank_math_schema_event_availability', 'Event Availability', 'radio', ['None' => 'None', 'InStock' => 'In Stock', 'SoldOut' => 'Sold Out', 'PreOrder' => 'PreOrder',], '<!--rank_math_schema_event_availability-->Possible \'Set with XPath\' values: \'None\', \'InStock\', \'SoldOut\', \'PreOrder\'.'),
                        $this->add_on->add_field('rank_math_schema_event_availability_starts', 'Event Availability Starts', 'text'),
                        $this->add_on->add_field('rank_math_schema_event_inventory', 'Event Stock Inventory', 'text'),
                        $this->add_on->add_field('rank_math_schema_event_rating', 'Event Rating', 'text'),
                        $this->add_on->add_field('rank_math_schema_event_rating_min', 'Event Rating Minimum', 'text'),
                        $this->add_on->add_field('rank_math_schema_event_rating_max', 'Event Rating Maximum', 'text'),

                        // Course
                        $this->add_on->add_field('rank_math_schema_course_provider_type', 'Course Provider', 'radio',
                            ['Person' => 'Person', 'Organization' => 'Organization'], '<!--rank_math_schema_course_provider_type-->Possible \'Set with XPath\' values: \'Person\', \'Organization\''),
                        $this->add_on->add_field('rank_math_schema_course_provider', 'Course Provider Name', 'text'),
                        $this->add_on->add_field('rank_math_schema_course_provider_url', 'Course Provider URL', 'text'),
                        $this->add_on->add_field('rank_math_schema_course_rating', 'Course Rating', 'text'),
                        $this->add_on->add_field('rank_math_schema_course_rating_min', 'Course Rating Minimum', 'text'),
                        $this->add_on->add_field('rank_math_schema_course_rating_max', 'Course Rating Maximum', 'text'),

                        // Article
                        $this->add_on->add_field('rank_math_schema_article_type', 'Article Type', 'radio',
                            ['Article' => 'Article', 'BlogPosting' => 'Blog Post', 'NewsArticle' => 'News Article'], '<!--rank_math_schema_article_type-->Possible \'Set with XPath\' options are \'Article\', \'BlogPosting\', \'NewsArticle\'.'
                        ),

                        // Book, Music
                        $this->add_on->add_field('rank_math_schema_url', 'URL', 'text', null, '<!--rank_math_schema_url-->Used for \'Book\' & \'Music\' schema types.'),

                        // Music
                        $this->add_on->add_field('rank_math_schema_music_type', 'Music Type', 'radio', ['MusicGroup' => 'MusicGroup', 'MusicAlbum' => 'MusicAlbum'], '<!--rank_math_schema_music_type-->Possible \'Set with XPath\' values: \'MusicGroup\', \'MusicAlbum\'.'),

                        // Book
                        $this->add_on->add_field('rank_math_schema_author', 'Book Author', 'text'),
                        $this->add_on->add_field('rank_math_schema_book_rating', 'Book Rating', 'text'),
                        $this->add_on->add_field('rank_math_schema_book_rating_min', 'Book Rating Minimum', 'text'),
                        $this->add_on->add_field('rank_math_schema_book_rating_max', 'Book Rating Maximum', 'text'),
                        $this->add_on->add_options(null,
                            'Book Editions',
                            array(
                                $this->add_on->add_field('rank_math_schema_edition_title', 'Title', 'text', null, 'Separate multiple editions with pipes: | '),
                                $this->add_on->add_field('rank_math_schema_edition_edition', 'Edition', 'text', null, 'Separate multiple editions with pipes: | '),
                                $this->add_on->add_field('rank_math_schema_edition_isbn', 'ISBN', 'text', null, 'Separate multiple editions with pipes: | '),
                                $this->add_on->add_field('rank_math_schema_edition_url', 'URL', 'text', null, 'Separate multiple editions with pipes: | '),
                                $this->add_on->add_field('rank_math_schema_edition_author', 'Author', 'text', null, 'Separate multiple editions with pipes: | '),
                                $this->add_on->add_field('rank_math_schema_edition_date', 'Date Published', 'text', null, 'Separate multiple editions with pipes: | '),
                                $this->add_on->add_field('rank_math_schema_edition_format', 'Book Format', 'radio',
                                    ['https://schema.org/EBook' => 'EBook', 'https://schema.org/Hardcover' => 'Hardcover', 'https://schema.org/Paperback' => 'Paperback', 'https://schema.org/AudioBook' => 'Audio Book'], 'Possible \'Set with XPath\' values: \'https://schema.org/EBook\', \'https://schema.org/Hardcover\', \'https://schema.org/Paperback\', \'https://schema.org/AudioBook\'. Separate multiple editions with pipes: | '),
                            )
                        )

                    ));

            }
        }

        /**
         * The taxonomy Rank Math treats as primary for this post type, from its
         * Titles & Meta setting (titles.pt_{post_type}_primary_taxonomy). Returns ''
         * when none is configured ('off'), so no primary-term field is offered.
         */
        private function get_primary_taxonomy(){
            $setting = 'pt_' . $this->post_type . '_primary_taxonomy';

            if( class_exists('RankMath\\Helper') ){
                $value = \RankMath\Helper::get_settings( 'titles.' . $setting );
                if( !empty($value) ){
                    return 'off' === $value ? '' : $value;
                }
            }

            $titles = get_option( 'rank_math_titles', [] );
            if( is_array($titles) && !empty($titles[ $setting ]) ){
                return 'off' === $titles[ $setting ] ? '' : $titles[ $setting ];
            }

            // Rank Math's built-in defaults, for sites where the option isn't initialised yet.
            $defaults = [ 'post' => 'category', 'product' => 'product_cat' ];
            return isset($defaults[ $this->post_type ]) ? $defaults[ $this->post_type ] : '';
        }
    }
}
