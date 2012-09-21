<?php

/**
 *
 * Abstract class that must be implemented by all PayPal related classes
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name PayPal
 * @version 1.1
 *
 */

namespace SPL\Billing\PayPal;

abstract class PayPal implements PayPalInterface
{
    /**
     * Environment constants
     *
     */
    const ENV_PRODUCTION = 1;
    const ENV_TESTING = 2;

    /**
     * Platform constatns
     *
     */
    const PLATFORM_DESKTOP = 1;
    const PLATFORM_MOBILE = 2;

    /**
     * Returns the image for the PayPal ExpressCheckout button
     *
     * @param void
     * @return string
     */
    protected static function getExpressCheckoutButton()
    {
        return '<img src="https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif" align="left" style="margin-right:7px;" />';
    }

    /**
     * Returns the image for the Digital Goods for ExpressCheckout button
     *
     * @param void
     * @return string
     */
    protected static function getDigitalGoodsButton()
    {
        return '<img src="https://www.paypal.com/en_US/i/btn/btn_dg_pay_w_paypal.gif" />';
    }
}