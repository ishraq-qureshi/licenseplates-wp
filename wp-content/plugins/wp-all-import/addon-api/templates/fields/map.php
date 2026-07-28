<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$html_id   = str_replace( [ '[', ']' ], [ '_', '' ], $html_name );
$search_by = $field_value['search_logic'] ?? 'by_address';
?>

<div class="pmxi-switcher">
    <div class="pmxi-switcher-radio-group">
        <label class="pmxi-switcher-radio-item">
            <input type="radio" id="<?php echo esc_attr($html_id); ?>_by_address" class="switcher" data-test="switcher-by_address"
                   name="<?php echo esc_attr($html_name); ?>[search_logic]"
                   value="by_address" <?php echo 'by_address' == $search_by ? 'checked="checked"' : '' ?> />
            <span><?php esc_html_e('Search by address', 'wp-all-import'); ?></span>
        </label>

        <label class="pmxi-switcher-radio-item">
            <input type="radio" id="<?php echo esc_attr($html_id); ?>_by_coordinates" class="switcher"
                   data-test="switcher-by_coordinates" name="<?php echo esc_attr($html_name); ?>[search_logic]"
                   value="by_coordinates" <?php echo 'by_coordinates' == $search_by ? 'checked="checked"' : ''; ?> />
            <span><?php esc_html_e('Search by coordinates', 'wp-all-import'); ?></span>
        </label>

        <label class="pmxi-switcher-radio-item">
            <input type="radio" id="<?php echo esc_attr($html_id); ?>_manual" class="switcher" data-test="switcher-manual"
                   name="<?php echo esc_attr($html_name); ?>[search_logic]"
                   value="manual" <?php echo 'manual' == $search_by ? 'checked="checked"' : ''; ?> />
            <span><?php esc_html_e('Manual', 'wp-all-import'); ?></span>
            <a href="#help" class="wpallimport-help" style="top: -1px;"
               title="<?php esc_attr_e( "Don't geocode the value, leave it as is.", 'wp-all-import' ); ?>">?</a>
        </label>
    </div>

    <div class="pmxi-switcher-target switcher-target-<?php echo esc_attr($html_id); ?>_by_address pmxi-switcher-target--expanded">
        <label for="<?php echo esc_attr($html_id . '_address'); ?>"><?php esc_html_e('Address', 'wp-all-import'); ?></label>
        <div class="pmxi-addon-input-wrap">
            <input type="text" name="<?php echo esc_attr($html_name); ?>[address]" id="<?php echo esc_attr($html_id . '_address'); ?>"
                   value="<?php echo esc_attr($field_value['address'] ?? ''); ?>" class="text widefat rad4 ui-droppable">
        </div>
    </div>

    <div class="pmxi-switcher-target switcher-target-<?php echo esc_attr($html_id); ?>_by_coordinates">
        <div class="pmxi-addon-two-columns">
            <div>
                <label for="<?php echo esc_attr($html_id . '_lat'); ?>"><?php esc_html_e('Latitude', 'wp-all-import'); ?></label>
                <div class="pmxi-addon-input-wrap">
                    <input type="text" name="<?php echo esc_attr($html_name); ?>[lat]" id="<?php echo esc_attr($html_id . '_lat'); ?>"
                           value="<?php echo esc_attr($field_value['lat'] ?? ''); ?>" class="ui-droppable">
                </div>
            </div>

            <div>
                <label for="<?php echo esc_attr($html_id . '_lng'); ?>"><?php esc_html_e('Longitude', 'wp-all-import'); ?></label>
                <div class="pmxi-addon-input-wrap">
                    <input type="text" name="<?php echo esc_attr($html_name); ?>[lng]" id="<?php echo esc_attr($html_id . '_lng'); ?>"
                           value="<?php echo esc_attr($field_value['lng'] ?? ''); ?>" class="ui-droppable">
                </div>
            </div>
        </div>
    </div>

    <div class="pmxi-switcher-target switcher-target-<?php echo esc_attr($html_id); ?>_manual pmxi-switcher-target--expanded">
        <label for="<?php echo esc_attr($html_id . '_manual_location'); ?>"><?php esc_html_e('Address', 'wp-all-import'); ?></label>
        <div class="pmxi-addon-input-wrap">
            <input type="text" name="<?php echo esc_attr($html_name); ?>[manual_location]"
                   id="<?php echo esc_attr($html_id . '_manual_location'); ?>"
                   value="<?php echo esc_attr($field_value['manual_location'] ?? ''); ?>" class="text widefat rad4 ui-droppable">
            <div class="pmxi-addon-two-columns">
                <div>
                    <label for="<?php echo esc_attr($html_id . '_manual_lat'); ?>"><?php esc_html_e('Latitude', 'wp-all-import'); ?></label>
                    <div class="pmxi-addon-input-wrap">
                        <input type="text" name="<?php echo esc_attr($html_name); ?>[manual_lat]" id="<?php echo esc_attr($html_id . 'manual_lat'); ?>"
                               value="<?php echo esc_attr($field_value['manual_lat'] ?? ''); ?>" class="ui-droppable">
                    </div>
                </div>

                <div>
                    <label for="<?php echo esc_attr($html_id . '_manual_lng'); ?>"><?php esc_html_e('Longitude', 'wp-all-import'); ?></label>
                    <div class="pmxi-addon-input-wrap">
                        <input type="text" name="<?php echo esc_attr($html_name); ?>[manual_lng]" id="<?php echo esc_attr($html_id . 'manual_lng'); ?>"
                               value="<?php echo esc_attr($field_value['manual_lng'] ?? ''); ?>" class="ui-droppable">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="pmxi-addon-map-settings">
        <div class="pmxi-addon-map-settings-header">
            <input type="hidden" name="<?php echo esc_attr($html_name); ?>[enable_maps_settings]" value="0"/>
            <input type="checkbox" class="pmxi-reveal-on-change" data-target="<?php echo esc_attr($html_id); ?>_maps_settings"
                   name="<?php echo esc_attr($html_name); ?>[enable_maps_settings]" id="<?php echo esc_attr($html_id . '_enable_maps_settings'); ?>"
                   value="1" <?php echo ! empty( $field_value['enable_maps_settings'] ) ? 'checked="checked"' : ''; ?>>
            <label for="<?php echo esc_attr($html_id . '_enable_maps_settings'); ?>">
                <span><?php
                    /* translators: %s: geocode provider name (e.g. Google Maps) */
                    printf(esc_html__('%s Settings', 'wp-all-import'), esc_html($field_class->getGeocodeProviderName()));
                ?></span>
            </label>
        </div>

        <div class="pmxi-addon-map-settings-content pmxi-content-to-reveal" id="<?php echo esc_attr($html_id); ?>_maps_settings" hidden>

            <input type="hidden" name="<?php echo esc_attr($html_name); ?>[use_custom_api_key]" value="0"/>
            <input type="checkbox" class="pmxi-reveal-on-change" data-target="<?php echo esc_attr($html_id); ?>_custom_api_key"
                   name="<?php echo esc_attr($html_name); ?>[use_custom_api_key]" id="<?php echo esc_attr($html_id . '_use_custom_api_key'); ?>"
                   value="1" <?php echo ! empty( $field_value['use_custom_api_key'] ) ? 'checked="checked"' : ''; ?>>
            <label for="<?php echo esc_attr($html_id . '_use_custom_api_key'); ?>">
                <span><?php
                    /* translators: %s: geocode provider name */
                    printf(esc_html__('Use Custom %s API Key', 'wp-all-import'), esc_html($field_class->getGeocodeProviderName()));
                ?></span>
            </label>

            <div class="pmxi-content-to-reveal" id="<?php echo esc_attr($html_id); ?>_custom_api_key" hidden="">
                <div class="pmxi-addon-input-wrap">
                    <input type="text" name="<?php echo esc_attr($html_name); ?>[custom_api_key]"
                           value="<?php echo esc_attr($field_value['custom_api_key'] ?? ''); ?>"
                           class="text widefat rad4 ui-droppable"
                           placeholder="<?php
                               /* translators: %s: geocode provider name */
                               printf(esc_attr__('%s API Key', 'wp-all-import'), esc_attr($field_class->getGeocodeProviderName()));
                           ?>">
                </div>
            </div>

            <div class="pmxi-addon-two-columns">
                <div class="pmxi-addon-input-wrap">
                    <label for="<?php echo esc_attr($html_id . '_custom_region'); ?>"><?php esc_html_e('Region', 'wp-all-import'); ?></label>
                    <a href="#help" class="wpallimport-help" style="top: -1px;"
                       title="<?php esc_attr_e( "The region code, specified as a ccTLD (top-level domain) two-character value.", 'wp-all-import' ); ?>">?</a>

                    <input type="text" name="<?php echo esc_attr($html_name); ?>[custom_region]"
                           value="<?php echo esc_attr($field_value['custom_region'] ?? ''); ?>"
                           class="text widefat rad4 ui-droppable" id="<?php echo esc_attr($html_id . '_custom_region'); ?>">
                </div>

                <div class="pmxi-addon-input-wrap">
                    <label for="<?php echo esc_attr($html_id . '_custom_language'); ?>"><?php esc_html_e('Language', 'wp-all-import'); ?></label>
                    <a href="#help" class="wpallimport-help" style="top: -1px;"
                       title="<?php esc_attr_e( "The language in which to return results, specified as a two-letter language code.", 'wp-all-import' ); ?>">?</a>
                    <input type="text" name="<?php echo esc_attr($html_name); ?>[custom_language]"
                           value="<?php echo esc_attr($field_value['custom_language'] ?? ''); ?>"
                           class="text widefat rad4 ui-droppable" id="<?php echo esc_attr($html_id . '_custom_language'); ?>">
                </div>
            </div>
        </div>
    </div>
</div>
