<?php
/*
 * The template for displaying archive pages
 */

get_header(); ?>

<?php
// Get the Blog Index Banner from the Customizer
$blog_index_banner = get_theme_mod( 'lptv_blog_index_banner' );
// Display the Blog Index Banner if it's set
if ( $blog_index_banner ) {
    echo '<div class="bannerWrap blogBanner" style="background-image: url(' . esc_url( $blog_index_banner ) . ');">';
        echo '<div class="container"><div class="positionBox">';
            echo '<h1 class="title">' . do_shortcode( '[pagetitle]' ) . '</h1>';?> 
            <p class="trustText">but were afraid to ask…</p>             
        <?php 
        echo do_shortcode('[post type="content" id="1599"]');
        echo '</div></div>';
    echo '</div>';
} else {
    echo '<div class="blogBanner-default bannerWrap blogBanner">';
    echo '<div class="container"><div class="positionBox">';
            echo '<h1 class="title">' . do_shortcode( '[pagetitle]' ) . '</h1>';?> 
            <p class="trustText">but were afraid to ask…</p>
        <?php 
        echo do_shortcode('[post type="content" id="1599"]');
        echo '</div></div>';
    echo '</div>';
}
?>

<div class="blogWrap">
    <div class="container">         
        <div class="row row-cols-xl-3 row-cols-lg-3 row-cols-sm-2 row-cols-1 rowInnr">                     
            <?php if ( have_posts() ) :
                while ( have_posts() ) : the_post();
                    get_template_part( 'templates/content', get_post_format() );
                endwhile;
            endif;
            ?>         
        </div>

        <div class="pagination">
            <?php
            the_posts_pagination( array(
                'prev_text'          => __( 'Previous', 'lptv' ),
                'next_text'          => __( 'Next', 'lptv' ),
                'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'Page', 'lptv' ) . ' </span>',
            ) );  
            ?>
        </div>
    </div>
</div>


<div class="formWrap receiveWrap">
    <div class="container">
        <div class="bgBox">
            <div class="wp-block-group__inner-container">
                <?php echo do_shortcode('[post type="content" id="1209"]'); ?>
                <div class="row">
                    <div class="col-xl-7 col-lg-12 textBox">
                        <?php echo do_shortcode('[post type="content" id="813"]'); ?>
                    </div>
                    <div class="col-xl-5 col-lg-12 buttonBox">
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
