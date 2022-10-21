<?php

use App\Viabill\Viabill;
use App\Viabill\SampleData;

class ViabillExample
{
           
    /**
     * @return Viabill
     *
     * Initializing Viabill Class for examples
     */
    public static function initialize() : Viabill
    {        
        return new Viabill();
    }    

    public static function  getOrderData($renew_order_data = false)
    {
        $sample_order = SampleData::getSampleOrderData($renew_order_data);

        $order = [
            'id' => $sample_order['id'], // the order ID
            'transaction_id' => $sample_order['transaction_id'], // the transaction ID
            'amount' => $sample_order['amount'], // the amount of the order
            'currency' => $sample_order['currency'], // the currency of the order           
        ];

        return $order;
    }
}