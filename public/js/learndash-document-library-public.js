(function ($) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
	// var data = [
	// 	{
	// 		name: "userInfo1",
	// 		label: "My Account 1",
	// 		labelIcon: "customIcon1",
	// 		type: "bar",
	// 		template: "#template1"
	// 	},

	// ];

	// var hl = $.hlRightPanel(data);

	
	jQuery( document ).ready(function() {
		jQuery(".ldd_sidebar ul li h5 button").click(function(){
			// Find the parent li and then find the next element with class '.ld-side-tble'
			var $ldSideTable = jQuery(this).parent().next('.ld-side-tble');
			
			// Close all .ld-side-tble elements except the one you've clicked
			jQuery(".ldd_sidebar ul li .ld-side-tble").not($ldSideTable).fadeOut();
		
			// Toggle the visibility of the clicked .ld-side-tble element
			$ldSideTable.fadeToggle();
		
			// Toggle the rotation of the clicked button
			var rotated = jQuery(this).data("rotated") || false;
			var rotation = rotated ? 0 : 180;
			jQuery(this).css("transform", "rotate(" + rotation + "deg)");
			jQuery(this).data("rotated", !rotated);
		});
		
	});

})(jQuery);
