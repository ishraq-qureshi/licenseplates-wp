<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
if ( ! defined( 'ABSPATH' ) ) exit;

$custom_type = wp_all_import_custom_type( $post_type );

$exclude_taxonomies = apply_filters('pmxi_exclude_taxonomies', (class_exists('PMWI_Plugin')) ? array('post_format', 'product_type', 'product_shipping_class') : array('post_format'));	
$post_taxonomies = array_diff_key(get_taxonomies_by_object_type($post['is_override_post_type'] ? array_keys(get_post_types( '', 'names' )) : array($post_type), 'object'), array_flip($exclude_taxonomies));

if ( ! empty($post_taxonomies)): 
?>
	<div class="wpallimport-collapsed closed wpallimport-section">
		<div class="wpallimport-content-section">
			<div class="wpallimport-collapsed-header">
				<h3><?php esc_html_e('Taxonomies, Categories, Tags','wp-all-import');?></h3>	
			</div>
			<div class="wpallimport-collapsed-content" style="padding: 0;">
				<div class="wpallimport-collapsed-content-inner" style="padding-bottom:0;">
					<input type="button" rel="taxonomies_hints" value="<?php esc_attr_e('Show Hints', 'wp-all-import');?>" class="show_hints">
					<table class="form-table" style="max-width:none;">
					
						<?php $private_ctx = 0; ?>	
						<tr>
							<td colspan="3" style="padding-bottom:20px;">								
								<?php foreach ($post_taxonomies as $ctx): if ("" == $ctx->labels->name or (class_exists('PMWI_Plugin') and strpos($ctx->name, "pa_") === 0 and $post_type == "product")) continue;?>					
								<?php if (! $ctx->show_ui ) $private_ctx++; ?>
								<table style="width:100%;">
									<tr class="<?php echo ( ! $ctx->show_ui) ? 'private_ctx' : ''; ?>">
										<td>
											<div class="post_taxonomy">
												<div class="input">
													<input type="hidden" name="tax_assing[<?php echo esc_attr($ctx->name);?>]" value="0"/>
													<input type="checkbox" class="assign_post switcher" name="tax_assing[<?php echo esc_attr($ctx->name);?>]" id="tax_assing_<?php echo esc_attr($ctx->name);?>" <?php echo ( ! empty($post['tax_assing'][$ctx->name]) ) ? 'checked="checked"' : ''; ?> value="1"/>
													<label for="tax_assing_<?php echo esc_attr($ctx->name);?>"><?php echo esc_html($ctx->labels->name); ?></label>
												</div>
												<div class="switcher-target-tax_assing_<?php echo esc_attr($ctx->name);?>">
													<div class="input sub_input">
														<div class="input">
															<input type="radio" name="tax_logic[<?php echo esc_attr($ctx->name);?>]" value="single" id="tax_logic_single_<?php echo esc_attr($ctx->name);?>" class="switcher" <?php echo (empty($post['tax_logic'][$ctx->name]) or $post['tax_logic'][$ctx->name] == 'single') ? 'checked="checked"' : ''; ?>/>
															<label for="tax_logic_single_<?php echo esc_attr($ctx->name);?>"><?php /* translators: %1$s: post type singular name, %2$s: taxonomy singular name */ printf(esc_html__('Each %1$s has just one %2$s', 'wp-all-import'), esc_html($custom_type->labels->singular_name), esc_html($ctx->labels->singular_name)); ?></label>
															<div class="switcher-target-tax_logic_single_<?php echo esc_attr($ctx->name);?> sub_input">
																<input type="hidden" name="term_assing[<?php echo esc_attr($ctx->name);?>]" value="1"/>
																<input type="text" class="widefat single_xpath_field" name="tax_single_xpath[<?php echo esc_attr($ctx->name); ?>]" value="<?php echo ( isset($post['tax_single_xpath'][$ctx->name])) ? esc_textarea($post['tax_single_xpath'][$ctx->name]) : ''; ?>" style="width:50%;"/>
																<div class="input tax_is_full_search_single" style="margin: 10px 0;">
																	<input type="hidden" name="tax_is_full_search_single[<?php echo esc_attr($ctx->name); ?>]" value="0"/>
																	<input type="checkbox" id="tax_is_full_search_single_<?php echo esc_attr($ctx->name); ?>" class="switcher" <?php if ( ! empty($post['tax_is_full_search_single'][$ctx->name]) ) echo "checked='checked'"; ?> name="tax_is_full_search_single[<?php echo esc_attr($ctx->name); ?>]" value="1"/>
																	<label for="tax_is_full_search_single_<?php echo esc_attr($ctx->name);?>"><?php /* translators: %s: taxonomy name */ printf(esc_html__('Try to match terms to existing child %s', 'wp-all-import'), esc_html($ctx->labels->name)); ?></label>
																	<a href="#help" class="wpallimport-help" style="position: relative; top: -2px;" title="<?php /* translators: %1$s: taxonomy singular name, %2$s: taxonomy name, %3$s: taxonomy singular name, %4$s: taxonomy singular name */ echo esc_attr(sprintf(__('If this box is checked, WP All Import will try to match the %1$s in your import file to child %2$s. If it can\'t make a successful match, it will create a new %3$s using the %4$s in your import file.', 'wp-all-import'), $ctx->labels->singular_name, $ctx->labels->name, $ctx->labels->singular_name, $ctx->labels->singular_name)); ?>">?</a>
																</div>
															</div>
														</div>
														<div class="input">
															<input type="radio" name="tax_logic[<?php echo esc_attr($ctx->name);?>]" value="multiple" id="tax_logic_multiple_<?php echo esc_attr($ctx->name);?>" class="switcher" <?php echo ( ! empty($post['tax_logic'][$ctx->name]) and $post['tax_logic'][$ctx->name] == 'multiple') ? 'checked="checked"' : ''; ?>/>
															<label for="tax_logic_multiple_<?php echo esc_attr($ctx->name);?>"><?php /* translators: %1$s: post type singular name, %2$s: taxonomy name */ printf(esc_html__('Each %1$s has multiple %2$s', 'wp-all-import'), esc_html($custom_type->labels->singular_name), esc_html($ctx->labels->name)); ?></label>
															<div class="switcher-target-tax_logic_multiple_<?php echo esc_attr($ctx->name);?> sub_input">
																<input type="hidden" name="multiple_term_assing[<?php echo esc_attr($ctx->name);?>]" value="1"/>
																<input type="text" class="widefat multiple_xpath_field" name="tax_multiple_xpath[<?php echo esc_attr($ctx->name); ?>]" value="<?php echo ( ! empty($post['tax_multiple_xpath'][$ctx->name])) ? esc_textarea($post['tax_multiple_xpath'][$ctx->name]) : ''; ?>" style="width:50%;"/>
																<label><?php esc_html_e('Separated by', 'wp-all-import'); ?></label>
																<input type="text" class="small tax_delim" name="tax_multiple_delim[<?php echo esc_attr($ctx->name); ?>]" value="<?php echo esc_attr(( ! empty($post['tax_multiple_delim'][$ctx->name]) ) ? str_replace("&amp;","&", htmlentities(htmlentities($post['tax_multiple_delim'][$ctx->name]))) : ','); ?>" />
																<div class="input tax_is_full_search_multiple" style="margin: 10px 0;">
																	<input type="hidden" name="tax_is_full_search_multiple[<?php echo esc_attr($ctx->name); ?>]" value="0"/>
																	<input type="checkbox" id="tax_is_full_search_multiple_<?php echo esc_attr($ctx->name); ?>" class="switcher" <?php if ( ! empty($post['tax_is_full_search_multiple'][$ctx->name]) ) echo "checked='checked'"; ?> name="tax_is_full_search_multiple[<?php echo esc_attr($ctx->name); ?>]" value="1"/>
																	<label for="tax_is_full_search_multiple_<?php echo esc_attr($ctx->name);?>"><?php /* translators: %s: taxonomy name */ printf(esc_html__('Try to match terms to existing child %s', 'wp-all-import'), esc_html($ctx->labels->name)); ?></label>
																	<a href="#help" class="wpallimport-help" style="position: relative; top: -2px;" title="<?php /* translators: %1$s: taxonomy singular name, %2$s: taxonomy name, %3$s: taxonomy singular name, %4$s: taxonomy singular name */ echo esc_attr(sprintf(__('If this box is checked, WP All Import will try to match the %1$s in your import file to child %2$s. If it can\'t make a successful match, it will create a new %3$s using the %4$s in your import file.', 'wp-all-import'), $ctx->labels->singular_name, $ctx->labels->name, $ctx->labels->singular_name, $ctx->labels->singular_name)); ?>">?</a>
																</div>
															</div>
														</div>
														<?php if ($ctx->hierarchical): ?>
														<div class="input">
															<input type="radio" name="tax_logic[<?php echo esc_attr($ctx->name);?>]" value="hierarchical" id="tax_logic_hierarchical_<?php echo esc_attr($ctx->name);?>" class="switcher" <?php echo ( ! empty($post['tax_logic'][$ctx->name]) and $post['tax_logic'][$ctx->name] == 'hierarchical') ? 'checked="checked"' : ''; ?>/>
															<label for="tax_logic_hierarchical_<?php echo esc_attr($ctx->name);?>"><?php /* translators: %1$s: post type singular name, %2$s: taxonomy name */ printf(esc_html__('%1$ss have hierarchical (parent/child) %2$s (i.e. Sports > Golf > Clubs > Putters)', 'wp-all-import'), esc_html($custom_type->labels->singular_name), esc_html($ctx->labels->name)); ?></label>
															<div class="switcher-target-tax_logic_hierarchical_<?php echo esc_attr($ctx->name);?> sub_input">
																<div class="input">
																	<input type="hidden" name="tax_hierarchical_logic_entire[<?php echo esc_attr($ctx->name);?>]" value="0" />
																	<input type="checkbox" name="tax_hierarchical_logic_entire[<?php echo esc_attr($ctx->name);?>]" value="1" id="hierarchical_logic_entire_<?php echo esc_attr($ctx->name);?>" class="switcher" <?php echo (!empty($post['tax_hierarchical_logic_entire'][$ctx->name])) ? 'checked="checked"' : ''; ?>/>
																	<label for="hierarchical_logic_entire_<?php echo esc_attr($ctx->name);?>"><?php esc_html_e('An element in my file contains the entire hierarchy (i.e. you have an element with a value = Sports > Golf > Clubs > Putters)', 'wp-all-import'); ?></label>
																	<div class="switcher-target-hierarchical_logic_entire_<?php echo esc_attr($ctx->name);?> sub_input" style="margin-left: 20px; padding-left: 20px;">
																		<ul class="tax_hierarchical_logic no-margin">
																			<?php $txes_count = 0; if ( ! empty($post['tax_hierarchical_xpath'][$ctx->name])): foreach ($post['tax_hierarchical_xpath'][$ctx->name] as $k => $path) : if (empty($path)) continue; ?>
																				<li class="dragging">
																					<div style="position:relative;">
																						<input type="hidden" class="assign_term" name="tax_hierarchical_assing[<?php echo esc_attr($ctx->name);?>][<?php echo esc_attr($k);?>]" value="1"/>
																						<input type="text" class="widefat hierarchical_xpath_field" name="tax_hierarchical_xpath[<?php echo esc_attr($ctx->name); ?>][]" value="<?php echo esc_textarea($path); ?>"/>
																						<a href="javascript:void(0);" class="icon-item remove-ico"></a>
																					</div>
																				</li>
																			<?php $txes_count++; endforeach; endif; ?>
																			<?php if ( ! $txes_count): ?>
																				<li class="dragging">
																					<div style="position:relative;">
																						<input type="hidden" class="assign_term" name="tax_hierarchical_assing[<?php echo esc_attr($ctx->name);?>][0]" value="1"/>
																				    	<input type="text" class="widefat hierarchical_xpath_field" name="tax_hierarchical_xpath[<?php echo esc_attr($ctx->name); ?>][]" value=""/>
																						<a href="javascript:void(0);" class="icon-item remove-ico"></a>
																				    </div>
																			    </li>
																			<?php endif; ?>
																			<li class="dragging template">
																				<div style="position:relative;">
																					<input type="hidden" class="assign_term" name="tax_hierarchical_assing[<?php echo esc_attr($ctx->name);?>][NUMBER]" value="1"/>
																			    	<input type="text" class="widefat hierarchical_xpath_field" name="tax_hierarchical_xpath[<?php echo esc_attr($ctx->name); ?>][]" value=""/>
																					<a href="javascript:void(0);" class="icon-item remove-ico"></a>
																			    </div>
																		    </li>
																		</ul>
																		<label><?php esc_html_e('Separated by', 'wp-all-import'); ?></label>
																		<input type="text" class="small tax_delim" name="tax_hierarchical_delim[<?php echo esc_attr($ctx->name); ?>]" value="<?php echo esc_attr(( ! empty($post['tax_hierarchical_delim'][$ctx->name]) ) ? str_replace("&amp;","&", htmlentities(htmlentities($post['tax_hierarchical_delim'][$ctx->name]))) : '>'); ?>" />
																		<div class="input">
																			<input type="hidden" name="is_tax_hierarchical_group_delim[<?php echo esc_attr($ctx->name); ?>]" value="0" />
																			<input type="checkbox" id="is_tax_hierarchical_group_delim_<?php echo esc_attr($ctx->name); ?>" name="is_tax_hierarchical_group_delim[<?php echo esc_attr($ctx->name); ?>]" value="1" class="switcher" <?php echo ( ! empty($post['is_tax_hierarchical_group_delim'][$ctx->name])) ? 'checked="checked"': '' ?> />
																			<label for="is_tax_hierarchical_group_delim_<?php echo esc_attr($ctx->name); ?>"><?php esc_html_e('Separate hierarchy groups via symbol', 'wp-all-import'); ?></label>
																			<div class="switcher-target-is_tax_hierarchical_group_delim_<?php echo esc_attr($ctx->name);?> sub_input">
																				<label><?php esc_html_e('Separated by', 'wp-all-import'); ?></label>
																				<input type="text" class="small tax_delim" name="tax_hierarchical_group_delim[<?php echo esc_attr($ctx->name); ?>]" value="<?php echo esc_attr(( ! empty($post['tax_hierarchical_group_delim'][$ctx->name]) ) ? str_replace("&amp;","&", htmlentities(htmlentities($post['tax_hierarchical_group_delim'][$ctx->name]))) : '|'); ?>" />
																			</div>
																		</div>
																		<div class="input">
																			<a href="javascript:void(0);" class="icon-item add-new-cat" style="width: 200px;"><?php esc_html_e('Add Another Hierarchy Group','wp-all-import');?></a>
																		</div>
																	</div>
																</div>
																<div class="input">
																	<input type="hidden" name="tax_hierarchical_logic_manual[<?php echo esc_attr($ctx->name);?>]" value="0" />
																	<input type="checkbox" name="tax_hierarchical_logic_manual[<?php echo esc_attr($ctx->name);?>]" value="1" id="hierarchical_logic_manual_<?php echo esc_attr($ctx->name);?>" class="switcher" <?php echo (!empty($post['tax_hierarchical_logic_manual'][$ctx->name])) ? 'checked="checked"' : ''; ?>/>
																	<label for="hierarchical_logic_manual_<?php echo esc_attr($ctx->name);?>"><?php esc_html_e('Manually design the hierarchy with drag & drop', 'wp-all-import'); ?></label>
																	<div class="switcher-target-hierarchical_logic_manual_<?php echo esc_attr($ctx->name);?> sub_input">
																		<p style="margin-bottom: 10px;"><?php /* translators: %s: image URL */ echo wp_kses( sprintf(__('Drag the <img src="%s" class="wpallimport-drag-icon"/> to the right to create a child, drag up and down to re-order.', 'wp-all-import'), esc_url(WP_ALL_IMPORT_ROOT_URL . '/static/img/drag.png')), array('img' => array('src' => array(), 'class' => array())) ); ?></p>
																		<ol class="sortable no-margin" style="margin-left: 20px;">
																			<?php
																			if ( ! empty($post['post_taxonomies'][$ctx->name]) ):

																				$taxonomies_hierarchy = json_decode($post['post_taxonomies'][$ctx->name]);
																				
																				if (!empty($taxonomies_hierarchy) and is_array($taxonomies_hierarchy)): $i = 0; 

																					foreach ($taxonomies_hierarchy as $cat) { $i++;
																						if (is_null($cat->parent_id) or empty($cat->parent_id))
																						{
																							?>
																							<li id="item_<?php echo esc_attr($i); ?>" class="dragging">
																								<div class="drag-element">		
																									<input type="hidden" class="assign_term" value="1"/>
																									<input type="text" class="widefat xpath_field" value="<?php echo esc_textarea($cat->xpath); ?>"/>
																									
																									<?php do_action('pmxi_category_view', $cat, $i, $ctx->name, $post_type); ?>

																								</div>
																								<?php if ($i>1):?><a href="javascript:void(0);" class="icon-item remove-ico"></a><?php endif;?>
																								<?php echo wp_kses_post(reverse_taxonomies_html($taxonomies_hierarchy, $cat->item_id, $i, $ctx->name, $post_type)); ?>
																							</li>
																							<?php
																						}
																					}

																				endif;

																			endif;

																			if (empty($taxonomies_hierarchy) or !is_array($taxonomies_hierarchy) or count($taxonomies_hierarchy) == 0):
																				?>
																				<li id="item_1" class="dragging">
																					<div class="drag-element">
																						<input type="hidden" class="assign_term" value="1"/>
																						<input type="text" class="widefat xpath_field" value=""/>
																						<?php do_action('pmxi_category_view', false, 1, $ctx->name, $post_type); ?>
																					</div>
																				</li>
																				<?php
																			endif;
																			?>

																			<li id="item" class="template">
																		    	<div class="drag-element">
																		    		<input type="hidden" class="assign_term" value="1"/>
																		    		<input type="text" class="widefat xpath_field" value=""/>
																		    		<?php do_action('pmxi_category_view', false, false, $ctx->name, $post_type); ?>
																		    	</div>
																		    	<a href="javascript:void(0);" class="icon-item remove-ico"></a>
																		    </li>

																		</ol>																		
																		<input type="hidden" class="hierarhy-output" name="post_taxonomies[<?php echo esc_attr($ctx->name); ?>]" value="<?php echo empty($post['post_taxonomies'][$ctx->name]) ? '' : esc_attr($post['post_taxonomies'][$ctx->name]) ?>"/>
																		<?php do_action('pmxi_category_options_view', ((!empty($post['post_taxonomies'][$ctx->name])) ? $post['post_taxonomies'][$ctx->name] : false), $ctx->name, $post_type, $ctx->labels->name); ?>
																		<div class="input" style="margin-left:17px;">
																			<label><?php esc_html_e('Separated by', 'wp-all-import'); ?></label>
																			<input type="text" class="small tax_delim" name="tax_manualhierarchy_delim[<?php echo esc_attr($ctx->name); ?>]" value="<?php echo esc_attr(( ! empty($post['tax_manualhierarchy_delim'][$ctx->name]) ) ? str_replace("&amp;","&", htmlentities(htmlentities($post['tax_manualhierarchy_delim'][$ctx->name]))) : ','); ?>" />
																		</div>
																		<a href="javascript:void(0);" class="icon-item add-new-ico"><?php esc_html_e('Add Another Row','wp-all-import');?></a>
																	</div>																	
																</div>
															</div>
														</div>
														<?php endif; ?>
														<div class="input" style="margin: 4px;">
															<?php
																$tax_mapping = ( ! empty($post['tax_mapping'][$ctx->name]) ) ? json_decode($post['tax_mapping'][$ctx->name], true) : false;
															?>
															<input type="hidden" name="tax_enable_mapping[<?php echo esc_attr($ctx->name); ?>]" value="0"/>
															<input type="checkbox" id="tax_mapping_<?php echo esc_attr($ctx->name); ?>" class="pmxi_tax_mapping switcher" <?php if ( ! empty($post['tax_enable_mapping'][$ctx->name]) ) echo "checked='checked'"; ?> name="tax_enable_mapping[<?php echo esc_attr($ctx->name); ?>]" value="1"/>
															<label for="tax_mapping_<?php echo esc_attr($ctx->name);?>"><?php /* translators: %s: taxonomy name */ printf(esc_html__('Enable Mapping for %s', 'wp-all-import'), esc_html($ctx->labels->name)); ?></label>
															<div class="switcher-target-tax_mapping_<?php echo esc_attr($ctx->name);?> sub_input custom_type" rel="tax_mapping">
																<fieldset style="padding: 0;">
																	<table cellpadding="0" cellspacing="5" class="tax-form-table" rel="tax_mapping_<?php echo esc_attr($ctx->name); ?>" style="width: 100%;">
																		<thead>
																			<tr>
																				<td><?php esc_html_e('In Your File', 'wp-all-import') ?></td>
																				<td><?php esc_html_e('Translated To', 'wp-all-import') ?></td>
																				<td>&nbsp;</td>
																			</tr>
																		</thead>
																		<tbody>
																			<?php
																				if ( ! empty($tax_mapping) and is_array($tax_mapping) ){

																					foreach ($tax_mapping as $key => $value) {

																						$k = $key;

																						if (is_array($value)){
																							$keys = array_keys($value);
																							$k = $keys[0];
																						}

																						?>
																						<tr class="form-field">
																							<td>
																								<input type="text" class="mapping_from widefat" value="<?php echo esc_textarea($k); ?>">
																							</td>
																							<td>
																								<input type="text" class="mapping_to widefat" value="<?php echo esc_textarea((is_array($value)) ? $value[$k] : $value); ?>">
																							</td>
																							<td class="action remove">
																								<a href="#remove" style="right:-10px; top: 7px;"></a>
																							</td>
																						</tr>
																						<?php
																					}

																				}

																				if (empty($tax_mapping) or !is_array($tax_mapping) or count($tax_mapping) == 0):
																					?>
																					<tr class="form-field">
																						<td>
																							<input type="text" class="mapping_from widefat">
																						</td>
																						<td>
																							<input type="text" class="mapping_to widefat">
																						</td>
																						<td class="action remove">
																							<a href="#remove" style="right:-10px; top: 7px;"></a>
																						</td>
																					</tr>
																					<?php
																				endif;
																			?>
																			<tr class="form-field template">
																				<td>
																					<input type="text" class="mapping_from widefat">
																				</td>
																				<td>
																					<input type="text" class="mapping_to widefat">
																				</td>
																				<td class="action remove">
																					<a href="#remove" style="right:-10px; top: 7px;"></a>
																				</td>
																			</tr>
																			<tr>
																				<td colspan="3">
																					<a href="javascript:void(0);" title="<?php esc_attr_e('Add Another Rule', 'wp-all-import'); ?>" class="action add-new-key add-new-entry"><?php esc_html_e('Add Another Rule', 'wp-all-import') ?></a>
																				</td>
																			</tr>
																		</tbody>
																	</table>
																	<input type="hidden" name="tax_mapping[<?php echo esc_attr($ctx->name); ?>]" value="<?php if (!empty($post['tax_mapping'][$ctx->name])) echo esc_html($post['tax_mapping'][$ctx->name]); ?>"/>
																</fieldset>
																<div class="input">
																	<input type="hidden" name="tax_logic_mapping[<?php echo esc_attr($ctx->name); ?>]" value="0"/>
																	<input type="checkbox" id="tax_logic_mapping_<?php echo esc_attr($ctx->name); ?>" class="switcher" <?php if ( ! empty($post['tax_logic_mapping'][$ctx->name]) ) echo "checked='checked'"; ?> name="tax_logic_mapping[<?php echo esc_attr($ctx->name); ?>]" value="1"/>
																	<label for="tax_logic_mapping_<?php echo esc_attr($ctx->name); ?>"><?php esc_html_e('Enable full search for mapping', 'wp-all-import'); ?></label>
																	<a href="#help" class="wpallimport-help" style="position: relative; top: -2px;" title="<?php /* translators: %s: taxonomy name */ echo esc_attr(sprintf(__('If this box is checked, WP All Import will try to match terms to existing child %s.', 'wp-all-import'), $ctx->labels->name)); ?>">?</a>
																</div>
															</div>
														</div>
													</div>
												</div>
											</div>
										</td>
									</tr>
								</table>
								<?php endforeach; ?>

							</td>
						</tr>												
					</table>
				</div>
            <div class="wpallimport-collapsed closed wpallimport-section">
                <div class="wpallimport-content-section rad0" style="margin:0; border-top:1px solid #ddd; border-bottom: none; border-right: none; border-left: none; background: #f1f2f2; position:relative; bottom:-15px;">
                    <div class="wpallimport-collapsed-header">
                        <h3 style="color:#40acad;"><?php esc_html_e('Advanced Options','wp-all-import');?></h3>
                    </div>
                    <div class="wpallimport-collapsed-content" style="padding: 0;">
                        <div class="wpallimport-collapsed-content-inner">
                            <hr>
                            <table class="form-table" style="max-width:none;margin-top:1.5em;">
                                <tr>
                                    <td>
                                <div class="input">
                                    <input type="hidden" name="do_not_create_terms" value="0"/>
                                    <input type="checkbox" id="do_not_create_terms" name="do_not_create_terms" value="1" class="assign_post switcher" <?php echo !empty($post['do_not_create_terms']) ? 'checked="checked"': '' ?> />
                                    <label for="do_not_create_terms"><?php esc_html_e('Do not create new terms', 'wp-all-import'); ?></label><a href="#help" class="wpallimport-help" style="position: relative; top: -2px;" title="<?php esc_attr_e('When this box is checked WP All Import will not create any terms and will only match existing terms on your site.', 'wp-all-import'); ?>">?</a>
                                </div>
	                                <?php if ($private_ctx): ?>
                                    <div class="input">
                                        <input type="checkbox" id="show_hidden_ctx"/>
                                        <label for="show_hidden_ctx"><?php esc_html_e('Show "private" taxonomies', 'wp-all-import'); ?></label>
                                    </div>
	                                <?php endif;?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
		<div id="taxonomies_hints" style="display:none;">	
			<ul>
				<li><?php esc_html_e('Taxonomies that don\'t already exist on your site will be created unless the \'Do not create new terms\' option is checked.', 'wp-all-import'); ?></li>
				<li><?php esc_html_e('To import to existing parent taxonomies, use the existing taxonomy name or slug.', 'wp-all-import'); ?></li>
				<li><?php esc_html_e('To import to existing hierarchical taxonomies, create the entire hierarchy using the taxonomy names or slugs.', 'wp-all-import'); ?></li>			
			</ul>
		</div>
	</div>
<?php endif; ?>
