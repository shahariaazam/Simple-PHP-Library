<?php
/**
 *
 * The Vault uses the MCRYPT_RIJNDAEL_256 encryption algorithm and the CBC block cipher mode.
 *
 * @author Brian
 * @link https://github.com/brian978
 * @copyright 2012
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @name Vault
 * @version 1.1
 *
 */

namespace SPL\Security;

class Vault
{
    /**
     * Options array
     *
     * @var array
     */
    private $options;

    /**
     * Hold the handle for the encryption module and mode
     *
     * @var encryption_descriptor
     */
    private $td;

    /**
     * Holds the initialization vector which will be used for the encryption
     *
     * @var string
     */
    private $iv;

    /**
     * Holds the key which will be used for the encryption
     *
     * @var string
     */
    private $key;

    /**
     * Array of IV's for each encryption layer
     *
     * @var array
     */
    private $ivs = array();

    /**
     * Array of keys for each encryption layer
     *
     * @var array
     */
    private $keys = array();

    /**
     * Config mode.
     * Determins if the class should be in config mode or not. This means encryption/decryption won't work and the construtor will return random Keys and IVs
     *
     * @var boolean
     */
    private $config_mode = false;

    /**
     * Config data. Contains the random key and IV
     *
     * @var array
     */
    public $config_data = array();

    /**
     * Class constructor.
     *
     * @access public
     * @param array $options
     * @return void
     */
    public function __construct(array $options = array())
    {
        // ==== Rewriting default values for variables in case they are set ==== //
        $this->options['debug']         = false;
        $this->options['algo']          = MCRYPT_RIJNDAEL_256;
        $this->options['mode']          = MCRYPT_MODE_CBC;
        $this->options['iv']            = '';
        $this->options['key']           = '';
        $this->options['layers']        = 1; // Encryption layers
        $this->options['shift']         = 4; // It's a shift multiplier for generating multilayer keys. Suggested value range: 2-8 and even numbers

        // ==== Overwriting default options ==== //
        if(count($options) > 0)
        {
            $this->options = array_merge($this->options, $options);
        }

        // ==== Checking if the IV or the key are not empty ==== //
        if(!empty($this->options['iv']) && !empty($this->options['key']))
        {
            // ==== Initializing the IV from the options ==== //
            $this->iv = $this->options['iv'];

            // ==== Initializing the key from the options ==== //
            $this->key = $this->options['key'];
        }
        else
        {
            // ==== Triggering the config mode ==== //
            $this->config_mode = true;

            // ==== Generating the config data ==== //
            $this->generate();
        }
    }

    /**
     * The method is used to generate the initial Key and IV
     *
     *
     * @param void
     * @return array
     */
    private function generate()
    {
        // ==== Opening encryption module ==== //
        $td = mcrypt_module_open($this->options['algo'], '', $this->options['mode'], '');

        /////////////////////////////////////////////////////////////////////////////////////
        // IV
        ///////////////////////////////////////////////////////////////////////////////////
        // ==== Generating the IV ==== //
        $iv = mcrypt_createiv(mcrypt_enc_get_iv_size($td), MCRYPT_DEV_RANDOM);


        /////////////////////////////////////////////////////////////////////////////////////
        // KEY
        ///////////////////////////////////////////////////////////////////////////////////
        // ==== Getting key length ==== //
        $key_len = mcrypt_enc_get_key_size($td);

        // ==== Characters ==== //
        $chars = str_split('01$%234{}-56789qwe&*rtyu=_+~!@#iop[]asd:"|fg|(hjkl;\zxc|^vbnm,./?><:")');
        $chars_len = count($chars);

        // ==== Key ==== //
        $key = '';

        // ==== Generating the key ==== //
        for($i = 0; $i < $key_len; $i++)
        {
            $key .= $chars[mt_rand(0, $chars_len-1)];
        }

        // ==== Closing the encryption module ==== //
        mcrypt_module_close($td);

        // ==== Result ==== //
        $this->config_data = array('iv' => $iv, 'key' => $key);
    }

