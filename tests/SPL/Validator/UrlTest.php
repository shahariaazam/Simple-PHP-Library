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
    /**
     * @var \SPL\Validator\Url
     */
    protected $validator;

    public function setUp()
    {
        $this->validator = new Url();
    }

    public function testIsUrlValidWithCUrl()
    {
        $this->assertTrue($this->validator->isValid('http://www.google.com', true));
    }

    public function testIsUrlInvalidWithCUrl()
    {
        $this->assertFalse($this->validator->isValid('http://12345', true));
    }

    public function testIsUrlValidWithoutCUrl()
    {
        $this->assertTrue($this->validator->isValid('http://www.google.com'));
    }

    public function testIsUrlInvalidWithoutCUrl()
    {
        $this->assertTrue($this->validator->isValid('http://12345'));
    }
}