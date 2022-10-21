<?php

namespace App\Viabill;

use App\Viabill\ViabillHelper;
use App\Viabill\Constants\ISOCodes;
use App\Viabill\Constants\ViaBillServices;
use App\Viabill\Exceptions\ViabillInvalidValueException;
use App\Viabill\Exceptions\ViabillMissingKeyOrSecretException;
use App\Viabill\Exceptions\ViabillMissingSignatureException;
use App\Viabill\Exceptions\ViabillRequestException;

class Viabill
{
    /**
     * The current API protocol version
     */
    private const API_PROTOCOL = '3.0';

    /**     
     * @var bool
     */
    protected $testMode;

    /**     
     * @var string
     */
    protected $apiSecret;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var ViabillHelper
     */
    public $helper;

    /**
     * ViaBill constructor
     */
    public function __construct()
    {        
        $this->helper = new ViabillHelper();

        $this->testMode = $this->helper->getTestMode();
        $this->apiKey = $this->helper->getAPIKey();
        $this->apiSecret = $this->helper->getSecretKey();
    }

    /**
     * @throws ViabillRequestException
     */
    public function checkout(array $data = [], array $headers = [], $shop = null) : array
    {
        $redirect_url = null;

        $data['protocol'] = self::API_PROTOCOL;

        if (!empty($customerInfo)) {
            $data['customParams'] = $customerInfo;
        }

        $response = $this->getRequestData($data, [], true, 'checkout', false);

        $status = intval($response['status']);
        if (($status == 301) || ($status == 302)) {
            $redirect_url = $response['response']['headers']['Location'][0];
        }

        if (empty($redirect_url)) {
            return [
                'error' => 'Request already made'
            ];
        }

        $message = $this->getApiEndPointMessage($status, 'checkout');

        return [           
            'redirect_url' => $redirect_url,
            'status' => $status,
            'message' => $message,
            'input_data' => $data
        ];
    }

    /**
     * @param  array $data
     * @param  array $headers
     * @param  bool  $verbose
     * @return array|bool
     * @throws ViaBillInvalidValueException
     * @throws ViaBillMissingKeyOrSecretException
     * @throws ViaBillMissingSignatureException
     * @throws ViaBillRequestException
     */
    public function captureTransaction(array $data = [], array $headers = [], bool $verbose = false)
    {
        return $this->getRequestDataTransaction($data, $headers, $verbose, 'capture_transaction');
    }

    /**
     * @throws ViabillInvalidValueException
     * @throws ViabillRequestException
     */
    public function refundTransaction(array $data = [], array $headers = [], bool $verbose = false)
    {
        return $this->getRequestDataTransaction($data, $headers, $verbose, 'refund_transaction');
    }

    /**
     * @throws ViabillInvalidValueException
     * @throws ViabillRequestException
     */
    public function cancelTransaction(array $data = [], array $headers = [], bool $verbose = false)
    {
        return $this->getRequestDataTransaction($data, $headers, $verbose, 'cancel_transaction');
    }

    /**
     * @throws ViabillInvalidValueException
     * @throws ViabillRequestException
     */
    public function status(array $data = [], array $headers = [], bool $verbose = false)
    {
        return $this->getRequestDataTransaction($data, $headers, $verbose, 'transaction_status');
    }

    /**
     * @param array $data
     * @param array $headers
     * @param bool  $verbose
     *
     * @return array|bool
     * @throws ViabillRequestException
     */
    public function myViabill(array $data = [], array $headers = [], bool $verbose = false)
    {        
        $return = [
            'error' => null,
            'url' => null
        ];

        $response_str = $this->getRequestData($data, $headers, $verbose, 'myviabill');
			
        if (empty($response_str)) {
            $return['error'] = "myViabill returned an empty response!";            
            return false;
        } else {
            $response = json_decode($response_str, true);
            if (isset($response['errors'])) {
                $return['error'] = $response['errors'][0]['error'];            
            } else if (isset($response['url'])) {
                $return['url'] = $response['url'];                
            }
        }        

        return $return;
    }

    /**
     * @param array $data
     * @param array $headers
     * @param bool  $verbose
     *
     * @return array|bool
     * @throws ViabillRequestException
     */
    public function notifications(array $data = [], array $headers = [], bool $verbose = false)
    {
        $return = [
            'error' => null,
            'messages' => null
        ];
        
        $response_str = $this->getRequestData($data, $headers, $verbose, 'notifications');

        if (empty($response_str)) {
            $return['error'] = "notifications returned an empty response!";            
            return false;
        } else {
            $response = json_decode($response_str, true);
            if (isset($response['errors'])) {
                $return['error'] = $response['errors'][0]['error'];            
            } else if (isset($response['messages'])) {
                $return['messages'] = $response['messages'];                
            }
        }        

        return $return;
    }

