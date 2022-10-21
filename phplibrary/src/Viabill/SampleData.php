<?php

namespace App\Viabill;

class SampleData
{
    /**
     * TODO:
     * This is your API Key that uniquely identifies each merchant
     * You retrieve this key during the sign up process
     */
    const API_KEY = 'eyJhbGciOiJIUzI1NiJ9.eyJyb2xlcyI6WyJNRVJDSEFOVCJdLCJ1dWlkIjoiZmQ0Y2JmODEtZWJlYi0xMWVjLTk5ODAtYTEwZmEwYTQyYzQwIiwidHYiOjAsImVudiI6IlBST0RVQ1RJT04iLCJpYXQiOjE2NTUyMTU5MzQsImV4cCI6MTk3MDgzNTEzNH0.sOWmS81MBa0WfA9ubIg-he8W6g7-7y-4FMlW-qOk9ow'; // Your Api Key;

    /**
     * TODO:
     * This is your Secret Key that is used to authenticate your requests
     * to the Viabill server and it is private.
     * You retrieve this key during the sign up process
     */
    const SECRET_KEY = 'dMRPZ5SRoGjx'; // Your Secret Key
    
    /**
     * TODO:
     * Test mode can be 'true' or 'false' as string values. If test mode is true
     * then no transaction with real money take place, but still you are
     * able to test all transaction types.
     */
    const TEST_MODE = 'true'; // 'true' or 'false'

    /**
     * TODO:
     * Transaction type can be "sale" or "authorize"
     * If the transaction type is set to "sale" then after the
     * payment authorization, a payment capture will take place
     */
    const TRANSACTION_TYPE = 'sale'; // 'sale' or 'authorize'

    /**
     * TODO:
     * The PriceTag's merchant ID is a parameter that is used to fine tune
     * the pricetag settings. It is retrieved during the registration phase
     */
    const PRICETAG_MERCHANT_ID = null;

    /**
     * TODO:
     * The Pricetag script is responsible for rendering the Pricetags,
     * and it's retrieved during the registration step.
     * If you have one you can specify it in the function below,
     * otherwise you can use the default one.
     */
    public static function getPricetagScript()
    {
        $merchant_id = self::PRICETAG_MERCHANT_ID;        
        $script = "<script>(function(){var o=document.createElement('script');o.type='text/javascript';o.async=true;o.src='https://pricetag.viabill.com/script/{$merchant_id}';var s=document.getElementsByTagName('script')[0];s.parentNode.insertBefore(o,s);})();</script>";
        return $script;
    }

    /**
     * TODO:
     * Each order must have a unique order ID and transaction ID.
     * You can use any method to generate the random order data,
     * provided you follow this rule.
     */
    public static function getSampleOrderData($renew_order_data = false)
    {
        // Generate some random order data, if needed
        // Note that each transaction should have a unique transaction ID
        $sample_order_filename = __DIR__ . '/../../logs/sample_order.txt';

        $order = null;
        if (file_exists($sample_order_filename)) {
            $order_str = file_get_contents($sample_order_filename);
            if (!empty($order_str)) {
                $order = json_decode($order_str, true);                
            }
        }

        if (empty($order)) $renew_order_data = true;

        if ($renew_order_data) {
            $order_id = mt_rand(1, 32000);
            $transaction_id = 'TRANS'.$order_id;
            $currency = 'USD';
            $amount = mt_rand(100, 300); // the amount should fall into certain valid range
                                          // not all amounts are acceptible            

            $order = [
                'id' => $order_id, // the order ID
                'transaction_id' => $transaction_id, // the transaction ID
                'amount' => $amount, // the amount of the order
                'currency' => $currency // the currency of the order 
            ];
    
            $order_str = json_encode($order);
            file_put_contents($sample_order_filename, $order_str);                                          
        }                

        return $order;
    }
}

