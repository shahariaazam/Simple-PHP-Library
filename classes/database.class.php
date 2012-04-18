<?php
/**
 *
 * Enhanced CrossDatabase Engine. Default database engine is mysql
 *
 * @author Brian
 * @link http://brian.hopto.org/framework_wiki/
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name Database
 * @version 3.4
 *
 */

///////////////////////////////////////////////////////////////////////
//  Database Module Interface                                       //
/////////////////////////////////////////////////////////////////////
interface db_module
{
    /**
     * Method that connects to the database
     *
     * @param void
     * @return boolean
     */
    public function connect();

    /**
     * Method that disconnects from the database
     *
     * @param void
     * @return boolean
     */
    public function disconnect();

    /**
     * The method executes a query
     *
     * @param string $query
     * @return boolean
     */
    public function query($query);

    /**
     * The method executes a select query
     *
     * @param array $tables
     * @param string $where
     * @param string $fields
     * @param int $limit
     * @param int $offset
     */
//    public function select(array $tables, $where = '', $fields = '*', $order = '', $limit = null, $offset = null)

    /**
     * The method returns a single row and/or field from the query
     *
     * @param integer $row
     * @param integer $field
     * @return mixed false if unsuccessfull or if $row/$field is not numeric and result on success
     */
    public function result($row=0, $field=0);

    /**
     * The method counts the number of rows in the current query
     *
     * @param void
     * @return mixed false on fail or number of rows on success
     */
    public function num_rows();

    /**
     * The method counts the number of affected rows in the current query
     *
     * @param void
     * @return mixed false on fail or number of affected rows if successfull
     */
    public function affected_rows();

    /**
     * The method is returns an associative array from the current query
     *
     * @param void
     * @return mixed false on fail or array on success
     */
    public function fetch_assoc();

    /**
     * The method is returns an associative array, a numeric array, or both from the current query
     *
     * @param void
     * @return mixed false on fail or array on success
     */
    public function fetch_array();

    /**
     * The method returns a single row from the query
     *
     * @param integer
     * @return mixed false on fail or string on success
     */
    public function fetch_row($row=0);

    /**
     * Escapes special characters in a string for use in a SQL statement
     *
     * @param string $string
     * @return mixed false on fail or escaped string on success
     */
    public function escape_string($string);

    /**
     * To avoid getting the wrong last id the method executes the query itself and then returns the last id
     *
     * @param string $query
     * @param string $autoincrementField //This is important for compatibility with PostgreSQL
     * @return mixed false on fail or integer on success
     */
    public function last_id($query, $autoIncrementField);

    /**
     * The method returns the last error mysql threw
     *
     * @param void
     * @return string Returns the last error text or '' (empty string) if no error occurred or no resource found
     */
    public function error();
}

////////////////////////////////////////////////////////////////////////////
//  Database Initializor                                                 //
//////////////////////////////////////////////////////////////////////////
abstract class Database
{

    /**
     * Supported database types
     *
     * @var array
     */
    private static $supported = array('mysql', 'pgsql', 'mysql_i', 'dbase');

    /**
     * Instance identifier
     *
     * @var object
     */
    private static $instance;

    /**
     * Singleton initiator
     *
     * @param void
     * @return object on success or integer on fail: 2 for wrong options, 3 for unsupported database type
     */
    public static function init($options=array(), $new=false)
    {
        // ==== Error code ==== //
        $error_code = false;

        // ==== Getting database type to initialize ==== //
        if(isset($options['type']))
        {
            $type = $options['type'];
        }
        else
        {
            $type = 'mysql';
        }

        // ==== Creating database object using Singleton ==== //
        if(!isset(self::$instance) || $new == true)
        {
            // ==== Checking if the database type is supported ==== //
            if(self::isSupported($type))
            {
                // ==== Correcting the type name ==== //
                $class = ucfirst($type);

                // ==== Checking if the $options parameter is an array ==== //
                if(is_array($options))
                {
                    self::$instance = new $class($options);
                }
                else
                {
                    $error_code = 2;
                }
            }
            else
            {
                $error_code = 3;
            }
        }

        // ==== Checking if a database instance has been created ==== //
        if(is_object(self::$instance))
        {
            return self::$instance;
        }
        else
        {
            return $error_code;
        }
    }

