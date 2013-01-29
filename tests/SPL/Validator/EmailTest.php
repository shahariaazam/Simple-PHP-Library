<?php

use SPL\Validator\Email;

/**
 * User: brian978
 * Created on: 1/29/13
 * License: Creative Commons Attribution-ShareAlike 3.0
 *
 */
class EmailTest extends PHPUnit_Framework_TestCase
{
    public function testValidationFail()
    {
        $email = 'asdf@';

        $this->assertFalse(Email::isValid($email));
    }

    public function testValidationFailFakeDomain()
    {
        $email = 'asdf@asda.com';

        $this->assertTrue(Email::isValid($email));
    }

    public function testValidationFailFakeDomainWithDnsCheck()
    {
        $email = 'asdf@a11sda.com';

        $this->assertFalse(Email::isValid($email, true));
    }
}