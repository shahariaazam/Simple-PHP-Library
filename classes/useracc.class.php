<?php
/**
 *
 * The class handles the user accounts
 *
 * @author Brian
 * @link http://brian.hopto.org/framework_wik/
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name UserAcc
 * @version 1.3
 *
 * @uses getFullURL function from functions/common.inc.php
 * @uses ckPasswdComplexity function from functions/common.inc.php
 *
 * Internal errors:
 *
 * ========= Login data errors =========
 * 
 * 1 - Username empty
 * 5 - Password empty
 * 15 - Account inactive
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
 * ========= Recover password data errors =========
 *
 * 160 - Data required for password recovery was not found or improper format
 *
 *
 * ========= Salt errors =========
 *
 * 150 - Salt data is not present or incorrect
 *
 *
 * ========= Database errors =========
 *
 * 100 - Account with the given login data not found
 * 101 - Salt could not be retrieved from the database because it could not be found aka Account invalid
 * 102 - Could not register account because query failed
 * 103 - Could not do login because query failed
 *
 *
 * 
 * External errors from the UserAuth class:
 *
 * ========= Data errors =========
 *
 * 200 - Internal class error (used to signal the UserAcc class that an error has occured)
 * 201 - Could not insert authentication info into the database
 *
 */

class UserAcc
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
     * User authentication object
     *
     * @var object
     */
    protected $auth;

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
     * Class constructor.
     *
     * @param object $db
     * @param object $auth
     * @param array $options
     * @return void
     */
    public function __construct(db_module $db, UserAuth $auth, Vault $vault, array $options=array())
    {
        // ==== Default $options ==== //
        $this->options['unique_mail']     = '';
        $this->options['debug']           = false;
        $this->options['mail']            = 'webmaster@' . $_SERVER['HTTP_HOST'];

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

        // ==== Initializing the authentication object ==== //
        $this->auth = $auth;

        // ==== Initializing the database object ==== //
        $this->db = $db;

        // ==== Initializing the vault object ==== //
        $this->vault = $vault;
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
     * The method builds the password using a salt and a string
     *
     * @param string $salt
     * @param string $passwd
     * @return string
     */
    protected function buildPasswd($salt, $passwd)
    {
        $passwd = hash('sha512', $salt.$this->vault->encrypt($passwd));

        return $passwd;
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
     * Conditions to allow the salt to be extracted
     *
     * @param array $data
     * @return boolean
     */
    protected function allowSalt(array $data)
    {
        // ==== Result variable ==== //
        $result = true;

        //////////////////////////////////////////////////
        // BEGIN INPUT SANITIZATION
        /////////////////////////////////////////////////
        // == username == //
        if(!empty($data['username']))
        {
            $data['username'] = $this->db->escape_string($data['username']);
        }
        //////////////////////////////////////////////////
        // END INPUT SANITIZATION
        /////////////////////////////////////////////////

        //////////////////////////////////////////////////
        // BEGIN REQUIRED FIELDS CHECKS
        /////////////////////////////////////////////////
        // == Username == //
        if(!isset($data['username']))
        {
            // ===== Errors ==== //
            $this->errors[] = 150;
            $result = false;
        }
        //////////////////////////////////////////////////
        // END REQUIRED FIELDS CHECKS
        /////////////////////////////////////////////////

        // ==== Result ==== //
        return $result;
    }

    /**
     * The method returns the SQL for the getSalt method
     *
     * @param array $data
     * @return string
     */
    protected function sqlSalt(array $data)
    {
        /**
         * ----------------------
         * OVERWRITE THIS METHOD
         * ----------------------
         * 
         */
    }

    /**
     * The method retrieves the salt for a given account
     *
     * @param array $data
     * @return string or false on failure
     */
    protected function getSalt(array $data, $fromdb = true)
    {
        // ==== Result var ==== //
        $result = false;

        // ==== Checking if we should get the salt from the database ==== //
        if($fromdb === true)
        {
            // ==== Checking if the username is set ==== //
            if($this->allowSalt($data))
            {
                // ==== Building the SQL ==== //
                $sql = $this->sqlSalt($data);

                // ==== executing the SQL ==== //
                $this->db->query($sql);

                // ==== checking if data was found ==== //
                if($this->db->num_rows() == 1)
                {
                    // ==== Retrieving the regdate info === //
                    $regdate = $this->db->result(0, 0);

                    // ==== The salt ==== //
                    $result = $regdate;
                }
                else
                {
                    // ==== Failed ==== //
                    $result = false;

                    // ==== Errors ==== //
                    $this->errors[] = 101;
                }
            }
        }
        else
        {
            // ==== the salt ==== //
            $result = date('Y-m-d H:i:s', time());
        }

        // ==== returning result ==== //
        return $result;
    }

    /**
     * Login conditions
     *
     * @param array $data
     * @return mixed array of errors or true on success
     */
    protected function allowLogin(array $data)
    {
        // ==== Result variable ==== //
        $result = true;

        //////////////////////////////////////////////////
        // BEGIN INPUT SANITIZATION
        /////////////////////////////////////////////////
        // == Username == //
        if(!empty($data['username']))
        {
            $data['username'] = $this->db->escape_string($data['username']);
        }

        // == Password == //
        if(!empty($data['passwd']))
        {
            $data['passwd'] = $this->db->escape_string($data['passwd']);
        }
        //////////////////////////////////////////////////
        // END INPUT SANITIZATION
        /////////////////////////////////////////////////

        //////////////////////////////////////////////////
        // BEGIN REQUIRED FIELDS CHECKS
        /////////////////////////////////////////////////
        // == username == //
        if(empty($data['username']))
        {
            $this->errors[] = 1;
            $result = false;
        }

        // == password == //
        if(empty($data['passwd']))
        {
            $this->errors[] = 5;
            $result = false;
        }
        //////////////////////////////////////////////////
        // END REQUIRED FIELDS CHECKS
        /////////////////////////////////////////////////

        // ==== result ==== //
        return $result;
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
     * This method prepares the data for the login SQL and returns the SQL
     *
     * @param array $data
     * @return array
     */
    protected function prepareLogin(array $data)
    {
        // ==== result variable ==== //
        $result = true;

        // ==== Getting the salt ==== //
        $salt = $this->getSalt($data);

        // ==== Checking if salt retrieved successfully ==== //
        if($salt !== false)
        {
            // ==== Building the password hash ==== //
            $data['passwd'] = $this->buildPasswd($salt, $data['passwd']);
        }
        else
        {
            // ==== Failed ==== //
            $result = false;
        }

        // ==== Result ==== //
        if($result == true)
        {
            return $data;
        }
        else
        {
            return $result;
        }
    }

    /**
     * The method does the login process
     *
     * @param array $data
     * @return true on success or an error number on failure
     */
    public function doLogin(array $data)
    {
        // ==== Result variable ==== //
        $result = true;

        // ==== Checking the required fields ==== //
        $result = $this->allowLogin($data);

        // ==== Checking if the required fields are present ==== //
        if($result == true)
        {
            // ==== Getting the SQL ==== //
            $data = $this->prepareLogin($data);

            // ==== Checking the data ==== //
            if($data !== false)
            {
                // ==== Getting the SQL ==== //
                $sql = $this->sqlLogin($data);

                // ==== Executing the SQL ==== //
                $this->db->query($sql);

                // ==== Checking if an error occured ==== //
                if($this->db->error() == '')
                {
                    // ==== Checking if info was found ==== //
                    if($this->db->num_rows() == 1)
                    {
                        // ==== Getting the query row results ==== //
                        $row = $this->db->fetch_assoc();

                        // ==== Checking if the account is active ==== //
                        if($row['active'] == 1)
                        {
                            // ==== Getting the account ID ==== //
                            $data['account_id'] = $row['account_id'];

                            // ==== Checking if persistent login was requested ==== //
                            if(isset($data['rememberlogin']) && $data['rememberlogin'] == true)
                            {
                                $persistent = true;
                            }
                            else
                            {
                                $persistent = false;
                            }

                            // ==== Letting the authentication class handle the rest of the login ==== //
                            $auth_result = $this->auth->login($data, $persistent);

                            // ==== Checking the auth result ==== //
                            if($auth_result == false)
                            {
                                $result = false;

                                // ==== Merging the errors ==== //
                                $this->errors = array_merge($this->errors, $this->auth->getErrors());
                            }
                            else
                            {
                                // ==== Adding the account id to the session ==== //
                                $_SESSION['userid'] = $data['account_id'];
                            }
                        }
                        else
                        {
                            // ==== Failed ==== //
                            $result = false;

                            // ==== Error ==== //
                            $this->errors[] = 15;
                        }
                    }
                    else
                    {
                        // ==== Failed ==== //
                        $result = false;

                        // ==== Error ==== //
                        $this->errors[] = 100;
                    }
                }
                else
                {
                    // ==== Failed ==== //
                    $result = false;

                    // ==== Error ==== //
                    $this->errors[] = 103;
                }
            }
            else
            {
                // ==== Failed ==== //
                $result = false;

                // No error message required because it is triggered by the getSalt method
            }
        }

        // ==== Returning result ==== //
        return $result;
    }


    /**
     * The method does the logout process. Mostly wrapper function.
     *
     * @param void
     * @return boolean
     */
    public function doLogout()
    {
        // ==== Logging out ==== //
        $this->auth->logout();
    }

    /**
     * The method checks if the username exits
     *
     * @param string $username
     * @return boolean
     */
    protected function doesUsenameExist($username)
    {
        /**
         * ----------------------
         * OVERWRITE THIS METHOD
         * ----------------------
         *
         */
    }

    /**
     * The method checks if the email exits
     *
     * @param string $email
     * @return boolean
     */
    protected function doesEmailExist($email)
    {
        /**
         * ----------------------
         * OVERWRITE THIS METHOD
         * ----------------------
         *
         */
    }

    /**
     * Register conditions
     *
     * @param array $data
     * @return boolean
     */
    protected function allowRegister(array $data)
    {
        // ==== result variable ==== //
        $result = true;

        //////////////////////////////////////////////////
        // BEGIN INPUT SANITIZATION
        /////////////////////////////////////////////////
        // == username == //
        if(!empty($data['username']))
        {
            $data['username'] = $this->db->escape_string($data['username']);
        }

        // == password == //
        if(!empty($data['passwd']))
        {
            $data['passwd'] = $this->db->escape_string($data['passwd']);
        }

        // == email == //
        if(!empty($data['email']))
        {
            $data['email'] = $this->db->escape_string($data['email']);
        }
        //////////////////////////////////////////////////
        // END INPUT SANITIZATION
        /////////////////////////////////////////////////

        //////////////////////////////////////////////////
        // BEGIN REQUIRED FIELDS CHECKS
        /////////////////////////////////////////////////
        // == username == //
        if(empty($data['username']))
        {
            $this->errors[] = 20;
            $result = false;
        }
        else
        {
            // ==== Checking if the username exists in the database ==== //
            if($this->doesUsenameExist($data['username']))
            {
                $this->errors[] = 21;
                $result = false;
            }
        }

        // == password == //
        if(empty($data['passwd']))
        {
            $this->errors[] = 25;
            $result = false;
        }
        else
        {
            // ==== Password complexity options ==== //
            $options = array();

            // ===== Checking the complexity ==== //
            $complexityOk = ckPasswdComplexity($data['passwd']);
            if($complexityOk == false)
            {
                $this->errors[] = 26;
                $result = false;
            }

        }

        // == email == //
        if(empty($data['email']))
        {
            $this->errors[] = 27;
            $result = false;
        }
        else
        {
            // ==== Checking if the email is valid ==== //
            $valid = validateMail($data['email'], true);
            if($valid == false)
            {
                $this->errors[] = 28;
                $result = false;
            }
            else
            {
                // ==== Checking if email exists ==== //
                if($this->doesEmailExist($data['email']))
                {
                    $this->errors[] = 29;
                    $result = false;
                }
            }
        }
        //////////////////////////////////////////////////
        // END REQUIRED FIELDS CHECKS
        /////////////////////////////////////////////////

        // ==== result ==== //
        return $result;
    }

    /**
     * Register SQL
     *
     * @param array $data
     * @return array
     */
    protected function sqlRegister(array $data)
    {
        /**
         * ----------------------
         * OVERWRITE THIS METHOD
         * ----------------------
         *
         */
    }

    /**
     * This method prepares the SQL for the login and the data that is required for the query
     *
     * @param array $data
     * @return string
     */
    protected function prepareRegister(array $data)
    {
        // ==== result variable ==== //
        $result = true;

        // ==== Getting the salt ==== //
        $data['regdate'] = $this->getSalt($data, false);

        // ==== Checking if salt retrieved successfully ==== //
        if($data['regdate'] !== false)
        {
            // ==== Building the password hash ==== //
            $data['passwd'] = $this->buildPasswd($data['regdate'], $data['passwd']);
        }
        else
        {
            // ==== Failed ==== //
            $result = false;
        }

        // ==== Result ==== //
        if($result == true)
        {
            return $data;
        }
        else
        {
            return $result;
        }
    }

    /**
     * The method takes care of the registration process
     *
     * @param array $data
     * @return true on success of an error number on fail
     */
    public function doRegister(array $data)
    {
        // ==== Result variable ==== //
        $result = true;

        // ==== Checking the required fields ==== //
        $result = $this->allowRegister($data);

        // ==== Checking if the required fields are present ==== //
        if($result == true)
        {
            // ==== Getting the SQL ==== //
            $data = $this->prepareRegister($data);

            // ==== Checking the data ==== //
            if($data !== false)
            {
                // ==== Getting the SQL ==== //
                $sql = $this->sqlRegister($data);

                // ==== Executing the SQL ==== //
                $this->db->query($sql);

                // ==== Checking if an error occured ==== //
                if($this->db->error() != '')
                {
                    // ==== Error ==== //
                    $this->errors[] = 102;
                }
            }
            else
            {
                // ==== Failed ==== //
                $result = false;
            }
        }
        else
        {
            // ==== Failed ==== //
            $result = false;
        }

        // ==== Returning result ==== //
        return $result;
    }

    /**
     * The method checks the password recovery should be allowed
     *
     * @param array $data
     * @return boolean
     */
    protected function allowRecovery(array $data)
    {
        // ==== Result variable ==== //
        $result = true;

        //////////////////////////////////////////////////
        // BEGIN INPUT SANITIZATION
        /////////////////////////////////////////////////
        // == Email == //
        if(!empty($data['email']))
        {
            $data['email'] = $this->db->escape_string($data['email']);
        }
        //////////////////////////////////////////////////
        // END INPUT SANITIZATION
        /////////////////////////////////////////////////

        //////////////////////////////////////////////////
        // BEGIN REQUIRED FIELDS CHECKS
        /////////////////////////////////////////////////
        // ==== Email ==== //
        if(empty($data['email']))
        {
            // ==== Errors ==== //
            $this->errors[] = 160;
            $result = false;
        }
        //////////////////////////////////////////////////
        // END REQUIRED FIELDS CHECKS
        /////////////////////////////////////////////////

        // ==== Result ==== //
        return $result;
    }

    /**
     * The function takes care of Stage 1 of the password recovery process
     *
     * @param array $data
     * @return boolean
     */
    public function recoverPassword(array $data)
    {
        // ===== Result variable ==== //
        $result = true;

        // ==== Checking if the password recovery should be allowed ==== //
        if($this->allowRecovery($data))
        {
            // ===== Getting the SQL ===== //
            $sql = $this->sqlRecovery($data);
        }
        else
        {
            $result = false;
        }

        // ==== Returning result ==== //
        return $result;
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
            $this->log .= 'Other info<hr>';
            $this->log .= '<strong>URL:</strong><pre>'.getFullURL().'<br /><br />';
            $this->log .= '<strong>ERRORS:</strong><pre>'.print_r($this->errors, true).'<br /><br />';
            $this->log .= '<strong>GET:</strong><pre>'.print_r($_GET, true).'<br /><br />';
            $this->log .= '<strong>POST:</strong><pre>'.print_r($_POST, true).'<br /><br />';

            // ==== Sending debug mail ==== //
            mail($this->mopt['to'], $this->mopt['subject'], $this->log, $this->mopt['headers']);
        }
    }
}