<?php
/**
 *
 * The class handles the authentication of users
 *
 * @author Brian
 * @link http://brian.hopto.org/framework_wik/
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name UserAuth
 * @version 1.3
 *
 * Internal errors:
 *
 * ========= Data errors =========
 *
 * 200 - Internal class error (used to signal the UserAcc class that an error has occured)
 * 201 - Could not insert authentication info into the database
 *
 */

class UserAuth
{
    /**
     * Options array
     *
     * @var array
     */
    protected $_options;

    /**
     * Errors array
     *
     * @var array
     */
    protected $_errors = array();

    /**
     * Log holder
     *
     * @var string
     */
    protected $_log;

    /**
     * Mail options
     *
     * @var array
     */
    protected $_mopt;

    /**
     * Database object
     *
     * @var object
     */
    protected $_db;

    /**
     * This property is only set when the login is triggered from inside the class
     *
     * @var boolean
     */
    protected $_trusted = false;

    /**
     * Cookie container
     *
     * @var string
     */
    protected $_cookie;

    /**
     * Authenticated trigger to avoid multiple cookie generations
     *
     * @var boolean
     */
    protected $_authenticated = false;


    /**
     * Class constructor
     *
     * @param object $db
     * @param array $options
     * @return void
     */
    public function __construct(db_module $db, array $options=array())
    {
        // ==== Default $options ==== //
        $this->_options['unique_mail']     = '';
        $this->_options['debug']           = false;
        $this->_options['mail']            = 'webmaster@' . $_SERVER['HTTP_HOST'];
        $this->_options['cookie_name']     = 'auth';
        $this->_options['cookie_expire']   = 2592000;
        $this->_options['cookie_path']     = '/';
        $this->_options['cookie_domain']   = '';

        // ==== Replacing the internal values with the external ones ==== //
        if(is_array($options))
        {
            $this->_options = array_merge($this->_options, $options);
        }

        // ==== Setting up mail options ==== //
        $this->_mopt['to']        = $this->_options['mail'];
        $this->_mopt['subject']   = '[DEBUG] ' . __CLASS__ . ' Class ' . $this->_options['unique_mail'];
        $this->_mopt['headers']   = 'MIME-Version: 1.0' . "\r\n";
        $this->_mopt['headers']  .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

        // ==== Initializing default values ==== //
        $this->_log = '';

        // ==== Initializing the database object ==== //
        $this->_db = $db;

        // ==== Hidding the cookie ==== //
        $this->hideCookie();

        // ==== Triggering the auto-authentication ==== //
        $this->authenticate();
    }

    /**
     * The method is used to retrieve the errors
     *
     * @param void
     * @return array
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * The method hides the cookies purpose
     *
     * @param void
     * @return void
     */
    protected function hideCookie()
    {
        $this->_options['cookie_name'] = substr(hash('sha512', $this->_options['cookie_name']), 0, 10);
    }

    /**
     * Login SQL
     *
     * @param array $data
     * @return string
     */
    protected function sqlLogin(array $data)
    {
        /**
         * ----------------------
         * OVERWRITE THIS METHOD
         * ----------------------
         *
         */
    }

    /**
     * Used to generate an authentication cookie
     *
     * @param array $data
     * @return void
     */
    protected function generateCookie(array $data)
    {
        // ==== The cookie ==== //
        $cookie         = sha1(uniqid().$data['username']);
        $this->_cookie  = $cookie;
    }

    /**
     * Creates the cookie on the users computer
     *
     * @param void
     * @return void
     */
    protected function createCookie()
    {
        setcookie($this->_options['cookie_name'], $this->_cookie, time()+$this->_options['cookie_expire'], $this->_options['cookie_path'], $this->_options['cookie_domain']);
    }

    /**
     * Deletes the cookie from the users computer
     *
     * @param void
     * @return void
     */
    protected function deleteCookie()
    {
        setcookie($this->_options['cookie_name'], $this->_cookie, time()-$this->_options['cookie_expire'], $this->_options['cookie_path'], $this->_options['cookie_domain']);
    }

