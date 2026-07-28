(function ($) {
	$( window ).on( "load",function () {
		$('.knob').knob();
		var data = $('.knob').attr('rel');

		$({value: 0}).animate({value: data}, {
			duration: 3000,
			easing  : 'swing',
			step    : function () {
				$('.knob').val(Math.ceil(this.value)).trigger('change');
			}
		});

		$('.woocommerce-af-risk-failure-list ul').hide();

		$('.woocommerce-af-risk-failure-list-toggle').click(function(){
			$('.woocommerce-af-risk-failure-list ul').slideToggle();
			var text = $(this).text();
			$(this).text( $(this).data('toggle') );
			$(this).data('toggle', text);
		});

		$('.woocommerce-af-risk-maxmind-list ul').hide();

		$('.woocommerce-af-risk-maxmind-list-toggle').click(function(){
			$('.woocommerce-af-risk-maxmind-list ul').slideToggle();
			var text = $(this).text();
			$(this).text( $(this).data('toggle') );
			$(this).data('toggle', text);
		});

		$('.woocommerce-af-risk-ai-fraud-list ul').hide();

		$('.woocommerce-af-risk-ai-fraud-list-toggle').click(function(){
			$('.woocommerce-af-risk-ai-fraud-list ul').slideToggle();
			var text = $(this).text();
			$(this).text( $(this).data('toggle') );
			$(this).data('toggle', text);
		});

		// IP Multiple Order Details toggle
		$('.wc-af-ip-multiple-content').hide();

		$('.wc-af-ip-multiple-toggle').click(function(e){
			e.preventDefault();
			$('.wc-af-ip-multiple-content').slideToggle();
			var text = $(this).text();
			$(this).text( $(this).data('toggle') );
			$(this).data('toggle', text);
		});
		
		$('.unblock-email').click(function(){
			let email = $(this).data('email'),
				wpnonce = $(this).data('wpnonce'),
				el_error_msg = $(this).parent().parent().find('.anti-fraud-error-msg');

			$.ajax({
				method : 'POST',
				url : ajaxurl,
				data : { action : 'whitelist_email', email : email, _wpnonce: wpnonce },
				success : function(result) {
					if( result.success ) {
						el_error_msg.css('color', '#009688').html(result.data);
						$(this).parent().remove();
						setTimeout(function() {
							window.location.reload();
						}, 1500 );
					}else {
						el_error_msg.html(result.data);
					}
				}				
			})
		});
		
		// Blacklist Email button handler
		$('.wc-af-blacklist-email-btn').click(function(){
			var $btn = $(this),
				order_id = $btn.data('order-id'),
				wpnonce = $btn.data('wpnonce'),
				$message = $('.wc-af-blacklist-message');

			// Disable button during request
			$btn.prop('disabled', true);
			$message.html('');

			$.ajax({
				method : 'POST',
				url : ajaxurl,
				data : { 
					action : 'wc_af_blacklist_email', 
					order_id : order_id, 
					_wpnonce: wpnonce 
				},
				success : function(result) {
					if( result.success ) {
						var messageClass = result.data.already_exists ? 'notice-info' : 'notice-success';
						$message.html('<div class="notice ' + messageClass + ' inline" style="margin: 0; padding: 8px 12px;"><p>' + result.data.message + '</p></div>');
						
						// Reload after short delay if it was newly added
						if (!result.data.already_exists) {
							setTimeout(function() {
								window.location.reload();
							}, 1500);
						} else {
							$btn.prop('disabled', false);
						}
					} else {
						$message.html('<div class="notice notice-error inline" style="margin: 0; padding: 8px 12px;"><p>' + result.data + '</p></div>');
						$btn.prop('disabled', false);
					}
				},
				error : function() {
					$message.html('<div class="notice notice-error inline" style="margin: 0; padding: 8px 12px;"><p>' + 'An error occurred. Please try again.' + '</p></div>');
					$btn.prop('disabled', false);
				}
			});
		});

		// Blacklist IP button handler
		$('.wc-af-blacklist-ip-btn').click(function(){
			var $btn = $(this),
				order_id = $btn.data('order-id'),
				wpnonce = $btn.data('wpnonce'),
				$message = $('.wc-af-blacklist-message');

			// Disable button during request
			$btn.prop('disabled', true);
			$message.html('');

			$.ajax({
				method : 'POST',
				url : ajaxurl,
				data : { 
					action : 'wc_af_blacklist_ip', 
					order_id : order_id, 
					_wpnonce: wpnonce 
				},
				success : function(result) {
					if( result.success ) {
						var messageClass = result.data.already_exists ? 'notice-info' : 'notice-success';
						$message.html('<div class="notice ' + messageClass + ' inline" style="margin: 0; padding: 8px 12px;"><p>' + result.data.message + '</p></div>');
						
						// Reload after short delay if it was newly added
						if (!result.data.already_exists) {
							setTimeout(function() {
								window.location.reload();
							}, 1500);
						} else {
							$btn.prop('disabled', false);
						}
					} else {
						$message.html('<div class="notice notice-error inline" style="margin: 0; padding: 8px 12px;"><p>' + result.data + '</p></div>');
						$btn.prop('disabled', false);
					}
				},
				error : function() {
					$message.html('<div class="notice notice-error inline" style="margin: 0; padding: 8px 12px;"><p>' + 'An error occurred. Please try again.' + '</p></div>');
					$btn.prop('disabled', false);
				}
			});
		});
		
	});
	
	
	
})(jQuery);