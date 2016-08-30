<?php

/**
 * Flooer Framework
 *
 * LICENSE: BSD License (2 Clause)
 *
 * @category    Flooer
 * @package     Flooer_Http
 * @subpackage  Cookie
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 * @copyright   Akira Ohgaki
 * @license     http://www.freebsd.org/copyright/freebsd-license.html  BSD License (2 Clause)
 * @link        https://github.com/akiraohgaki/flooer
 */

/**
 * Usage
 *
 * $cookie = new Flooer_Http_Cookie();
 * $cookie->setExpire(time() + 3600);
 * $cookie->setPath('/');
 * $cookie->key = $value;
 */

/**
 * HTTP cookie class
 *
 * @category    Flooer
 * @package     Flooer_Http
 * @subpackage  Cookie
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 */
class Flooer_Http_Cookie
{

    /**
     * Configuration options
     *
     * @var     array
     */
    protected $_config = array(
        'importPredefinedVars' => true,
        'expire' => 0,
        'path' => null,
        'domain' => null,
        'secure' => false,
        'httponly' => false
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
        $bool = setcookie(
            $key,
            $value,
            $this->_config['expire'],
            $this->_config['path'],
            $this->_config['domain'],
            $this->_config['secure'],
            $this->_config['httponly']
        );
        if ($bool) {
            $this->_properties[$key] = $value;
        }
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
        $bool = setcookie(
            $key,
            '',
            time() - 60,
            $this->_config['path'],
            $this->_config['domain'],
            $this->_config['secure'],
            $this->_config['httponly']
        );
        if ($bool) {
            unset($this->_properties[$key]);
        }
    }

    /**
     * Import a predefined variables
     *
     * @return  void
     */
    public function importPredefinedVars()
    {
        if (!empty($_COOKIE)) {
            $this->_properties += $_COOKIE;
        }
    }

    /**
     * Encode a data
     *
     * @param   mixed $data
     * @return  string Encoded data
     */
    public function encode($data)
    {
        return base64_encode(serialize($data));
    }

    /**
     * Decode a data
     *
     * @param   string $data Encoded data
     * @return  mixed
     */
    public function decode($data)
    {
        return unserialize(base64_decode($data));
    }

    /**
     * Set a cookie expires
     *
     * @param   int $expire
     * @return  void
     */
    public function setExpire($expire)
    {
        $this->_config['expire'] = $expire;
    }

    /**
     * Get a cookie expires
     *
     * @return  int
     */
    public function getExpire()
    {
        return $this->_config['expire'];
    }

    /**
     * Set a path on the server
     *
     * @param   string $path
     * @return  void
     */
    public function setPath($path)
    {
        $this->_config['path'] = $path;
    }

    /**
     * Get a path on the server
     *
     * @return  string
     */
    public function getPath()
    {
        return $this->_config['path'];
    }

    /**
     * Set a domain
     *
     * @param   string $domain
     * @return  void
     */
    public function setDomain($domain)
    {
        $this->_config['domain'] = $domain;
    }

    /**
     * Get a domain
     *
     * @return  string
     */
    public function getDomain()
    {
        return $this->_config['domain'];
    }

    /**
     * Set a secure option
     *
     * @param   bool $secure
     * @return  void
     */
    public function setSecure($secure)
    {
        $this->_config['secure'] = $secure;
    }

    /**
     * Get a secure option
     *
     * @return  bool
     */
    public function getSecure()
    {
        return $this->_config['secure'];
    }

    /**
     * Set an HTTP access option
     *
     * @param   bool $httponly
     * @return  void
     */
    public function setHttponly($httponly)
    {
        $this->_config['httponly'] = $httponly;
    }

    /**
     * Get an HTTP access option
     *
     * @return  bool
     */
    public function getHttponly()
    {
        return $this->_config['httponly'];
    }

}
