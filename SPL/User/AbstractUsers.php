<?php
/**
 *
 * The class handles the user accounts
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @uses Password validator object
 *
 * @name AbstractUserAcc
 * @version 2.3
 *
 *
 * Internal errors:
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
 * ========= User handling =========
 *
 * 200 - No info found in the database for the given account ID
 * 210 - The user list could not be retrieved
 * 220 - The user could not be added
 * 230 - The user could not be updated
 * 240 - The user could not be deleted
 *
 */

namespace SPL\User;

use SPL\Url;
use SPL\Validator;
use SPL\Http\Headers as Headers;
use SPL\Security\VaultInterface;

abstract class AbstractUsers implements UsersInterface
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
     * User prototype object
     *
     * @var \SPL\User\User
     */
    protected $userPrototype;

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
     * Sets different class properties and some options
     *
     * @param object $db
     * @param VaultInterface $vault
     * @param array $options
     * @return void
     */
    public function __construct($db, VaultInterface $vault, array $options = array())
    {
        // ==== Default $options ==== //
        $this->options['unique_mail']     = '';
        $this->options['debug']           = false;
        $this->options['mail']            = 'webmaster@' . $_SERVER['HTTP_HOST'];

        // ==== Replacing the internal values with the external ones ==== //
        if(is_array($options) && count($options) > 0)
        {
            $this->options = array_merge($this->options, $options);
        }

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
     * Sets the prototype of the user object
     *
     * @param UserInterface $userPrototype
     * @return UsersInterface
     */
    public function setUserPrototype(UserInterface $userPrototype)
    {
        $this->userPrototype = $userPrototype;
    }

    /**
     * Used to retrieve the errors
     *
     * @param void
     * @return array
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
        $_SESSION = array_merge($_SESSION, $this->session);
    }

    /**
     * Tries to retrieve a certain information about the users account
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
     * Is used to log a message
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
     * Builds the password using a salt and a string
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
     * Returns the SQL for the getSalt method
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
                    while($row = $this->db->fetch_assoc())
                    {
                        $result = $row;
                    }
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
     * Does the login process
     *
     * @param array $data
     * @return mixed false on failure or an user_id on success
     */
    public function checkLogin(array $data)
    {
        // ==== Checking the required fields ==== //
        $result = $this->allowLogin($data);

        // ==== Checking if the required fields are present ==== //
        if($result === true)
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
                            $result = $this->userinfo['user_id'];
                        }
                        else
                        {
                            // ==== Failed ==== //
                            $result = false;

                            // ==== Adding the error ==== //
                            $this->log_message('error', 'User inactive', __METHOD__, 15);
                        }
                    }
                    else
                    {
                        // ==== Failed ==== //
                        $result = false;

                        // ==== Adding the error ==== //
                        $this->log_message('error', 'User with the given login data not found', __METHOD__, 100);
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
     * Build an SQL used for the account info retrieval
     *
     * @param integer $user_id
     * @return string
     */
    protected abstract function sqlUserInfo($user_id);

    /**
     *
     * Retrieves info about a user using the given user_id
     *
     * @param integer $user_id
     * @return mixed false on failure or an array on success
     */
    public function getUserInfo($user_id = 0)
    {
        // ==== Result var ==== //
        $result = false;

        // ==== Getting the account ID if none was provided ==== //
        if(!is_numeric($user_id) || $user_id == 0)
        {
            $user_id = $this->userinfo['user_id'];
        }

        // ==== Checking if we have the required data ==== //
        if(is_numeric($user_id) && $user_id != 0)
        {
            // ==== Getting the SQL ==== //
            $sql = $this->sqlUserInfo($user_id);

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
                    $this->log .= '<b>ERROR:</b> User info retrieval failed.<br />';
                    $this->log .= '<b>QUERY:</b>' . $sql . '<br />';
                    $this->log .= '<b>SQL ERROR:</b>' . $sql_error . '<br /><br />';
                }
            }
        }

        // ==== Result ==== //
        return $result;
    }

    /**
     * SQL builder for the getUserList method
     *
     * @param array $params
     * @return string
     */
    abstract protected function sqlGetUserList($params);

    /**
     * The method retrieves a list of users
     *
     * @param array $params
     * @return mixed False on failure, an empty array if no users were found or an array or \SPL\User\User objects.
     * @throws Exception\InvalidArgumentException|Exception\RuntimeException
     */
    public function getUserList($params = array())
    {
        // Checking if we have a prototype to work with
        if($this->userPrototype instanceof UserInterface == false)
        {
            throw new Exception\RuntimeException('No User class prototype has been set.');
        }

        // Checking if the argument is an array
        if(is_array($params))
        {
            // Result var
            $list = false;

            // Getting the SQL
            $sql = $this->sqlGetUserList($params);

            // Checking if the $sql is not empty
            if(!empty($sql))
            {
                // Running the SQL
                $this->db->query($sql);

                // Getting the SQL error (if any)
                $sql_error = $this->db->error();

                // Checking if we have an error
                if($sql_error == '')
                {
                    // Converting the list variable to an array
                    $list = array();

                    // Checking if any users are in the list
                    if($this->db->num_rows() >= 1)
                    {
                        // Going through the rows
                        while($row = $this->db->fetch_assoc())
                        {
                            // Cloning the prototype object
                            $list[] = $user = clone $this->userPrototype;

                            // Setting the user info
                            $user->setInfo($row);
                        }
                    }
                }
                else
                {
                    // ==== Adding the error ==== //
                    $this->log_message('error', 'The user list could not be retrieved.', __METHOD__, 210);

                    // ==== Debug === //
                    if($this->options['debug'])
                    {
                        $this->log .= '<hr><hr><strong>' . __METHOD__ . '</strong><hr><br />';
                        $this->log .= '<b>ERROR:</b> User list retrieval failed.<br />';
                        $this->log .= '<b>QUERY:</b>' . $sql . '<br />';
                        $this->log .= '<b>SQL ERROR:</b>' . $sql_error . '<br /><br />';
                        $this->log .= '<b>$params:</b><pre>' . print_r($params, 1) . '</pre><br /><br />';
                    }
                }
            }

            // Returning the list
            return $list;
        }

        // Throwing an exception if the parameter is not an array
        throw new Exception\InvalidArgumentException('The $params argument must be an array.');
    }

    /**
     * SQL builder for the userAdd method
     *
     * @param array $params
     * @return string
     */
    abstract protected function sqlUserAdd($params);

    /**
     * The method adds a user to the database
     *
     * @param array $params
     * @return boolean
     * @throws Exception\InvalidArgumentException
     */
    public function userAdd($params = array())
    {
        // Checking if the argument is an array
        if(is_array($params))
        {
            // Result var
            $success = false;

            // Getting the SQL
            $sql = $this->sqlUserAdd($params);

            // Checking if the $sql is not empty
            if(!empty($sql))
            {
                // Running the SQL
                $this->db->query($sql);

                // Getting the SQL error (if any)
                $sql_error = $this->db->error();

                // Checking if we have an error
                if($sql_error != '')
                {
                    // ==== Adding the error ==== //
                    $this->log_message('error', 'The user could not be added', __METHOD__, 220);

                    // ==== Debug === //
                    if($this->options['debug'])
                    {
                        $this->log .= '<hr><hr><strong>' . __METHOD__ . '</strong><hr><br />';
                        $this->log .= '<b>ERROR:</b> User list retrieval failed.<br />';
                        $this->log .= '<b>QUERY:</b>' . $sql . '<br />';
                        $this->log .= '<b>SQL ERROR:</b>' . $sql_error . '<br /><br />';
                        $this->log .= '<b>$params:</b><pre>' . print_r($params, 1) . '</pre><br /><br />';
                    }
                }
                else
                {
                    // Setting the flag
                    $success = true;
                }
            }

            // Returning the result
            return $success;
        }

        // Throwing an exception if the parameter is not an array
        throw new Exception\InvalidArgumentException('The $params argument must be an array.');
    }

    /**
     * SQL builder for the userUpdate method
     *
     * @param array $params
     * @return string
     */
    abstract protected function sqlUserUpdate($params);

    /**
     * The method updates a user from the database
     *
     * @param array $params
     * @return boolean
     * @throws Exception\InvalidArgumentException
     */
    public function userUpdate($params = array())
    {
        // Checking if the argument is an array
        if(is_array($params))
        {
            // Result var
            $success = false;

            // Getting the SQL
            $sql = $this->sqlUserUpdate($params);

            // Checking if the $sql is not empty
            if(!empty($sql))
            {
                // Running the SQL
                $this->db->query($sql);

                // Getting the SQL error (if any)
                $sql_error = $this->db->error();

                // Checking if we have an error
                if($sql_error != '')
                {
                    // ==== Adding the error ==== //
                    $this->log_message('error', 'The user could not be updated', __METHOD__, 230);

                    // ==== Debug === //
                    if($this->options['debug'])
                    {
                        $this->log .= '<hr><hr><strong>' . __METHOD__ . '</strong><hr><br />';
                        $this->log .= '<b>ERROR:</b> User list retrieval failed.<br />';
                        $this->log .= '<b>QUERY:</b>' . $sql . '<br />';
                        $this->log .= '<b>SQL ERROR:</b>' . $sql_error . '<br /><br />';
                        $this->log .= '<b>$params:</b><pre>' . print_r($params, 1) . '</pre><br /><br />';
                    }
                }
                else
                {
                    // Setting the flag
                    $success = true;
                }
            }

            // Returning the result
            return $success;
        }

        // Throwing an exception if the parameter is not an array
        throw new Exception\InvalidArgumentException('The $params argument must be an array.');
    }

    /**
     * SQL builder for the userDelete method
     *
     * @param array $params
     * @return string
     */
    abstract protected function sqlUserDelete($params);

    /**
     * The method delete a user from the database
     *
     * @param array $params
     * @return boolean
     * @throws Exception\InvalidArgumentException
     */
    public function userDelete($params = array())
    {
        // Checking if the argument is an array
        if(is_array($params))
        {
            // Result var
            $success = false;

            // Getting the SQL
            $sql = $this->sqlUserDelete($params);

            // Checking if the $sql is not empty
            if(!empty($sql))
            {
                // Running the SQL
                $this->db->query($sql);

                // Getting the SQL error (if any)
                $sql_error = $this->db->error();

                // Checking if we have an error
                if($sql_error != '')
                {
                    // ==== Adding the error ==== //
                    $this->log_message('error', 'The user could not be deleted', __METHOD__, 240);

                    // ==== Debug === //
                    if($this->options['debug'])
                    {
                        $this->log .= '<hr><hr><strong>' . __METHOD__ . '</strong><hr><br />';
                        $this->log .= '<b>ERROR:</b> User list retrieval failed.<br />';
                        $this->log .= '<b>QUERY:</b>' . $sql . '<br />';
                        $this->log .= '<b>SQL ERROR:</b>' . $sql_error . '<br /><br />';
                        $this->log .= '<b>$params:</b><pre>' . print_r($params, 1) . '</pre><br /><br />';
                    }
                }
                else
                {
                    // Setting the flag
                    $success = true;
                }
            }

            // Returning the result
            return $success;
        }

        // Throwing an exception if the parameter is not an array
        throw new Exception\InvalidArgumentException('The $params argument must be an array.');
    }

    /**
     *
     * Updates the local account info
     *
     * @param integer $userinfo
     * @return mixed false on failure or an array on success
     */
    public function setUserInfo($userinfo)
    {
        $this->userinfo = $userinfo;
    }

    /**
     * Checks if the username exits
     *
     * @param string $username
     * @return boolean
     */
    protected abstract function doesUsenameExist($username);

    /**
     * Checks if the email exits
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

            // Creating the password validator object
            $passwdValidator = new Validator\Password();

            // ===== Checking the complexity ==== //
            $complexityOk = $passwdValidator->isValid($data['passwd']);
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
            $valid = Validator\Email::isValid($data['email'], true);
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
     * Prepares the SQL for the login and the data that is required for the query
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
     * Takes care of the registration process
     *
     * @param array $data
     * @return mixed true on success of an error number on fail
     */
    public function doRegister(array $data)
    {
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
     * Password recovery conditions
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
     * Used to retrieve the SQL for the retrive password process
     *
     * @param array $data
     * @return string
     */
    protected abstract function sqlRecovery(array $data);

    /**
     * Takes care of Stage 1 of the password recovery process
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
            $this->log .= '<strong>ERRORS:</strong><pre>'.print_r($this->errors, true).'<br /><br />';
            $this->log .= '<strong>URL:</strong><pre>' . Url\Url::getFullURL() . '<br /><br />';
            $this->log .= '<strong>GET:</strong><pre>'.print_r($_GET, true).'<br /><br />';
            $this->log .= '<strong>POST:</strong><pre>'.print_r($_POST, true).'<br /><br />';
            $this->log .= '<strong>SESSION:</strong><pre>'.print_r($this->session, true).'<br /><br />';
            $this->log .= '<strong>COOKIE:</strong><pre>'.print_r($_COOKIE, true).'<br /><br />';
            $this->log .= '<strong>HEADERS:</strong><pre>'.print_r(Headers::request(), true).'<br /><br />';
            $this->log .= '<strong>SERVER:</strong><pre>'.print_r($_SERVER, true).'<br /><br />';

            // Mail options
            $to      = $this->options['mail'];
            $subject = '[DEBUG] ' . __CLASS__ . ' Class ' . $this->options['unique_mail'];
            $headers = 'MIME-Version: 1.0' . "\r\n" . 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

            // ==== Sending debug mail ==== //
            mail($to, $subject, $this->log, $headers);
        }
    }
}