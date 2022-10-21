<?php

namespace App\Viabill;

use GuzzleHttp\Client;

class OutgoingRequests
{
    /**
     * API Test mode base URL
     */
    private const TEST_BASE_URL = 'https://secure.viabill.com';

    /**
     * API Live mode base URL
     */
    private const PROD_BASE_URL = 'https://secure.viabill.com';

    /**
     * @param string $endPoint
     * @param string $method
     * @param array  $data
     * @param bool   $testMode
     * @param array  $extraHeaders
     * @param bool   $manual
     *
     * @return array
     */
    public static function request(string $endPoint, string $method = 'GET', array $data = [], bool $testMode = false, array $extraHeaders = [], bool $manual = false)
    {
        $baseUrl = self::PROD_BASE_URL;
        if ($testMode) {
            $baseUrl = self::TEST_BASE_URL;
        }
        $requestUrl = self::buildRequestUrl($baseUrl, $endPoint);
        $headers = [
            'Accept' => '*/*',
            'Accept-Encoding' => 'gzip, deflate',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'Referer' => $requestUrl,
        ];

        // Merge the extra headers into the default headers
        // making sure that extra headers overwrite the defaults
        $headers = array_merge($headers, $extraHeaders);

        $client = new Client();

        if ($method == 'GET') {
            $response = $client->request(
                'GET', $requestUrl, [
                    'headers' => $headers,
                    'query' => $data
                ]
            );
        }

        if ($method == 'POST') {
            $response = $client->request(
                'POST', $requestUrl, [
                    'form_params' => $data
                ]
            );
        }

        $output = [
            'request' => [                        // Information about the request
                'url' => $requestUrl,            //         - The requested URL
                'headers' => $headers,            //         - The request headers
                'params' => $data,                //        - Any data supplied with the request
                'method' => $method,            //        - The request method
            ],
            'status' => $response->getStatusCode(),            // The HTTP status code of the response
            'response' => [                        // Information about the response
                'headers' => $response->getHeaders(),    //         - The response headers
                'body' => $response->getBody()        //        - The response body
            ],
        ];

        // Check for a 3XX redirect
        if (filter_var(
            $response->getStatusCode(), FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 300, 'max_range' => 399]]
        )
        ) {
            // If we fail to set the Referer header, the resulting checkout page from ViaBill
            // will appear, but the form will not load correctly. This is not documented, but
            // it appears to be the case. So we'll add the referer header using our original
            // request URL.
            $output['response']['headers']['Referer'] = $requestUrl;
        }

        return $output;
    }


    /**
     * @param string $endPoint
     * @param string $method
     * @param array  $data
     * @param bool   $testMode
     *
     * @return array
     */
    public static function requestWithoutRedirect(string $endPoint, string $method = 'POST', array $data = [], bool $testMode = false)
    {
        $baseUrl = self::PROD_BASE_URL;
        if ($testMode) {
            $baseUrl = self::TEST_BASE_URL;
        }
        $requestUrl = self::buildRequestUrl($baseUrl, $endPoint);
        $headers = [
            'Accept' => '*/*',
            'Accept-Encoding' => 'gzip, deflate',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'Referer' => $requestUrl,
        ];


        $client = new Client();

        $response = $client->request(
            'POST', $requestUrl, [
                'allow_redirects' => false,
                'headers' => $headers,
                'form_params' => $data
            ]
        );

        $output = [
            'request' => [                        // Information about the request
                'url' => $requestUrl,            //         - The requested URL
                'headers' => $headers,            //         - The request headers
                'params' => $data,                //        - Any data supplied with the request
                'method' => $method,            //        - The request method
            ],
            'status' => $response->getStatusCode(),            // The HTTP status code of the response
            'response' => [                        // Information about the response
                'headers' => $response->getHeaders(),    //         - The response headers
                'body' => $response->getBody()        //        - The response body
            ],
        ];

        // Check for a 3XX redirect
        if (filter_var(
            $response->getStatusCode(), FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 300, 'max_range' => 399]]
        )
        ) {
            // If we fail to set the Referer header, the resulting checkout page from ViaBill
            // will appear, but the form will not load correctly. This is not documented, but
            // it appears to be the case. So we'll add the referer header using our original
            // request URL.
            $output['response']['headers']['Referer'] = $requestUrl;
        }

        return $output;
    }


    /**
     * @param string $baseUrl
     * @param string $endPoint
     *
     * @return string
     */
    protected static function buildRequestUrl(string $baseUrl = '', string $endPoint = ''): string
    {
        $baseUrl = rtrim($baseUrl, '/');
        $endPoint = ltrim($endPoint, '/');
        return $baseUrl . '/' . $endPoint;
    }    

}
