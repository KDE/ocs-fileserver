<?php

/**
 * Flooer Framework
 *
 * LICENSE: BSD License (2 Clause)
 *
 * @category    Flooer
 * @package     Flooer_Http
 * @subpackage  Session
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 * @copyright   Akira Ohgaki
 * @license     http://www.freebsd.org/copyright/freebsd-license.html  BSD License (2 Clause)
 * @link        https://github.com/akiraohgaki/flooer
 */

/**
 * Usage
 *
 * $session = new Flooer_Http_Session();
 * $session->setName('SessionID');
 * $session->start();
 * $session->key = $value;
 */

/**
 * Session class
 *
 * @category    Flooer
 * @package     Flooer_Http
 * @subpackage  Session
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 */
class Flooer_Http_Session
{

    /**
     * Configuration options
     *
     * @var     array
     */
    protected $_config = array(
        'importPredefinedVars' => true,
        'start' => false,
        'moduleName' => null,
        'savePath' => null,
        'saveHandlerOpen' => null,
        'saveHandlerClose' => null,
        'saveHandlerRead' => null,
        'saveHandlerWrite' => null,
        'saveHandlerDestroy' => null,
        'saveHandlerGc' => null,
        'cacheParamsLimiter' => null,
        'cacheParamsExpire' => null,
        'cookieParamsLifetime' => null,
        'cookieParamsPath' => null,
        'cookieParamsDomain' => null,
        'cookieParamsSecure' => false,
        'cookieParamsHttponly' => false,
        'name' => null,
        'id' => null
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
        if ($this->_config['moduleName']) {
            $this->setModuleName($this->_config['moduleName']);
        }
        if ($this->_config['savePath']) {
            $this->setSavePath($this->_config['savePath']);
        }
        if ($this->_config['saveHandlerOpen']
            || $this->_config['saveHandlerClose']
            || $this->_config['saveHandlerRead']
            || $this->_config['saveHandlerWrite']
            || $this->_config['saveHandlerDestroy']
            || $this->_config['saveHandlerGc']
        ) {
            $this->setSaveHandler(
                $this->_config['saveHandlerOpen'],
                $this->_config['saveHandlerClose'],
                $this->_config['saveHandlerRead'],
                $this->_config['saveHandlerWrite'],
                $this->_config['saveHandlerDestroy'],
                $this->_config['saveHandlerGc']
            );
        }
        if ($this->_config['cacheParamsLimiter']) {
            $this->setCacheParams(
                $this->_config['cacheParamsLimiter'],
                $this->_config['cacheParamsExpire']
            );
        }
        if ($this->_config['cookieParamsLifetime']) {
            $this->setCookieParams(
                $this->_config['cookieParamsLifetime'],
                $this->_config['cookieParamsPath'],
                $this->_config['cookieParamsDomain'],
                $this->_config['cookieParamsSecure'],
                $this->_config['cookieParamsHttponly']
            );
        }
        if ($this->_config['name']) {
            $this->setName($this->_config['name']);
        }
        if ($this->_config['id']) {
            $this->setId($this->_config['id']);
        }
        if ($this->_config['start']) {
            $this->start($this->_config['importPredefinedVars']);
        }
    }

