<?php if ( ! defined( 'ABSPATH' ) ) exit; // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals ?>
<script type="text/javascript">
	var plugin_url = '<?php echo esc_url(WP_ALL_IMPORT_ROOT_URL); ?>';
</script>

<div class="change_file">

	<div class="rad4 first-step-errors error-upload-rejected">
		<div class="wpallimport-notify-wrapper">
			<div class="error-headers exclamation">
				<h3><?php esc_html_e('File upload rejected by server', 'wp-all-import');?></h3>
				<h4><?php esc_html_e("Contact your host and have them check your server's error log.", "wp-all-import"); ?></h4>
			</div>		
		</div>		
		<a class="button wpallimport-large-button wpallimport-notify-read-more" href="http://www.wpallimport.com/documentation/troubleshooting/problems-with-import-files/?utm_source=import-plugin-free&utm_medium=error&utm_campaign=file-rejected" target="_blank"><?php esc_html_e('Read More', 'wp-all-import');?></a>		
	</div>

	<div class="rad4 first-step-errors error-file-validation" <?php if ( ! empty($upload_validation) ): ?> style="display:block;" <?php endif; ?>>
		<div class="wpallimport-notify-wrapper">
			<div class="error-headers exclamation">
				<h3><?php esc_html_e('There\'s a problem with your import file', 'wp-all-import');?></h3>
				<h4>
					<?php 
					if ( ! empty($upload_validation) ):
						$file_type = strtoupper(pmxi_getExtension($post['file']));
						printf(
							/* translators: %s: file extension in uppercase */
							esc_html__('This %s file has errors and is not valid.', 'wp-all-import'),
							esc_html($file_type)
						);
					endif;
					?>
				</h4>
			</div>		
		</div>		
		<a class="button wpallimport-large-button wpallimport-notify-read-more" href="http://www.wpallimport.com/documentation/troubleshooting/problems-with-import-files/#invalid?utm_source=import-plugin-free&utm_medium=error&utm_campaign=invalid-file" target="_blank"><?php esc_html_e('Read More', 'wp-all-import');?></a>		
	</div>	
	
	<div class="wpallimport-content-section">
		<div class="wpallimport-collapsed-header" style="padding-left:30px;">
			<h3><?php esc_html_e('Import File','wp-all-import');?></h3>	
		</div>
		<div class="wpallimport-collapsed-content" style="padding-bottom: 40px;">
			<hr>
			<table class="form-table" style="max-width:none;">
				<tr>
					<td colspan="3">

						<div class="wpallimport-import-types">
							<h3><?php esc_html_e('Specify the location of the file to use for future runs of this import.', 'wp-all-import'); ?></h3>
							<a class="wpallimport-import-from wpallimport-upload-type <?php echo 'upload' == $import->type ? 'selected' : '' ?>" rel="upload_type" href="javascript:void(0);">
								<span class="wpallimport-icon"></span>
								<span class="wpallimport-icon-label"><?php esc_html_e('Upload a file', 'wp-all-import'); ?></span>
							</a>
							<a class="wpallimport-import-from wpallimport-url-type <?php echo ('url' == $import->type || 'ftp' == $import->type) ? 'selected' : '' ?>" rel="url_type" href="javascript:void(0);">
								<span class="wpallimport-icon"></span>
								<span class="wpallimport-icon-label"><?php esc_html_e('Download a file', 'wp-all-import'); ?></span>
							</a>
							<a class="wpallimport-import-from wpallimport-file-type <?php echo 'file' == $import->type ? 'selected' : '' ?>" rel="file_type" href="javascript:void(0);">
								<span class="wpallimport-icon"></span>
								<span class="wpallimport-icon-label"><?php esc_html_e('Use existing file', 'wp-all-import'); ?></span>
							</a>
						</div>						
						
						<input type="hidden" value="upload" name="new_type"/>

						<div class="wpallimport-upload-type-container" rel="upload_type">							
							<div id="plupload-ui" class="wpallimport-file-type-options">
					            <div>				                
					                <input type="hidden" name="filepath" value="<?php if ('upload' == $import->type) echo esc_attr($import->path); ?>" id="filepath"/>
					                <a id="select-files" href="javascript:void(0);"/><?php esc_html_e('Click here to select file from your computer...', 'wp-all-import'); ?></a>
					                <div id="progressbar" class="wpallimport-progressbar">
					                	<?php if ('upload' == $import->type) {
					                		echo wp_kses( sprintf(
					                			/* translators: %s: uploaded file name */
					                			__('<span>Upload Complete</span> - %s 100%%', 'wp-all-import'),
					                			esc_html(basename($import->path))
					                		), array('span' => array()) );
					                	} ?>
					                </div>
					                <div id="progress" class="wpallimport-progress" <?php if ('upload' == $import->type):?>style="display: block;"<?php endif;?>>
					                	<div id="upload_process" class="wpallimport-upload-process"></div>				                	
					                </div>
					            </div>
					        </div>
						</div>
                        <div class="wpallimport-upload-type-container" rel="url_type">
                            <div class="wpallimport-choose-data-type">
                                <a class="wpallimport-download-from rad4 wpallimport-download-file-from-url <?php if ($import->type == 'url') echo 'wpallimport-download-from-checked'; ?>" rel="url" href="javascript:void(0);">
                                    <span class="wpallimport-download-from-title"><?php esc_html_e('From URL', 'wp-all-import'); ?></span>
                                    <span class="wpallimport-download-from-arrow"></span>
                                </a>
                                <a class="wpallimport-download-from rad4 wpallimport-download-file-from-ftp <?php if ($import->type == 'ftp') echo 'wpallimport-download-from-checked'; ?>" rel="ftp" href="javascript:void(0);">
                                    <span class="wpallimport-download-from-title"><?php esc_html_e('From FTP/SFTP', 'wp-all-import'); ?></span>
                                    <span class="wpallimport-download-from-arrow"></span>
                                </a>
                            </div>
                        </div>
						<div class="wpallimport-upload-type-container" rel="file_type">		
							<?php $upload_dir = wp_upload_dir(); ?>					
							<div class="wpallimport-file-type-options">								
								
								<div id="file_selector" class="dd-container" style="width: 600px;">
									<div class="dd-select" style="width: 600px; background: none repeat scroll 0% 0% rgb(238, 238, 238);">
										<input type="hidden" class="dd-selected-value" value="">
										<a class="dd-selected" style="color: rgb(207, 206, 202);">
											<label class="dd-selected-text "><?php esc_html_e('Select a previously uploaded file', 'wp-all-import'); ?></label>
										</a>
										<span class="dd-pointer dd-pointer-down"></span>
									</div>									
								</div>								
								
								<input type="hidden" name="file" value="<?php if ('file' == $import->type) echo esc_attr($import->path); ?>"/>	
								
								<div class="wpallimport-note" style="margin: 0 auto; ">
									<?php echo wp_kses( sprintf(
										/* translators: %s: upload directory path */
										__('Files uploaded to <strong>%s</strong> will appear in this list.', 'wp-all-import'),
										esc_html($upload_dir['basedir'] . '/wpallimport/files')
									), array('strong' => array()) ); ?>
								</div>
								<div class="wpallimport-free-edition-notice">									
									<a href="https://www.wpallimport.com/checkout/?edd_action=add_to_cart&download_id=5839966&edd_options%5Bprice_id%5D=1&discount=welcome-upgrade-99&utm_source=import-plugin-free&utm_medium=error&utm_campaign=use-existing-file" target="_blank" class="upgrade_link"><?php esc_html_e('Upgrade to the Pro edition of WP All Import to Use Existing Files', 'wp-all-import');?></a>
									<p style="margin-top:16px;"><?php esc_html_e('If you already own it, remove the free edition and install the Pro edition.', 'wp-all-import'); ?></p>
								</div>
							</div>
						</div>
                        <div class="wpallimport-download-resource-step-two">
                            <div class="wpallimport-download-resource wpallimport-download-resource-step-two-url">
                                <div class="wpallimport-file-type-options">
                                    <span class="wpallimport-input-icon wpallimport-url-icon"></span>
                                    <input type="text" class="regular-text" name="url" value="<?php echo ('url' == $import->type) ? esc_attr($import->path) : 'Enter a web address to download the file from...'; ?>"/>
                                    <!--a href="javascript:void(0);" class="wpallimport-download-from-url"><?php esc_html_e('Upload', 'wp-all-import'); ?></a-->
                                </div>
                                <div class="wpallimport-free-edition-notice">
                                    <a href="https://www.wpallimport.com/checkout/?edd_action=add_to_cart&download_id=5839966&edd_options%5Bprice_id%5D=1&discount=welcome-upgrade-99&utm_source=import-plugin-free&utm_medium=upgrade-notice&utm_campaign=download-from-url" target="_blank" class="upgrade_link"><?php esc_html_e('Upgrade to the Pro edition of WP All Import to Download from URL', 'wp-all-import');?></a>
                                    <p style="margin-top:16px;"><?php esc_html_e('If you already own it, remove the free edition and install the Pro edition.', 'wp-all-import'); ?></p>
                                </div>
                                <input type="hidden" name="downloaded"/>
                            </div>
                            <div class="wpallimport-download-resource wpallimport-download-resource-step-two-ftp">
                                <div class="wpallimport-file-type-options">
                                    <span class="wpallimport-input-icon wpallimport-ftp-host-icon"></span>
                                    <input type="text" class="regular-text" name="ftp_host" value="<?php echo ( ! empty($import->options['ftp_host'])) ? esc_attr($import->options['ftp_host']) : ''; ?>" placeholder="Enter FTP server address"/>
                                    <a class="wpallimport-help" href="#help" style="position: relative; top: -2px;" title="<?php esc_html_e('The server address of your FTP/SFTP server. This can be an IP address or domain name. You do not need to include the connection protocol. For example, files.example.com or 127.0.0.1', 'wp-all-import'); ?>">?</a>
                                </div>
                                <div class="wpallimport-file-type-options">
                                    <span class="wpallimport-input-icon wpallimport-ftp-port-icon"></span>
                                    <input type="text" class="regular-text" name="ftp_port" value="<?php echo ( ! empty($import->options['ftp_port'])) ? esc_attr($import->options['ftp_port']) : ''; ?>" placeholder="Enter FTP port"/>
                                    <a class="wpallimport-help" href="#help" style="position: relative; top: -2px;" title="<?php esc_html_e('The port that your server uses. FTP usually uses port 21, SFTP usually uses port 22', 'wp-all-import'); ?>">?</a>
                                </div>
                                <div class="wpallimport-file-type-options">
                                    <span class="wpallimport-input-icon wpallimport-ftp-username-icon"></span>
                                    <input type="text" class="regular-text" name="ftp_username" value="<?php echo ( ! empty($import->options['ftp_username'])) ? esc_attr($import->options['ftp_username']) : ''; ?>" placeholder="Enter FTP username"/>
                                    <a class="wpallimport-help" href="#help" style="position: relative; top: -2px;" title="<?php esc_html_e('If you don\'t know your FTP/SFTP username, contact the host of the server.', 'wp-all-import'); ?>">?</a>
                                </div>
                                <div class="wpallimport-file-type-options">
                                    <span class="wpallimport-input-icon wpallimport-ftp-password-icon"></span>
                                    <input type="text" class="regular-text" name="ftp_password" value="<?php echo ( ! empty($import->options['ftp_password'])) ? esc_attr($import->options['ftp_password']) : ''; ?>" placeholder="Enter FTP password"/>
                                    <a class="wpallimport-help" href="#help" style="position: relative; top: -2px;" title="<?php esc_html_e('These passwords are stored in plaintext in your WordPress database. Ideally, the user account should only have read access to the files that you are importing.
