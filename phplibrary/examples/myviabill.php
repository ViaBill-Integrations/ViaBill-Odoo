<?php
require_once '../vendor/autoload.php';
require_once 'ViabillExample.php';

use \App\Viabill\Exceptions\ViabillRequestException;

$viabill = ViabillExample::initialize();

$data = [
    'key' => $viabill->helper->getAPIKey(),
    'secret' => $viabill->helper->getSecretKey()
];

$response = null;

try {
    $response = $viabill->myViabill($data);
	if (!empty($response['url'])) {
        $redirect_url = $response['url'];
        return $viabill->helper->httpRedirect($redirect_url);
    } else {
        exit($response['error']);
    }
} catch (ViabillRequestException $e) {
    var_dump($e);
    return false;
}
