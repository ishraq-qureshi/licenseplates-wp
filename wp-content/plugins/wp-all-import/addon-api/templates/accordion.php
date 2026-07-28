<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
<div class="wpallimport-collapsed pmxi-addon <?php echo $addon->isAccordionClosed($type, $subtype) ? 'closed' : ''; ?>" data-addon="<?php echo esc_attr($addon->slug); ?>" data-type="<?php echo esc_attr($type); ?>" data-subtype="<?php echo esc_attr($subtype); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('wp_rest')) ?>">
    <div class="wpallimport-content-section">
        <div class="wpallimport-collapsed-header">
            <h3 data-test="toggle"><?php echo esc_html($addon->name()); ?></h3>
        </div>

        <div class="wpallimport-collapsed-content" style="padding: 0;">
            <div class="wpallimport-collapsed-content-inner">
                <table class="form-table" style="max-width:none;">
                    <tr>
                        <td colspan="3">
                            <?php if (!empty($groups)) : ?>
                                <p>
                                    <strong><?php esc_html_e("Please choose your group.", 'wp-all-import'); ?></strong>
                                </p>
                                <ul>
                                    <?php foreach ($groups as $group) {
                                        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
                                        $show_group = apply_filters('wp_all_import_addon_show_group', true, $addon, $group, $type);
                                        $id = $group['id'];
                                        $label = $group['label'];
                                        $is_checked = in_array($id, ($importOptions[$addon->slug . '_groups'] ?? []));

                                        if ($show_group) : ?>
                                            <li>
                                                <input id="addon-group-<?php echo esc_attr("{$addon->slug}-{$id}"); ?>" type="checkbox" data-label="<?php echo esc_attr($label); ?>" name="<?php echo esc_attr($addon->slug); ?>_groups[]" value="<?php echo esc_attr($id); ?>" data-group="<?php echo esc_attr($id); ?>" <?php if ($is_checked) : ?>checked="checked" <?php endif; ?> class="wpallimport-import-group-checkbox" />
                                                <label for="addon-group-<?php echo esc_attr("{$addon->slug}-{$id}"); ?>"><?php echo esc_html($label); ?></label>
                                            </li>
                                        <?php endif; ?>
                                    <?php } ?>
                                </ul>

                                <div class="pmxi-addon-groups-output"></div>
                            <?php else : ?>
                                <p>
                                    <strong><?php esc_html_e("Please create Groups.", 'wp-all-import'); ?></strong>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
