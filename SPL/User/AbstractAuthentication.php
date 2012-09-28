<?php

/**
 *
 * The class handles the authentication of users
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name AbstractUserAuth
 * @version 2.3
 *
 *
 * External errors:
 *
 * ========= Login data errors =========
 *
 * 1 - Username empty
 * 5 - Password empty
 * 15 - User inactive
 *
 *
 * ========= Register data errors =========
 *
 * 20 - Username field empty
 * 21 - Username exists
 * 25 - Password field empty
 * 26 - Password complexity too low
 * 27 - Email field empty
 * 28 - Email is invalid
 * 29 - Email exists
 *
 *
 * ========= Database errors =========
 *
 * 100 - User with the given login data not found
 * 101 - Salt could not be retrieved from the database because it could not be found aka User invalid
 * 102 - Could not register account because query failed
 * 103 - Could not do login because query failed
 * 104 - Could not retrieve the account info for the given account id
 *
 *
 * ========= Salt errors =========
 *
 * 150 - Salt data is not present or incorrect
 *
 *
 * ========= Recover password data errors =========
 *
 * 160 - Data required for password recovery was not found or improper format
 *
 *
 * ========= Other errors =========
 *
 * 200 - No info found in the database for the given account ID
 *
 *
 * Internal errors:
 *
 * ========= Data errors =========
 *
 * 500 - Internal class error (used to signal the UserAcc class that an error has occured)
 * 501 - Could not insert authentication info into the database
 *
 */

namespace SPL\User;

use SPL\Url;
use SPL\Http\Headers as Headers;
use SPL\Security\SecurityInterface;
use SPL\Variable\Variable as Variable;

abstract class AbstractAuthentication implements AuthenticationInterface
{

    /**
     * Options array
     *
     * @var array
     */
    protected $options;

    /**
     * Errors array
     *
     * @var array
     */
    protected $errors = array();

    /**
     * Log holder
     *
     * @var string
     */
    protected $log;

    /**
     * Database object
     *
     * @var db_module
     */
    protected $db;

    /**
     * Vault object
     *
     * @var Vault
     */
    protected $vault;

    /**
     * Local session array
     *
     * @var array
     */
    protected $session;

    /**
     * Cookie container
     *
     * @var string
     */
    protected $cookie;

    /**
     * Authenticated trigger to avoid multiple cookie generations
     *
     * @var boolean
     */
    protected $authenticated = false;

    /**
     *
     * UserAcc object
     *
     * @var BaseUserAcc
     */
    protected $userAcc;

    /**
     * Sets different class properties and some options
     *
     * @param object $db
     * @param UsersInterface $userAcc
     * @param SecurityInterface $vault
     * @param array $options
     * @return void
     */
    public function __construct($db, UsersInterface $userAcc, SecurityInterface $vault, array $options = array())
    {
        // ==== Default $options ==== //
        $this->options['unique_mail']   = '';
        $this->options['debug']         = false;
        $this->options['mail']          = 'webmaster@' . $_SERVER['HTTP_HOST'];
        $this->options['cookie_name']   = 'auth';
        $this->options['cookie_expire'] = 2592000;
        $this->options['cookie_path']   = '/';
        $this->options['cookie_domain'] = '';

        // ==== Replacing the internal values with the external ones ==== //
        if(is_array($options) && count($options) > 0)
        {
            $this->options = array_merge($this->options, $options);
        }

        // ==== Initializing default values ==== //
        $this->log = '';

        // ==== Getting the database object ==== //
        $this->db = $db;

        // ==== Getting the vault object ==== //
        $this->vault = $vault;

        // ==== Getting the UserAcc object ==== //
        $this->userAcc = $userAcc;

        // ==== Getting the session data ==== //
        $this->getSession();

        // ==== Hidding the cookie ==== //
        $this->hideCookie();

        // ==== Triggering the auto-authentication ==== //
        $this->doAuth();
    }

    /**
     * Retrieve the errors
     *
     * @param void
     * @return array Empty array when no errors present or an array of error numbers
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Retrieves the data from the session
     *
     * @param void
     * @return void
     */
    protected function getSession()
    {
        $this->session = &$_SESSION;
    }

    /**
     * Sets the data to the session
     *
     * @param void
     * @return void
     */
    protected function setSession()
    {
        if(is_array($_SESSION))
        {
            $_SESSION = array_merge($_SESSION, $this->session);
        }
        else
        {
            $_SESSION = $this->session;
        }
    }

