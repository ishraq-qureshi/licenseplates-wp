<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php if ( ! $isWizard): ?>
<!--p class="note" style="float:left; margin-top:30px;"><?php esc_html_e('To run the import, click Run Import on the Manage Imports page.', 'wp-all-import'); ?></p-->
<?php endif; ?>
<p class="wpallimport-submit-buttons">
	<?php wp_nonce_field('options', '_wpnonce_options') ?>
	<input type="hidden" name="is_submitted" value="1" />
	<input type="hidden" name="converted_options" value="1"/>
	
	<?php if ($isWizard): ?>

		<a href="<?php echo esc_url(apply_filters('pmxi_options_back_link', add_query_arg('action', 'template', $this->baseUrl), $isWizard)); ?>" class="back rad3"><?php esc_html_e('Back to Step 3', 'wp-all-import') ?></a>

		<?php if (isset($source_type) and in_array($source_type, array('url', 'ftp', 'file'))): ?>
			<!--input type="hidden" class="save_only" value="0" name="save_only"/-->
			<input type="submit" name="save_only" class="button wpallimport-large-button" value="<?php esc_html_e('Save Only', 'wp-all-import') ?>" style="background:#425f9a;"/>
		<?php endif ?>

		<input type="submit" class="button wpallimport-large-button" value="<?php esc_html_e('Continue', 'wp-all-import') ?>" />		

	<?php else: ?>		
		<a href="<?php echo esc_url(apply_filters('pmxi_options_back_link', remove_query_arg('id', remove_query_arg('action', $this->baseUrl)), $isWizard)); ?>" class="back rad3"><?php esc_html_e('Back to Manage Imports', 'wp-all-import') ?></a>
		<input type="submit" class="button wpallimport-large-button" value="<?php esc_html_e('Save Import Configuration', 'wp-all-import') ?>" />
	<?php endif ?>
</p>