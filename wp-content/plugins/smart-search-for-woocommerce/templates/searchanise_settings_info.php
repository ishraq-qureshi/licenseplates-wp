<?php
	namespace Searchanise\SmartWoocommerceSearch;

	defined('ABSPATH') || exit;

	$info = Info::get_info(Api::get_instance()->get_locale());
	$yes_icon = '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
	$no_icon = '<mark class="no"><span class="dashicons dashicons-no"></span></mark>';
	$error_icon = '<mark class="error"><span class="dashicons dashicons-warning"></span> %s</mark>';
?>

<h1><?php echo esc_html(__('Searchanise Info', 'smart-search-for-woocommerce')); ?></h1>

<table class="wc_status_table se_info_table widefat" cellspacing="0" id="se-info">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Searchanise enviroment"><h2><?php esc_html_e('Searchanise enviroment', 'smart-search-for-woocommerce' ); ?></h2></th>
		</tr>
		<tbody>
			<tr>
				<td><?php esc_html_e('Plugin version', 'smart-search-for-woocommerce'); ?>:</td>
				<td class="help"><?php fn_se_print_help_tip(esc_html__('Searchanise plugin version.', 'smart-search-for-woocommerce')); ?></td>
				<td><?php echo esc_html($info['addon_version']); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e('API Keys', 'smart-search-for-woocommerce'); ?>:</td>
				<td class="help"><?php fn_se_print_help_tip(esc_html__('Registered API keys.', 'smart-search-for-woocommerce')); ?></td>
				<td>
					<?php foreach ($info['api_key'] as $lang_code => $key) { ?>
						<?php echo esc_html('[' . $lang_code . '] ' . $key); ?><br>
					<?php } ?>
				</td>
			</tr>
			<tr>
				<td><?php esc_html_e('Export status', 'smart-search-for-woocommerce'); ?>:</td>
				<td class="help"><?php fn_se_print_help_tip(esc_html__('Searchanise engine export statuses.', 'smart-search-for-woocommerce')); ?></td>
				<td>
					<?php foreach ($info['export_status'] as $lang_code => $st) { ?>
						<?php echo esc_html('[' . $lang_code . '] ' . $st); ?><br>
					<?php } ?>
				</td>
			</tr>
			<tr>
				<td><?php esc_html_e('Search input selector', 'smart-search-for-woocommerce'); ?>:</td>
				<td class="help"><?php fn_se_print_help_tip(esc_html__('Search input CSS selector.', 'smart-search-for-woocommerce')); ?></td>
				<td><?php echo esc_html($info['search_input_selector']); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e('Sync mode', 'smart-search-for-woocommerce'); ?>:</td>
				<td class="help"><?php fn_se_print_help_tip(esc_html__('Searchanise synchronization mode.', 'smart-search-for-woocommerce')); ?></td>
				<td><?php echo esc_html($info['sync_mode']); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e('Plugin enabled', 'smart-search-for-woocommerce'); ?>:</td>
				<td class="help"><?php fn_se_print_help_tip(esc_html__('Searchanise plugin status.', 'smart-search-for-woocommerce')); ?></td>
				<td><?php echo 'enabled' == $info['addon_status'] ? wp_kses($yes_icon, array('mark' => array('class' => array()), 'span' => array('class' => array()))) : wp_kses($no_icon, array('mark' => array('class' => array()), 'span' => array('class' => array()))); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e('Search enabled', 'smart-search-for-woocommerce'); ?>:</td>
				<td class="help"><?php fn_se_print_help_tip(esc_html__('Enable search with Searchanise', 'smart-search-for-woocommerce')); ?></td>
				<td><?php echo 'Y' == $info['search_enabled'] ? wp_kses($yes_icon, array('mark' => array('class' => array()), 'span' => array('class' => array()))) : wp_kses($no_icon, array('mark' => array('class' => array()), 'span' => array('class' => array()))); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e('Cron async enabled', 'smart-search-for-woocommerce'); ?>:</td>
				<td class="help"><?php fn_se_print_help_tip(esc_html__('Enable synchronization via Cron', 'smart-search-for-woocommerce')); ?></td>
				<td><?php echo 'Y' == $info['cron_async_enabled'] ? wp_kses($yes_icon, array('mark' => array('class' => array()), 'span' => array('class' => array()))) : wp_kses($no_icon, array('mark' => array('class' => array()), 'span' => array('class' => array()))); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e('Ajax async enabled', 'smart-search-for-woocommerce'); ?>:</td>
				<td class="help"><?php fn_se_print_help_tip(esc_html__('Enable synchronization via Ajax calls', 'smart-search-for-woocommerce')); ?></td>
				<td><?php echo 'Y' == $info['ajax_async_enabled'] ? wp_kses($yes_icon, array('mark' => array('class' => array()), 'span' => array('class' => array()))) : wp_kses($no_icon, array('mark' => array('class' => array()), 'span' => array('class' => array()))); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e('Log directory', 'smart-search-for-woocommerce'); ?>:</td>
				<td class="help"><?php fn_se_print_help_tip(esc_html__('Searchanise log directory', 'smart-search-for-woocommerce')); ?></td>
				<td><?php echo esc_html($info['log_dir']); ?></td>
			</tr>
		</tbody>
	</thead>