<br/><br/>Even if the password is correct, sometimes your host will require SFTP connections to use an SSH key and will deny connection attempts using passwords. If you\'re unable to login, you don\'t have a SSH/SFTP Private Key, and you are sure the password is correct, contact the host of the server.', 'wp-all-import'); ?>">?</a>
                                </div>
                                <div class="wpallimport-file-type-options">
                                    <span class="wpallimport-input-icon wpallimport-ftp-private-key-icon"></span>
                                    <textarea class="wpai-ftp-text-area" name="ftp_private_key" placeholder="SFTP Private Key"><?php echo ( ! empty($import->options['ftp_private_key'])) ? esc_attr($import->options['ftp_private_key']) : ''; ?></textarea>
                                    <a class="wpallimport-help" id="wpai-ftp-text-area-help" href="#help" style="position: relative; top: -2px;" title="<?php esc_html_e('If you don\'t know if you need an SFTP Private Key, contact the host of the server.', 'wp-all-import'); ?>">?</a>
                                </div>
                                <div class="wpallimport-file-type-options ftp_path">

                                    <input type="text" class="regular-text" name="ftp_path"
                                           value="<?php echo ( ! empty($import->options['ftp_path'])) ? esc_attr($import->options['ftp_path']) : ''; ?>"
                                           placeholder="FTP file path"/>

                                    <a class="wpallimport-ftp-builder rad4 button wpallimport-large-button wpai-ftp-select-file-button"
                                       href="javascript:void(0);">
                                        <div class="easing-spinner"
                                             style="display: none; left: 36px !important; top: 2px;">
                                            <div class="double-bounce1"></div>
                                            <div class="double-bounce2"></div>
                                        </div>
										<?php esc_html_e( 'Select File', 'wp-all-import' ); ?>
                                    </a>

                                </div>
                                <div style="display:block;position:relative;width:75%;margin:auto;">
                                    <span class="wpallimport-input-icon wpallimport-ftp-path-icon"></span>
                                    <a class="wpallimport-help" href="#help"
                                       style="position: absolute;top: -32px;right: -30px;"
                                       title="<?php esc_html_e( 'The path to the file you want to import. In case multiple files are found, only the first will be downloaded. Examples: /home/ftpuser/import.csv or import-files/*.csv', 'wp-all-import' ); ?>">?</a>
                                </div>

                                <span class="wpallimport-ftp-builder-wrap">
                                    <div class="wpallimport-ftp-connection-builder" id="wpallimport-ftp-connection-builder"></div>
                                    <input type="hidden" id="wpai-ftp-browser-nonce" value="<?php echo esc_attr(wp_create_nonce( 'wpai-ftp-browser' )); ?>"/>
                                </span>

                                <div class="rad4 first-step-errors wpai-ftp-connection-error">
                                    <div class="wpallimport-notify-wrapper">
                                        <div class="error-headers exclamation">
                                            <h3><?php esc_html_e('Unable to Connect', 'wp-all-import');?></h3>
                                            <br/>
                                            <span id="wpai-ftp-connection-error-message"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="wpallimport-free-edition-notice">
                                    <a href="https://www.wpallimport.com/checkout/?edd_action=add_to_cart&download_id=5839966&edd_options%5Bprice_id%5D=1&discount=welcome-upgrade-99&utm_source=import-plugin-free&utm_medium=upgrade-notice&utm_campaign=download-from-url" target="_blank" class="upgrade_link"><?php esc_html_e('Upgrade to the Pro edition of WP All Import to Download from FTP/SFTP', 'wp-all-import');?></a>
                                    <p style="margin-top:16px;"><?php esc_html_e('If you already own it, remove the free edition and install the Pro edition.', 'wp-all-import'); ?></p>
                                </div>
                            </div>
                        </div>
					</td>
				</tr>
			</table>
		</div>		
	</div>
</div>