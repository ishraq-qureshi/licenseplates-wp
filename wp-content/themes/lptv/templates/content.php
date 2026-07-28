<?php
/**
 * The template part for displaying content
 *
 */
?> 

<div class="postgridWrap">
    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>> 
       <?php 
		if (has_post_thumbnail( $post->ID ) ) {
			$image  = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'full' );
			$fImage = $image[0];
		} else {
			$fImage = '';
		} ?>            
      
        <div class="featuredImg">       
         	<a class="featuredbgBox" href="<?php echo get_permalink(); ?>" style="background-image: url('<?php echo $fImage; ?>'); display: block;"></a> 
         	<?php
               $image_id = get_post_thumbnail_id();
               $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', TRUE);
           ?>      
           <img src="<?php echo $fImage; ?>" alt="<?php echo $image_alt; ?>">     
         </div>   

        <div class="textGrid i-v">  	    	
	    	<div class="flexBox"> 
	    		<?php the_title( sprintf( '<h3 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h3>' ); ?>
		    	<div class="innerBox">
		    		<div class="box authorBox">
			    		<div class="image">	<img src="<?php echo get_stylesheet_directory_uri();?>/images/blog-author-icon.svg" alt="LPTV blog author icon"></div>
			    		<div class="name intText"><?php echo get_the_author(); ?></div> 
			    	</div>
			    	<label class="box line">|</label>
			    	<div class="box">
			    		<div class="image">	<img src="<?php echo get_stylesheet_directory_uri();?>/images/blog-date-icon.svg" alt="LPTV blog date icon"></div>
		        		<div class="date intText"><?php the_time( get_option( 'date_format' ) ); ?></div> 
		        	</div>
	        	</div> 
		        <div class="excerptBox">
		            <p><?php echo excerpt(40); ?></p>
		        </div>  
	    	</div>
	        <div class="button3">
	        	<a href="<?php echo get_permalink(); ?>" class="btnbx">Read more</a>
	        </div>
	        
		</div>	
    </article><!-- #post-## -->
</div> 