    /**
     * Logs a message
     *
     * @param string $type
     * @param string $message
     * @param string $location
     * @param string $number
     * @param string $extra1
     * @param string $extra2
     * @return void
     */
    protected function log_message($type, $message, $location = '', $number = 0, $extra1 = '', $extra2 = '')
    {
        // ==== Switching based on type ==== //
        switch($type)
        {
            // SQL
            case 'sql';
                // ==== Error ==== //
                if(is_numeric($number) && $number > 0)
                {
                    $this->errors[] = $number;
                }

                // ==== Checking if debug is active ==== //
                if($this->options['debug'])
                {
                    $this->log .= '<hr><hr><strong>' . $location . '</strong><hr><br />';
                    $this->log .= '<b>ERROR:</b> ' . $message . '<br />';
                    $this->log .= '<b>QUERY:</b>' . $extra1 . '<br />';
                    $this->log .= '<b>SQL ERROR:</b>' . $extra2 . '<br /><br />';
                }

                break;

            // ERROR
            case 'error':
                // ==== Error ==== //
                if(is_numeric($number) && $number > 0)
                {
                    $this->errors[] = $number;
                }

            // LOG
            case 'log':
                $this->log .= '<hr><hr><strong>' . $location . '</strong><hr><br />';
                $this->log .= $message;

                break;

            // INVALID TYPE
            default:
                break;
        }
    }

    /**
     * Regenerates the session ID
     *
     * @param void
     * @return void
     */
    protected function regenerateSession()
    {
        session_regenerate_id();
    }

    /**
     * Destroys the session
     *
     * @param void
     * @return void
     */
    protected function killSession()
    {
        // ==== Destroying the session ===== //
        session_unset();
        session_destroy();
    }

    /**
     * Generates an unredable cookie name
     *
     * @param void
     * @return void
     */
    protected function hideCookie()
    {
        $this->options['cookie_name'] = substr(hash('sha512', $this->options['cookie_name']), 0, 10);
    }

    /**
     * Used to generate a value for the authentication cookie
     *
     * @param array $data
     * @return void
     */
    protected function generateCookieValue(array $data)
    {
        // ==== The cookie ==== //
        $this->cookie = sha1(uniqid() . $data['username']);
    }

    /**
     * Creates the cookie on the user's computer
     *
     * @param void
     * @return void
     */
    protected function createCookie()
    {
        $expire = time() + $this->options['cookie_expire'];

        // ==== Logging the expire time ==== //
        $this->log_message('log', 'Cookie expire: ' . $expire . '<br /><br />', __METHOD__);

        setcookie($this->options['cookie_name'], $this->cookie, $expire, $this->options['cookie_path'], $this->options['cookie_domain']);
    }

    /**
     * Deletes the cookie from the user's computer
     *
     * @param void
     * @return void
     */
    protected function deleteCookie()
    {
        $expire = time() - $this->options['cookie_expire'];

        // ==== Logging the expire time ==== //
        $this->log_message('log', 'Cookie expire: ' . $expire . '<br /><br />', __METHOD__);

        setcookie($this->options['cookie_name'], $this->cookie, $expire, $this->options['cookie_path'], $this->options['cookie_domain']);
    }

    /**
     * Prepares the login data
     *
     * @param array $data
     * @return array
     */
    protected function prepareLogin(array $data)
    {
        // ==== Getting the IP address ==== //
        $data['ip_addr'] = $_SERVER['REMOTE_ADDR'];

        // ==== The cookie ==== //
        $data['cookie'] = $this->cookie;

        // ==== Getting all the headers ==== //
        $headers = Headers::request();

        // ==== Getting the headers ==== //
        $data['headers'] = base64_encode(serialize($headers['HTTP_USER_AGENT']));

        // ==== Adding log data ==== //
        if($this->options['debug'])
        {
            $log = '<strong>Info:</strong><br />';
            $log .= 'Cookie name: ' . $this->options['cookie_name'] . '<br />';
            $log .= 'Data: ' . Variable::print_array($data, 1) . '<br />';
            $log .= '<br /><br />';

            // ==== Adding log ==== //
            $this->log_message('log', $log, __METHOD__);
        }

        // ==== Result ==== //
        return $data;
    }

    /**
     * Login SQL
     *
     * @param number $user_id
     * @param array $data
     * @return string
     */
    protected abstract function sqlLogin($user_id, array $data);

