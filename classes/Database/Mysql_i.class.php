<?php
/**
 *
 * Enhanced CrossDatabase Engine. Default database engine is mysql
 *
 * Mysql_i Module
 *
 * @author Brian
 * @link http://brian.hopto.org/wiki/hypermvc/
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name Database
 * @version 3.6
 *
 */

namespace Database;

/////////////////////////////////////////////////////////////////////////////////////////////////
//              MySQLi Database Class                                                         //
///////////////////////////////////////////////////////////////////////////////////////////////
class Mysql_i implements \Database\db_module
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
        $this->link = new \mysqli($this->options['host'], $this->options['user'], $this->options['passwd'], $this->options['db'], $this->options['port']);

        // ==== Checking if connection was successfull ==== //
        if($this->link->connect_error != NULL)
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
        if($this->conn_trigger == true && is_object($this->link))
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
     * @param mixed $field
     * @return mixed false if unsuccessfull or if $row is not numeric and result on success
     */
    public function result($row=0, $field=0)
    {
        // ==== Check variable for success/failure ==== //
        $failed = false;

        // ==== Checking to see if $this->result is an MySQLi_result object and that the $row and $field vars are numeric ==== //
        if(is_object($this->result) && is_numeric($row))
        {
            // ==== Moving pointer to the desired row ==== //
            $seek = $this->result->data_seek($row);

            // ==== Checking if seek was succesfull === //
            if($seek == true)
            {
                // ==== Getting row from pointer ==== //
                $row_num = $this->result->fetch_row();

                // ==== Moving pointer to the desired row ==== //
                $seek = $this->result->data_seek($row);

                // ==== Checking if seek was succesfull === //
                if($seek == true)
                {
                    // ==== Getting row from pointer ==== //
                    $row_assoc = $this->result->fetch_assoc();

                    // ==== Merging the row results ==== //
                    $row = array_merge($row_num, $row_assoc);

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
            else
            {
                $failed = true;
            }
        }

        // ==== Checking the status ==== //
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
     * The method counts the number of rows returned by the current query
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
     * The method counts the number of affected rows by the current query
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
     * @param string $result_type
     * @return mixed false on fail or array on success
     */
    public function fetch_array($result_type='both')
    {
        // ==== Check variable for success/failure ==== //
        $failed = false;

        // ==== Checking to see if $this->result is an MySQLi_result object ==== //
        if(is_object($this->result))
        {
            // ==== Types array ==== //
            $types = array(
                'both'  => MYSQLI_BOTH,
                'assoc' => MYSQLI_ASSOC,
                'num'   => MYSQLI_NUM
            );

            $array = $this->result->fetch_array($types[$result_type]);

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

            // ==== Getting the last ID ==== //
            $id = $this->link->insert_id;

            // ==== Checking if we have a proper last ID ==== //
            if(is_numeric($id) && $id > 0)
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
        // ==== Default error ==== //
        $error = '';

        // ==== Checking to see if $this->result is an MySQLi_result object ==== //
        if(is_object($this->link))
        {
            // ==== Checking for errors ==== //
            if($this->link->connect_error != NULL)
            {
                $error = $this->link->connect_error;
            }
            elseif($this->link->error  != NULL)
            {
                $error = $this->link->error;
            }
        }

        // ==== Returning the error ==== //
        return $error;
    }
}