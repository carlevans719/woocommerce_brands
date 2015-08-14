<?php
global $wcbFilter;
$availableBrands = array_keys($wcbFilter->get_params()['availableBrands']);
$filterBrands = is_array($wcbFilter->get_params()['brand']) ? $wcbFilter->get_params()['brand'] : array(0=>$wcbFilter->get_params()['brand']);
$componentMarkup = '<div class="tilesWrapper"><input type="hidden" id="brandInput" name="brand"><ul>';
for ($i=0; $i < count($availableBrands); $i++) { 
	$is_active = in_array(intval($availableBrands[$i]), $filterBrands) ? 'selected" data-content="X"':'"';

	$componentMarkup .= '
	<li class="tileItem '.$is_active.' data-id="'.$availableBrands[$i].'">
		<div class="tileThumb-container">'.get_the_post_thumbnail($availableBrands[$i]).'</div>
		<div class="tileCaption-container">
			<p class="tileCaption">'.get_post($availableBrands[$i])->post_title.'</p>
		</div>
	</li>';
}
$componentMarkup .= '</ul></div>';