    /**
     * Destructor
     *
     * @return  void
     */
    public function __destruct()
    {
        if (isset($_SESSION)) {
            $this->writeClose();
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
        if (!empty($_SESSION)) {
            $this->_properties += $_SESSION;
        }
    }

    /**
     * Start a session
     *
     * @param   bool $importPredefinedVars
     * @return  bool
     */
    public function start($importPredefinedVars = true)
    {
        $bool = session_start();
        if ($bool && $importPredefinedVars) {
            $this->importPredefinedVars();
        }
        return $bool;
    }

    /**
     * Write and close a session
     *
     * @return  void
     */
    public function writeClose()
    {
        $_SESSION = $this->_properties;
        session_write_close();
    }

    /**
     * Regenerate a session ID
     *
     * @param   bool $deleteOldSession
     * @return  bool
     */
    public function regenerateId($deleteOldSession = true)
    {
        return session_regenerate_id($deleteOldSession);
    }

    /**
     * Destroy a session data
     *
     * @param   bool $removeCookie
     * @param   bool $removeVars
     * @return  bool
     */
    public function destroy($removeCookie = true, $removeVars = true)
    {
        if ($removeVars) {
            $this->_properties = array();
            $_SESSION = array();
        }
        if ($removeCookie) {
            $name = session_name();
            $params = session_get_cookie_params();
            if (isset($_COOKIE[$name])) {
                setcookie(
                    $name,
                    '',
                    time() - 60,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
        }
        return session_destroy();
    }

    /**
     * Encode a session data
     *
     * @return  string Encoded session data
     */
    public function encode()
    {
        $_SESSION = $this->_properties;
        session_write_close();
        return session_encode();
    }

    /**
     * Decode a session data
     *
     * @param   string $data Encoded session data
     * @return  bool
     */
    public function decode($data)
    {
        $bool = session_decode($data);
        if ($bool) {
            $this->_properties = $_SESSION;
        }
        return $bool;
    }

    /**
     * Set a session module name
     *
     * @param   string $module
     * @return  void
     */
    public function setModuleName($module)
    {
        session_module_name($module);
    }

    /**
     * Get a session module name
     *
     * @return  string
     */
    public function getModuleName()
    {
        return session_module_name();
    }

    /**
     * Set a session save path
     *
     * @param   string $path
     * @return  void
     */
    public function setSavePath($path)
    {
        session_save_path($path);
    }

    /**
     * Get a session save path
     *
     * @return  string
     */
    public function getSavePath()
    {
        return session_save_path();
    }

    /**
     * Set a session save handler
     *
     * @param   callback $open
     * @param   callback $close
     * @param   callback $read
     * @param   callback $write
     * @param   callback $destroy
     * @param   callback $gc
     * @return  bool
     */
    public function setSaveHandler($open = null, $close = null, $read = null, $write = null, $destroy = null, $gc = null)
    {
        return session_set_save_handler(
            $open,
            $close,
            $read,
            $write,
            $destroy,
            $gc
        );
    }

    /**
     * Set a session cache parameters
     *
     * @param   string $limiter Available values to session_cache_limiter()
     * @param   int $expire Minutes
     * @return  void
     */
    public function setCacheParams($limiter, $expire = null)
    {
        session_cache_limiter($limiter);
        if ($limiter != 'nocache' && $expire !== null) {
            session_cache_expire($expire);
        }
    }

    /**
     * Get a session cache parameters
     *
     * @return  array
     */
    public function getCacheParams()
    {
        $cacheParams = array(
            'limiter' => session_cache_limiter(),
            'expire' => session_cache_expire()
        );
        return $cacheParams;
    }

    /**
     * Set a session cookie parameters
     *
     * @param   int $lifetime
     * @param   string $path
     * @param   string $domain
     * @param   bool $secure
     * @param   bool $httponly
     * @return  void
     */
    public function setCookieParams($lifetime, $path = null, $domain = null, $secure = false, $httponly = false)
    {
        session_set_cookie_params(
            $lifetime,
            $path,
            $domain,
            $secure,
            $httponly
        );
    }

    /**
     * Get a session cookie parameters
     *
     * @return  array
     */
    public function getCookieParams()
    {
        return session_get_cookie_params();
    }

    /**
     * Set a session name
     *
     * @param   string $name
     * @return  void
     */
    public function setName($name)
    {
        session_name($name);
    }

    /**
     * Get a session name
     *
     * @return  string
     */
    public function getName()
    {
        return session_name();
    }

    /**
     * Set a session ID
     *
     * @param   string $id
     * @return  void
     */
    public function setId($id)
    {
        session_id($id);
    }

    /**
     * Get a session ID
     *
     * @return  string
     */
    public function getId()
    {
        return session_id();
    }

}
