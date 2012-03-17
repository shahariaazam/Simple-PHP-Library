<?php
/**
 * The file contains common functions
 *
 * @author Brian
 * @link http://brian.serveblog.net
 * @copyright 2011
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 */

/**
 *
 * The function returns the current url (everything in the URL bar)
 *
 * @param void
 * @return string
 */
function getFullURL()
{
    $protocol = isset($_SERVER['HTTPS'])?'https://':'http://';
    $domain = $_SERVER['SERVER_NAME'];
    $request_uri = $_SERVER['REQUEST_URI'];

    $full_url = $protocol.$domain.$request_uri;

    return $full_url;
}

/**
 *
 * The function gets the extension of a given filename
 *
 * @param string $file
 * @return string
 */
function getFileExt($file)
{
    $array = explode(".", $file);
    $ext = $array[sizeof($array)-1];

    return $ext;
}

/**
 * The function redirects the user using either the header method or the http_redirect method
 *
 * @param string $url
 * @param string $method
 * @param array $settings // Optional but only for http_redirect method
 * @return void
 */
function redirect($url, $method='header', $settings=array())
{
    $url = urldecode($url);

    switch($method)
    {
        default:
            header('Location: '.$url.'');
        break;

        case 'http_redirect':
            // ==== Default http_redirect parameters ==== //
            $params['params'] = array();
            $params['session'] = false;
            $params['status'] = 0;

            // ==== Merging arrays ==== //
            if(is_array($settings))
            {
                    $params = array_replace($params, $settings);
            }

            if(function_exists('http_redirect'))
            {
                    http_redirect($url, $params['params'], $params['session'], $params['status']);
            }
            else
            {
                trigger_error('You need PECL extension to use the http_redirect function. <br />Please install the PECL extension or switch to header redirect.', E_USER_ERROR);
            }
        break;
    }
}

/**
 * The function returns the headers of a given URL in 2 formats: numeric and associative array
 *
 * @param string $url
 * @return array
 */
function getHeaders($url)
{
    $headers = array();
    $headers['numeric'] = get_headers($url);
    $headers['assoc'] = get_headers($url, 1);

    return $headers;
}

/**
 * The function replaces or adds header data
 *
 * @param array $headers
 * @param numeric $type //This can be 0 (for numeric array) or 1 for associative array)
 * @return void
 */
function setHeaders($headers, $type=1)
{
    if($type == 0) // Numeric array
    {
        foreach($headers as $header)
        {
            header(trim($header));
        }
    }
    elseif($type == 1) // Associative array
    {
        foreach($headers as $header => $value)
        {
            header(trim($header).': '.trim($value));
        }
    }
}

/**
 * The function reverses the effect of parse_url
 *
 * @param array $comps
 * @return string
 */
function deparse_url($comps)
{
    $url = $comps['scheme'].'://'.                              // Protocol
           $comps['host'].                                      // Host
           (isset($comps['port'])?$comps['port']:'').           // Port
           (isset($comps['path'])?$comps['path']:'').           // Path
           (isset($comps['query'])?'?'.$comps['query']:'').     // Query string
           (isset($comps['fragment'])?$comps['fragment']:'');   // Anchor

    return $url;
}


/**
 * The function cuts a string to size similar to substr but it also checks for html entities to avoid
 * cutting a html entity in half
 *
 * @param string $string
 * @param integer $from
 * @param interger $length
 * @param array $more_entities
 * @return string on success or false on failure
 */
function cutstr($string, $from, $length, $more_entities=array())
{
	// ==== HTML entities array ==== //
	$entities = array(
		"&trade;",
		"&#039;"
	);

	// ==== Adding more entities to the ones already defined ==== //
	if(is_array($more_entities) && sizeof($more_entities) > 0)
	{
		$entities = array_merge($entities, $more_entities);
	}

	// ==== Getting $to limit ==== //
	$to = $from+$length;

	// ==== Going through the text and checking if there are any entities that get cut ==== //
	foreach($entities as $key => $entity)
	{
		// ==== Getting entity size ==== //
		$esize = strlen($entity);

		// ==== Getting start position of entity ==== //
		$epos_start = strpos($string, $entity);

		// ==== Getting end position of entity ==== //
		$epos_end = $epos_start + $esize;

		// ==== Checking if $from will cut the $entity ==== //
		if($from > $epos_start && $from <= $epos_end)
		{
			$from = $epos_start;
		}

		// ==== Checking if $to will cut the $entity ==== //
		if($to >= $epos_start && $to < $epos_end)
		{
			$to = $epos_start;
		}
	}

	// ==== Getting $current_length ==== //
	$new_length = $to-$from;

	// ==== Cutting the text to the proper length ==== //
	if($new_length <= $length)
	{
		// ==== Cutting string to size ==== //
		$string = substr($string, $from, $new_length);

		return $string;
	}
	elseif($new_length > $length) // If the text has shifted go through the function again
	{
		return cutstr($string, $from, $length, $more_entities);
	}
	else
	{
		return false;
	}
}

