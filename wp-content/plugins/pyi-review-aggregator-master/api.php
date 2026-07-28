<?php
function pyi_review_aggregator_import($apiBaseURL) {
	try {
		$response = wp_remote_get($apiBaseURL, array(
			'headers' => array(
				'Content-Type' => 'application/json'
			)
		));
		if((!is_wp_error($response)) && (200 == wp_remote_retrieve_response_code($response))) {
			$responseBody = json_decode($response['body']);
			if((json_last_error() == JSON_ERROR_NONE)) {
				if(isset($responseBody->issuccess) && ($responseBody->issuccess == 1) && isset($responseBody->results)) {
					return $responseBody->results;
				}
			}
		}
	} catch(Exception $ex) {
	}
	return false;
}
?>