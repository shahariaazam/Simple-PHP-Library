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
    /**
     * @expectedException \SPL\Url\Exception\RuntimeException
     * @expectedExceptionMessage The site root parameter is not set.
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
     * @expectedExceptionMessage To switch to SSL you need to set the site_root_ssl option
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
