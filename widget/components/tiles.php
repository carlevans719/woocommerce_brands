<?php
global $wcbFilter;
$availableBrands = is_array($wcbFilter->get_params('availableBrands')) ? array_keys($wcbFilter->get_params('availableBrands')) : null;
$filterBrands = is_array($wcbFilter->get_params('brand')) ? $wcbFilter->get_params('brand') : array(0=>$wcbFilter->get_params('brand'));
$componentInner = '';
$componentMarkup = '<div class="tilesWrapper clearfix"><input type="hidden" id="brandInput" name="brand"><ul class="tilesWrapper-tileList clearfix">';
for ($i=0; $i < count($availableBrands); $i++) {
	$is_active = in_array(intval($availableBrands[$i]), $filterBrands) ? 'selected" data-content="X"':'"';
	$imgMarkup = get_the_post_thumbnail($availableBrands[$i]) ? get_the_post_thumbnail($availableBrands[$i]) : '<img width="151" height="100" src="https://placehold.it/151x100" class="attachment-post-thumbnail wp-post-image" alt="'.get_post($availableBrands[$i])->post_title.'" />';
	$componentInner .= '
	<li class="tileItem clearfix '.$is_active.' data-id="'.$availableBrands[$i].'">
		<div class="tileThumb-container"><div class="selectedOverlay"></div>'.$imgMarkup.'</div>
		<div class="tileCaption-container clearfix">
			<p class="tileCaption">'.get_post($availableBrands[$i])->post_title.'</p>
		</div>
	</li>';
}
$componentMarkup = $componentInner ? $componentMarkup . $componentInner . '</ul></div>' : '';
