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
 * @name BaseUserAcc
 * @version 2.0
 *
 * @uses getFullURL function from functions/common.inc.php
 * @uses ckPasswdComplexity function from functions/common.inc.php
 *
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
 * ========= Database errors =========
 *
 * 100 - Account with the given login data not found
 * 101 - Salt could not be retrieved from the database because it could not be found
 * 102 - Could not register account because query failed
 * 103 - Could not do login because query failed
 * 104 - Could not retrieve the account info for the given account id
 *
 *
 * ========= Salt errors =========
 *
 * 150 - Data required for salt retrieval is not present
 * 
 *
 * ========= Recover password data errors =========
 *
 * 160 - Data required for password recovery was not found or improper format
 * 161 - An error occured while trying to recover your password
 *
 *
 * ========= Account handling =========
 *
 * 200 - No info found in the database for the given account ID
 * 
 */

abstract class BaseUserAcc
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
     * Array with the userinfo
     *
     * @var array
     */
    protected $userinfo = array();


    /**
     * Class constructor.
     *
     * @param object $db
     * @param object $vault
     * @param array $options
     * @return void
     */
    public function __construct($db, Vault $vault, array $options=array())
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

        // ==== Getting default values ==== //
        $this->log = '';

        // ==== Getting the database object ==== //
        $this->db = $db;

        // ==== Getting the vault object ==== //
        $this->vault = $vault;
        
        // ==== Getting the session data ==== //
        $this->getSession();
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
     * The method retrieves the data from the session
     * 
     * @param void
     * @return void
     */
    protected function getSession()
    {
        $this->session = $_SESSION;
    }
    
    /**
     * The method sets the data to the session
     * 
     * @param void
     * @return void
     */
    protected function setSession()
    {
        $_SESSION = array_merge($_SESSION, $this->session);
    }

    /**
     *
     * The method tries to retrieve a certain information about the users account
     *
     * @param string $field
     * @return mixed It returns NULL if the information is not found or it returns the information as found in the database
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
            return NULL;
        }
    }
    
    /**
     * The method is used to log a message
     * 
     * @param string $type
     * @param string $message
     * @param string $location
     * @param string $number
     * @param string $extra1
     * @param string $extra2
     * @return void
     */
     protected function log_message($type, $message, $location = '', $number=0, $extra1 = '', $extra2 = '')
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
        if(empty($data['username']))
        {         
            // ==== Adding the error ==== //
            $this->log_message('error', 'Data required for salt retrieval is not present', __METHOD__, 150);
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
    protected abstract function sqlSalt(array $data);

    /**
     * The method retrieves the salt for a given account
     *
     * @param array $data
     * @param boolean $generate
     * @return string or false on failure
     */
    protected function getSalt(array $data, $generate = false)
    {
        // ==== Result var ==== //
        $result = false;

        // ==== Checking if we should get the salt from the database ==== //
        if($generate === false)
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
                    $result = $this->db->result(0, 0);
                }
                else
                {
                    // ==== Failed ==== //
                    $result = false;
                    
                    // ==== Adding the error ==== //
                    $this->log_message('error', 'Salt could not be retrieved from the database because it could not be found', __METHOD__, 101);
                }
            }
        }
        else
        {
            // ==== The salt ==== //
            $result = date('Y-m-d H:i:s', time());
        }

        // ==== Returning result ==== //
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
            // ==== Adding the error ==== //
            $this->log_message('error', 'Username empty', __METHOD__, 1);
            $result = false;
        }

        // == password == //
        if(empty($data['passwd']))
        {
            // ==== Adding the error ==== //
            $this->log_message('error', 'Password empty', __METHOD__, 5);
            $result = false;
        }
        //////////////////////////////////////////////////
        // END REQUIRED FIELDS CHECKS
        /////////////////////////////////////////////////

        // ==== Result ==== //
        return $result;
    }

    /**
     * Login SQL
     *
     * @param array $data
     * @return string
     */
    protected abstract function sqlLogin(array $data);

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
     * @return mixed false on failure or an account_id on success
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

                // ==== Getting the SQL error ==== //
                $sql_error = $this->db->error();

                // ==== Checking if an error occured ==== //
                if($sql_error == '')
                {
                    // ==== Checking if info was found ==== //
                    if($this->db->num_rows() == 1)
                    {
                        // ==== Getting the query row results ==== //
                        $row = $this->db->fetch_assoc();

                        // ==== Checking if the account is active ==== //
                        if($row['active'] == 1)
                        {
                            // ==== Getting the user info ==== //
                            $this->userinfo = $row;

                            // ==== Getting the account ID ==== //
                            $result = &$row['account_id'];
                        }
                        else
                        {
                            // ==== Failed ==== //
                            $result = false;
                            
                            // ==== Adding the error ==== //
                            $this->log_message('error', 'Account inactive', __METHOD__, 15);
                        }
                    }
                    else
                    {
                        // ==== Failed ==== //
                        $result = false;
                        
                        // ==== Adding the error ==== //
                        $this->log_message('error', 'Account with the given login data not found', __METHOD__, 100);
                    }
                }
                else
                {
                    // ==== Failed ==== //
                    $result = false;
                                       
                    // ==== Adding the error ==== //
                    $this->log_message('sql', 'Could not do login because query failed', __METHOD__, 103, $sql, $sql_error);
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
     *
     * The method build an SQL used for the account info retrieval
     *
     * @param integer $account_id
     * @return string
     */
    protected abstract function sqlAccountInfo($account_id);

    /**
     *
     * The method retrieves info about a user using the given account_id
     *
     * @param integer $account_id
     * @return mixed false on failure or an array on success
     */
    public function getAccountInfo($account_id=0)
    {
        // ==== Result var ==== //
        $result = false;

        // ==== Getting the account ID if none was provided ==== //
        if(!is_numeric($account_id) || $account_id == 0)
        {
            $account_id = $this->userinfo['account_id'];
        }

        // ==== Checking if we have the required data ==== //
        if(is_numeric($account_id) && $account_id != 0)
        {
            // ==== Getting the SQL ==== //
            $sql = $this->sqlAccountInfo($account_id);

            // ==== running the sql ==== //
            $this->db->query($sql);

            // ==== Getting the SQL error ==== //
            $sql_error = $this->db->error();

            // ==== checking for errors ==== //
            if($sql_error == '')
            {
                // ==== checking if anything was found ==== //
                if($this->db->num_rows() == 1)
                {
                    // ==== getting the row ==== //
                    $row = $this->db->fetch_assoc();

                    // ==== Updating the userinfo ==== //
                    $this->userinfo = $row;

                    // ==== Updating the result variable ==== //
                    $result = &$row;
                }
                else
                {                  
                    // ==== Adding the error ==== //
                    $this->log_message('error', 'No info found in the database for the given account ID', __METHOD__, 200);
                }
            }
            else
            {              
                // ==== Adding the error ==== //
                $this->log_message('error', 'Could not retrieve the account info for the given account id', __METHOD__, 104);

                // ==== Debug === //
                if($this->options['debug'])
                {
                    $this->log .= '<hr><hr><strong>' . __METHOD__ . '</strong><hr><br />';
                    $this->log .= '<b>ERROR:</b> Account info retrieval failed.<br />';
                    $this->log .= '<b>QUERY:</b>' . $sql . '<br />';
                    $this->log .= '<b>SQL ERROR:</b>' . $sql_error . '<br /><br />';
                }
            }
        }

        // ==== Result ==== //
        return $result;
    }

    /**
     *
     * The method updates the local account info
     *
     * @param integer $userinfo
     * @return mixed false on failure or an array on success
     */
    public function setAccountInfo($userinfo)
    {
        $this->userinfo = $userinfo;
    }

    /**
     * The method checks if the username exits
     *
     * @param string $username
     * @return boolean
     */
    protected abstract function doesUsenameExist($username);

    /**
     * The method checks if the email exits
     *
     * @param string $email
     * @return boolean
     */
    protected abstract function doesEmailExist($email);

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
            // ==== Adding the error ==== //
            $this->log_message('error', 'Username field empty', __METHOD__, 20);
            $result = false;
        }
        else
        {
            // ==== Checking if the username exists in the database ==== //
            if($this->doesUsenameExist($data['username']))
            {
                // ==== Adding the error ==== //
                $this->log_message('error', 'Username exists', __METHOD__, 21);
                $result = false;
            }
        }

        // == password == //
        if(empty($data['passwd']))
        {
            // ==== Adding the error ==== //
            $this->log_message('error', 'Password field empty', __METHOD__, 25);
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
                // ==== Adding the error ==== //
                $this->log_message('error', 'Password complexity too low', __METHOD__, 26);
                $result = false;
            }

        }

        // == email == //
        if(empty($data['email']))
        {
            // ==== Adding the error ==== //
            $this->log_message('error', 'Email field empty', __METHOD__, 27);
            $result = false;
        }
        else
        {
            // ==== Checking if the email is valid ==== //
            $valid = validateMail($data['email'], true);
            if($valid == false)
            {
                // ==== Adding the error ==== //
                $this->log_message('error', 'Email is invalid', __METHOD__, 28);
                $result = false;
            }
            else
            {
                // ==== Checking if email exists ==== //
                if($this->doesEmailExist($data['email']))
                {                   
                    // ==== Adding the error ==== //
                    $this->log_message('error', 'Email exists', __METHOD__, 29);
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
    protected abstract function sqlRegister(array $data);

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
     * @return mixed true on success of an error number on fail
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

                // ==== Getting the SQL error ==== //
                $sql_error = $this->db->error();

                // ==== Checking if an error occured ==== //
                if($sql_error != '')
                {                   
                    // ==== Adding the error ==== //
                    $this->log_message('sql', 'Could not register account because query failed', __METHOD__, 102, $sql, $sql_error);
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
            // ==== Adding the error ==== //            
            $this->log_message('error', 'Data required for password recovery is not present', __METHOD__, 160);
            $result = false;
        }
        //////////////////////////////////////////////////
        // END REQUIRED FIELDS CHECKS
        /////////////////////////////////////////////////

        // ==== Result ==== //
        return $result;
    }

    /**
     * The method is used to retrieve the SQL for the retrive password process
     *
     * @param array $data
     * @return string
     */
    protected abstract function sqlRecovery(array $data);

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
            
            // ==== Getting the last error (if any) ==== //
            $sql_error = $this->db->error();
            
            // ==== Checking if an error occured ==== //
            if($sql_error == '') // No error
            {
                // ==== Getting the data from the database ==== //
                $info = $this->db->fetch_assoc();
            }
            else
            {               
                // ==== Adding the error ==== //
                $this->log_message('sql', 'An error occured while trying to recover your password', __METHOD__, 161, $sql, $sql_error);
            }
        }
        else
        {
            $result = false;
        }

        // ==== Returning result ==== //
        return $result;
    }
    
    /**
     * The method generates a new password

    /**
     * Class destructor
     *
     * @param void
     * @return void
     */
    public function __destruct()
    {
        // ==== Saving the session info ==== //
        $this->setSession();
        
        // ==== Debug ==== //
        if($this->options['debug'] && $this->log != '')
        {
            // ==== Adding some more data to the log ==== //
            $this->log .= '<hr><hr><strong>Other info</strong><hr>';
            $this->log .= '<strong>ERRORS:</strong><pre>'.print_r($this->errors, true).'<br /><br />';
            $this->log .= '<strong>URL:</strong><pre>'.getFullURL().'<br /><br />';
            $this->log .= '<strong>GET:</strong><pre>'.print_r($_GET, true).'<br /><br />';
            $this->log .= '<strong>POST:</strong><pre>'.print_r($_POST, true).'<br /><br />';
            $this->log .= '<strong>SESSION:</strong><pre>'.print_r($this->session, true).'<br /><br />';
            $this->log .= '<strong>COOKIE:</strong><pre>'.print_r($_COOKIE, true).'<br /><br />';
            $this->log .= '<strong>HEADERS:</strong><pre>'.print_r(get_request_headers(), true).'<br /><br />';
            $this->log .= '<strong>SERVER:</strong><pre>'.print_r($_SERVER, true).'<br /><br />';

            // ==== Sending debug mail ==== //
            mail($this->mopt['to'], $this->mopt['subject'], $this->log, $this->mopt['headers']);
        }
    }
}