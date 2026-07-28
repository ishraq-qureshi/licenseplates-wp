<?php if ( ! defined( 'ABSPATH' ) ) exit; // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals ?>
<h4>
	<?php if ($import->friendly_name): ?>
		<em><?php printf(
			/* translators: 1: import friendly name, 2: import ID */
			esc_html__('%1$s - ID: %2$s Import History', 'wp-all-import'),
			esc_html($import->friendly_name),
			intval($import->id)
		); ?></em>
		<?php else: ?>
		<em><?php printf(
			/* translators: 1: import name, 2: import ID */
			esc_html__('%1$s - ID: %2$s Import History', 'wp-all-import'),
			esc_html($import->name),
			intval($import->id)
		); ?></em>
	<?php endif ?>
</h4>

<?php if ($this->errors->get_error_codes()): ?>
	<?php $this->error() ?>
<?php endif ?>

<?php
// define the columns to display, the syntax is 'internal name' => 'display name'
$columns = array(
	'id'			=> __('ID', 'wp-all-import'),
	'date'			=> __('Date', 'wp-all-import'),
	'time_run'		=> __('Run Time', 'wp-all-import'),	
	'type'			=> __('Type', 'wp-all-import'),
	'summary'		=> __('Summary', 'wp-all-import'),	
	'download'		=> '',
);
?>

<?php if ( $import->triggered ): ?>
	<p> <strong><?php esc_html_e('Scheduling Status', 'wp-all-import'); ?>:</strong> <?php esc_html_e('triggered', 'wp-all-import'); ?> <?php if ($import->processing) esc_html_e('and processing', 'wp-all-import'); ?>...</p>
<?php endif; ?>

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
			<th class="manage-column column-cb check-column" scope="col" style="padding: 8px 10px;">
				<input type="checkbox" style="margin-top:1px;"/>
			</th>
			<?php
			$col_html = '';
			foreach ($columns as $column_id => $column_display_name) {
				if ( ! in_array($column_id, array('download'))){
					$column_link = "<a href='";
					$order2 = 'ASC';
					if ($order_by == $column_id)
						$order2 = ($order == 'DESC') ? 'ASC' : 'DESC';

					$column_link .= esc_url(add_query_arg(array('id' => $id, 'order' => $order2, 'order_by' => $column_id), $this->baseUrl));
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
			<th class="manage-column column-cb check-column" scope="col" style="padding: 8px 10px;">
				<input type="checkbox" />
			</th>
			<?php echo wp_kses_post($col_html); ?>
		</tr>
		</tfoot>
		<tbody id="the-pmxi-admin-import-list" class="list:pmxi-admin-imports">
		<?php if ($list->isEmpty()): ?>
			<tr>
				<td colspan="<?php echo count($columns) + 1 ?>"><?php esc_html_e('No previous history found.', 'wp-all-import') ?></td>
			</tr>
		<?php else: ?>
			<?php
			$class = '';
			?>
			<?php foreach ($list as $item): ?>
				<?php $class = ('alternate' == $class) ? '' : 'alternate'; ?>
				<tr class="<?php echo esc_attr($class); ?>" valign="middle">
					<th scope="row" class="check-column" style="vertical-align: middle; padding: 8px 10px;">
						<input type="checkbox" id="item_<?php echo esc_attr($item['id']); ?>" name="items[]" value="<?php echo esc_attr($item['id']) ?>" />
					</th>
					<?php foreach ($columns as $column_id => $column_display_name): ?>
						<?php
						switch ($column_id):
							case 'id':
								?>
								<th valign="top" scope="row" style="vertical-align: middle;">
									<?php echo esc_html($item['id']); ?>
								</th>
								<?php
								break;
							case 'date':
								?>
								<td style="vertical-align: middle;">
									<?php if ('0000-00-00 00:00:00' == $item['date']): ?>
										<em>never</em>
									<?php else: ?>
										<?php echo esc_html(get_date_from_gmt($item['date'], "m/d/Y g:i a")); ?>
									<?php endif ?>
								</td>
								<?php
								break;
							case 'time_run':
								?>
								<td style="vertical-align: middle;">
									<?php echo ($item['time_run'] and is_numeric($item['time_run'])) ? esc_html(gmdate("H:i:s", $item['time_run'])) : '-'; ?>
								</td>
								<?php
								break;							
							case 'summary':
								?>
								<td style="vertical-align: middle;">
									<?php echo esc_html($item['summary']);?>
								</td>
								<?php
								break;
							case 'type':
								?>
								<td style="vertical-align: middle;">
									<?php
									switch ($item['type']) {
										case 'manual':
											esc_html_e('manual run', 'wp-all-import');
											break;
										case 'continue':
											esc_html_e('continue run', 'wp-all-import');
											break;
										case 'processing':
											esc_html_e('cron processing', 'wp-all-import');
											break;
										case 'trigger':
											esc_html_e('triggered by cron', 'wp-all-import');
											break;
										default:
											# code...
											break;
									}
									?>
								</td>
								<?php
								break;
							case 'download':
								?>
								<td style="vertical-align: middle;">
									<?php 
									if ( ! in_array($item['type'], array('trigger'))){
										$wp_uploads = wp_upload_dir();
										$log_file = wp_all_import_secure_file( $wp_uploads['basedir'] . DIRECTORY_SEPARATOR . PMXI_Plugin::LOGS_DIRECTORY, $item['id'], false, false ) . DIRECTORY_SEPARATOR . $item['id'] . '.html';
										if (file_exists($log_file)){
											?>											
											<a href="<?php echo esc_url(add_query_arg(array('id' => $import->id, 'action' => 'log', 'history_id' => $item['id'], '_wpnonce' => wp_create_nonce( '_wpnonce-download_log' )), $this->baseUrl)); ?>"><?php esc_html_e('Download Log', 'wp-all-import'); ?></a>
											<?php
										} 
										else { 
											esc_html_e('Log Unavailable', 'wp-all-import'); 
										}										
									} 
									else { 
										?>									
										&nbsp;
										<?php 
									}; 
									?>
								</td>
								<?php
								break;							
							default:
								?>
								<td>
									<?php echo esc_html($item[$column_id]); ?>
								</td>
								<?php
								break;
						endswitch;
						?>
					<?php endforeach; ?>
				</tr>								
			<?php endforeach; ?>
		<?php endif ?>
		</tbody>
	</table>

	<div class="tablenav">
		<?php if ($page_links): ?><div class="tablenav-pages"><?php echo wp_kses_post($page_links_html); ?></div><?php endif ?>

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