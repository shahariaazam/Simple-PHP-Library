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
 * @version 3.9
 *
 */

namespace SPL\Language;

use SPL\Url\UrlInterface;

class Language
{

    /**
     * Log
     *
     * @var string
     */
    protected $log = '';

    /**
     * Session array
     *
     * @var array
     */
    protected $session;

    /**
     * Variable that contains options for the class
     *
     * @var array
     */
    protected $options;

    /**
     * Unique identifier
     *
     * @var string
     */
    protected $uq;

    /**
     * Url object
     *
     * @var \SPL\Url\Url
     */
    protected $url;

    /**
     * Language file location
     *
     * @var string
     */
    protected $file = null;

    /**
     * Array with all the texts
     *
     * @var array
     */
    protected $texts;

    /**
     * Detected language
     *
     * @var string
     */
    protected $lang;

    /**
     * CodeIgniter object
     *
     * @var CodeIgniter
     */
    protected $CI;

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
        $this->options['default_language'] = 'en';
        $this->options['code_igniter']     = false;

        // ==== Language files options ==== //
        $this->options['lang_dir']         = 'lang/';
        $this->options['lang_sufix']       = '.lang.php';

        // ==== Cookie options ==== //
        $this->options['cookie_enabled']   = false;
        $this->options['cookie_expire']    = 2592000; // 30 days
        $this->options['cookie_domain']    = '';
        $this->options['cookie_path']      = '/';

        // ==== Debug options ==== //
        $this->options['debug']            = false;
        $this->options['mail_id']          = '[GENERIC]';
        $this->options['mail']             = 'webmaster@' . $_SERVER['HTTP_HOST'];

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

        // Getting the Url object
        $this->url = $url;

        // Initializing
        $this->initialize();
    }

    /**
     * Initializes the language class properties
     *
     * @param void
     * @return void
     */
    protected function initialize()
    {
        // ==== Generating a unique ID ==== //
        $this->uq = sha1(serialize($this->options));

        // ==== Detecting the language ==== //
        $this->detectLanguage();

        // ==== Setting the language
        $this->setLanguage();

        // ==== Loading the language ==== //
        $this->loadLanguage();
    }

    /**
     * The method retrieves the data from session
     *
     * @param void
     * @return void
     */
    protected function getSession()
    {
        $this->session = &$_SESSION;
    }

    /**
     * The method saves the session data
     *
     * @param void
     * @return void
     */
    protected function setSession()
    {
        if(is_array($_SESSION))
        {
            $_SESSION = array_merge($_SESSION, $this->session);
        }
        else
        {
            $_SESSION = $this->session;
        }
    }

    /**
     * Detectes the current language
     *
     * @param void
     * @return void
     */
    protected function detectLanguage()
    {
        // ==== Checking the possible locations for a language ==== //
        /**
         * -------------------------
         * LANGUAGE ID FROM GET
         * -------------------------
         */
        if($this->url->getParam('lang') !== null)
        {
            $this->lang = $this->url->getParam('lang');
        }
        /**
         * -------------------------
         * LANGUAGE ID FROM SESSION
         * -------------------------
         */
        else if(isset($this->session['lang_' . $this->uq]))
        {
            $this->lang = $this->session['lang_' . $this->uq];
        }
        /**
         * -------------------------
         * LANGUAGE ID FROM COOKIE
         * -------------------------
         */
        else if($this->options['cookie_enabled'] == true && isset($_COOKIE['lang_' . $this->uq]))
        {
            $this->lang = $_COOKIE['lang_' . $this->uq];
        }
        /**
         * -------------------------
         * DEFAULT LANGUAGE
         * -------------------------
         */
        else
        {
            $this->lang = $this->options['default_language'];
        }
    }

    /**
     * Sets the language in different locations (like session and cookie)
     *
     * @param void
     * @return void
     */
    protected function setLanguage()
    {
        // ==== Intializing or overwriting the session variable ===== //
        $this->session['lang_' . $this->uq] = $this->lang;

        // ==== If rememeber is active ==== //
        if($this->options['cookie_enabled'] == true
                && is_numeric($this->options['cookie_expire'])
                && $this->options['cookie_expire'] >= 0
                && !empty($this->options['cookie_path'])
                && !empty($this->options['cookie_domain'])
                && ((isset($_COOKIE['lang_' . $this->uq]) && $_COOKIE['lang_' . $this->uq] != $this->lang) || !isset($_COOKIE['lang_' . $this->uq]))
        )
        {
            // ==== Setting the cookie ==== //
            setcookie('lang_' . $this->uq, $this->lang, time() + $this->options['cookie_expire'], $this->options['cookie_path'], $this->options['cookie_domain']);

            // ==== Setting the cookie var ==== //
            $_COOKIE['lang_' . $this->uq] = $this->lang;
        }

        // Setting the session
        $this->setSession();
    }

    /**
     * Returns the current language
     *
     * @param void
     * @return string
     */
    public function getLanguage()
    {
        return $this->lang;
    }

    /**
     * Loads the language file for the requested language
     *
     * @param void
     * @return void
     */
    protected function loadLanguage()
    {
        // ==== Building language file path for the requested language ==== //
        $file = $this->options['lang_dir'] . $this->lang . $this->options['lang_sufix'];

        // ==== Checking if the file exists == falling back to default if not ==== //
        if(!is_file($file))
        {
            $this->lang = $this->options['default_language'];

            // ==== Building language file path for the default language ==== //
            $file = $this->options['lang_dir'] . $this->lang . $this->options['lang_sufix'];

            // Checking if the default language exists
            if(is_file($file))
            {
                // ==== Assign language file path ==== //
                $this->file = $file;
            }

            // ==== Adding debug data ==== //
            if($this->options['debug'])
            {
                $this->log .= '<b>Notice:</b> File <i><u>' . $file . '</u></i> not found. Falling back to default language.<br /><br />';
            }
        }
        else
        {
            // ==== Assign language file path ==== //
            $this->file = $file;
        }
    }

    /**
     * Loads the texts in the class cache
     *
     * @param void
     * @return void
     */
    protected function loadTexts()
    {
        // ==== Temporary log ==== //
        $log = '';

        // ==== Check variable ==== //
        $isOk = true;

        // ==== Checking if the texts have already been loaded ==== //
        if(!is_array($this->texts))
        {
            // ==== Checking if a file was detected ==== //
            if($this->file == null)
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
            $this->log .= $log;
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
        // Static var to determine if the texts were loaded
        static $textsLoaded = false;

        // Checking if the texts were loaded
        if($textsLoaded == false)
        {
            // Loading the texts
            $this->loadTexts();

            // Setting the flag
            $textsLoaded = true;
        }

        // ==== Checking if the text exists ==== //
        if(isset($this->texts[$txt]))
        {
            // ==== Getting the text into a variable ==== //
            $text = $this->texts[$txt];

            // ==== Checking if we have any data to used for the parsing ==== //
            if(count($data) > 0)
            {
                // ==== Going through the data and replacing stuff in the text ==== //
                foreach($data as $token => $value)
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

                $this->log .= '<br /><b>Info:</b><br />Line: ' . $line . '<br />File: ' . $file . '<br /><br />' . $log;
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
        if($this->options['debug'] && $this->log != '')
        {
            // ==== Setting up mail options ==== //
            $subject = '[DEBUG]' . $this->options['mail_id'] . ' ' . __CLASS__ . ' Class';
            $headers = 'MIME-Version: 1.0' . "\r\n" . 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

            // ==== Sending debug mail ==== //
            mail($this->options['mail'], $subject, $this->log, $headers);
        }
    }

}