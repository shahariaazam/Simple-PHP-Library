<?php
/**
 *
 * Enhanced CrossDatabase Engine. Default database engine is mysql
 *
 * Database module interface
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
     * @param mixed $field
     * @return mixed false if unsuccessfull or if $row/$field is not numeric and result on success
     */
    public function result($row=0, $field=0);

    /**
     * The method counts the number of rows returned by the current query
     *
     * @param void
     * @return mixed false on fail or number of rows on success
     */
    public function num_rows();

    /**
     * The method counts the number of affected rows by the current query
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
     * @param string $result_type
     * @return mixed false on fail or array on success
     */
    public function fetch_array($result_type);

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