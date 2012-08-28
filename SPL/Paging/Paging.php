<?php
/**
 *
 * Paging class
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @uses URL object
 *
 * @name Paging
 * @version 2.3.1
 *
 */

namespace SPL\Paging;

use SPL\URL;


class Paging
{
    /**
     * Options array
     *
     * @var array
     */
    private $options;

    /**
     * URL Object
     *
     * @var URL
     */
    private $url;

    /**
     * Rows the query had
     *
     * @var integer
     */
    private $rows=0;

    /**
     * Sets the URL object and the provided options
     *
     * @param object $url
     * @param array $options
     * @return void
     */
    public function __construct(URL\URL $url, array $options = array())
    {
        // ==== Default options ==== //
        $this->options['ipp'] = 10;
        $this->options['pages'] = 5;

        // ==== Replacing the internal values with the external ones ==== //
        if(count($options) > 0)
        {
            $this->options = array_replace($this->options, $options);
        }

        // ==== Getting the url object ==== //
        $this->url = $url;
    }

    /**
     * Calculates the offset for the query
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

        $ipp = $this->options['ipp'];
        $offset = ($page-1)*$ipp;

        return $offset;
    }

    /**
     * Executes the query limiting it to a number of rows
     *
     * @param db_module $db
     * @param string $query
     * @param boolean $complex
     * @return mixed boolean when $complex is set to true. If $complex is set to true, it returns 0 for no results, false for query failed or array of results.
     */
    public function query(db_module &$db, $query, $complex = false)
    {
        // ==== Checking if the query was succesfull or not ==== //
        if(!$db->query($query))
        {
            // ==== Returning result ==== //
            return false;
        }

        // ==== Is this a complex search ==== //
        if(!$complex)
        {
            $this->rows = $db->num_rows();

            // ==== Getting offset ==== //
            $offset = $this->getOffset();

            // ==== Bulding query with LIMIT statement ==== //
            $query .= " LIMIT " . $offset . ", " . $this->options['ipp'] . "";

            // ==== Executing query ==== //
            $db->query($query);

            // ==== Returning result ==== //
            return true;
        }

        // ==== Array which will store the data ==== //
        $data = array();

        // ==== Getting data ==== //
        if($db->numrows() != 0)
        {
            // == Adding data from query to the data array == //
            while($data[] = $db->fetch_assoc());

            // == Getting number of rows == //
            $this->rows = $db->num_rows();

            // ==== Getting offset ==== //
            $offset = $this->getOffset();

            // ==== Slicing the $data array ==== //
            $data = array_slice($data, $offset, $this->options['ipp']);

            // == Returning result == //
            return $data;
        }

        // ==== Returning result ==== //
        return 0;
    }

    /**
     * Used when instead of a query we have an array of elements
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
            $this->rows = count($array);

            // ==== Getting offset ==== //
            $offset = $this->getOffset();

            // ==== Slicing the $data array ==== //
            $array = array_slice($array, $offset, $this->options['ipp']);
        }
        else
        {
            $array = array();
        }

        // == Returning result == //
        return $array;
    }

    /**
     * Returns the proper URL
     *
     * @param integer $pagenr
     * @return string
     */
    private function getURL($pagenr)
    {
        // ==== Getting the URL ==== //
        if($pagenr == 1)
        {
            $url = $this->url->get('', array('page'));
        }
        else
        {
            $url = $this->url->get('index', array('page' => $pagenr), true);
        }

        return $url;
    }

    /**
     * Prints the pages
     *
     * @param boolean $default_layout
     * @return void
     */
    public function show($default_layout = false)
    {
        // ==== Array that holds the data about the links ==== //
        $links = array();

        // ==== Getting page number ==== //
        if(isset($_GET['page']) && $_GET['page'] != '' && $_GET['page'] > 1)
        {
            $pagenum = $_GET['page'];
        }
        else
        {
            $pagenum = 1;
        }

        $minpagenum = 1;                          // Default minimum page number
        $maxpagenum = 0;                          // Default maximum page number
        $numrows    = &$this->rows;              // Number of rows got from query
        $ipp        = &$this->options['ipp'];    // Items per page
        $pages      = &$this->options['pages'];  // Pages displayed

        //If rows per page not 0
        if($numrows != 0)
        {
            // ==== Getting number of total pages ==== //
            $maxpages = ceil($numrows/$ipp);

            // ==== Failsafe for the number of pages to display ==== //
            if($maxpages < $pages)
            {
                $pages = $maxpages;
            }

            // ==== Failsafe in case the requested page is out of range ==== //
            if($pagenum > $maxpages)
            {
                $pagenum = 1;
            }

            //If there is more the 1 page
            if($maxpages != 1)
            {
                // ==== Identifiers ==== //
                $margin = $pages - 1;
                $middle = floor($margin/2);

                // ==== Default layout ==== //
                if($default_layout)
                {
                    echo '<br /><center>';
                }

                // ==== Getting minimum page number ==== //
                if($pagenum - $middle <= 0) // In range of the first page
                {
                    $minpagenum = 1;
                }
                elseif($maxpages - $pagenum - $middle < 0) // Max. pages shown is not reached
                {
                    $minpagenum = $maxpages - $pages + 1;
                }
                else // Reached magimum number of showed pages
                {
                    $minpagenum = $pagenum - $middle;
                }


                // ==== Getting maximum page number ==== //
                if($pagenum + $middle >= $maxpages) // In range of the last page
                {
                    $maxpagenum = $maxpages;
                }
                elseif($minpagenum + $pagenum + $middle <= $pages) // Max. pages shown is not reached
                {
                    $maxpagenum = $pages;
                }
                else // Reached maximum number of showed pages
                {
                    $maxpagenum = $pagenum + $middle;
                }

                // ==== Getting first & previous page and printing links ==== //
                if($pagenum > 1)
                {
                    // == First page == //
                    $firstpage = $this->getURL(1);
                    $firstpage_txt = 'First';

                    // == Previous page == //
                    $prevpage = $this->getURL($pagenum-1);
                    $prevpage_txt = 'Previous';

                    // ==== Default layout ==== //
                    if($default_layout)
                    {
                        echo '<a href="'.$firstpage.'">'.$firstpage_txt.'</a>&nbsp;';
                        echo '&nbsp;&nbsp;<a href="'.$prevpage.'">'.$prevpage_txt.'</a>&nbsp;';
                    }
                    else // Array layout
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
                            // ==== Default layout ==== //
                            if($default_layout)
                            {
                                echo ''.$page.'&nbsp;';
                            }
                            else // Array layout
                            {
                                $links[] = array(false, $page);
                            }
                        }
                        else
                        {
                            $pageurl = $this->getURL($page);

                            // ==== Default layout ==== //
                            if($default_layout)
                            {
                                echo '<a href="'.$pageurl.'">'.$page.'</a>&nbsp;';
                            }
                            else // Array layout
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

                    // ==== Default layout ==== //
                    if($default_layout)
                    {
                        echo '<a href="'.$nextpage.'">Next</a>';
                        echo '&nbsp;&nbsp;<a href="'.$lastpage.'">Last</a>';
                    }
                    else // Array layout
                    {
                        $links[] = array($nextpage, $nextpage_txt, true);
                        $links[] = array($lastpage, $lastpage_txt, true);
                    }
                }

                // ==== Default layout ==== //
                if($default_layout)
                {
                    echo '</center>';
                }

                // ==== Array layout ==== //
                if(!$default_layout)
                {
                    return $links;
                }
            }
        }
    }
}