<?php
require_once '../vendor/autoload.php';
require_once 'ViabillExample.php';

use App\Viabill\Exceptions\ViabillInvalidValueException;
use App\Viabill\Exceptions\ViabillRequestException;

$viabill = ViabillExample::initialize();

$order = ViabillExample::getOrderData();

$data = [
    'id' => $order['transaction_id'], // Transaction number isa a unique id for each apikey
    'apikey' => $viabill->helper->getAPIKey(),
    'amount' => $order['amount'], // Total price to be refunded
    'currency' => $order['currency']
];

try {
    $response = $viabill->refundTransaction($data);
} catch (ViabillInvalidValueException $e) {
} catch (ViabillRequestException $e) {
    var_dump($e);
    return false;
}
// Response status should be 204

var_dump($response);