<?php
/**
 *
 * URL manager class.
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name URL
 * @version 2.9
 *
 */

namespace SPL\URL;

class URL
{
    /**
     * Options array. This contains settings about how the class should act.
     *
     * @var array
     */
    protected $options;

    /**
     * Current url
     *
     * @var string
     */
    protected $url;

    /**
     * Site root
     *
     * @var string
     */
    protected $site_root;

    /**
     * Flag that determins if SSL should be used or not
     *
     * @var boolean
     */
    protected $use_ssl = false;

    /**
     * Flag used to trigger SSL usage only once
     *
     * @var boolean
     */
    protected $tmp_ssl = false;

    /**
     * Array of params that the object will automatically load
     *
     * @var array
     */
    protected $persistent_params = array();

    /**
     * URL params (holds the params found in the URL
     *
     * @var array
     */
    private $url_params = array();

    /**
     * Rewrite active or not
     *
     * @var boolean
     */
    protected $rewrite;

    /**
     * CodeIgniter object
     *
     * @var CodeIgniter
     */
    protected $CI;

    /**
     * Class constructor. It also validates the URL
     *
     * @param array $options
     * @return \URL
     */
    public function __construct(array $options = array())
    {
        // ==== Default options ==== //
        $this->options['site_root']         = '';
        $this->options['site_root_ssl']     = '';
        $this->options['controller']        = 'c';
        $this->options['action']            = 'a';
        $this->options['index_page']        = 'index';
        $this->options['persistent_params'] = array();
        $this->options['rewrite']           = false;
        $this->options['use_get_array']     = true;
        $this->options['secure']            = false;
        $this->options['code_igniter']      = false;
        $this->options['mvc_style']         = false; // URL format similar to the ones used by a MVC

        // ==== Replacing options with custom ones ==== //
        if(count($options) > 0)
        {
            $this->options = array_replace($this->options, $options);
        }

        // ==== Checking if the CodeIgniter support is enabled ==== //
        if($this->options['code_igniter'])
        {
            $this->CI = &get_instance();

            // Getting the site_root
            if (strpos($this->CI->config->item('base_url'), 'http') === 0)
            {
                $this->options['site_root'] = $this->CI->config->item('base_url');
            }
            else
            {
                $this->options['site_root'] = 'http://' . $this->CI->config->item('base_url');
            }

            // ==== Getting some options from CodeIgniter ==== //
            $this->options['site_root_ssl'] = str_replace('http://', 'https://', $this->options['site_root']);  // SITE ROOT SSL
            $this->options['controller']    = $this->CI->config->item('controller_trigger');                    // CONTROLLER TRIGGER
            $this->options['action']        = $this->CI->config->item('function_trigger');                      // FUNCTION TRIGGER
            $this->options['index_page']    = $this->CI->router->routes['default_controller'];                  // INDEX PAGE
            $this->options['mvc_style']     = true;                                                             // ACTIVATING THE MVC STYLE FORMAT
        }

        // ==== Checking if the site_root option has been set ==== //
        if(!empty($this->options['site_root']))
        {
            // ==== Setting rewrite property ==== //
            $this->rewrite = $this->options['rewrite'];

            // ==== Getting URL ==== //
            $this->url = self::getFullURL();

            // ==== Correcting the site root ==== //
            if(strlen($this->options['site_root']) > (strrpos($this->options['site_root'], '/')+1))
            {
                $this->options['site_root'] .= '/';
            }

            // ==== Correcting the URL ==== //
            if($this->rewrite && strlen($this->url) > (strrpos($this->url, '/')+1) && strpos($this->url, '?'.$this->options['controller'].'=') === false)
            {
                $this->url .= '/';
            }

            // ==== Getting the site URL ==== //
            $this->site_root = &$this->options['site_root'];

            // ==== Changing to SSL if that's the case ==== //
            if($this->options['secure'] == true)
            {
                $this->enableSSL();
            }

            // == If invalid == //
            if(\SPL\Validator\URL::isValid($this->options['site_root'], false) === false)
            {
                throw new Exception\InvalidArgumentException('Invalid site root URL. URL: ' . $this->options['site_root']);
            }
            else
            {
                // Loading the params in $_GET
                $this->loadGetParams();

                // ==== Getting the URL data ==== //
                $this->getURLData();

                // ==== Initializing the default params ==== //
                $this->initParams();
            }
        }
        else
        {
            // ==== Triggering error ==== //
            throw new Exception\InvalidArgumentException('The site root parameter is not set.');
        }
    }

