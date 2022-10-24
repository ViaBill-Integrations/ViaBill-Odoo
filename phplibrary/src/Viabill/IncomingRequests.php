<?php

namespace App\Viabill;

use App\Viabill\Exceptions\ViabillRequestException;
use Exception;
use ViabillExample;

class IncomingRequests
{
    /**
     * @var ViabillHelper
     */
    private $helper;

    /**
     * RequestProcessor constructor.
     *
     * @param bool $testMode
     */
    public function __construct()
    {
        $this->helper = new ViabillHelper();
    }

    /**
     * This function for initiating the checkout request to the Viabill server,
     *
     */
    public function checkout_init()
    {
        $parent_dir = dirname(__DIR__, 2);
        require_once $parent_dir . '/vendor/autoload.php';
        require_once $parent_dir . '/examples/ViabillExample.php';
        $json = file_get_contents('php://input');
        $order = json_decode($json, true);
        try {
            $data = $this->getServerData('success');
            $request = print_r($_REQUEST, true);
            $debug_filename = __DIR__ . '/debug.txt';
            $debug_str = "New Request data: " . $request . "\n";
            file_put_contents($debug_filename, $debug_str, FILE_APPEND);

            $viabill = new Viabill($order['test']);

            $data = [
                'apikey' => $this->helper->getAPIKey(),
                'transaction' => $order['description'],
                'order_number' => $order['description'],
                'amount' => $order['amount']['value'],
                'currency' => 'DKK',
//                'currency' => $order['amount']['currency'],
                'success_url' => $viabill->helper->getSuccessURL($order),
                'cancel_url' => $viabill->helper->getCancelURL($order),
                'callback_url' => $viabill->helper->getCallbackURL($order),
                'test' => $order['test']
            ];
            error_log(print_r($data, true));
            try {
                $response = $viabill->checkout($data, []);
                if (isset($response['redirect_url'])) {
                    // This URL is sent by the Viabill server
                    // in order to redirect buyer/customer into the checkout page
                    // on the Viabill server
                    return json_encode(["url" => $response['redirect_url']]);
                }
            } catch (ViabillRequestException $e) {
                error_log($e->getMessage());
                return $this->helper->httpResponse($e->getMessage(), $e->getCode());
            }

        } catch (Exception $e) {
            $code = $e->getCode();
            error_log($e->getMessage());
            $content = "An error has occured during the checkout request to the Viabill server: " . $code;
            return $this->helper->httpResponse($content, $code);
        }
    }

    /**
     * This function is handling the "success" request that is invoked by the Viabill server,
     * if the buyer has completed the payment during the checkout process.
     * It should redirect the buyer into a "Thank you for your payment" page.
     *
     */
    public function checkout_success()
    {
        try {
            $data = $this->getServerData('success');
        } catch (Exception $e) {
            $code = $e->getCode();
            $content = "An error has occured during the success call: " . $code;
            return $this->helper->httpResponse($content, $code);
        }

        return $this->helper->httpRedirect($_ENV['ODOO_BASE_URL'] . '/payment/viabill/return?reference=' . $data['transaction']);
    }

    /**
     * This function is handling the "success" request that is invoked by the Viabill server,
     * if the buyer has completed the payment during the checkout process.
     * It should redirect the buyer into a "Thank you for your payment" page.
     *
     */
    public function checkout_capture()
    {
        $parent_dir = dirname(__DIR__, 2);
        require_once $parent_dir . '/vendor/autoload.php';
        require_once $parent_dir . '/examples/ViabillExample.php';
        $json = file_get_contents('php://input');
        $order = json_decode($json, true);

        try {
            $data = $this->getServerData('capture');
        } catch (Exception $e) {
            $code = $e->getCode();
            $content = "An error has occured during the success call: " . $code;
            return $this->helper->httpResponse($content, $code);
        }

        $viabill = new Viabill($order['test']);

        $captureData = [
            'id' => $order['transaction'],
            'apikey' => $viabill->helper->getAPIKey(),
            // amount must be negative
            'amount' => ($order['amount'] <= 0 ? $order['amount'] : (-1 * abs($order['amount']))),
            'currency' => 'DKK',
        ];
        try {
            $capture = $viabill->captureTransaction($captureData);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->helper->httpResponse($e->getMessage(), $e->getCode());
        }

        if (!$capture) {
            return 404;
        }
        return json_encode(['status' => true]);
    }

    /**
     * This function is handling the "success" request that is invoked by the Viabill server,
     * if the buyer has completed the payment during the checkout process.
     * It should redirect the buyer into a "Thank you for your payment" page.
     *
     */
    public function checkout_void()
    {
        $parent_dir = dirname(__DIR__, 2);
        require_once $parent_dir . '/vendor/autoload.php';
        require_once $parent_dir . '/examples/ViabillExample.php';
        $json = file_get_contents('php://input');
        $order = json_decode($json, true);

        try {
            $data = $this->getServerData('cancel');
        } catch (Exception $e) {
            error_log($e->getMessage());
            $code = $e->getCode();
            $content = "An error has occured during the success call: " . $code;
            return $this->helper->httpResponse($content, $code);
        }

        $viabill = new Viabill($order['test']);

        $voidData = [
            'id' => $order['transaction'],
            'apikey' => $viabill->helper->getAPIKey(),
        ];
        try {
            $void = $viabill->cancelTransaction($voidData);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->helper->httpResponse($e->getMessage(), $e->getCode());
        }

        if (!$void) {
            return 404;
        }

        return json_encode(['status' => true]);
    }