/**
 * The function validates the email address provided. It also can check the DNS to see if it is valid.
 *
 * @param string $email
 * @param boolean $checkDNS [optional]
 * @return boolean
 */
function validateMail($email, $checkDNS = false)
{
    // ==== Check variable ==== //
    $isOk = true;

    // ==== Sanitizing the email ==== //
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);

    // ==== Validating email ==== //
    $email = filter_var($email, FILTER_VALIDATE_EMAIL);

    // ==== Checking DNS record (if activated) if the email is ok so far ===== //
    if($email == false)
    {
        $isOk = false;
    }
    elseif($checkDNS)
    {
        // ==== Getting DNS part of the mail ==== //
        $pieces = explode('@', $email);
        $dns = $pieces[1];

        // ==== Checking for required function ==== //
        if(function_exists('checkdnsrr'))
        {
            // ==== Checking DNS ==== //
            if(checkdnsrr($dns) === false)
            {
                $isOk = false;
            }
        }
        elseif(function_exists('gethostbyname'))
        {
            // ==== Checking DNS ==== //
            if(gethostbyname($dns) === $dns)
            {
                $isOk = false;
            }
        }
        else
        {
            trigger_error('DNS checking requires the checkdnsrr or the gethostbyname function.', E_USER_WARNING);
        }
    }

    // ==== Returning result ==== //
    return $isOk;
}

/**
 * The function cleans up a given directory except for the files in the whitelist
 *
 * @param string $dir
 * @param array $whitelist
 * @return void
 */
function cleanup($dir, array $whitelist=array())
{
    // ==== Reading the files from the directory and deleting the ones not present in the whitelist ==== //
    if(is_dir($dir))
    {
        // ==== Opening the directory ==== //
        $dh = opendir($dir);

        // ==== Checking if the directory was opened succesfully ==== //
        if($dh != false)
        {
            while(($file = readdir($dh)) !== false)
            {
                // ==== Checking if the file exists in the whitelist and it's different from dot ==== //
                if(!in_array($file, $whitelist) && $file != '.' && $file != '..')
                {
                    // ==== Removing ==== //
                    if(is_dir($dir.$file))
                    {
                        // ==== Recursive ==== //
                        cleanup($dir.$file.'/', $whitelist);

                        // ==== Removing directory ==== //
                        @rmdir($dir.$file);
                    }
                    elseif(is_file($dir.$file))
                    {
                        // ==== Deleting file ==== //
                        unlink($dir.$file);
                    }
                    else
                    {
                        // Optimization
                    }
                }
            }
        }
    }
}

/**
 * The function is used to unpack a zip archive
 *
 * @param string $archive
 * @param string $directory
 * @return boolean
 */
function unzip($archive, $directory='./')
{
    // ==== Check variable ==== //
    $isOk = true;

    // ==== Creating the ZipArchive object ==== //
    $zip = new ZipArchive();

    // ==== Opening the archive ==== //
    if($zip->open($archive) === true)
    {
        $zip->extractTo($directory);
        $zip->close();
    }
    else
    {
        $isOk = false;
    }

    // ==== Returning result ==== //
    return $isOk;
}

/**
 * The function checks if a given URL location exists
 *
 * @param string $url
 * @return boolean
 */
