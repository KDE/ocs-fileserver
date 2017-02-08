<?php

/**
 * Flooer Framework
 *
 * LICENSE: BSD License (2 Clause)
 *
 * @category    Flooer
 * @package     Flooer_Controller
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 * @copyright   Akira Ohgaki
 * @license     https://opensource.org/licenses/BSD-2-Clause  BSD License (2 Clause)
 * @link        https://github.com/akiraohgaki/flooer
 */

/**
 * Usage
 *
 * class Controller extends Flooer_Controller
 * {
 *     public function action()
 *     {
 *     }
 * }
 * $controller = new Controller();
 * $controller->execute('action');
 */

/**
 * Action controller class
 *
 * @category    Flooer
 * @package     Flooer_Controller
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 */
abstract class Flooer_Controller
{

    /**
     * Configuration options
     *
     * @var     array
     */
    protected $_config = array();

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
     * Magic method for undefined methods
     *
     * @param   string $method
     * @param   array $arguments
     * @return  void
     */
    public function __call($method, array $arguments)
    {
        trigger_error(
            "Method ($method) does not exist",
            E_USER_ERROR
        );
    }

    /**
     * Execute an action
     *
     * @param   string $action
     * @return  void
     */
    public function execute($action)
    {
        $this->construct();
        $this->$action();
        $this->destruct();
    }

    /**
     * Alternative constructor
     *
     * @return  void
     */
    public function construct()
    {
    }

    /**
     * Alternative destructor
     *
     * @return  void
     */
    public function destruct()
    {
    }

}
