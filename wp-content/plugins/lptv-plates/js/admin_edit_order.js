// update product thumbnail on edit product meta
jQuery(function ($) {

    $('#woocommerce-order-items').on('click', '.edit-order-item', function () {

        let parent = $(this).closest('.item');

        let inp1 = $(parent).find('input[value="_plate_text1"]')[0];
        let inp2 = $(parent).find('input[value="_plate_text2"]')[0];

        let text1 = $(inp1).parent().find('textarea');
        let text2 = $(inp2).parent().find('textarea');

        let img = $(parent).find('.wc-order-item-thumbnail img')[0];

        function updateSrc() {
            console.log('key up');

            // image url
            let plate_text_1 = text1 ? text1.val().trim() : '';
            let plate_text_2 = text2 ? text2.val().trim() : '';

            let src = $(img).attr('src').trim();

            src = src.replace(/(text1=[^&]*)/, 'text1=' + plate_text_1);
            src = src.replace(/(text2=[^&]*)/, '&text2=' + plate_text_2);

            $(img).attr('src', src);

        }

        $(text1).on('keyup', function () {
            updateSrc()
        });

        $(text2).on('keyup', function () {
            updateSrc()
        });
    })
})