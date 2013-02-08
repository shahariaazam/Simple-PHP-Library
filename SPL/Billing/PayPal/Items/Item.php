<?php

/**
 *
 * Item class
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name Item
 * @version 1.0
 *
 */

namespace SPL\Billing\PayPal\Items;

class Item
{
    private $name;
    private $desc;
    private $price;
    private $quantity;

    /**
     * Items object
     *
     * @var \SPL\Billing\PayPal\Items\Items
     */
    private $observer;

    /**
     * Getter method
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->$name;
    }

    /**
     * Sets the items object
     *
     * @param \SPL\Billing\PayPal\Items\Items $items
     * @return \SPL\Billing\PayPal\Items\Item
     */
    public function setObserver(Items $items)
    {
        $this->observer = $items;

        return $this;
    }

    /**
     * Sets the name of the item
     *
     * @param string $string
     * @return object
     */
    public function setName($string)
    {
        $string = trim($string);

        if(!empty($string) && is_string($string))
        {
            $this->name = $string;
        }

        return $this;
    }

    /**
     * Sets the description of the item
     *
     * @param string $string
     * @return object
     */
    public function setDesc($string)
    {
        $string = trim($string);

        if(!empty($string) && is_string($string))
        {
            $this->desc = $string;
        }

        return $this;
    }

    /**
     * Sets the price of the item
     *
     * @param number $number
     * @return object
     */
    public function setPrice($number)
    {
        if(is_numeric($number))
        {
            $this->price = $number;

            // Telling the observer to increase the price
            $this->observer->updatePrice($this);
        }

        return $this;
    }

    /**
     * Sets the quantity of the item
     *
     * @param number $number
     * @return object
     */
    public function setQuantity($number)
    {
        if(is_int($number))
        {
            $this->quantity = $number;
        }

        return $this;
    }
}