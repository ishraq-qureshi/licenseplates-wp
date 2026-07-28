jQuery(document).ready(function($) {       
 
    // needed for new theme
    // header search 
    $('header .search_icon').on( "click", function() {
        $('body').toggleClass('search-active');
        setTimeout(() => {
            const input = document.getElementById('wp-block-search__input-1');
            input && input.focus();
        }, 100);
    });
	
    $(document).on("keydown", function(event) {
        if (event.key === "Escape") {
            $('body').removeClass('menu-active search-active'); 
        }
    });

    $(".single-product .title, .productWrap .productTitle, .shop-page-banner .title").each(function () {
        const $this = $(this);
        const text = $this.text().trim();
        const words = text.split(" ");
		
        if (words.length > 1) {
            const lastWord = words.pop(); // Remove the last word
            $this.html(words.join(" ") + ` <label class="last-word">${lastWord}</label>`);
        }
    });
     
    $( ".tax-product_cat li.product-category img" ).each(function() {
        $(this).wrap("<div class='box'></div>");
    });

    $('.acdn-title').append('<label></label>');

    $(window).scroll(function() {
        var theta = $(window).scrollTop() / 600 % Math.PI;
        $('.use_img').css({ transform: 'rotate(' + theta + 'rad)' });
    });
    
	$(".info1 ~ select").wrap( "<div class='plateFont'></div>");   

	$('.aeudqbrw').filter(function() {
		return $(this).text().trim() === '';
	}).text('.').addClass('dot');
  
    $('.characterBox button').each(function() { 
        if ($(this).html().trim() === '') {
          $(this).html('&nbsp;');
        }
    });

    //$("header .header:nth-child(1) .navBox.mainMenu ul.sub-menu li.cat-canadian-license-plates").insertAfter(".navBox.mainMenu ul.sub-menu li.cat-european-plates");
    //$("header .header:nth-child(1) .navBox.mainMenu ul.sub-menu li.cat-canadian-license-plates").insertAfter(".navBox.mainMenu ul.sub-menu li.cat-european-plates");

    // not good approach to resort a menu >>>>>

    // $("header .header:nth-child(1) .navBox.mainMenu ul.sub-menu li.cat-clearance").insertAfter(".navBox.mainMenu ul.sub-menu li.cat-european-plates");
    // $("header .header:nth-child(1) .navBox.mainMenu ul.sub-menu li.cat-auto-brand-plates").insertAfter(".navBox.mainMenu ul.sub-menu li.cat-european-plates");
    // $("header .header:nth-child(1) .navBox.mainMenu ul.sub-menu li.cat-flag-plates-oval-id").insertAfter(".navBox.mainMenu ul.sub-menu li.cat-european-plates");
    // $("header .header:nth-child(1) .navBox.mainMenu ul.sub-menu li.cat-religious-plates").insertAfter(".navBox.mainMenu ul.sub-menu li.cat-european-plates");
    // $("header .header:nth-child(1) .navBox.mainMenu ul.sub-menu li.cat-sport-hobby-plates").insertAfter(".navBox.mainMenu ul.sub-menu li.cat-european-plates");
    // $("header .header:nth-child(1) .navBox.mainMenu ul.sub-menu li.cat-promotional-plates").insertAfter(".navBox.mainMenu ul.sub-menu li.cat-european-plates");
    // $("header .header:nth-child(1) .navBox.mainMenu ul.sub-menu li.cat-nautical-plates").insertAfter(".navBox.mainMenu ul.sub-menu li.cat-european-plates");
    // $("header .header:nth-child(1) .navBox.mainMenu ul.sub-menu li.cat-custom-fun-plates").insertAfter(".navBox.mainMenu ul.sub-menu li.cat-european-plates");
    // $("header .header:nth-child(1) .navBox.mainMenu ul.sub-menu li.cat-canadian-license-plates").insertAfter(".navBox.mainMenu ul.sub-menu li.cat-european-plates");
    // $("header .header:nth-child(1) .navBox.mainMenu ul.sub-menu li.cat-military-plates").insertAfter(".navBox.mainMenu ul.sub-menu li.cat-european-plates");
    // $("header .header:nth-child(1) .navBox.mainMenu ul.sub-menu li.cat-gcc-plates").insertAfter(".navBox.mainMenu ul.sub-menu li.cat-european-plates"); 
    // $("header .header:nth-child(1) .navBox.mainMenu ul.sub-menu li.cat-motorcycle-plates").insertAfter(".navBox.mainMenu ul.sub-menu li.cat-european-plates"); 
    // $("header .header:nth-child(1) .navBox.mainMenu ul.sub-menu li.cat-international-plates").insertAfter(".navBox.mainMenu ul.sub-menu li.cat-european-plates"); 
    // $("header .header:nth-child(1) .navBox.mainMenu ul.sub-menu li.cat-usa-state-plates").insertAfter(".navBox.mainMenu ul.sub-menu li.cat-european-plates");  
	
	
	//  $("header .header:nth-child(1) .navBox.mainMenu2 ul.sub-menu li.cat-clearance").insertAfter(".navBox.mainMenu2 ul.sub-menu li.cat-european-plates");
    // $("header .header:nth-child(1) .navBox.mainMenu2 ul.sub-menu li.cat-auto-brand-plates").insertAfter(".navBox.mainMenu2 ul.sub-menu li.cat-european-plates");
    // $("header .header:nth-child(1) .navBox.mainMenu2 ul.sub-menu li.cat-flag-plates-oval-id").insertAfter(".navBox.mainMenu2 ul.sub-menu li.cat-european-plates");
    // $("header .header:nth-child(1) .navBox.mainMenu2 ul.sub-menu li.cat-religious-plates").insertAfter(".navBox.mainMenu2 ul.sub-menu li.cat-european-plates");
    // $("header .header:nth-child(1) .navBox.mainMenu2 ul.sub-menu li.cat-sport-hobby-plates").insertAfter(".navBox.mainMenu2 ul.sub-menu li.cat-european-plates");
    // $("header .header:nth-child(1) .navBox.mainMenu2 ul.sub-menu li.cat-promotional-plates").insertAfter(".navBox.mainMenu2 ul.sub-menu li.cat-european-plates");
    // $("header .header:nth-child(1) .navBox.mainMenu2 ul.sub-menu li.cat-nautical-plates").insertAfter(".navBox.mainMenu2 ul.sub-menu li.cat-european-plates");
    // $("header .header:nth-child(1) .navBox.mainMenu2 ul.sub-menu li.cat-custom-fun-plates").insertAfter(".navBox.mainMenu2 ul.sub-menu li.cat-european-plates");
    // $("header .header:nth-child(1) .navBox.mainMenu2 ul.sub-menu li.cat-canadian-license-plates").insertAfter(".navBox.mainMenu2 ul.sub-menu li.cat-european-plates");
    // $("header .header:nth-child(1) .navBox.mainMenu2 ul.sub-menu li.cat-military-plates").insertAfter(".navBox.mainMenu2 ul.sub-menu li.cat-european-plates");
    // $("header .header:nth-child(1) .navBox.mainMenu2 ul.sub-menu li.cat-gcc-plates").insertAfter(".navBox.mainMenu2 ul.sub-menu li.cat-european-plates"); 
    // $("header .header:nth-child(1) .navBox.mainMenu2 ul.sub-menu li.cat-motorcycle-plates").insertAfter(".navBox.mainMenu2 ul.sub-menu li.cat-european-plates"); 
    // $("header .header:nth-child(1) .navBox.mainMenu2 ul.sub-menu li.cat-international-plates").insertAfter(".navBox.mainMenu2 ul.sub-menu li.cat-european-plates"); 
    // $("header .header:nth-child(1) .navBox.mainMenu2 ul.sub-menu li.cat-usa-state-plates").insertAfter(".navBox.mainMenu2 ul.sub-menu li.cat-european-plates"); 
	
	//$('#menu-menu').clone().appendTo('.menu-box');
	
            
});

