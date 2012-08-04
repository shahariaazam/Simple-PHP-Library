<?php

/**
 * The function sends a debug mail in case a fatal error happens.
 * The function requires a WEBMASTER constant.
 * The function can also use a SITE_ROOT constant if defined.
 *
 * @param void
 * @return void
 */
if(!function_exists('error_handler'))
{

    function error_handler()
    {
        // ==== Getting the last error ==== //
        $error = error_get_last();

        // ==== Checking if an error has occured ==== //
        if($error !== null)
        {
            // ==== Error types ==== //
            $err_types = array(
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

            // ==== Getting the error message that will be appended to the email title ==== //
            if(isset($err_types[$error['type']]) && $err_types[$error['type']] !== false)
            {
                $error_type = $err_types[$error['type']];
            }
            elseif(isset($err_types[$error['type']]) && $err_types[$error['type']] === false)
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
                if(defined('WEBMASTER'))
                {
                    mail(WEBMASTER, '[ERROR][' . $error_type . '] Site: ' . $site_root, $message, $headers);
                }
            }
        }
    }

}