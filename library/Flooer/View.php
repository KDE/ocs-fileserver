<?php

/**
 * Flooer Framework
 *
 * LICENSE: BSD License (2 Clause)
 *
 * @category    Flooer
 * @package     Flooer_View
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 * @copyright   Akira Ohgaki
 * @license     https://opensource.org/licenses/BSD-2-Clause  BSD License (2 Clause)
 * @link        https://github.com/akiraohgaki/flooer
 */

/**
 * Usage
 *
 * $view = new Flooer_View();
 * $view->setBaseDir('./views');
 * $view->setFile('view.phtml');
 * $view->key = $value;
 * echo $view->render();
 */

/**
 * View class
 *
 * @category    Flooer
 * @package     Flooer_View
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 */
class Flooer_View
{

    /**
     * Configuration options
     *
     * @var     array
     */
    protected $_config = array(
        'baseDir' => null,
        'file' => null
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
        if ($key[0] != '_') {
            $this->$key = $value;
            return;
        }
        trigger_error(
            "Setting protected or private property ($key) is not allowed",
            E_USER_NOTICE
        );
    }

    /**
     * Magic method to get a property
     *
     * @param   string $key
     * @return  mixed
     */
    public function __get($key)
    {
        if ($key[0] != '_' && isset($this->$key)) {
            return $this->$key;
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
        if ($key[0] != '_') {
            return isset($this->$key);
        }
        return false;
    }

    /**
     * Magic method to unset a property
     *
     * @param   string $key
     * @return  void
     */
    public function __unset($key)
    {
        if ($key[0] != '_') {
            unset($this->$key);
        }
    }

    /**
     * Render a view
     *
     * @param   string $filename
     * @return  string
     */
    public function render($filename = null)
    {
        if (!$filename && $this->_config['file']) {
            $filename = $this->_config['file'];
        }
        if ($this->_config['baseDir']) {
            $filename = $this->_config['baseDir'] . '/' . $filename;
        }
        if (is_file($filename)) {
            ob_start();
            include $filename;
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        }
        trigger_error(
            "View script file ($filename) not found",
            E_USER_NOTICE
        );
    }

    /**
     * Set the path of a base directory
     *
     * @param   string $path
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
     * Set the filename of a view script
     *
     * @param   string $filename
     * @return  void
     */
    public function setFile($filename)
    {
        $this->_config['file'] = $filename;
    }

    /**
     * Get the filename of a view script
     *
     * @return  string
     */
    public function getFile()
    {
        return $this->_config['file'];
    }

}