    /**
     *
     * Returns the current url (everything in the URL bar)
     *
     * @param void
     * @return string
     */
    public static function getFullURL()
    {
        $protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
        $domain = $_SERVER['SERVER_NAME'];
        $request_uri = $_SERVER['REQUEST_URI'];

        $full_url = $protocol . $domain . $request_uri;

        return $full_url;
    }

    /**
     * Reverses the effect of parse_url
     *
     * @param array $comps
     * @return string
     */
    public static function deparse_url($comps)
    {
        // Building the URL from the components
        $url = (isset($comps['scheme']) ? $comps['scheme'] : '') . '://' .  // Protocol
               (isset($comps['host']) ? $comps['host'] : '') .              // Host
               (isset($comps['port']) ? ':' . $comps['port'] : '') .        // Port
               (isset($comps['path']) ? $comps['path'] : '') .              // Path
               (isset($comps['query']) ? '?' . $comps['query'] : '') .      // Query string
               (isset($comps['fragment']) ? $comps['fragment'] : '');       // Anchor

        // Returning the URL
        return $url;
    }

    /**
     * Gets the site root
     *
     * @param void
     * @return string
     */
    private function getSiteRoot()
    {
        // Default site root
        $site_root = $this->options['site_root'];

        // Parsing the URL
        $parsed_url = parse_url($this->url);

        // Checking if the local site root should be modified
        if($parsed_url['scheme'] == 'https')
        {
            $site_root = $this->options['site_root_ssl'];
        }

        // Returing the site root
        return $site_root;
    }

    /**
     * Loads the GET params
     *
     * @param void
     * @return void
     */
    private function loadGetParams()
    {
        // Going through the elements in the $_GET array
        foreach($_GET as $index => $value)
        {
            $this->setParam($index, rawurldecode(trim($value)));
        }

        // Resseting the $_GET array if we should not use it
        if($this->options['use_get_array'] === false)
        {
            $_GET = array();
        }
    }

/**
     * Retrieves data from the URL string
     *
     * @throws Exception
     * @return void
     */
    protected function getURLData()
    {
        // ==== Setting some default values ==== //
        if($this->getParam($this->options['controller']) === null)
        {
            $this->setParam($this->options['controller'], $this->options['index_page']);
        }

        // ==== Processing the URL only if it's not the site root ==== //
        if($this->getSiteRoot() != $this->url)
        {
            // ==== Check variable to see if site root was found ==== //
            $found_site_root = 0;

            // ==== Creating a local site root copy to be able to handle the decoding of the URL ==== //
            $site_root = $this->getSiteRoot();

            // ==== Removing the site root from the URL ==== //
            $data = str_replace($site_root, '', $this->url, $found_site_root);

            // ==== Checking if something was replaced ==== //
            if($found_site_root != 0)
            {
                // ==== Checking if the data (query string) contains the question mark sign ==== //
                if(strpos(trim($data), '?') !== 0)
                {
                    // ==== Breaking the URL into pieces ==== //
                    $data = explode('/', $data);

                    // ==== Removing the last piece of the array (if it's empty) ==== //
                    if(trim($data[count($data) - 1]) == '')
                    {
                        array_pop($data);
                    }
                }

                // ==== Checking if there is any data to process ==== //
                if(is_array($data) && count($data) > 0)
                {
                    ////////////////////////////////////////////////////////////////
                    //    PROCESSING THE URL - REWRITE ENABLED/FOUND
                    ///////////////////////////////////////////////////////////////
                    // ==== Getting the controller ==== //
                    $this->setParam($this->options['controller'], $data[0]);

                    // Removing from data
                    unset($data[0]);

                    // ==== Getting the method ==== //
                    if($this->options['mvc_style'] && isset($data[1]))
                    {
                        $this->setParam($this->options['action'], $data[1]);

                        // Removing from data
                        unset($data[1]);
                    }

                    // ==== The data should contain an even number of elements ==== //
                    if(count($data)%2 == 0)
                    {
                        // Reindexing the array
                        $data = array_values($data);

                        // ==== Going through the names and building the URL params array ==== //
                        foreach($data as $key => $value)
                        {
                            // Adding the parameter data to the URL params array
                            if($key % 2 == 0)
                            {
                                $this->setParam($data[$key], $data[$key + 1]);
                            }
                        }
                    }

                    // ==== Merging the $_GET array with the $get array ==== //
                    if($this->options['use_get_array'] === true)
                    {
                        $_GET = array_merge($_GET, $this->url_params);
                    }
                }
            }
        }
    }

