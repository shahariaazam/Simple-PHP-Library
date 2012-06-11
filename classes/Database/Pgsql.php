<?php
/**
 *
 * Enhanced CrossDatabase Engine. Default database engine is mysql
 *
 * Pgsql Module
 *
 * @author Brian
 * @link http://brian.hopto.org/wiki/hypermvc/
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name Pgsql
 * @version 3.6
 *
 */

namespace Database;

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
        // ==== Checking if we have a resource and that the $row vars are numeric ==== //
        if(is_resource($this->resource) && is_numeric($row))
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
     * The method counts the number of rows returned by the current query
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
     * The method counts the number of affected rows by the current query
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
     * @param string $result_type
     * @return mixed false on fail or array on success
     */
    public function fetch_array($result_type='both')
    {
        // ==== Checking if we have a resource ==== //
        if(is_resource($this->resource))
        {
            // ==== Types array ==== //
            $types = array(
                'both'  => PGSQL_BOTH,
                'assoc' => PGSQL_ASSOC,
                'num'   => PGSQL_NUM
            );

            $result = pg_fetch_array($this->resource, null, $types[$result_type]);

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