<?php

/**
 * Benchmark class
 *
 * @author Brian
 * @link http://brian.hopto.org/wiki/hypermvc/
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name Benchmark
 * @version 1.5
 */

namespace SPL\Benchmark;

class Benchmark
{
    /**
     * Internal log of problems
     *
     * @var string
     */
    private $log;

    /**
     * Trigger variable for mail
     *
     * @var boolean
     */
    private $trigger;

    /**
     * Variable that holds the options for the debug mail
     *
     * @var array
     */
    private $mopt;

    /**
     * Variable that contains options for the class
     *
     * @var array
     */
    private $options;

    /**
     * Results array for exec time
     *
     * @var array
     */
    private $res_time;

    /**
     * Results array for memory usage
     *
     * @var array
     */
    private $res_mem;

    /**
     * Temporary array stat stores the start of each exec time test
     *
     * @var array
     */
    private $tmp_time;

    /**
     * Temporary array stat stores the start of each memory usage test
     *
     * @var array
     */
    private $tmp_mem;

    /**
     * Determins if the class should gather data or not
     *
     * @var boolean
     */
    private $enabled = true;

    /**
     * Class constructor
     *
     * @param array $options
     * @return void
     */
    public function __construct($options=array())
    {
        // ==== Initializing default values ==== //
        $this->log = '';

        // ==== Default $options ==== //
        $this->options['unique_mail']     = '';
        $this->options['threshold']       = 3000;   // Milliseconds
        $this->options['results']         = 'manual'; //Available options: manual, show, false; the last one deactivates the benchmark
        $this->options['debug']           = false;
        $this->options['mail']            = 'webmaster@' . $_SERVER['HTTP_HOST'];

        // ==== Replacing the internal values with the external ones ==== //
        if(is_array($options))
        {
            $this->options = array_merge($this->options, $options);
        }

        // ==== Determine if the class should not gather data ==== //
        if($this->options['results'] === false)
        {
            $this->enabled = false;
        }

        // ==== Setting up mail options ==== //
        $this->mopt['to']               = $this->options['mail'];
        $this->mopt['subject']          = '[DEBUG] ' . __CLASS__ . ' Class ' . $this->options['unique_mail'];
        $this->mopt['subject_tests']    = '[RESULTS] ' . __CLASS__ . ' Class ' . $this->options['unique_mail'];
        $this->mopt['msg']              = '';
        $this->mopt['headers']          = 'MIME-Version: 1.0' . "\r\n";
        $this->mopt['headers']         .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    }

    /**
     * The method disables the benchmark
     *
     * @param void
     * @return void
     */
    public function disable()
    {
        $this->enabled = false;
    }

    /**
     * The method kills all output that this class might output
     *
     * @param void
     * @return void
     */
    public function kill()
    {
        $this->disable();

        $this->options['debug'] = false;
    }

    /**
     * The method retrieves an array of data regarding the requested benchmark
     *
     * @param string $id
     * @return array on success or false on fail
     */
    public function get($id)
    {
        // ==== Check variable ==== //
        $isOk = true;

        // ==== Array of results ==== //
        $results = array();

        // ==== Checking if the results exist ==== //
        if(isset($this->res_time[$id]) && isset($this->res_mem[$id]))
        {
            $results['time'] = $this->res_time[$id];
            $results['memory'] = $this->res_mem[$id];
        }
        else
        {
            $isOk = false;
        }

        // ==== Returning result ==== //
        if($isOk)
        {
            return $results;
        }
        else
        {
            return false;
        }
    }

    /**
     * The method adds the starting point for the benchmark
     *
     * @param string $id
     * @return void
     */
    public function start($id)
    {
        // ==== Checking if the benchmark is enabled ==== //
        if($this->enabled)
        {
            // ==== Starting benchmark if it does not exist in the temporary arrays ==== //
            if(!isset($this->res_time[$id]) && !isset($this->res_mem[$id]))
            {
                // ==== Getting proper microtime ==== //
                $microtime = microtime(true);

                // ==== Storing test start info ==== //
                $this->tmp_time[$id] = $microtime;
                $this->tmp_mem[$id] = memory_get_usage();
            }
            else
            {
                // ==== Backtracking ===== //
                $backtrace = debug_backtrace();

                // ==== Gettin line and file of the call ==== //
                $line = $backtrace[0]['line'];
                $file = $backtrace[0]['file'];

                // ==== Adding debug data ==== //
                $this->log .= '<b>Notice:</b> You tried to start a benchmark with an existing ID (' . $id . ') at line ' . $line . ' in file ' . $file . '<br /><br />';
            }
        }
    }