    /**
     * Does the user loginprocess
     *
     * @param array $data
     * @param boolean $remember
     * @return boolean
     */
    public function doLogin(array $data, $remember = false)
    {
        // ==== Default result ==== //
        $result = true;

        // ==== Checking if the user is not already authenticated ==== //
        if(!$this->authenticated)
        {
            // ==== Doing the login via the UserAcc object ==== //
            $user_id = $this->userAcc->checkLogin($data);

            // ==== Checking if the login went OK ==== //
            if($user_id !== false)
            {
                // ==== Checking if persistent login is enabled ==== //
                if($remember === true)
                {
                    ////////////////////////////////////////////////////////////////
                    //  PERSISTENT LOGIN
                    ///////////////////////////////////////////////////////////////
                    // ==== Generating a cookie value ==== //
                    $this->generateCookieValue($data);

                    // ==== Preparing the data for the cookie insertion ==== //
                    $data = $this->prepareLogin($data);

                    // ==== Getting the SQL for the cookie insertion ==== //
                    $sql = $this->sqlLogin($user_id, $data);

                    // ==== Checking if the SQL is not empty ==== //
                    if($sql != '')
                    {
                        // ==== Executing the SQL ==== //
                        $this->db->query($sql);

                        // ==== Getting the SQL error ==== //
                        $sql_error = $this->db->error();

                        // ==== Checking if an error occured ==== //
                        if($sql_error == '')
                        {
                            // ==== Setting the cookie ==== //
                            $this->createCookie();

                            // ==== Authenticating ==== //
                            $success = $this->doAuth($user_id, true);

                            // ==== Checking if the authentication went ok ==== //
                            if($success === false)
                            {
                                $result = false;
                            }
                        }
                        else
                        {
                            // ==== Adding the error ==== //
                            $this->log_message('sql', 'Could not insert authentication info into the database', __METHOD__, 501, $sql, $sql_error);
                        }
                    }
                    else
                    {
                        // ==== Adding the error ==== //
                        $this->log_message('error', 'Internal class error (used to signal the UserAcc class that an error has occured)', __METHOD__, 500);
                    }
                }
                else
                {
                    ////////////////////////////////////////////////////////////////
                    // NON-PERSISTENT LOGIN
                    ///////////////////////////////////////////////////////////////
                    // ==== Authenticating ==== //
                    $success = $this->doAuth($user_id, true);

                    // ==== Checking if the authentication went ok ==== //
                    if($success === false)
                    {
                        $result = false;
                    }
                }
            }
            else
            {
                // ==== Authentication failed ==== //
                $result = false;

                // ==== Getting the errors from the UserAcc class ==== //
                $this->errors = array_merge($this->errors, $this->userAcc->getErrors());

                // ==== Adding log data ==== //
                if($this->options['debug'])
                {
                    $log = 'User ID: ' . $user_id . '<br />';
                    $log .= 'Function arguments: <pre>' . print_r(func_get_args(), true) . '</pre><br /><br />';

                    // ==== Adding the error ==== //
                    $this->log_message('log', $log, __METHOD__);
                }
            }
        }
        else
        {
            // ==== Adding log data ==== //
            if($this->options['debug'])
            {
                $log = '<strong>NOTICE:</strong> User already logged in.<br /><br />';

                // ==== Adding the error ==== //
                $this->log_message('log', $log, __METHOD__);
            }
        }

        // ==== Result ==== //
        return $result;
    }

    /**
     * Authentication SQL
     *
     * @param array $data
     * @return string
     */
    protected abstract function sqlAuth(array $data);

    /**
     * Prepares the authentication data
     *
     * @param void
     * @return array on success of false on fail
     */
    protected function prepareAuth()
    {
        // ==== Default data ==== //
        $data = false;

        // ===== Checking if the cookie exists ===== //
        if(isset($_COOKIE[$this->options['cookie_name']]))
        {
            // ==== Converting the data type of data ==== //
            $data = array();

            // ==== Getting the IP address ==== //
            $data['ip_addr'] = $_SERVER['REMOTE_ADDR'];

            // ==== Getting the cookie ==== //
            $data['cookie'] = $this->db->escape_string($_COOKIE[$this->options['cookie_name']]);

            // ==== Getting all the headers ==== //
            $headers = Headers::request();

            // ==== Getting the headers ==== //
            $data['headers'] = base64_encode(serialize($headers['HTTP_USER_AGENT']));

            // ==== Adding log data ==== //
            if($this->options['debug'])
            {
                $log = '<strong>Info:</strong><br />';
                $log .= 'Cookie name: ' . $this->options['cookie_name'] . '<br />';
                $log .= 'Data: ' . Variable::print_array($data, 1) . '<br />';
                $log .= '<br /><br />';

                // ==== Adding the error ==== //
                $this->log_message('log', $log, __METHOD__);
            }
        }
        else
        {
            // ==== Adding log data ==== //
            if($this->options['debug'])
            {
                $log = '<strong>ERROR:</strong> The required data for the prepareAuth method is not present. Required: authentication cookie.<br /><br />';

                // ==== Adding the error ==== //
                $this->log_message('log', $log, __METHOD__);
            }
        }

        // ==== Result ==== //
        return $data;
    }

