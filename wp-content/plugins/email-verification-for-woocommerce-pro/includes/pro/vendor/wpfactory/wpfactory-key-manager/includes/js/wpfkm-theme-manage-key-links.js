/**
 * wpfkm-theme-manage-key-links.js
 *
 * @version 1.0.0
 * @since   1.0.0
 *
 * @author  WPFactory.
 */

jQuery( document ).ready( function() {

	/**
	 * inArray.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	function inArray( needle, haystack ) {
		var length = haystack.length;
		for ( var i = 0; i < length; i++ ) {
			if ( haystack[ i ] == needle ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Main function.
	 *
	 * @version 1.0.1
	 * @since   1.0.0
	 */
	jQuery( 'div.theme-actions' ).each( function() {
		var theme_slug = jQuery( this ).parents( 'div.theme' ).attr( 'data-slug' );
		if ( inArray( theme_slug, wpfkm_object.themes_to_update ) ) {
			jQuery( this ).append( '<a' +
					' title="' + wpfkm_object.status_messages[ theme_slug ] + '"' +
					' class="button wpfkm_manage_key_theme"' +
					' href="' + wpfkm_object.admin_url + 'admin.php?page=wpfactory-key-manager&item_type=theme&item_slug=' + theme_slug + '">' +
				wpfkm_object.manage_key_text + '</a>' );
		}
	} );

} );
