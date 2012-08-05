<?php

/**
 * The class send the last detected error to the set email
 *
 * @name Errors
 * @version 1.0
 */

namespace SPL\Debug;

class Errors
{
    /**
     *
     * Error types
     * 
     * @var array
     */
    private static $err_types = array(
        E_ERROR => 'E_ERROR',
        E_WARNING => 'E_WARNING',
        E_PARSE => 'E_PARSE',
        E_NOTICE => 'E_NOTICE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_CORE_WARNING => 'E_CORE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR => 'E_USER_ERROR',
        E_USER_WARNING => 'E_USER_WARNING',
        E_USER_NOTICE => 'E_USER_NOTICE',
        E_STRICT => 'E_STRICT',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR'
    );
    
    /**
     * Email to send to
     * 
     * @var string
     */
    private static $email = '';
    
    /**
     * Sets the email address
     * 
     * @param string $email
     * @return void
     */
    public static function setEmail($email)
    {
        // If the email is valid we set it
        if(filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            self::$email = $email;
        }
    }
    
    /**
     * Registers the error handler function
     * 
     * @param void
     * @return void
     */
    public static function registerErrorHandler()
    {
        // Registering the error handler function
        register_shutdown_function(array('\SPL\Debug\Errors', 'getError'));
    }
    
    /**
     * The method detects the last error and send the details via mail
     * 
     * @param void
     * @return void
     */
    public static function getError()
    {
        // ==== Getting the last error ==== //
        $error = error_get_last();

        // ==== Checking if an error has occured ==== //
        if($error !== null)
        {
            // ==== Getting the error message that will be appended to the email title ==== //
            if(isset(self::$err_types[$error['type']]) && self::$err_types[$error['type']] !== false) // Has error string
            {
                $error_type = self::$err_types[$error['type']];
            }
            else if(isset(self::$err_types[$error['type']]) && self::$err_types[$error['type']] === false) // Error type disabled
            {
                $error_type = false;
            }
            else
            {
                $error_type = false;
            }

            // ==== Checking if we have a message type ==== //
            if($error_type !== false)
            {
                // ==== Determining the site root ==== //
                if(defined('SITE_ROOT'))
                {
                    $site_root = SITE_ROOT;
                }
                else
                {
                    $site_root = $_SERVER['HTTP_HOST'];
                }

                // ==== HTML Headers ==== //
                $headers = 'MIME-Version: 1.0' . "\r\n";
                $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";

                // ==== Message ==== //
                $message = '';
                $message .= '<b>URL:</b> ' . getFullURL() . '<hr><br /><br />';
                $message .= '<b>MESSAGE:</b> ' . $error['message'] . '<br />';
                $message .= '<b>FILE:</b> ' . $error['file'] . '<br />';
                $message .= '<b>LINE:</b> ' . $error['line'] . '<br />';
                $message .= '<b>TYPE:</b> ' . $error_type . '<hr><br /><br />';
                $message .= '<b>$_GET:</b><pre>' . print_r($_GET, true) . '</pre><br />';
                $message .= '<b>$_POST:</b><pre>' . print_r($_POST, true) . '</pre><br />';
                $message .= '<b>$_SERVER:</b><pre>' . print_r($_SERVER, true) . '</pre><br /><br />';

                // ==== Sending notification mail to webmaster ==== //
                if(!empty(self::$email))
                {
                    mail(self::$email, '[ERROR][' . $error_type . '] Site: ' . $site_root, $message, $headers);
                }
            }
        }
    }
}
