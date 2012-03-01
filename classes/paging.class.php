<?php
/**
 * 
 * Paging class
 * 
 * @author Brian
 * @link http://brian.serveblog.net
 * @copyright 2011
 * @license Creative Commons Attribution-ShareAlike 3.0
 * 
 * @name Paging
 * @version 2.2
 * 
 * @uses getFullURL function from functions/common.inc.php
 * @uses database object
 * 
 */

class Paging
{
    /**
     * Options array
     *
     * @var array
     */
    private $_options;

    /**
     * URL Object
     *
     * @var object
     */
    private $_url;

    /**
     * Rows the query had
     *
     * @var integer
     */
    private $_rows=0;

    /**
     * Class constructor
     *
     * @param object $url
     * @param array $options
     * @return void
     */
    public function __construct(URL $url, $options=array())
    {
        // ==== Default options ==== //
        $this->_options['ipp'] = 10;

        // ==== Replacing the internal values with the external ones ==== //
        if(is_array($options))
        {
            $this->_options = array_replace($this->_options, $options);
        }

        // ==== Getting the url object ==== //
        $this->_url = $url;
    }

    /**
     * The method calculates the offset for the query
     *
     * @param void
     * @return integer
     */
    private function getOffset()
    {
        if(isset($_GET['page']) && $_GET['page'] != '')
        {
            $page = $_GET['page'];
        }
        else
        {
            $page = 1;
        }

        $ipp = $this->_options['ipp'];
        $offset = ($page-1)*$ipp;

        return $offset;
    }

    /**
     * The method executes the query limiting it to a number of rows
     *
     * @param db_module $db
     * @param string $query
     * @param boolean $complex
     * @return mixed boolean when $complex is set to true. If $complex is set to true, it returns 0 for no results, false for query failed or array of results.
     */
    public function query(db_module $db, $query, $complex = false)
    {
        // ==== Checking if the query was succesfull or not ==== //
        if(!$db->query($query))
        {
            // ==== Returning result ==== //
            return false;
        }
        else
        {
            // ==== Is this a complex search ==== //
            if(!$complex)
            {
                $this->_rows = $db->num_rows();

                // ==== Getting offset ==== //
                $offset = $this->getOffset();

                // ==== Bulding query with LIMIT statement ==== //
                $query .= " LIMIT ".$offset.", ".$this->_options['ipp']."";

                // ==== Executing query ==== //
                $db->query($query);

                // ==== Returning result ==== //
                return true;
            }
            else
            {
                // ==== Array which will store the data ==== //
                $data = array();

                // ==== Getting data ==== //
                if($db->num_rows() != 0)
                {
                    // == Adding data from query to the data array == //
                    while($data[] = $db->fetch_assoc());

                    // == Getting number of rows == //
                    $this->_rows = $db->num_rows();

                    // ==== Getting offset ==== //
                    $offset = $this->getOffset();

                    // ==== Slicing the $data array ==== //
                    $data = array_slice($data, $offset, $this->_options['ipp']);

                    // == Returning result == //
                    return $data;
                }
                else
                {
                    // ==== Returning result ==== //
                    return 0;
                }
            }
        }
    }

    /**
     * The method is used when instead of a query we have an array of elements
     *
     * @param array $array
     * @return array
     */
    public function arrayPaging($array)
    {
        // ==== Checking if the passed parameter is an array ==== //
        if(is_array($array))
        {
            // == Getting number of rows == //
            $this->_rows = count($array);

            // ==== Getting offset ==== //
            $offset = $this->getOffset();

            // ==== Slicing the $data array ==== //
            $array = array_slice($array, $offset, $this->_options['ipp']);
        }
        else
        {
            $array = array();
        }

        // == Returning result == //
        return $array;
    }

    /**
     * The method returns the prper URL
     *
     * @param integer $pagenr
     * @return string
     */
    private function getURL($pagenr)
    {
        // ==== Getting the URL ==== //
        if($pagenr == 1)
        {
            $url = $this->_url->get('', array('page'));
        }
        else
        {
            $url = $this->_url->get('index', array('page' => $pagenr), true);
        }

        return $url;
    }

