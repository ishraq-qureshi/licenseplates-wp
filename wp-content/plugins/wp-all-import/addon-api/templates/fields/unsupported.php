<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<p>
    <?php
    echo wp_kses(
        sprintf(
            /* translators: %s: unsupported field type name */
            __('The field <b>%s</b> is currently not supported. Please contact support for more information.', 'wp-all-import'),
            esc_html($field['type'])
        ),
        array('b' => array())
    );
    ?>
</p>
