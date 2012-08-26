<?php

/**
 *
 * Class for PayPal's ExpressCheckout
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name ExpressCheckout
 * @version 1.6.1
 *
 * ----------------------------------------------------------
 * ERROR CODES
 * ----------------------------------------------------------
 *
 * 10 - The required parameters for the SetExpressCheckout method where not present
 * 20 - Invalid currency
 * 30 - PayPal request failed
 * 40 - The required parameters for the GetExpressCheckoutDetails method where not present
 * 50 - The required parameters for the DoExpressCheckoutPayment method where not present
 *
 * ----------------------------------------------------------
 *
 */

namespace SPL\Billing\PayPal;

use SPL\Billing\Exception;

class ExpressCheckout extends PayPal
{
    /**
     * Internal log
     *
     * @var string
     */
    protected $log = '';

    /**
     * Errors array
     *
     * @var array
     */
    protected $errors = array();

    /**
     * Options array
     *
     * @var array
     */
    protected $options = array();

    /**
     * Selected environment
     *
     * @var string
     */
    protected $environment;

    /**
     * Selected platform
     *
     * @var string
     */
    protected $platform;

    /**
     * Servers for the checkout
     *
     * @var array
     */
    protected $servers = array();

    /**
     * API version
     *
     * @var string
     */
    protected $version = '92.0';

    /**
     * Used to initialize the options and create the object
     *
     * @param array $options
     * @param string $environment
     * @param string $platform
     * @return ExpressCheckout
     * @throws SPL\Billing\Exception\InvalidArgumentException
     */
    public function __construct(array $options, $environment = self::ENV_TESTING, $platform = self::PLATFORM_DESKTOP)
    {
        // Checking the type of the options param
        if(!is_array($options))
        {
            throw new Exception\InvalidArgumentException('The $options parameter must be an array.');
        }

        // Checking the environment param to see if it's valid
        if(!in_array($environment, array(self::ENV_PRODUCTION, self::ENV_TESTING)))
        {
            throw new Exception\InvalidArgumentException('The $environment parameter can only be "PayPal::ENV_PRODUCTION" or "PayPal::ENV_TESTING".');
        }

        // Checking the $platform param to see if it's valid
        if(!in_array($platform, array(self::PLATFORM_DESKTOP, self::PLATFORM_MOBILE)))
        {
            throw new Exception\InvalidArgumentException('The $platform parameter can only be "PayPal::PLATFORM_DESKTOP" or "PayPal::PLATFORM_MOBILE".');
        }

        // Class options
        $this->options['debug']     = false;
        $this->options['mail']      = '';

        // API options
        $this->options['username']  = '';
        $this->options['password']  = '';
        $this->options['signature'] = '';

        // Overwrite default options
        if(count($options) > 0)
        {
            $this->options = array_merge($this->options, $options);
        }

        // Initializing the environment
        $this->environment = $environment;

        // Initializing the platform
        $this->platform = $platform;

        // Logging
        $this->log('log', '<strong>Parameters:</strong> <pre>' . print_r(func_get_args(), 1) . '</pre>', __METHOD__);

        // Initializing the servers
        $this->initServers();
    }

    /**
     * Initializes the servers used for each environment
     *
     * @param void
     * @return void
     */
    protected function initServers()
    {
        /**
         * ------------------------------------
         * Servers for requests
         * ------------------------------------
         *
         */
        $this->servers['request'] = array(

            self::ENV_PRODUCTION => 'https://api-3t.paypal.com/nvp',
            self::ENV_TESTING    => 'https://api-3t.sandbox.paypal.com/nvp'

        );

        /**
         * -----------------------------------------
         * Servers for redirects
         * -----------------------------------------
         *
         */
        if($this->platform === self::PLATFORM_DESKTOP)
        {
            $this->servers['redirect'] = array(

                self::ENV_PRODUCTION => 'https://www.paypal.com/webscr?cmd=_express-checkout&token={token}',
                self::ENV_TESTING    => 'https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token={token}'

            );
        }
        else if($this->platform === self::PLATFORM_MOBILE)
        {
            $this->servers['redirect'] = array(

                self::ENV_PRODUCTION => 'https://www.paypal.com/webscr?cmd=_express-checkout-mobile&token={token}',
                self::ENV_TESTING    => 'https://www.sandbox.paypal.com/webscr?cmd=_express-checkout-mobile&token={token}'

            );
        }

        // Logging
        $this->log('log', '<strong>Servers:</strong> <pre>' . print_r($this->servers, 1) . '</pre>', __METHOD__);
    }

