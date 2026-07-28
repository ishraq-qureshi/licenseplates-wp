jQuery(document).ready(function() {
	$text = wpbulkremove.wpbulkremove_string;
    $addhtml = '<div id="remcat" class="inline-edit-group wp-clearfix"><input type="checkbox" name="remove_cat" value="open"><span class="checkbox-title">'+ $text +'</span></div>';
	jQuery('.inline-edit-col-center.inline-edit-categories .inline-edit-col').append($addhtml);
	
});

jQuery(document).on('change', '#remcat input, #bulk-edit-remcat input', function() {

	$this = jQuery(this);
	
	var $repl = 'post_out_category';
	var $wraper = $this.closest('.inline-edit-col');
	
	jQuery('.cat-checklist', $wraper).each(function (t, ul) {		
			$list = jQuery(ul);
	
        if ($this.is(':checked')) {
            $list.addClass('red');
			jQuery('#remcat').addClass('red');
			$list.find('input').each(function(i, li) {
				jQuery(li).attr('name',$repl);
			});
        } else {
            $list.removeClass('red');
			$ininame = $list.prev('input').attr('name');
			jQuery('#remcat').removeClass('red');
			$list.find('input').each(function(i, li) {
				jQuery(li).attr('name',$ininame);
			});
        }
		
	});
});
jQuery(function($){
	$( 'body' ).on( 'click', 'input[name="bulk_edit"]', function(e) {
		
		
		$( this ).after('<span class="spinner is-active"></span>');
		//$( this ).prop('disabled', true);
 
		var bulk_edit_row = $( 'tr#bulk-edit' );
		remcat = bulk_edit_row.find( 'input[name="remove_cat"]' ).is(':checked') ? 1 : 0;
   
		if(remcat == 1){
			e.preventDefault();
				var post_ids = new Array();
				var catout = new Array();
				$('#bulk-edit .inline-edit-categories .cat-checklist').each(function(i, li) {
					taxname = $(li).prev().attr('name');
							$(li).find('li label input[name="post_out_category"]:checked').each(function(ii, cat) {							   
								var cat_id_add = $(cat).val();
									catout.push({
										'taxonomy': taxname,
										'taxonomy_id': cat_id_add
									});
							});
				});
	 
				bulk_edit_row.find( '#bulk-titles' ).children().each( function() {
					post_ids.push( $( this ).attr( 'id' ).replace( /^(ttle)/i, '' ) );
				});

				bulk_edit_row.find( '#bulk-titles-list .button-link.ntdelbutton' ).each( function() {
					post_ids.push( $( this ).attr( 'id' ).replace( /_/g, '' ) );
				});

				if (post_ids.length > 0) {
						
					$.ajax({
						url: wpbulkremove.ajax_url, 
						type: 'POST',
						async: true,					
						cache: false,
						data: {
							action: 'masterns_bulk_remove_cat',
							post_ids: post_ids, 
							catout: catout,
							security: wpbulkremove.security,
						},
						success:function(data) {
							//return false;
							location.reload();
						},
						error: function(errorThrown){
							console.log(errorThrown);
						}
					});

				} 
		} 
	});
});