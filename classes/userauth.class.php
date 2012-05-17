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
     * Mail options
     *
     * @var array
     */
    protected $mopt;

    /**
     * Database object
     *
     * @var object
     */
    protected $db;

    /**
     * Vault object
     *
     * @var object
     */
    protected $vault;

    /**
     * This property is only set when the login is triggered from inside the class
     *
     * @var boolean
     */
    protected $trusted = false;

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
     * Array with the userinfo
     *
     * @var array
     */
    protected $userinfo = array();


    /**
     * Class constructor
     *
     * @param object $db
     * @param array $options
     * @return void
     */
    public function __construct(db_module $db, Vault $vault, array $options=array())
    {
        // ==== Default $options ==== //
        $this->options['unique_mail']     = '';
        $this->options['debug']           = false;
        $this->options['mail']            = 'webmaster@' . $_SERVER['HTTP_HOST'];
        $this->options['cookie_name']     = 'auth';
        $this->options['cookie_expire']   = 2592000;
        $this->options['cookie_path']     = '/';
        $this->options['cookie_domain']   = '';

        // ==== Replacing the internal values with the external ones ==== //
        if(is_array($options))
        {
            $this->options = array_merge($this->options, $options);
        }

        // ==== Setting up mail options ==== //
        $this->mopt['to']        = $this->options['mail'];
        $this->mopt['subject']   = '[DEBUG] ' . __CLASS__ . ' Class ' . $this->options['unique_mail'];
        $this->mopt['headers']   = 'MIME-Version: 1.0' . "\r\n";
        $this->mopt['headers']  .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

        // ==== Initializing default values ==== //
        $this->log = '';

        // ==== Initializing the database object ==== //
        $this->db = $db;

        // ==== Initializing the vault object ==== //
        $this->vault = $vault;

        // ==== Hidding the cookie ==== //
        $this->hideCookie();

        // ==== Triggering the auto-authentication ==== //
        if($this->authenticate())
        {
            // ==== Getting the user information ==== //
            $this->userinfo = unserialize($this->vault->decrypt($_SESSION['userinfo']));
        }
    }

    /**
     * The method is used to retrieve the errors
     *
     * @param void
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     *
     * The method tries to retrieve a certain information about the users account
     *
     * @param string $field
     * @return mixed It returns false if the information is not found or the information as found in the database
     */
    public function __get($field)
    {
        // ==== Checking if the field exists ==== //
        if(!empty($this->userinfo[$field]))
        {
            return $this->userinfo[$field];
        }
        else
        {
            return false;
        }
    }

    /**
     * The method hides the cookies purpose
     *
     * @param void
     * @return void
     */
    protected function hideCookie()
    {
        $this->options['cookie_name'] = substr(hash('sha512', $this->options['cookie_name']), 0, 10);
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
        $cookie         = sha1(uniqid() . $data['username']);
        $this->cookie  = $cookie;
    }

    /**
     * Creates the cookie on the users computer
     *
     * @param void
     * @return void
     */
    protected function createCookie()
    {
        setcookie($this->options['cookie_name'], $this->cookie, time()+$this->options['cookie_expire'], $this->options['cookie_path'], $this->options['cookie_domain']);
    }

    /**
     * Deletes the cookie from the users computer
     *
     * @param void
     * @return void
     */
    protected function deleteCookie()
    {
        setcookie($this->options['cookie_name'], $this->cookie, time()-$this->options['cookie_expire'], $this->options['cookie_path'], $this->options['cookie_domain']);
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
        $data['cookie'] = $this->cookie;

        // ==== Getting all the headers ==== //
        $headers = get_request_headers();

        // ==== Getting the headers ==== //
        $data['headers'] = base64_encode(serialize($headers['HTTP_USER_AGENT']));

        // ==== Adding log data ==== //
        if($this->options['debug'])
        {
            $this->log .= '<hr><hr><strong>prepareLogin</strong><hr><br />';
            $this->log .= '<strong>Info:</strong><br />';
            $this->log .= 'Cookie name: '.$this->options['cookie_name'].'<br />';
            $this->log .= 'Data: '.print_array($data, 1).'<br />';
            $this->log .= '<br /><br />';
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
        if(!$this->authenticated)
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
                    $result = $this->db->query($sql);

                    // ==== Checking the result ==== //
                    if($result == true)
                    {
                        // ==== Trusted ==== //
                        $this->trusted = true;

                        // ==== Creating the cookie ==== //
                        $this->createCookie();

                        // ==== Authenticating ==== //
                        $this->authenticate($data);
                    }
                    else
                    {
                        // ==== Errors ==== //
                        $this->errors[] = 201;
                    }
                }
                else
                {
                    // ==== Errors ==== //
                    $this->errors[] = 200;
                }
            }
            else
            {
                ////////////////////////////////////////////////////////////////
                // NON-PERSISTENT LOGIN
                ///////////////////////////////////////////////////////////////
                // ==== Trusted ==== //
                $this->trusted = true;

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
            if($this->options['debug'])
            {
                $this->log .= '<hr><hr><strong>login</strong><hr><br />';
                $this->log .= '<strong>NOTICE:</strong> User already logged in.<br /><br />';
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
        if(isset($_COOKIE[$this->options['cookie_name']]))
        {
            // ==== Converting the data type of data ==== //
            $data = array();

            // ==== Getting the IP address ==== //
            $data['ip_addr'] = $_SERVER['REMOTE_ADDR'];

            // ==== Getting the cookie ==== //
            $data['cookie']  = $_COOKIE[$this->options['cookie_name']];

            // ==== Getting all the headers ==== //
            $headers = get_request_headers();

            // ==== Getting the headers ==== //
            $data['headers'] = base64_encode(serialize($headers['HTTP_USER_AGENT']));

            //////////////////////////////////////////////////
            // BEGIN INPUT SANITIZATION
            /////////////////////////////////////////////////
            // == cookie == //
            $data['cookie'] = $this->db->escape_string($data['cookie']);
            //////////////////////////////////////////////////
            // END INPUT SANITIZATION
            /////////////////////////////////////////////////

            // ==== Adding log data ==== //
            if($this->options['debug'])
            {
                $this->log .= '<hr><hr><strong>prepareAuth</strong><hr><br />';
                $this->log .= '<strong>Info:</strong><br />';
                $this->log .= 'Cookie name: '.$this->options['cookie_name'].'<br />';
                $this->log .= 'Data: '.print_array($data, 1).'<br />';
                $this->log .= '<br /><br />';
            }
        }
        else
        {
            // ==== Adding log data ==== //
            if($this->options['debug'])
            {
                $this->log .= '<hr><hr><strong>prepareAuth</strong><hr><br />';
                $this->log .= '<strong>ERROR:</strong> The required data for the prepareAuth method is not present. Required: authentication cookie.<br /><br />';
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
        if((isset($_SESSION['auth']) && $_SESSION['auth'] === false && ($this->trusted === true || isset($_COOKIE[$this->options['cookie_name']]))) || !isset($_SESSION['auth']))
        {
            // ==== Search database flag ===== //
            $checkdb = true;

            // ==== Checking if this was triggered from inside the class ==== //
            if($this->trusted === true)
            {
                // ==== Avoiding the db check ==== //
                $checkdb = false;

                // ==== Untrusting ==== //
                $this->trusted = false;

            }

            ////////////////////////////////////////////////
            // START DB CHECK ONLY AT FIRST ACCESS
            ///////////////////////////////////////////////
            if($checkdb)
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
                        $success = $this->db->query($sql);

                        // ==== Checking if something found ==== //
                        if($success == false || $this->db->num_rows() != 1)
                        {
                            // ==== Adding log data ==== //
                            if($this->options['debug'])
                            {
                                $this->log .= '<hr><hr><strong>authenticate</strong><hr><br />';
                                $this->log .= '<strong>Info:</strong><br />';
                                $this->log .= 'Authentication falied.<br /><br />';
                                $this->log .= '$_SESSION: '.print_array($_SESSION, 1).'<br />';
                                $this->log .= '$_COOKIE: '.print_array($_COOKIE, 1).'<br />';
                                $this->log .= '<br /><br />';
                            }

                            // ==== Removing the cookie ==== //
                            $this->deleteCookie();

                            $isOk = false;
                        }
                        else
                        {
                            // ==== Getting the row info ==== //
                            $row = $this->db->fetch_assoc();

                            // ==== Adding log data ==== //
                            if($this->options['debug'])
                            {
                                $this->log .= '<hr><hr><strong>authenticate</strong><hr><br />';
                                $this->log .= '<strong>Info:</strong> Authenticated user successfully.<br /><br />';
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
                if($this->options['debug'])
                {
                    $this->log .= '<hr><hr><strong>authenticate</strong><hr><br />';
                    $this->log .= '<strong>Info:</strong> Skipped db check.<br /><br />';
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
                if($checkdb === false) // Using data provided by the UserAcc class
                {
                    // ==== Getting the userinfo ==== //
                    $this->userinfo = array('account_id' => $data['account_id']);

                    // ==== Adding the userinfo to the session ==== //
                    $_SESSION['userinfo'] = $this->vault->encrypt(serialize($this->userinfo));
                }
                else // Using data from the database
                {
                    // ==== Getting the userinfo ==== //
                    $this->userinfo = array('account_id' => $row['account_id']);

                    // ==== Adding the userinfo to the session ==== //
                    $_SESSION['userinfo'] = $this->vault->encrypt(serialize($this->userinfo));
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
            if($this->options['debug'])
            {
                $this->log .= '<hr><hr><strong>authenticate</strong><hr><br />';
                $this->log .= '<strong>Info:</strong><br />';
                $this->log .= 'Skipped authentication<br /><br />';
                $this->log .= '$_SESSION: '.print_array($_SESSION, 1).'<br />';
                $this->log .= '$_COOKIE: '.print_array($_COOKIE, 1).'<br />';
                $this->log .= '<br /><br />';
            }
        }

        // ==== Checking if authenticated ==== //
        if($_SESSION['auth'] == true)
        {
            $this->authenticated = true;
        }
        else
        {
            $isOk = false;
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
        setcookie($this->options['cookie_name'], '', -10);

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
        if($this->options['debug'] && $this->log != '')
        {
            // ==== Adding some more data to the log ==== //
            $this->log .= '<hr><hr><strong>Other info</strong><hr>';
            $this->log .= '<strong>URL:</strong><pre>'.getFullURL().'<br /><br />';
            $this->log .= '<strong>GET:</strong><pre>'.print_r($_GET, true).'<br /><br />';
            $this->log .= '<strong>POST:</strong><pre>'.print_r($_POST, true).'<br /><br />';
            $this->log .= '<strong>COOKIE:</strong><pre>'.print_r($_COOKIE, true).'<br /><br />';
            $this->log .= '<strong>HEADERS:</strong><pre>'.print_r(get_request_headers(), true).'<br /><br />';

            // ==== Sending debug mail ==== //
            mail($this->mopt['to'], $this->mopt['subject'], $this->log, $this->mopt['headers']);
        }
    }
}