(function( $ ) {
  wcbHandlers = {};

  $.fn.serializeObject = function() {
    var o = {};
    var a = this.serializeArray();
    $.each(a, function() {
      if (o[this.name] !== undefined) {
        if (!o[this.name].push) {
          o[this.name] = [o[this.name]];
        }
        o[this.name].push(this.value || '');
      } else {
        o[this.name] = this.value || '';
      }
    });
    return o;
  };

  wcbSliderInit = function() {

    if (!wcbGlobals) wcbGlobals = {};
    wcbGlobals.wcbSlider = {
      cMin: parseFloat($("#sliderInitVals").data("min")),
      cMax: parseFloat($("#sliderInitVals").data("max")),
      aMin: parseFloat($("#slider-range").data("min")),
      aMax: parseFloat($("#slider-range").data("max")),
    };

    $(function() {
      $( "#slider-range" ).slider({
        range: true,
        min: wcbGlobals.wcbSlider.aMin,
        max: wcbGlobals.wcbSlider.aMax,
        values: [ wcbGlobals.wcbSlider.cMin, wcbGlobals.wcbSlider.cMax ],
        step: 0.01,
        slide: function( event, ui ) {
          $("#wcb_price_min").val( ui.values[0] );
          $("input#sliderWrapper-minInput").val(ui.values[0]);
          $("#wcb_price_max").val( ui.values[1] );
          $("input#sliderWrapper-maxInput").val(ui.values[1]);
        }
      });
      $( "#wcb_price_min" ).val( $( "#slider-range" ).slider( "values", 0 ) );
      $( "#wcb_price_max" ).val( $( "#slider-range" ).slider( "values", 1 ) );
      $("input#sliderWrapper-minInput").val($( "#slider-range" ).slider( "values", 0 ) );
      $("input#sliderWrapper-maxInput").val($( "#slider-range" ).slider( "values", 1 ) );
    });
  };

  setupHandlers = function() {
    $('div.tilesWrapper .tileItem').off();
    wcbHandlers.tiles = $('div.tilesWrapper .tileItem').on("click", function(e) {
      if ( $(e.currentTarget).is('.selected') ) {
        $(e.currentTarget).removeClass("selected");
      } else {
        $(e.currentTarget).addClass("selected");
      };
    });

    $('.brandCheck-checkboxWrapper .brandCheck-checkbox').off();
    wcbHandlers.checks = $('.brandCheck-checkboxWrapper .brandCheck-checkbox').on("click", function(e){
      if ( $(e.currentTarget).is('.selected') ) {
        $(e.currentTarget).removeClass("selected");
      } else {
        $(e.currentTarget).addClass("selected");
      };
    });
console.log("Setting up the handler 1");
    $('input[name="price_min"]').off();
    $('input[name="price_min"]').on('change', function(e) {
      var newVal = $(this).val();
      console.log(newVal, isFinite(newVal));
      if (isFinite(newVal)) $('#slider-range').slider({values: [newVal, $('input[name="price_max"]').val()]})
    });
console.log("Setting up the handler 2");
    $('input[name="price_max"]').off();
    $('input[name="price_max"]').on('change', function(e) {
      var newVal = $(this).val();
      console.log(newVal, isFinite(newVal));
      if (isFinite(newVal)) $('#slider-range').slider({values: [$('input[name="price_min"]').val(), newVal]})
    });


    $('#wcb_form_update_btn').off();
    $('#wcb_form_update_btn').on("click", function () {
        $('.wcbLoader').show();
        wcb_update_filter(event);
    });

    $('#wcb_form_reset_btn').off();
    $('#wcb_form_reset_btn').on("click", function () {
        $('.wcbLoader').show();
        wcb_clear_filter(event);
    });

  };

  var wcb_clear_filter = function(event) {
    event.preventDefault();

    $(wcbGlobals.productContainerSelector).load(window.location.href + ' ' + wcbGlobals.productContainerSelector, function(){
      $('.widget.widget_wcb-filterwidget').load(window.location.href + ' .widget.widget_wcb-filterwidget', function() {
        setupHandlers();
        wcbSliderInit();
        $('.wcbLoader').hide();
      });
    });
    return false;
  };

  var wcb_update_filter = function(event) {
    event.preventDefault();

    var newQuery = window.location.href.indexOf('?') > -1 ? '&wcb_filter=' : '?wcb_filter=',
        formData,
        workingHref = window.location.href,
        brandIds = [];

    $('.brandCheck-checkboxWrapper .brandCheck-checkbox.selected').each(function(index, el) {
      brandIds.unshift(parseInt($(el).attr("data-id")));
    });
    $('div.tilesWrapper .tileItem.selected').each(function(index, el) {
      brandIds.unshift(parseInt($(el).attr("data-id")));
    });
    $('#brandInput').val(brandIds.join('_'));

    formData = $('form#wcb_filterForm').serializeObject();
    if (formData) {

      /** PRICE **/
      if (formData.price_min && formData.price_max) {
        formData.price_min = parseFloat(formData.price_min);
        formData.price_max = parseFloat(formData.price_max);
        newQuery += 'price[' + formData.price_min.toFixed(2) + '_' + formData.price_max.toFixed(2) + ']';
      } else if (formData.price_min && !formData.price_max) {
        formData.price_min = parseFloat(formData.price_min);
        newQuery += 'price[' + formData.price_min.toFixed(2) + ']';
      } else if (!formData.price_min && formData.price_max) {
        formData.price_max = parseFloat(formData.price_max);
        newQuery += 'price[0.00_' + formData.price_max.toFixed(2) + ']';
      }

      /** BRANDS **/
      if (formData.brand) {
        newQuery += 'brand['+formData.brand+']';
      }

      /** Custom Attributes **/
      var attributes = {};
      $('.customAttributes-wcbCheck:checked').each(function(idx, el) {
        var attributeType = $(this).attr('data-value'),
            attributeValue = $(this)[0].id;

        if (!attributes[attributeType]) {
          attributes[attributeType] = attributeValue;
        } else {
          attributes[attributeType] += '_' + attributeValue;
        }
      });
        for (var attrib in attributes) {
          newQuery += 'attribute[' + attrib + ':' + attributes[attrib] + ']';
        }

      if (workingHref.indexOf('wcb_filter') > -1) {
        workingHref = workingHref.replace(new RegExp(/&?(wcb_filter=).*[&#]?/g), newQuery);
      } else {
        workingHref += newQuery;
      }
    }
    $(wcbGlobals.productContainerSelector).load(workingHref + ' ' + wcbGlobals.productContainerSelector, function() {
      $('.wcbLoader').hide();
    });
    $('button#wcb_form_reset_btn').attr('disabled', false);
    $('button#wcb_form_reset_btn').removeClass("disabled");
    return false;
  }

  setupHandlers();
  wcbSliderInit()
})( jQuery );
