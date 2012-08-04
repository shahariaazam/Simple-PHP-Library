<?php
/**
 *
 * Enhanced CrossDatabase Engine. Default database engine is mysql
 *
 * Dbase Module
 *
 * @author Brian
 * @link http://brian.hopto.org/wiki/hypermvc/
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name Dbase
 * @version 1.0
 *
 */

namespace SPL\Database;

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
     * Array that will hold all the entries that were retrieved by the query
     *
     * @param array
     */
    private $results = array();

    /**
     *
     * Counters array. The array contains counters for some types of database opperations
     *
     * @var array
     */
    private $counters = array();

    /**
     *
     * Holds the last query type
     *
     * @var string
     */
    private $query_type = '';

    /**
     *
     * Affected rows
     *
     * @var integer
     */
    private $_affected_rows = 0;


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
        $this->options['db']     = '';
        $this->options['host']   = 'localhost';
        $this->options['port']   = '0';
        $this->options['user']   = 'root';
        $this->options['passwd'] = '';
        $this->options['path']   = '';
        $this->options['mode']   = 0; // Can take 0 for read-only or 2 for read-write

        // ==== Replacing options with custom ones ==== //
        if(is_array($options))
        {
            $this->options = array_replace($this->options, $options);
        }

        // ==== Failsafe in case mode is set to 1 ==== //
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
        if($this->link_id != false)
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
    public function query($query='')
    {
        // ==== Getting the numer of rows in the database ==== //
        $num_rows = dbase_numrecords($this->link_id);

        // ==== Getting the records ==== //
        for($i = 1; $i <= $num_rows; $i++)
        {
            // ==== Getting the row data ==== //
            $assoc = dbase_get_record_with_names($this->link_id, $i);

            // ==== Creating a numeric array using the associative array ==== //
            $num = array();

            // ==== Going through the array ==== //
            foreach($assoc as $value)
            {
                $num[] = $value;
            }

            // ==== Adding the row to the result ==== //
            $result[] = array(
                'assoc' => $assoc,
                'num'   => $num,
            );
        }

        // ==== Adding the result to the class result ==== //
        $this->results = $result;
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
        // ==== Determining the array to look in ==== //
        if(is_numeric($field))
        {
            $type = 'num';
        }
        else
        {
            $type = 'assoc';
        }

        // ==== Returning the requested row if it's set ==== //
        if(isset($this->results[$row][$type][$field]) && is_numeric($row))
        {
            return $this->results[$row][$type][$field];
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
        // ==== Returning the count of results in the results array ==== //
        return count($this->results);
    }

    /**
     * The method counts the number of affected rows by the current query
     *
     * @param void
     * @return mixed false on fail or number of affected rows if successfull
     */
    public function affected_rows()
    {
        // ==== Getting the affected rows count ==== //
        $count = $this->_affected_rows;

        // ==== Resetting the affected rows ==== //
        $this->_affected_rows = 0;

        // ==== Result ==== //
        return $count;
    }

    /**
     * The method is returns an associative array from the current query
     *
     * @param void
     * @return mixed false on fail or array on success
     */
    public function fetch_assoc()
    {
        // ==== Counter for the number of calls ==== //
        static $calls = 0;

        // ==== Checking the counter ==== //
        if($calls == 0)
        {
            $row = current($this->results);

            // ==== Incrementing the calls variable ==== //
            $calls++;
        }
        else
        {
            $row = next($this->results);
        }

        // ==== Getting the associative array ==== //
        $row = $row['assoc'];

        // ==== Returning the result ==== //
        return $row;
    }

    /**
     * The method is returns an associative array, a numeric array, or both from the current query
     *
     * @param string $result_type
     * @return mixed false on fail or array on success
     */
    public function fetch_array($result_type='both')
    {
        // ==== Counter for the number of calls ==== //
        static $calls = 0;

        // ==== Checking the counter ==== //
        if($calls == 0)
        {
            // ==== Getting the current element ==== //
            $row = current($this->results);

            // ==== Incrementing the calls variable ==== //
            $calls++;
        }
        else
        {
            // ==== Getting the next element ==== //
            $row = next($this->results);
        }

        // ==== Getting the data depending on the result type ==== //
        switch($result_type)
        {
            case 'assoc':
                $row = $row['assoc'];
                break;

            case 'num':
                $row = $row['num'];
                break;

            default:
                $row = array_merge($row['assoc'], $row['num']);
                break;
        }

        // ==== Returning the result ==== //
        return $row;
    }

    /**
     * The method returns a single row from the query
     *
     * @param integer
     * @return mixed false on fail or string on success
     */
    public function fetch_row($row=0)
    {
        // ==== Returning the requested row if it's set ==== //
        if(isset($this->results[$row]))
        {
            return $this->results[$row]['num'];
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
        // ==== For dbase we do nothing because no query is involved ==== //
        return $string;
    }

    /**
     * To avoid getting the wrong last id the method executes the query itself and then returns the last id
     *
     * @param string $query
     * @param string $autoincrementField
     * @return mixed false on fail or integer on success
     */
    public function last_id($query, $autoIncrementField)
    {
        // ==== Result var ==== //
        $result = false;

        // ==== Checking if we have a connection to the database ==== //
        if($this->link_id != false)
        {
            // ==== Getting the number of elements from the current result set ==== //
            $results = count($this->results);

            // ==== Getting the result from the database ==== //
            $result_set = dbase_get_record_with_names($this->link_id, $results);

            // ==== Checking if the field is found ==== //
            if(isset($result_set[$autoIncrementField]))
            {
                $result = $result_set[$autoIncrementField];
            }
        }

        // ==== Returning result ==== //
        return $result;
    }

    /**
     * The method returns the last error mysql threw
     *
     * @param void
     * @return string Returns the last error text or '' (empty string) if no error occurred or no resource found
     */
    public function error()
    {
        // ==== No error retrieval available for dbase ==== //
        return '';
    }
}