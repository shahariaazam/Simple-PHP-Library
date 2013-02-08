<?php

/**
 * Identifies a file
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name File
 * @version 2.0
 *
 */

namespace SPL\File;

class File implements FileInterface
{

    /**
     * The fully qualified path name
     *
     * @var string
     */
    protected $fqpn;

    /**
     * The path of the file without the basename
     * For example: /usr/bin
     *
     * @var string
     */
    protected $path;

    /**
     * The name of the file (including the extension)
     * For example: server.conf
     *
     * @var string
     */
    protected $basename;

    /**
     *
     * @var string
     */
    protected $extension;

    /**
     *
     * @var float
     */
    protected $size;

    /**
     *
     * @var string
     */
    protected $type;

    /**
     * Mime type
     *
     * @var string
     */
    protected $mime;

    /**
     * An array that contains extra info about the file
     *
     * @var array
     */
    protected $extra;

    /**
     * The constructor of the class initializes the file properties using different methods
     *
     * @param string $fqpn This is the fully qualified path name (FQPN). Example: /usr/bin/server.conf
     * @param array $extra [ optional ] An array containing extra info about the object
     * @throws Exception\RuntimeException
     * @return \SPL\File\File
     */
    public function __construct($fqpn, array $extra = array())
    {
        if(!is_file($fqpn))
        {
            throw new Exception\RuntimeException('Given FQPN does not point to a file');
        }

        $pathinfo = pathinfo($fqpn);

        if(!empty($extra))
        {
            $this->extra = $extra;
        }

        $this->fqpn      = realpath($fqpn);
        $this->extension = !empty($pathinfo['extension']) ? $pathinfo['extension'] : '';
        $this->basename  = $pathinfo['basename'];
        $this->path      = $pathinfo['dirname'];
        $this->size      = filesize($fqpn);
        $this->type      = filetype($fqpn);
        $this->mime      = '';

        if(isset($this->extra['mime']))
        {
            $this->mime = $this->extra['mime'];

            // The mime does not need to be in 2 places
            unset($this->extra['mime']);
        }
    }

    /**
     *
     * @param string $name
     * @return Null|Mixed
     */
    public function __get($name)
    {
        if(property_exists($this, $name))
        {
            return $this->$name;
        }
        else if(isset($this->extra[$name]))
        {
            return $this->extra[$name];
        }
        else
        {
            return null;
        }
    }

    /**
     *
     * @param string $name
     * @param mixed $value
     * @return \SPL\File\File
     */
    public function __set($name, $value)
    {
        if(is_string($name))
        {
            if(property_exists($this, $name) && !in_array($name, array('fqpn', 'extension', 'basename', 'path', 'size')))
            {
                $this->$name = $value;
            }
            else if(is_numeric($name))
            {
                $this->extra[$name] = $value;
            }
        }

        return $this;
    }

    /**
     * Renames a file
     *
     * @param string $name
     * @param string $extension [ optional ]
     * @throws \SPL\File\Exception\RuntimeException
     * @return \SPL\File\File
     */
    public function rename($name, $extension = null)
    {
        // If the extension was not given then we assume the existing extension
        if($extension === null)
        {
            $extension = $this->extension;
        }

        if(!empty($name) && is_string($name) && is_string($extension))
        {
            $newname = $name . $extension;

            if(rename($this->path . '/' . $this->basename, $this->path . '/' . $newname) === false)
            {
                throw new Exception\RuntimeException('Failed to rename the file because of an unkown error');
            }

            $this->fqpn      = str_replace($this->basename, $newname, $this->fqpn);
            $this->extension = $extension;
            $this->basename  = $newname;
        }

        return $this;
    }

    /**
     * Ensures a secure download (does not reveal the filepath)
     *
     * @params void
     * @return void
     */
    public function download()
    {
        // ==== Checking if the file exists === //
        if(is_file($this->fqpn))
        {
            ob_end_clean();
            ob_start();
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($this->fqpn));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            @header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($this->fqpn));
            readfile($this->fqpn);
            exit();
        }
    }
}