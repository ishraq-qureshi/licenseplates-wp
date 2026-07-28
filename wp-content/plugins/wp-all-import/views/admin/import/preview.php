<?php if ( ! defined( 'ABSPATH' ) ) exit; // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals ?>
<div id="post-preview" class="wpallimport-preview">

	<div class="title">		
		<div class="navigation">			
			<?php if ($tagno > 1): ?><a href="#prev" class="previous_element">&nbsp;</a><?php else: ?><span class="previous_element">&nbsp;</span><?php endif ?>
			<?php echo wp_kses( sprintf(
				/* translators: 1: current tag index, 2: total tag count */
				__('<strong><input type="text" value="%1$s" name="tagno" class="tagno"/></strong><span class="out_of"> of <strong class="pmxi_count">%2$s</strong></span>', 'wp-all-import'),
				intval($tagno),
				intval(PMXI_Plugin::$session->count)
			), array(
				'strong' => array('class' => array()),
				'span'   => array('class' => array()),
				'input'  => array('type' => array(), 'value' => array(), 'name' => array(), 'class' => array()),
			) ); ?>
			<?php if ($tagno < PMXI_Plugin::$session->count): ?><a href="#next" class="next_element">&nbsp;</a><?php else: ?><span class="next_element">&nbsp;</span><?php endif ?>			
		</div>
	</div>
	
	<div class="wpallimport-preview-content">
		
		<?php if ($this->errors->get_error_codes()): ?>
			<?php $this->error() ?>
		<?php endif ?>
			
		<?php if (isset($title)): ?>
			<h2 class="title"><?php echo wp_kses_post( wp_all_import_filter_html_kses($title) ); ?></h2>
		<?php endif ?>
		<?php if (isset($content)): ?>
			<?php echo wp_kses_post( apply_filters('the_content', wp_all_import_filter_html_kses($content)) ); ?>
		<?php endif ?>

	</div>

</div>