<?php
/**
 * Date: 2/27/13
 * Time: 4:00 PM
 */
namespace tests\SPL\Validator;

use PHPUnit_Framework_TestCase;
use SPL\Validator\Password;

class PasswordTest extends PHPUnit_Framework_TestCase
{

    public function testDefaultValidation()
    {
        $validator = new Password();

        $this->assertTrue($validator->isValid('Asdf1231'));
    }

    public function testDefaultValidationFail()
    {
        $validator = new Password();

        $this->assertFalse($validator->isValid('asdf1231'));
    }

    public function testNumbersValidationFail()
    {
        $validator = new Password(array(
            'number' => true,
            'ucase' => false,
            'lcase' => false,
            'length' => false,
        ));

        $this->assertFalse($validator->isValid('asdfasdasd'));
    }

    public function testUpperCaseValidationFail()
    {
        $validator = new Password(array(
            'number' => false,
            'ucase' => true,
            'lcase' => false,
            'length' => false,
        ));

        $this->assertFalse($validator->isValid('sdfasdasd123'));
    }

    public function testLowerCaseValidationFail()
    {
        $validator = new Password(array(
            'number' => false,
            'ucase' => false,
            'lcase' => true,
            'length' => false,
        ));

        $this->assertFalse($validator->isValid('ADSDSADGFD123'));
    }

    public function testLengthValidationFail()
    {
        $validator = new Password(array(
            'number' => false,
            'ucase' => false,
            'lcase' => false,
            'length' => 5,
        ));

        $this->assertFalse($validator->isValid('Ad1'));
    }

    public function testNoValidation()
    {
        $validator = new Password(array(
            'number' => false,
            'ucase' => false,
            'lcase' => false,
            'length' => false,
        ));

        $this->assertTrue($validator->isValid(''));
    }
}