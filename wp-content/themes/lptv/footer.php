</main>    
    <footer>
        <?php        
        $args = array(
            'post_type'    => 'section',
            'posts_status' => 'publish',
            'name' => 'footer',
        );
        $footer_query = new wp_query($args);

        if( $footer_query->have_posts() ) {
            while ($footer_query->have_posts()) {
                $footer_query->the_post(); 
                the_content();   
            }
            wp_reset_postdata();
        } ?> 
    </footer> 
</div><!-- wrapperOuter -->

<script type="text/javascript" src="<?php bloginfo('template_url'); ?>/js/plugins.js?v=11062025"></script>
<script type="text/javascript" src="<?php bloginfo('template_url'); ?>/js/scripts.js?v=a08052025"></script>

<?php wp_footer(); ?>
    
<script>(function(){ var s = document.createElement('script'), e = ! document.body ? document.querySelector('head') : document.body; s.src = 'https://acsbapp.com/apps/app/dist/js/app.js'; s.async = true; s.onload = function(){ acsbJS.init({ statementLink : '', footerHtml : '', hideMobile : false, hideTrigger : false, language : 'en', position : 'right', leadColor : '#146FF8', triggerColor : '#146FF8', triggerRadius : '50%', triggerPositionX : 'right', triggerPositionY : 'bottom', triggerIcon : 'people', triggerSize : 'medium', triggerOffsetX : 20, triggerOffsetY : 20, mobile : { triggerSize : 'small', triggerPositionX : 'right', triggerPositionY : 'bottom', triggerOffsetX : 10, triggerOffsetY : 10, triggerRadius : '50%' } }); }; e.appendChild(s);}());</script>

<script type="text/javascript">
    jQuery(document).ready(function($) {
    // Check if the WooCommerce cart cookie exists and if the mini-cart says "Empty"
    if (document.cookie.indexOf('woocommerce_items_in_cart') !== -1) {
        if ($('.woocommerce-mini-cart__empty-message').length > 0) {
            // Force WooCommerce to refresh fragments
            $(document.body).trigger('wc_fragment_refresh');
            console.log('Cart fragments refresh triggered via cookie detection.');
        }
    }
});
</script>

</body>
</html>
