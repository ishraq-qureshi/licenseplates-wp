<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div id="post-preview" class="wpallimport-preview_prices">

	<div class="title">
		<div class="navigation">			
			<?php if ($tagno > 1): ?><a href="#prev" class="previous_element">&nbsp;</a><?php else: ?><span class="previous_element">&nbsp;</span><?php endif ?>
			<?php /* translators: %1$s: current tag number, %2$s: total count */ ?>
			<?php echo wp_kses( sprintf(__('<strong><input type="text" value="%1$s" name="tagno" class="tagno"/></strong><span class="out_of"> of <strong class="pmxi_count">%2$s</strong></span>', 'wp-all-import'), intval($tagno), intval(PMXI_Plugin::$session->count)), array('strong' => array('class' => array()), 'span' => array('class' => array()), 'input' => array('type' => array(), 'value' => array(), 'name' => array(), 'class' => array())) ); ?>
			<?php if ($tagno < PMXI_Plugin::$session->count): ?><a href="#next" class="next_element">&nbsp;</a><?php else: ?><span class="next_element">&nbsp;</span><?php endif ?>			
		</div>
	</div>
	
	<div class="wpallimport-preview-content">
		<?php if ($this->errors->get_error_codes()): ?>
			<?php $this->error() ?>
		<?php endif ?>

		<h3><?php esc_html_e('Preview Prices', 'wp-all-import'); ?></h3>	
		
		<p><?php esc_html_e('Regular Price', 'wp-all-import'); ?>: <?php echo empty($product_regular_price) ? '' : wp_kses_post( wp_all_import_filter_html_kses($product_regular_price) ); ?></p>
		<p><?php esc_html_e('Sale Price', 'wp-all-import'); ?>: <?php echo empty($product_sale_price) ? '' : wp_kses_post( wp_all_import_filter_html_kses($product_sale_price) ); ?></p>

	</div>

</div>