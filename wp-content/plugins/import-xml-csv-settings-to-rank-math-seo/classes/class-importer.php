<?php
require_once('class-schema.php');

if( !class_exists('WPAI_RankMath_SEO_Importer')) {
    class WPAI_RankMath_SEO_Importer
    {
        protected $add_on;
        protected $post_type;
        protected $schema;

        public function __construct( RapidAddon $addon_obj )
        {
            $this->add_on = $addon_obj;
            $helpers = new WPAI_RankMath_SEO_Helpers();
            $this->post_type = $helpers->get_post_type();
            $this->schema = new Wpai_RankMath_Add_On\Schema();
        }

        public function import($post_id, $data, $import_options, $article){

            $this->add_on->log('<b>'.__( 'Rank Math SEO Add-On', 'rmseoao-wpimport' ).':</b>');

            // process schema first then omit it from the $data array
            $schema_data = array_filter($data, function($key){return (!!preg_match('/rank_math_schema.*/', $key));}, ARRAY_FILTER_USE_KEY);

            // Only process schema if it's set to update.
            if ( empty( $article['ID'] ) || 'yes' == $import_options['options']['update_all_data'] || '1' == $import_options['options']['is_update_rank_math_schema'] ) {
                $this->add_on->log(__( 'Updating Rank Math schema.', 'rmseoao-wpimport' ));

                // process $schema_data
                $this->schema->map($schema_data, $post_id);
            }else{
                $this->add_on->log( __( 'Schema update skipped due to import settings.', 'rmseoao-wpimport' ));

            }

            // remove the schema fields from the $data fields
            $data = array_diff_key( $data, $schema_data );

            // process each field to be imported
            foreach( $data as $field => $value){
                // only process real data fields - note the !() usage.
                if( !(strpos($field, 'accordion_') !== false) ){
                    // make sure we can update this field
                    if( empty( $article['ID'] ) || $this->add_on->can_update_meta( $field, $import_options )) {

                        $this->add_on->log(__( 'Updating '. $field, 'rmseoao-wpimport' ));
                        if (is_array($value)) {
                            // these are our image fields
                            switch( $field ){

                                case ('rank_math_facebook_image'):
                                    $this->update_meta( $post_id, $field, wp_get_attachment_url($value['attachment_id']));
                                    $this->update_meta( $post_id, 'rank_math_facebook_image_id', $value['attachment_id']);
                                    break;

                                case ('rank_math_twitter_image'):
                                    $this->update_meta( $post_id, $field, wp_get_attachment_url($value['attachment_id']));
                                    $this->update_meta( $post_id, 'rank_math_twitter_image_id', $value['attachment_id']);
                                    break;

                            }

                        } else {
                            // these are the regular fields (text, textarea, radio)
                            switch( $field ) {
                                case 'rank_math_wpseo_meta-robots-noindex':
                                case 'rank_math_wpseo_meta-robots-nofollow':
                                case 'rank_math_wpseo_meta-robots-noarchive':
                                case 'rank_math_wpseo_meta-robots-noimageindex':
                                    break; // We only need to run the code once for this list of fields.
                                case 'rank_math_wpseo_meta-robots-nosnippet':

                                // Make sure we can update this field since it's a compilation and not checked earlier.
                                if( empty( $article['ID'] ) || $this->add_on->can_update_meta( 'rank_math_robots', $import_options )) {

                                    // Build the robots array
                                    $robots = [$data['rank_math_wpseo_meta-robots-noindex'], $data['rank_math_wpseo_meta-robots-nofollow'], $data['rank_math_wpseo_meta-robots-noarchive'], $data['rank_math_wpseo_meta-robots-noimageindex'], $data['rank_math_wpseo_meta-robots-nosnippet']];

                                    // filter out empty elements
                                    $robots = array_filter($robots);

                                    // save value
                                    $this->update_meta($post_id, 'rank_math_robots', $robots);
                                }
                                    break;

                                case 'rank_math_wpseo_meta_robots_max-video-preview':
                                case 'rank_math_wpseo_meta_robots_max-image-preview':
                                    break; // We only need to run the code once for this list of fields.
                                case 'rank_math_wpseo_meta_robots_max-snippet':

                                    // Make sure we can update this field since it's a compilation and not checked earlier.
                                    if (empty($article['ID']) || $this->add_on->can_update_meta('rank_math_advanced_robots', $import_options)) {
                                        // build values
                                        $max_video_preview = (empty($data['rank_math_wpseo_meta_robots_max-snippet']) || !is_numeric($data['rank_math_wpseo_meta_robots_max-snippet'])) ? [] : ['max-snippet' => $data['rank_math_wpseo_meta_robots_max-snippet']];
                                        $max_image_preview = (empty($data['rank_math_wpseo_meta_robots_max-video-preview']) || !is_numeric($data['rank_math_wpseo_meta_robots_max-video-preview'])) ? [] : ['max-video-preview' => $data['rank_math_wpseo_meta_robots_max-video-preview']];
                                        $max_snippet = (empty($data['rank_math_wpseo_meta_robots_max-image-preview']) || !in_array(strtolower($data['rank_math_wpseo_meta_robots_max-image-preview']), ['large', 'standard', 'none'])) ? [] : ['max-image-preview' => strtolower($data['rank_math_wpseo_meta_robots_max-image-preview'])];

                                        // Build the advanced robots array
                                        $adv_robots = array_merge($max_video_preview, $max_image_preview, $max_snippet);

                                        // save value
                                        $this->update_meta($post_id, 'rank_math_advanced_robots', $adv_robots);
                                    }
                                    break;

                                case 'rank_math_facebook_image_overlay':

                                    if(!empty($value)) {
                                        $this->update_meta($post_id, $field, $value);
                                        $this->update_meta($post_id, 'rank_math_facebook_enable_image_overlay', 'on');
                                    }else{
                                        $this->update_meta($post_id, 'rank_math_facebook_enable_image_overlay', 'off');
                                    }

                                    break;

                                case 'rank_math_twitter_image_overlay':
                                    if(!empty($value)) {
                                        $this->update_meta($post_id, $field, $value);
                                        $this->update_meta($post_id, 'rank_math_twitter_enable_image_overlay', 'on');
                                    }else{
                                        $this->update_meta($post_id, 'rank_math_twitter_enable_image_overlay', 'off');
                                    }

                                    break;

                                case 'rank_math_edition_title':
                                case 'rank_math_edition_edition':
                                case 'rank_math_edition_isbn':
                                case 'rank_math_edition_url':
                                case 'rank_math_edition_author':
                                case 'rank_math_edition_date':
                                    break; // We only need to run the code once for this list of fields.
                                case 'rank_math_edition_format':

                                // Make sure we can update this field since it's a compilation and not checked earlier.
                                if( empty( $article['ID'] ) || $this->add_on->can_update_meta( 'rank_math_snippet_book_editions', $import_options )) {


                                    // field names used in the add-on for editions
                                    $target_fields = ['rank_math_edition_title', 'rank_math_edition_edition', 'rank_math_edition_isbn', 'rank_math_edition_url', 'rank_math_edition_author', 'rank_math_edition_date', 'rank_math_edition_format'];

                                    // convert all strings to arrays with friendly key names
                                    $field_vals = [];
                                    $field_lengths = [];
                                    foreach ($target_fields as $current) {
                                        $edition_field = explode('_', $current);
                                        $edition_field = array_pop($edition_field);
                                        $field_vals[$edition_field] = explode('|', $data[$current]);
                                        $field_lengths[] = count($field_vals[$edition_field]);
                                    }

                                    // find highest number of array elements
                                    $max = max($field_lengths);

                                    // store the array formatted for the database
                                    $temp = [];

                                    // build the data array for editions
                                    for ($i = 0; $i < $max; $i++) {

                                        // format must be set so we determine the best value we can
                                        if (array_key_exists($i, $field_vals['format'])) {

                                            $temp[$i]['book_format'] = $field_vals['format'][$i];

                                        } elseif (array_key_exists(0, $field_vals['format'])) {

                                            $temp[$i]['book_format'] = $field_vals['format'][0];

                                        } else {
                                            // Rank Math defaults to hardcover so we do also.
                                            $temp[$i]['book_format'] = 'hardcover';

                                        }
                                        $temp[$i]['name'] = array_key_exists($i, $field_vals['title']) ? $field_vals['title'][$i] : '';
                                        $temp[$i]['book_edition'] = array_key_exists($i, $field_vals['edition']) ? $field_vals['edition'][$i] : '';
                                        $temp[$i]['isbn'] = array_key_exists($i, $field_vals['isbn']) ? $field_vals['isbn'][$i] : '';
                                        $temp[$i]['url'] = array_key_exists($i, $field_vals['url']) ? $field_vals['url'][$i] : '';
                                        $temp[$i]['author'] = array_key_exists($i, $field_vals['author']) ? $field_vals['author'][$i] : '';
                                        $temp[$i]['date_published'] = ( array_key_exists($i, $field_vals['date']) && !empty($field_vals['date'][$i]) ) ? date('Y-m-d', strtotime($field_vals['date'][$i])) : '';


                                    }

                                    // save the values
                                    $this->update_meta($post_id, 'rank_math_snippet_book_editions', $temp);
                                }
                                break;

                                // ISO8601 dates
                                case 'rank_math_snippet_event_startdate':
                                case 'rank_math_snippet_event_enddate':
                                case 'rank_math_snippet_event_availability_starts':
                                case 'rank_math_snippet_jobposting_startdate':
                                case 'rank_math_snippet_jobposting_expirydate':
                                case 'rank_math_snippet_product_price_valid':
                                case 'rank_math_snippet_recipe_video_date':

                                    // Skip empty values so we don't store a 1970-01-01 epoch date.
                                    if( empty($value) ){
                                        break;
                                    }
                                    $date = date(DATE_ATOM, strtotime($value));
                                    $this->update_meta($post_id, $field, $date);
                                    break;

                                // These fields should be omitted if blank.
                                case 'rank_math_snippet_event_status':
                                case 'rank_math_snippet_jobposting_payroll':

                                    if(!empty($value)){
                                        $this->update_meta($post_id, $field, $value);
                                    }
                                    break;

                                case (!!preg_match('/.*_address_.*/', $field)):
                                    $this->save_address($post_id, $field, $data, $article, $import_options);
                                    break;

                                // array of uppercase values
                                case 'rank_math_snippet_jobposting_employment_type':
                                    $vals = explode('|', str_replace(" ","", strtoupper($value)));
                                    $vals = array_filter($vals);
                                    $this->update_meta($post_id, $field, $vals);
                                    break;

                                // array of lowercase values
                                case 'rank_math_snippet_local_opendays':
                                    $vals = explode('|', str_replace(" ","", strtolower($value)));
                                    $vals = array_filter($vals);
                                    $this->update_meta($post_id, $field, $vals);
                                    break;

                                case 'rank_math_snippet_recipe_instructions_name':
                                    break;
                                case 'rank_math_snippet_recipe_instructions_text':
                                // Make sure we can update this field since it's a compilation and not checked earlier.
                                if( empty( $article['ID'] ) || $this->add_on->can_update_meta( 'rank_math_snippet_recipe_instructions', $import_options )) {

                                    $names = explode('|',$data['rank_math_snippet_recipe_instructions_name']);
                                    $text_vals = explode('|',$data['rank_math_snippet_recipe_instructions_text']);
                                    $instructions = [];

                                    foreach( $names as $key => $name) {
                                        $text = isset($text_vals[$key]) ? trim($text_vals[$key], "\n\r") : '';
                                        $instructions[] = ['name' => $name, 'text' => $text];
                                    }

                                    $this->update_meta($post_id, 'rank_math_snippet_recipe_instructions', $instructions);

                                }

                                    break;

                                case 'rank_math_snippet_local_opens':
                                case 'rank_math_snippet_local_closes':

                                    // Skip empty values so we don't store a 12:00 AM epoch time.
                                    if( empty($value) ){
                                        break;
                                    }
                                    $time = date("h:i A", strtotime($value));
                                    $this->update_meta($post_id, $field, $time);
                                    break;

                                case (!!preg_match('/^rank_math_primary_/', $field)):
                                    if( !empty($value)) {
                                        // Stash the raw value; resolve it to a term ID once the post and
                                        // its terms are saved, via pmxi_saved_post. The taxonomy is encoded
                                        // in the field name (rank_math_primary_{taxonomy}).
                                        $this->update_meta($post_id, $field . '_temp', $value);

                                        add_filter( 'pmxi_saved_post', [$this, 'set_primary_terms'], 10, 3);
                                    }
                                    break;

                                case (!!preg_match('/rank_math_schema.*/', $field)):

                                    break;

                                default:

                                    // Don't add empty fields.
                                    if( !empty($value) ){
                                        $this->update_meta($post_id, $field, $value);
                                    }
                                /*!empty($data['rank_math_rich_snippet']) || !(strpos($field,'_snippet_') !== false) || !(strpos($field,'_edition_') !== false)*/

                            }
                        }
                    }

                    //$this->add_on->log(__( 'Skipped '. $field .' due to import settings.', 'rmseoao-wpimport' ));
                }
            }
        }

        /**
         * @param $post_id
         * @param string $field
         * @param array $data
         * @param array $article
         * @param array $import_options
         */
        private function save_address($post_id, $field = '', &$data = [], &$article = [], &$import_options = []){

            //$field_parts = ['_address', '_locality', '_region', '_postalcode', '_country'];
            $base = preg_replace('/_(?:(?!_).)+$/', '', $field);
            $current_value = $this->get_meta($post_id, $base, true);

            // may need a field check if it becomes a problem that fields are missing (shouldn't happen though)

            // If the current value is an array then it's already been set for this record.
            if(!is_array($current_value)){

                // Make sure we can update this field since it's a compilation and not checked earlier.
                if( empty( $article['ID'] ) || $this->add_on->can_update_meta( $base, $import_options )) {

                    // Build address array.
                    $address = ['streetAddress' => $data[$base . '_street'], 'addressLocality' => $data[$base . '_locality'], 'addressRegion' => $data[$base . '_region'], 'postalCode' => $data[$base . '_postalcode'], 'addressCountry' => $data[$base . '_country']];

                    // Save address.
                    $this->update_meta($post_id, $base, $address);

                }
            }



        }

        /**
         * @param $post_id
         * @param $field
         * @param mixed $value
         */
        private function update_meta($post_id, $field, $value){

            switch( $this->post_type ){
                case 'import_users':
                case 'shop_customer':
                   return update_user_meta($post_id, $field, $value);
                    break;

                case 'taxonomies':
                    return update_term_meta($post_id, $field, $value);
                    break;

                default:
                    return update_post_meta($post_id, $field, $value);
            }
        }

        private function get_meta($post_id, $field, $single){

            switch( $this->post_type ){
                case 'import_users':
                case 'shop_customer':
                    return get_user_meta($post_id, $field, $single);
                    break;

                case 'taxonomies':
                    return get_term_meta($post_id, $field, $single);
                    break;

                default:
                    return get_post_meta($post_id, $field, $single);
            }
        }

        public function set_primary_terms( $post_id, $xml_node, $is_update ){

            global $wpdb;

            // Find every stashed primary-term value for this post. Reading from postmeta
            // (rather than instance state) is deliberate: a separate filter is registered
            // per record, and they all fire on each saved post, so the value must be keyed
            // to the post being saved.
            $like = $wpdb->esc_like('rank_math_primary_') . '%' . $wpdb->esc_like('_temp');
            $rows = $wpdb->get_results( $wpdb->prepare("SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE %s", $post_id, $like), ARRAY_A );

            foreach( (array) $rows as $row ){
                $temp_key = $row['meta_key'];
                $value    = $row['meta_value'];
                $field    = preg_replace('/_temp$/', '', $temp_key);          // rank_math_primary_{taxonomy}
                $taxonomy = preg_replace('/^rank_math_primary_/', '', $field);

                if( !empty($value) && taxonomy_exists($taxonomy) ){
                    $term_id = $this->resolve_term($value, $taxonomy);
                    if( $term_id ){
                        update_post_meta($post_id, $field, $term_id);
                    }
                }

                // Remove the temporary value.
                delete_post_meta($post_id, $temp_key);
            }
        }

        /**
         * Resolve a primary-term value (term ID, name, or slug) to a term ID within
         * the given taxonomy. Returns 0 when no matching term is found.
         */
        private function resolve_term( $value, $taxonomy ){
            $value = trim( $value );

            foreach( ['id', 'name', 'slug'] as $by ){
                $term = get_term_by( $by, $value, $taxonomy );
                if( $term ){
                    return $term->term_id;
                }
            }

            return 0;
        }
    }
}