<?php

namespace Wpai\AddonAPI;

if ( ! defined( 'ABSPATH' ) ) exit;

class PMXI_Addon_Time_Field extends PMXI_Addon_Field {

    public function beforeImport($postId, $value, $data, $logger, $rawData) {
        // Convert to 24 hour format
        return gmdate("H:i", strtotime($value));
    }
}
