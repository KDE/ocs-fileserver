<?php /** @noinspection PhpIncludeInspection */

/**
 * Flooer Framework
 *
 * LICENSE: BSD License (2 Clause)
 *
 * @category    Flooer
 * @package     Flooer_Application
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 * @copyright   Akira Ohgaki
 * @license     https://opensource.org/licenses/BSD-2-Clause  BSD License (2 Clause)
 * @link        https://github.com/akiraohgaki/flooer
 */

/**
 * Usage
 *
 * $application = new Flooer_Application();
 * $application->setConfig('baseDir', '../application');
 * $application->setConfig('environment', 'development');
 * $application->run();
 */

/**
 * Application environment class of application
 *
 * @category    Flooer
 * @package     Flooer_Application
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 */
class Flooer_Application
{

    /**
     * Configuration options
     *
     * @var     array
     */
    protected $_config = array();

    /**
     * Application resources
     *
     * @var     array
     */
    protected $_resources = array();

    /**
     * Application status
     *
     * @var     bool
     */
    protected $_status = false;

    /**
     * Constructor
     *
     * @param array $config
     *
     * @return  void
     */
    public function __construct(array $config = null)
    {
        ob_start();
        $this->_config = array(
            'baseDir'               => './',
            'environment'           => 'production',
            'safeModeSupport'       => ini_get('safe_mode'),
            'magicQuotesSupport'    => get_magic_quotes_gpc(),
            'mbstringSupport'       => extension_loaded('mbstring'),
            'memoryLimit'           => '128M',
            'maxExecutionTime'      => 30,
            'socketTimeout'         => 20,
            'timezone'              => 'UTC',
            'encoding'              => 'UTF-8',
            'newline'               => 'LF',
            'mbLanguage'            => 'uni',
            'mbDetectOrder'         => 'ASCII, JIS, UTF-8, EUC-JP, SJIS',
            'mbSubstituteCharacter' => 'none',
            'bootstrap'             => 'Bootstrap',
            'bootstrapFileSuffix'   => '.php',
            'autoloadConfig'        => array(
                'register'   => true,
                'extensions' => '.php',
            ),
            'bootstrapConfig'       => array(),
        );
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
     * Run an application
     *
     * @return  void
     * @throws Flooer_Exception
     */
    public function run()
    {
        if ($this->_status) {
            trigger_error(
                'Application is already running', E_USER_NOTICE
            );
        }
        $this->_status = true;
        $this->_setup();
        $this->_bootstrap();
        $this->_dispatch();
    }

    /**
     * Setup an application environment
     *
     * @return  void
     */
    protected function _setup()
    {
        $this->_config['baseDir'] = realpath($this->_config['baseDir']);
        $this->_config['environment'] = strtolower($this->_config['environment']);
        set_include_path(
            implode(
                PATH_SEPARATOR, array(
                                  dirname(dirname(__FILE__)),
                                  $this->_config['baseDir'],
                                  get_include_path(),
                              )
            )
        );
        switch ($this->_config['environment']) {
            case 'debug':
                ini_set('display_errors', 1);
                error_reporting(E_ALL | E_NOTICE | E_STRICT);
                break;
            case 'development':
                // Continue to testing
            case 'testing':
                ini_set('display_errors', 1);
                error_reporting(E_ALL ^ E_NOTICE);
                break;
            case 'staging':
                // Continue to production
            case 'production':
                // Continue to default
            default:
                ini_set('display_errors', 0);
                error_reporting(E_ERROR | E_WARNING | E_PARSE);
                break;
        }
        ini_set('memory_limit', $this->_config['memoryLimit']);
        ini_set('default_socket_timeout', $this->_config['socketTimeout']);
        date_default_timezone_set($this->_config['timezone']);
        if (!$this->_config['safeModeSupport']) {
            set_time_limit($this->_config['maxExecutionTime']);
        }
        if ($this->_config['mbstringSupport']) {
            mb_language($this->_config['mbLanguage']);
            mb_internal_encoding($this->_config['encoding']);
            mb_regex_encoding($this->_config['encoding']);
            mb_http_output($this->_config['encoding']);
            mb_detect_order($this->_config['mbDetectOrder']);
            mb_substitute_character($this->_config['mbSubstituteCharacter']);
        }
        require_once 'Flooer/Exception.php';
        Flooer_Exception::setExceptionHandler();
        Flooer_Exception::setErrorHandler();
        require_once 'Flooer/Autoload.php';
        $this->_resources['autoload'] = new Flooer_Autoload(
            $this->_config['autoloadConfig']
        );
    }

    /**
     * Bootstrap application
     *
     * @return  void
     */
    protected function _bootstrap()
    {
        require_once 'Flooer/Application/Bootstrap.php';
        if (is_file(
            $this->_config['baseDir'] . '/' . $this->_config['bootstrap'] . $this->_config['bootstrapFileSuffix']
        )) {
            include_once $this->_config['baseDir'] . '/' . $this->_config['bootstrap'] . $this->_config['bootstrapFileSuffix'];
            if (class_exists($this->_config['bootstrap'], false)) {
                $bootstrap = new $this->_config['bootstrap'](
                    $this, $this->_config['bootstrapConfig']
                );
                if ($bootstrap instanceof Flooer_Application_Bootstrap) {
                    $bootstrap->bootstrap();

                    return;
                }
            }
            trigger_error(
                'Invalid bootstrapper', E_USER_ERROR
            );
        }
        $bootstrap = new Flooer_Application_Bootstrap(
            $this, $this->_config['bootstrapConfig']
        );
        $bootstrap->bootstrap();
    }

    /**
     * Dispatch to a page script or an action controller
     *
     * @param string $filename
     *
     * @return  void
     * @throws Flooer_Exception
     */
    protected function _dispatch($filename = null)
    {
        if (isset($this->_resources['dispatch']) && $this->_resources['dispatch'] instanceof Flooer_Application_Dispatch && isset($this->_resources['request']) && $this->_resources['request'] instanceof Flooer_Http_Request && isset($this->_resources['response']) && $this->_resources['response'] instanceof Flooer_Http_Response) {
            $this->_resources['dispatch']->dispatch($filename);

            return;
        }
        trigger_error(
            'Resource for dispatcher, HTTP request and response' . ' must be initialized for application', E_USER_ERROR
        );
    }

    /**
     * Show an application information page
     *
     * @return  void
     * @throws Flooer_Exception
     */
    public function info()
    {
        if ($this->_status) {
            trigger_error(
                'Application is already running', E_USER_NOTICE
            );
        }
        $this->_status = true;
        $this->_setup();
        $this->_bootstrap();
        $this->_dispatch(
            dirname(__FILE__) . '/Application/pages/info.phtml'
        );
    }

    /**
     * Set a configuration options
     *
     * @param array $config
     *
     * @return  void
     */
    public function setConfigs(array $config)
    {
        $this->_config = $config;
    }

    /**
     * Get a configuration options
     *
     * @return  array
     */
    public function getConfigs()
    {
        return $this->_config;
    }

    /**
     * Get a configuration option
     *
     * @param string $key
     *
     * @return  mixed
     */
    public function getConfig($key)
    {
        if (isset($this->_config[$key])) {
            return $this->_config[$key];
        }

        return null;
    }

    /**
     * Set a configuration option
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return  void
     */
    public function setConfig($key, $value)
    {
        $this->_config[$key] = $value;
    }

    /**
     * Get a resources
     *
     * @return  array
     */
    public function getResources()
    {
        return $this->_resources;
    }

    /**
     * Set a resources
     *
     * @param array $resources
     *
     * @return  void
     */
    public function setResources(array $resources)
    {
        $this->_resources = $resources;
    }

    /**
     * Set a resource
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return  void
     */
    public function setResource($key, $value)
    {
        $this->_resources[$key] = $value;
    }

    /**
     * Get a resource
     *
     * @param string $key
     *
     * @return  mixed
     */
    public function getResource($key)
    {
        if (isset($this->_resources[$key])) {
            return $this->_resources[$key];
        }

        return null;
    }

}
