<?php
/**
 * The template for displaying 404 pages (not found)
 */

get_header(); ?>



<div class="errorWrap">
	<div class="container">    
	<div class="row">
		<div class="col-md-5 col-12 imgBox">
			<div class="imgInnr">
				<img src="<?php echo get_stylesheet_directory_uri();?>/images/404-image.jpg">  
			</div>
		</div>
		<div class="col-md-7 col-12 textBox">
			<h1>UhOh!</h1>       
			<h3>Something's wrong here.</h3>   
			<p>It seems that you were looking for something. We re-designed our website and re-worked its structure, so directories may have changed. Please try again.</p>   
			<p>If you are still having difficulties, feel free to contact us at <a href="tel:8004912068">800-491-2068</a> | <a href="tel:19544850995">1954-485-0995</a> or simply email us at <a href="mailto:8d29401cebd3@yopmail.com">8d29401cebd3@yopmail.com</a></p>          
		</div>
	</div>     
			     
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