jQuery(function ($) {

    function initQtyButtons() {

        $('.quantity').each(function () {

            var $this = $(this);

            // prevent duplicate buttons
            if ($this.find('.plus').length) return;

            var $input = $this.find('input.qty'),
                max = parseFloat($input.attr('max')) || Infinity,
                min = parseFloat($input.attr('min')) || 0,
                step = parseFloat($input.attr('step')) || 1;

            $('<button type="button" class="minus">-</button>').insertBefore($input);
            $('<button type="button" class="plus">+</button>').insertAfter($input);

            $this.on('click', '.minus', function () {
                var value = parseFloat($input.val()) - step;
                value = value < min ? min : value;
                $input.val(value).change();
            });

            $this.on('click', '.plus', function () {
                var value = parseFloat($input.val()) + step;
                value = value > max ? max : value;
                $input.val(value).change();
            });

        });
    }

    // run on page load
    initQtyButtons();

    // run after WooCommerce updates cart (THIS FIXES YOUR ISSUE)
    $(document.body).on('updated_wc_div', function () {
        initQtyButtons();
    });

});


document.querySelectorAll('.woocommerce-product-excerpt p').forEach(p => {
    if (p.innerHTML.trim() === '&nbsp;' || p.innerHTML.trim() === '') {
        p.style.display = 'none';
    }
});
