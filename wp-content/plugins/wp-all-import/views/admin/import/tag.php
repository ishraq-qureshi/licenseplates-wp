<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="tag">		
	<div>
		<?php if ( ! empty($elements->length) ):?>		
			<div class="title">
				<!--h3><?php esc_html_e('Elements', 'wp-all-import'); ?></h3-->
				<div class="navigation">
					<?php if ($tagno > 1): ?><a href="#prev" class="previous_element">&nbsp;</a><?php else: ?><span class="previous_element">&nbsp;</span><?php endif ?>
					<?php /* translators: %1$s: current tag number, %2$s: total count */ ?>
					<?php echo wp_kses( sprintf(__('<strong><input type="text" value="%1$s" name="tagno" class="tagno"/></strong><span class="out_of"> of <strong class="pmxi_count">%2$s</strong></span>', 'wp-all-import'), intval($tagno), intval(PMXI_Plugin::$session->count)), array('strong' => array('class' => array()), 'span' => array('class' => array()), 'input' => array('type' => array(), 'value' => array(), 'name' => array(), 'class' => array())) ); ?>
					<?php if ($tagno < PMXI_Plugin::$session->count): ?><a href="#next" class="next_element">&nbsp;</a><?php else: ?><span class="next_element">&nbsp;</span><?php endif ?>
				</div>
			</div>
			<div class="clear"></div>
			<div class="wpallimport-xml resetable"> 
				<?php 
				if ( ! empty($elements->length) ):					
					if ( PMXI_Plugin::$session->options['delimiter'] ):
						PMXI_Render::render_csv_element($elements->item($elements->length > 1 ? $tagno : 0), true);
					else:
						PMXI_Render::render_xml_element($elements->item($elements->length > 1 ? $tagno : 0), true);
					endif;

				endif;
				?>			
			</div>			
		<?php else: ?>
			<div class="error inline below-h2" style="padding:10px; margin-top:45px;">
				<?php esc_html_e('History file not found. Probably you are using wrong encoding.', 'wp-all-import'); ?>
			</div>
		<?php endif; ?>		
	</div>
</div>