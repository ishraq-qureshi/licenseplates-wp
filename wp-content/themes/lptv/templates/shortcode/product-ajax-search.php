<?php
/**
 * The template used for shortcode layout
 *
 */

if ( class_exists( 'WooCommerce' ) ) {

    $args = array();
    $args['order'] = 'name';
    $args['orderby'] = 'ASC';
    $args['hide_empty'] = true;
    $args['parent'] = 0;

    $product_cats = get_terms('product_cat', $args); ?>

    <div class="core-product">
        <h2 class="scnTitle i-v">Search <strong>Products</strong></h2> 
        <div class="inner">
            <div class="product-content-cats">           
                <h3>Product Categories</h3>      
                <select id="product-cat-select">
                    <option value="all"><?php esc_html_e('All Categories', 'lmr'); ?></option>
                    <?php foreach ($product_cats as $product_cat) {
                      	echo '<option value="'.esc_attr($product_cat->slug).'">'.esc_attr($product_cat->name).'</option>';
                    } ?>
              	</select>
            </div>
            <div class="core-product-box"> 
                <h3>Products</h3>  
                <div class="product-content-items">
                    <input type="text" placeholder="Search Here.." id="productInput">
                	<ul class="product-list"></ul>
                </div>
                <div class="product-search-button">
                    <button class="btn" id="product-search-submit"><i class="fa fa-search"></i></button>
                </div>
            </div>
        </div>
    </div>
        
    <script type="text/javascript">
        jQuery(document).ready(function($) {

            $('#productInput').on('click', function() {
                $(this).parents('.product-content-items').toggleClass('active');
            });

            $('#productInput').keyup(function() {
                var filterInput = $(this).val();
                $('.product-content-items ul.product-list li').each(function(index, value) {       
                    if ($(this).find('a').text().search(new RegExp(filterInput, "i")) < 0) {
                        $(this).hide();
                    } else {
                        $(this).show()
                    }
                });
            });

            $("#product-search-submit").on("click", function() {
                var filterInput = $('#productInput').val();
                if (filterInput != '') {
                    $('.product-content-items ul.product-list li').each(function(index, value) {       
                        if ($(this).find('a').text().search(new RegExp(filterInput, "i")) < 0) {
                            $(this).hide();
                        } else {
                            $(this).show()
                        }
                    });
                } else {
                    return;
                }                 
            });

            var filterData = 'all';

            function callProductAjax(dataFilter) {
                var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";

                $.ajax({
                    method: 'POST',
                    url : ajaxurl,
                    dataType : 'json',
                    data: {
                        action: 'lptv_ajax_product_search',
                        itemcat: dataFilter,
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown) {
                        console.log('AJAX error - ' + errorThrown);
                    },
                    success: function(response) {
                        var $filteredProducts = $.parseHTML(response.products);
                        if ($filteredProducts.length) {
                            $('.product-content-items ul.product-list').empty();
                            $('.product-content-items ul.product-list').append($filteredProducts);
                        }
                    }
                });                 
            }

            callProductAjax(filterData);

            $('#product-cat-select').on('change', function() {          
                filterData = this.value;
                callProductAjax(filterData);
            });

        });        
    </script>

<?php } ?>