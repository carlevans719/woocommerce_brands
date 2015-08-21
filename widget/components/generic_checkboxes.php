<?php
global $wcbFilter;
$availableAttributes = array_keys($wcbFilter->get_params('availableAttributes'));
$filterAttributes = is_array($wcbFilter->get_params('attribute')) ? $wcbFilter->get_params('attribute') : array(0=>$wcbFilter->get_params('attribute'));
$componentInner = '';
$componentMarkup = '<div class="attributeCheck-wrapper"><input type="hidden" id="attributeInput" name="attribute"><ul>';
for ($i=0; $i < count($availableAttributes); $i++) { 
	$is_active = in_array(intval($availableAttributes[$i]), $filterAttributes) ? 'selected" checked="checked"':'"';

	$componentInner .= '<li class="attributeCheck-checkboxWrapper">
	<label for="attributeCheck-'.$availableAttributes[$i].'">'.get_post($availableAttributes[$i])->post_title.'
		<input type="checkbox" class="attributeCheck-checkbox '.$is_active.' data-id="'.$availableAttributes[$i].'" id="attributeCheck-'.$availableAttributes[$i].'">
	</label></li>';
}
$componentMarkup = $componentInner ? $componentMarkup . $componentInner . '</ul></div>' : '';
