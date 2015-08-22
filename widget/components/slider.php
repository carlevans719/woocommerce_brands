<?php
global $wcbFilter;
$price = $wcbFilter->get_params('price');
$availablePrices = $wcbFilter->get_params('availablePrices');
$price[1] = count($price) == 2 ? $price[1] : $availablePrices[1];
if (isset($availablePrices[0]) && ( ($availablePrices[0] === 999999 && $availablePrices[1] === 0) || ($availablePrices[0] === 0 && $availablePrices[1] === 0) ) ) {
    $componentMarkup = '';
} else {
    $componentMarkup = '
    <div id="slider-range" data-min="'.$availablePrices[0].'" data-max="'.$availablePrices[1].'"></div>
    <div id="sliderInitVals" data-min="'.$price[0].'" data-max="'.$price[1].'"></div>
    <div class="sliderWrapper clearfix">
      <div class="sliderWrapper-labelledInputWrapper clearfix">
        <div class="labelledInput">
          <div class="label" unselectable="on" onselectstart="return false;" onmousedown="return false;">
            min
          </div>
          <input type="number" max="'.$availablePrices[1].'" min="'.$availablePrices[0].'" step="0.01" name="price_min" id="wcb_price_min" value="'.$availablePrices[0].'" placeholder="'.$availablePrices[0].'">
        </div>
        <div class="labelledInput">
          <div class="label" unselectable="on" onselectstart="return false;" onmousedown="return false;">
            max
          </div>
          <input type="number" max="'.$availablePrices[1].'" min="'.$availablePrices[0].'" step="0.01" name="price_max" id="wcb_price_max" value="'.$availablePrices[1].'" placeholder="'.$availablePrices[1].'">
        </div>
      </div>
    </div>';
};
