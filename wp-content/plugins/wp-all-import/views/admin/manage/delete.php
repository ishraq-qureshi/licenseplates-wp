<?php if ( ! defined( 'ABSPATH' ) ) exit; // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals ?>
<h2><?php esc_html_e('Delete Import', 'wp-all-import') ?></h2>

<?php

if (!empty($item->options['custom_type'])){
	switch ($item->options['custom_type']){
		
		default:
			$custom_type = get_post_type_object( $item->options['custom_type'] );
			if ( ! empty($custom_type) ) {
				$custom_type->label = $custom_type->labels->name;
				$custom_type->singular_label = $custom_type->labels->singular_name;
			}
			break;
	}
	$cpt_name = ( ! empty($custom_type)) ? ( ($associated_posts == 1) ? $custom_type->singular_label : $custom_type->label) : '';
	// Remove mention of WooCommerce from post type string
	$cpt_del_name = str_replace("WooCommerce", "", $cpt_name);
}
else{
	$cpt_name = '';
	$cpt_del_name = '';
}

?>

<form method="post">	
	<div class="input">
		<div class="input">
			<input type="hidden" name="is_delete_import" value="0"/>
			<input type="checkbox" id="is_delete_import" name="is_delete_import" style="position: relative; top: 2px;" value="1"/> 
			<label for="is_delete_import"><?php esc_html_e('Delete import','wp-all-import');?> </label>
		</div>
		<div class="input">
			<input type="hidden" name="is_delete_posts" value="0"/>
			<input type="checkbox" id="is_delete_posts" name="is_delete_posts" class="switcher" style="position: relative; top: 2px;" value="1"/>
			<label for="is_delete_posts"><?php printf(
				/* translators: 1: post type name (lowercased), 2: import name */
				esc_html__('Delete %1$s created by %2$s','wp-all-import'),
				esc_html(strtolower($cpt_del_name)),
				esc_html(empty($item->friendly_name) ? $item->name : $item->friendly_name)
			);?> </label>
		</div>
		<div class="switcher-target-is_delete_posts" style="padding: 5px 17px;">
			<div class="input">
				<input type="hidden" name="is_delete_images" value="no"/>
				<input type="checkbox" id="is_delete_images" name="is_delete_images" value="yes" />
				<label for="is_delete_images"><?php esc_html_e('Delete associated images from media gallery', 'wp-all-import') ?></label>			
			</div>
			<div class="input">
				<input type="hidden" name="is_delete_attachments" value="no"/>
				<input type="checkbox" id="is_delete_attachments" name="is_delete_attachments" value="yes" />
				<label for="is_delete_attachments"><?php esc_html_e('Delete associated files from media gallery', 'wp-all-import') ?></label>			
			</div>			
		</div>
		<?php if ( ! empty($item->options['deligate']) and $item->options['deligate'] == 'wpallexport' and class_exists('PMXE_Plugin')): ?>
			<?php
				$export = new PMXE_Export_Record();
				$export->getById($item->options['export_id']);
				if ( ! $export->isEmpty() ){
					?>
					<p class="wpallimport-delete-posts-warning"><strong><?php esc_html_e('Important', 'wp-all-import'); ?></strong>: <?php printf(
						/* translators: %s: export friendly name */
						esc_html__('this import was created automatically by WP All Export. All posts exported by the "%s" export job have been automatically associated with this import.', 'wp-all-import'),
						esc_html($export->friendly_name)
					); ?></p>
					<?php
				}
			?>
		<?php endif; ?>		

		<?php
		$cpt_name = '';
		if (!empty($item['options']['custom_type']))
		{
			$custom_type = get_post_type_object( $item['options']['custom_type'] );
			$cpt_name = ( ! empty($custom_type)) ? $custom_type->label : '';
		}		
		?>

		<p class="wp-all-import-sure-to-delete"><?php esc_html_e('Are you sure you want to delete ', 'wp-all-import'); ?><span class="sure_delete_posts"><strong><?php echo esc_html($associated_posts) . ' ' . esc_html($cpt_name); ?></strong></span><span class="sure_delete_posts_and_import"> <?php esc_html_e('and', 'wp-all-import');?> </span><span class="sure_delete_import"><?php echo wp_kses( sprintf(
			/* translators: %s: import name */
			__('the <strong>%s</strong> import', 'wp-all-import'),
			esc_html(empty($item->friendly_name) ? $item->name : $item->friendly_name)
		), array('strong' => array()) );?></span>?</p>
	</div>
	<div class="submit" style="width: 90px;">
		<?php wp_nonce_field('delete-import', '_wpnonce_delete-import') ?>
		<input type="hidden" name="is_confirmed" value="1" />
		<input type="hidden" name="import_ids[]" value="<?php echo esc_attr($item->id); ?>" />
		<input type="hidden" name="base_url" value="<?php echo esc_url($this->baseUrl); ?>">
		<input type="submit" class="button-primary delete-single-import wp_all_import_ajax_deletion" value="Delete" />
		<div class="wp_all_import_functions_preloader"></div>
	</div>
	<div class="wp_all_import_deletion_log"></div>
</form>