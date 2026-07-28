<?php
/**
 * The template used for shortcode layout *
 */
?> 

<button class="navbar-toggler">
    <span class="navbar-toggler-icon"></span>
    <span class="navbar-toggler-icon"></span>
    <span class="navbar-toggler-icon"></span>
</button>

<div class="navBox mainMenu2"> 
    <div class="innerBox">
    <div class="nav-bg">
        <div class="page-bg"></div>
        <div class="animation-wrapper">
          <div class="particle particle-1"></div>
          <div class="particle particle-2"></div>
          <div class="particle particle-3"></div>
          <div class="particle particle-4"></div>
        </div>
    </div>
    <div class="row">        
        <div class="inner">
            <div class="col-md-12 col-12 logo"><?php echo do_shortcode('[logo]'); ?></div>
            <div class="col-md-12 col-12">
                <div class="menu-box firstBox lineBox">
                    <?php                    
                    $defaults = array(
                        'theme_location'  => 'menu-3', 
                        'menu'            => '',      
                        'container'       => 'div',
                        'container_class' => 'boxInner',
                        'container_id'    => 'topmenu',
                        'menu_class'      => 'nav-menu',
                        'menu_id'         => '',
                        'echo'            => true,
                        'fallback_cb'     => 'wp_page_menu',
                        'before'          => '',
                        'after'           => '',
                        'link_before'     => '',
                        'link_after'      => '',
                        'items_wrap'      => '<ul id="menu" class="%2$s">%3$s</ul>',
                        'depth'           => 0, 
                        'walker'          => ''
                    );                 
                    wp_nav_menu( $defaults ); ?>
                </div>            
                
            </div>
        </div> 
    </div>
  </div>    
</div>
                    