    /**
     * Checks if the user is authenticated
     *
     * @param void
     * @return mixed False on failure or the user ID on success
     */
    protected function checkAuth()
    {
        // ==== Check var ==== //
        $user_id = false;

        // ==== Preparing the auth data ==== //
        $data = $this->prepareAuth();

        // ==== Checking the data ==== //
        if($data != false)
        {
            // ===== Getting the SQL ==== //
            $sql = $this->sqlAuth($data);

            // ==== Checking the SQL ==== //
            if($sql != '')
            {
                // ==== Executing the SQL ==== //
                $this->db->query($sql);

                // ==== Getting the SQL error ==== //
                $sql_error = $this->db->error();

                // ==== Checking if something found ==== //
                if($sql_error != '' || $this->db->num_rows() != 1)
                {
                    // ==== Adding log data ==== //
                    if($this->options['debug'])
                    {
                        $log = '<strong>Info:</strong><br />';
                        $log .= 'Authentication falied.<br /><br />';
                        $log .= '$this->session: ' . Variable::print_array($this->session, 1) . '<br />';
                        $log .= '$_COOKIE: ' . Variable::print_array($_COOKIE, 1) . '<br />';
                        $log .= '$sql_error: ' . $sql_error . '<br />';
                        $log .= '<br /><br />';

                        // ==== Adding the error ==== //
                        $this->log_message('log', $log, __METHOD__);
                    }
                }
                else
                {
                    // ==== Getting the row info ==== //
                    $row = $this->db->fetch_assoc();

                    // ==== Getting the account ID ==== //
                    $user_id = &$row['user_id'];

                    // ==== Adding log data ==== //
                    if($this->options['debug'])
                    {
                        $log = '<strong>Info:</strong> Authenticated user successfully.<br /><br />';

                        // ==== Adding the error ==== //
                        $this->log_message('log', $log, __METHOD__);
                    }
                }
            }
        }

        // ==== Result ==== //
        return $user_id;
    }

    /**
     * Authenticates the user
     *
     * @param integer $user_id
     * @param boolean $bypass_db
     * @return boolean
     */
    protected function doAuth($user_id = 0, $bypass_db = false)
    {
        // ==== Check variable ==== //
        $success = true;

        // ==== Skipping if already authenticated ==== //
        if($user_id > 0 // provided by doLogin
                || (isset($this->session['auth'])
                && $this->session['auth'] !== true
                && !empty($_COOKIE[$this->options['cookie_name']])
                )
                || (!isset($this->session['auth'])
                && !empty($_COOKIE[$this->options['cookie_name']])
                )
        )
        {
            //////////////////////////////////////////////////////////
            // BEGIN DB CHECK ONLY WHEN AUTHENTICATING VIA COOKIE
            /////////////////////////////////////////////////////////
            if($bypass_db === false)
            {
                // ==== Checking if authenticated ==== //
                $user_id = $this->checkAuth();

                // ==== Checking if the user_id was retrieved ==== //
                if($user_id === false)
                {
                    $success = false;
                }
            }
            else
            {
                // ==== Adding log data ==== //
                if($this->options['debug'])
                {
                    $log = '<strong>Info:</strong> Skipped db check.<br /><br />';

                    // ==== Adding the error ==== //
                    $this->log_message('log', $log, __METHOD__);
                }
            }
            //////////////////////////////////////////////////////////
            // END DB CHECK ONLY WHEN AUTHENTICATING VIA COOKIE
            /////////////////////////////////////////////////////////
            // ==== Checking if the authentication process went OK ==== //
            if($success == true)
            {
                // ==== Getting the userinfo ==== //
                $userinfo = $this->userAcc->getUserInfo($user_id);

                // ==== Regenerating the session ==== //
                $this->regenerateSession();

                // ==== Setting the authentication flag ==== //
                $this->session['auth'] = true;

                // ==== Storing the userinfo into the session ==== //
                $this->session['userinfo'] = $this->vault->encrypt(serialize($userinfo));
            }
            else
            {
                // ==== Setting the authentication flag ==== //
                $this->session['auth'] = false;
            }
        }
        // Authenticated
        else if(isset($this->session['auth']) && $this->session['auth'] == true)
        {
            // ==== Getting the userinfo from the session ==== //
            $userinfo = unserialize($this->vault->decrypt($this->session['userinfo']));

            // ==== Updating the userinfo of the UserAcc class ==== //
            $this->userAcc->setUserInfo($userinfo);

            // ==== Adding log data ==== //
            if($this->options['debug'])
            {
                $log = '<strong>Info:</strong><br />';
                $log .= 'Skipped database authentication<br /><br />';
                $log .= 'User info: ' . Variable::print_array($userinfo, 1) . '<br />';
                $log .= '<br /><br />';

                // ==== Adding the error ==== //
                $this->log_message('log', $log, __METHOD__);
            }
        }
        // Fail safe
        else
        {
            // ==== Adding log data ==== //
            if($this->options['debug'])
            {
                $log = '<strong>Info:</strong><br />';
                $log .= 'Function arguments: <pre>' . print_r(func_get_args(), true) . '</pre><br /><br />';
                $log .= '<br /><br />';

                // ==== Adding the error ==== //
                $this->log_message('log', $log, __METHOD__);
            }
        }

        // ==== Checking if authenticated ==== //
        if(isset($this->session['auth']) && $this->session['auth'] == true)
        {
            $this->authenticated = true;
        }
        else
        {
            $success = false;
        }

        // ==== Saving the session info ==== //
        $this->setSession();

        // ===== Result ==== //
        return $success;
    }