    /**
     * @param array  $data
     * @param array  $headers
     * @param bool   $verbose
     * @param string $type
     * @param bool   $force
     *
     * @return array|bool
     * @throws ViabillRequestException
     * @throws ViabillInvalidValueException
     */
    private function getRequestData(
        array  $data = [],
        array  $headers = [],
        bool   $verbose = false,
        string $type = '',
        bool   $force = false
    ) {
        $request = $this->getEndPointData($type, $data);

        if ($request) {
            if ($type == 'checkout') {
                $response = OutgoingRequests::requestWithoutRedirect($request['endpoint'], $request['method'], $request['data'], $this->testMode);
            } else {
                $response = OutgoingRequests::request($request['endpoint'], $request['method'], $request['data'], $this->testMode, $headers, false);
            }

            return $verbose ? $response : $response['response']['body'];
        }
        return false;
    }


    /**
     * @param string $type
     * @param array  $data
     *
     * @return array|bool
     * @throws ViabillRequestException
     * @throws ViabillInvalidValueException
     */
    private function getEndPointData(string $endPoint = '', array $data = [])
    {
        $ed = ViaBillServices::getApiEndPoint($endPoint);
        if (!empty($ed)) {
            $endPoint = $ed['endpoint'];
            $method = $ed['method'];
            $requestData = [];
            foreach ($ed['required_fields'] as $field) {
                $isTest = ($field === 'test');
                // Check for signature/md5check fields
                if (array_key_exists($field, $ed)) {
                    $format = $ed[$field];
                    // Parse the format to generate a signature if one is required
                    try {
                        $format = $this->parseFormat($format, $data);
                    } catch (ViabillInvalidValueException $e) {
                    } catch (ViabillMissingKeyOrSecretException $e) {
                    } catch (ViabillMissingSignatureException $e) {
                        return false;
                    }
                    $requestData[$field] = md5($format);
                    // Process the remaining required fields
                } elseif (array_key_exists($field, $data)) {
                    // Make sure the test field is set to true if test mode is enabled globally
                    // or for this specific request
                    if ($isTest) {
                        $requestData[$field] = $data[$field];
                    } elseif ($field === 'country') {
                        $requestData[$field] = ($this->validISO($data[$field]) ? strtoupper(trim($data[$field])) : $data[$field]);
                    } else {
                        $requestData[$field] = $data[$field];
                    }

                } elseif ($field === 'protocol') {
                    $requestData[$field] = self::API_PROTOCOL;
                } elseif ($isTest) {
                    $requestData[$field] = $this->testMode;
                } else {
                    throw new ViabillRequestException('Data is missing required field; ' . $field);
                }
            }

            foreach ($ed['optional_fields'] as $field) {
                if (array_key_exists($field, $data)) {
                    $requestData[$field] = $data[$field];
                }
            }
            $requestData = $this->prepareData($requestData);

            return [
                'endpoint' => $endPoint,
                'method' => $method,
                'data' => $requestData
            ];
        }
        return false;
    }

    /**
     * ViaBill::verifyCallbackSignature(array $data, [string $format, boolean $silent])
     * Verifies the signature contained in the data sent to a checkout callback_url
     *
     * @param  array  $data   An associative array containing the data that was sent to the checkout callback_url
     * @param  string $format An optional custom format to use during verification of the signature
     * @param  bool   $silent If true, returns false on signature mismatch instead of throwing an exception
     * @return bool             Returns true if the calculated signature matches the signature contained in the data or false if it does not match.
     * @throws ViaBillException
     * @throws ViaBillInvalidValueException
     * @throws ViaBillMissingKeyOrSecretException
     * @throws ViaBillMissingSignatureException
     */
    public function verifyCallbackSignature(array $data, string $format = '', bool $silent = true): bool
    {
        $format = trim($format);
        // Set the default format if no optional format is specified.
        if (empty($format)) {
            $format = '{transaction}#{orderNumber}#{amount}#{currency}#{status}#{time}#{secret}';
        }

        if (!array_key_exists('signature', $data)) {
            throw new ViaBillMissingSignatureException(__METHOD__ . ': Callback data is missing a "signature" key.');
        }
        if ($this->apiSecret === null) {
            throw new ViaBillMissingKeyOrSecretException('You must set the apiSecret with ' . __CLASS__ . '::apiSecret() before calling ' . __METHOD__ . '().');
        }
        // Retrieve the expected signature from the data array
        $sig = $data['signature'];
        // Remove the expected signature from the data array
        unset($data['signature']);
        // Parse the format and data into a populated string
        $format = $this->parseFormat($format, $data);
        // Calculate the MD5 checksum of the string
        $calculated = md5($format);
        if ($calculated === $sig) {
            return true;
        }
        if ($silent) {
            return false;
        }
        throw new ViaBillInvalidValueException(__METHOD__ . ':Expected signature [' . $sig . '] but got signature [' . $calculated . '].');
    }

