<?php if ( ! defined( 'ABSPATH' ) ) exit; // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals ?>
<?php $is_new_import = ($isWizard or $import->imported + $import->skipped == $import->count or $import->imported + $import->skipped == 0 or $import->options['is_import_specified'] or $import->triggered); ?>
<?php $visible_sections = apply_filters('pmxi_visible_confirm_sections', array('data_to_import'), $post['custom_type']); ?>
<h2 class="wpallimport-wp-notices"></h2>

<div class="wpallimport-wrapper wpallimport-step-5">

	<div class="wpallimport-wrapper">
		<div class="wpallimport-header">
			<div class="wpallimport-logo"></div>
			<div class="wpallimport-title">
				<h2><?php esc_html_e('Confirm & Run', 'wp-all-import'); ?></h2>
			</div>
			<?php echo wp_kses_post(apply_filters('wpallimport_links_block', ''));?>
		</div>
		<div class="clear"></div>
	</div>
	<?php
	$is_valid_root_element = true;
	$error_codes = $this->errors->get_error_codes();
	if ( ! empty($error_codes) and is_array($error_codes) and in_array('root-element-validation', $error_codes))
	{
		$is_valid_root_element = false;
	}
	?>
	<div class="ajax-console">
		<?php if ($this->errors->get_error_codes()): ?>
			<?php $this->error() ?>
		<?php endif ?>
		<?php if ($this->warnings->get_error_codes()): ?>
			<?php $this->warning() ?>
		<?php endif ?>

		<?php
			wp_all_import_template_notifications( $post );
		?>
	</div>

	<div class="rad4 first-step-errors error-no-root-element" <?php if ($is_valid_root_element === false):?>style="display:block;"<?php endif; ?>>
		<div class="wpallimport-notify-wrapper">
			<div class="error-headers exclamation">
				<h3><?php esc_html_e('There\'s a problem with your import file', 'wp-all-import');?></h3>
				<h4><?php esc_html_e("It has changed and is not compatible with this import template.", "wp-all-import"); ?></h4>
			</div>
		</div>
		<a class="button wpallimport-large-button wpallimport-notify-read-more" href="http://www.wpallimport.com/documentation/troubleshooting/problems-with-import-files/#invalid?utm_source=import-plugin-free&utm_medium=error&utm_campaign=docs" target="_blank"><?php esc_html_e('Read More', 'wp-all-import');?></a>
	</div>

	<?php $custom_type = get_post_type_object( PMXI_Plugin::$session->custom_type ); ?>

	<div class="wpallimport-content-section" style="padding: 30px; overflow: hidden;">
		<div class="wpallimport-ready-to-go">

			<?php if ($is_new_import):?>
			<h3><?php esc_html_e('Your file is all set up!', 'wp-all-import'); ?></h3>
			<?php else: ?>
			<h3><?php esc_html_e('This import did not finish successfully last time it was run.', 'wp-all-import'); ?></h3>
			<?php endif; ?>

			<?php if ($is_new_import):?>
				<h4><?php esc_html_e('Check the settings below, then click the green button to run the import.', 'wp-all-import'); ?></h4>
			<?php else: ?>
				<h4><?php esc_html_e('You can attempt to continue where it left off.', 'wp-all-import'); ?></h4>
			<?php endif; ?>

		</div>
		<?php if ($is_new_import):?>
			<form class="confirm <?php echo ! $isWizard ? 'edit' : '' ?>" method="post" style="float:right;">
				<?php wp_nonce_field('confirm', '_wpnonce_confirm') ?>
				<input type="hidden" name="is_confirmed" value="1" />
				<input type="submit" class="rad10" value="<?php esc_attr_e('Confirm & Run Import', 'wp-all-import') ?>" />
			</form>
		<?php else: ?>
			<form class="confirm <?php echo ! $isWizard ? 'edit' : '' ?>" method="post" style="float: right;">
				<?php wp_nonce_field('confirm', '_wpnonce_confirm') ?>
				<input type="hidden" name="is_confirmed" value="1" />
				<!--input type="hidden" name="is_continue" value="1" /-->
				<div class="input wpallimport-is-continue">
					<div class="input">
						<input type="radio" name="is_continue" value="yes" checked="checked" id="is_continue_yes"/>
						<label for="is_continue_yes"><?php esc_html_e('Continue from the last run', 'wp-all-import'); ?></label>
					</div>
					<div class="input">
						<input type="radio" name="is_continue" value="no" id="is_continue_no"/>
						<label for="is_continue_no"><?php esc_html_e('Run from the beginning', 'wp-all-import'); ?></label>
					</div>
				</div>
				<input type="submit" class="rad10" value="<?php esc_attr_e('Continue Import', 'wp-all-import') ?>" style="margin-left: 0px; float: right;"/>
				<!--div class="input" style="margin-top:20px;">
					<a href="<?php echo esc_url(add_query_arg(array('id' => $import->id, 'action' => 'update', 'continue' => 'no'), $this->baseUrl)); ?>" id="entire_run"><?php esc_html_e('Run entire import from the beginning', 'wp-all-import'); ?></a>
				</div-->
			</form>
		<?php endif; ?>
	</div>

	<div class="clear"></div>

	<table class="wpallimport-layout confirm">
		<tr>
			<td class="left">

			<?php if ( $is_new_import ):?>

			<?php $max_execution_time = ini_get('max_execution_time');?>

			<div class="wpallimport-section" style="margin-top: -20px;">
				<div class="wpallimport-content-section">
					<div class="wpallimport-collapsed-header" style="padding-left: 30px;">
						<h3 style="color: #425e99;"><?php esc_html_e('Import Summary', 'wp-all-import'); ?> <?php if (!$isWizard):?><span style="color:#000;"><?php /* translators: 1: import ID, 2: import name */ printf(esc_html__(" - ID: %1\$s - %2\$s", 'wp-all-import'), intval($import->id), empty($import->friendly_name) ? esc_html($import->name) : esc_html($import->friendly_name));?></span><?php endif;?></h3>
					</div>
					<div class="wpallimport-collapsed-content" style="padding: 15px 25px 25px;">

						<!-- Warnings -->
						<?php if ($max_execution_time != -1): ?>
						<?php /* translators: %s: max_execution_time in seconds */ ?>
						<p><?php printf(esc_html__('Your max_execution_time is %s seconds', 'wp-all-import'), esc_html($max_execution_time)); ?></p>
						<?php endif;?>

						<!-- General -->
						<?php
							$import_type = (!empty($source['type'])) ? $source['type'] : $import['type'];
							$path = $source['path'];
							if ( in_array($import_type, array('upload', 'file'))){
								$path = wp_all_import_get_absolute_path($source['path']);
							}
							if ( in_array($import_type, array('upload'))){
								$path_parts = pathinfo($source['path']);
								if ( ! empty($path_parts['dirname'])){
									$path_all_parts = explode('/', $path_parts['dirname']);
									$dirname = array_pop($path_all_parts);
									if ( wp_all_import_isValidMd5($dirname)){
										$path = str_replace($dirname, preg_replace('%^(.{3}).*(.{3})$%', '$1***$2', $dirname), str_replace('temp/', '', $path));
									}
								}
							} else{
								$path = str_replace("\\", '/', preg_replace('%^(\w+://[^:]+:)[^@]+@%', '$1*****@', $path));
							}
							if ( in_array($import_type, array('upload', 'file'))){ $path = preg_replace('%.*wp-content/%', 'wp-content/', $path); }
						?>
						<?php /* translators: 1: file path, 2: human readable file size */ ?>
						<p><?php echo wp_kses(sprintf(__('WP All Import will import the file <span style="color:#40acad;">%1$s</span>, which is <span style="color:#000; font-weight:bold;">%2$s</span>', 'wp-all-import'), esc_html($path), (isset($locfilePath)) ? esc_html(pmxi_human_filesize(filesize($locfilePath))) : esc_html__('undefined', 'wp-all-import')), array('span' => array('style' => array()))); ?></p>

						<?php if ( strpos($xpath, '[') !== false){ ?>
						<?php /* translators: %s: XPath expression */ ?>
						<p><?php echo wp_kses(sprintf(__('WP All Import will process the records matching the XPath expression: <span style="color:#46ba69; font-weight:bold;">%s</span>', 'wp-all-import'), esc_html($xpath)), array('span' => array('style' => array()))); ?></p>
						<?php } elseif ($post['delimiter'] and $isWizard ) { ?>
						<?php /* translators: %s: number of rows */ ?>
						<p><?php echo wp_kses(sprintf(__('WP All Import will process <span style="color:#46ba69; font-weight:bold;">%s</span> rows in your file', 'wp-all-import'), intval($count)), array('span' => array('style' => array()))); ?></p>
						<?php } elseif ( $isWizard ) { ?>
						<?php /* translators: 1: number of records, 2: root element name */ ?>
						<p><?php echo wp_kses(sprintf(__('WP All Import will process all %1$s <span style="color:#46ba69; font-weight:bold;">&lt;%2$s&gt;</span> records in your file', 'wp-all-import'), intval($count), esc_html($source['root_element'])), array('span' => array('style' => array()))); ?></p>
						<?php } ?>

						<?php if ( $post['is_import_specified']): ?>
						<?php /* translators: %s: comma separated list of record indexes */ ?>
						<p><?php printf(esc_html__('WP All Import will process only specified records: %s', 'wp-all-import'), esc_html($post['import_specified'])); ?></p>
						<?php endif;?>

						<!-- Record Matching -->
						<?php $custom_type = get_post_type_object( $post['custom_type'] ); ?>

						<?php if ( "new" == $post['wizard_type']): ?>

							<?php /* translators: %s: unique key value */ ?>
							<p><?php echo wp_kses(sprintf(__('Your unique key is <span style="color:#000; font-weight:bold;">%s</span>', 'wp-all-import'), esc_html(wp_all_import_clear_xss($post['unique_key']))), array('span' => array('style' => array()))); ?></p>

							<?php if ( ! $isWizard and !empty($custom_type)): ?>

								<?php /* translators: 1: singular post type name, 2: import ID */ ?>
								<p><?php printf(esc_html__('%1$ss previously imported by this import (ID: %2$s) with the same unique key will be updated.', 'wp-all-import'), esc_html($custom_type->labels->singular_name), esc_html($import->id)); ?></p>

								<?php if ( $post['is_delete_missing'] and $post['delete_missing_action'] != 'keep' and ! $post['is_update_missing_cf'] and ! $post['is_change_post_status_of_removed']): ?>
									<?php /* translators: 1: singular post type name, 2: import ID */ ?>
									<p><?php printf(esc_html__('%1$ss previously imported by this import (ID: %2$s) that aren\'t present for this run of the import will be deleted.', 'wp-all-import'), esc_html($custom_type->labels->singular_name), esc_html($import->id)); ?></p>
								<?php endif; ?>

								<?php if ( $post['is_delete_missing'] and $post['delete_missing_action'] == 'keep' and $post['is_change_post_status_of_removed']): ?>
									<?php /* translators: 1: singular post type name, 2: import ID */ ?>
									<p><?php printf(esc_html__('%1$ss previously imported by this import (ID: %2$s) that aren\'t present for this run of the import will be set to draft.', 'wp-all-import'), esc_html($custom_type->labels->singular_name), esc_html($import->id)); ?></p>
								<?php endif; ?>

								<?php if ( $post['create_new_records']): ?>
									<?php /* translators: 1: singular post type name, 2: import ID */ ?>
									<p><?php printf(esc_html__('Records with unique keys that don\'t match any unique keys from %1$ss created by previous runs of this import (ID: %2$s) will be created.', 'wp-all-import'), esc_html($custom_type->labels->singular_name), esc_html($import->id)); ?></p>
								<?php endif; ?>

							<?php endif; ?>

						<?php else: ?>

							<?php
							$criteria = '';
							if ( 'pid' == $post['duplicate_indicator']) $criteria = 'has the same ID';
							if ( 'title' == $post['duplicate_indicator']){
								switch ($post['custom_type']){
									case 'import_users':
									case 'shop_customer':
										$criteria = 'has the same Login';
										break;
									default:
										$criteria = 'has the same Title';
										break;
								}
							}
							if ( 'content' == $post['duplicate_indicator']){
								switch ($post['custom_type']){
									case 'import_users':
									case 'shop_customer':
										$criteria = 'has the same Email';
										break;
									default:
										$criteria = 'has the same Content';
										break;
								}
							}
							if ( 'custom field' == $post['duplicate_indicator']) $criteria = 'has Custom Field named "'. $post['custom_duplicate_name'] .'" with value = ' . $post['custom_duplicate_value'];
							?>
							<?php /* translators: 1: singular post type name, 2: match criteria description */ ?>
							<p><?php printf(esc_html__('WP All Import will merge data into existing %1$ss, matching the following criteria: %2$s', 'wp-all-import'), esc_html($custom_type->labels->singular_name), esc_html($criteria)); ?></p>

							<?php if ( "no" == $post['is_keep_former_posts'] and "yes" == $post['update_all_data']){ ?>
							<p><?php esc_html_e('Existing data will be updated with the data specified in this import.', 'wp-all-import'); ?></p>
							<?php } elseif ("no" == $post['is_keep_former_posts'] and "no" == $post['update_all_data']){?>
							<div>
								<?php /* translators: %s: singular post type name */ ?>
								<p><?php echo wp_kses(sprintf(__('Next %s data will be updated, <strong>all other data will be left alone</strong>', 'wp-all-import'), esc_html($custom_type->labels->singular_name)), array('strong' => array())); ?></p>
								<?php if ( in_array('data_to_import', $visible_sections)):?>
								<ul style="padding-left: 35px;">
									<?php if ( $post['is_update_status']): ?>
									<li> <?php esc_html_e('status', 'wp-all-import'); ?></li>
									<?php endif; ?>
									<?php if ( $post['is_update_title']): ?>
									<li> <?php esc_html_e('title', 'wp-all-import'); ?></li>
									<?php endif; ?>
									<?php if ( $post['is_update_slug']): ?>
									<li> <?php esc_html_e('slug', 'wp-all-import'); ?></li>
									<?php endif; ?>
									<?php if ( $post['is_update_content']): ?>
									<li> <?php esc_html_e('content', 'wp-all-import'); ?></li>
                                    <?php endif; ?>
                                    <?php if ( $post['is_update_author']): ?>
                                        <li> <?php esc_html_e('author', 'wp-all-import'); ?></li>
                                    <?php endif; ?>
                                    <?php if ( $post['is_update_comment_status']): ?>
                                        <li> <?php esc_html_e('comment status', 'wp-all-import'); ?></li>
                                    <?php endif; ?>
                                    <?php if ( $post['is_update_post_format']): ?>
                                        <li> <?php esc_html_e('post format', 'wp-all-import'); ?></li>
                                    <?php endif; ?>
									<?php if ( $post['is_update_excerpt']): ?>
									<li> <?php esc_html_e('excerpt', 'wp-all-import'); ?></li>
									<?php endif; ?>
									<?php if ( $post['is_update_dates']): ?>
									<li> <?php esc_html_e('dates', 'wp-all-import'); ?></li>
									<?php endif; ?>
									<?php if ( $post['is_update_menu_order']): ?>
									<li> <?php esc_html_e('menu order', 'wp-all-import'); ?></li>
									<?php endif; ?>
									<?php if ( $post['is_update_parent']): ?>
									<li> <?php esc_html_e('parent post', 'wp-all-import'); ?></li>
									<?php endif; ?>
									<?php if ( $post['is_update_post_type']): ?>
									<li> <?php esc_html_e('post type', 'wp-all-import'); ?></li>
									<?php endif; ?>
									<?php if ( $post['is_update_attachments']): ?>
									<li> <?php esc_html_e('attachments', 'wp-all-import'); ?></li>
									<?php endif; ?>
									<?php if ( ! empty($post['is_update_acf'])): ?>
										<li>
										<?php
										switch($post['update_acf_logic']){
											case 'full_update':
												esc_html_e('all advanced custom fields', 'wp-all-import');
												break;
											case 'mapped':
												esc_html_e('only ACF presented in import options', 'wp-all-import');
												break;
											case 'only':
												/* translators: %s: comma separated list of ACF names */
												printf(esc_html__('only these ACF : %s', 'wp-all-import'), esc_html($post['acf_only_list']));
												break;
											case 'all_except':
												/* translators: %s: comma separated list of ACF names */
												printf(esc_html__('all ACF except these: %s', 'wp-all-import'), esc_html($post['acf_except_list']));
												break;
										} ?>
										</li>
									<?php endif; ?>
									<?php if ( ! empty($post['is_update_images'])): ?>
										<li>
										<?php
										switch($post['update_images_logic']){
											case 'full_update':
												esc_html_e('old images will be updated with new', 'wp-all-import');
												break;
											case 'add_new':
												esc_html_e('only new images will be added', 'wp-all-import');
												break;
										} ?>
										</li>
									<?php endif; ?>
									<?php if ( ! empty($post['is_update_custom_fields'])): ?>
										<li>
										<?php
										switch($post['update_custom_fields_logic']){
											case 'full_update':
												esc_html_e('all custom fields', 'wp-all-import');
												break;
											case 'only':
												/* translators: %s: comma separated list of custom field names */
												printf(esc_html__('only these custom fields : %s', 'wp-all-import'), esc_html($post['custom_fields_only_list']));
												break;
											case 'all_except':
												/* translators: %s: comma separated list of custom field names */
												printf(esc_html__('all custom fields except these: %s', 'wp-all-import'), esc_html($post['custom_fields_except_list']));
												break;
										} ?>
										</li>
									<?php endif; ?>
									<?php if ( ! empty($post['is_update_categories'])): ?>
										<li>
										<?php
										switch($post['update_categories_logic']){
											case 'full_update':
												esc_html_e('remove existing taxonomies, add new taxonomies', 'wp-all-import');
												break;
											case 'add_new':
												esc_html_e('only add new', 'wp-all-import');
												break;
											case 'only':
												/* translators: %s: comma separated list of taxonomy names */
												printf(esc_html__('update only these taxonomies: %s , leave the rest alone', 'wp-all-import'), esc_html($post['taxonomies_only_list']));
												break;
											case 'all_except':
												/* translators: %s: comma separated list of taxonomy names */
												printf(esc_html__('leave these taxonomies: %s alone, update all others', 'wp-all-import'), esc_html($post['taxonomies_except_list']));
												break;
										}
										if(!empty($post['do_not_create_terms']))
											esc_html_e(' - no new terms will be created', 'wp-all-import');
										?>
										</li>
									<?php endif; ?>
								</ul>
								<?php endif; ?>
								<?php do_action('pmxi_confirm_data_to_import', $isWizard, $post);?>
							</div>
							<?php } ?>
							<?php if ( $post['create_new_records']): ?>
							<?php /* translators: %s: singular post type name */ ?>
							<p><?php printf(esc_html__('New %ss will be created from records that don\'t match the above criteria.', 'wp-all-import'), esc_html($custom_type->labels->singular_name)); ?></p>
							<?php endif; ?>
						<?php endif; ?>

						<!-- Import Performance -->
						<?php if ( "default" == $post['import_processing']): ?>
						<p><?php esc_html_e('High-Speed, Small File Processing enabled. Your import will fail if it takes longer than your server\'s max_execution_time.', 'wp-all-import'); ?></p>
						<?php else: ?>
						<?php /* translators: 1: records per request, 2: records per request */ ?>
						<p><?php printf(esc_html__('Piece By Piece Processing enabled. %1$s records will be processed each iteration. If it takes longer than your server\'s max_execution_time to process %2$s records, your import will fail.', 'wp-all-import'), esc_html($post['records_per_request']), esc_html($post['records_per_request'])); ?></p>
						<?php endif; ?>

						<?php if ($post['chuncking'] and "default" != $post['import_processing']):?>
						<?php /* translators: %s: chunk size (number of records) */ ?>
						<p><?php printf(esc_html__('Your file will be split into %s records chunks before processing.', 'wp-all-import'), esc_html(PMXI_Plugin::getInstance()->getOption('large_feed_limit'))); ?></p>
						<?php endif; ?>

						<?php if ($post['is_fast_mode']):?>
						<p><?php esc_html_e('do_action calls will be disabled in wp_insert_post and wp_insert_attachment during the import.', 'wp-all-import'); ?></p>
						<?php endif; ?>

					</div>
				</div>
			</div>

			<?php endif; ?>

			</td>
		</tr>
	</table>
	<?php if ($is_new_import):?>
	<form id="wpai-submit-confirm-form" class="confirm <?php echo ! $isWizard ? 'edit' : '' ?>" method="post">
		<?php wp_nonce_field('confirm', '_wpnonce_confirm') ?>
		<input type="hidden" name="is_confirmed" value="1" />
		<input type="submit" class="rad10" value="<?php esc_attr_e('Confirm & Run Import', 'wp-all-import') ?>" />
		<p>
		<?php if ($isWizard): ?>
			<a href="<?php echo esc_url(apply_filters('pmxi_options_back_link', add_query_arg('action', 'options', $this->baseUrl), $isWizard)); ?>"><?php esc_html_e('or go back to Step 4', 'wp-all-import') ?></a>
		<?php else:?>
			<a href="<?php echo esc_url(apply_filters('pmxi_options_back_link', remove_query_arg('id', remove_query_arg('action', $this->baseUrl)), $isWizard)); ?>"><?php esc_html_e('or go back to Manage Imports', 'wp-all-import') ?></a>
		<?php endif; ?>
		</p>
	</form>
	<?php endif; ?>

    <div class="wpallimport-display-columns wpallimport-margin-top-forty">
		<?php echo wp_kses_post(apply_filters('wpallimport_footer', '')); ?>
    </div>

</div>
