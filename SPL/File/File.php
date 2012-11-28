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
 * @version 1.0
 *
 */

namespace SPL\File;

class File extends FileInterface
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
     * The constructor of the class initializes the file properties using different methods
     *
     * @param string $fqpn This is the fully qualified path name (FQPN). Example: /usr/bin/server.conf
     * @return void
     */
    public function __construct($fqpn)
    {
        $pathinfo = pathinfo($fqpn);

        $this->fqpn      = $fqpn;
        $this->extension = !empty($pathinfo['extension']) ? $pathinfo['extension'] : '';
        $this->basename  = $pathinfo['basename'];
        $this->path      = $pathinfo['dirname'];
        $this->size      = filesize($fqpn);
        $this->type      = filetype($fqpn);
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
        else
        {
            return null;
        }
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
        if(!emtpy($name) && !is_numeric($name))
        {
            if($extension === null || !is_string($extension) || strval($extension) == '')
            {
                $extension = $this->extension;
            }

            $newname = $name . $extension;

            // This is used for an extra check so that we make sure the rename is successfull
            $count = 0;

            // We need to replace the old name in the FQPN so they are not out of sync
            $this->fqpn = str_replace($this->basename, $newname, $this->fqpn, $count);

            // If the replace failed we need to exit to prevent further modifications
            if($count === 0)
            {
                throw new Exception\RuntimeException('The file could not be renamed because the basename is not present in the FPQN');
            }

            if(rename($this->basename, $newname) === false)
            {
                throw new Exception\RuntimeException('Failed to rename the file because of an unkown error');
            }

            if($this->extension !== $extension)
            {
                $this->extension = $extension;
            }

            $this->basename = $newname;
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