    /**
     * @param  string $country A two character country code to check against the ISO codes array.
     * @param  bool   $silent  If true, returns false if country code is not a valid ISO 3166-1 alpha 2 code, instead of throwing an exception
     * @return bool                           Returns true if the specified country code is a valid ISO 3166-1 alpha 2 code, or false if not.
     * @throws ViabillInvalidValueException   When specified value is not a valid ISO 3166-1 alpha 2 country code and $silent=false
     */
    public function validISO($country = '', $silent = true): bool
    {
        $country = strtoupper(trim($country));
        // Return false if country code is too long, or too short
        if (strlen($country) !== 2) {
            return false;
        }

        if (in_array($country, ISOCodes::ISO_CODES, false)) {
            return true;
        }
        if ($silent) {
            return false;
        }
        $message = sprintf('%s: Value %s is not a valid ISO 3166-1 alpha 2 Country Code.', __METHOD__, $country);
        throw new ViabillInvalidValueException($message);
    }

    /**
     * ViaBill::parseFormat(string $format, array &$data)
     * Parses a format string and data array into a string that can be used to generate a signature
     *
     * @param  $format
     * @param  $data
     * @return mixed
     * @throws ViabillInvalidValueException
     * @throws ViabillMissingKeyOrSecretException
     * @throws ViabillMissingSignatureException
     */
    protected function parseFormat($format, &$data)
    {
        preg_match_all('/(?:\{([^\{\}#]+)\}#?)/', $format, $formatFields);
        if (empty($formatFields)) {
            throw new ViabillInvalidValueException(__METHOD__ . ': Invalid format string - Format does not contain any fields.');
        }
        foreach ($formatFields[1] as $key) {
            if (array_key_exists($key, $data)) {
                $val = $data[$key];
                if ($key === 'country') {
                    $val = ($this->validISO($val) ? strtoupper(trim($val)) : $val);
                }
                $format = str_replace('{' . $key . '}', $val, $format);
            } elseif ($key === 'secret') {
                if ($this->apiSecret === null) {
                    throw new ViabillMissingKeyOrSecretException('You must set the apiSecret with ' . __CLASS__ . '::apiSecret() before calling ' . __METHOD__ . '().');
                }
                $format = str_replace('{' . $key . '}', $this->apiSecret, $format);
            } elseif (in_array($key, ['key', 'apikey', 'apiKey'])) {
                if ($this->apiKey === null) {
                    throw new ViabillMissingKeyOrSecretException('You must set the apiKey with ' . __CLASS__ . '::apiSecret() before calling ' . __METHOD__ . '().');
                }
                $format = str_replace('{' . $key . '}', $this->apiKey, $format);

            } elseif ($key === 'protocol') {
                $format = str_replace('{' . $key . '}', self::API_PROTOCOL, $format);
            } elseif ($key === 'test') {
                $format = str_replace('{' . $key . '}', $this->testMode, $format);
            } else {
                throw new ViabillMissingSignatureException('Data is missing a required signature field; ' . $key);
            }
        }
        return trim($format);
    }

    /**
     * ViaBill::prepareData(&$input)
     * Performs an in-place conversion on boolean values inside an array, or string into their string-equivalent
     * values (i.e. boolean true to 'true') without duplicating the original array or string.
     *
     * @param  mixed &$input An array or string containing boolean values
     * @return mixed                        Works in-place, but can return the converted input to a new variable
     */
    protected function prepareData(&$input)
    {
        $checkVal = static function ($value) {
            if (is_bool($value)) {
                $value = ($value ? 'true' : 'false');
            }
            return $value;
        };
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                if (is_array($value)) {
                    $input[$key] = $this->prepareData($value);
                } else {
                    $input[$key] = $checkVal($value);
                }
            }
        } else {
            $input = $checkVal($input);
        }
        return $input;
    }

    public function getApiEndPointMessage($status, $endPoint)
    {
        $message = '';
        $ed = ViaBillServices::getApiEndPoint($endPoint);
        if (!empty($ed)) {
            if (isset($ed['status_codes'])) {
                $status_codes = $ed['status_codes'];
                if (isset($status_codes[(int)$status])) {
                    $message = $status_codes[(int)$status];
                }
            }
        }
        return $message;
    }

    /**
     * @param  array  $data
     * @param  array  $headers
     * @param  bool   $verbose
     * @param  string $type
     * @return array|bool
     * @throws ViaBillInvalidValueException
     * @throws ViaBillRequestException
     */
    private function getRequestDataTransaction(
        array $data = [],
        array $headers = [],
        bool $verbose = false,
        string $type = ''
    ) {
        $force = $this->isForceRequest($data);
        $response = $this->getRequestData($data, $headers, true, $type, $force);

        if ($response && !$verbose) {
            if ($this->checkResponseStatus($response)) {
                return true;
            }
            return $response['response']['body'];
        }
        return $response;
    }

    /**
     * @param  array $response
     * @return bool
     */
    private function checkResponseStatus(array $response): bool
    {
        if (filter_var(
            $response['status'], FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 200, 'max_range' => 299]]
        )
        ) {
            return true;
        }
        return false;
    }

    /**
     * @param  array $data
     * @return bool
     */
    private function isForceRequest(array $data): bool
    {
        return !array_key_exists('apikey', $data);
    }
}