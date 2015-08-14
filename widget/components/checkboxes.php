<?php
global $wcbFilter;
$availableBrands = array_keys($wcbFilter->get_params()['availableBrands']);
$filterBrands = is_array($wcbFilter->get_params()['brand']) ? $wcbFilter->get_params()['brand'] : array(0=>$wcbFilter->get_params()['brand']);
$componentMarkup = '<div class="brandCheck-wrapper"><input type="hidden" id="brandInput" name="brand"><ul>';
for ($i=0; $i < count($availableBrands); $i++) { 
	$is_active = in_array(intval($availableBrands[$i]), $filterBrands) ? 'selected" checked="checked"':'"';

	$componentMarkup .= '<li class="brandCheck-checkboxWrapper">
	<label for="brandCheck-'.$availableBrands[$i].'">'.get_post($availableBrands[$i])->post_title.'
		<input type="checkbox" class="brandCheck-checkbox '.$is_active.' data-id="'.$availableBrands[$i].'" id="brandCheck-'.$availableBrands[$i].'">
	</label></li>';
}
$componentMarkup .= '</ul></div>';