function url_exists($url)
{
    // ==== Check variable ==== //
    $exists = true;

    // ==== Sanitizing the URL ==== //
    $url = filter_var($url, FILTER_SANITIZE_URL);

    // ==== Validating URL ==== //
    $url = filter_var($url, FILTER_VALIDATE_URL);

    // ==== Checking if the URL passed the previous checks ==== //
    if($url === false)
    {
        $exists = false;
    }
    else
    {
        // ==== Initializing the cURL handle ==== //
        $ch = curl_init();

        // ==== Setting the cURL options ==== //
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_NOBODY, true);

        // ==== Executing the cURL ==== //
        curl_exec($ch);

        // ==== Getting the returned code ==== //
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // ==== Closing the cURL handle ==== //
        curl_close($ch);

        // ==== If code is different than 200 then the URL does not exist ==== //
        if($code != 200)
        {
            $exists = false;
        }
    }

    // ==== Returning result ==== //
    return $exists;
}

/**
 * Converts a string containg a boolean value to a true boolean
 *
 * @param string $value
 * @return boolean
 */
function strtoboolean($value)
{
    if($value == 'true' || $value == '1')
    {
        $value = true;
    }
    elseif($value == 'false' || $value == '0')
    {
        $value = false;
    }

    return $value;
}

/**
 * The function ensures a secure download (does not reveal the filepath)
 *
 * @params $file
 * @return void
 */
function secure_download($file)
{
    // ==== Checking if the file exists === //
    if(is_file($file))
    {
        ob_end_clean();
        ob_start();
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename='.basename($file));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit();
    }
}

/**
 * The function checks the complexity of a password
 *
 * @param string $passwd
 * @param array $options
 * @return boolean
 */
function ckPasswdComplexity($passwd, array $options=array())
{
    // ==== Result variable ==== //
    $result = true;

    // ==== Checking if overwrite is in effect ==== //
    if((isset($options['overwrite']) && $options['overwrite'] == false) || !isset($options['overwrite']))
    {
        // ==== Check variable ==== //
        $failed_count = 0;

        // ==== Checking if the length check is enabled ==== //
        if(isset($options['length']) && is_numeric($options['length']))
        {
            // ==== Checking the length ==== //
            if(strlen(trim($passwd)) < $options['length'])
            {
                $failed_count++;
            }
        }

        // ==== Checking if the number or lowercase or uppercase check is active ==== //
        if(isset($options['number']) || isset($options['lcase']) || isset($options['ucase']))
        {
            // ==== Character counters ==== //
            $lChr = 0;
            $number = 0;
            $uChr = 0;

            // ==== Checking each character in the password ==== //
            for($i=0; $i < strlen($passwd); $i++)
            {
                // ==== Check variables ==== //
                $checked = false;

                // ==== Number check ==== //
                if(isset($options['number']))
                {
                    if(is_numeric(substr($passwd, $i, 1)))
                    {
                        $number++;

                        $checked = true;
                    }
                }

                // ==== Lowercase check ==== //
                if(isset($options['lcase']) && $checked == false)
                {
                    if(is_string(substr($passwd, $i, 1)) && preg_match('/[a-z]/', substr($passwd, $i, 1)))
                    {
                        $lChr++;

                        $checked = true;
                    }
                }

                // ==== Uppercase check ==== //
                if(isset($options['ucase']) && $checked == false)
                {
                    if(is_string(substr($passwd, $i, 1)) && preg_match('/[A-Z]/', substr($passwd, $i, 1)))
                    {
                        $uChr++;

                        $checked = true;
                    }
                }
            }

            // ==== Checking number count ==== //
            if(isset($options['number']) && $number == 0)
            {
                $failed_count++;
            }

            // ==== Checking lowercase count ==== //
            if(isset($options['lcase']) && $lChr == 0)
            {
                $failed_count++;
            }

            // ==== Checking uppercase count ==== //
            if(isset($options['ucase']) && $uChr == 0)
            {
                $failed_count++;
            }
        }

        // ==== Checking the failed count ==== //
        if($failed_count != 0)
        {
            $result = false;
        }
    }


    // ==== returning result ==== //
    return $result;
}

/**
 * The function properly prints an array
 *
 * @param array $array
 * @return void
 */
function print_array(array $array, $return=false)
{
    $str = '<pre>'.print_r($array, 1).'</pre>';

    if($return == true)
    {
        return $str;
    }
    else
    {
        echo $str;
    }
}
?>