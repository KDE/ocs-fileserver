<?php

/**
 * Flooer Framework
 *
 * LICENSE: BSD License (2 Clause)
 *
 * @category    Flooer
 * @package     Flooer_Http
 * @subpackage  Server
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 * @copyright   Akira Ohgaki
 * @license     https://opensource.org/licenses/BSD-2-Clause  BSD License (2 Clause)
 * @link        https://github.com/akiraohgaki/flooer
 */

/**
 * Usage
 *
 * $server = new Flooer_Http_Server();
 * echo $server->baseUri;
 */

/**
 * Server and execution environment information class
 *
 * @category    Flooer
 * @package     Flooer_Http
 * @subpackage  Server
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 */
class Flooer_Http_Server
{

    /**
     * Configuration options
     *
     * @var     array
     */
    protected $_config = array(
        'importPredefinedVars' => true,
        'generateAdditionalVars' => true
    );

    /**
     * Overloaded properties
     *
     * @var     array
     */
    protected $_properties = array();

    /**
     * Constructor
     *
     * @param   array $config
     * @return  void
     */
    public function __construct(array $config = null)
    {
        if ($config) {
            $this->_config = $config + $this->_config;
        }
        if ($this->_config['importPredefinedVars']) {
            $this->importPredefinedVars();
        }
        if ($this->_config['generateAdditionalVars']) {
            $this->generateAdditionalVars();
        }
    }

    /**
     * Magic method to set a property
     *
     * @param   string $key
     * @param   mixed $value
     * @return  void
     */
    public function __set($key, $value)
    {
        $this->_properties[$key] = $value;
    }

    /**
     * Magic method to get a property
     *
     * @param   string $key
     * @return  mixed
     */
    public function __get($key)
    {
        if (isset($this->_properties[$key])) {
            return $this->_properties[$key];
        }
        return null;
    }

    /**
     * Magic method to check a property
     *
     * @param   string $key
     * @return  bool
     */
    public function __isset($key)
    {
        return isset($this->_properties[$key]);
    }

    /**
     * Magic method to unset a property
     *
     * @param   string $key
     * @return  void
     */
    public function __unset($key)
    {
        unset($this->_properties[$key]);
    }

    /**
     * Import a predefined variables
     *
     * @return  void
     */
    public function importPredefinedVars()
    {
        if (!empty($_SERVER)) {
            $this->_properties += $_SERVER;
        }
    }

    /**
     * Generate an additional information variables
     *
     * @return  void
     */
    public function generateAdditionalVars()
    {
        $scheme = 'http';
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $scheme = 'https';
        }
        $port = '';
        if ($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) {
            $port = ':' . $_SERVER['SERVER_PORT'];
        }
        $location = $scheme . '://' . $_SERVER['SERVER_NAME'] . $port;
        $additionalVars = array(
            'serverUri' => $location . '/',
            'baseUri' => dirname($location . $_SERVER['SCRIPT_NAME']) . '/',
            'scriptUri' => $location . $_SERVER['SCRIPT_NAME']
        );
        $this->_properties += $additionalVars;
    }

}
