(function( $ ) {

	$(document).on("ready", function() {

		add_wcb_handlers($);

	});


	jQuery(document).ajaxSuccess(function(e, xhr, settings) {
		if(settings.data.search('action=save-widget') != -1 && (settings.data.search('widget-id=wcb-filterwidget') != -1 || settings.data.search('id_base=wcb-filterwidget') != -1) ) {
			add_wcb_handlers($);
		}
	});

})( jQuery );

function add_wcb_handlers($) {

	$('input.brandLayoutVal').val($('input.brandLayoutVal').attr("data-value"));

	$('input.priceOptions-priceCheck').off('change');
	$('input.priceOptions-priceCheck').on("change", function(e) {
		if (e.currentTarget.checked === true) {
			$('div.priceOptions-container').slideDown();
		} else if (e.currentTarget.checked === false) {
			$('div.priceOptions-container').slideUp();
		}
	});

	$('input.brandOptions-brandCheck').off('change');
	$('input.brandOptions-brandCheck').on("change", function(e) {
		if (e.currentTarget.checked === true) {
			$('div.brandOptions-container').slideDown();
		} else if (e.currentTarget.checked === false) {
			$('div.brandOptions-container').slideUp();
		}
	});

	$('div.brandOptions-container input.brandLayout').off("change");
	$('div.brandOptions-container input.brandLayout').on("change", function(e) {
		$('div.brandOptions-container input.brandLayoutVal').val(e.currentTarget.value);
	});

	$('button.customAttributes-btn').off("click");
	$('button.customAttributes-btn').on("click", function(e) {
		var toActivate = $('div.customAttributes-row[data-ca-key="' + $(e.currentTarget).siblings('select.customAttributes-select').val() + '"]');
		if (toActivate.length && !toActivate.is('.active')) {
			toActivate.addClass('active');
			toActivate.slideDown();
		}
		return false;
	});

	$('button.customAttributes-rm-btn').off('click');
	$('button.customAttributes-rm-btn').on('click', function(e) {
		var toDeactivate = $('div.customAttributes-row[data-ca-key="' + $(e.currentTarget).attr('data-ca-key') + '"]');
		if (toDeactivate.length && toDeactivate.is('.active')) {
			toDeactivate.removeClass('active');
			toDeactivate.slideUp();
		}
		return false;
	});

}
