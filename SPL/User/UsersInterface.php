<?php

/**
 *
 * Interface for the Accounts class
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 */

namespace SPL\User;

interface UsersInterface
{
    public function getErrors();
    public function checkLogin(array $data);
    public function getUserInfo();
    public function setUserInfo($data);
}