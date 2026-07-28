<?php
/**
 * supplies javascript to dynamically update the states/provinces list when the country is changed
 * TABLES: zones
 *
 * return string
 */
  function zen_fec_js_zone_list_shipping($country, $form, $field) {
    global $db;
    $countries = $db->Execute("select distinct zone_country_id
                               from " . TABLE_ZONES . "
                               order by zone_country_id");
    $num_country = 1;
    $output_string = '';
    while (!$countries->EOF) {
      if ($num_country == 1) {
        $output_string .= '  if (' . $country . ' == "' . $countries->fields['zone_country_id'] . '") {' . "\n";
      } else {
        $output_string .= '  } else if (' . $country . ' == "' . $countries->fields['zone_country_id'] . '") {' . "\n";
      }

      $states = $db->Execute("select zone_name, zone_id
                              from " . TABLE_ZONES . "
                              where zone_country_id = '" . $countries->fields['zone_country_id'] . "'
                              order by zone_name");
      $num_state = 1;
      while (!$states->EOF) {
        if ($num_state == '1') $output_string .= '    ' . $form . '.' . $field . '.options[0] = new Option("' . PLEASE_SELECT . '", "");' . "\n";
        $output_string .= '    ' . $form . '.' . $field . '.options[' . $num_state . '] = new Option("' . $states->fields['zone_name'] . '", "' . $states->fields['zone_id'] . '");' . "\n";
        $num_state++;
        $states->MoveNext();
      }
      $num_country++;
      $countries->MoveNext();
      $output_string .= '    hideStateFieldShipping(' . $form . ');' . "\n" ;
    }
    $output_string .= '  } else {' . "\n" .
                      '    ' . $form . '.' . $field . '.options[0] = new Option("' . TYPE_BELOW . '", "");' . "\n" .
                      '    showStateFieldShipping(' . $form . ');' . "\n" .
                      '  }' . "\n";
    return $output_string;
  }
 
  function enable_shippingAddressCheckbox() {
    if ($_SESSION['cart']->get_content_type() == 'virtual') {
      return false;
    }
    if (FEC_SHIPPING_ADDRESS != 'true') {
      return false;
    }
    return true;  
  }
  
  function enable_shippingAddress() {
    if (isset($_POST['shippingAddress']) && $_POST['shippingAddress'] == '1') { 
      return false;
    }
    if ($_SESSION['cart']->get_content_type() == 'virtual') {
      return false;
    }
    if (FEC_SHIPPING_ADDRESS != 'true') {
      return false;
    }
    return true;  
  }
  
    if (!function_exists('zen_output_string_protected')) {
        function zen_output_string_protected($str) {
            return zen_db_prepare_input($str);
        }
    }  
// eof