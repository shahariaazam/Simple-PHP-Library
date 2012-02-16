<?php
/**
 *
 * URL manager class.
 *
 * @author Brian
 * @link http://brian.serveblog.net
 * @copyright 2011
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name URL
 * @version 2.0
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
    private $_options;

    /**
     * Current url
     *
     * @var string
     */
    private $_url;

    /**
     * What page is the user in
     *
     * @var string
     */
    private $_page;

    /**
     * Hold the pattern for the rewrite
     *
     * @var string
     */
    private $_pattern;

    /**
     * Array of params that the object will automatically load
     *
     * @var array
     */
    private $_params = array();

    /**
     * Rewrite active or not
     *
     * @var boolen
     */
    private $_rewrite;

    /**
     * Class constructor. It also validates the URL
     *
     * @param string $url
     * @return boolean
     */
    public function __construct($options=array())
    {
        // ==== Default options ==== //
        $this->_options['site_root']      = '';
        $this->_options['page_token']     = 'goto';
        $this->_options['get_params']     = array();
        $this->_options['rewrite']        = false;

        // ==== Replacing options with custom ones ==== //
        if(is_array($options))
        {
            $this->_options = array_replace($this->_options, $options);
        }

        // ==== Setting rewrite property ==== //
        $this->_rewrite = $this->_options['rewrite'];

        // ==== Getting URL ==== //
        $this->_url = getFullURL();

        // ==== Correcting the site root ==== //
        if(strlen($this->_options['site_root']) > (strrpos($this->_options['site_root'], '/')+1))
        {
            $this->_options['site_root'] .= '/';
        }

        // ==== Correcting the URL ==== //
        if($this->_rewrite && strlen($this->_url) > (strrpos($this->_url, '/')+1) && strpos($this->_url, '?'.$this->_options['page_token'].'=') === false)
        {
            $this->_url .= '/';
        }

        // ==== Getting the URL data ==== //
        $this->getURLData();

        // ==== Initializing the default params ==== //
        $this->initParams();

        // ==== Determining if the URL is valid ==== //
        $is_valid = filter_var($this->_url, FILTER_VALIDATE_URL);

        // == If invalid == //
        if($is_valid === false)
        {
            trigger_error('Invalid URL. The URL class could not process the URL. URL: '.$this->url, E_USER_WARNING);
        }
    }

    /**
     * The method initializes the parameters that must be passed along in the URL, with the values
     * found in the URL
     *
     * @param void
     * @return void
     */
    private function initParams()
    {
        // ==== Checking if the get params option has some info in it ==== //
        if(count($this->_options['get_params']) > 0)
        {
            // ==== Going through the $_GET params ==== //
            foreach($this->_options['get_params'] as $name)
            {
                // ==== Checking if the parameter exists ==== //
                if(isset($_GET[$name]))
                {
                    // ==== Trimming down the param ==== //
                    $value = trim($_GET[$name]);

                    // ==== Adding parameter to the class parameters ==== //
                    if(!empty($value))
                    {
                        $this->_params[$name] = $value;
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
    private function getURLData()
    {
        // ==== Processing the URL only if it's not the site root ==== //
        if($this->_options['site_root'] != $this->_url)
        {
            ////////////////////////////////////////////////////////////////
            //    PROCESSING THE URL - REWRITE ENABLED/FOUND
            ///////////////////////////////////////////////////////////////
            // ==== Removing the site root from the URL ==== //
            $data = str_replace($this->_options['site_root'], '', $this->_url);

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
                $this->_page = $data[0];

                // ==== Putting the current page in $_GET ==== //
                $_GET[$this->_options['page_token']] = $this->_page;

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
                if(isset($_GET[$this->_options['page_token']]))
                {
                    $this->_page = $_GET[$this->_options['page_token']];
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
    private static function array_merge_v2($array1, $array2)
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
     * The method builds the URL with the available data
     *
     * @param string $page Page to link to
     * @param array $params Parameters that must be added to the URL. If an empty string is provided for the page parameter then the params given here will be removed from the URL. In the latter case if no params are given all the $_GET params will be removed.
     * @param boolean $merge_get When set to true the method will merge $_GET with $params if the request points to the current page
     * @return string
     */
    public function get($page='', array $params=array(), $merge_get=false)
    {
        // ==== Defaults ==== //
        $url = $this->_options['site_root'];

        // ==== Checking if a page has actualy been requested ==== //
        if(empty($page)) // Base link to the same page without the given params
        {
            // ==== Defaulting to the current page ==== //
            $page = $this->_page;

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
        elseif($page == $this->_page && $merge_get === true) // Link to the same page but with different params (this includes the $_GET params)
        {
            // ==== If the page is exactly the same as the one the user is on then take all the $_GET parameters ==== //
            $params = self::array_merge_v2($_GET, $params);
        }
        else // New page with params that must be automaticaly loaded
        {
            // ==== Adding default params ==== //
            $params = self::array_merge_v2($this->_params, $params);
        }

        // ==== Removing the page token from the params ==== //
        if(isset($params[$this->_options['page_token']]))
        {
            unset($params[$this->_options['page_token']]);
        }

        // ==== Processing the data to generate the URL ==== //
        if($this->_rewrite)
        {
            ////////////////////////////////////////////////////////////////
            //    REWRITE ENABLED
            ///////////////////////////////////////////////////////////////
            // ==== Adding the requested page to the URL ==== //
            $url .= $page.'/';

            // ==== Going through the params and building the URL ==== //
            foreach($params as $name => $value)
            {
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
            $url .= '?'.$this->_options['page_token'].'='.$page;

            // ==== Going through the params and building the URL ==== //
            foreach($params as $param => $value)
            {
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
?>