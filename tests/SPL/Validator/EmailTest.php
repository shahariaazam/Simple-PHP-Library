<?php

/**
 * User: brian978
 * Created on: 1/29/13
 * License: Creative Commons Attribution-ShareAlike 3.0
 *
 */

namespace tests\SPL\Validator;

use PHPUnit_Framework_TestCase;
use SPL\Validator\Email;

class EmailTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \SPL\Validator\Email
     */
    protected $validator;

    public function setUp()
    {
        $this->validator = new Email();
    }

    public function testValidationFail()
    {
        $email = 'asdf@';

        $this->assertFalse($this->validator->isValid($email));
    }

    public function testValidationFailFakeDomain()
    {
        $email = 'asdf@asda.com';

        $this->assertTrue($this->validator->isValid($email));
    }

    public function testValidationFailFakeDomainWithDnsCheck()
    {
        $email = 'asdf@a11sda.com';

        $this->assertFalse($this->validator->isValid($email, true));
    }
}