    /**
     * The method generates new Keys for each encryption layer
     *
     * @param void
     * @return void
     */
    protected function generateKeys()
    {
        // ==== Ascii black list ==== //
        $blacklist = array();

        // ==== Generating the automatic part of the blacklist ==== //
        for($i = 0; $i <= 32; $i++)
        {
            $blacklist[] = $i;
        }

        // ==== Blacklisting some more ascii ==== //
        $blacklist[] = 127;
        $blacklist[] = 129;
        $blacklist[] = 141;
        $blacklist[] = 143;
        $blacklist[] = 144;
        $blacklist[] = 157;
        $blacklist[] = 160;

        // ==== Maximum ascii value ==== //
        $max_ascii = 255;

        // ==== Initializing the first key ==== //
        $this->keys[1] = $this->options['key'];

        // ==== Generating the keys for the other layers ==== //
        for($i = 2; $i <= $this->options['layers']; $i++)
        {
            // ==== Default key value ==== //
            $key = '';

            // ===== Splitting the previous key into an array ==== //
            $prevkey = str_split($this->keys[$i-1]);

            // ==== Calculating the shift value ==== //
            $shift = $this->options['shift'] * $i;

            // ==== Adjusting the shift value ==== //
            if($shift >= $max_ascii)
            {
                $shift = ceil($max_ascii/$i);
            }

            // ==== Going through the characters of the previous key ==== //
            foreach($prevkey as $k => $char)
            {
                // ==== Converting the character into ascii ==== //
                $ascii = ord($char);

                // ==== Calculating the new ascii value ==== //
                $ascii += $shift;

                // ==== Adjusting the new ascii value ==== //
                if($ascii > $max_ascii)
                {
                    $ascii -= $max_ascii;
                }

                // ==== Checking if the ascii code is in the blacklist ==== //
                if(in_array($ascii, $blacklist))
                {
                    // ==== Check variable ==== //
                    $accepted = false;

                    // ==== Searching for the next available ascii value ==== //
                    while(!$accepted)
                    {
                        // ==== Adjusting ==== //
                        $ascii++;

                        // ==== Checking ==== //
                        if(!in_array($ascii, $blacklist))
                        {
                            $accepted= true;
                        }
                    }
                }

                // ==== Converting the ascii into a character ==== //
                $char = chr($ascii);

                // ==== Addding the character to the key ==== //
                $key .= $char;
            }

            // ==== Adding the key ==== //
            $this->keys[$i] = $key;
        }
    }

    /**
     * Initializing the cryptographic module
     *
     * @param void
     * @return void
     */
    private function initialize()
    {
        // ==== Opening encryption module ==== //
        $this->td = mcrypt_module_open($this->options['algo'], '', $this->options['mode'], '');

        // ==== Getting initialization vector size ==== //
        $iv_size = mcrypt_enc_get_iv_size($this->td);

        // ==== Creating initialization vector from random string ==== //
        $this->iv = substr($this->iv, 0, $iv_size);

        // ==== Getting key size ==== //
        $key_size = mcrypt_enc_get_key_size($this->td);

        // ==== Generating random key ==== //
        $this->key = substr($this->key, 0, $key_size);

        // ==== Initializing all buffers needed for encryption/decryption ==== //
        mcrypt_generic_init($this->td, $this->key, $this->iv);
    }

    /**
     * Closes the cryptographic module
     *
     * @param void
     * @return void
     */
    private function terminate()
    {
        // ==== Closing encryption handle ==== //
        @mcrypt_generic_deinit($this->td);

        // ==== Closing encryption module ==== //
        @mcrypt_module_close($this->td);
    }

    /**
     * The method encrypts the data passed to it and return a base64 database friendly string
     *
     * @access public
     * @param string $data
     * @return encrypted data or false if data not string
     */
    private function crypt($data)
    {
        if(is_string($data))
        {
            // ==== Initializing ==== //
            $this->initialize();

            // ==== Encrypting ==== //
            $vault_layer     = mcrypt_generic($this->td, $data);
            $base64_layer    = base64_encode($vault_layer);
            $encrypted       = &$base64_layer;

            // ==== Terminating ==== //
            $this->terminate();

            // ==== Returning result ==== //
            return trim($encrypted);
        }

        return false;
    }

