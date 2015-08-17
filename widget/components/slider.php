<?php
global $wcbFilter;
$price = $wcbFilter->get_params('price');
$availablePrices = $wcbFilter->get_params('availablePrices');
$price[1] = count($price) == 2 ? $price[1] : $availablePrices[1];
if (isset($availablePrices[0]) && ( ($availablePrices[0] === 999999 && $availablePrices[1] === 0) || ($availablePrices[0] === 0 && $availablePrices[1] === 0) ) ) {
    $componentMarkup = '';
} else {
    $componentMarkup = '
    <div class="sliderWrapper">
        <input name="price_min" type="hidden" id="wcb_price_min">
        <input name="price_max" type="hidden" id="wcb_price_max">
    <div id="slider-range" data-min="£'.$availablePrices[0].'" data-max="£'.$availablePrices[1].'"></div></div>
    <script>
    (function( $ ) {
        wcbSliderInit = function() {
            $(function() {
                $( "#slider-range" ).slider({
                    range: true,
                    min: '. $availablePrices[0] .',
                    max: '. $availablePrices[1] .',
                    values: [ '. $price[0] . ', ' . $price[1] .' ],
                    step: 0.01,
                    slide: function( event, ui ) {
                        $( "#wcb_price_min" ).val( ui.values[0] );
                        $($( "span.ui-slider-handle.ui-state-default.ui-corner-all")[0]).attr("data-content", "£" + ui.values[0] );
                        $( "#wcb_price_max" ).val( ui.values[1] );
                        $($( "span.ui-slider-handle.ui-state-default.ui-corner-all")[1]).attr("data-content", "£" + ui.values[1] );
                    }
                });
                $( "#wcb_price_min" ).val( $( "#slider-range" ).slider( "values", 0 ) );
                $( "#wcb_price_max" ).val( $( "#slider-range" ).slider( "values", 1 ) );
                $($( "span.ui-slider-handle.ui-state-default.ui-corner-all")[0]).attr("data-content", "£" + $( "#slider-range" ).slider( "values", 0 ) );
                $($( "span.ui-slider-handle.ui-state-default.ui-corner-all")[1]).attr("data-content", "£" + $( "#slider-range" ).slider( "values", 1 ) );
            });
        };

        wcbSliderInit();
    })( jQuery );
    </script>';
};