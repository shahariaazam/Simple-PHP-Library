<?php

/**
 * CustomException class
 * 
 * The class extends the exception class and adds an extra method to make the retrieval of exceptions more easy
 * 
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 * 
 * @name Exception
 * @version 1.0
 * 
 */

namespace SPL\Exception;

class Exception extends \Exception
{
    /**
     * Constructs the exception and prints it in a more readable format
     * 
     * @param string $message [ optional ]
     * @param int $code [ optional ]
     * @param \Exception $previous [ optional ]
     */
    public function __construct ($message = '', $code = 0, $previous = NULL){
        
        // Calling the parent constructor
        parent::__construct($message, $code, $previous);
        
        // Showing the message
        echo '<h3>Message:</h3><strong>Fatal error:</strong> Uncaught exception \'SPL\Exception\Exception\' with message "' . $this->getMessage() . '"<br />';
        echo '<h3>in file:</h3>' . $this->getFile() . '<br />';
        echo '<h3>@ line:</h3>' . $this->getLine() . '<br />';
        echo '<h3>Code:</h3>' . $this->getCode() . '<br />';
        echo '<h3>Trace:</h3><pre>' . $this->getTraceAsString() . '</pre>';
        
        // Because this is a fatal error the rest of the output from the Exception class is not needed
        die();
    }
}