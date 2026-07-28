<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
if ( ! defined( 'ABSPATH' ) ) exit;
$custom_type = get_post_type_object( $post_type );
?>

<div class="wpallimport-collapsed closed wpallimport-section ">
	<div class="wpallimport-content-section ">
		<div class="wpallimport-collapsed-header">
			<h3><?php
				/* translators: %s: custom post type singular name */
				printf(esc_html__('Other %s Options','wp-all-import'), esc_html($custom_type->labels->singular_name));?></h3>
		</div>
		<div class="wpallimport-collapsed-content" style="padding: 0;">
			<div class="wpallimport-collapsed-content-inner">
				<table class="form-table" style="max-width:none;">
					<tr>
						<td>					
							<input type="hidden" name="encoding" value="<?php echo ($this->isWizard) ? esc_attr(PMXI_Plugin::$session->encoding) : esc_attr($post['encoding']); ?>"/>
							<input type="hidden" name="delimiter" value="<?php echo ($this->isWizard) ? esc_attr(PMXI_Plugin::$session->is_csv) : esc_attr($post['delimiter']); ?>"/>

							<?php $is_support_post_format = ( current_theme_supports( 'post-formats' ) && post_type_supports( $post_type, 'post-formats' ) ) ? true : false; ?>
							
							<h4><?php esc_html_e('Post Status', 'wp-all-import') ?></h4>								
							<div class="input">
								<input type="radio" id="status_publish" name="status" value="publish" <?php echo 'publish' == $post['status'] ? 'checked="checked"' : '' ?> class="switcher"/>
								<label for="status_publish"><?php esc_html_e('Published', 'wp-all-import') ?></label>
							</div>
							<div class="input">
								<input type="radio" id="status_draft" name="status" value="draft" <?php echo 'draft' == $post['status'] ? 'checked="checked"' : '' ?> class="switcher"/>
								<label for="status_draft"><?php esc_html_e('Draft', 'wp-all-import') ?></label>
							</div>
							<div class="input fleft" style="position:relative;width:220px;">
								<input type="radio" id="status_xpath" class="switcher" name="status" value="xpath" <?php echo 'xpath' == $post['status'] ? 'checked="checked"': '' ?>/>
								<label for="status_xpath"><?php esc_html_e('Set with XPath', 'wp-all-import' )?></label> <br>
								<div class="switcher-target-status_xpath">
									<div class="input">
										&nbsp;<input type="text" class="smaller-text" name="status_xpath" style="width:190px;" value="<?php echo esc_attr($post['status_xpath']) ?>"/>
										<a href="#help" class="wpallimport-help" title="<?php esc_attr_e('The value of presented XPath should be one of the following: (\'publish\', \'draft\', \'trash\', \'private\').', 'wp-all-import') ?>" style="position:relative; top:13px; float: right;">?</a>
									</div>
								</div>
							</div>								
							<div class="clear"></div>													
						</td>
					</tr>			
					<tr>
						<td>					
							<h4><?php esc_html_e('Post Dates', 'wp-all-import') ?><a href="#help" class="wpallimport-help" style="position:relative; top: 1px;" title="<?php esc_attr_e('Use any format supported by the PHP <b>strtotime</b> function. That means pretty much any human-readable date will work.', 'wp-all-import') ?>">?</a></h4>
							<div class="input">
								<input type="radio" id="date_type_specific" class="switcher" name="date_type" value="specific" <?php echo 'random' != $post['date_type'] ? 'checked="checked"' : '' ?> />
								<label for="date_type_specific">
									<?php esc_html_e('As specified', 'wp-all-import') ?>
								</label>
								<div class="switcher-target-date_type_specific" style="vertical-align:middle; margin-top: 5px; margin-bottom: 10px;">
									<input type="text" class="datepicker" name="date" value="<?php echo esc_attr($post['date']) ?>"/>
								</div>
							</div>
							<div class="input">
								<input type="radio" id="date_type_random" class="switcher" name="date_type" value="random" <?php echo 'random' == $post['date_type'] ? 'checked="checked"' : '' ?> />
								<label for="date_type_random">
									<?php esc_html_e('Random dates', 'wp-all-import') ?><a href="#help" class="wpallimport-help" style="position:relative; top:0;" title="<?php esc_attr_e('Posts will be randomly assigned dates in this range. WordPress ensures posts with dates in the future will not appear until their date has been reached.', 'wp-all-import') ?>">?</a>
								</label>
								<div class="switcher-target-date_type_random" style="vertical-align:middle; margin-top:5px;">
									<input type="text" class="datepicker" name="date_start" value="<?php echo esc_attr($post['date_start']) ?>" />
									<?php esc_html_e('and', 'wp-all-import') ?>
									<input type="text" class="datepicker" name="date_end" value="<?php echo esc_attr($post['date_end']) ?>" />
								</div>
							</div>											
						</td>
					</tr>
                    <tr>
                        <td>
                            <h4><?php esc_html_e('Comments', 'wp-all-import'); ?></h4>
                            <div class="input">
                                <input type="radio" id="comment_status_open" name="comment_status" value="open" <?php echo 'open' == $post['comment_status'] ? 'checked="checked"' : '' ?> class="switcher"/>
                                <label for="comment_status_open"><?php esc_html_e('Open', 'wp-all-import') ?></label>
                            </div>
                            <div class="input">
                                <input type="radio" id="comment_status_closed" name="comment_status" value="closed" <?php echo 'closed' == $post['comment_status'] ? 'checked="checked"' : '' ?> class="switcher"/>
                                <label for="comment_status_closed"><?php esc_html_e('Closed', 'wp-all-import') ?></label>
                            </div>
                            <div class="input fleft" style="position:relative;width:220px;">
                                <input type="radio" id="comment_status_xpath" class="switcher" name="comment_status" value="xpath" <?php echo 'xpath' == $post['comment_status'] ? 'checked="checked"': '' ?>/>
                                <label for="comment_status_xpath"><?php esc_html_e('Set with XPath', 'wp-all-import' )?></label> <br>
                                <div class="switcher-target-comment_status_xpath">
                                    <div class="input">
                                        &nbsp;<input type="text" class="smaller-text" name="comment_status_xpath" style="width:190px;" value="<?php echo esc_attr($post['comment_status_xpath']) ?>"/>
                                        <a href="#help" class="wpallimport-help" title="<?php esc_attr_e('The value of presented XPath should be one of the following: (\'open\', \'closed\').', 'wp-all-import') ?>" style="position:relative; top:13px; float: right;">?</a>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
					<tr>
						<td>	
							<h4><?php esc_html_e('Trackbacks and Pingbacks', 'wp-all-import'); ?></h4>
							<div class="input">
								<input type="radio" id="ping_status_open" name="ping_status" value="open" <?php echo 'open' == $post['ping_status'] ? 'checked="checked"' : '' ?> class="switcher"/>
								<label for="ping_status_open"><?php esc_html_e('Open', 'wp-all-import') ?></label>
							</div>
							<div class="input">
								<input type="radio" id="ping_status_closed" name="ping_status" value="closed" <?php echo 'closed' == $post['ping_status'] ? 'checked="checked"' : '' ?> class="switcher"/>
								<label for="ping_status_closed"><?php esc_html_e('Closed', 'wp-all-import') ?></label>
							</div>
							<div class="input fleft" style="position:relative;width:220px;">
								<input type="radio" id="ping_status_xpath" class="switcher" name="ping_status" value="xpath" <?php echo 'xpath' == $post['ping_status'] ? 'checked="checked"': '' ?>/>
								<label for="ping_status_xpath"><?php esc_html_e('Set with XPath', 'wp-all-import' )?></label> <br>
								<div class="switcher-target-ping_status_xpath">
									<div class="input">
										&nbsp;<input type="text" class="smaller-text" name="ping_status_xpath" style="width:190px;" value="<?php echo esc_attr($post['ping_status_xpath']) ?>"/>
										<a href="#help" class="wpallimport-help" title="<?php esc_attr_e('The value of presented XPath should be one of the following: (\'open\', \'closed\').', 'wp-all-import') ?>" style="position:relative; top:13px; float: right;">?</a>
									</div>
								</div>
							</div>								
						</td>
					</tr>
					<tr>
						<td>	
							<h4><?php esc_html_e('Post Slug', 'wp-all-import') ?></h4>
							<div>
								<input type="text" name="post_slug" style="width:100%;" value="<?php echo esc_attr($post['post_slug']); ?>" />
							</div> 
						</td>
					</tr>
					<tr>
						<td>
							<h4><?php esc_html_e('Post Author', 'wp-all-import') ?></h4>
							<div>
								<input type="text" name="author" value="<?php echo esc_attr($post['author']) ?>"/> <a href="#help" class="wpallimport-help" style="position: relative; top: -2px;" title="<?php esc_attr_e('Assign the post to an existing user account by specifying the user ID, username, or e-mail address.', 'wp-all-import') ?>">?</a>			
							</div>																	
						</td>								
					</tr>	
					<tr>
						<td>
							<h4><?php esc_html_e('Download & Import Attachments', 'wp-all-import') ?></h4>
							<div class="delimiter-field">
								<input type="text" name="attachments" value="<?php echo esc_attr($post['attachments']) ?>" />
								<input type="text" class="small" name="atch_delim" value="<?php echo esc_attr($post['atch_delim']) ?>" />
							</div>			
							<div class="input" style="margin:3px;">
								<input type="hidden" name="is_search_existing_attach" value="0" />
								<input type="checkbox" id="is_search_existing_attach" name="is_search_existing_attach" value="1" <?php echo $post['is_search_existing_attach'] ? 'checked="checked"' : '' ?> class="fix_checkbox"/>
								<label for="is_search_existing_attach"><?php esc_html_e('Search for existing attachments to prevent duplicates in media library','wp-all-import');?> </label>						
							</div>														
						</td>								
					</tr>	
					<?php if ($is_support_post_format):?>
					<tr>
						<td>													
							<h4><?php esc_html_e('Post Format', 'wp-all-import') ?></h4>
							<div>
								<?php $post_formats = get_theme_support( 'post-formats' ); ?>

								<div class="input">
									<input type="radio" id="post_format_<?php echo "standart_" . esc_attr($post_type); ?>" name="post_format" value="0" <?php echo (empty($post['post_format']) or ( empty($post_formats) )) ? 'checked="checked"' : '' ?> />
									<label for="post_format_<?php echo "standart_" . esc_attr($post_type); ?>"><?php esc_html_e( "Standard", 'wp-all-import') ?></label>
								</div>

								<?php								
									if ( ! empty($post_formats[0]) ){
										foreach ($post_formats[0] as $post_format) {
											?>
											<div class="input">
												<input type="radio" id="post_format_<?php echo esc_attr($post_format); ?>" name="post_format" value="<?php echo esc_attr($post_format); ?>" <?php echo $post_format == $post['post_format'] ? 'checked="checked"' : '' ?> />
												<label for="post_format_<?php echo esc_attr($post_format); ?>"><?php echo esc_html(ucfirst($post_format)); ?></label>
											</div>
											<?php
										}
									}			
								?>
								<div class="input fleft" style="position:relative;width:220px; ">
									<input type="radio" id="post_format_xpath" class="switcher" name="post_format" value="xpath" <?php echo 'xpath' == $post['post_format'] ? 'checked="checked"': '' ?>/>
									<label for="post_format_xpath"><?php esc_html_e('Set with XPath', 'wp-all-import' )?></label> <br>
									<div class="switcher-target-post_format_xpath">
										<div class="input">
											&nbsp;<input type="text" class="smaller-text" name="post_format_xpath" style="width:190px;" value="<?php echo esc_attr($post['post_format_xpath']) ?>"/>											
										</div>
									</div>
								</div>	
							</div>									
						</td>
					</tr>
					<?php endif; ?>		

					<?php
					global $wp_version;
					if ( 'page' == $post_type || version_compare($wp_version, '4.7.0', '>=') ):?>
					<tr>
						<td>
							<h4><?php esc_html_e('Page Template', 'wp-all-import') ?></h4>
							<div class="input">
								<input type="radio" id="is_multiple_page_template_yes" name="is_multiple_page_template" value="yes" <?php echo 'yes' == $post['is_multiple_page_template'] ? 'checked="checked"' : '' ?> class="switcher" style="margin-left:0;"/>
								<label for="is_multiple_page_template_yes"><?php esc_html_e('Select a template', 'wp-all-import') ?></label>
								<div class="switcher-target-is_multiple_page_template_yes">
									<div class="input">
										<select name="page_template" id="page_template">
											<option value='default'><?php esc_html_e('Default', 'wp-all-import') ?></option>
											<?php page_template_dropdown($post['page_template']); ?>
										</select>
									</div>
								</div>
							</div>
							<div class="input fleft" style="position:relative;width:220px; margin-top: 5px;">
								<input type="radio" id="is_multiple_page_template_no" class="switcher" name="is_multiple_page_template" value="no" <?php echo 'no' == $post['is_multiple_page_template'] ? 'checked="checked"': '' ?> style="margin-left:0;"/>
								<label for="is_multiple_page_template_no"><?php esc_html_e('Set with XPath', 'wp-all-import' )?></label> <br>
								<div class="switcher-target-is_multiple_page_template_no">
									<div class="input">
										&nbsp;<input type="text" class="smaller-text" name="single_page_template" style="width:190px;" value="<?php echo esc_attr($post['single_page_template']) ?>"/>										
									</div>
								</div>
							</div>	
						</td>
					</tr>
					<?php endif; ?>
					<tr>
						<td>
							<?php if ( 'page' == $post_type ):?>	

								<h4><?php esc_html_e('Page Parent', 'wp-all-import') ?><a href="#help" class="wpallimport-help" title="<?php esc_attr_e('Enter the ID, title, or slug of the desired page parent. If adding the child and parent pages in the same import, set \'Records per Iteration\' to 1, run the import twice, or run separate imports for child and parent pages.', 'wp-all-import') ?>" style="position:relative; top:-1px;">?</a></h4>

								<div class="input">
									<input type="radio" id="is_multiple_page_parent_yes" name="is_multiple_page_parent" value="yes" <?php echo 'yes' == $post['is_multiple_page_parent'] ? 'checked="checked"' : '' ?> class="switcher" style="margin-left:0;"/>
									<label for="is_multiple_page_parent_yes"><?php esc_html_e('Select page parent', 'wp-all-import') ?></label>
									<div class="switcher-target-is_multiple_page_parent_yes">
										<div class="input">
										<?php wp_dropdown_pages(array('post_type' => 'page', 'selected' => esc_attr($post['parent']), 'name' => 'parent', 'show_option_none' => esc_html__('(no parent)', 'wp-all-import'), 'sort_column'=> 'menu_order, post_title', 'number' => 500)); ?>
										</div>
									</div>
								</div>

								<div class="input fleft" style="position:relative;width:220px; margin-top: 5px;">
									<input type="radio" id="is_multiple_page_parent_no" class="switcher" name="is_multiple_page_parent" value="no" <?php echo 'no' == $post['is_multiple_page_parent'] ? 'checked="checked"': '' ?> style="margin-left:0;"/>
									<label for="is_multiple_page_parent_no"><?php esc_html_e('Set with XPath', 'wp-all-import' )?></label> <br>
									<div class="switcher-target-is_multiple_page_parent_no">
										<div class="input">
											&nbsp;<input type="text" class="smaller-text" name="single_page_parent" style="width:190px;" value="<?php echo esc_attr($post['single_page_parent']) ?>"/>										
										</div>
									</div>
								</div>	

							<?php endif;?>

							<?php if ( 'page' != $post_type && $custom_type->hierarchical ): ?>

								<h4><?php esc_html_e('Post Parent', 'wp-all-import') ?><a href="#help" class="wpallimport-help" title="<?php esc_attr_e('Enter the ID, title, or slug of the desired post parent. If adding the child and parent posts in the same import, set \'Records per Iteration\' to 1, run the import twice, or run separate imports for child and parent posts.', 'wp-all-import') ?>" style="position:relative; top:-1px;">?</a></h4>
								
								<div class="input">
									<input type="radio" id="is_multiple_page_parent_yes" name="is_multiple_page_parent" value="yes" <?php echo 'yes' == $post['is_multiple_page_parent'] ? 'checked="checked"' : '' ?> class="switcher" style="margin-left:0;"/>
									<label for="is_multiple_page_parent_yes"><?php esc_html_e('Set post parent', 'wp-all-import') ?></label>
									<div class="switcher-target-is_multiple_page_parent_yes">
										<div class="input">
											<input type="text" class="" name="parent" value="<?php echo esc_attr($post['parent']) ?>" />									
										</div>
									</div>
								</div>

								<div class="input fleft" style="position:relative;width:220px; margin-top: 5px;">
									<input type="radio" id="is_multiple_page_parent_no" class="switcher" name="is_multiple_page_parent" value="no" <?php echo 'no' == $post['is_multiple_page_parent'] ? 'checked="checked"': '' ?> style="margin-left:0;"/>
									<label for="is_multiple_page_parent_no"><?php esc_html_e('Set with XPath', 'wp-all-import' )?></label> <br>
									<div class="switcher-target-is_multiple_page_parent_no">
										<div class="input">
											&nbsp;<input type="text" class="smaller-text" name="single_page_parent" style="width:190px;" value="<?php echo esc_attr($post['single_page_parent']) ?>"/>										
										</div>
									</div>
								</div>	

							<?php endif; ?>
														
						</td>
					</tr>					
					<tr>
						<td>
							<h4><?php esc_html_e('Menu Order', 'wp-all-import') ?></h4>
							<div class="input">
								<input type="text" class="" name="order" value="<?php echo esc_attr($post['order']) ?>" />
							</div>
						</td>
					</tr>					
					<?php if ( ! empty($post['deligate']) and $post['deligate'] == 'wpallexport' ): ?>
					<tr>
						<td>
							<h4><?php esc_html_e('Dynamic Post Type', 'wp-all-import') ?></h4>
							<div class="input">
								<div style="margin: 11px; float: left;">
									<input type="hidden" name="is_override_post_type" value="0"/>
									<input type="checkbox" value="1" class="switcher-horizontal fix_checkbox" name="is_override_post_type" id="is_override_post_type" <?php echo ( ! empty($post['is_override_post_type'])) ? 'checked="checked"' : '' ?>>
									<label for="is_override_post_type"><?php esc_html_e('Slug','wp-all-import');?></label>
								</div>
								<div class="switcher-target-is_override_post_type" style="float: left; overflow: hidden;">
									<input type="text" name="post_type_xpath" style="vertical-align:middle; line-height: 26px;" value="<?php echo esc_attr($post['post_type_xpath']) ?>" />											
								</div>	
								<a href="#help" class="wpallimport-help" title="<?php esc_attr_e('If records in this import have different post types specify the slug of the desired post type here.
', 'wp-all-import') ?>" style="position:relative; top:12px;">?</a>
							</div>
						</td>
					</tr>			
					<?php endif; ?>									
				</table>
			</div>
		</div>
	</div>
</div>
