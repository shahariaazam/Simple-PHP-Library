<?php

/**
 *
 * The object created with this class identifies a user
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name User
 * @version 1.0
 * 
 */

namespace SPL\User;

class User implements UserInterface
{
    /**
     * User information
     *
     * @var array
     */
    protected $info = array();

    /**
     * Constructor
     *
     * @param array $info
     * @return void
     */
    public function __construct($info = array())
    {
        if(is_array($info))
        {
            $this->setInfo($info);
        }
    }

    /**
     * Sets some user info
     *
     * @param array $info
     * @return void
     */
    public function setInfo($info)
    {
        if(is_array($info))
        {
            $this->info = $info;
        }
    }

    /**
     * Getter method
     *
     * @param string $name
     * @return mixed Null if no value found or the value of the info
     */
    public function __get($name)
    {
        $value = null;

        if(isset($this->info[$name]))
        {
            $value = $this->info[$name];
        }

        return $value;
    }

    /**
     * Setter method
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        $this->info[$name] = $value;
    }
}