    /**
     * The method stops the benchmark for a certain uniqueid
     *
     * @param string $id
     * @return void
     */
    public function stop($id)
    {
        // ==== Checking if the benchmark is enabled ==== //
        if($this->enabled)
        {
            // ==== Starting benchmark if it does not exist in the temporary arrays ==== //
            if(isset($this->tmp_time[$id]) && isset($this->tmp_mem[$id]))
            {
                // ==== Getting proper microtime ==== //
                $microtime = microtime(true);

                // ==== Getting memory usage in MB ==== //
                $mem_usage = round((memory_get_usage() - $this->tmp_mem[$id]) / 1048576, 5);

                // ==== Storing results ==== //
                $this->res_time[$id] = number_format($microtime - $this->tmp_time[$id], 5);
                $this->res_mem[$id] = ($mem_usage < 0 ? 0 : $mem_usage);

                // ==== Checking if the resulted time exceeded the threshold ==== //
                if(($this->res_time[$id]*1000) > $this->options['threshold'])
                {
                    $this->trigger = true;
                }
            }
            else
            {
                // ==== Backtracking ===== //
                $backtrace = debug_backtrace();

                // ==== Gettin line and file of the call ==== //
                $line = $backtrace[0]['line'];
                $file = $backtrace[0]['file'];

                // ==== Adding debug data ==== //
                $this->log .= '<b>Notice:</b> You tried to stop a benchmark with an nonexistent ID (' . $id . ') at line ' . $line . ' in file ' . $file . '<br /><br />';
            }
        }
    }

    /**
     * The method stops all the benchmarks
     *
     * @param void
     * @return void
     */
    protected function stopAll()
    {
        // ==== Going through the temporary times ==== //
        foreach ($this->tmp_time as $id => $time)
        {
            if(!isset($this->res_time[$id]))
            {
                // ==== Stopping ==== //
                $this->stop($id);
            }
        }
    }

    /**
     * The method processes the results data and makes a cool string out of it
     *
     * @param void
     * @return string
     */
    public function getAll()
    {
        // ==== Checking if the benchmark is enabled ==== //
        if($this->enabled)
        {
            if($this->options['results'] !== 'db')
            {
                // ==== Default var ==== //
                $results = '';

                // ==== Checking if there are any results ==== //
                if(count($this->res_time) > 0)
                {
                    // ==== Going through the results ==== //
                    foreach($this->res_time as $key => $val)
                    {
                        $results .= '<b>' . ucfirst($key) . '</b><br />';
                        $results .= 'Execution time: ' . $val . ' seconds.<br />';
                        $results .= 'Memory usage: ' . $this->res_mem[$key] . ' MB.<br /><br />';
                    }
                }

                // ==== Adding local results to class results ==== //
                return $results;
            }
            else
            {
                /**
                 * @todo implement database result
                 */
            }
        }
        else
        {
            return '';
        }
    }

    /**
     * Class destructor.
     *
     * @param void
     * @return void
     */
    public function __destruct()
    {

        if($this->options['results'] !== 'show')
        {
            // ==== Building full url ==== //
            $url = ($_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

            // ==== Setting up page header ==== //
            $header = '<b>Results for page:</b> ' . $url . '<br /><br />';

            $header .= '<b>Access date:</b> ' . date("Y-m-d H:i:s", time()) . '<br /><br />';

            $header .= '<b>Accessed by:</b> ' . $_SERVER['REMOTE_ADDR'] . '<br /><br />';
        }
        else
        {
            $header = '';
        }


        if($this->enabled)
        {
            // ==== Stopping all the benchmarks ==== //
            $this->stopAll();

            // ==== Processing results ==== //
            $results = $header . $this->getAll();

            // ==== Handling results ==== //
            if($this->options['results'] === 'show') // Print on page
            {
                echo '<br />' . $results;
            }
            else
            {
                // Do nothing. Used for optimization.
            }

            // ==== If the trigger has been activated then send the result ==== //
            if($this->trigger == true && $this->options['debug'] == true)
            {
                // ==== Processing results ==== //
                $message = $header . $this->getAll();

                // ==== Sending debug mail ==== //
                mail($this->mopt['to'], $this->mopt['subject_tests'], $message, $this->mopt['headers']);
            }
        }

        // ==== Sending debug if on ==== //
        if($this->options['debug'] && $this->log != '')
        {
            // ==== Adding log to message ==== //
            $this->mopt['msg'] = $header . $this->log;

            // ==== Sending debug mail ==== //
            mail($this->mopt['to'], $this->mopt['subject'], $this->mopt['msg'], $this->mopt['headers']);
        }
    }

}
?>