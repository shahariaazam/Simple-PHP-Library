<?php

use SPL\Url\Url;

/**
 * User: brian978
 * Created on: 1/24/13
 * License: Creative Commons Attribution-ShareAlike 3.0
 *
 */
class UrlTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $_SERVER['HTTPS']       = 'On';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '';
    }

    /**
     * @expectedException \SPL\Url\Exception\RuntimeException
     * @expectedExceptionMessage Invalid site root URL.
     */
    public function testSiteRootIsNotSet()
    {
        new Url();
    }

    /**
     * @expectedException \SPL\Url\Exception\RuntimeException
     */
    public function testSiteRootIsInvalid()
    {
        $config = array(
            'site_root' => 'htt://localhost'
        );

        new Url($config);
    }

    /**
     * @expectedException \SPL\Url\Exception\RuntimeException
     * @expectedExceptionMessage Invalid SSL site root URL
     */
    public function testSiteRootSslIsInvalid()
    {
        $config = array(
            'site_root' => 'http://localhost',
            'require_ssl' => true
        );

        new Url($config);
    }
}
