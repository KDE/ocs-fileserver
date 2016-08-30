<?php

/**
 * Flooer Framework
 *
 * LICENSE: BSD License (2 Clause)
 *
 * @category    Flooer
 * @package     Flooer_Application
 * @subpackage  Bootstrap
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 * @copyright   Akira Ohgaki
 * @license     http://www.freebsd.org/copyright/freebsd-license.html  BSD License (2 Clause)
 * @link        https://github.com/akiraohgaki/flooer
 */

/**
 * Usage
 *
 * $bootstrap = new Flooer_Application_Bootstrap($application);
 * $bootstrap->bootstrap();
 */

/**
 * Bootstrapper class of application
 *
 * @category    Flooer
 * @package     Flooer_Application
 * @subpackage  Bootstrap
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 */
class Flooer_Application_Bootstrap
{

    /**
     * Application environment object
     *
     * @var     Flooer_Application
     */
    protected $_application = null;

    /**
     * Configuration options
     *
     * @var     array
     */
    protected $_config = array(
        'methodPrefix' => 'init'
    );

    /**
     * Bootstrap status
     *
     * @var     bool
     */
    protected $_status = false;

    /**
     * Constructor
     *
     * @param   Flooer_Application &$application
     * @param   array $config
     * @return  void
     */
    public function __construct(Flooer_Application &$application, array $config = null)
    {
        $this->_application =& $application;
        if ($config) {
            $this->_config = $config + $this->_config;
        }
    }

    /**
     * Bootstrap application
     *
     * @return  void
     */
    public function bootstrap()
    {
        if ($this->_status) {
            trigger_error(
                'Bootstrap is already running',
                E_USER_NOTICE
            );
        }
        $this->_status = true;
        $methods = get_class_methods($this);
        foreach ($methods as $method) {
            if (strpos($method, $this->_config['methodPrefix']) === 0
                && $method != $this->_config['methodPrefix']
            ) {
                $this->$method();
            }
        }
    }

    /**
     * Predefined method to initialize a dispatcher object
     *
     * @return  void
     */
    public function initDispatch()
    {
        require_once 'Flooer/Application/Dispatch.php';
        $this->_application->setResource(
            'dispatch',
            new Flooer_Application_Dispatch(
                $this->_application,
                array(
                    'baseDir' => $this->_application->getConfig('baseDir')
                        . '/controllers'
                )
            )
        );
    }

    /**
     * Predefined method to initialize an HTTP request object
     *
     * @return  void
     */
    public function initRequest()
    {
        require_once 'Flooer/Http/Request.php';
        $this->_application->setResource(
            'request',
            new Flooer_Http_Request(array(
                'uriMapRules' => array(
                    array(
                        '^([^/]+)/?([^/]*)/?(.*)$',
                        'controller=$1&action=$2&params=$3',
                        'params'
                    )
                )
            ))
        );
    }

    /**
     * Predefined method to initialize an HTTP response object
     *
     * @return  void
     */
    public function initResponse()
    {
        require_once 'Flooer/Http/Response.php';
        $this->_application->setResource(
            'response',
            new Flooer_Http_Response(array(
                'headers' => array(
                    'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT',
                    'X-Powered-By' => ''
                )
            ))
        );
    }

    /**
     * Predefined method to initialize an HTTP cookie object
     *
     * @return  void
     */
    public function initCookie()
    {
        require_once 'Flooer/Http/Cookie.php';
        $this->_application->setResource(
            'cookie',
            new Flooer_Http_Cookie(array(
                'expire' => time() + 60 * 60 * 24 * 7,
                'path' => '/'
            ))
        );
    }

    /**
     * Predefined method to initialize a session object
     *
     * @return  void
     */
    public function initSession()
    {
        require_once 'Flooer/Http/Session.php';
        $this->_application->setResource(
            'session',
            new Flooer_Http_Session(array(
                'cacheParamsLimiter' => 'nocache',
                'cacheParamsExpire' => 0,
                'name' => 'Flooer_Http_Session'
            ))
        );
    }

    /**
     * Predefined method to initialize a server and execution environment information object
     *
     * @return  void
     */
    public function initServer()
    {
        require_once 'Flooer/Http/Server.php';
        $this->_application->setResource(
            'server',
            new Flooer_Http_Server()
        );
    }

    /**
     * Predefined method to initialize a view object
     *
     * @return  void
     */
    public function initView()
    {
        require_once 'Flooer/View.php';
        $this->_application->setResource(
            'view',
            new Flooer_View(array(
                'baseDir' => $this->_application->getConfig('baseDir')
                    . '/views'
            ))
        );
    }

    /**
     * Predefined method to initialize a gettext and locale information setting object
     *
     * @return  void
     */
    public function initGettext()
    {
        require_once 'Flooer/Gettext.php';
        $this->_application->setResource(
            'gettext',
            new Flooer_Gettext(array(
                'baseDir' => $this->_application->getConfig('baseDir')
                    . '/locales',
                'encoding' => $this->_application->getConfig('encoding')
            ))
        );
    }

    /**
     * Predefined method to initialize an error logger object
     *
     * @return  void
     */
    public function initLog()
    {
        require_once 'Flooer/Log.php';
        $this->_application->setResource(
            'log',
            new Flooer_Log(array(
                'file' => $this->_application->getConfig('baseDir')
                    . '/logs/error.log',
            ))
        );
    }

    /**
     * Get an application environment object
     *
     * @return  Flooer_Application
     */
    public function getApplication()
    {
        return $this->_application;
    }

    /**
     * Set a method prefix
     *
     * @param   string $prefix
     * @return  void
     */
    public function setMethodPrefix($prefix)
    {
        $this->_config['methodPrefix'] = $prefix;
    }

    /**
     * Get a method prefix
     *
     * @return  string
     */
    public function getMethodPrefix()
    {
        return $this->_config['methodPrefix'];
    }

}
