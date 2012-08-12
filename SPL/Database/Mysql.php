<?php
/**
 *
 * Enhanced CrossDatabase Engine. Default database engine is mysql
 *
 * MySQL Module
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name Mysql
 * @version 3.6.1
 * 
 */

namespace SPL\Database;

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
        // ==== Checking if we have a resource and that the $row vars are numeric ==== //
        if(is_resource($this->resource) && is_numeric($row))
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
     * The method counts the number of rows returned by the current query
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
     * The method counts the number of affected rows by the current query
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
     * @param string $result_type
     * @return mixed false on fail or array on success
     */
    public function fetch_array($result_type='both')
    {
        // ==== Checking if we have a resource ==== //
        if(is_resource($this->link_id))
        {
            // ==== Types array ==== //
            $types = array(
                'both'  => MYSQL_BOTH,
                'assoc' => MYSQL_ASSOC,
                'num'   => MYSQL_NUM
            );

            $result = mysql_fetch_array($this->resource, $types[$result_type]);

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