<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * @var PMXI_Addon_Base $addon
 * @var string $prefix
 * @var string $prefix_id
 * @var array $options
 */

use Wpai\AddonAPI\PMXI_Addon_Base;

?>

<ul style="padding-left: 35px;">
    <?php if ( ! empty( $post['is_update'] ) ): ?>
        <li>
            <?php
            switch ( $post['update_logic'] ) {
                case 'full_update':
                    /* translators: %s: add-on name */
                    printf(esc_html__( 'all %s fields', 'wp-all-import' ), esc_html($addon->name()));
                    break;
                case 'only':
                    /* translators: 1: add-on name, 2: comma-separated field list */
                    printf( esc_html__( 'only these %1$s fields : %2$s', 'wp-all-import' ), esc_html($addon->name()), esc_html($post['fields_only_list']) );
                    break;
                case 'all_except':
                    /* translators: 1: add-on name, 2: comma-separated field list */
                    printf( esc_html__( 'all %1$s fields except these: %2$s', 'wp-all-import' ), esc_html($addon->name()), esc_html($post['fields_except_list']) );
                    break;
            } ?>
        </li>
    <?php endif; ?>
</ul>