    /**
     * Initializes the parameters that must be passed along in the URL, with the values found in the URL
     *
     * @param void
     * @return void
     */
    protected function initParams()
    {
        // ==== Checking if the get params option has some info in it ==== //
        if(count($this->options['persistent_params']) > 0)
        {
            // ==== Going through the $_GET params ==== //
            foreach($this->options['persistent_params'] as $name)
            {
                // ==== Checking if the parameter exists ==== //
                if($this->getParam($name) !== null)
                {
                    // ==== Trimming down the param ==== //
                    $value = $this->getParam($name);

                    // ==== Adding parameter to the class parameters ==== //
                    if(!empty($value))
                    {
                        $this->persistent_params[$name] = $value;
                    }
                }
            }
        }
    }

    /**
     * Gets a parameter from the URL
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getParam($name, $default = null)
    {
        // Checking if the parameter exists
        if(isset($this->url_params[$name]))
        {
            return $this->url_params[$name];
        }

        // Returning null if the parameter does not exist
        return $default;
    }

    /**
     * Sets a parameter
     *
     * @param string $name
     * @param mixed $value
     * @return object
     */
    public function setParam($name, $value)
    {
        // Setting the parameters value
        if(!empty($value))
        {
            $this->url_params[$name] = rawurlencode(trim($value));
        }

        // Returning the current object
        return $this;
    }

    /**
     * Retrieves all the URL paramters
     *
     * @param void
     * @return array
     */
    public function getParams()
    {
        return $this->url_params;
    }

    /**
     * Retrieves the current page
     *
     * @param void
     * @return string
     */
    public function getCurrentPage()
    {
        return $this->getParam($this->options['controller']);
    }

    /**
     * Replaces the values in the first array with ones from the second one (similar to array_merge) but then appends the remaining values from the first to the end of the second
     *
     * @param array $array1
     * @param array $array2
     * @return array
     */
    protected static function array_append($array1, $array2)
    {
        // ==== Going through the first array ==== //
        foreach($array1 as $idx => $value)
        {
            if(isset($array2[$idx]))
            {
                $array1[$idx] = $array2[$idx];

                unset($array2[$idx]);
            }
        }

        // ==== Adding the elements of the first array to the end of the second one ==== //
        $array = array_merge($array1, $array2);

        // ==== result ==== //
        return $array;
    }

    /**
     * Changes the site root to the SSL one
     *
     * @param void
     * @return object
     */
    public function enableSSL()
    {
        // ==== Checking if the SSL site root is even set ==== //
        if(!empty($this->options['site_root_ssl']))
        {
            $this->use_ssl = true;
        }
        else
        {
            // ==== Triggering an error ==== //
            Exception\RuntimeException('To switch to SSL you need to set the site_root_ssl option.');
        }

        return $this;
    }

    /**
     * Changes the site root to the non-SSL one
     *
     * @param void
     * @return object
     */
    public function disableSSL()
    {
        // ==== Checking if the SSL site root is even set ==== //
        if(!empty($this->options['site_root']))
        {
            $this->use_ssl = false;
        }
        else
        {
            // ==== Triggering an error ==== //
            throw new Exception\RuntimeException('To switch to non-SSL you need to set the site_root option.');
        }

        return $this;
    }

    /**
     * Used to trigger the temporary SSL (when you want SSL for a single link)
     *
     * @param void
     * @return object
     */
    public function ssl()
    {
        // ==== Checking if the SSL site root is even set ==== //
        if(!empty($this->options['site_root_ssl']))
        {
            $this->tmp_ssl = true;
        }
        else
        {
            // ==== Triggering an error ==== //
            Exception\RuntimeException('To switch to SSL you need to set the site_root_ssl option.');
        }

        return $this;
    }

