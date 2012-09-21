<?php

/**
 * Language class
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name Language
 * @version 3.7.1
 *
 */

namespace SPL\Language;

use SPL\Url\UrlInterface;

class Language
{

    /**
     * Variable that contains options for the class
     *
     * @var array
     */
    private $options;

    /**
     * Unique identifier
     *
     * @var string
     */
    private $uq;

    /**
     * Language file location
     *
     * @var string
     */
    private $file;

    /**
     * Array with all the texts
     *
     * @var array
     */
    private $texts;

    /**
     * Variable that holds the options for the debug mail
     *
     * @var array
     */
    private $mopt;

    /**
     * Url object
     *
     * @var \SPL\Url\Url
     */
    protected $url;

    /**
     * CodeIgniter object
     *
     * @var CodeIgniter
     */
     private $CI;

    /**
     * Sets class options
     *
     * @param Url $url
     * @param array $options
     * @return void
     */
    public function __construct(UrlInterface $url, array $options = array())
    {
        // ==== Default options ==== //
        $this->options['default_language']  = 'en';
        $this->options['code_igniter']      = false;
        $this->options['lang_dir']          = 'lang/';
        $this->options['lang_sufix']        = '.lang.php';
        $this->options['cookie_enabled']    = false;
        $this->options['cookie_expire']     = 2592000;
        $this->options['cookie_domain']     = '';
        $this->options['cookie_path']       = '/';
        $this->options['debug']             = false;
        $this->options['mail_id']           = '[GENERIC]';
        $this->options['mail']              = 'webmaster@'.$_SERVER['HTTP_HOST'];

        // ==== Replacing the internal values with the external ones ==== //
        if(count($options) > 0)
        {
            $this->options = array_merge($this->options, $options);
        }

        // ==== Getting the Code Igniter object instance if the class has the support activated for it ==== //
        if($this->options['code_igniter'])
        {
            $this->CI = &get_instance();

            // ==== Getting the cookie domain ==== //
            $this->options['cookie_domain'] = $this->CI->config->item('cookie_domain');
        }
        else
        {
            // ==== Checking for session initialization ==== //
            if(session_id() == '')
            {
                trigger_error('The Language class requires sessions to work properly.', E_USER_WARNING);
            }
        }

        // Getting the Url object
        $this->url = $url;

        // ==== Setting up mail options ==== //
        $this->mopt['to']         = $this->options['mail'];
        $this->mopt['subject']    = '[DEBUG]' . $this->options['mail_id'] . ' ' . __CLASS__ . ' Class';
        $this->mopt['msg']        = '';
        $this->mopt['headers']    = 'MIME-Version: 1.0' . "\r\n";
        $this->mopt['headers']   .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

        // ==== Loading the language ==== //
        $this->loadLanguage();

        // ==== Loading the texts ==== //
        $this->loadTexts();
    }

    /**
     * Retrieves the current language
     *
     * @param void
     * @return string
     */
    public function getLanguage()
    {
        // ==== Checking the possible locations for a language ==== //
        if($this->url->getParam('lang') !== null) // LANG PARAM IN GET
        {
            $lang = $this->url->getParam('lang');
        }
        elseif(isset($_SESSION['lang_' . $this->uq])) // LANGUAGE ID FROM SESSION
        {
            $lang = $_SESSION['lang_' . $this->uq];
        }
        elseif($this->options['code_igniter'] == true && $this->CI->session->userdata('lang_' . $this->uq) != false) // LANGUAGE ID FROM CI SESSION
        {
            $lang = $this->CI->session->userdata('lang_' . $this->uq);
        }
        elseif(isset($_COOKIE['lang_' . $this->uq]) && $this->options['cookie_enabled'] == true) // LANGUAGE ID FROM COOKIE
        {
            $lang = $_COOKIE['lang_' . $this->uq];
        }
        else // DEFAULT LANGUAGE
        {
            $lang = $this->options['default_language'];
        }

        // ==== Returning the language ==== //
        return $lang;
    }

