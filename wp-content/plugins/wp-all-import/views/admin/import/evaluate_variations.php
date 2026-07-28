<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php if (!empty($variation_list_count)):?>
<div class="updated">
	<?php /* translators: %1$s: number of matched elements, %2$s: element/elements label */ ?>
	<p><?php echo wp_kses( sprintf(__('Current selection matches <span class="matches_count">%1$s</span> %2$s.', 'wp-all-import'), intval($variation_list_count), esc_html(_n('element', 'elements', $variation_list_count, 'wp-all-import'))), array('span' => array('class' => array())) ) ?></p>
	<?php if (PMXI_Plugin::getInstance()->getOption('highlight_limit') and $variation_list_count > PMXI_Plugin::getInstance()->getOption('highlight_limit')): ?>
		<p><?php echo wp_kses( __('<strong>Note</strong>: Highlighting is turned off since can be very slow on large sets of elements.', 'wp-all-import'), array('strong' => array()) ) ?></p>
	<?php endif ?>
</div>
<div id="current_xml">
	<div class="variations_tag">	
		<input type="hidden" name="variations_tagno" value="<?php echo esc_attr($tagno) + 1 ?>" />
		<div class="title">			
			<div class="navigation">
				<?php if ($tagno > 0): ?><a href="#variation_prev" class="previous_element">&nbsp;</a><?php else: ?><span class="previous_element">&nbsp;</span><?php endif ?>
				<?php /* translators: %1$s: current element index, %2$s: total element count */ ?>
				<?php echo wp_kses( sprintf(__('#<strong>%1$s</strong> out of <strong>%2$s</strong>', 'wp-all-import'), intval($tagno) + 1, intval($variation_list_count)), array('strong' => array()) ); ?>
				<?php if ($tagno < $variation_list_count - 1): ?><a href="#variation_next" class="next_element">&nbsp;</a><?php else: ?><span class="next_element">&nbsp;</span><?php endif ?>
			</div>
		</div>
		<div class="clear"></div>
		<div class="xml resetable"> <?php if (!empty($variation_list_count)) PMXI_Render::render_xml_element($variation_elements->item($tagno), true);  ?></div>	
	</div>
</div>
<?php endif; ?>
<script type="text/javascript">
(function($){	
	var paths = <?php echo json_encode($paths) ?>;
	var $xml = $('#variations_xml');	
	
	$xml.html($('#current_xml').html()).css({'visibility':'visible'});
	for (var i = 0; i < paths.length; i++) {
		$xml.find('.xml-element[title="' + paths[i] + '"]').addClass('selected').parents('.xml-element').find('> .xml-content.collapsed').removeClass('collapsed').parent().find('> .xml-expander').html('-');
	}
})(jQuery);
</script>