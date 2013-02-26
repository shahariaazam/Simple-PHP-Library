<?php

/**
 * 
 * User: brian978
 * Created on: 2/7/13
 * License: Creative Commons Attribution-ShareAlike 3.0
 * 
 */

namespace tests\SPL\Validator;

use PHPUnit_Framework_TestCase;
use SPL\Validator\Url;

class UrlTest extends PHPUnit_Framework_TestCase
{
    public function testIsUrlValidWithoutCUrl()
    {
        $this->assertTrue(Url::isValid('http://www.google.com'));
    }

    public function testIsUrlValidWithCUrl()
    {
        $this->assertTrue(Url::isValid('http://www.google.com', true));
    }

    public function testIsUrlInvalidWithoutCUrl()
    {
        $this->assertTrue(Url::isValid('http://12345'));
    }

    public function testIsUrlInvalidWithCUrl()
    {
        $this->assertFalse(Url::isValid('http://12345', true));
    }
}