</table>

<table class="wc_status_table se_info_table widefat" cellspacing="0" id="se-info">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Server environment"><h2><?php esc_html_e('Server environment', 'smart-search-for-woocommerce' ); ?></h2></th>
		</tr>
		<tbody>
			<tr>
				<td><?php esc_html_e('Max execution time', 'smart-search-for-woocommerce'); ?>:</td>
				<td class="help"><?php fn_se_print_help_tip(esc_html__('Default maximum execution script time', 'smart-search-for-woocommerce')); ?></td>
				<td><?php echo esc_html($info['max_execution_time']); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e('Max execution time after', 'smart-search-for-woocommerce'); ?>:</td>
				<td class="help"><?php fn_se_print_help_tip(esc_html__('Maximum execution script time for Searchanise', 'smart-search-for-woocommerce')); ?></td>
				<td><?php echo esc_html($info['max_execution_time_after']); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e('Ignore user abort', 'smart-search-for-woocommerce'); ?>:</td>
				<td class="help"><?php fn_se_print_help_tip(esc_html__('Default value of ignore_user_abort PHP setting.', 'smart-search-for-woocommerce')); ?></td>
				<td><?php echo esc_html($info['ignore_user_abort']); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e('Ignore user abort after', 'smart-search-for-woocommerce'); ?>:</td>
				<td class="help"><?php fn_se_print_help_tip(esc_html__('value of ignore_user_abort PHP setting for Searchanise.', 'smart-search-for-woocommerce')); ?></td>
				<td><?php echo esc_html($info['ignore_user_abort_after']); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e('Memory limit', 'smart-search-for-woocommerce'); ?>:</td>
				<td class="help"><?php fn_se_print_help_tip(esc_html__('Default memory limit for scripts.', 'smart-search-for-woocommerce')); ?></td>
				<td><?php echo esc_html($info['memory_limit']); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e('Memory limit after', 'smart-search-for-woocommerce'); ?>:</td>
				<td class="help"><?php fn_se_print_help_tip(esc_html__('Memory limit for Searchanise.', 'smart-search-for-woocommerce')); ?></td>
				<td><?php echo esc_html($info['memory_limit_after']); ?></td>
			</tr>
		</tbody>
	</thead>
</table>

<table class="wc_status_table se_info_table widefat" cellspacing="0" id="se-info">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Searchanise queue"><h2><?php esc_html_e('Searchanise queue', 'smart-search-for-woocommerce' ); ?></h2></th>
		</tr>
		<tbody>
			<tr>
				<td><?php esc_html_e('Queue status', 'smart-search-for-woocommerce'); ?>:</td>
				<td class="help"><?php fn_se_print_help_tip(esc_html__('Common queue status.', 'smart-search-for-woocommerce')); ?></td>
				<td><?php echo 'Y' == $info['queue_status'] ? wp_kses($yes_icon, array('mark' => array('class' => array()), 'span' => array('class' => array()))) : ( sprintf( wp_kses($error_icon, array('mark' => array('class' => array()), 'span' => array('class' => array()))), esc_html__('Something is wrong in queue processing. Please contact Searchanise <a href="mailto:feedback@searchnise.com">feedback@searchnise.com</a> technical support', 'smart-search-for-woocommerce')) ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e('Total items', 'smart-search-for-woocommerce'); ?>:</td>
				<td class="help"><?php fn_se_print_help_tip(esc_html__('Total items in Searchanise queue', 'smart-search-for-woocommerce')); ?></td>
				<td><?php echo esc_html($info['total_items_in_queue']); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e('Next queue', 'smart-search-for-woocommerce'); ?>:</td>
				<td class="help"><?php fn_se_print_help_tip(esc_html__('Next item in queue', 'smart-search-for-woocommerce')); ?></td>
				<td><?php echo esc_html(print_r($info['next_queue'], true)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r ?></td>
			</tr>
		</tbody>
	</thead>
</table>

<?php if (!empty($info['plugins'])) { ?>
<table class="wc_status_table se_info_table widefat" cellspacing="0" id="se-info">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Active plugins"><h2><?php esc_html_e('Active plugins', 'smart-search-for-woocommerce' ); ?></h2></th>
		</tr>
		<tbody>
			<?php foreach ($info['plugins'] as $pl) { ?>
				<tr>
					<td><?php echo wp_kses("<a href='{$pl['PluginURI']}' aria-label='" . __('Visit plugin page', 'smart-search-for-woocommerce') . "'>{$pl['Name']}</a>", array('a' => array('href' => array()))); ?></td>
					<td class="help">&nbsp;</td>
					<td><?php echo esc_html($pl['Version']); ?></td>
				</tr>
			<?php } ?>
		</tbody>
	</thead>
</table>
<?php } ?>
