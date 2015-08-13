<?php
global $wcbFilter;
$price = $wcbFilter->get_params()['price'];
$absPrice = $wcbFilter->get_params()['absPrice'];
$min_price = $price[0];
$max_price = $price[1];
$absMin_price = $absPrice[0];
$absMax_price = $absPrice[1];
$componentMarkup = '<script>
    $ = jQuery;
    $(function() {
        $( "#slider-range" ).slider({
            range: true,
            min: '. $absMin_price .',
            max: '. $absMax_price .',
            values: [ '. $min_price . ', ' . $max_price .' ],
            slide: function( event, ui ) {
                $( "#wcb_price_min" ).val( $( "#slider-range" ).slider( "values", 0 ) );
                $($( "span.ui-slider-handle.ui-state-default.ui-corner-all")[0]).attr("data-content", "£" + $( "#slider-range" ).slider( "values", 0 ) );
                $( "#wcb_price_max" ).val( $( "#slider-range" ).slider( "values", 1 ) );
                $($( "span.ui-slider-handle.ui-state-default.ui-corner-all")[1]).attr("data-content", "£" + $( "#slider-range" ).slider( "values", 1 ) );
            }
        });
        $( "#wcb_price_min" ).val( $( "#slider-range" ).slider( "values", 0 ) );
        $( "#wcb_price_max" ).val( $( "#slider-range" ).slider( "values", 1 ) );
        $($( "span.ui-slider-handle.ui-state-default.ui-corner-all")[0]).attr("data-content", "£" + $( "#slider-range" ).slider( "values", 0 ) );
        $($( "span.ui-slider-handle.ui-state-default.ui-corner-all")[1]).attr("data-content", "£" + $( "#slider-range" ).slider( "values", 1 ) );
    });
</script>

<input name="price_min" type="hidden" id="wcb_price_min">
<input name="price_max" type="hidden" id="wcb_price_max">
<div id="slider-range" data-min="£'.$absMin_price.'" data-max="£'.$absMax_price.'"></div>
';