<?php
/**
 * 
 * Simple cart class
 * 
 * @author Brian
 * @link http://brian.serveblog.net
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 * 
 * @name Cart
 * @version 1.3
 * 
 */

class Cart
{
    /**
     * Instance identifier
     *
     * @var object
     */
    private static $instance;

    /**
     *
     * Items in cart
     *
     * @var array
     * @example $items[1] = 'Item title';
     */
    private $items = array();

    /**
     *
     * Determins if the cart should empty at __destruct()
     *
     * @var boolean
     */
    private $doEmpty = false;

    /**
     * The method is the constructor for this class.
     * The constructor is private because only the Singleton pattern should be used when creating the object
     *
     * @param void
     * @return void
     */
    private function __construct()
    {

    }

    /**
     * Singleton initiator
     *
     * @param void
     * @return object
     */
    public static function init()
    {
        // ==== Checking if a session exists ==== //
        if(session_id() == '')
        {
            trigger_error('The cart class needs and active session in order to work.', E_USER_WARNING);
        }

        // ==== Checking if we have a cart object ==== //
        if(isset($_SESSION['cart']) && is_string($_SESSION['cart']))
        {
            return unserialize($_SESSION['cart']);
        }
        else
        {
            // ==== Creating cart instantce if none found ==== //
            if(!isset(self::$instance))
            {
                $class = __CLASS__;
                self::$instance = new $class();
            }

            return self::$instance;
        }
    }

    /**
     * Adds an item to the cart
     *
     * @access public
     * @param array $item_info Must contain and "id" field
     * @return boolean or -1 if item already in cart
     */
    public function addItem($item_info)
    {
        // ==== Result variable ==== //
        $result = false;

        // ==== Checking if the item_info is an array and if an id field is present ==== //
        if(is_array($item_info) && isset($item_info['id']) && is_numeric($item_info['id']))
        {
            // ======== Initializing the cart item details ======== //
            if(!isset($this->items[$item_info['id']]))
            {
                $this->items[$item_info['id']] = $item_info;

                $result = true;
            }
            else
            {
                $result = -1;
            }
        }

        // ==== returning result ==== //
        return $result;
    }

    /**
     * Removes an item from the cart
     * 
     * @access public
     * @param integer $id
     * @return boolean
     */
    public function removeItem($id)
    {
        // ==== Checking if the id is numeric ==== //
        if(is_numeric($id))
        {
            unset($this->items[$id]);

            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Updates an item from the cart
     * 
     * @access public
     * @param array $item_info Must contain and "id" field
     * @return boolean
     */
    public function updateItem($item_info)
    {
        // ==== Checking if the item_info is an array and if an id field is present ==== //
        if(is_array($item_info) && isset($item_info['id']) && is_numeric($item_info['id']) && isset($this->items[$item_info['id']]))
        {
            // ==== Updating the item ==== //
            $this->items[$item_info['id']] = $item_info;

            // ==== Returning result ==== //
            return true;
        }
        else
        {
            // ==== Returning result ==== //
            return false;
        }
    }

    /**
     * The method retrieves info about an item
     *
     * @access public
     * @param numeric $id
     * @return array of false when item is not found
     */
    public function getItem($id)
    {
        // ==== Result variable ==== //
        $result = false;

        // ==== Checking if the id is numeric ==== //
        if(is_numeric($id))
        {
            // ==== Checking if the item exists ==== //
            if(isset($this->items[$id]))
            {
                $result = $this->items[$id];
            }
        }

        // ===== returning result ==== //
        return $result;
    }

    /**
     * Checks if an item is already in the cart
     *
     * @access public
     * @param integer $id
     * @return boolean if not found or id not numeric
     */
    public function isInCart($id)
    {
        // ==== Result variable ==== //
        $result = false;

        // ==== Checking if the id is numeric ==== //
        if(is_numeric($id))
        {
            // ==== Checking if the item exists ==== //
            if(isset($this->items[$id]))
            {
                $result = true;
            }
        }

        // ===== returning result ==== //
        return $result;
    }

    /**
     * The method returns some stats about the items in the car
     *
     * @param void
     * @return array
     */
    public function stats()
    {
        // ==== Counting the items ==== //
        $icount = count($this->items);

        // ====== Calculating total price ======== //
        $total_price = 0;
        foreach ($this->items as $itemid => $item_info)
        {
            $total_price += $item_info['price'] * $item_info['qty'];
        }

        // ==== Returning result ==== //
        return array(
            "icount"      => $icount,
            "total_price" => $total_price
        );
    }

    /**
     * The method returns an array of items
     *
     * @param void
     * @return array
     */
    public function myCart()
    {
        return $this->items;
    }


    /**
     * The method is used to empty the contents of the cart
     *
     * @param void
     * @return void
     */
    public function emptyCart()
    {
        // ==== Unsetting the cart session variable ==== //
        unset($_SESSION['cart']);

        // ==== Checking to see if the session variable exists ==== //
        if(!isset($_SESSION['cart']))
        {
            // ==== Triggering empty procedure === //
            $this->doEmpty = true;

            return true;
        }
        else
            return false;
    }


    /**
     * Class destructor. If makes sure the cart is save after each page load
     *
     * @param void
     * @return void
     */
    public function __destruct()
    {
        // ==== Updating the cart session variable if the empty procedure was not triggered ==== //
        if($this->doEmpty == false)
        {
            $_SESSION['cart'] = serialize($this);
        }
    }

}

?>