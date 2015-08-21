<?php
global $wcbFilter;
$availableAttributes = $wcbFilter->get_params('availableAttributes');
$filterAttributes = is_array($wcbFilter->get_params('attribute')) ? $wcbFilter->get_params('attribute') : array(0=>$wcbFilter->get_params('attribute'));
$componentInner = '';
$titles = array();
foreach ($instance as $key => $value) {
	if (strpos($key, 'wcb_ca-') !== false) $titles[substr($key, 7, strlen($key))] = $value ? $value : substr($key, 7, strlen($key));
}
function getCheckbox($class = 'wcbCheck', $id = '', $name = '', $checked = false, $title = '', $other = '') {
	$checked = $checked ? 'checked="checked"' : '';
	return '<label for="'.$id.'">'.$title.'</label><input class="'.$class.'" type="checkbox" '.$checked.' name="'.$name.'" id="'.$id.'" '.$other.'>';
}

$output = '<div class="customAttributes-wrapper">';
foreach ($availableAttributes as $attribute => $values) {
	$title = isset($titles[$attribute]) ? $titles[$attribute] : '';
	$output .=  $title ? '<div class="customAttributes-title">'.$title.'</div>' : '';
	$output .= '<div class="customAttributes-attribute">';
	foreach ($values as $value => $qty) {
		// TODO: change false and the second-to-last $values below to something correct
		$checkbox = getCheckbox('customAttributes-wcbCheck', $value, 'attribute', false, $value, 'data-value="'.$attribute.'"');
		$output .= '<div class="customAttributes-attributeValue">'.$checkbox.'</div>';
	}
	$output .= '</div>';
}
$output .= '</div>';

$componentMarkup = $output;
