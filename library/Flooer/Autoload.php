<?php

/**
 * Flooer Framework
 *
 * LICENSE: BSD License (2 Clause)
 *
 * @category    Flooer
 * @package     Flooer_Autoload
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 * @copyright   Akira Ohgaki
 * @license     http://www.freebsd.org/copyright/freebsd-license.html  BSD License (2 Clause)
 * @link        https://github.com/akiraohgaki/flooer
 */

/**
 * Usage
 *
 * $autoload = new Flooer_Autoload();
 * $autoload->register();
 * $autoload->setExtensions('.php,.inc');
 */

/**
 * Class autoloader class
 *
 * @category    Flooer
 * @package     Flooer_Autoload
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 */
class Flooer_Autoload
{

    /**
     * Configuration options
     *
     * @var     array
     */
    protected $_config = array(
        'register' => false,
        'extensions' => null
    );

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
        if ($this->_config['register']) {
            $this->register();
        }
        if ($this->_config['extensions']) {
            $this->setExtensions($this->_config['extensions']);
        }
    }

    /**
     * Autoload a class
     *
     * @param   string $class
     * @return  void
     */
    public function autoload($class)
    {
        $extensions = explode(
            ',',
            str_replace(' ', '', spl_autoload_extensions())
        );
        $directories = explode(
            PATH_SEPARATOR,
            get_include_path()
        );
        $pearStyleFile = null;
        if (strpos($class, '_')) {
            $pearStyleFile = str_replace('_', '/', $class);
        }
        $lowerCaseFile = strtolower($class);
        foreach ($extensions as $extension) {
            foreach ($directories as $directory) {
                if ($pearStyleFile
                    && is_file($directory . '/' . $pearStyleFile . $extension)
                ) {
                    include_once $directory . '/' . $pearStyleFile . $extension;
                    break 2;
                }
                if (is_file($directory . '/' . $lowerCaseFile . $extension)) {
                    include_once $directory . '/' . $lowerCaseFile . $extension;
                    break 2;
                }
                if (is_file($directory . '/' . $class . $extension)) {
                    include_once $directory . '/' . $class . $extension;
                    break 2;
                }
            }
        }
    }

    /**
     * Register the autoload function
     *
     * @return  bool
     */
    public function register()
    {
        return spl_autoload_register(array($this, 'autoload'));
    }

    /**
     * Unregister the autoload function
     *
     * @return  bool
     */
    public function unregister()
    {
        return spl_autoload_unregister(array($this, 'autoload'));
    }

    /**
     * Get a registered functions
     *
     * @return  array|false
     */
    public function getFunctions()
    {
        return spl_autoload_functions();
    }

    /**
     * Set a file extensions
     *
     * @param   string $extensions Comma-separated list
     * @return  void
     */
    public function setExtensions($extensions)
    {
        spl_autoload_extensions($extensions);
    }

    /**
     * Get a file extensions
     *
     * @return  string
     */
    public function getExtensions()
    {
        return spl_autoload_extensions();
    }

}
