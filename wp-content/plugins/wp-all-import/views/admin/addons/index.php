<?php if ( ! defined( 'ABSPATH' ) ) exit; // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals ?>
<div class="wrap" style="max-width:970px;">

	<h2><?php esc_html_e('WP All Import Add-ons', 'wp-all-import') ?></h2>
		
	<div id="pmxi-add-ons" class="clear">
		
		<div class="pmxi-add-on-group clear">
			<h3><?php esc_html_e('Premium Add-ons', 'wp-all-import'); ?></h3>
			<?php foreach( $premium as $addon ): ?>
			<div class="pmxi-add-on wp-box <?php if( $addon['active'] ): ?>pmxi-add-on-active<?php endif; ?>">
				<a target="_blank" href="<?php echo esc_url($addon['url']); ?>">
					<img src="<?php echo esc_url($addon['thumbnail']); ?>" />
				</a>
				<div class="inner">
					<h3><a target="_blank" href="<?php echo esc_url($addon['url']); ?>"><?php echo esc_html($addon['title']); ?></a></h3>
					<p><?php echo esc_html($addon['description']); ?></p>
				</div>
				<div class="footer">
					<?php if ( $addon['active'] ): ?>
						<a class="button button-disabled"><span class="pmxi-sprite-tick"></span><?php esc_html_e("Installed", 'wp-all-import'); ?></a>
					<?php elseif ( $addon['free_installed'] ): ?>
						<a class="button button-disabled"><span class="pmxi-sprite-tick"></span><?php esc_html_e("Free Version Installed", 'wp-all-import'); ?></a>
					<?php elseif ($addon['required_plugins']): ?>
						<?php 
						$all_required_plugins_installed = true;
						foreach ($addon['required_plugins'] as $name => $active): 
							if (!$active){
								?>
								<p style="margin:3px 0px;"><?php echo esc_html($name) . esc_html__(' required', 'wp-all-import'); ?></p>
								<?php
								$all_required_plugins_installed = false;
							}							
						endforeach; 
						if ($all_required_plugins_installed){
							?>
							<a target="_blank" href="<?php echo esc_url($addon['url']); ?>" class="button"><?php esc_html_e("Download", 'wp-all-import'); ?></a>
							<?php
						}
						?>
					<?php else: ?>					
						<a target="_blank" href="<?php echo esc_url($addon['url']); ?>" class="button"><?php esc_html_e("Purchase & Install", 'wp-all-import'); ?></a>
					<?php endif; ?>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
		
		<div class="pmxi-add-on-group clear">
			<h3><?php esc_html_e('Free Add-ons', 'wp-all-import'); ?></h3>
			<?php foreach( $free as $addon ): ?>
			<div class="pmxi-add-on wp-box <?php if( $addon['active'] ): ?>pmxi-add-on-active<?php endif; ?>">
				<a target="_blank" href="<?php echo esc_url($addon['url']); ?>">
					<img src="<?php echo esc_url($addon['thumbnail']); ?>" />
				</a>
				<div class="inner">
					<h3><a target="_blank" href="<?php echo esc_url($addon['url']); ?>"><?php echo esc_html($addon['title']); ?></a></h3>
					<p><?php echo esc_html($addon['description']); ?></p>
				</div>
				<div class="footer">
					<?php if( $addon['active'] ): ?>
						<a class="button button-disabled"><span class="pmxi-sprite-tick"></span><?php esc_html_e("Installed", 'wp-all-import'); ?></a>
					<?php elseif ($addon['paid_installed']): ?>
						<a class="button button-disabled"><span class="pmxi-sprite-tick"></span><?php esc_html_e("Paid Version Installed", 'wp-all-import'); ?></a>
					<?php elseif ($addon['required_plugins']): ?>
						<?php 
						$all_required_plugins_installed = true;
						foreach ($addon['required_plugins'] as $name => $active): 
							if (!$active){
								?>
								<p style="margin:3px 0px;"><?php echo esc_html($name) . esc_html__(' required', 'wp-all-import'); ?></p>
								<?php
								$all_required_plugins_installed = false;
							}							
						endforeach; 
						if ($all_required_plugins_installed){
							?>
							<a target="_blank" href="<?php echo esc_url($addon['url']); ?>" class="button"><?php esc_html_e("Download", 'wp-all-import'); ?></a>
							<?php
						}
						?>
					<?php else: ?>
						<a target="_blank" href="<?php echo esc_url($addon['url']); ?>" class="button"><?php esc_html_e("Download", 'wp-all-import'); ?></a>
					<?php endif; ?>
				</div>
			</div>
			<?php endforeach; ?>	
		</div>
		
				
	</div>
	
</div>
<script type="text/javascript">
(function($) {
	
	$(window).load(function(){
		
		$('#pmxi-add-ons .pmxi-add-on-group').each(function(){
		
			var $el = $(this),
				h = 0;
			
			
			$el.find('.pmxi-add-on').each(function(){
				
				h = Math.max( $(this).height(), h );
				
			});
			
			$el.find('.pmxi-add-on').height( h );
			
		});
		
	});

})(jQuery);
</script>

<div class="wpallimport-display-columns wpallimport-margin-top-forty">
	<?php echo wp_kses_post( apply_filters('wpallimport_footer', '') ); ?>
</div>