<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>

<head profile="http://gmpg.org/xfn/11">

<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
<title><?php wp_title('&laquo;', true, 'right'); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">  



<!-- Bootstrap core CSS -->
<link rel="preload" href="<?php bloginfo('template_url'); ?>/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript>
    <link rel="stylesheet" href="<?php bloginfo('template_url'); ?>/css/bootstrap.min.css">
</noscript>

<!-- Custom styles for fonts -->
<link rel="preload" href="<?php bloginfo('template_url'); ?>/fonts/fonts.css?v=11062025" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript>
    <link rel="stylesheet" href="<?php bloginfo('template_url'); ?>/fonts/fonts.css?v=11062025">
</noscript>

<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />

<script type="text/javascript" src="<?php bloginfo('template_url'); ?>/js/jquery-3.5.1.min.js"></script> 

<?php if ( is_singular() ) wp_enqueue_script( 'comment-reply' ); ?>
	
<script async type="text/javascript">
	
function addalljs(){  
    var tag = document.createElement("script");
    tag.src = "https://www.googletagmanager.com/gtag/js?id=G-T3P91847NY";
    document.getElementsByTagName("head")[0].appendChild(tag);
    const myTimeout1    = setTimeout(callGTag,   500);     // delay long enough for the GTM JS to be loaded and parsed.
    
    var rvw = document.createElement("script");
	rvw.src = "https://widget.reviewability.com/js/popupWidget.min.js";
	rvw.async = true;
	rvw.setAttribute('id','popup-rating-widget-script');
	rvw.setAttribute('data-gfspw','https://app.gatherup.com/popup-pixel/get/403ad6a469904d03b393f4740ebbec37caf27f9c');
	document.getElementsByTagName("head")[0].appendChild(rvw);
}

function callGTag (){
    var func = document.createElement("script");
    var funcText = document.createTextNode("window.dataLayer = window.dataLayer || []; function gtag() { dataLayer.push(arguments); }");
    func.appendChild(funcText);
    document.getElementsByTagName("head")[0].appendChild(func);
    gtag('js', new Date());
    gtag('config', 'G-T3P91847NY');
}
	
function callFbPxl (){
    var temp = document.createElement("script");
    var tempInner = document.createTextNode("!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}");
    temp.appendChild(tempInner);
    document.getElementsByTagName("head")[0].appendChild(temp);
    fbq('init', '319411928670292');
    fbq('track', 'PageView');
}



<!-- Moved this call to the load event, so it will not be called until after Document Complete --> 
<!-- addalljs(); -->
window.addEventListener("load", addalljs); 	
</script>
<script>
  window.addEventListener("load", function() {
      var fa = document.createElement('link');
      fa.href = '/wp-content/themes/lptv/fonts/fa-regular-400.woff2';
      fa.rel = 'stylesheet';
      fa.media = 'print';
      fa.onload = function(){ this.media='all'; };
      document.head.appendChild(fa);
  });
</script>

<?php wp_head(); ?>
	
</head>

<body <?php body_class(); ?>>

<div class="wrapperOuter withoutScrl">

    <?php        

    // set correct id for topbar and header
    $topbar_id = 585;
    $header_id = 541;

    $sections = get_posts([
        'post_type'      => 'section',
        'post_status'    => 'publish',
        'post__in'       => [$topbar_id, $header_id],
        'posts_per_page' => 2,
        'orderby'       => 'post__in'
    ]);


    $start = time();
    $section_content = [];

    foreach ($sections as $section) {
        $section_content[$section->ID] = apply_filters('the_content', $section->post_content);
    }

    // print topbar
    if (!empty($section_content[$topbar_id])) {
        echo $section_content[$topbar_id];
    }

    ?> 
    <header> 
        <?php        
       
        // print header
        if (!empty($section_content[$header_id])) {
            echo $section_content[$header_id];
        }

        ?>
    </header>

    <main>
 











    