    /**
     * The method decrypts the data passed to it.
     *
     * @access public
     * @param string $data
     * @return decrypted data or false if data not string
     */
    private function dcrypt($data)
    {
        if(is_string($data))
        {
            // ==== Initializing ==== //
            $this->initialize();

            // ==== Decrypting ==== //
            $base64_layer   = base64_decode($data);
            $vault_layer    = mdecrypt_generic($this->td, $base64_layer);
            $decrypted      = &$vault_layer;

            // ==== Terminating ==== //
            $this->terminate();

            // ==== Returning result ==== //
            return trim($decrypted);
        }

        return false;
    }

    /**
     * Wrapper method for enc to support multilayer encryption
     *
     * @access public
     * @param string $data
     * @return encrypted data or false if data not string
     */
    public function encrypt($data)
    {
        // ==== Checking if config mode is active or not ==== //
        if($this->config_mode === false)
        {
            // ==== Determining how many layers the encryption will go through ==== //
            if($this->options['layers'] == 1)
            {
                // ==== Encrypting ==== //
                $data = $this->crypt($data);
            }
            else
            {
                // ==== Generating the keys ==== //
                $this->generateKeys();

                // ==== Debug data ==== //
                if($this->options['debug'])
                {
                    // ==== Encrypted array ==== //
                    $encrypted = array();

                    // ==== Initial value ==== //
                    $encrypted[0] = $data;
                }

                // ==== Encrypting the data with each key ==== //
                for($i = 1; $i <= $this->options['layers']; $i++)
                {
                    // ==== Changing the key ==== //
                    $this->key = $this->keys[$i];

                    // ==== Encrypting ==== //
                    $data = $this->crypt($data);

                    // ==== Debug data ==== //
                    if($this->options['debug'])
                    {
                        // ==== Debug array ==== //
                        $encrypted[$i] = $data;
                    }
                }

                // ==== Debug data ==== //
                if($this->options['debug'])
                {
                    echo 'Encrypted: <pre>'.print_r($encrypted, 1).'</pre>';
                    echo 'Keys: <pre>'.print_r($this->keys, 1).'</pre><br />';
                }
            }
        }
        else
        {
            $data = false;
        }

        // ==== Result ==== //
        return $data;
    }

    /**
     * Wrapper method for dec to support multilayer encryption
     *
     * @access public
     * @param string $data
     * @return encrypted data or false if data not string
     */
    public function decrypt($data)
    {
        // ==== Checking if config mode is active or not ==== //
        if($this->config_mode === false)
        {
            // ==== Determining how many layers the encryption will go through ==== //
            if($this->options['layers'] == 1)
            {
                // ==== Decrypting ==== //
                $data = $this->dcrypt($data);
            }
            else
            {
                // ==== Generating the keys ==== //
                $this->generateKeys();

                // ==== Debug data ==== //
                if($this->options['debug'])
                {
                    // ==== Decrypted array ==== //
                    $decrypted = array();

                    // ==== Initial value ==== //
                    $decrypted[$this->options['layers']] = $data;
                }

                // ==== Encrypting the data with each key ==== //
                for($i = $this->options['layers']; $i >= 1; $i--)
                {
                    // ==== Changing the key ==== //
                    $this->key = $this->keys[$i];

                    // ==== Decrypting ==== //
                    $data = $this->dcrypt($data);

                    // ==== Debug data ==== //
                    if($this->options['debug'])
                    {
                        // ==== Debug array ==== //
                        $decrypted[$i-1] = $data;
                    }
                }

                // ==== Debug data ==== //
                if($this->options['debug'])
                {
                    echo 'Decrypted: <pre>'.print_r($decrypted, 1).'</pre>';
                    echo 'Keys: <pre>'.print_r(array_reverse($this->keys, true), 1).'</pre><br />';
                }
            }
        }
        else
        {
            $data = false;
        }

        // ==== Result ==== //
        return $data;
    }

    /**
     * Class destructor
     *
     * @param void
     * @return void
     */
    public function __destruct() {}
}