    /**
     * Builds the URL using the provided params
     *
     * @param string $page Page to link to
     * @param array $params Parameters that must be added to the URL. If an empty string is provided for the page parameter then the params given here will be removed from the URL. In the latter case if no params are given all the $_GET params will be removed.
     * @param boolean $merge_get When set to true the method will merge $_GET with $params if the request points to the current page
     * @return string
     */
    public function get($page = '', array $params = array(), $merge_get = false)
    {
        // Default site root to use
        $url = $this->options['site_root'];

        // ==== Getting the SSL site root if required ==== //
        if($this->use_ssl === true || $this->tmp_ssl === true)
        {
            $url = $this->options['site_root_ssl'];

            // Disabing the temporary SSL
            $this->tmp_ssl = false;
        }

        // Link to the same page but with different params (this includes the $_GET params)
        if($page == $this->getCurrentPage() && $merge_get === true)
        {
            // ==== If the page is exactly the same as the one the user is on then take all the $_GET parameters ==== //
            $params = self::array_append($this->getParams(), $params);
        }
        // New page with params that must be automaticaly loaded
        else
        {
            // ===== Checking if we should merge the GET ==== //
            if($merge_get === true)
            {
                $params = self::array_append($this->getParams(), $params);
            }

            // ==== Adding default params ==== //
            $params = self::array_append($params, $this->persistent_params);
        }

        // ==== Failsafes for when CI support is enabled ==== //
        if($this->options['mvc_style'])
        {
            // Controller param
            if(!isset($params[$this->options['controller']]))
            {
                $params[$this->options['controller']] = $page;
            }

            // Adding the default params only if params count is higher then 1
            if(count($params) > 1)
            {
                // Method param
                if(!isset($params[$this->options['action']]))
                {
                    $params[$this->options['action']] = 'index';
                }
            }
        }

        // ==== Processing the data to generate the URL ==== //
        if($this->rewrite)
        {
            ////////////////////////////////////////////////////////////////
            //    REWRITE ENABLED
            ///////////////////////////////////////////////////////////////
            // ==== Checking if the URL is to be build using the MVC style ==== //
            if($this->options['mvc_style'])
            {
                // ==== Building the firs part of the URL ==== //
                $url .= $params[$this->options['controller']] . '/';

                // ==== Checking for the rest of the params ==== //
                if(isset($params[$this->options['action']]))
                {
                     $url .= $params[$this->options['action']] . '/';
                }

                // ==== Building the omit array ==== //
                $omit_array = array(
                    $this->options['controller'],
                    $this->options['action']
                );
            }
            else
            {
                // ==== Adding the requested page to the URL ==== //
                $url .= $page . '/';

                // ==== Building the omit array ==== //
                $omit_array = array(
                    $this->options['controller']
                );
            }

            // ==== Going through the params and building the URL ==== //
            foreach($params as $name => $value)
            {
                // ==== Skipping the page token if present ==== //
                if(in_array($name, $omit_array))
                {
                    continue;
                }

                // ==== Adding the parameter to the URL ==== //
                if(trim($value) != '')
                {
                    $url .= $name . '/' . $value . '/';
                }
            }
        }
        elseif(!empty($page))
        {
            ////////////////////////////////////////////////////////////////
            //    REWRITE DISABLED
            ///////////////////////////////////////////////////////////////
            // ==== Checking if the URL is to be build using the MVC style ==== //
            if($this->options['mvc_style'])
            {
                // ==== Building the firs part of the URL ==== //
                $url .= '?' . $this->options['controller'] . '=' . $params[$this->options['controller']];


                // ==== Checking for the rest of the params ==== //
                if(isset($params[$this->options['action']]))
                {
                    $url .= '&' . $this->options['action'] . '=' . $params[$this->options['action']];
                }

                // ==== Building the omit array ==== //
                $omit_array = array(
                    $this->options['controller'],
                    $this->options['action']
                );
            }
            else
            {
                // ==== Adding the requested page to the URL ==== //
                $url .= '?' . $this->options['controller'] . '=' . $page;

                // ==== Building the omit array ==== //
                $omit_array = array(
                    $this->options['controller']
                );
            }

            // ==== Going through the params and building the URL ==== //
            foreach($params as $name => $value)
            {
                // ==== Skipping the page token if present ==== //
                if(in_array($name, $omit_array))
                {
                    continue;
                }

                // ==== Adding the parameter to the URL ==== //
                if(trim($value) != '')
                {
                    $url .= '&' . $name . '=' . $value;
                }
            }
        }

        // ==== Returning result ==== //
        return $url;
    }
}