    /**
     * Loads the language file for the requested language
     *
     * @param void
     * @return void
     */
    protected function loadLanguage()
    {
        // ==== Generating unique identifier ==== //
        $salt = realpath(dirname(__FILE__));

        // ==== Hashing unique ID ==== //
        $this->uq = sha1($salt);

        // ==== Getting the language ==== //
        $lang = $this->getLanguage();

        // ==== Building language file path for the requested language ==== //
        $langfile = $this->options['lang_dir'] . $lang . $this->options['lang_sufix'];

        // ==== Checking if the file exists == falling back to default if not ==== //
        if(!is_file($langfile))
        {
            $lang = $this->options['default_language'];

            // ==== Building language file path for the default language ==== //
            $langfile = $this->options['lang_dir'] . $lang . $this->options['lang_sufix'];

            // ==== Adding debug data ==== //
            if($this->options['debug'])
            {
                $this->mopt['msg'] .= '<b>Notice:</b> File <i><u>' . $this->file . '</u></i> not found. Falling back to default language.<br /><br />';
            }
        }

        // ==== Intializing or overwriting the session variable ===== //
        if($this->options['code_igniter'] == true)
        {
            $this->CI->session->set_userdata('lang_' . $this->uq, $lang);
        }
        else
        {
            $_SESSION['lang_' . $this->uq] = $lang;
        }

        // ==== If rememeber is active ==== //
        if($this->options['cookie_enabled'] == true
                && is_numeric($this->options['cookie_expire'])
                && $this->options['cookie_expire'] >= 0
                && !empty($this->options['cookie_path'])
                && !empty($this->options['cookie_domain'])
                && ((isset($_COOKIE['lang_' . $this->uq]) && $_COOKIE['lang_' . $this->uq] != $lang) || !isset($_COOKIE['lang_' . $this->uq]))
        )
        {
            // ==== Setting the cookie ==== //
            setcookie('lang_' . $this->uq, $lang, time() + $this->options['cookie_expire'], $this->options['cookie_path'], $this->options['cookie_domain']);

            // ==== Setting the cookie var ==== //
            $_COOKIE['lang_' . $this->uq] = $lang;
        }

        // ==== Assign language file path ==== //
        $this->file = $langfile;
    }

    /**
     * Loads the texts in the class cache
     *
     * @param void
     * @return void
     */
    private function loadTexts()
    {
        // ==== Temporary log ==== //
        $log = '';

        // ==== Check variable ==== //
        $isOk = true;

        // ==== Checking if the texts have already been loaded ==== //
        if(!is_array($this->texts))
        {
            // ==== Second file check == First was in constructor ==== //
            if(!is_file($this->file))
            {
                // ==== Adding debug data ==== //
                if($this->options['debug'])
                {
                    $log .= '<b>ERROR:</b> File <i><u>' . $this->file . '</u></i> not found.<br /><br />';
                }

                $isOk = false;
            }
            else
            {
                // ==== Getting required file ==== //
                require_once $this->file;

                // ==== Getting texts ==== //
                if(!is_array($text))
                {
                    // ==== Adding debug data ==== //
                    if($this->options['debug'])
                    {
                        $log .= '<b>ERROR:</b> File <i><u>' . $this->file . '</u></i> not found.<br /><br />';
                    }

                    $isOk = false;
                }
                else
                {
                    $this->texts = $text;
                }
            }
        }

        // ==== Adding debug data ==== //
        if($this->options['debug'] && $isOk === false)
        {
            $this->mopt['msg'] .= $log;
        }

        // ==== Returning result ==== //
        return $isOk;
    }

    /**
     * Retrieves a text from the cache
     *
     * @param string $text
     * @param array $data The given data is used to parse the text
     * @return mixed false on fail or string on success
     */
    public function _($txt, array $data = array())
    {
        // ==== Checking if the text exists ==== //
        if(isset($this->texts[$txt]))
        {
            // ==== Getting the text into a variable ==== //
            $text = $this->texts[$txt];

            // ==== Checking if we have any data to used for the parsing ==== //
            if(count($data) > 0)
            {
                // ==== Going through the data and replacing stuff in the text ==== //
                foreach ($data as $token => $value)
                {
                    $text = str_replace('{' . $token . '}', $value, $text);
                }
            }

            return $text;
        }
        else
        {
            // ==== Adding debug data ==== //
            if($this->options['debug'])
            {
                $log .= '<b>Notice:</b> Text <i><u>' . $txt . '</u></i> does not exist.<br /><br />';

                // ==== Backtracking ===== //
                $backtrace = debug_backtrace();

                // ==== Gettin line and file of the call ==== //
                $line = $backtrace[0]['line'];
                $file = $backtrace[0]['file'];

                $this->mopt['msg'] .= '<br /><b>Info:</b><br />Line: ' . $line . '<br />File: ' . $file . '<br /><br />' . $log;
            }

            return false;
        }
    }

    /**
     * Sends debug mail (if debug is active and present)
     *
     * @param void
     * @return void
     */
    public function __destruct()
    {
        // ==== Sending debug if on ==== //
        if($this->options['debug'] && $this->mopt['msg'] != '')
        {
            // ==== Sending debug mail ==== //
            mail($this->mopt['to'], $this->mopt['subject'], $this->mopt['msg'], $this->mopt['headers']);
        }
    }

}