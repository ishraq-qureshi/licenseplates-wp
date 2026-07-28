<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<h2><?php esc_html_e('Update Import', 'wp-all-import') ?></h2>

<?php if ($this->errors->get_error_codes()): ?>
		<?php $this->error() ?>
<?php endif ?>

<?php if ($item->path): ?>
	<form method="post">
		<p><?php echo wp_kses( sprintf(
			/* translators: %s: import name */
			__('Are you sure you want to update <strong>%s</strong> import?', 'wp-all-import'),
			esc_html($item->name)
		), array('strong' => array()) ); ?></p>
		<p><?php echo wp_kses( sprintf(
			/* translators: %s: source path */
			__('Source path is <strong>%s</strong>', 'wp-all-import'),
			esc_html($item->path)
		), array('strong' => array()) ); ?></p>
		
		<p class="submit">
			<?php wp_nonce_field('update-import', '_wpnonce_update-import') ?>
			<input type="hidden" name="is_confirmed" value="1" />
			<input type="submit" class="button-primary ajax-update" value="Create Posts" />
		</p>
		
	</form>
<?php else: ?>
	<div class="error">
		<p><?php esc_html_e('Update feature is not available for this import since it has no external path linked.', 'wp-all-import') ?></p>
	</div>
<?php endif ?>