    /**
     * The method checks if the database type is supported
     *
     * @param string type
     * @return boolean
     */
    private static function isSupported($type)
    {
        if(in_array($type, self::$supported))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

}

/////////////////////////////////////////////////////////////////////////////////////////////////
//              MySQL Database Class                                                          //
///////////////////////////////////////////////////////////////////////////////////////////////
class Mysql implements db_module
{

    /**
     * Options array
     *
     * @var array
     */
    private $options;

    /**
     * Resource variable
     *
     * @var resource
     */
    private $resource;

    /**
     * Database link identifier
     *
     * @var link_indentifier
     */
    private $link_id;

    /**
     * Determins if there was a connection attempt
     *
     * @var boolean
     */
    private $conn_trigger = false;
    

    /**
     * Class constructor
     *
     * @param array $options
     * @return void
     */
    public function __construct($options)
    {
        // ==== Default options ==== //
        $this->options['db']     = 'default';
        $this->options['host']   = 'localhost';
        $this->options['port']   = '3306';
        $this->options['user']   = 'root';
        $this->options['passwd'] = '';

        // ==== Replacing options with custom ones ==== //
        if(is_array($options))
        {
            $this->options = array_replace($this->options, $options);
        }
    }

    /**
     * Method that connects to the database
     *
     * @param void
     * @return boolean
     */
    public function connect()
    {
        // ==== A connection was attempted ==== //
        $this->conn_trigger = true;

        if(!$this->link_id = mysql_connect($this->options['host'] . ':' . $this->options['port'], $this->options['user'], $this->options['passwd']))
        {
            return false;
        }
        else
        {
            if(!mysql_select_db($this->options['db'], $this->link_id))
            {
                return false;
            }
            else
            {
                return true;
            }
        }
    }

    /**
     * Method that disconnects from the database
     *
     * @param void
     * @return boolean
     */
    public function disconnect()
    {
        // ==== Resetting the connection trigger ==== //
        $this->conn_trigger = false;

        // ==== Freeing result memory ==== //
        if(is_resource($this->resource))
        {
            mysql_free_result($this->resource);
        }

        // ==== Closing connection to database ==== //
        if(is_resource($this->link_id))
        {
            return mysql_close($this->link_id);
        }
        else
        {
            return false;
        }
    }

    /**
     * The method executes a query
     *
     * @param string $query
     * @return boolean
     */
    public function query($query)
    {
        // ==== Lazy connect trigger ==== //
        $lazy_connect = false;

        // ==== Checking if the connection was successful ==== //
        if($this->conn_trigger == true && is_resource($this->link_id))
        {
            $connect_ok = true;
        }
        else
        {
            $connect_ok = false;
        }

        // ==== Checking that a connection does not exist and a connection attempt was not triggered ==== //
        if($connect_ok == false && $this->conn_trigger == false)
        {
            // ==== Triggering the lazy connect ==== //
            $lazy_connect = true;

            // ==== Connecting to the database ==== //
            $this->connect();

            // ==== Checking if the connection was succesfull ==== //
            if(is_resource($this->link_id))
            {
                $connect_ok = true;
            }
            else
            {
                $connect_ok = false;
            }
        }

        // ==== Checking if the connection was successful ==== //
        if($connect_ok)
        {
            // ==== Executing the query ===== //
            $query = mysql_query($query, $this->link_id);
        }
        else
        {
            $query = false;
        }

        // ==== If lazy connect was triggered disconnect from the database ==== //
        if($lazy_connect && $connect_ok)
        {
            $this->disconnect();
        }

        // ==== Returning result ==== //
        if(!$query)
        {
            // ==== Resetting the resource ==== //
            $this->resource = '';

            return false;
        }
        else
        {
            // ==== Getting the query resource ===
            $this->resource = $query;

            return true;
        }
    }

