(function( $ ) {
	'use strict';


	jQuery(document).ready(function($) {
	    // Open the modal
	    $('.summaraize-popup-btn').on('click', function() {
	        $(this).next('.summaraize-popup-modal').fadeIn();
	    });

	    // Close the modal
	    $('.summaraize-popup-close').on('click', function() {
	        $(this).closest('.summaraize-popup-modal').fadeOut();
	    });

	    // Close the modal when clicking outside the content
	    $(window).on('click', function(event) {
	        if ($(event.target).hasClass('summaraize-popup-modal')) {
	            $('.summaraize-popup-modal').fadeOut();
	        }
	    });
	});



})( jQuery );
