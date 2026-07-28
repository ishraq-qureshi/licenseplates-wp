<?php if ( ! defined( 'ABSPATH' ) ) exit; // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals ?>
<h2></h2> <!-- Do not remove -->

<?php
	// notify user
	if (!PMXI_Plugin::getInstance()->getOption('dismiss_manage_bottom')) {
		?>
		<div class="updated_bottom">
			<?php echo wp_kses(
					__('<span><a href="https://wordpress.org/support/view/plugin-reviews/wp-all-import#postform" target="_blank">If our plugin helped you, please rate us on WordPress.org. It would really help us!</a><a href="https://wordpress.org/support/view/plugin-reviews/wp-all-import#postform" class="pmxi_stars" target="_blank"></a></span><a href="javascript:void(0);" id="dismiss_manage_bottom">dismiss</a>', 'wp-all-import'),
					array('span' => array(), 'a' => array('href' => array(), 'target' => array(), 'class' => array(), 'id' => array()))
			) ?>
		</div>
		<?php
	}
?>


<div class="wpallimport-header" style="overflow:hidden; height: 70px; padding-top: 10px; margin-bottom: -15px;">
	<div class="wpallimport-logo"></div>
	<div class="wpallimport-title">
		<h3><?php esc_html_e('Manage Imports', 'wp-all-import'); ?></h3>
	</div>
</div>

<?php if ($this->errors->get_error_codes()): ?>
	<?php $this->error() ?>
<?php endif ?>

<form method="get">
	<input type="hidden" name="page" value="<?php echo esc_attr($this->input->get('page')) ?>" />
	<p class="search-box">
		<label for="search-input" class="screen-reader-text"><?php esc_html_e('Search Imports', 'wp-all-import') ?>:</label>
		<input id="search-input" type="text" name="s" value="<?php echo esc_attr($s) ?>" />
		<input type="submit" class="button" value="<?php esc_html_e('Search Imports', 'wp-all-import') ?>">
	</p>
</form>

<?php
// define the columns to display, the syntax is 'internal name' => 'display name'
$columns = array(
	'id'		=> __('ID', 'wp-all-import'),
	'name'		=> __('File', 'wp-all-import'),
	'actions'	=> '',
	'summary'	=> __('Summary', 'wp-all-import'),
	'info'		=> __('Info & Options', 'wp-all-import'),
);

$columns = apply_filters('pmxi_manage_imports_columns', $columns);

