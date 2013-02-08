<?php

/**
 *
 * Class will hold the response from PayPal
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name Response
 * @version 1.0
 *
 */

namespace SPL\Billing\PayPal;

class Response extends \ArrayIterator
{
    /**
     * Response array
     *
     * @var array
     */
    private $response = array();

    /**
     * Constructor
     *
     * @param array $response
     */
    public function __construct($response)
    {
        if(is_array($response))
        {
            $this->response = $response;
        }

        parent::__construct($this->response);
    }

    /**
     * Getter method
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        $value = null;

        if(isset($this->response[$name]))
        {
            $value = $this->response[$name];
        }

        return $value;
    }

    /**
     * Retrieves an entry from the array
     *
     * @param string $index
     * @return mixed Null on fail or string on success
     */
    public function offsetGet($index)
    {
        $value = null;

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        if(parent::offsetExists($index))
        {
            $value = parent::offsetGet($index);
        }

        return $value;
    }
}