    /**
     * Login data
     *
     * @param array $data
     * @return array
     */
    protected function prepareLogin(array $data)
    {
        // ==== Getting the IP address ==== //
        $data['ip_addr'] = $_SERVER['REMOTE_ADDR'];

        // ==== The cookie ==== //
        $data['cookie'] = $this->_cookie;

        // ==== Getting all the headers ==== //
        $headers = get_request_headers();

        // ==== Getting the headers ==== //
        $data['headers'] = base64_encode(serialize($headers['HTTP_USER_AGENT']));

        // ==== Adding log data ==== //
        if($this->_options['debug'])
        {
            $this->_log .= '<hr><hr><strong>prepareLogin</strong><hr><br />';
            $this->_log .= '<strong>Info:</strong><br />';
            $this->_log .= 'Cookie name: '.$this->_options['cookie_name'].'<br />';
            $this->_log .= 'Data: '.print_array($data, 1).'<br />';
            $this->_log .= '<br /><br />';
        }

        // ==== Result ==== //
        return $data;
    }

    /**
     * Login method. It creates the necessary info for the authentication.
     *
     * @param array $data
     * @param boolean $persistent
     * @return boolean
     */
    public function login(array $data, $persistent=false)
    {
        // ==== Default result ==== //
        $result = false;

        // ==== Checking if the user is not already authenticated ==== //
        if(!$this->_authenticated)
        {
            // ==== Checking if persistent login is enabled ==== //
            if($persistent === true)
            {
                ////////////////////////////////////////////////////////////////
                //  PERSISTENT LOGIN
                ///////////////////////////////////////////////////////////////
                // ==== Generating the cookie ==== //
                $this->generateCookie($data);

                // ==== Preparing the login data ==== //
                $data = $this->prepareLogin($data);

                // ==== Getting the SQL for the cookie insertion ==== //
                $sql = $this->sqlLogin($data);

                // ==== Checking if the SQL is not empty ==== //
                if($sql != '')
                {
                    // ==== Executing the SQL ==== //
                    $result = $this->_db->query($sql);

                    // ==== Checking the result ==== //
                    if($result == true)
                    {
                        // ==== Trusted ==== //
                        $this->_trusted = true;

                        // ==== Creating the cookie ==== //
                        $this->createCookie();

                        // ==== Authenticating ==== //
                        $this->authenticate($data);
                    }
                    else
                    {
                        // ==== Errors ==== //
                        $this->_errors[] = 201;
                    }
                }
                else
                {
                    // ==== Errors ==== //
                    $this->_errors[] = 200;
                }
            }
            else
            {
                ////////////////////////////////////////////////////////////////
                // NON-PERSISTENT LOGIN
                ///////////////////////////////////////////////////////////////
                // ==== Trusted ==== //
                $this->_trusted = true;

                // ==== Authenticating ==== //
                $success = $this->authenticate($data);

                // ==== Checking if the authentication went ok ==== //
                if($success == true)
                {
                    $result = true;
                }
            }
        }
        else
        {
            $result = true;

            // ==== Adding log data ==== //
            if($this->_options['debug'])
            {
                $this->_log .= '<hr><hr><strong>login</strong><hr><br />';
                $this->_log .= '<strong>NOTICE:</strong> User already logged in.<br /><br />';
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
    protected function sqlAuth(array $data)
    {
        /**
         * ----------------------
         * OVERWRITE THIS METHOD
         * ----------------------
         *
         */
    }

    /**
     * Authentication data
     *
     * @param void
     * @return array on success of false on fail
     */
    protected function prepareAuth()
    {
        // ==== Default data ==== //
        $data = false;

        // ===== Checking if the cookie exists ===== //
        if(isset($_COOKIE[$this->_options['cookie_name']]))
        {
            // ==== Converting the data type of data ==== //
            $data = array();

            // ==== Getting the IP address ==== //
            $data['ip_addr'] = $_SERVER['REMOTE_ADDR'];

            // ==== Getting the cookie ==== //
            $data['cookie']  = $_COOKIE[$this->_options['cookie_name']];

            // ==== Getting all the headers ==== //
            $headers = get_request_headers();

            // ==== Getting the headers ==== //
            $data['headers'] = base64_encode(serialize($headers['HTTP_USER_AGENT']));

            // ==== Adding log data ==== //
            if($this->_options['debug'])
            {
                $this->_log .= '<hr><hr><strong>prepareAuth</strong><hr><br />';
                $this->_log .= '<strong>Info:</strong><br />';
                $this->_log .= 'Cookie name: '.$this->_options['cookie_name'].'<br />';
                $this->_log .= 'Data: '.print_array($data, 1).'<br />';
                $this->_log .= '<br /><br />';
            }
        }
        else
        {
            // ==== Adding log data ==== //
            if($this->_options['debug'])
            {
                $this->_log .= '<hr><hr><strong>prepareAuth</strong><hr><br />';
                $this->_log .= '<strong>ERROR:</strong> The required data for the prepareAuth method is not present. Required: authentication cookie.<br /><br />';
            }
        }

        // ==== Result ==== //
        return $data;
    }

    /**
     * The method authenticates the user
     *
     * @param array $data
     * @return boolean
     */
    public function authenticate(array $data=array())
    {
        // ==== Check variable ==== //
        $isOk = true;

        // ==== Skipping if already authenticated ==== //
        if((isset($_SESSION['auth']) && $_SESSION['auth'] === false && ($this->_trusted === true || isset($_COOKIE[$this->_options['cookie_name']]))) || !isset($_SESSION['auth']))
        {
            // ==== Search database flag ===== //
            $check_db = true;

            // ==== Checking if this was triggered from inside the class ==== //
            if($this->_trusted === true)
            {
                // ==== Avoiding the db check ==== //
                $check_db = false;

                // ==== Untrusting ==== //
                $this->_trusted = false;

            }

            ////////////////////////////////////////////////
            // START DB CHECK ONLY AT FIRST ACCESS
            ///////////////////////////////////////////////
            if($check_db)
            {
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
                        $success = $this->_db->query($sql);

                        // ==== Checking if something found ==== //
                        if($success == false || $this->_db->num_rows() != 1)
                        {
                            // ==== Adding log data ==== //
                            if($this->_options['debug'])
                            {
                                $this->_log .= '<hr><hr><strong>authenticate</strong><hr><br />';
                                $this->_log .= '<strong>Info:</strong><br />';
                                $this->_log .= 'Authentication falied.<br /><br />';
                                $this->_log .= '$_SESSION: '.print_array($_SESSION, 1).'<br />';
                                $this->_log .= '$_COOKIE: '.print_array($_COOKIE, 1).'<br />';
                                $this->_log .= '<br /><br />';
                            }

                            // ==== Removing the cookie ==== //
                            $this->deleteCookie();

                            $isOk = false;
                        }
                        else
                        {
                            // ==== Getting the row info ==== //
                            $row = $this->_db->fetch_assoc();

                            // ==== Getting the account ID ==== //
                            $account_id = $row['account_id'];

                            // ==== Adding log data ==== //
                            if($this->_options['debug'])
                            {
                                $this->_log .= '<hr><hr><strong>authenticate</strong><hr><br />';
                                $this->_log .= '<strong>Info:</strong> Authenticated user successfully.<br /><br />';
                            }
                        }
                    }
                    else // Error handling in sqlAuth
                    {
                        $isOk = false;
                    }
                }
                else // Error handling in prepareAuth
                {
                    $isOk = false;
                }
            }
            else
            {
                // ==== Adding log data ==== //
                if($this->_options['debug'])
                {
                    $this->_log .= '<hr><hr><strong>authenticate</strong><hr><br />';
                    $this->_log .= '<strong>Info:</strong> Skipped db check.<br /><br />';
                }
            }
            ////////////////////////////////////////////////
            // END DB CHECK ONLY AT FIRST ACCESS
            ///////////////////////////////////////////////

            // ==== Setting the authentication flag ==== //
            if($isOk == true)
            {
                // ==== Regenerating the session ==== //
                session_regenerate_id();

                $_SESSION['auth'] = true;

                // ==== Setting the account ID ==== //
                if($check_db === false) // Using data provided by the UserAcc class
                {
                    $_SESSION['userid'] = $data['account_id'];
                }
                else // Using data from the database
                {
                    $_SESSION['userid'] = $account_id;
                }
            }
            else
            {
                $_SESSION['auth'] = false;
            }
        }
        else
        {
            // ==== Adding log data ==== //
            if($this->_options['debug'])
            {
                $this->_log .= '<hr><hr><strong>authenticate</strong><hr><br />';
                $this->_log .= '<strong>Info:</strong><br />';
                $this->_log .= 'Skipped authentication<br /><br />';
                $this->_log .= '$_SESSION: '.print_array($_SESSION, 1).'<br />';
                $this->_log .= '$_COOKIE: '.print_array($_COOKIE, 1).'<br />';
                $this->_log .= '<br /><br />';
            }
        }

        // ==== Checking if authenticated ==== //
        if($_SESSION['auth'] == true)
        {
            $this->_authenticated = true;
        }

        // ===== Result ==== //
        return $isOk;
    }

    /**
     * SQL logout
     *
     * @param array $data
     * @return void
     */
    protected function sqlLogout(array $data)
    {
        /**
         * ----------------------
         * OVERWRITE THIS METHOD
         * ----------------------
         *
         */
    }

    /**
     * Logout method
     *
     * @param void
     * @return void
     */
    public function logout()
    {
        // ==== Deleting the cookie ==== //
        setcookie($this->_options['cookie_name'], '', -10);

        // ==== Preparing the data ==== //
        $data = $this->prepareAuth();

        // ==== Checking if we should delete data from the database ==== //
        if($data != false)
        {
            // ==== Getting the SQL ==== //
            $sql = $this->sqlLogout($data);

            // ==== Executing the SQL ==== //
            $this->_db->query($sql);
        }

        // ==== Destroying the session ===== //
        session_unset();
        session_destroy();

        // ===== Auth session var ==== //
        $_SESSION['auth'] = false;
    }

    /**
     * The method verifies if the user is logged in or not
     *
     * @param void
     * @return boolean
     */
    public function isLoggedIn()
    {
        // ==== Checking ===== //
        if(isset($_SESSION['auth']) && $_SESSION['auth'] == true)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Class destructor
     *
     * @param void
     * @return void
     */
    public function __destruct()
    {
        // ==== Debug ==== //
        if($this->_options['debug'] && $this->_log != '')
        {
            // ==== Adding some more data to the log ==== //
            $this->_log .= '<hr><hr><strong>Other info</strong><hr>';
            $this->_log .= '<strong>URL:</strong><pre>'.getFullURL().'<br /><br />';
            $this->_log .= '<strong>GET:</strong><pre>'.print_r($_GET, true).'<br /><br />';
            $this->_log .= '<strong>POST:</strong><pre>'.print_r($_POST, true).'<br /><br />';
            $this->_log .= '<strong>COOKIE:</strong><pre>'.print_r($_COOKIE, true).'<br /><br />';
            $this->_log .= '<strong>HEADERS:</strong><pre>'.print_r(get_request_headers(), true).'<br /><br />';

            // ==== Sending debug mail ==== //
            mail($this->_mopt['to'], $this->_mopt['subject'], $this->_log, $this->_mopt['headers']);
        }
    }
}