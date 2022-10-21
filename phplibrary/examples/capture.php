<?php
require_once '../vendor/autoload.php';
require_once 'ViabillExample.php';

use App\Viabill\Exceptions\ViabillInvalidValueException;

$viabill = ViabillExample::initialize();

$order = ViabillExample::getOrderData();

$data = [
    'id' => $order['transaction_id'], // Given Transaction id
    'apikey' => $viabill->helper->getAPIKey(),
    'amount' => $order['amount'] * -1, // Capture amount must be negative
    'currency' => $order['currency']
];

$response = null;

try {
    $response = $viabill->captureTransaction($data);
} catch (ViabillInvalidValueException $e) {
    var_dump($e);
    return false;
}

var_dump($response);