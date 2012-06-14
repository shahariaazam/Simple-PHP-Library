<?php
/**
 * Language class
 *
 * @author Brian
 * @link http://brian.hopto.org/framwork_wiki/
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 *
 * @name Language
 * @version 3.5
 */

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
     * Class constructor
     *
     * @param array $options
     * @return void
     */
    public function __construct($options=array())
    {
        // ==== Checking if a session exists ==== //
        if(session_id() == '')
        {
            trigger_error('The Language class needs and active session in order to work.', E_USER_WARNING);
        }

        // ==== Default options ==== //
        $this->options['default_language']  = 'en';
        $this->options['lang_dir']          = 'lang/';
        $this->options['lang_sufix']        = '.lang.php';
        $this->options['remember']          = false;
        $this->options['cookie']            = array('expire' => 0, 'path' => '', 'domain' => '');
        $this->options['debug']             = false;
        $this->options['mail_id']           = '[GENERIC]';
        $this->options['mail']              = 'webmaster@'.$_SERVER['HTTP_HOST'];

        // ==== Replacing the internal values with the external ones ==== //
        if(is_array($options))
        {
            $this->options = array_merge($this->options, $options);
        }

        // ==== Setting up mail options ==== //
        $this->mopt['to']         = $this->options['mail'];
        $this->mopt['subject']    = '[DEBUG]'.$this->options['mail_id'].' ' . __CLASS__ . ' Class';
        $this->mopt['msg']        = '';
        $this->mopt['headers']    = 'MIME-Version: 1.0' . "\r\n";
        $this->mopt['headers']   .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

        // ==== Loading the language ==== //
        $this->loadLanguage();

        // ==== Loading the texts ==== //
        $this->loadTexts();
    }

    /**
     * The method retrieves the current language
     *
     * @param void
     * @return string
     */
    public function getLanguage()
    {
        // ==== Checking the possible locations for a language ==== //
        if(isset($_GET['lang']))
        {
            $lang = $_GET['lang'];
        }
        elseif(isset($_SESSION['lang_'.$this->uq]))
        {
            $lang = $_SESSION['lang_'.$this->uq];
        }
        elseif(isset($_COOKIE['lang_'.$this->uq]) && $this->options['remember'] == true)
        {
            $lang = $_COOKIE['lang_'.$this->uq];
        }
        else
        {
            $lang = $this->options['default_language'];
        }

        // ==== Returning the language ==== //
        return $lang;
    }

    /**
     * The methods loads the language file
     *
     * @param void
     * @return void
     */
    protected function loadLanguage()
    {
        // ==== Generating unique identifier ==== //
        $salt = (defined('__SITE_ROOT__')?__SITE_ROOT__:dirname(__FILE__));

        // ==== Hashing unique ID ==== //
        $this->uq = sha1($salt);

        // ==== Getting the language ==== //
        $lang = $this->getLanguage();

        // ==== Building language file path for the requested language ==== //
        $langfile = $this->options['lang_dir'].$lang.$this->options['lang_sufix'];

        // ==== Checking if the file exists == falling back to default if not ==== //
        if(!is_file($langfile))
        {
            $lang = $this->options['default_language'];

            // ==== Building language file path for the default language ==== //
            $langfile = $this->options['lang_dir'].$lang.$this->options['lang_sufix'];

            // ==== Adding debug data ==== //
            if($this->options['debug'])
            {
                $this->mopt['msg'] .= '<b>Notice:</b> File <i><u>'.$this->file.'</u></i> not found. Falling back to default language.<br /><br />';
            }
        }

        // ==== Intializing or overwriting the session variable ===== //
        $_SESSION['lang_'.$this->uq] = $lang;

        // ==== If rememeber is active ==== //
        if($this->options['remember'] == true
                && isset($this->options['cookie']['expire'])
                && is_numeric($this->options['cookie']['expire'])
                && $this->options['cookie']['expire'] >= 0
                && isset($this->options['cookie']['path'])
                && !empty($this->options['cookie']['path'])
                && isset($this->options['cookie']['domain'])
                && !empty($this->options['cookie']['domain'])
          )
        {
            // ==== Setting the cookie ==== //
            setcookie('lang_'.$this->uq, $lang, time()+$this->options['cookie']['expire'], $this->options['cookie']['path'], $this->options['cookie']['domain']);

            // ==== Setting the cookie var ==== //
            $_COOKIE['lang_'.$this->uq] = $lang;
        }

        // ==== Assign language file path ==== //
        $this->file = $langfile;
    }

    /**
     * The method loads the texts in the class cache
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
                    $log .= '<b>ERROR:</b> File <i><u>'.$this->file.'</u></i> not found.<br /><br />';
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
                        $log .= '<b>ERROR:</b> File <i><u>'.$this->file.'</u></i> not found.<br /><br />';
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
     * The method retrieves a text from the language file
     *
     * @param string $text
     * @param array $data The given data is used to parse the text
     * @return mixed false on fail or string on success
     */
    public function _($txt, array $data=array())
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
                foreach($data as $token => $value)
                {
                    $text = str_replace('{'.$token.'}', $value, $text);
                }
            }

            return $text;
        }
        else
        {
            // ==== Adding debug data ==== //
            if($this->options['debug'])
            {
                $log .= '<b>Notice:</b> Text <i><u>'.$txt.'</u></i> does not exist.<br /><br />';

                // ==== Backtracking ===== //
                $backtrace = debug_backtrace();

                // ==== Gettin line and file of the call ==== //
                $line = $backtrace[0]['line'];
                $file = $backtrace[0]['file'];

                $this->mopt['msg'] .= '<br /><b>Info:</b><br />Line: '.$line.'<br />File: '.$file.'<br /><br />'.$log;
            }

            return false;
        }
    }

    /**
     * Class destructor
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
?>