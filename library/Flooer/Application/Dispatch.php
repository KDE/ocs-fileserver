<?php
/** @noinspection PhpUnused */
/** @noinspection PhpIncludeInspection */

/**
 * Flooer Framework
 *
 * LICENSE: BSD License (2 Clause)
 *
 * @category    Flooer
 * @package     Flooer_Application
 * @subpackage  Dispatch
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 * @copyright   Akira Ohgaki
 * @license     https://opensource.org/licenses/BSD-2-Clause  BSD License (2 Clause)
 * @link        https://github.com/akiraohgaki/flooer
 */

require_once 'Flooer/Filter.php';
require_once 'Flooer/Controller.php';

/**
 * Usage
 *
 * $dispatch = new Flooer_Application_Dispatch($application);
 * $dispatch->setBaseDir('../application/controllers');
 * $dispatch->dispatch();
 */

/**
 * Dispatcher class of application
 *
 * @category    Flooer
 * @package     Flooer_Application
 * @subpackage  Dispatch
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 */
class Flooer_Application_Dispatch
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
        'limitMethod'          => true,
        'filterInput'          => true,
        'filterOutput'         => true,
        'sendResponse'         => true,
        'renderView'           => true,
        'renderErrorPage'      => true,
        'writeLog'             => true,
        'allowedMethod'        => 'GET, POST, PUT, DELETE',
        'baseDir'              => null,
        'defaultController'    => 'Index',
        'defaultAction'        => 'Index',
        'errorController'      => 'Error',
        'errorAction'          => 'Error',
        'errorMethod'          => 'catch',
        'layoutView'           => 'layout',
        'controllerFileSuffix' => '.php',
        'viewFileSuffix'       => '.html',
        'controllerConfig'     => array(),
    );

    /**
     * Dispatch status
     *
     * @var     bool
     */
    protected $_status = false;

    /**
     * Constructor
     *
     * @param Flooer_Application &$application
     * @param array               $config
     *
     * @return  void
     */
    public function __construct(Flooer_Application &$application, array $config = null)
    {
        ob_start();
        $this->_application =& $application;
        if ($config) {
            $this->_config = $config + $this->_config;
        }
    }

    /**
     * Destructor
     *
     * @return  void
     */
    public function __destruct()
    {
        if (ob_get_length()) {
            ob_end_flush();
        }
    }

    /**
     * Dispatch to a page script or an action controller
     *
     * Flooer Framework have an additional support to PUT, DELETE
     * and more methods but those HTTP methods are not supported
     * on a lot of web browsers, so Flooer Framework accept the
     * request method emulation on POST method
     * with 'X-HTTP-Method-Override' request header
     * or 'method' request parameter.
     *
     * @param string $filename
     *
     * @return  void
     * @throws Flooer_Exception
     */
    public function dispatch($filename = null)
    {
        if ($this->_status) {
            trigger_error(
                'Dispatch is already running', E_USER_NOTICE
            );
        }
        $this->_status = true;
        // Request method check
        if ($this->_config['limitMethod']) {
            $this->limitMethod();
        }
        // Input filtering
        if ($this->_config['filterInput']) {
            $this->filterInput();
        }
        // Dispatch to a page script
        if ($filename) {
            // Render the page script
            try {
                $this->renderPage($filename);
            } // Error handling
            catch (Flooer_Exception $exception) {
                $this->_application->setResource('exception', $exception);
                if ($this->_config['writeLog'] && $this->_application->getResource('log') && $this->_application->getResource('log') instanceof Flooer_Log) {
                    $this->writeLog();
                }
                if ($this->_config['renderErrorPage']) {
                    $this->renderErrorPage();
                }
            }
        } // Dispatch to an action controller
        else {
            // Detect a meta-parameters
            if ($this->_application->getResource('request')->controller) {
                $controller = $this->_application->getResource('request')->controller;
            } else {
                $controller = $this->_config['defaultController'];
            }
            if ($this->_application->getResource('request')->action) {
                $action = $this->_application->getResource('request')->action;
            } else {
                $action = $this->_config['defaultAction'];
            }
            if ($this->_application->getResource('request')->getMethod() == 'POST') {
                if ($this->_application->getResource('request')->method) {
                    $method = $this->_application->getResource('request')->method;
                } else {
                    $method = $this->_application->getResource('request')->getMethod(true);
                }
            } else {
                $method = $this->_application->getResource('request')->getMethod();
            }
            // Execute the action and render the view
            try {
                $this->executeAction($controller, $action, $method);
                if ($this->_config['renderView'] && $this->_application->getResource('view') && $this->_application->getResource('view') instanceof Flooer_View) {
                    $this->renderView($controller, $action);
                }
            } // Error handling
            catch (Flooer_Exception $exception) {
                $this->_application->setResource('exception', $exception);
                if ($this->_config['writeLog'] && $this->_application->getResource('log') && $this->_application->getResource('log') instanceof Flooer_Log) {
                    $this->writeLog();
                }
                // Execute the error action and render the view
                try {
                    $this->executeAction(
                        $this->_config['errorController'], $this->_config['errorAction'], $this->_config['errorMethod']
                    );
                    if ($this->_config['renderView'] && $this->_application->getResource('view') && $this->_application->getResource('view') instanceof Flooer_View) {
                        $this->renderView(
                            $this->_config['errorController'], $this->_config['errorAction']
                        );
                    }
                } // If error happened again, render a default error page
                catch (Flooer_Exception $exception) {
                    $this->_application->setResource('exception', $exception);
                    if ($this->_config['writeLog'] && $this->_application->getResource('log') && $this->_application->getResource('log') instanceof Flooer_Log) {
                        $this->writeLog();
                    }
                    if ($this->_config['renderErrorPage']) {
                        $this->renderErrorPage();
                    }
                }
            }
        }
        // Output filtering
        if ($this->_config['filterOutput']) {
            $this->filterOutput();
        }
        // Send a response
        if ($this->_config['sendResponse']) {
            $this->sendResponse();
        }
    }

    /**
     * Request limits to an allowed request method
     *
     * @return  void
     */
    public function limitMethod()
    {
        if ($this->_application->getResource('request')->getMethod() == 'HEAD') {
            $this->_application->getResource('response')->setBody('');
            $this->_application->getResource('response')->send();
            exit;
        } else {
            if ($this->_application->getResource('request')->getMethod() == 'OPTIONS') {
                $this->_application->getResource('response')->setHeader(
                    'Allow', $this->_config['allowedMethod']
                );
                $this->_application->getResource('response')->setBody(
                    'Allow: ' . $this->_config['allowedMethod']
                );
                $this->_application->getResource('response')->send();
                exit;
            } else {
                if (!in_array(
                    $this->_application->getResource('request')
                                       ->getMethod(true), explode(',', str_replace(' ', '', $this->_config['allowedMethod']))
                )) {
                    $this->_application->getResource('response')->setStatus(405);
                    $this->_application->getResource('response')->setBody(
                        'Method Not Allowed'
                    );
                    $this->_application->getResource('response')->send();
                    exit;
                }
            }
        }
    }

    /**
     * Input filtering
     *
     * @return  void
     */
    public function filterInput()
    {
        $filter = new Flooer_Filter(
            array(
                'convertEncoding' => $this->_application->getConfig('mbstringSupport'),
                'convertNewline'  => true,
                'stripNull'       => true,
                'stripSlashes'    => $this->_application->getConfig('magicQuotesSupport'),
                'trimWhitespace'  => true,
                'encoding'        => $this->_application->getConfig('encoding'),
                'newline'         => $this->_application->getConfig('newline'),
            )
        );
        $request = $this->_application->getResource('request');
        $filter->filter($request);
        $this->_application->setResource('request', $request);
        if ($this->_application->getResource('cookie') && $this->_application->getResource('cookie') instanceof Flooer_Http_Cookie) {
            $cookie = $this->_application->getResource('cookie');
            $filter->filter($cookie);
            $this->_application->setResource('cookie', $cookie);
        }
        if ($this->_application->getResource('server') && $this->_application->getResource('server') instanceof Flooer_Http_Server) {
            $server = $this->_application->getResource('server');
            $filter->filter($server);
            $this->_application->setResource('server', $server);
        }
    }

    /**
     * Render a page
     *
     * @param string $filename
     *
     * @return  void
     * @throws  Flooer_Exception
     */
    public function renderPage($filename)
    {
        if (is_file($filename)) {
            try {
                ob_start();
                include $filename;
                $this->_application->getResource('response')->setBody(ob_get_contents());
                ob_end_clean();
                if (!$this->_application->getResource('response')->getHeader('Content-Type')) {
                    $type = $this->_application->getResource('response')->detectContentType($filename);
                    if ($type) {
                        $this->_application->getResource('response')->setHeader(
                            'Content-Type', $type
                        );
                    }
                }
            } catch (Flooer_Exception $exception) {
                ob_clean();
                $this->_application->getResource('response')->setStatus(500);
                throw $exception;
            } catch (Exception $exception) {
                ob_clean();
                $this->_application->getResource('response')->setStatus(500);
                throw new Flooer_Exception(
                    $exception->getMessage(), $exception->getCode(), $exception->getFile(), $exception->getLine()
                );
            }

            return;
        }
        $this->_application->getResource('response')->setStatus(404);
        $message = "Page script file ($filename) not found";
        if (in_array($this->_application->getConfig('environment'), array('production', 'staging'))) {
            $message = 'Not Found';
        }
        throw new Flooer_Exception(
            $message, LOG_NOTICE
        );
    }

    /**
     * Write a log and/or send an email notification
     *
     * @return  void
     */
    public function writeLog()
    {
        $message = $this->_application->getResource('exception')->getMessage();
        $code = $this->_application->getResource('exception')->getCode();
        $file = $this->_application->getResource('exception')->getFile();
        $line = $this->_application->getResource('exception')->getLine();
        $uri = $this->_application->getResource('request')->getUri();
        $this->_application->getResource('log')->log(
            "$message; $file($line); $uri", $code
        );
    }

    /**
     * Render a default error page
     *
     * @return  void
     * @throws Flooer_Exception
     * @throws Flooer_Exception
     */
    public function renderErrorPage()
    {
        $this->renderPage(
            dirname(__FILE__) . '/pages/error.phtml'
        );
    }

    /**
     * Execute an action
     *
     * @param string $controller
     * @param string $action
     * @param string $method
     *
     * @return  void
     * @throws  Flooer_Exception
     */
    public function executeAction($controller, $action, $method)
    {
        $previousException = null;
        if ($this->_application->getResource('exception') && $this->_application->getResource('exception') instanceof Flooer_Exception) {
            $previousException = $this->_application->getResource('exception');
        }
        $controller = ucfirst(strtolower($controller));
        $controller = str_replace('controller', 'Controller', $controller);
        $action = ucfirst(strtolower($action));
        $method = strtolower($method);
        if (is_file(
            $this->_config['baseDir'] . '/' . $controller . $this->_config['controllerFileSuffix']
        )) {
            include_once $this->_config['baseDir'] . '/' . $controller . $this->_config['controllerFileSuffix'];
            if (class_exists($controller, false)) {
                $controllerInstance = new $controller($this->_config['controllerConfig']);
                if ($controllerInstance instanceof Flooer_Controller) {
                    if (method_exists($controllerInstance, $method . $action)) {
                        foreach ($this->_application->getResources() as $key => $value) {
                            // A resources should be an object.
                            $controllerInstance->$key = $value;
                        }
                        try {
                            $controllerInstance->execute($method . $action);
                        } catch (Flooer_Exception $exception) {
                            ob_clean();
                            if (!$this->_application->getResource('response')->getStatus()) {
                                $this->_application->getResource('response')->setStatus(500);
                            }
                            throw $exception;
                        } catch (Exception $exception) {
                            ob_clean();
                            if (!$this->_application->getResource('response')->getStatus()) {
                                $this->_application->getResource('response')->setStatus(500);
                            }
                            throw new Flooer_Exception(
                                $exception->getMessage(), $exception->getCode(), $exception->getFile(), $exception->getLine()
                            );
                        }

                        return;
                    }
                    $this->_application->getResource('response')->setStatus(404);
                    $message = "Unknown action ($action)";
                    if (in_array($this->_application->getConfig('environment'), array('production', 'staging'))) {
                        $message = 'Not Found';
                    }
                    throw new Flooer_Exception(
                        $message, LOG_NOTICE, null, null, $previousException
                    );
                }
                $this->_application->getResource('response')->setStatus(500);
                $message = "Invalid controller ($controller)";
                if (in_array($this->_application->getConfig('environment'), array('production', 'staging'))) {
                    $message = 'Internal Server Error';
                }
                throw new Flooer_Exception(
                    $message, LOG_ERR, null, null, $previousException
                );
            }
            $this->_application->getResource('response')->setStatus(500);
            $message = "Invalid controller ($controller)";
            if (in_array($this->_application->getConfig('environment'), array('production', 'staging'))) {
                $message = 'Internal Server Error';
            }
            throw new Flooer_Exception(
                $message, LOG_ERR, null, null, $previousException
            );
        }
        $this->_application->getResource('response')->setStatus(404);
        $message = "Unknown controller ($controller)";
        if (in_array($this->_application->getConfig('environment'), array('production', 'staging'))) {
            $message = 'Not Found';
        }
        throw new Flooer_Exception(
            $message, LOG_NOTICE, null, null, $previousException
        );
    }

    /**
     * Render a view
     *
     * @param string $controller
     * @param string $action
     *
     * @return  void
     * @throws  Flooer_Exception
     */
    public function renderView($controller, $action)
    {
        $controller = strtolower($controller);
        $action = strtolower($action);
        $file = $this->_application->getResource('view')->getFile();
        if (!$file) {
            if (is_file(
                $this->_application->getResource('view')
                                   ->getBaseDir() . '/' . $this->_config['layoutView'] . $this->_config['viewFileSuffix']
            )) {
                $file = $this->_config['layoutView'] . $this->_config['viewFileSuffix'];
            } else {
                if (is_file(
                    $this->_application->getResource('view')
                                       ->getBaseDir() . '/' . $controller . $this->_config['viewFileSuffix']
                )) {
                    $file = $controller . $this->_config['viewFileSuffix'];
                } else {
                    $file = $controller . '/' . $action . $this->_config['viewFileSuffix'];
                }
            }
        }
        if (is_file(
            $this->_application->getResource('view')->getBaseDir() . '/' . $file
        )) {
            try {
                $this->_application->getResource('response')->setBody(
                    $this->_application->getResource('view')->render($file)
                );
                if (!$this->_application->getResource('response')->getHeader('Content-Type')) {
                    $type = $this->_application->getResource('response')->detectContentType($file);
                    if ($type) {
                        $this->_application->getResource('response')->setHeader(
                            'Content-Type', $type
                        );
                    }
                }
            } catch (Flooer_Exception $exception) {
                ob_clean();
                $this->_application->getResource('response')->setStatus(500);
                throw $exception;
            } catch (Exception $exception) {
                ob_clean();
                $this->_application->getResource('response')->setStatus(500);
                throw new Flooer_Exception(
                    $exception->getMessage(), $exception->getCode(), $exception->getFile(), $exception->getLine()
                );
            }

            return;
        }
        $this->_application->getResource('response')->setStatus(404);
        $message = 'View script file (' . $this->_application->getResource('view')
                                                             ->getBaseDir() . '/' . $file . ') not found';
        if (in_array($this->_application->getConfig('environment'), array('production', 'staging'))) {
            $message = 'Not Found';
        }
        throw new Flooer_Exception(
            $message, LOG_NOTICE
        );
    }

    /**
     * Output filtering
     *
     * @return  void
     */
    public function filterOutput()
    {
        $filter = new Flooer_Filter(
            array(
                'convertEncoding' => $this->_application->getConfig('mbstringSupport'),
                'convertNewline'  => true,
                'stripNull'       => true,
                'stripSlashes'    => false,
                'trimWhitespace'  => true,
                'encoding'        => $this->_application->getConfig('encoding'),
                'newline'         => $this->_application->getConfig('newline'),
            )
        );
        $body = $this->_application->getResource('response')->getBody();
        $filter->filter($body);
        $this->_application->getResource('response')->setBody($body);
    }

    /**
     * Send a response
     *
     * @return  void
     */
    public function sendResponse()
    {
        $this->_application->getResource('response')->send();
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
     * Set an executable flag for limitMethod()
     *
     * @param bool $bool
     *
     * @return  void
     */
    public function setLimitMethod($bool)
    {
        $this->_config['limitMethod'] = $bool;
    }

    /**
     * Get an executable flag for limitMethod()
     *
     * @return  bool
     */
    public function getLimitMethod()
    {
        return $this->_config['limitMethod'];
    }

    /**
     * Set an executable flag for filterInput()
     *
     * @param bool $bool
     *
     * @return  void
     */
    public function setFilterInput($bool)
    {
        $this->_config['filterInput'] = $bool;
    }

    /**
     * Get an executable flag for filterInput()
     *
     * @return  bool
     */
    public function getFilterInput()
    {
        return $this->_config['filterInput'];
    }

    /**
     * Set an executable flag for filterOutput()
     *
     * @param bool $bool
     *
     * @return  void
     */
    public function setFilterOutput($bool)
    {
        $this->_config['filterOutput'] = $bool;
    }

    /**
     * Get an executable flag for filterOutput()
     *
     * @return  bool
     */
    public function getFilterOutput()
    {
        return $this->_config['filterOutput'];
    }

    /**
     * Set an executable flag for sendResponse()
     *
     * @param bool $bool
     *
     * @return  void
     */
    public function setSendResponse($bool)
    {
        $this->_config['sendResponse'] = $bool;
    }

    /**
     * Get an executable flag for sendResponse()
     *
     * @return  bool
     */
    public function getSendResponse()
    {
        return $this->_config['sendResponse'];
    }

    /**
     * Set an executable flag for renderView()
     *
     * @param bool $bool
     *
     * @return  void
     */
    public function setRenderView($bool)
    {
        $this->_config['renderView'] = $bool;
    }

    /**
     * Get an executable flag for renderView()
     *
     * @return  bool
     */
    public function getRenderView()
    {
        return $this->_config['renderView'];
    }

    /**
     * Set an executable flag for renderErrorPage()
     *
     * @param bool $bool
     *
     * @return  void
     */
    public function setRenderErrorPage($bool)
    {
        $this->_config['renderErrorPage'] = $bool;
    }

    /**
     * Get an executable flag for renderErrorPage()
     *
     * @return  bool
     */
    public function getRenderErrorPage()
    {
        return $this->_config['renderErrorPage'];
    }

    /**
     * Set an executable flag for writeLog()
     *
     * @param bool $bool
     *
     * @return  void
     */
    public function setWriteLog($bool)
    {
        $this->_config['writeLog'] = $bool;
    }

    /**
     * Get an executable flag for writeLog()
     *
     * @return  bool
     */
    public function getWriteLog()
    {
        return $this->_config['writeLog'];
    }

    /**
     * Set an allowed method
     *
     * @param string $allowedMethod Comma-separated list
     *
     * @return  void
     */
    public function setAllowedMethod($allowedMethod)
    {
        $this->_config['allowedMethod'] = $allowedMethod;
    }

    /**
     * Get an allowed method
     *
     * @return  string
     */
    public function getAllowedMethod()
    {
        return $this->_config['allowedMethod'];
    }

    /**
     * Set the path of a base directory
     *
     * @param string $path
     *
     * @return  void
     */
    public function setBaseDir($path)
    {
        $this->_config['baseDir'] = $path;
    }

    /**
     * Get the path of a base directory
     *
     * @return  string
     */
    public function getBaseDir()
    {
        return $this->_config['baseDir'];
    }

    /**
     * Set a default controller name
     *
     * @param string $controller
     *
     * @return  void
     */
    public function setDefaultController($controller)
    {
        $this->_config['defaultController'] = $controller;
    }

    /**
     * Get a default controller name
     *
     * @return  string
     */
    public function getDefaultController()
    {
        return $this->_config['defaultController'];
    }

    /**
     * Set a default action name
     *
     * @param string $action
     *
     * @return  void
     */
    public function setDefaultAction($action)
    {
        $this->_config['defaultAction'] = $action;
    }

    /**
     * Get a default action name
     *
     * @return  string
     */
    public function getDefaultAction()
    {
        return $this->_config['defaultAction'];
    }

    /**
     * Set an error controller name
     *
     * @param string $controller
     *
     * @return  void
     */
    public function setErrorController($controller)
    {
        $this->_config['errorController'] = $controller;
    }

    /**
     * Get an error controller name
     *
     * @return  string
     */
    public function getErrorController()
    {
        return $this->_config['errorController'];
    }

    /**
     * Set an error action name
     *
     * @param string $action
     *
     * @return  void
     */
    public function setErrorAction($action)
    {
        $this->_config['errorAction'] = $action;
    }

    /**
     * Get an error action name
     *
     * @return  string
     */
    public function getErrorAction()
    {
        return $this->_config['errorAction'];
    }

    /**
     * Set an error method name
     *
     * @param string $method
     *
     * @return  void
     */
    public function setErrorMethod($method)
    {
        $this->_config['errorMethod'] = $method;
    }

    /**
     * Get an error method name
     *
     * @return  string
     */
    public function getErrorMethod()
    {
        return $this->_config['errorMethod'];
    }

    /**
     * Set a view name for layout
     *
     * @param string $layout
     *
     * @return  void
     */
    public function setLayoutView($layout)
    {
        $this->_config['layoutView'] = $layout;
    }

    /**
     * Get a view name for layout
     *
     * @return  string
     */
    public function getLayoutView()
    {
        return $this->_config['layoutView'];
    }

    /**
     * Set a controller file suffix
     *
     * @param string $suffix
     *
     * @return  void
     */
    public function setControllerFileSuffix($suffix)
    {
        $this->_config['controllerFileSuffix'] = $suffix;
    }

    /**
     * Get a controller file suffix
     *
     * @return  string
     */
    public function getControllerFileSuffix()
    {
        return $this->_config['controllerFileSuffix'];
    }

    /**
     * Set a view file suffix
     *
     * @param string $suffix
     *
     * @return  void
     */
    public function setViewFileSuffix($suffix)
    {
        $this->_config['viewFileSuffix'] = $suffix;
    }

    /**
     * Get a view file suffix
     *
     * @return  string
     */
    public function getViewFileSuffix()
    {
        return $this->_config['viewFileSuffix'];
    }

    /**
     * Set a configuration options for a controller class
     *
     * @param array $config
     *
     * @return  void
     */
    public function setControllerConfig(array $config)
    {
        $this->_config['controllerConfig'] = $config;
    }

    /**
     * Get a configuration options for a controller class
     *
     * @return  array
     */
    public function getControllerConfig()
    {
        return $this->_config['controllerConfig'];
    }

}
