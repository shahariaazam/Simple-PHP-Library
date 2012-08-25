<?php

/**
 *
 * Class that creates Items for the ExpressCheckout class
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name Items
 * @version 1.0
 *
 */

namespace SPL\Billing\PayPal\Items;

class Items
{
    /**
     * Array of Item objects
     *
     * @var array
     */
    protected $items = array();

    /**
     * Total price of items
     *
     * @var number
     */
    private $price = 0;

    /**
     * Currency
     *
     * @var string
     */
    public $currency;

    /**
     * Category
     *
     * @var string
     */
    public $category;

    /**
     * Creates a new Item object and returns it
     *
     * @param void
     * @return Item
     */
    public function newItem()
    {
        $item = new Item();

        // Creating the item
        $this->items[] = $item;

        // Setting the observer
        $item->setObserver($this);

        return $item;
    }

    /**
     * Retrieves the items price
     *
     * @param void
     * @return number
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Updates the price of the items
     *
     * @param Item $item
     * @param number $number
     * @return void
     */
    public function updatePrice(Item $item)
    {
        $this->price += $item->price;
    }

    /**
     * Retrieves the array of items
     *
     * @param void
     * @return void
     */
    public function getItems()
    {
        return $this->items;
    }
}