?>
<form method="post" id="import-list" action="<?php echo esc_url(remove_query_arg('pmxi_nt')); ?>">
	<input type="hidden" name="action" value="bulk" />
	<?php wp_nonce_field('bulk-imports', '_wpnonce_bulk-imports') ?>

	<div class="tablenav">
		<div class="alignleft actions">
			<select name="bulk-action">
				<option value="" selected="selected"><?php esc_html_e('Bulk Actions', 'wp-all-import') ?></option>
				<option value="delete"><?php esc_html_e('Delete', 'wp-all-import') ?></option>
			</select>
			<input type="submit" value="<?php esc_attr_e('Apply', 'wp-all-import') ?>" name="doaction" id="doaction" class="button-secondary action" />
		</div>

		<?php if ($page_links): ?>
			<div class="tablenav-pages">
				<?php $page_links_html = sprintf(
					/* translators: 1: from number, 2: to number, 3: total count, 4: page links HTML */
					'<span class="displaying-num">' . esc_html__('Displaying %1$s&#8211;%2$s of %3$s', 'wp-all-import') . '</span>%s',
					esc_html(number_format_i18n(($pagenum - 1) * $perPage + 1)),
					esc_html(number_format_i18n(min($pagenum * $perPage, $list->total()))),
					esc_html(number_format_i18n($list->total())),
					wp_kses_post($page_links)
				); echo wp_kses_post($page_links_html); ?>
			</div>
		<?php endif ?>
	</div>
	<div class="clear"></div>

	<table class="widefat pmxi-admin-imports">
		<thead>
		<tr>
			<th class="manage-column column-cb check-column" scope="col">
				<input type="checkbox" />
			</th>
			<?php
			$col_html = '';
			foreach ($columns as $column_id => $column_display_name) {
				if (in_array($column_id, array('id', 'name'))){
					$column_link = "<a href='";
					$order2 = 'ASC';
					if ($order_by == $column_id)
						$order2 = ($order == 'DESC') ? 'ASC' : 'DESC';

					$column_link .= esc_url(add_query_arg(array('order' => $order2, 'order_by' => $column_id), $this->baseUrl));
					$column_link .= "'>{$column_display_name}</a>";
					$col_html .= '<th scope="col" class="column-' . $column_id . ' ' . ($order_by == $column_id ? $order : '') . '">' . $column_link . '</th>';
				}
				else $col_html .= '<th scope="col" class="column-' . $column_id . '">' . $column_display_name . '</th>';
			}
			echo wp_kses_post($col_html);
			?>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<th class="manage-column column-cb check-column" scope="col">
				<input type="checkbox" />
			</th>
			<?php echo wp_kses_post($col_html); ?>
		</tr>
		</tfoot>
		<tbody id="the-pmxi-admin-import-list" class="list:pmxi-admin-imports">
		<?php if ($list->isEmpty()): ?>
			<tr>
				<td colspan="<?php echo intval(count($columns) + 1) ?>"><?php echo wp_kses( sprintf(
					/* translators: %s: URL to start a new import */
					__('No previous imports found. <a href="%s">Start a new import...</a>', 'wp-all-import'),
					esc_url(add_query_arg(array('page' => 'pmxi-admin-import'), admin_url('admin.php')))
				), array('a' => array('href' => array())) ); ?></td>
			</tr>
		<?php else: ?>
			<?php
			$class = '';
			?>
			<?php foreach ($list as $item): ?>
				<?php $class = ('alternate' == $class) ? '' : 'alternate'; ?>
				<tr class="<?php echo esc_attr($class); ?>" valign="middle">
					<th scope="row" class="check-column">
						<input type="checkbox" id="item_<?php echo esc_attr($item['id']) ?>" name="items[]" value="<?php echo esc_attr($item['id']) ?>" />
					</th>
					<?php foreach ($columns as $column_id => $column_display_name): ?>
						<?php
						switch ($column_id):
							case 'id':
								?>
								<th valign="top" scope="row">
									<?php echo esc_html($item['id']) ?>
								</th>
								<?php
								break;
							case 'first_import':
								?>
								<td>
									<?php if ('0000-00-00 00:00:00' == $item['first_import']): ?>
										<em>never</em>
									<?php else: ?>
										<?php echo esc_html(get_date_from_gmt($item['first_import'], 'Y/m/d g:i a')); ?>
									<?php endif ?>
								</td>
								<?php
								break;
							case 'registered_on':
								?>
								<td>
									<?php if ('0000-00-00 00:00:00' == $item['registered_on']): ?>
										<em>never</em>
									<?php else: ?>
										<?php echo esc_html(get_date_from_gmt($item['registered_on'], 'Y/m/d g:i a')); ?>
									<?php endif ?>
								</td>
								<?php
								break;
							case 'name':
								?>
								<td>
									<strong><?php echo esc_html(apply_filters("pmxi_import_name", (!empty($item['friendly_name'])) ? $item['friendly_name'] : $item['name'], $item['id'])); ?></strong><br>

									<?php if ($item['path']): ?>
										<?php if ( in_array($item['type'], array('upload'))): ?>
											<?php $item['path'] = wp_all_import_get_absolute_path($item['path']); ?>
											<?php
											$path = $item['path'];
											$path_parts = pathinfo($item['path']);
											if ( ! empty($path_parts['dirname'])){
												$path_all_parts = explode('/', $path_parts['dirname']);
												$dirname = array_pop($path_all_parts);
												if ( wp_all_import_isValidMd5($dirname)){
													$path = str_replace($dirname, preg_replace('%^(.{3}).*(.{3})$%', '$1***$2', $dirname), str_replace('temp/', '', $item['path']));
												}
											}
											?>
											<em><a href="<?php echo esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'feed', '_wpnonce' => wp_create_nonce( '_wpnonce-download_feed' )), $this->baseUrl)); ?>" class="wp_all_import_show_path" rel="<?php echo esc_attr($item['path']); ?>"><?php echo esc_html(preg_replace('%.*wp-content/%', 'wp-content/', $path)); ?></a></em>
										<?php elseif (in_array($item['type'], array('file'))):?>
											<?php $item['path'] = wp_all_import_get_absolute_path($item['path']); ?>
											<em><a href="<?php echo esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'feed', '_wpnonce' => wp_create_nonce( '_wpnonce-download_feed' )), $this->baseUrl)); ?>" class="wp_all_import_show_path" rel="<?php echo esc_attr($item['path']); ?>"><?php echo esc_html(preg_replace('%.*wp-content/%', 'wp-content/', $item['path'])); ?></a></em>
										<?php else: ?>
										<em><?php echo esc_html(str_replace("\\", '/', preg_replace('%^(\w+://[^:]+:)[^@]+@%', '$1*****@', $item['path']))); ?></em>
										<?php endif; ?>
									<?php endif ?>
									<div class="row-actions">
										<?php do_action('pmxi_import_menu', $item['id'], $this->baseUrl); ?>
										<?php
											$import_actions = array(
												'import_template' => array(
													'url' => ( ! $item['processing'] and ! $item['executing'] ) ? esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'edit'), $this->baseUrl)) : '',
													'title' => __('Edit Template', 'wp-all-import'),
													'class' => 'edit'
												),
												'import_settings' => array(
													'url' => ( ! $item['processing'] and ! $item['executing'] ) ? esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'options'), $this->baseUrl)) : '',
													'title' => __('Settings', 'wp-all-import'),
													'class' => 'edit'
												),
												'delete' => array(
													'url' => esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'delete'), $this->baseUrl)),
													'title' => __('Delete', 'wp-all-import'),
													'class' => 'delete'
												),
											);

											$import_actions = apply_filters('pmxi_import_actions', $import_actions, $item );

											$ai = 1;
											foreach ($import_actions as $key => $action) {
												switch ($key) {
													default:
														?>
														<span class="<?php echo esc_attr($action['class']); ?>">
															<?php if ( ! empty($action['url']) ): ?>
															<a class="<?php echo esc_attr($action['class']); ?>" href="<?php echo esc_url($action['url']); ?>"><?php echo esc_html($action['title']); ?></a>
															<?php else: ?>
															<span class="wpallimport-disabled"><?php echo esc_html($action['title']); ?></span>
															<?php endif; ?>
														</span> <?php if ($ai != count($import_actions)): ?>|<?php endif; ?>
														<?php
														break;
												}
												$ai++;
											}
										?>

									</div>
								</td>
								<?php
								break;
							case 'summary':
								?>
								<td>
									<?php
									if ($item['triggered'] and ! $item['processing']){
										esc_html_e('triggered with cron', 'wp-all-import');
										if ($item['last_activity'] != '0000-00-00 00:00:00'){
											$diff = ceil((time() - strtotime($item['last_activity']))/60);
											?>
											<br>
											<span <?php if ($diff >= 10) echo 'style="color:red;"';?>>
											<?php
												printf(
												/* translators: %s: human-readable time difference */
												esc_html__('last activity %s ago', 'wp-all-import'),
												esc_html(human_time_diff(strtotime($item['last_activity']), time()))
											);
											?>
											</span>
											<?php
										}
									}
									elseif ($item['processing']){
										esc_html_e('currently processing with cron', 'wp-all-import'); echo '<br/>';
										printf(
											/* translators: %s: number of records processed */
											esc_html__('Records Processed %s', 'wp-all-import'),
											esc_html($item['imported'])
										);
										if ($item['last_activity'] != '0000-00-00 00:00:00'){
											$diff = ceil((time() - strtotime($item['last_activity']))/60);
											?>
											<br>
											<span <?php if ($diff >= 10) echo 'style="color:red;"';?>>
											<?php
												printf(
												/* translators: %s: human-readable time difference */
												esc_html__('last activity %s ago', 'wp-all-import'),
												esc_html(human_time_diff(strtotime($item['last_activity']), time()))
											);
											?>
											</span>
											<?php
										}
									}
									elseif($item['executing']){
										esc_html_e('Import currently in progress', 'wp-all-import');
										if ($item['last_activity'] != '0000-00-00 00:00:00'){
											$diff = ceil((time() - strtotime($item['last_activity']))/60);
											?>
											<br>
											<span <?php if ($diff >= 10) echo 'style="color:red;"';?>>
											<?php
												printf(
												/* translators: %s: human-readable time difference */
												esc_html__('last activity %s ago', 'wp-all-import'),
												esc_html(human_time_diff(strtotime($item['last_activity']), time()))
											);
											?>
											</span>
											<?php
										}
									}
									elseif($item['canceled'] and $item['canceled_on'] != '0000-00-00 00:00:00'){
										printf(
											/* translators: %s: date and time of import attempt */
											esc_html__('Import Attempt at %s', 'wp-all-import'),
											esc_html(get_date_from_gmt($item['canceled_on'], "m/d/Y g:i a"))
										); echo '<br/>';
										esc_html_e('Import canceled', 'wp-all-import');
									}
									elseif($item['failed'] and $item['failed_on'] != '0000-00-00 00:00:00'){
										printf(
											/* translators: %s: date and time of import attempt */
											esc_html__('Import Attempt at %s', 'wp-all-import'),
											esc_html(get_date_from_gmt($item['failed_on'], "m/d/Y g:i a"))
										); echo '<br/>';
										esc_html_e('Import failed, please check logs', 'wp-all-import');
									}
									else{
										if (!empty($item['options']['custom_type'])){
											$custom_type = get_post_type_object( $item['options']['custom_type'] );
											$cpt_name = ( ! empty($custom_type)) ? $custom_type->label : '';
										}
										else{
											$cpt_name = '';
										}
										printf(
											/* translators: %s: date and time of last run */
											esc_html__('Last run: %s', 'wp-all-import'),
											esc_html(($item['registered_on'] == '0000-00-00 00:00:00') ? __('never', 'wp-all-import') : get_date_from_gmt($item['registered_on'], "m/d/Y g:i a"))
										); echo '<br/>';
										printf(
											/* translators: 1: number of posts created, 2: post type label */
											esc_html__('%1$d %2$s created', 'wp-all-import'),
											intval($item['created']),
											esc_html($cpt_name)
										); echo '<br/>';
										printf(
											/* translators: 1: updated count, 2: skipped count, 3: deleted count */
											esc_html__('%1$d updated, %2$d skipped, %3$d deleted', 'wp-all-import'),
											intval($item['updated']),
											intval($item['skipped']),
											intval($item['deleted'])
										);
									}

									if ($item['settings_update_on'] != '0000-00-00 00:00:00' and $item['last_activity'] != '0000-00-00 00:00:00' and strtotime($item['settings_update_on']) > strtotime($item['last_activity'])){
										echo '<br/>';
										?>
										<strong><?php esc_html_e('settings edited since last run', 'wp-all-import'); ?></strong>
										<?php
									}

									?>
								</td>
								<?php
								break;
							case 'info':
								?>
								<td>
									<a href="#" class="scheduling-disabled"><?php esc_html_e('Scheduling Options', 'wp-all-import'); ?></a>
                                    <a href="#help" class="wpallimport-help" style="position: relative; top: -2px; margin-left: 0;"  title="<?php esc_html_e("To run this import on a schedule you must use the 'Download from URL' or 'Use existing file' option on the Import Settings page.", 'wp-all-import');?>">?</a>
                                    <br/>
									<a href="<?php echo esc_url(add_query_arg(array('page' => 'pmxi-admin-history', 'id' => $item['id']), remove_query_arg('pagenum', $this->baseUrl))); ?>"><?php esc_html_e('History Logs', 'wp-all-import'); ?></a>
								</td>
								<?php
								break;
							case 'actions':
								?>
								<td style="width: 130px;">
									<?php if ( ! $item['processing'] and ! $item['executing'] ): ?>
									<h2 style="float:left;"><a class="add-new-h2" href="<?php echo esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'update'), remove_query_arg('pagenum', $this->baseUrl))); ?>"><?php esc_html_e('Run Import', 'wp-all-import'); ?></a></h2>
									<?php elseif ($item['processing']) : ?>
									<h2 style="float:left;"><a class="add-new-h2" href="<?php echo esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'cancel', '_wpnonce' => wp_create_nonce( '_wpnonce-cancel_import' )), remove_query_arg('pagenum', $this->baseUrl))); ?>"><?php esc_html_e('Cancel Cron', 'wp-all-import'); ?></a></h2>
									<?php elseif ($item['executing']) : ?>
									<h2 style="float:left;"><a class="add-new-h2" href="<?php echo esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'cancel', '_wpnonce' => wp_create_nonce( '_wpnonce-cancel_import' )), remove_query_arg('pagenum', $this->baseUrl))); ?>"><?php esc_html_e('Cancel', 'wp-all-import'); ?></a></h2>
									<?php endif; ?>
								</td>
								<?php
								break;
							default:
								?>
								<td>
									<?php do_action('pmxi_manage_imports_column', $column_id, $item); ?>
								</td>
								<?php
								break;
						endswitch;
						?>
					<?php endforeach; ?>
				</tr>
				<?php do_action('pmxi_manage_imports', $item, $class); ?>
			<?php endforeach; ?>
		<?php endif ?>
		</tbody>
	</table>

	<div class="tablenav">
		<?php if ($page_links): ?><div class="tablenav-pages"><?php echo wp_kses_post($page_links_html) ?></div><?php endif ?>

		<div class="alignleft actions">
			<select name="bulk-action2">
				<option value="" selected="selected"><?php esc_html_e('Bulk Actions', 'wp-all-import') ?></option>
				<?php if ( empty($type) or 'trash' != $type): ?>
					<option value="delete"><?php esc_html_e('Delete', 'wp-all-import') ?></option>
				<?php else: ?>
					<option value="restore"><?php esc_html_e('Restore', 'wp-all-import')?></option>
					<option value="delete"><?php esc_html_e('Delete Permanently', 'wp-all-import')?></option>
				<?php endif ?>
			</select>
			<input type="submit" value="<?php esc_attr_e('Apply', 'wp-all-import') ?>" name="doaction2" id="doaction2" class="button-secondary action" />
		</div>
	</div>
	<div class="clear"></div>

    <div class="wpallimport-display-columns wpallimport-margin-top-forty">
		<?php echo wp_kses_post( apply_filters('wpallimport_footer', '') ); ?>
    </div>

</form>
