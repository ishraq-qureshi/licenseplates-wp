<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="updated found_records">
	<?php if ($is_csv):?>
		<?php /* translators: %1$s: number of matched rows, %2$s: row/rows label */ ?>
		<h3><?php echo wp_kses( sprintf(__('<span class="matches_count">%1$s</span> <strong>%2$s</strong> will be imported', 'wp-all-import'), intval($node_list_count), esc_html(_n('row', 'rows', $node_list_count, 'wp-all-import'))), array('span' => array('class' => array()), 'strong' => array()) ); ?></h3>
	<?php else:?>
		<?php /* translators: %1$s: number of matched elements, %2$s: root element name, %3$s: element/elements label */ ?>
		<h3><?php echo wp_kses( sprintf(__('<span class="matches_count">%1$s</span> <strong>%2$s</strong> %3$s will be imported', 'wp-all-import'), intval($node_list_count), esc_html(PMXI_Plugin::$session->source['root_element']), esc_html(_n('element', 'elements', $node_list_count, 'wp-all-import'))), array('span' => array('class' => array()), 'strong' => array()) ); ?></h3>
	<?php endif; ?>
	<h4><?php esc_html_e('Click an element to select it, or scroll down to add filtering options.', 'wp-all-import'); ?></h4>
	<?php if (PMXI_Plugin::getInstance()->getOption('highlight_limit') and $elements->length > PMXI_Plugin::getInstance()->getOption('highlight_limit')): ?>
		<p><?php echo wp_kses( __('<strong>Note</strong>: Highlighting is turned off since can be very slow on large sets of elements.', 'wp-all-import'), array('strong' => array()) ) ?></p>
	<?php endif ?>
</div>
<div id="current_xml">	
	<?php if ($is_csv): ?>
		<?php PMXI_Render::render_csv_element($elements->item($elements->length > 1 ? $show_element : 0), false, '//'); ?>
	<?php else:?>
		<?php PMXI_Render::render_xml_element($elements->item($elements->length > 1 ? $show_element : 0), false, '//'); ?>
	<?php endif;?>
</div>
<script type="text/javascript">
(function($){	
	var paths = <?php echo json_encode($paths) ?>;
	var $xml = $('.wpallimport-xml');
	$('.wpallimport-console').fadeIn();
	$xml.html($('#current_xml').html()).css({'visibility':'visible'});
	for (var i = 0; i < paths.length; i++) {
		$xml.find('.xml-element[title="' + paths[i] + '"]').addClass('selected').parents('.xml-element').find('> .xml-content.collapsed').removeClass('collapsed').parent().find('> .xml-expander').html('-');
	}	
})(jQuery);
</script>