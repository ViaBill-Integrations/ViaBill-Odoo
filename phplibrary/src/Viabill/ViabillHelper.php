<?php

namespace App\Viabill;

use App\Viabill\SampleData;

class ViabillHelper
{
    /**
     * Check whether you want to use sample data or not
     */
    private const USE_SAMPLE_DATA = 1;

    /**
     * @var bool
     */
    protected $testMode;

    /**
     * @var string
     */
    protected $transactionType;

    /**
     * @var string
     */
    protected $apiSecret;

    /**
     * @var string
     */
    public $apiKey;

    /**
     * @var string
     */
    public $priceTagScript;
    
    /**
     * ViabillHelper constructor.  
     */
    public function __construct()
    {
        $use_sample_data = self::USE_SAMPLE_DATA;
        if ($use_sample_data) {
            $this->loadSampleSettings();            
        } else {
            $this->loadViabillSettings();
        }        
    }

    public function getTestMode()
    {
        return $this->testMode;
    }

    public function getTransactionType($transaction_id = null)
    {
        return $this->transactionType;
    }

    public function getAPIKey()
    {
        return $this->apiKey;
    }

    public function getSecretKey()
    {
        return $this->apiSecret;
    }
    
    public function getSuccessURL($order)
    {
        $base_url = $this->getBaseURL();
        $success_url = 'index.php?controller=success&transaction=' . $order['description'];
        return $base_url.$success_url;
    }

    public function getCancelURL($order)
    {
        $base_url = $this->getBaseURL();
        $cancel_url = 'index.php?controller=cancel&transaction=' . $order['description'];
        return $base_url.$cancel_url;
    }

    public function getCallbackURL($order)
    {
        $base_url = $this->getBaseURL();
        $callback_url = 'index.php?controller=callback&transaction=' . $order['description'] . '&capture=' . $order['capture'];
        return $base_url.$callback_url;
    }

    public function getCheckoutURL()
    {
        $base_url = $this->getBaseURL();
        $checkout_url = 'index.php?controller=checkout&transaction=';
        return $base_url.$checkout_url;
    }
    
    public function getCheckoutRedirectURL($source, $transaction_id, $order_id)
    {
        $redirect_url = null;
        $base_url = $this->getBaseURL();
        if ($source == 'success') {
            $redirect_url = $base_url.'pages/thankyou.php';
        } else if ($source == 'cancel') {
            $redirect_url = $base_url.'pages/ordercancelled.php';
        }

        return $redirect_url;
    }

    public function resolveCheckoutCallbackAction($callback_action, $transaction_id, $order_id)
    {
        switch ($callback_action) {
        case 'approve':
            $this->log("Payment {$transaction_id} for order #{$order_id} is Approved!");
            break;
        case 'cancel':
            $this->log("Payment {$transaction_id} for order #{$order_id} is Cancelled!");
            break;
        case 'reject':
            $this->log("Payment {$transaction_id} for order #{$order_id} is Rejected!");
            break;
        case 'pending':
            $this->log("Payment {$transaction_id} for order #{$order_id} is Pending!");
            break;
        }
    }

    /**
     * TODO:
     * Store message(s) for further debugging purposes
     * Note that each platform/framework will have a specific built-in function for logging
     */
    public function log($msg)
    {
        $log_filepath = __DIR__.'/../../logs/transactions.log';
        if (!empty($log_filepath)) {
            $log_entry = date('Y-m-d H:i:s').' '.$msg."\n";
            file_put_contents($log_filepath, $log_entry, FILE_APPEND);
        }
    }

    /**
     * TODO:
     * Send the HTTP response back to the Viabill server
     * Note that each platform/framework will have a specific built-in function
     * to send back the responses. Use this instead of the following
     * "raw" method.
     */
    public function httpResponse($content, $status_code)
    {
        // TODO: 
        // Each PHP framework has its own way of sending
        // back responses to the requests.
        // Make sure you are using the correct one.

        status_header($status_code);

        header('content-type: text/plain; charset=utf-8');        

        die($content);
    }

    /**
     * TODO:
     * Redirect the visitor/buyer into another URL
     * Note that each platform/framework will have a specific built-in function
     * for creating redirections. Use this instead of the following
     * "raw" method.      
     */
    public function httpRedirect($redirect_url, $status_code = 302)
    {       
        header("Location: $redirect_url", true, $status_code);
 
        return true;
    }

    public function getBaseURL()
    {
        $errors_found = false;
        $error_msg = '';

        $protocol = null;
        $host = null;
        $uri = null;

        // make sure HTTPS is used
        if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
            $protocol = "https";
        } else {
            $protocol = 'http';
            exit("You should use the secure HTTPS protocol, instead of HTTP");
        }

        // make sure you are running on a local host, it should be publicly
        // accesible URL
        $host = $_SERVER['HTTP_HOST'];
        if (strpos($host, 'localhost')!==false) {
            exit("You should run it on a local host!");
        }

        // Clean request URI, if needed
        $uri = $_SERVER['REQUEST_URI'];                
        $uri = str_replace(
            [           
            'examples/cancel.php',
            'examples/capture.php',
            'examples/checkout.php',
            'examples/myviabill.php',
            'examples/notifications.php',
            'examples/refund.php',
            'pages/thankyou.php',
            'pages/ordercancelled.php',
            'index.php'], '', $uri
        );
        $pos = strpos($uri, '?');
        if ($pos !== false) {
            $uri = substr($uri, 0, $pos-1);
        }
                                                        
        // Print the link
        return rtrim($protocol . '://' . $host . $uri, '/').'/';
    }

    /**
     * TODO: 
     * The Viabill settings, such as apiKey, secretKey,
     * testMode, priceTag preferenes, etc should be stored
     * in a database table and retrieved when needed.
     * The actual values usually are generated during
     * the registration process.
     * If you need help contact tech@viabill.com
     */
    protected function loadViabillSettings()
    {                
        exit(
            "loadViabillSettings not implemented yet!
            You need to implement it first or set the USE_SAMPLE_DATA = 1 instead,
            if you want to try the transactions with sample data."
        );
    }
    
    protected function loadSampleSettings()
    {        
        $this->testMode = SampleData::TEST_MODE;
        $this->transactionType = SampleData::TRANSACTION_TYPE;
        $this->apiSecret = SampleData::SECRET_KEY;
        $this->apiKey = SampleData::API_KEY;
        $this->priceTagScript = SampleData::getPricetagScript();

        // Sanity checks - Make sure they have been initialized properly
        $sample_filepath = str_replace('ViabillHelper.php', '<strong>SampleData.php</strong>', __FILE__);
        if ($this->apiKey == 'XXXXX') {            
            exit("You need to initialized the <strong>API KEY</strong> inside the $sample_filepath file!");
        }
        if ($this->apiSecret == 'XXXXX') {
            exit("You need to initialized the <strong>SECRET KEY</strong> inside the $sample_filepath file!");
        }
    }    
}