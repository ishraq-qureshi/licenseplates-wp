<?php if ( ! defined( 'ABSPATH' ) ) exit; // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
<select name="<?php echo esc_attr($html_name); ?>" data-test="input">
    <option value=""><?php esc_html_e('Select', 'wp-all-import'); ?></option>
    <?php foreach ($field['choices'] as $choice) : ?>
        <option value="<?php echo esc_attr($choice['value']); ?>" <?php echo $field_value && $choice['value'] == $field_value ? 'selected="selected"' : ''; ?>>
            <?php echo esc_html($choice['label']); ?>
        </option>
    <?php endforeach; ?>
</select>
