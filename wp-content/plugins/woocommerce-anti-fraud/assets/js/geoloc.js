jQuery(document).ready(function(){
    jQuery(document).on( "updated_checkout", function(){
        if(bigdatacloud_key.key !== ''){
        if(navigator.geolocation){

            navigator.geolocation.getCurrentPosition(function(position) {
            
                var latitude = position.coords.latitude;
                var longitude = position.coords.longitude;
            
                jQuery.ajax({
                    url: myAjax.ajaxurl,
                    type : "POST",
                    data: {
                        'action':'my_action_geo_country',
                        'latitude':latitude,
                        'longitude':longitude,
                        '_wpnonce': myAjax.nonce,
                    },
                    success:function(response) {
                        console.log(response);
                    }
                    
                }); 
                
            }, function(error) {
                if (error.code == error.PERMISSION_DENIED)
                    jQuery.ajax({
                        url: myAjax.ajaxurl,
                        type : "POST",
                        data: {
                            'action':'my_action_geo_country',
                            'latitude':'',
                            'longitude':'',
                            '_wpnonce': myAjax.nonce,
                        },
                        success:function(response) {
                            console.log(response);
                        }
                    
                }); 
            });
        } 
        }     
    }); 
});
