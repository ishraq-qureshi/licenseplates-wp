<?php
/**
 * The template for displaying all single posts and attachments
 */

get_header(); 
?>


<?php
// Get the Single Post Banner from the Customizer
$single_post_banner = get_theme_mod( 'lptv_single_post_banner' );

// Display the Single Post Banner with the [page-title] shortcode
if ( $single_post_banner ) {
    echo '<div class="singleBanner innerBnner" style="background-image: url(' . esc_url( $single_post_banner ) . ');">';
        echo '<div class="container"><div class="positionBox">';
            echo '<h2 class="title">' . do_shortcode('[pagetitle]') . '</h2>';
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
 
<div class="postWrap" id="post-<?php the_ID(); ?>">
    <div <?php post_class(); ?>>
        <div class="container">
            <div class="featuredImg">                 
                <?php
                if (has_post_thumbnail()) {
                    // Get the post thumbnail ID
                    $thumbnail_id = get_post_thumbnail_id();

                    // Get the featured image URL
                    $fImage = wp_get_attachment_url($thumbnail_id);

                    // Get the alt text
                    $alt_text = get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true);
                    ?>
                    <!-- Background Box -->
                    <div class="featuredbgBox i-v" style="background-image: url('<?php echo esc_url($fImage); ?>'); display: block;"></div>
                    
                    <!-- Featured Image with Alt -->
                    <?php
                    echo wp_get_attachment_image($thumbnail_id, 'full', false, array('alt' => esc_attr($alt_text)));
                } 
                ?>
            </div>        
            
            <div class="contentGrid">       
                <?php
                    while ( have_posts() ) : the_post();
                        get_template_part( 'templates/content', 'single' );
                        
                        if ( comments_open() || get_comments_number() ) {
                            comments_template();
                        }
                    endwhile;
                    ?> 
            </div>
        </div>
    </div>
</div>


<div class="pltmeisterWrap">
    <div class="container">
        <div class="bgimageBox">
            <div class="row">
                <!-- Image Box -->
                <div class="col-md-3 col-12 imageBox">
                    <div class="inrImage">
                        <?php
                        $profile_image = get_field('profile_image', 'user_' . $user_id);
                        if ($profile_image) {
                            echo '<img src="' . esc_url($profile_image) . '" />';
                        };
                         ?>
                    </div>
                </div>

                <!-- Text Box -->
                <div class="col-md-9 col-12 textBox">
                    <!-- Author Name -->
                    <div class="name">
                        <?php echo esc_html( get_the_author() ); ?>
                    </div> 

                    <!-- Author Description -->
                    
                        <?php
                        $author_id = get_the_author_meta( 'ID' );
                        $author_bio = get_the_author_meta( 'description', $author_id );

                        if ( ! empty( $author_bio ) ) {
                            echo '<div class="description">';
                            echo esc_html( $author_bio );
                            echo '</div>';
                        } 
                        ?>
                </div> 
            </div>
        </div>
    </div>
</div>


<div class="relatedGrid blogWrap"> <!-- blogWrap class is here to get the style of blog grid -->
    <div class="container">
        <h2 class="scnTitle">You Might Also Like</h2>
        <div class="row row-cols-md-3 row-cols-1 rowInnr">  
            <?php        
               $args = array(
                   'post_type'      => 'post',
                   'posts_status'   => 'publish',
                   'orderby'        => 'date',
                   'order'          => 'desc',
                   'showposts'      => 3,  
                   'post__not_in' => array( $post->ID ), //except the current post 
                    
               );
               $rp_query = new wp_query($args);

               if( $rp_query->have_posts() ) {
                   $count_post = $rp_query->post_count;   

                   while ($rp_query->have_posts()) {
                       $rp_query->the_post();
                       echo '<div class="postgridWrap">'; 
                          get_template_part( 'templates/related-content', get_post_format() );
                       echo '</div>';
                   }
                   wp_reset_postdata();
            }?>
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


<?php lptv_set_post_views(get_the_ID()); /* this is for most viewed counting */ ?>
<?php get_footer(); ?>