    /**
     * This function is handling the "success" request that is invoked by the Viabill server,
     * if the buyer has completed the payment during the checkout process.
     * It should redirect the buyer into a "Thank you for your payment" page.
     *
     */
    public function checkout_refund()
    {
        $parent_dir = dirname(__DIR__, 2);
        require_once $parent_dir . '/vendor/autoload.php';
        require_once $parent_dir . '/examples/ViabillExample.php';
        $json = file_get_contents('php://input');
        $order = json_decode($json, true);

        try {
            $data = $this->getServerData('refund');
        } catch (Exception $e) {
            $code = $e->getCode();
            $content = "An error has occured during the success call: " . $code;
            return $this->helper->httpResponse($content, $code);
        }

        $viabill = new Viabill($order['test']);

        $refundData = [
            'id' => $order['transaction'],
            'apikey' => $viabill->helper->getAPIKey(),
            'amount' => ($order['amount'] >= 0 ? $order['amount'] : abs($order['amount'])),
            'currency' => $order['currency'],
        ];

        try {
            $refund = $viabill->refundTransaction($refundData);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->helper->httpResponse($e->getMessage(), $e->getCode());
        }

        if (!$refund) {
            return 404;
        }
        return json_encode(['status' => true]);
    }


    /**
     * This function is handling the "status" request that is invoked by the Viabill server,
     *
     */
    public function checkout_status()
    {
        $parent_dir = dirname(__DIR__, 2);
        require_once $parent_dir . '/vendor/autoload.php';
        require_once $parent_dir . '/examples/ViabillExample.php';
        $json = file_get_contents('php://input');
        $order = json_decode($json, true);
        $viabill = ViabillExample::initialize();

        $data = [
            'apikey' => $this->helper->getAPIKey(),
            'id' => $order['transaction'],
        ];

        try {
            $response = $viabill->status($data, [], true);
        } catch (Exception $e) {
            return $this->helper->httpResponse($e->getMessage(), $e->getCode());
        }

        $r = json_decode($response['response']['body'], true);

        return json_encode(['status' => $r['state']]);

    }


    /**
     * This function is handling the "cancel" request that is invoked by the Viabill server,
     * if the buyer has cancelled the payment during the checkout process.
     * It should redirect the buyer into a "The payment has been cancelled" page.
     *
     */
    public function checkout_cancel()
    {
        try {
            $data = $this->getServerData('cancel');
        } catch (Exception $e) {
            $code = $e->getCode();
            $content = "An error has occured during the cancel call: " . $code;
            return $this->helper->httpResponse($content, $code);
        }

        return $this->helper->httpRedirect($_ENV['ODOO_BASE_URL'] . '/payment/viabill/return?reference=' . $data['transaction']);

    }

    /**
     * This function is handling the "callback" request that is invoked by the Viabill server,
     * after the checkout process has been completed.
     * You should use this function to update the status of the order, based on the callback status
     * that can APPROVED, CANCELLED or REJECTED.
     *
     */
    public function checkout_callback()
    {
        try {
            $data = $this->getServerData('callback');
        } catch (Exception $e) {
            $code = $e->getCode();
            $content = "An error has occured during the callback call: " . $code;
            return $this->helper->httpResponse($content, $code);
        }
        $payload = json_decode(file_get_contents('php://input'), true);

        $transaction_id = $payload['transaction'] ?? null;
        $order_id = $payload['orderNumber'] ?? null;
        $amount = $payload['amount'] ?? null;
        $currency = $payload['currency'] ?? null;
        $autoCapture = $data['capture'];

        $log_msg = '';

        // Set the ViaBill API Key and Secret for the shop_id
        $viabill = new Viabill();
        // Verify the callback signature
        if ($viabill->verifyCallbackSignature($payload)) {
            $callback_status = $payload['status'];
            switch ($callback_status) {
                case 'APPROVED':
                    $log_msg = 'SUCCESS: Transaction was approved';
                    $result_action = 'approve';
                    if ($autoCapture) {

                        $captureData = [
                            'id' => $transaction_id,
                            'apikey' => $viabill->helper->getAPIKey(),
                            // amount must be negative
                            'amount' => $amount <= 0 ? $amount : (-1 * abs($amount)),
                            'currency' => $currency,
                        ];

                        $capture = $viabill->captureTransaction($captureData);

                        if ($capture !== true) {
                            $log_msg = 'ERROR: Transaction was approved, but not captured';
                            $result_action = 'pending';
                        }
                    }

                    break;
                case 'CANCELLED':
                    $log_msg = 'CANCELLED: Transaction was cancelled';
                    $result_action = 'cancel';
                    break;
                case'REJECTED':
                    $log_msg = 'REJECTED: Transaction was rejected';
                    $result_action = 'reject';
                    break;
                default:
                    $error_log_msg = 'ERROR: Unknown Viabill Server Callback Status:' . $callback_status;
                    $result_action = 'cancel';
                    break;
            }

            // Based on the callback status, you need to take some action
            // For instance, you may need to change the order status and
            // send a confirmation message to the buyer
            $this->helper->resolveCheckoutCallbackAction($result_action, $transaction_id, $order_id);
        } else {
            $log_msg = 'ERROR: Failed to verify callback signature.';
        }

        if (!empty($log_msg)) {
            $this->helper->log($log_msg);
        }

        $get =  file_get_contents('http://192.168.1.51:8069/payment/viabill/return?reference=' . $transaction_id);
    }

    /**
     * TODO:
     * Get data from the Viabill server, after a call has been invoked ("success", "cancel" or "callback")
     * Note that each platform/framework will have a specific built-in function
     * to retrieve the request data in a safe and clean way. Use this instead of the following
     * "raw" method.
     *
     * @param string $task
     *
     * @return array
     */
    public function getServerData($task = null)
    {
        $data = $_REQUEST;

        return $data;
    }

}
