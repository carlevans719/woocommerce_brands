<?php
global $wcbFilter;
$availableAttributes = $wcbFilter->get_params('availableAttributes');
$filterAttributes = is_array($wcbFilter->get_params('attribute')) ? $wcbFilter->get_params('attribute') : array(0=>$wcbFilter->get_params('attribute'));
$componentInner = '';
// logit($availableAttributes);
// Colours:
//Black []
//Blue []

//Speeds:
//1mph []

/*Array
(
    [pa_speed] => Array
        (
            [1mph] => 1
        )

    [pa_color] => Array
        (
            [Black] => 2
            [Blue] => 1
            [Green] => 1
        )

)*/

function getCheckbox($class = 'wcbCheck', $id = '', $name = '', $checked = false, $title = '', $other = '') {
	$checked = $checked ? 'checked="checked"' : '';
	return '<label for="'.$id.'">'.$title.'</label><input class="'.$class.'" type="checkbox" '.$checked.' name="'.$name.'" id="'.$id.'" '.$other.'>';
}

$output = '<div class="customAttributes-wrapper">';
foreach ($availableAttributes as $attribute => $values) {
	logit("Here come the attribute and value");
	logit($attribute);
	logit($values);
	$title = $wcbFilter->get_params('wcb_ca-'.$attribute) ? $wcbFilter->get_params('wcb_ca-'.$attribute) : '';
	$output .=  $title ? '<div class="customAttributes-title">'.$title.'</div>' : '';
	$output .= '<div class="customAttributes-attribute">';
	foreach ($values as $value => $qty) {

		// TODO: change false and the second-to-last $values below to something correct
		$checkbox = getCheckbox('customAttributes-wcbCheck', $value, $value, false, $value, 'data-id="'.$attribute.'"');
		$output .= '<div class="customAttributes-attributeValue">'.$checkbox.'</div>';
	}
	$output .= '</div>';
}
$output .= '</div>';

$componentMarkup = $output;

































//
//
//
//
//
//
// $componentMarkup = '<div class="attributeTitle"><div class="attributeCheck-wrapper"><input type="hidden" id="attributeInput" name="attribute"><ul>';
// for ($i=0; $i < count($availableAttributes); $i++) {
// 	$is_active = in_array(intval($availableAttributes[$i]), $filterAttributes) ? 'selected" checked="checked"':'"';
//
// 	$componentInner .= '<li class="attributeCheck-checkboxWrapper">
// 	<label for="attributeCheck-'.$availableAttributes[$i].'">'.get_post($availableAttributes[$i])->post_title.'
// 		<input type="checkbox" class="attributeCheck-checkbox '.$is_active.' data-id="'.$availableAttributes[$i].'" id="attributeCheck-'.$availableAttributes[$i].'">
// 	</label></li>';
// }
// $componentMarkup = $componentInner ? $componentMarkup . $componentInner . '</ul></div>' : '';
