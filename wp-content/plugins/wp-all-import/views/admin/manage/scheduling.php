<?php if ( ! defined( 'ABSPATH' ) ) exit; // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals ?>
<h2>
	<?php esc_html_e('Cron Scheduling', 'wp-all-import') ?>
</h2>

<?php if ( in_array($item['type'], array('url', 'ftp', 'file'))):?>

	<p>
		<?php esc_html_e('To schedule an import, you must create two cron jobs in your web hosting control panel. One cron job will be used to run the Trigger script, the other to run the Execution script.', 'wp-all-import'); ?>
	</p>

	<p>
		Trigger Script URL<br /><small>Run the trigger script when you want to update your import. Once per 24 hours is recommended.</small><br /><input style='width: 700px;' type='text' value='<?php echo esc_attr(home_url() . '/wp-cron.php?import_key=' . $cron_job_key . '&import_id=' . $id . '&action=trigger'); ?>' />
		<br /><br />

		Execution Script URL<br /><small>Run the execution script frequently. Once per two minutes is recommended.</small><br /><input style='width: 700px;' type='text' value='<?php echo esc_attr(home_url() . '/wp-cron.php?import_key=' . $cron_job_key . '&import_id=' . $id . '&action=processing'); ?>' /><br /><br />
	</p>


	<p><strong><?php esc_html_e('Trigger Script', 'wp-all-import'); ?></strong></p>

	<p><?php esc_html_e('Every time you want to schedule the import, run the trigger script.', 'wp-all-import'); ?></p>

	<p><?php esc_html_e('To schedule the import to run once every 24 hours, run the trigger script every 24 hours. Most hosts require you to use “wget” to access a URL. Ask your host for details.', 'wp-all-import'); ?></p>

	<p><i><?php esc_html_e('Example:', 'wp-all-import'); ?></i></p>

	<p>wget -q -O /dev/null "<?php echo esc_url(home_url() . '/wp-load.php?import_key=' . $cron_job_key . '&import_id=' . $id . '&action=trigger'); ?>"</p>
	 
	<p><strong><?php esc_html_e('Execution Script', 'wp-all-import'); ?></strong></p>

	<p><?php esc_html_e('The Execution script actually executes the import, once it has been triggered with the Trigger script.', 'wp-all-import'); ?></p>

	<p><?php esc_html_e('It processes in iteration (only importing a few records each time it runs) to optimize server load. It is recommended you run the execution script every 2 minutes.', 'wp-all-import'); ?></p>

	<p><?php esc_html_e('It also operates this way in case of unexpected crashes by your web host. If it crashes before the import is finished, the next run of the cron job two minutes later will continue it where it left off, ensuring reliability.', 'wp-all-import'); ?></p>

	<p><i><?php esc_html_e('Example:', 'wp-all-import'); ?></i></p>

	<p>wget -q -O /dev/null "<?php echo esc_url(home_url() . '/wp-load.php?import_key=' . $cron_job_key . '&import_id=' . $id . '&action=processing'); ?>"</p>

	<p><strong><?php esc_html_e('Notes', 'wp-all-import'); ?></strong></p>
	 
	<p>
		<?php esc_html_e('Your web host may require you to use a command other than wget, although wget is most common. In this case, you must asking your web hosting provider for help.', 'wp-all-import'); ?>
	</p>

	<p>
		See the <a href='http://www.wpallimport.com/documentation/recurring/cron/?utm_source=import-plugin-free&utm_medium=help&utm_campaign=manual-scheduling'>documentation</a> for more details.
	</p>

<?php else: ?>
	
	<p>
		<?php esc_html_e('To schedule this import with a cron job, you must use the "Download from URL" option on the Import Settings screen of WP All Import.', 'wp-all-import'); ?>
	</p>
	<p>
		<a href="<?php echo esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'options'), $this->baseUrl)); ?>"><?php esc_html_e('Go to Import Settings now...', 'wp-all-import'); ?></a>
	</p>

<?php endif; ?>

<div class="wpallimport-display-columns wpallimport-margin-top-forty">
	<?php echo wp_kses_post( apply_filters('wpallimport_footer', '') ); ?>
</div>