    /**
     * The method returns a single row and/or field from the query
     *
     * @param integer $row
     * @param integer $field
     * @return mixed false if unsuccessfull or if $row/$field is not numeric and result on success
     */
    public function result($row=0, $field=0)
    {
        // ==== Checking if we have a resource and that the $row and $field vars are numeric ==== //
        if(is_resource($this->resource) && is_numeric($row) && is_numeric($field))
        {
            $result = mysql_result($this->resource, $row, $field);

            if(!$result)
            {
                return false;
            }
            else
            {
                return $result;
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * The method counts the number of rows in the current query
     *
     * @param void
     * @return mixed false on fail or number of rows on success
     */
    public function num_rows()
    {
        if(is_resource($this->resource))
        {
            $result = mysql_num_rows($this->resource);

            if(!$result)
            {
                return false;
            }
            else
            {
                return $result;
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * The method counts the number of affected rows in the current query
     *
     * @param void
     * @return mixed false on fail or number of affected rows if successfull
     */
    public function affected_rows()
    {
        if(is_resource($this->link_id))
        {
            $result = mysql_affected_rows($this->link_id);

            if(!$result)
            {
                return false;
            }
            else
            {
                return $result;
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * The method is returns an associative array from the current query
     *
     * @param void
     * @return mixed false on fail or array on success
     */
    public function fetch_assoc()
    {
        if(is_resource($this->link_id))
        {
            $result = mysql_fetch_assoc($this->resource);

            if(!$result)
            {
                return false;
            }
            else
            {
                return $result;
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * The method is returns an associative array, a numeric array, or both from the current query
     *
     * @param void
     * @return mixed false on fail or array on success
     */
    public function fetch_array()
    {
        if(is_resource($this->link_id))
        {
            $result = mysql_fetch_array($this->resource);

            if(!$result)
            {
                return false;
            }
            else
            {
                return $result;
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * The method returns a single row from the query
     *
     * @param integer
     * @return mixed false on fail or string on success
     */
    public function fetch_row($row=0)
    {
        if(is_resource($this->resource) && is_numeric($row))
        {
            // ==== Going to requested row ==== //
            mysql_data_seek($this->resource, $row);

            // ==== Returning row === //
            return mysql_fetch_row($this->resource);
        }
        else
        {
            return false;
        }
    }

    /**
     * Escapes special characters in a string for use in a SQL statement
     *
     * @param string $string
     * @return mixed false on fail or escaped string on success
     */
    public function escape_string($string)
    {
        // ==== Checking if we have a resource and that the parameter is a string ==== //
        if(is_resource($this->link_id) && is_string($string))
        {
            return mysql_real_escape_string($string, $this->link_id);
        }
        else
        {
            return false;
        }
    }

    /**
     * To avoid getting the wrong last id the method executes the query itself and then returns the last id
     *
     * USE THIS INSTEAD OF QUERY WHEN YOU WANT THE LAST INSERTED ID
     *
     * @param string $query
     * @param string $autoincrementField
     * @return mixed false on fail or integer on success
     */
    public function last_id($query, $autoincrementField)
    {
        if(is_resource($this->link_id))
        {
            $this->query($query);

            return mysql_insert_id($this->link_id);
        }
        else
        {
            return false;
        }
    }

    /**
     * The method returns the last error mysql threw
     *
     * @param void
     * @return string Returns the last error text or '' (empty string) if no error occurred or no resource found
     */
    public function error()
    {
        if(is_resource($this->link_id))
        {
            return mysql_error($this->link_id);
        }
        else
        {
            return '';
        }
    }
}

/////////////////////////////////////////////////////////////////////////////////////////////////
//              PostgreSQL Database Class                                                     //
///////////////////////////////////////////////////////////////////////////////////////////////
class Pgsql implements db_module
{

    /**
     * Options array
     *
     * @var array
     */
    private $options;

    /**
     * Resource variable
     *
     * @var resource
     */
    private $resource;

    /**
     * Established connection holder
     *
     * @var connection_resource
     */
    private $link;

    /**
     * Determins if there was a connection attempt
     *
     * @var boolean
     */
    private $conn_trigger = false;
    

    /**
     * Class constructor
     *
     * @param array $options
     * @return void
     */
    public function __construct($options)
    {
        // ==== Default options ==== //
        $this->options['db']     = 'default';
        $this->options['host']   = 'localhost';
        $this->options['port']   = '5432';
        $this->options['user']   = 'root';
        $this->options['passwd'] = '';

        // ==== Replacing options with custom ones ==== //
        if(is_array($options))
        {
            $this->options = array_replace($this->options, $options);
        }
    }

    /**
     * Method that connects to the database
     *
     * @param void
     * @return boolean
     */
    public function connect()
    {
        // ==== A connection was attempted ==== //
        $this->conn_trigger = true;

        // ==== Building connection string ==== //
        $conn_string = "host=" . $this->options['host'] . " ";
        $conn_string .= "dbname=" . $this->options['db'] . " ";
        $conn_string .= "user=" . $this->options['user'] . " ";
        $conn_string .= "password=" . $this->options['passwd'] . " ";
        $conn_string .= "port=" . $this->options['port'];

        // ==== Connecting to the database ==== //
        $this->link = pg_connect($conn_string);

        // ==== Checking if the connection was successfull
        if($this->link == false)
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    /**
     * Method that disconnects from the database
     *
     * @param void
     * @return boolean
     */
    public function disconnect()
    {
        // ==== Resetting the connection trigger ==== //
        $this->conn_trigger = false;

        if(is_resource($this->link))
        {
            // ==== Freeing result memory ==== //
            if(is_resource($this->resource))
            {
                pg_free_result($this->resource);
            }

            // ==== Closing connection to database ==== //
            return pg_close($this->link);
        }
        else
        {
            return false;
        }
    }

    /**
     * The method executes a query
     *
     * @param string $query
     * @return boolean
     */
    public function query($query)
    {
        // ==== Lazy connect trigger ==== //
        $lazy_connect = false;

        // ==== Checking if the connection was successful ==== //
        if($this->conn_trigger == true && is_resource($this->link))
        {
            $connect_ok = true;
        }
        else
        {
            $connect_ok = false;
        }

        // ==== Checking that a connection does not exist and a connection attempt was not triggered ==== //
        if($connect_ok == false && $this->conn_trigger == false)
        {
            // ==== Triggering the lazy connect ==== //
            $lazy_connect = true;

            // ==== Connecting to the database ==== //
            $this->connect();

            // ==== Checking if the connection was successful ==== //
            if($lazy_connect && is_resource($this->link))
            {
                $connect_ok = true;
            }
            else
            {
                $connect_ok = false;
            }
        }

        // ==== Checking if the connection was successful ==== //
        if($connect_ok)
        {
            // ==== Now we can execute the query ==== //
            $query = pg_query($this->link, $query);
        }
        else
        {
            $query = false;
        }

        // ==== If lazy connect was triggered disconnect from the database ==== //
        if($lazy_connect && $connect_ok)
        {
            $this->disconnect();
        }

        // ==== Returning result ==== //
        if($query == false)
        {
            // ==== Resetting the resource ==== //
            $this->resource = '';

            return false;
        }
        else
        {
            // ==== Getting the query resource ==== //
            $this->resource = $query;

            return true;
        }
    }

    /**
     * The method returns a single row and/or field from the query
     *
     * @param integer $row
     * @param mixed $field
     * @return mixed false if unsuccessfull or if $row/$field is not numeric and result on success
     */
    public function result($row=0, $field=0)
    {
        // ==== Checking if we have a resource and that the $row and $field vars are numeric ==== //
        if(is_resource($this->resource) && is_numeric($row) && is_numeric($field))
        {
            $result = pg_fetch_result($this->resource, $row, $field);

            if(!$result)
            {
                return false;
            }
            else
            {
                return $result;
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * The method counts the number of rows in the current query
     *
     * @param void
     * @return mixed false on fail or number of rows on success
     */
    public function num_rows()
    {
        if(is_resource($this->resource))
        {
            $result = pg_num_rows($this->resource);

            if(!$result)
            {
                return false;
            }
            else
            {
                return $result;
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * The method counts the number of affected rows in the current query
     *
     * @param void
     * @return mixed false on fail or number of affected rows if successfull
     */
    public function affected_rows()
    {
        if(is_resource($this->resource))
        {
            $result = pg_affected_rows($this->resource);

            if(!$result)
            {
                return false;
            }
            else
            {
                return $result;
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * The method is returns an associative array from the current query
     *
     * @param void
     * @return mixed false on fail or array on success
     */
    public function fetch_assoc()
    {
        if(is_resource($this->resource))
        {
            $result = pg_fetch_assoc($this->resource);

            if(!$result)
            {
                return false;
            }
            else
            {
                return $result;
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * The method is returns an associative array, a numeric array, or both from the current query
     *
     * @param void
     * @return mixed false on fail or array on success
     */
    public function fetch_array()
    {
        if(is_resource($this->resource))
        {
            $result = pg_fetch_array($this->resource);

            if(!$result)
            {
                return false;
            }
            else
            {
                return $result;
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * The method returns a single row from the query
     *
     * @param integer
     * @return mixed false on fail or string on success
     */
    public function fetch_row($row=0)
    {
        if(is_resource($this->resource) && is_int($row))
        {
            return pg_fetch_row($this->resource, $row);
        }
        else
        {
            return false;
        }
    }

    /**
     * Escapes special characters in a string for use in a SQL statement
     *
     * @param string $string
     * @return mixed false on fail or escaped string on success
     */
    public function escape_string($string)
    {
        // ==== Checking if we have a resource and that the parameter is a string ==== //
        if(is_resource($this->link) && is_string($string))
        {
            return pg_escape_string($this->link, $string);
        }
        else
        {
            return false;
        }
    }

    /**
     * To avoid getting the wrong last id the method executes the query itself and then returns the last id
     *
     * USE THIS INSTEAD OF QUERY WHEN YOU WANT THE LAST INSERTED ID
     *
     * @param string $query
     * @param string $autoincrementField
     * @return mixed false on fail or integer on success
     */
    public function last_id($query, $autoincrementField)
    {
        $last_id = $this->query($query . " RETURNING " . $autoincrementField);

        return $last_id;
    }

    /**
     * The method returns the last error PostgreSQL threw
     *
     * @param void
     * @return string Returns the last error text or '' (empty string) if no error occurred or no resource found
     */
    public function error()
    {
        if(is_resource($this->link))
        {
            return pg_last_error($this->link);
        }
        else
        {
            return '';
        }
    }
}

/////////////////////////////////////////////////////////////////////////////////////////////////
//              MySQLi Database Class                                                         //
///////////////////////////////////////////////////////////////////////////////////////////////
class Mysql_i implements db_module
{

    /**
     * Options array
     *
     * @var array
     */
    private $options;

    /**
     * MySQLi object
     *
     * @var object
     */
    private $link;

    /**
     * MySQLi result object
     *
     * @var object
     */
    private $result;

    /**
     * Determins if there was a connection attempt
     *
     * @var boolean
     */
    private $conn_trigger = false;
    

    /**
     * Class constructor
     *
     * @param array $options
     * @return void
     */
    public function __construct($options)
    {
        // ==== Default options ==== //
        $this->options['db']     = 'default';
        $this->options['host']   = 'localhost';
        $this->options['port']   = '3306';
        $this->options['user']   = 'root';
        $this->options['passwd'] = '';

        // ==== Replacing options with custom ones ==== //
        if(is_array($options))
        {
            $this->options = array_replace($this->options, $options);
        }
    }

    /**
     * The method connects to the database
     *
     * @param void
     * @return boolean
     */
    public function connect()
    {
        // ==== A connection was attempted ==== //
        $this->conn_trigger = true;

        // ==== Initializing MySQLi object ==== //
        $this->link = new mysqli($this->options['host'], $this->options['user'], $this->options['passwd'], $this->options['db'], $this->options['port']);

        // ==== Checking if connection was successfull ==== //
        if($this->link->connect_error)
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    /**
     * Method that disconnects from the database
     *
     * @param void
     * @return boolean
     */
    public function disconnect()
    {
        // ==== Resetting the connection trigger ==== //
        $this->conn_trigger = false;

        // ==== Freeing result memory ==== //
        if(is_object($this->result))
        {
            $this->result->free();
        }

        // ==== Closing connection to database ==== //
        if(is_object($this->link))
        {
            return $this->link->close();
        }
        else
        {
            return false;
        }
    }

    /**
     * The method executes a query
     *
     * @param string $query
     * @return boolean
     */
    public function query($query)
    {
        // ==== Lazy connect trigger ==== //
        $lazy_connect = false;

        // ==== Checking if the connection was successful ==== //
        if($this->conn_trigger == true && !is_object($this->link))
        {
            $connect_ok = true;
        }
        else
        {
            $connect_ok = false;
        }

        // ==== Checking that a connection does not exist and a connection attempt was not triggered ==== //
        if($connect_ok == false && $this->conn_trigger == false)
        {
            // ==== Triggering the lazy connect ==== //
            $lazy_connect = true;

            // ==== Connecting to the database ==== //
            $this->connect();

            // ==== Checking if the connection was successful ==== //
            if($lazy_connect && is_object($this->link))
            {
                $connect_ok = true;
            }
            else
            {
                $connect_ok = false;
            }
        }

        // ==== Checking if the connection was successful ==== //
        if($connect_ok)
        {
            // ==== Executing the query ===== //
            $query = $this->link->query($query);
        }
        else
        {
            $query = false;
        }

        // ==== If lazy connect was triggered disconnect from the database ==== //
        if($lazy_connect && $connect_ok)
        {
            $this->disconnect();
        }

        // ==== Returning result ==== //
        if($query == false)
        {
            // ==== Resetting the result ==== //
            $this->result = '';

            return false;
        }
        else
        {
            // ==== Getting the query object ==== //
            $this->result = $query;

            return true;
        }
    }

    /**
     * The method returns a single row and/or field from the query
     *
     * @param integer $row
     * @param integer $field
     * @return mixed false if unsuccessfull or if $row/$field is not numeric and result on success
     */
    public function result($row=0, $field=0)
    {
        // ==== Check variable for success/failure ==== //
        $failed = false;

        // ==== Checking to see if $this->result is an MySQLi_result object and that the $row and $field vars are numeric ==== //
        if(is_object($this->result) && is_numeric($row) && is_numeric($field))
        {
            // ==== Moving pointer to the desired row ==== //
            $seek = $this->result->data_seek($row);

            // ==== Checking if seek was succesfull === //
            if($seek == true)
            {
                // ==== Getting row from pointer ==== //
                $row = $this->result->fetch_row();

                // ==== Checking existance of field in row === //
                if(!isset($row[$field]))
                {
                    $failed = true;
                }
            }
            else
            {
                $failed = true;
            }
        }

        if($failed === false)
        {
            return $row[$field];
        }
        else
        {
            return false;
        }
    }

    /**
     * The method counts the number of rows in the current query
     *
     * @param void
     * @return mixed false on fail or number of rows on success
     */
    public function num_rows()
    {
        // ==== Checking to see if $this->result is an MySQLi_result object ==== //
        if(is_object($this->result))
        {
            return $this->result->num_rows;
        }
        else
        {
            return false;
        }
    }

    /**
     * The method counts the number of affected rows in the current query
     *
     * @param void
     * @return mixed false on fail or number of affected rows if successfull
     */
    public function affected_rows()
    {
        // ==== Checking to see if $this->link is an MySQLi_result object ==== //
        if(is_object($this->link))
        {
            return $this->link->affected_rows;
        }
        else
        {
            return false;
        }
    }

    /**
     * The method is returns an associative array from the current query
     *
     * @param void
     * @return mixed false on fail or array on success
     */
    public function fetch_assoc()
    {
        // ==== Check variable for success/failure ==== //
        $failed = false;

        // ==== Checking to see if $this->result is an MySQLi_result object ==== //
        if(is_object($this->result))
        {
            $array = $this->result->fetch_assoc();

            if($array === NULL)
            {
                $failed = true;
            }
        }
        else
        {
            $failed = true;
        }

        // ==== Returning result ==== //
        if($failed === false)
        {
            return $array;
        }
        else
        {
            return false;
        }
    }

    /**
     * The method is returns an associative array, a numeric array, or both from the current query
     *
     * @param void
     * @return mixed false on fail or array on success
     */
    public function fetch_array()
    {
        // ==== Check variable for success/failure ==== //
        $failed = false;

        // ==== Checking to see if $this->result is an MySQLi_result object ==== //
        if(is_object($this->result))
        {
            $array = $this->result->fetch_array();

            if($array === NULL)
            {
                $failed = true;
            }
        }
        else
        {
            $failed = true;
        }

        // ==== Returning result ==== //
        if($failed === false)
        {
            return $array;
        }
        else
        {
            return false;
        }
    }

    /**
     * The method returns a single row from the query
     *
     * @param integer
     * @return mixed false on fail or string on success
     */
    public function fetch_row($row=0)
    {
        if(is_object($this->result))
        {
            // ==== Going to requested row ==== //
            $this->result->data_seek($row);

            // ==== Returning row === //
            return $this->result->fetch_row();
        }
        else
        {
            return false;
        }
    }

    /**
     * Escapes special characters in a string for use in a SQL statement
     *
     * @param string $string
     * @return mixed false on fail or escaped string on success
     */
    public function escape_string($string)
    {
        // ==== Checking to see if $this->link is an MySQLi_result object ==== //
        if(is_object($this->link) && is_string($string))
        {
            return $this->link->real_escape_string($string);
        }
        else
        {
            return false;
        }
    }

    /**
     * To avoid getting the wrong last id the method executes the query itself and then returns the last id
     *
     * USE THIS INSTEAD OF QUERY WHEN YOU WANT THE LAST INSERTED ID
     *
     * @param string $query
     * @param string $autoincrementField
     * @return mixed false on fail or integer on success
     */
    public function last_id($query, $autoIncrementField)
    {
        // ==== Checking to see if $this->link is an MySQLi_result object ==== //
        if(is_object($this->link))
        {
            // ==== Executing Query ==== //
            $this->query($query);

            $id = $this->link->last_id;

            if($id !== 0)
            {
                return $id;
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * The method returns the last error mysql threw
     *
     * @param void
     * @return string Returns the last error text or '' (empty string) if no error occurred or no resource found
     */
    public function error()
    {
        // ==== Checking to see if $this->result is an MySQLi_result object ==== //
        if(is_object($this->link))
        {
            return $this->link->error;
        }
        else
        {
            return '';
        }
    }
}

/////////////////////////////////////////////////////////////////////////////////////////////////
//              dBase Database Class                                                          //
///////////////////////////////////////////////////////////////////////////////////////////////
class Dbase implements db_module
{

    /**
     * Options array
     *
     * @var array
     */
    private $options;

    /**
     * Resource variable
     *
     * @var resource
     */
    private $resource;

    /**
     * Established connection holder
     *
     * @var link_identifier
     */
    private $link_id;

    /**
     * Determins if there was a connection attempt
     *
     * @var boolean
     */
    private $conn_trigger = false;
    

    /**
     * 
     * Class constructor
     *
     * @param array $options
     * @return void
     */
    public function __construct($options)
    {
        // ==== Default options ==== //
        $this->options['db']     = 'default.dbf';
        $this->options['host']   = 'localhost';
        $this->options['port']   = '0';
        $this->options['user']   = 'root';
        $this->options['passwd'] = '';
        $this->options['path']   = '/';
        $this->options['mode']   = 0; // Can take 0 for read-only or 2 for read-write

        // ==== Replacing options with custom ones ==== //
        if(is_array($options))
        {
            $this->options = array_replace($this->options, $options);
        }

        // ==== Failsafe in case access is set to 1 ==== //
        if($this->options['mode'] === 1)
        {
            $this->options['mode'] = 0;
        }
    }

    /**
     * Method that connects to the database
     *
     * @param void
     * @return boolean
     */
    public function connect()
    {
        // ==== Connecting to the database ==== //
        $this->link_id = dbase_open($this->options['path'] . $this->options['db'], $this->options['mode']);

        // ==== Checking if the connection was successfull ==== //
        if($this->link_id == false)
        {
            return false;
        }
        else
        {
            return true;
        }
        
    }

    /**
     * Method that disconnects from the database
     *
     * @param void
     * @return boolean
     */
    public function disconnect()
    {
        // ==== Disconnecting from the database ==== //
        if(is_resource($this->link_id))
        {
            return dbase_close($this->link_id);
        }
        else
        {
            return false;
        }
    }

    /**
     * The method executes a query
     *
     * @param string $query
     * @return boolean
     */
    public function query($query);

    /**
     * The method returns a single row and/or field from the query
     *
     * @param integer $row
     * @param integer $field
     * @return mixed false if unsuccessfull or if $row/$field is not numeric and result on success
     */
    public function result($row=0, $field=0);

    /**
     * The method counts the number of rows in the current query
     *
     * @param void
     * @return mixed false on fail or number of rows on success
     */
    public function num_rows();

    /**
     * The method counts the number of affected rows in the current query
     *
     * @param void
     * @return mixed false on fail or number of affected rows if successfull
     */
    public function affected_rows();

    /**
     * The method is returns an associative array from the current query
     *
     * @param void
     * @return mixed false on fail or array on success
     */
    public function fetch_assoc();

    /**
     * The method is returns an associative array, a numeric array, or both from the current query
     *
     * @param void
     * @return mixed false on fail or array on success
     */
    public function fetch_array();

    /**
     * The method returns a single row from the query
     *
     * @param integer
     * @return mixed false on fail or string on success
     */
    public function fetch_row($row=0);

    /**
     * Escapes special characters in a string for use in a SQL statement
     *
     * @param string $string
     * @return mixed false on fail or escaped string on success
     */
    public function escape_string($string);

    /**
     * To avoid getting the wrong last id the method executes the query itself and then returns the last id
     *
     * @param string $query
     * @param string $autoincrementField //This is important for compatibility with PostgreSQL
     * @return mixed false on fail or integer on success
     */
    public function last_id($query, $autoIncrementField);

    /**
     * The method returns the last error mysql threw
     *
     * @param void
     * @return string Returns the last error text or '' (empty string) if no error occurred or no resource found
     */
    public function error();
}
?>
