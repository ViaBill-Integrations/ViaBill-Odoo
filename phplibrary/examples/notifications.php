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
    $response = $viabill->notifications($data);
    if (!empty($response['messages'])) {
        var_dump($response['messages']);
    } else {
        exit("You have no notification messages!");
    }
} catch (ViabillRequestException $e) {
    var_dump($e);
    return false;
}