    /**
     * Verifies if the user is logged in or not
     *
     * @param void
     * @return boolean
     */
    public function isLoggedIn()
    {
        // ==== Adding log data ==== //
        if($this->options['debug'])
        {
            $log = '<strong>Info:</strong> Authenticated: ' . ($this->authenticated == true ? 'yes' : 'no') . '<br />';
            $log .= '<br /><br />';

            // ==== Adding the error ==== //
            $this->log_message('log', $log, __METHOD__);
        }

        // ==== returning the result ==== //
        return $this->authenticated;
    }

    /**
     * SQL logout
     *
     * @param array $data
     * @return string
     */
    protected abstract function sqlLogout(array $data);

    /**
     * Logout method
     *
     * @param void
     * @return void
     */
    public function doLogout()
    {
        // ==== Deleting the cookie ==== //
        $this->deleteCookie();

        // ==== Preparing the data ==== //
        $data = $this->prepareAuth();

        // ==== Checking if we should delete data from the database ==== //
        if($data != false)
        {
            // ==== Getting the SQL ==== //
            $sql = $this->sqlLogout($data);

            // ==== Executing the SQL ==== //
            $this->db->query($sql);
        }

        // Killing the session
        $this->killSession();
    }

    /**
     * Sends debug mail (if debug is active and there is something to send)
     *
     * @param void
     * @return void
     */
    public function __destruct()
    {
        // ==== Debug ==== //
        if($this->options['debug'] && $this->log != '')
        {
            // ==== Adding some more data to the log ==== //
            $this->log .= '<hr><hr><strong>Other info</strong><hr><br /><br />';
            $this->log .= '<strong>ERRORS:</strong><pre>' . print_r($this->errors, true) . '<br /><br />';
            $this->log .= '<strong>URL:</strong><pre>' . Url\Url::getFullURL() . '<br /><br />';
            $this->log .= '<strong>GET:</strong><pre>' . print_r($_GET, true) . '<br /><br />';
            $this->log .= '<strong>POST:</strong><pre>' . print_r($_POST, true) . '<br /><br />';
            $this->log .= '<strong>SESSION:</strong><pre>' . print_r($this->session, true) . '<br /><br />';
            $this->log .= '<strong>COOKIE:</strong><pre>' . print_r($_COOKIE, true) . '<br /><br />';
            $this->log .= '<strong>HEADERS:</strong><pre>' . print_r(Headers::request(), true) . '<br /><br />';
            $this->log .= '<strong>SERVER:</strong><pre>' . print_r($_SERVER, true) . '<br /><br />';

            // Mail options
            $to      = $this->options['mail'];
            $subject = '[DEBUG] ' . __CLASS__ . ' Class ' . $this->options['unique_mail'];
            $headers = 'MIME-Version: 1.0' . "\r\n" . 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

            // ==== Sending debug mail ==== //
            mail($to, $subject, $this->log, $headers);
        }
    }

}