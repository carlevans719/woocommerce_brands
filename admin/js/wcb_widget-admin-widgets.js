(function( $ ) {

	$(document).on("ready", function() {

		$('input.brandLayoutVal').val($('input.brandLayoutVal').attr("data-value"));

		$('input.brandCheck').on("change", function(e) {
			if (e.currentTarget.checked === true) {
				$('div.brandOptions-container').slideDown();
			} else if (e.currentTarget.checked === false) {
				$('div.brandOptions-container').slideUp();
			}
		});

		$('div.brandOptions-container input.brandLayout').on("change", function(e) {
			$('div.brandOptions-container input.brandLayoutVal').val(e.currentTarget.value);
		});

	});

})( jQuery );
