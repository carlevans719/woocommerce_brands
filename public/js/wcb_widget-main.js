(function( $ ) {
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

  $('div.tilesWrapper .tileItem').on("click", function(e) {
    if ( $(e.currentTarget).is('.selected') ) {
      $(e.currentTarget).attr("data-content", "");
      $(e.currentTarget).removeClass("selected");
    } else {
      // todo: add a tick code to the line below instead of an 'x'
      $(e.currentTarget).attr("data-content", "X");
      $(e.currentTarget).addClass("selected");
    };
  });

  $('.brandCheck-checkboxWrapper .brandCheck-checkbox').on("click", function(e){
    if ( $(e.currentTarget).is('.selected') ) {
      $(e.currentTarget).removeClass("selected");
    } else {
      // todo: add a tick code to the line below instead of an 'x'
      $(e.currentTarget).addClass("selected");
    };
  });

  $('#wcb_form_update_btn').on("click", function () {
      wcb_update_filter(event);
  });

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

      if (workingHref.indexOf('wcb_filter') > -1) {
        workingHref = workingHref.replace(new RegExp(/&?(wcb_filter=).*[&#]?/g), newQuery);
      } else {
        workingHref += newQuery;
      }
    }
    window.location.href = workingHref;
    return false;
  }
})( jQuery );