    /**
     * Checks if a given currency is supported by PayPal
     *
     * @param string $currency
     * @return boolean
     */
    public function isCurrencySupported($currency)
    {
        // Check var
        $supported = false;

        // Checking if the currency is of the correct type
        if(is_string($currency))
        {
            // Supported currencies
            $currencies = array(
                'AUD' => true,
                'CAD' => true,
                'CZK' => true,
                'DKK' => true,
                'EUR' => true,
                'HKD' => true,
                'HUF' => true,
                'JPY' => true,
                'NOK' => true,
                'NZD' => true,
                'PLN' => true,
                'GBP' => true,
                'SGD' => true,
                'SEK' => true,
                'CHF' => true,
                'USD' => true,
            );

            // Checking if the currency is supported
            if(isset($currencies[$currency]))
            {
                $supported = true;
            }
        }

        return $supported;
    }

    /**
     * Gets the PayPal ExpressCheckout button
     *
     * @param void
     * @return string
     */
    public function getButton()
    {
        return self::getExpressCheckoutButton();
    }

    /**
     * Returns the URL to PayPal
     *
     * @param Response $response
     * @return string
     */
    public function getUrl(Response $response)
    {
        $url = '#';

        if($response->TOKEN !== null)
        {
            $url = $this->getEndpointUrl('redirect', $response->TOKEN);
        }

        return $url;
    }

    /**
     * Returns an URL for the request depending on the $type
     *
     * @param string $type
     * @param string $token [ optional ]
     * @return string
     */
    protected function getEndpointUrl($type, $token = '')
    {
        // Getting the URL
        $url = $this->servers[$type][$this->environment];

        // Checking if the token is empty or not and replacing
        if(!empty($token))
        {
            $url = str_replace('{token}', $token, $url);
        }

        // Returning the URL
        return $url;
    }

    /**
     * Returns the token provided by PayPal
     *
     * @param void
     * @return string
     */
    protected function getToken()
    {
        $token = '';

        if(isset($_GET['token']))
        {
            $token = $_GET['token'];
        }

        return $token;
    }

    /**
     * Returns the payerId provided by PayPal
     *
     * @param void
     * @return string
     */
    protected function getPayerId()
    {
        $token = '';

        if(isset($_GET['PayerID']))
        {
            $token = $_GET['PayerID'];
        }

        return $token;
    }

    /**
     * Used to initiate the payment flow and redirect to PayPal if the operation was successful
     *
     * @param Items\Items $items
     * @param string $returnUrl
     * @param string $cancelUrl
     * @return mixed Response object on success or false on fail
     * @throws SPL\Billing\Exception\RuntimeException
     */
    public function SetExpressCheckout(Items\Items $items, $returnUrl, $cancelUrl)
    {
        // Status
        $result = true;

        // Local log
        $log = '';

        // Price
        $price = $items->getPrice();

        // Checking the required vars
        if(!empty($this->options['username'])
                && !empty($this->options['password'])
                && !empty($this->options['signature'])
                && is_numeric($price)
                && !empty($items->currency)
                && !empty($items->category) && in_array($items->category, array('Digital', 'Physical'))
                && !empty($returnUrl)
                && !empty($cancelUrl)
            )
        {
            // Checking if the currency is supported by PayPal
            if($this->isCurrencySupported($items->currency))
            {
                // Encoding
                $returnUrl = urlencode($returnUrl);
                $cancelUrl = urlencode($cancelUrl);
                $price     = urlencode($price);

                // Request string
                $request = 'METHOD=SetExpressCheckout'
                        . '&VERSION=' . $this->version
                        . '&USER=' . $this->options['username']
                        . '&PWD=' . $this->options['password']
                        . '&SIGNATURE=' . $this->options['signature']
                        . '&PAYMENTREQUEST_0_ITEMCATEGORY=' . $items->category
                        . '&PAYMENTREQUEST_0_CURRENCYCODE=' . $items->currency
                        . '&PAYMENTREQUEST_0_AMT=' . $price
                        . '&PAYMENTREQUEST_0_PAYMENTACTION=Sale'
                        . '&RETURNURL=' . $returnUrl
                        . '&CANCELURL=' . $cancelUrl;

                // Building the rest of the request using the items object
                foreach($items->getItems() as $index => $item)
                {
                    $request .= '&L_PAYMENTREQUEST_0_NAME' . $index . '=' . urlencode($item->name)
                            . '&L_PAYMENTREQUEST_0_DESC' . $index . '=' . urlencode($item->desc)
                            . '&L_PAYMENTREQUEST_0_AMT' . $index . '=' . urlencode($item->price)
                            . '&L_PAYMENTREQUEST_0_QTY' . $index . '=' . urlencode($item->quantity);
                }

                // More request options depending on platform
                if($this->platform === self::PLATFORM_MOBILE)
                {
                    $request .= '&LANDINGPAGE=Login';
                }

                // Request URL
                $url = $this->getEndpointUrl('request');

                // Getting the response
                $response = $this->post($url, $request);

                // Logging
                $log .= '<strong>Request URL:</strong> ' . $url . '<br /><br />';
                $log .= '<strong>Response:</strong> <pre>' . print_r($response, 1) . '</pre><br /><br />';

                try{

                    // Checking the response
                    $isResponseOk = $this->checkResponse($response);

                } catch (Exception\InvalidArgumentException $e) {

                    throw new Exception\RuntimeException('The response could not be checked', 0, $e);
                }

                // Verifying
                if($isResponseOk === true)
                {
                    $result = new Response($response);
                }
                else
                {
                    $result = false;
                }
            }
            else
            {
                $result = false;

                // Logging
                $this->log('error', 'The provided currency (' . $items->currency . ') is not supported by PayPal', 20);
            }
        }
        else
        {
            $result = false;

            // Logging
            $this->log('error', 'The required parameters for the SetExpressCheckout method where not present', 10);
        }

        // Logging
        $log .= '<strong>Parameters:</strong> <pre>' . print_r(func_get_args(), 1) . '</pre><br /><br />';
        $this->log('log', $log, __METHOD__);

        // Returning the status
        return $result;
    }

