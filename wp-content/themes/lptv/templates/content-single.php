<?php
/**
 * The template part for displaying single posts
 */
?>

<article>
	<?php the_title( '<h1 class="postTitle">', '</h1>' ); ?> 
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

	<div class="entry-content">
		<?php
		the_content();
		get_template_part( 'templates/social-share' );
		?>	
	</div><!-- .entry-content -->


</article><!-- #post-## -->
