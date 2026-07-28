<?php if ( ! defined( 'ABSPATH' ) ) exit; // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals ?>
<div class="wpallimport-collapsed closed">
    <div class="wpallimport-content-section">
        <div class="wpallimport-collapsed-header">
            <h3><?php esc_html_e('Manage Filtering Options', 'wp-all-import'); ?></h3>
        </div>
        <div class="wpallimport-collapsed-content">
            <div>
                <div class="rule_inputs">
                    <table style="width:100%;">
                        <tr>
                            <th><?php esc_html_e('Element', 'wp-all-import'); ?></th>
                            <th><?php esc_html_e('Rule', 'wp-all-import'); ?></th>
                            <th><?php esc_html_e('Value', 'wp-all-import'); ?></th>
                            <th>&nbsp;</th>
                        </tr>
                        <tr>
                            <td style="width:25%;">
                                <select id="pmxi_xml_element">
                                    <option value=""><?php esc_html_e('Select Element', 'wp-all-import'); ?></option>
                                    <?php
                                    if (isset($elements) && $elements && $elements->length) {
                                        // Reset the static option paths array for fresh rendering
                                        PMXI_Render::$option_paths = array();
                                        PMXI_Render::render_xml_elements_for_filtring($elements->item(0));
                                    }
                                    ?>
                                </select>
                            </td>
                            <td style="width:25%;">
                                <select id="pmxi_rule">
                                    <option value=""><?php esc_html_e('Select Rule', 'wp-all-import'); ?></option>
                                    <option value="equals"><?php esc_html_e('equals', 'wp-all-import'); ?></option>
                                    <option value="not_equals"><?php esc_html_e('not equals', 'wp-all-import'); ?></option>
                                    <option value="greater"><?php esc_html_e('greater than', 'wp-all-import');?></option>
                                    <option value="equals_or_greater"><?php esc_html_e('equals or greater than', 'wp-all-import'); ?></option>
                                    <option value="less"><?php esc_html_e('less than', 'wp-all-import'); ?></option>
                                    <option value="equals_or_less"><?php esc_html_e('equals or less than', 'wp-all-import'); ?></option>
                                    <option value="contains"><?php esc_html_e('contains', 'wp-all-import'); ?></option>
                                    <option value="not_contains"><?php esc_html_e('not contains', 'wp-all-import'); ?></option>
                                    <option value="is_empty"><?php esc_html_e('is empty', 'wp-all-import'); ?></option>
                                    <option value="is_not_empty"><?php esc_html_e('is not empty', 'wp-all-import'); ?></option>
                                </select>
                            </td>
                            <td style="width:25%;">
                                <input id="pmxi_value" type="text" placeholder="value" value=""/>
                            </td>
                            <td style="width:15%;">
                                <a id="pmxi_add_rule" href="javascript:void(0);"><?php esc_html_e('Add Rule', 'wp-all-import');?></a>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="clear"></div>
            <table class="xpath_filtering">
                <tr>
                    <td style="width:5%; font-weight:bold; color: #000;"><?php esc_html_e('XPath','wp-all-import');?></td>
                    <td style="width:95%;">
                        <input type="text" name="xpath" value="<?php echo esc_attr($post['xpath']) ?>" style="max-width:none;" />
                        <input type="hidden" id="root_element" name="root_element" value="<?php echo esc_attr(PMXI_Plugin::$session->source['root_element']); ?>"/>
                    </td>
                </tr>
            </table>
            <input type="hidden" name="is_csv" value="<?php echo empty($is_csv) ? '' : esc_attr($is_csv);?>" />
            <div id="wpallimport-filters" class="wpallimport-collapsed-content" style="padding:0; <?php if (!empty($post['filters_output'])):?>display: block;<?php endif; ?>">
                <table style="width: 100%; font-weight: bold; padding: 20px 20px 0 20px;">
                    <tr>
                        <td style="width: 30%; padding-left: 30px;"><?php esc_html_e('Element', 'wp-all-import'); ?></td>
                        <td style="width:20%;"><?php esc_html_e('Rule', 'wp-all-import'); ?></td>
                        <td style="width:20%;"><?php esc_html_e('Value', 'wp-all-import'); ?></td>
                        <td style="width:25%;"><?php esc_html_e('Condition', 'wp-all-import'); ?></td>
                    </tr>
                </table>
                <div class="wpallimport-content-section">
                    <fieldset id="filtering_rules">
                        <p style="margin:20px 0 5px; text-align:center; <?php if (!empty($post['filters_output'])):?>display: none;<?php endif; ?>"><?php esc_html_e('No filtering options. Add filtering options to only import records matching some specified criteria.', 'wp-all-import');?></p>
                        <ol class="filtering_rules"><?php if (!empty($post['filters_output'])):?><?php echo wp_kses_post(json_decode($post['filters_output']));?><?php endif; ?></ol>
                        <div class="clear"></div>
                        <a href="javascript:void(0);" id="apply_filters" <?php if (empty($post['filters_output'])):?>style="display:none;"<?php endif; ?>><?php esc_html_e('Apply Filters To XPath', 'wp-all-import');?></a>
                        <input type="hidden" class="filtering-output" name="filters_output" value="<?php echo esc_attr($post['filters_output'] ?? ''); ?>"/>
                    </fieldset>
                </div>
            </div>
        </div>
    </div>
</div>
