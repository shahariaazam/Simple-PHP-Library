<?php
/**
 *
 * URL manager class.
 *
 * @author Brian
 * @link http://brian.hopto.org/wiki/hypermvc/
 * @copyright 2011
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name URL
 * @version 2.5
 *
 * @uses getFullURL function from functions/common.inc.php
 *
 */

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
     * Temporary site root for SSL URL generation
     *
     * @var string
     */
    protected $site_root_tmp = false;

    /**
     * What page is the user in
     *
     * @var string
     */
    protected $page;

    /**
     * Hold the pattern for the rewrite
     *
     * @var string
     */
    protected $pattern;

    /**
     * Array of params that the object will automatically load
     *
     * @var array
     */
    protected $params = array();

    /**
     * Rewrite active or not
     *
     * @var boolen
     */
    protected $rewrite;

    /**
     * Class constructor. It also validates the URL
     *
     * @param string $url
     * @return boolean
     */
    public function __construct($options=array())
    {
        // ==== Default options ==== //
        $this->options['site_root']      = '';
        $this->options['site_root_ssl']  = '';
        $this->options['page_token']     = 'goto';
        $this->options['index_page']     = 'index';
        $this->options['get_params']     = array();
        $this->options['rewrite']        = false;
        $this->options['secure']         = false;

        // ==== Checking if the site_root option has been set ==== //
        if(!empty($options['site_root']))
        {
            // ==== Replacing options with custom ones ==== //
            if(is_array($options))
            {
                $this->options = array_replace($this->options, $options);
            }

            // ==== Setting rewrite property ==== //
            $this->rewrite = $this->options['rewrite'];

            // ==== Getting URL ==== //
            $this->url = getFullURL();

            // ==== Correcting the site root ==== //
            if(strlen($this->options['site_root']) > (strrpos($this->options['site_root'], '/')+1))
            {
                $this->options['site_root'] .= '/';
            }

            // ==== Correcting the URL ==== //
            if($this->rewrite && strlen($this->url) > (strrpos($this->url, '/')+1) && strpos($this->url, '?'.$this->options['page_token'].'=') === false)
            {
                $this->url .= '/';
            }

            // ==== Getting the site URL ==== //
            $this->_site_root = $this->options['site_root'];

            // ==== Changing to SSL if that's the case ==== //
            if($this->options['secure'] == true)
            {
                $this->enableSSL();
            }

            // ==== Getting the URL data ==== //
            $this->getURLData();

            // ==== Initializing the default params ==== //
            $this->initParams();

            // ==== Determining if the URL is valid ==== //
            $is_valid = filter_var($this->url, FILTER_VALIDATE_URL);

            // == If invalid == //
            if($is_valid === false)
            {
                trigger_error('Invalid URL. The URL class could not process the URL. URL: '.$this->url, E_USER_WARNING);
            }
        }
        else
        {
            // ==== Triggering error ==== //
            exit('The site root is not set.');
        }
    }

    /**
     * The method initializes the parameters that must be passed along in the URL, with the values
     * found in the URL
     *
     * @param void
     * @return void
     */
    protected function initParams()
    {
        // ==== Checking if the get params option has some info in it ==== //
        if(count($this->options['get_params']) > 0)
        {
            // ==== Going through the $_GET params ==== //
            foreach($this->options['get_params'] as $name)
            {
                // ==== Checking if the parameter exists ==== //
                if(isset($_GET[$name]))
                {
                    // ==== Trimming down the param ==== //
                    $value = trim($_GET[$name]);

                    // ==== Adding parameter to the class parameters ==== //
                    if(!empty($value))
                    {
                        $this->params[$name] = $value;
                    }
                }
            }
        }
    }

    /**
     * The method retrives data from the URL
     *
     * @param void
     * @return void
     */
    protected function getURLData()
    {
        // ==== Setting some default values ==== //
        if(!isset($_GET[$this->options['page_token']]))
        {
            $_GET[$this->options['page_token']] = $this->options['index_page'];
        }

        // ==== Processing the URL only if it's not the site root ==== //
        if($this->_site_root != $this->url)
        {
            // ==== Creating a local site root copy to be able to handle the decoding of the URL ==== //
            $site_root = $this->_site_root;

            // ==== Getting the actual protocol used to access the site ==== //
            $protocol = isset($_SERVER['HTTPS'])?'https://':'http://';

            // ==== Matches array for the preg match ==== //
            $matches = array();

            // ==== Getting matches ==== //
            preg_match('((http://)|(https://))', $site_root, $matches);

            // ==== Getting the site root protocol ==== //
            if(isset($matches[0]))
            {
                // ==== Replacing the site root protocol with the actual protocol so that we can remove the site root from the URL ==== //
                $site_root = str_replace($matches[0], $protocol, $site_root);
            }
            else
            {
                // ==== Creating the correct site root ==== //
                $site_root = $protocol.$site_root;

                // ==== Adjusting the site root ==== //
                $this->options['site_root'] = $site_root;
            }

            // ==== Check variable to see if site root was found ==== //
            $found_site_root = 0;
            

            ////////////////////////////////////////////////////////////////
            //    PROCESSING THE URL - REWRITE ENABLED/FOUND
            ///////////////////////////////////////////////////////////////
            // ==== Removing the site root from the URL ==== //
            $data = str_replace($site_root, '', $this->url, $found_site_root);

            // ==== Checking if something was replaced ==== //
            if($found_site_root != 0)
            {
                // ==== Breaking the URL into pieces ==== //
                $data = explode('/', $data);

                // ==== Removing the last piece of the array ==== //
                array_pop($data);

                // ==== Checking if there is any data to process ==== //
                if(count($data) > 0)
                {
                    // ==== Temporary get holder ==== //
                    $get = array();

                    // ==== Getting the page ==== //
                    $this->page = $data[0];

                    // ==== Putting the current page in $_GET ==== //
                    $_GET[$this->options['page_token']] = $this->page;

                    // ==== Removing the page from the data array ==== //
                    unset($data[0]);

                    // ==== The data should contain an even number of elements ==== //
                    if(count($data)%2 == 0)
                    {
                        // ==== Going through the data ==== //
                        foreach($data as $idx => $value)
                        {
                            // ==== Checking if this should be skipped ==== //
                            if($idx%2 != 0)
                            {
                                $get[$value] = $data[$idx+1];
                            }
                        }
                    }

                    // ==== Merging the $_GET array with the $get array ==== //
                    $_GET = array_merge($_GET, $get);
                }
                else
                {
                    ////////////////////////////////////////////////////////////////
                    //    PROCESSING THE URL - REWRITE DISABLED/NOT FOUND
                    ///////////////////////////////////////////////////////////////
                    // ==== Getting the current page ==== //
                    if(isset($_GET[$this->options['page_token']]))
                    {
                        $this->page = $_GET[$this->options['page_token']];
                    }
                }
            }
        }
    }

    /**
     * The method replaces the values in the first array with ones from the second one (similar to array_merge) but then appends the remaining values
     * from the first to the end of the second
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
     * The method changes the site root to the SSL one
     *
     * @param void
     * @return void
     */
    public function enableSSL()
    {
        // ==== Checking if the SSL site root is even set ==== //
        if(!empty($this->options['site_root_ssl']))
        {
            $this->_site_root = $this->options['site_root_ssl'];
        }
        else
        {
            // ==== Triggering an error ==== //
            trigger_error('To switch to SSL you need to set the site_root_ssl option.', E_USER_WARNING);
        }        
    }

    /**
     * The method changes the site root to the non-SSL one
     *
     * @param void
     * @return void
     */
    public function disableSSL()
    {
        // ==== Checking if the SSL site root is even set ==== //
        if(!empty($this->options['site_root']))
        {
            $this->_site_root = $this->options['site_root'];
        }
        else
        {
            // ==== Triggering an error ==== //
            trigger_error('To switch to non-SSL you need to set the site_root option.', E_USER_WARNING);
        }
    }

    /**
     * The method returns the HTTPS URL.
     * It uses the get method to do this
     *
     * @param string $page Page to link to
     * @param array $params Parameters that must be added to the URL. If an empty string is provided for the page parameter then the params given here will be removed from the URL. In the latter case if no params are given all the $_GET params will be removed.
     * @param boolean $merge_get When set to true the method will merge $_GET with $params if the request points to the current page
     * @return string
     */
    public function get_ssl($page='', array $params=array(), $merge_get=false)
    {
        // ==== Checking if the SSL site root is even set ==== //
        if(!empty($this->options['site_root_ssl']))
        {
            // ==== Setting the temporary site root ==== //
            $this->site_root_tmp = $this->options['site_root_ssl'];

            // ==== Getting the URL ==== //
            $url = $this->get($page, $params, $merge_get);

            // ==== Resetting the temporary site root ==== //
            $this->site_root_tmp = false;
        }
        else
        {
            // ==== Dummy URL ==== //
            $url = '#no_ssl_found';

            // ==== Triggering an error ==== //
            trigger_error('To generate an URL using the SSL site root you need to set the site_root_ssl option.', E_USER_WARNING);
        }

        // ==== Returning the URL ==== //
        return $url;
    }

    /**
     * The method builds the URL with the available data
     *
     * @param string $page Page to link to
     * @param array $params Parameters that must be added to the URL. If an empty string is provided for the page parameter then the params given here will be removed from the URL. In the latter case if no params are given all the $_GET params will be removed.
     * @param boolean $merge_get When set to true the method will merge $_GET with $params if the request points to the current page
     * @return string
     */
    public function get($page='', array $params=array(), $merge_get=false)
    {
        // ==== Default URL (actually it's the site root) ==== //
        if($this->site_root_tmp !== false)
        {
            $url = $this->site_root_tmp;
        }
        else
        {
            $url = $this->_site_root;
        }

        // ==== Checking if a page has actualy been requested ==== //
        if(empty($page)) // Base link to the same page without the given params
        {
            // ==== Defaulting to the current page ==== //
            $page = $this->page;

            // ==== Parameters should be present in order to remove them from the URL ==== //
            if(count($params) > 0)
            {
                // ==== If a blank page was given then the given params will be removed ==== //
                foreach($params as $param)
                {
                    // ==== Removing the parameter URL ==== //
                    if(isset($_GET[$param]))
                    {
                        unset($_GET[$param]);
                    }
                }

                // ==== Adding the parameters from $_GET ==== //
                $params = $_GET;
            }
        }
        elseif($page == $this->page && $merge_get === true) // Link to the same page but with different params (this includes the $_GET params)
        {
            // ==== If the page is exactly the same as the one the user is on then take all the $_GET parameters ==== //
            $params = self::array_append($_GET, $params);
        }
        else // New page with params that must be automaticaly loaded
        {
            // ===== Checking if we should merge the GET ==== //
            if($merge_get === true)
            {
                $params = self::array_append($_GET, $params);
            }

            // ==== Adding default params ==== //
            $params = self::array_append($params, $this->params);
        }

        // ==== Removing the page token from the params ==== //
        if(isset($params[$this->options['page_token']]))
        {
            unset($params[$this->options['page_token']]);
        }

        // ==== Processing the data to generate the URL ==== //
        if($this->rewrite)
        {
            ////////////////////////////////////////////////////////////////
            //    REWRITE ENABLED
            ///////////////////////////////////////////////////////////////
            // ==== Adding the requested page to the URL ==== //
            $url .= $page.'/';

            // ==== Going through the params and building the URL ==== //
            foreach($params as $name => $value)
            {
                // ==== Skipping the page token if present ==== //
                if($name == $this->options['page_token'])
                {
                    continue;
                }

                // ==== Adding the parameter to the URL ==== //
                if(!empty($value))
                {
                    $url .= $name.'/'.$value.'/';
                }
            }
        }
        elseif(!empty($page))
        {
            ////////////////////////////////////////////////////////////////
            //    REWRITE DISABLED
            ///////////////////////////////////////////////////////////////
            // ==== Adding the requested page to the URL ==== //
            $url .= '?'.$this->options['page_token'].'='.$page;

            // ==== Going through the params and building the URL ==== //
            foreach($params as $param => $value)
            {
                // ==== Skipping the page token if present ==== //
                if($name == $this->options['page_token'])
                {
                    continue;
                }

                // ==== Adding the parameter to the URL ==== //
                if(!empty($value))
                {
                    $url .= '&'.$param.'='.$value;
                }
            }
        }

        // ==== Returning result ==== //
        return $url;
    }
}