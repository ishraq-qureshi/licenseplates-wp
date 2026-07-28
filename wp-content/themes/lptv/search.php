<?php
/**
 * The template for displaying search results pages
 */

get_header(); ?>

<?php
// Get the Single Post Banner from the Customizer
$single_post_banner = get_theme_mod( 'lptv_single_post_banner' );

// Display the Single Post Banner with the [page-title] shortcode
if ( $single_post_banner ) {
    echo '<div class="singleBanner innerBnner" style="background-image: url(' . esc_url( $single_post_banner ) . ');">';
        echo '<div class="container"><div class="positionBox">';
            echo '<h1 class="title">' . do_shortcode('[pagetitle]') . '</h1>';
            echo do_shortcode('[post type="content" id="1599"]');
        echo '</div></div>';
    echo '</div>';
} else {
    echo '<div class="singleBanner-default">';
    echo '<div class="container"><div class="positionBox">';
        echo '<h2 class="title">' . do_shortcode('[pagetitle]') . '</h2>';
        echo do_shortcode('[post type="content" id="1599"]');
    echo '</div></div>';
    echo '</div>';
}
?>

<div class="blogWrap search-page">  
	<div class="container">
		<?php if ( have_posts() ) : ?> 
	        <?php        
	        echo "<div class='row row-cols-xl-3 row-cols-lg-3 row-cols-sm-2 row-cols-1 rowInnr'>";
				while ( have_posts() ) : the_post();
					get_template_part( 'templates/content', get_post_format() );
				endwhile; 
	        echo "</div>"; ?>     
			 
	        <div class="pagination">
	            <?php
	                the_posts_pagination( array(
	                    'prev_text'          => __( 'Previous', 'lptv' ),
	                    'next_text'          => __( 'Next', 'lptv' ),
	                    'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'Page', 'lptv' ) . ' </span>',
	                ) );  
	            ?>
	        </div>          
		 <?php endif; ?>
	</div>
</div>

<div class="formWrap receiveWrap">
    <div class="container">
        <div class="bgBox">
            <div class="wp-block-group__inner-container">
                <?php echo do_shortcode('[post type="content" id="1209"]'); ?>
                <div class="row">
                    <div class="col-md-6 col-12 textBox">
                        <?php echo do_shortcode('[post type="content" id="813"]'); ?>
                    </div>
                    <div class="col-md-6 col-12 buttonBox">
                        <?php echo do_shortcode('[contact-form-7 id="9c3fa00" title="Sign-UP to Receive Form"]'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="formWrap questionWrap">
    <div class="container">
        <?php echo do_shortcode('[post type="content" id="825"]'); ?>
        <?php echo do_shortcode('[contact-form-7 id="1c1d715" title="Have Questions? We’re Here To Help Form"]'); ?>
    </div>
</div>  

<?php get_footer(); ?>
