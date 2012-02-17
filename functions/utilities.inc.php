<?php
/**
 * Class autoloader
 * The function requires a __WEBMASTER__ constant.
 *
 * @global array $classes_dirs
 * @param string $class
 * @return void
 */
function class_loader($class)
{
    // ==== Getting some outside variables ===== //
    global $classes_dirs;

    // ==== Check variable ==== //
    $found = false;

    // ==== Location of the class file ==== //
    $class_file = '';

    // ==== Converting class name to lower case letters ==== //
    $cls = strtolower($class);

    // ==== Going through the directories ==== //
    if(isset($classes_dirs) && is_array($classes_dirs))
    {
        foreach($classes_dirs as $directory)
        {
            // ==== Telling it from where to include the files ==== //
            $file = $directory.$cls.'.class.php';

            // ==== Checking if file exists ==== //
            if(is_file($file))
            {
                // ==== Found the file ==== //
                $found = true;

                // ==== Getting the file ==== //
                $class_file = $file;

                // ==== Exiting the loop ==== //
                break;
            }
        }
    }
    else
    {
        $classes_dirs = array('No directories found or variable not an array.');
    }

    // ==== Checking if a class was found ==== //
    if($found == true) // Loading the class
    {
        require_once $class_file;
    }
    else // Sending debug mail
    {
        // ==== HTML Headers ==== //
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";

        // ==== More info ==== //
        $more = '';
        $more .= '<b>URL:</b> '.getFullURL().'<br /><br />';
        $more .= '<b>List of class directories:</b><pre>'.print_r($classes_dirs, true).'</pre><br /><br />';
        $more .= '<b>$_GET:</b><pre>'.print_r($_GET, true).'</pre><br /><br />';
        $more .= '<b>$_POST:</b><pre>'.print_r($_POST, true).'</pre><br /><br />';
        $more .= '<b>$_SERVER:</b><pre>'.print_r($_SERVER, true).'</pre><br /><br />';

        // ==== Sending notification mail to webmaster ==== //
        if(defined('__WEBMASTER__'))
        {
            mail(__WEBMASTER__, '[FATAL ERROR] Site: '.$_SERVER['HTTP_HOST'], '<b>ERROR:</b> There is no file for class <u>'.$class.'</u>.<br /><br />'.$more, $headers);
        }

        // ==== Showing message to users ==== //
        exit('<h1>An error has occured. Our team has been notified and is working right now to fix it. Please come back later...</h1>');
    }
}

/**
 * The function sends a debug mail in case a fatal error happens.
 * The function requires a __WEBMASTER__ constant.
 * The function can also use a __SITE_ROOT__ constant if defined.
 *
 * @param void
 * @return void
 */
function error_handler()
{
    // ==== Getting the last error ==== //
    $error = error_get_last();

    // ==== Checking if an error has occured ==== //
    if($error !== null)
    {
        // ==== Error types ==== //
        $err_types = array(
            E_ERROR             => 'E_ERROR',
            E_WARNING           => 'E_WARNING',
            E_PARSE             => 'E_PARSE',
            E_NOTICE            => 'E_NOTICE',
            E_CORE_ERROR        => 'E_CORE_ERROR',
            E_CORE_WARNING      => 'E_CORE_WARNING',
            E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
            E_USER_ERROR        => 'E_USER_ERROR',
            E_USER_WARNING      => 'E_USER_WARNING',
            E_USER_NOTICE       => 'E_USER_NOTICE',
            E_STRICT            => 'E_STRICT',
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
            $error_type = 'UNKNOWN ERROR';
        }

        // ==== Checking if we have a message type ==== //
        if($error_type !== false)
        {
            // ==== Determining the site root ==== //
            if(defined('__SITE_ROOT__'))
            {
                $site_root = __SITE_ROOT__;
            }
            else
            {
                $site_root = $_SERVER['HTTP_HOST'];
            }

            // ==== HTML Headers ==== //
            $headers  = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";

            // ==== Message ==== //
            $message = '';
            $message .= '<b>URL:</b> '.getFullURL().'<hr><br /><br />';
            $message .= '<b>MESSAGE:</b> '.$error['message'].'<br />';
            $message .= '<b>FILE:</b> '.$error['file'].'<br />';
            $message .= '<b>LINE:</b> '.$error['line'].'<br />';
            $message .= '<b>TYPE:</b> '.$error_type.'<hr><br /><br />';
            $message .= '<b>$_GET:</b><pre>'.print_r($_GET, true).'</pre><br />';
            $message .= '<b>$_POST:</b><pre>'.print_r($_POST, true).'</pre><br />';
            $message .= '<b>$_SERVER:</b><pre>'.print_r($_SERVER, true).'</pre><br /><br />';

            // ==== Sending notification mail to webmaster ==== //
            if(defined('__WEBMASTER__'))
            {
                mail(__WEBMASTER__, '['.$error_type.'] Site: '.$site_root, $message, $headers);
            }
        }
    }
}
?>
