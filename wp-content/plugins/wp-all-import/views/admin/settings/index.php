<?php if ( ! defined( 'ABSPATH' ) ) exit; // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals ?>
<form class="settings" method="post" action="" enctype="multipart/form-data">

	<div class="wpallimport-header">
		<div class="wpallimport-logo"></div>
		<div class="wpallimport-title">
			<h3><?php esc_html_e('Settings', 'wp-all-import'); ?></h3>			
		</div>	
	</div>

	<h2 style="padding:0px;"></h2>
	
	<div class="wpallimport-setting-wrapper">
		<?php if ($this->errors->get_error_codes()): ?>
			<?php $this->error() ?>
		<?php endif ?>
		
		<h3><?php esc_html_e('Import/Export Templates', 'wp-all-import') ?></h3>
		<?php $templates = new PMXI_Template_List(); $templates->getBy()->convertRecords() ?>
		<?php wp_nonce_field('delete-templates', '_wpnonce_delete-templates') ?>		
		<?php if ($templates->total()): ?>
			<table>
				<?php foreach ($templates as $t): ?>
					<tr>
						<td>
							<label class="selectit" for="template-<?php echo esc_attr($t->id); ?>"><input id="template-<?php echo esc_attr($t->id); ?>" type="checkbox" name="templates[]" value="<?php echo esc_attr($t->id); ?>" /> <?php echo esc_html($t->name); ?></label>
						</td>				
					</tr>
				<?php endforeach ?>
			</table>
			<p class="submit-buttons">				
				<input type="submit" class="button-primary" name="delete_templates" value="<?php esc_html_e('Delete Selected', 'wp-all-import') ?>" />
				<input type="submit" class="button-primary" name="export_templates" value="<?php esc_html_e('Export Selected', 'wp-all-import') ?>" />
			</p>	
		<?php else: ?>
			<em><?php esc_html_e('There are no templates saved', 'wp-all-import') ?></em>
		<?php endif ?>
		<p>
			<input type="hidden" name="is_templates_submitted" value="1" />
			<input type="file" name="template_file"/>
			<input type="submit" class="button-primary" name="import_templates" value="<?php esc_html_e('Import Templates', 'wp-all-import') ?>" />
		</p>
	</div>

</form>

