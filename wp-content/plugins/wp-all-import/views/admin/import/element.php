<?php if ( ! defined( 'ABSPATH' ) ) exit; // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals ?>

<h2 class="wpallimport-wp-notices"></h2>

<form class="wpallimport-choose-elements no-enter-submit wpallimport-step-2 wpallimport-wrapper" method="post">	
	<div class="wpallimport-header">
		<div class="wpallimport-logo"></div>
		<div class="wpallimport-title">
			<h2><?php esc_html_e('Review Import File', 'wp-all-import'); ?></h2>
		</div>
		<?php echo wp_kses_post( apply_filters('wpallimport_links_block', '') );?>
	</div>	
	<div class="clear"></div>	
	<?php $custom_type = get_post_type_object( PMXI_Plugin::$session->custom_type ); ?>
	<div class="wpallimport-content-section wpallimport-console">
		<div class="ajax-console">
			<?php if ($this->errors->get_error_codes()): ?>
				<?php $this->error() ?>			
			<?php endif ?>
		</div>		
		<input type="submit" class="button wpallimport-large-button" value="<?php esc_html_e('Continue to Step 3', 'wp-all-import'); ?>" style="position:absolute; top:45px; right:10px;"/>
	</div>
	
	<div class="wpallimport-content-section wpallimport-elements-preloader">
		<div class="preload" style="height: 80px; margin-top: 25px;"></div>
	</div>

	<div class="wpallimport-content-section" style="padding-bottom:0; max-height: 600px; overflow:scroll; width: 100%;">

		<table class="wpallimport-layout" style="width:100%;">
			<tr>				
				<?php if ( ! $is_csv): ?>
				<td class="left" style="width: 25%; min-width: unset; border-right: 1px solid #ddd;">
					<h3 class="txt_center"><?php esc_html_e('What element are you looking for?', 'wp-all-import'); ?></h3>				
					<?php
					if ( ! empty($elements_cloud) and ! $is_csv ){												
						foreach ($elements_cloud as $tag => $count){
							?>
							<a href="javascript:void(0);" rel="<?php echo esc_attr($tag);?>" class="wpallimport-change-root-element <?php if (PMXI_Plugin::$session->source['root_element'] == $tag) echo 'selected';?>">
								<span class="tag_name"><?php echo esc_html(strtolower($tag)); ?></span>
								<span class="tag_count"><?php echo esc_html($count); ?></span>
							</a>
							<?php
						}						
					}
					?>			
				</td>			
				<?php endif; ?>	
				<td class="right" <?php if ( ! $is_csv){?>style="width:75%; padding:0;"<?php } else {?>style="width:100%; padding:0;"<?php }?>>
					<div class="action_buttons">
						<table style="width:100%;">
							<tr>
								<td>
									<a href="javascript:void(0);" id="prev_element" class="wpallimport-go-to">&nbsp;</a>
								</td>
								<td class="txt_center">

									<p class="wpallimport-root-element">
										<?php echo wp_kses_post(PMXI_Plugin::$session->source['root_element']);?>
									</p>								
									<input type="text" id="goto_element" value="1"/>
									<span class="wpallimport-elements-information">
										<?php echo wp_kses( sprintf(
											/* translators: %s: element count */
											__('of <span class="wpallimport-elements-count-info">%s</span>','wp-all-import'),
											intval(PMXI_Plugin::$session->count)
										), array('span' => array('class' => array())) );?>
									</span>																	

								</td>
								<td>
									<a href="javascript:void(0);" id="next_element" class="wpallimport-go-to">&nbsp;</a>
								</td>
							</tr>
						</table>																
					</div>
					<fieldset class="widefat" style="background:fafafa;">												
						
						<div class="input">

							<?php if ($is_csv !== false): ?>										
															
								<div class="wpallimport-set-csv-delimiter">
									<label>
										<?php esc_html_e("Set delimiter for CSV fields:", "wp-all-import"); ?>
									</label>									
									<input type="text" name="delimiter" value="<?php echo esc_attr($is_csv);?>"/>
									<input type="button" name="apply_delimiter" class="rad4" value="<?php esc_html_e('Apply', 'wp-all-import'); ?>"/>									
								</div>							

							<?php else: ?>
							
								<input type="hidden" value="" name="delimiter"/>

							<?php endif; ?>
						
						</div>

						<div class="wpallimport-xml">
							<?php //$this->render_xml_element($dom->documentElement) ?>
						</div>
					</fieldset>		
					<div class="import_information">
						<?php if (PMXI_Plugin::$session->wizard_type == 'new') :?>
						<h3>
							<?php echo wp_kses( sprintf(
								/* translators: 1: XML root element name, 2: post type singular name */
								__('Each <span>&lt;<span class="root_element">%1$s</span>&gt;</span> element will be imported into a <span>New %2$s</span>', 'wp-all-import'),
								esc_html(PMXI_Plugin::$session->source['root_element']),
								esc_html($custom_type->labels->singular_name)
							), array('span' => array('class' => array())) ); ?>
						</h3>
						<?php else: ?>
						<h3>
							<?php echo wp_kses( sprintf(
								/* translators: 1: XML root element name, 2: post type name */
								__('Data in <span>&lt;<span class="root_element">%1$s</span>&gt;</span> elements will be imported to <span>%2$s</span>', 'wp-all-import'),
								esc_html(PMXI_Plugin::$session->source['root_element']),
								esc_html($custom_type->labels->name)
							), array('span' => array('class' => array())) ); ?>
						</h3>
						<?php endif; ?>
						
						<h3 class="wp_all_import_warning">
							<?php esc_html_e('This doesn\'t look right, try manually selecting a different root element on the left.', 'wp-all-import'); ?>
						</h3>
						
					</div>
				</td>
			</tr>
		</table>
	</div>

	<div class="wpallimport-collapsed closed">
		<div class="wpallimport-content-section">
			<div class="wpallimport-collapsed-header">
				<h3><?php esc_html_e('Add Filtering Options', 'wp-all-import'); ?></h3>
			</div>
			<div class="wpallimport-collapsed-content">
				<div>
					<div class="rule_inputs">
						<table style="width:100%;">
							<tr>
								<th><?php esc_html_e('Element', 'wp-all-import'); ?></th>
								<th><?php esc_html_e('Rule', 'wp-all-import'); ?></th>
								<th><?php esc_html_e('Value', 'wp-all-import'); ?></th>
								<th>&nbsp;</th>
							</tr>
							<tr>
								<td style="width:25%;">
									<select id="pmxi_xml_element">
										<option value=""><?php esc_html_e('Select Element', 'wp-all-import'); ?></option>
										<?php PMXI_Render::render_xml_elements_for_filtring($elements->item(0)); ?>
									</select>
								</td>
								<td style="width:25%;">
									<select id="pmxi_rule">
										<option value=""><?php esc_html_e('Select Rule', 'wp-all-import'); ?></option>
										<option value="equals"><?php esc_html_e('equals', 'wp-all-import'); ?></option>
										<option value="not_equals"><?php esc_html_e('not equals', 'wp-all-import'); ?></option>
										<option value="greater"><?php esc_html_e('greater than', 'wp-all-import');?></option>
										<option value="equals_or_greater"><?php esc_html_e('equals or greater than', 'wp-all-import'); ?></option>
										<option value="less"><?php esc_html_e('less than', 'wp-all-import'); ?></option>
										<option value="equals_or_less"><?php esc_html_e('equals or less than', 'wp-all-import'); ?></option>
										<option value="contains"><?php esc_html_e('contains', 'wp-all-import'); ?></option>
										<option value="not_contains"><?php esc_html_e('not contains', 'wp-all-import'); ?></option>
										<option value="is_empty"><?php esc_html_e('is empty', 'wp-all-import'); ?></option>
										<option value="is_not_empty"><?php esc_html_e('is not empty', 'wp-all-import'); ?></option>
									</select>
								</td>
								<td style="width:25%;">
									<input id="pmxi_value" type="text" placeholder="value" value=""/>
								</td>
								<td style="width:15%;">
									<a id="pmxi_add_rule" href="javascript:void(0);"><?php esc_html_e('Add Rule', 'wp-all-import');?></a>
								</td>
							</tr>
						</table>						
					</div>					
				</div>
				<div class="clear"></div>				
				<table class="xpath_filtering">
					<tr>
						<td style="width:5%; font-weight:bold; color: #000;"><?php esc_html_e('XPath','wp-all-import');?></td>
						<td style="width:95%;">
							<input type="text" name="xpath" value="<?php echo esc_attr($post['xpath']) ?>" style="max-width:none;" />					
							<input type="hidden" id="root_element" name="root_element" value="<?php echo esc_attr(PMXI_Plugin::$session->source['root_element']); ?>"/>
						</td>
					</tr>
				</table>				
			</div>
		</div>	
		<div id="wpallimport-filters" class="wpallimport-collapsed-content" style="padding:0;">
			<table style="width: 100%; font-weight: bold; padding: 20px 20px 0 20px;">
				<tr>					
					<td style="width: 30%; padding-left: 30px;"><?php esc_html_e('Element', 'wp-all-import'); ?></td>
					<td style="width:20%;"><?php esc_html_e('Rule', 'wp-all-import'); ?></td>
					<td style="width:20%;"><?php esc_html_e('Value', 'wp-all-import'); ?></td>
					<td style="width:25%;"><?php esc_html_e('Condition', 'wp-all-import'); ?></td>
				</tr>
			</table>
			<div class="wpallimport-content-section">					
				<fieldset id="filtering_rules">					
					<p style="margin:20px 0 5px; text-align:center;"><?php esc_html_e('No filtering options. Add filtering options to only import records matching some specified criteria.', 'wp-all-import');?></p>					
					<ol class="filtering_rules">
						
					</ol>	
					<div class="clear"></div>				
					<a href="javascript:void(0);" id="apply_filters" style="display:none;"><?php esc_html_e('Apply Filters To XPath', 'wp-all-import');?></a>
				</fieldset>
			</div>	
		</div>
	</div>

	<hr>

	<p class="wpallimport-submit-buttons" style="text-align:center;">
		<a href="<?php echo esc_url(add_query_arg('action', 'index', $this->baseUrl)); ?>" class="back rad3"><?php esc_html_e('Back to Step 1','wp-all-import');?></a>
		&nbsp;
		<input type="hidden" name="is_submitted" value="1" />
		<?php wp_nonce_field('choose-elements', '_wpnonce_choose-elements') ?>
		<input type="submit" class="button wpallimport-large-button" value="<?php esc_html_e('Continue to Step 3', 'wp-all-import'); ?>" />
	</p>

    <div class="wpallimport-display-columns wpallimport-margin-top-forty">
		<?php echo wp_kses_post( apply_filters('wpallimport_footer', '') ); ?>
    </div>
	
</form>