<?php
if(!function_exists('smartlogix_get_control')) {
	function smartlogix_get_control($type, $label, $id, $name, $value = '',  $data = null, $info = '', $style = 'input widefat') {
		$output = '<p>';
		switch($type) {
			case 'text':
				if($label != '') { $output .= '<label for="'.$name.'">'.$label.'</label>:'; }
				$output .= '<input type="text" id="'.$id.'" name="'.$name.'" value="'.$value.'" class="multilanguage-input '.$style.'">';
				break;
			case 'checkbox':
				$output .= '<input type="checkbox" id="'.$id.'" name="'.$name.'" value="1" class="input" '.checked($value, 1, false).' />';
				if($label != '') { $output .= '<label for="'.$name.'">'.$label.'</label>'; }		
				break;	
			case 'textarea':
				if($label != '') { $output .= '<label for="'.$name.'">'.$label.'</label>:<br />'; }
				$output .= '<textarea id="'.$id.'" name="'.$name.'" class="multilanguage-input '.$style.'" style="height: 100px;">'.$value.'</textarea>';			
				break;
			case 'textarea-big':
				if($label != '') { $output .= '<label for="'.$name.'">'.$label.'</label>:<br />'; }
				$output .= '<textarea id="'.$id.'" name="'.$name.'" class="multilanguage-input '.$style.'" style="height: 300px;">'.$value.'</textarea>';			
				break;
			case 'select':
				if($label != '') { $output .= '<label for="'.$name.'">'.$label.'</label>:'; }
				$output .= '<select id="'.$id.'" name="'.$name.'" class="'.$style.'">';
				if($data) {
					foreach($data as $option) {
						$output .= '<option '.((isset($option['parent']))?'data-parent="'.$option['parent'].'"':'').' value="'.$option['value'].'" '.selected($value, $option['value'], false).'>'.$option['text'].'</option>';
					}
				}
				$output .= '</select>';
				break;
			case 'upload':
				if($label != '') { $output .= '<label for="'.$name.'">'.$label.'</label>:<br />'; }
				$output .= '<input type="text" id="'.$id.'" name="'.$name.'" value="'.$value.'" class="'.$style.'" style="width: 74%;" />';
				$output .= '<input type="button" value="Upload Image" class="smartlogix_uploader_button" id="upload_image_button" style="width: 25%;" />';
				break;
			case 'multiselect':
				if($label != '') { $output .= '<label for="'.$name.'">'.$label.'</label>:<br />'; }
				$output .= '<select id="'.$id.'" name="'.$name.'" class="'.$style.'" multiple="multiple" style="height: 220px">';
				if($data) {
					foreach($data as $option) {
						if(is_array($value) && in_array($option['value'], $value)) {
							$output .= '<option value="'.$option['value'].'" selected="selected">'.$option['text'].'</option>';
						} else {
							$output .= '<option value="'.$option['value'].'">'.$option['text'].'</option>';
						}
					}
				}
				$output .= '</select>';
				break;
		}
		if($info != '') {
			$output .= '<span style="font-size: small;">'.$info.'</span>';
		}
		$output .= '</p>';
		return $output;
	}
}
?>