    /**
     * Paging method that prints out the pages
     *
     * @param boolean $default_layout
     * @return void
     */
    public function show($default_layout=false)
    {
        // ==== Array that holds the data about the links ==== //
        $links = array();

        // ==== Getting page number ==== //
        if(isset($_GET['page']) && $_GET['page'] != '')
        {
            $pagenum = $_GET['page'];
        }
        else
        {
            $pagenum = 1;
        }

        $minpagenum = 1;
        $maxpagenum = 0;
        $numrows    = &$this->_rows;              // Number of rows got from query
        $rownr      = &$this->_options['ipp'];    // Items per page

        //If rows per page not 0
        if($numrows != 0)
        {
            // ==== Getting number of total pages ==== //
            $maxpages = ceil($numrows/$rownr);

            //If there is more the 1 page
            if($maxpages != 1)
            {
                if($default_layout)
                {
                    echo '<br /><center>';
                }

                // ==== Getting minimum page number ==== //
                if($pagenum - 2 <= 0 || $pagenum - 4 <= 0)
                {
                    $minpagenum = 1;
                }
                elseif($pagenum == $maxpages && $pagenum - 4 > 0)
                {
                    $minpagenum = $maxpages - 4;
                }
                else
                {
                    $minpagenum = $pagenum - 2;
                }


                // ==== Getting maximum page number ==== //
                if($pagenum + 4 > $maxpages || $pagenum + 2 > $maxpages)
                {
                    $maxpagenum = $maxpages;
                }
                else
                {
                    $maxpagenum = $pagenum + 2;
                }

                // ==== Getting first & previous page and printing links ==== //
                if($pagenum > 1)
                {
                    // == First page == //
                    $firstpage = $this->getURL(1);
                    $firstpage_txt = 'first';
                    
                    // == Previous page == //
                    $prevpage = $this->getURL($pagenum-1);
                    $prevpage_txt = 'previous';

                    if($default_layout)
                    {
                        echo '<a href="'.$firstpage.'">'.$firstpage_txt.'</a>&nbsp;';
                        echo '&nbsp;&nbsp;<a href="'.$prevpage.'">'.$prevpage_txt.'</a>&nbsp;';
                    }
                    else
                    {
                        $links[] = array($firstpage, $firstpage_txt, true);
                        $links[] = array($prevpage, $prevpage_txt, true);
                    }
                }

                // ==== Printing remaining pages ==== //
                for($page = $minpagenum; $page <= $maxpagenum; $page++)
                {
                        if($page == $pagenum)
                        {
                            if($default_layout)
                            {
                                echo ''.$page.'&nbsp;';
                            }
                            else
                            {
                                $links[] = array(false, $page);
                            }
                        }
                        else
                        {
                            $pageurl = $this->getURL($page);

                            if($default_layout)
                            {
                                echo '<a href="'.$pageurl.'">'.$page.'</a>&nbsp;';
                            }
                            else
                            {
                                $links[] = array($pageurl, $page);
                            }
                        }
                }

                // ==== Getting next & last page and printing links ==== //
                if($pagenum < $maxpages)
                {
                    // == Next page == //
                    $nextpage = $this->getURL($pagenum+1);
                    $nextpage_txt = 'next';

                    // == Last page == //
                    $lastpage = $this->getURL($maxpages);
                    $lastpage_txt = 'last';

                    if($default_layout)
                    {
                        echo '<a href="'.$nextpage.'">Next</a>';
                        echo '&nbsp;&nbsp;<a href="'.$lastpage.'">Last</a>';
                    }
                    else
                    {
                        $links[] = array($nextpage, $nextpage_txt, true);
                        $links[] = array($lastpage, $lastpage_txt, true);
                    }
                }

                if($default_layout)
                {
                    echo '</center>';
                }

                if(!$default_layout)
                {
                    return $links;
                }
            }
        }
    }
}
?>