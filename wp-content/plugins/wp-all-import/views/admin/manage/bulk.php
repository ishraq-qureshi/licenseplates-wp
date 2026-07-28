<?php if ( ! defined( 'ABSPATH' ) ) exit; // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals ?>
<h2><?php esc_html_e('Bulk Delete Imports', 'wp-all-import');?></h2>

<form method="post">
	<input type="hidden" name="action" value="bulk" />
	<input type="hidden" name="bulk-action" value="<?php echo esc_attr($action) ?>" />
	<?php foreach ($ids as $id): ?>
		<input type="hidden" name="items[]" value="<?php echo esc_attr($id) ?>" />
	<?php endforeach ?>
	
	<p><?php echo wp_kses( sprintf(
		/* translators: 1: number of selected imports, 2: singular or plural label */
		__('Are you sure you want to delete <strong>%1$s</strong> selected %2$s?', 'wp-all-import'),
		intval($items->count()),
		esc_html(_n('import', 'imports', $items->count(), 'wp-all-import'))
	), array('strong' => array()) ); ?></p>
	<div class="input">
		<input type="checkbox" id="is_delete_posts" name="is_delete_posts" class="switcher"/> <label for="is_delete_posts"><?php esc_html_e('Delete associated posts as well','wp-all-import');?> </label>
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
		<?php foreach($items->convertRecords() as $item) : ?>
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
		<?php endforeach; ?>
	</div>
	
	<p class="submit">
		<?php wp_nonce_field('bulk-imports', '_wpnonce_bulk-imports') ?>
		<input type="hidden" name="is_confirmed" value="1" />
		<?php foreach ($ids as $id): ?>
			<input type="hidden" name="import_ids[]" value="<?php echo esc_attr($id) ?>" />
		<?php endforeach ?>
		<input type="submit" class="button-primary" value="Delete" />
	</p>
</form>