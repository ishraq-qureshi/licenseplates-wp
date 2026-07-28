<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
if ( ! defined( 'ABSPATH' ) ) exit;
	$isWizard = $this->isWizard;
	$baseUrl  = $this->baseUrl;	
?>

<input type="hidden" id="selected_post_type" value="<?php echo (!empty($post['custom_type'])) ? esc_attr($post['custom_type']) : '';?>">
<input type="hidden" id="selected_type" value="<?php echo (!empty($post['type'])) ? esc_attr($post['type']) : '';?>">

<div class="wpallimport-step-4">
	
	<h2 class="wpallimport-wp-notices"></h2>

	<div class="wpallimport-wrapper">
		<h2 class="wpallimport-wp-notices"></h2>
		<div class="wpallimport-header">
			<div class="wpallimport-logo"></div>
			<div class="wpallimport-title">
				<h2><?php esc_html_e('Import Settings', 'wp-all-import'); ?></h2>
			</div>
			<?php echo wp_kses_post( apply_filters('wpallimport_links_block', '') );?>
		</div>	
		<div class="clear"></div>		
	</div>		

	<?php $visible_sections = apply_filters('pmxi_visible_options_sections', array('reimport', 'settings'), $post['custom_type']); ?>

	<table class="wpallimport-layout">
		<tr>
			<td class="left">		
	
				<?php do_action('pmxi_options_header', $isWizard, $post); ?>

				<?php
				$is_valid_root_element = true;		
				$error_codes = $this->warnings->get_error_codes();		
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
							<h4 style="font-size:18px;"><?php esc_html_e("It has changed and is not compatible with this import template.", "wp-all-import"); ?></h4>
						</div>		
					</div>		
					<a class="button wpallimport-large-button wpallimport-notify-read-more" href="http://www.wpallimport.com/documentation/troubleshooting/problems-with-import-files/#invalid?utm_source=import-plugin-free&utm_medium=error&utm_campaign=import-file-changed" target="_blank"><?php esc_html_e('Read More', 'wp-all-import');?></a>		
				</div>										

				<form class="<?php echo ! $isWizard ? 'edit' : 'options' ?>" method="post" enctype="multipart/form-data" autocomplete="off" <?php echo ! $isWizard ? 'style="overflow:visible;"' : '' ?>>

					<?php $post_type = $post['custom_type']; ?>				

					<?php  if ( ! $this->isWizard): ?>
						
						<?php include( 'options/_import_file.php' ); ?>

					<?php endif; ?>

					<div class="options">
						<?php

							if ( in_array('reimport', $visible_sections)) include( 'options/_reimport_template.php' );
							do_action('pmxi_options_tab', $isWizard, $post);

                            if(!isset($import)) {
                                $import = $update_previous;
                            }
                            include( 'options/scheduling/_scheduling_ui.php' );

							if ( in_array('settings', $visible_sections)) include( 'options/_settings_template.php' );

							include( 'options/_buttons_template.php' );

						?>
					</div>

				</form>

                <div class="wpallimport-display-columns wpallimport-margin-top-forty">
					<?php echo wp_kses_post( apply_filters('wpallimport_footer', '') ); ?>
                </div>
					
			</td>
			<td class="right template-sidebar ">
				<div style="position:relative;">
					<?php $this->tag( false ); ?>
				</div>
			</td>	
		</tr>
	</table>

</div>

<div id="record_matching_pointer" style="display:none;">	

	<h3><?php esc_html_e("Record Matching", "wp-all-import");?></h3>

	<p>
		<b><?php esc_html_e("Record Matching is how WP All Import matches records in your file with posts that already exist WordPress.","wp-all-import");?></b>
	</p>

	<p>
		<?php esc_html_e("Record Matching is most commonly used to tell WP All Import how to match up records in your file with posts WP All Import has already created on your site, so that if your file is updated with new data, WP All Import can update your posts accordingly.","wp-all-import");?>
	</p>

	<hr />

	<p><?php esc_html_e("AUTOMATIC RECORD MATCHING","wp-all-import");?></p>

	<p>
		<?php esc_html_e("Automatic Record Matching allows WP All Import to update records that were imported or updated during the last run of this same import.","wp-all-import");?>
	</p>

	<p>
		<?php esc_html_e("Your unique key must be UNIQUE for each record in your feed. Make sure you get it right - you can't change it later. You'll have to re-create your import.","wp-all-import");?>
	</p>

	<hr />

	<p><?php esc_html_e("MANUAL RECORD MATCHING", "wp-all-import");?></p>

	<p>
		<?php esc_html_e("Manual record matching allows WP All Import to update any records, even records that were not imported with WP All Import, or are part of a different import.","wp-all-import");?>
	</p>

</div>