(function( $ ) {

	$(document).on("ready", function() {

		add_wcb_handlers($);

	});

	
	jQuery(document).ajaxSuccess(function(e, xhr, settings) {
		console.log(settings.data);
		if(settings.data.search('action=save-widget') != -1 && (settings.data.search('widget-id=wcb-filterwidget') != -1 || settings.data.search('id_base=wcb-filterwidget') != -1) ) {
			add_wcb_handlers($);
		}
	});

})( jQuery );

function add_wcb_handlers($) {

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

}