<form name="settings" method="post" action="" class="settings">	

	<h3><?php esc_html_e('Files', 'wp-all-import') ?></h3>
	
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row"><label><?php esc_html_e('Secure Mode', 'wp-all-import'); ?></label></th>
				<td>
					<fieldset style="padding:0;">
						<legend class="screen-reader-text"><span><?php esc_html_e('Secure Mode', 'wp-all-import'); ?></span></legend>
						<input type="hidden" name="secure" value="0"/>
						<label for="secure"><input type="checkbox" value="1" id="secure" name="secure" <?php echo (($post['secure']) ? 'checked="checked"' : ''); ?>><?php esc_html_e('Randomize folder names', 'wp-all-import'); ?></label>																				
					</fieldset>														
					<p class="description">
						<?php
							$wp_uploads = wp_upload_dir();
						?>
						<?php printf(
							/* translators: %s: uploads directory path */
							esc_html__('Imported files, chunks, logs and temporary files will be placed in a folder with a randomized name inside of %s.', 'wp-all-import'),
							esc_html($wp_uploads['basedir'] . '/wpallimport' )
						); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label><?php esc_html_e('Log Storage', 'wp-all-import'); ?></label></th>
				<td>
					<input type="text" class="regular-text" name="log_storage" value="<?php echo esc_attr($post['log_storage']); ?>"/>
					<p class="description"><?php esc_html_e('Number of logs to store for each import. Enter 0 to never store logs.', 'wp-all-import'); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label><?php esc_html_e('Clean Up Temp Files', 'wp-all-import'); ?></label></th>
				<td>
					<a class="button-primary wpallimport-clean-up-tmp-files" href="<?php echo esc_url(add_query_arg(array('action' => 'cleanup', '_wpnonce' => wp_create_nonce( '_wpnonce-cleanup_logs' )), $this->baseUrl)); ?>"><?php esc_html_e('Clean Up', 'wp-all-import'); ?></a>
					<p class="description"><?php esc_html_e('Attempt to remove temp files left over by imports that were improperly terminated.', 'wp-all-import'); ?></p>
				</td>
			</tr>
		</tbody>
	</table>	

	<div class="clear"></div>

	<h3><?php esc_html_e('Advanced Settings', 'wp-all-import') ?></h3>
	
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row"><label><?php esc_html_e('Chunk Size', 'wp-all-import'); ?></label></th>
				<td>
					<input type="text" class="regular-text" name="large_feed_limit" value="<?php echo esc_attr($post['large_feed_limit']); ?>"/>
					<p class="description"><?php esc_html_e('Split file into chunks containing the specified number of records.', 'wp-all-import'); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label><?php esc_html_e('WP_IMPORTING', 'wp-all-import'); ?></label></th>
				<td>
					<fieldset style="padding:0;">
						<legend class="screen-reader-text"><span>Membership</span></legend>
						<input type="hidden" name="pingbacks" value="0"/>
						<label for="pingbacks"><input type="checkbox" value="1" id="pingbacks" name="pingbacks" <?php echo (($post['pingbacks']) ? 'checked="checked"' : ''); ?>><?php esc_html_e('Enable WP_IMPORTING', 'wp-all-import'); ?></label>																				
					</fieldset>														
					<p class="description"><?php esc_html_e('Setting this constant avoids triggering pingback.', 'wp-all-import'); ?></p>
				</td>
			</tr>		
			<tr>
				<th scope="row"><label><?php esc_html_e('Add Port To URL', 'wp-all-import'); ?></label></th>
				<td>
					<input type="text" class="regular-text" name="port" value="<?php echo esc_attr($post['port']); ?>"/>
					<p class="description"><?php esc_html_e('Specify the port number to add if you\'re having problems continuing to Step 2 and are running things on a custom port. Default is blank.', 'wp-all-import'); ?></p>
				</td>
			</tr>	
		</tbody>
	</table>	

	<h3><?php esc_html_e('Force Stream Reader', 'wp-all-import') ?></h3>
	
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row"><label><?php esc_html_e('Force WP All Import to use StreamReader instead of XMLReader to parse all import files', 'wp-all-import'); ?></label></th>
				<td>
					<fieldset style="padding:0;">						
						<input type="hidden" name="force_stream_reader" value="0"/>
						<label for="force_stream_reader"><input type="checkbox" value="1" id="force_stream_reader" name="force_stream_reader" <?php echo (($post['force_stream_reader']) ? 'checked="checked"' : ''); ?>><?php esc_html_e('Enable Stream Reader', 'wp-all-import'); ?></label>																				
					</fieldset>					
					<p class="description"><?php esc_html_e('XMLReader is much faster, but has a bug that sometimes prevents certain records from being imported with import files that contain special cases.', 'wp-all-import'); ?></p>
					<p class="submit-buttons">
						<?php wp_nonce_field('edit-settings', '_wpnonce_edit-settings') ?>
						<input type="hidden" name="is_settings_submitted" value="1" />
						<input type="submit" class="button-primary" value="Save Settings" />
					</p>
				</td>
			</tr>						
		</tbody>
	</table>			
</form>

<?php
	$uploads = wp_upload_dir();
	$functions = $uploads['basedir'] . DIRECTORY_SEPARATOR . WP_ALL_IMPORT_UPLOADS_BASE_DIRECTORY . DIRECTORY_SEPARATOR . 'functions.php';	
?>
<hr />
<br>
<h3><?php esc_html_e('Function Editor', 'wp-all-import') ?></h3>
<div class="wpallimport-free-edition-notice" style="text-align:center; margin-top:0; margin-bottom: 40px;">
	<a href="https://www.wpallimport.com/checkout/?edd_action=add_to_cart&download_id=5839966&edd_options%5Bprice_id%5D=1&discount=welcome-upgrade-99&utm_source=import-plugin-free&utm_medium=upgrade-notice&utm_campaign=function-editor" target="_blank" class="upgrade_link"><?php esc_html_e('Upgrade to the Pro edition of WP All Import to use the Function Editor.', 'wp-all-import');?></a>
	<p><?php esc_html_e('If you already own it, remove the free edition and install the Pro edition.', 'wp-all-import'); ?></p>
</div>
<textarea id="wp_all_import_code" name="wp_all_import_code"><?php echo "<?php\n\n?>";?></textarea>						

<div class="input" style="margin-top: 10px;">

	<div class="input" style="display:inline-block; margin-right: 20px;">
		<input type="button" class="button-primary wp_all_import_save_functions" disabled="disabled" value="<?php esc_attr_e("Save Functions", 'wp-all-import'); ?>"/>
		<a href="#help" class="wpallimport-help" title="<?php printf(
			/* translators: %s: path to functions file */
			esc_attr__("Add functions here for use during your import. You can access this file at %s", "wp-all-import"),
			esc_attr(preg_replace("%.*wp-content%", "wp-content", $functions))
		);?>" style="top: 0;">?</a>
		<div class="wp_all_import_functions_preloader"></div>
	</div>						
	<div class="input wp_all_import_saving_status" style="display:inline-block;">

	</div>

</div>

<div class="wpallimport-display-columns wpallimport-margin-top-forty">
	<?php echo wp_kses_post( apply_filters('wpallimport_footer', '') ); ?>
</div>