    /**
     * Used to obtain details about an Express Checkout transaction
     *
     * @param void
     * @return mixed Response object on success or false on fail
     * @throws SPL\Billing\Exception\RuntimeException
     */
    public function GetExpressCheckoutDetails()
    {
        // Status
        $result = true;

        // Local log
        $log = '';

        // Token
        $token = $this->getToken();

        // Checking the required vars
        if(!empty($this->options['username'])
                && !empty($this->options['password'])
                && !empty($this->options['signature'])
                && !empty($token)
            )
        {
            // Request string
            $request = 'METHOD=GetExpressCheckoutDetails'
                    . '&VERSION=' . $this->version
                    . '&USER=' . $this->options['username']
                    . '&PWD=' . $this->options['password']
                    . '&SIGNATURE=' . $this->options['signature']
                    . '&TOKEN=' . $token;

            // Request URL
            $url = $this->getEndpointUrl('request');

            // Getting the response
            $response = $this->post($url, $request);

            // Logging
            $log .= '<strong>Request URL:</strong> ' . $url . '<br /><br />';
            $log .= '<strong>Response:</strong> <pre>' . print_r($response, 1) . '</pre><br /><br />';

            try{

                // Checking the response
                $isResponseOk = $this->checkResponse($response);

            } catch (Exception\InvalidArgumentException $e) {

                throw new Exception\RuntimeException('The response could not be checked', 0, $e);
            }

            // Verifying
            if($isResponseOk === true)
            {
                $result = new Response($response);
            }
            else
            {
                $result = false;
            }
        }
        else
        {
            $result = false;

            // Logging
            $this->log('error', 'The required parameters for the GetExpressCheckoutDetails method where not present', 40);
        }

        // Logging
        $this->log('log', $log, __METHOD__);

        // Returning the status
        return $result;
    }

