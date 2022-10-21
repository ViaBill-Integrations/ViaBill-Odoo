<?php
require_once '../vendor/autoload.php';
require_once 'ViabillExample.php';

use App\Viabill\Exceptions\ViabillInvalidValueException;
use App\Viabill\Exceptions\ViabillRequestException;

$viabill = ViabillExample::initialize();

$order = ViabillExample::getOrderData();

$data = [
    'id' => $order['transaction_id'], // Given Transaction id
    'apikey' => $viabill->helper->getAPIKey(),
    'currency' => $order['currency']
];

$response = null;

try {
    $response = $viabill->cancelTransaction($data);
} catch (ViabillInvalidValueException $e) {
} catch (ViabillRequestException $e) {
    var_dump($e);
    return false;
}
// Response status should be 204
var_dump($response);