    /**
     * Used to complete an Express Checkout transaction
     *
     * @param Items\Items $items
     * @return mixed Response object on success or false on fail
     * @throws SPL\Billing\Exception\RuntimeException
     */
    public function DoExpressCheckoutPayment(Items\Items $items)
    {
        // Status
        $result = true;

        // Local log
        $log = '';

        // Price
        $price = $items->getPrice();

        // Token
        $token = $this->getToken();

        // PayerId
        $payerId = $this->getPayerId();

        // Checking the required vars
        if(!empty($this->options['username'])
                && !empty($this->options['password'])
                && !empty($this->options['signature'])
                && is_numeric($price)
                && !empty($items->currency)
                && !empty($token)
                && !empty($payerId)
            )
        {
            // Checking if the currency is supported by PayPal
            if($this->isCurrencySupported($items->currency))
            {
                // Encoding
                $price = urlencode($price);

                // Request string
                $request = 'METHOD=DoExpressCheckoutPayment'
                        . '&VERSION=' . $this->version
                        . '&USER=' . $this->options['username']
                        . '&PWD=' . $this->options['password']
                        . '&SIGNATURE=' . $this->options['signature']
                        . '&TOKEN=' . $token
                        . '&PAYERID=' . $payerId
                        . '&PAYMENTREQUEST_0_AMT=' . $price
                        . '&PAYMENTREQUEST_0_CURRENCYCODE=' . $items->currency
                        . '&PAYMENTREQUEST_0_PAYMENTACTION=Sale';

                // Request URL
                $url = $this->getEndpointUrl('request');

                // Getting the response
                $response = $this->post($url, $request);

                // Logging
                $log .= '<strong>Request URL:</strong> ' . $url . '<br /><br />';
                $log .= '<strong>Response:</strong> <pre>' . print_r($response, 1) . '</pre><br /><br />';

                try{

                    // Checking the response
                    $isResponseOk = $this->checkResponse($response);

                } catch (Exception\InvalidArgumentException $e) {

                    throw new Exception\RuntimeException('The response could not be checked', 0, $e);
                }

                // Verifying
                if($isResponseOk === true)
                {
                    $result = new Response($response);
                }
                else
                {
                    $result = false;
                }
            }
            else
            {
                $result = false;

                // Logging
                $this->log('error', 'The provided currency (' . $items->currency . ') is not supported by PayPal', 20);
            }
        }
        else
        {
            $result = false;

            // Logging
            $this->log('error', 'The required parameters for the DoExpressCheckoutPayment method where not present', 50);
        }

        // Logging
        $this->log('log', $log, __METHOD__);

        // Returning the status
        return $result;
    }

    /**
     * Sends a POST to a specified URL
     *
     * @param string $url
     * @param string $query_string
     * @return string
     */
    protected function post($url, $query_string)
    {
        // Initializing the cURL session
        $ch = curl_init();

        // Setting the URL
        curl_setopt($ch, CURLOPT_URL, $url);

        // Setting the request type
        curl_setopt($ch, CURLOPT_POST, true);

        // Setting the post fields
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);

        // Other options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        // Executing
        $response = curl_exec($ch);

        // close cURL resource, and free up system resources
        curl_close($ch);

        // Returning the response
        return $this->processResponse($response);
    }

    /**
     * Processes a response from PayPal
     *
     * @param string $response
     * @return array
     */
    protected function processResponse($response)
    {
        // Result var
        $result = array();

        // Decoding and exploding by "&"
        $response = explode('&', urldecode($response));

        // Going through the response an building a key => value assoc
        foreach($response as $keyvalue)
        {
            // Separating the key from the value
            $kv = explode('=', $keyvalue);

            // Storing
            $result[$kv[0]] = $kv[1];
        }

        return $result;
    }

    /**
     * Checks a response for errors
     *
     * @param array $response
     * @return boolean
     * @throws SPL\Billing\Exception\InvalidArgumentException
     */
    protected function checkResponse($response)
    {
        $status = true;

        // Checking if the provided param is an array
        if(is_array($response))
        {
            // Checking the ACK
            if($response['ACK'] !== 'Success')
            {
                $status = false;

                $this->log('error', 'The PayPal request failed with code ' . $response['L_ERRORCODE0'] . ' and message "' . $response['L_LONGMESSAGE0'] . '"', 30);
            }
        }
        else
        {
            throw new Exception\InvalidArgumentException('The checkResponse method requires response array.');
        }

        return $status;
    }

    /**
     * Adds a message to the Log
     *
     * @param string $type
     * @param string $message
     * @param mixed $identifier
     * @return void
     */
    protected function log($type = 'log', $message = '', $identifier = '')
    {
        if($this->options['debug'] === true)
        {
            switch($type)
            {
                case 'log':

                    $this->log .= '<strong>' . $identifier . '</strong><br />';
                    $this->log .= $message;
                    $this->log .= '<br /><br /><hr>';

                    break;

                case 'error':

                    $this->errors[$identifier] = $message;

                    break;

                default:
                    break;
            }
        }
    }

    public function __destruct()
    {
        if($this->options['debug'] === true)
        {
            // Logging the errors
            $this->log('log', '<strong>ERRORS:</strong> <pre>' . print_r($this->errors, 1) . '</pre>', __METHOD__);

            $to      = $this->options['mail'];
            $subject = '[DEBUG] ' . $_SERVER['SERVER_NAME'] . ' ' . __CLASS__ . ' Class';
            $message = &$this->log;
            $headers = 'MIME-Version: 1.0' . "\r\n" . 'Content-type: text/html; charset=utf-8' . "\r\n";

            // Sending the mail
            mail($to, $subject, $message, $headers);
        }
    }
}