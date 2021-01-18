<?php

/**
 * Flooer Framework
 *
 * LICENSE: BSD License (2 Clause)
 *
 * @category    Flooer
 * @package     Flooer_Db
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 * @copyright   Akira Ohgaki
 * @license     https://opensource.org/licenses/BSD-2-Clause  BSD License (2 Clause)
 * @link        https://github.com/akiraohgaki/flooer
 */

require_once 'Flooer/Db/Statement.php';
require_once 'Flooer/Db/Table.php';

/**
 * Usage
 *
 * $db = new Flooer_Db(array(
 *     'dsn' => 'mysql:host=localhost;dbname=database',
 *     'username' => 'username',
 *     'password' => 'password'
 * ));
 * $value = $db->TableName->RecordID->ColumnName;
 */

/**
 * Database connection class of SQL database abstraction layer
 *
 * @category    Flooer
 * @package     Flooer_Db
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 */
class Flooer_Db extends PDO
{

    /**
     * Configuration options
     *
     * @var     array
     */
    protected $_config = array(
        'dsn' => null,
        'username' => null,
        'password' => null,
        'driverOptions' => array(),
        'tableConfig' => array()
    );

    /**
     * Table exists
     *
     * @var     array
     */
    protected $_tableExists = array();

    /**
     * Table object cache
     *
     * @var     array
     */
    protected $_tableCache = array();

    /**
     * SQL statement log
     *
     * @var     array
     */
    protected $_statementLog = array();

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
        set_exception_handler(array(__CLASS__, 'exceptionHandler'));
        parent::__construct(
            $this->_config['dsn'],
            $this->_config['username'],
            $this->_config['password'],
            $this->_config['driverOptions']
        );
        restore_exception_handler();
        if (!isset($this->_config['driverOptions'][parent::ATTR_STATEMENT_CLASS])) {
            parent::setAttribute(
                parent::ATTR_STATEMENT_CLASS,
                array('Flooer_Db_Statement')
            );
        }
        if (!isset($this->_config['driverOptions'][parent::ATTR_DEFAULT_FETCH_MODE])) {
            parent::setAttribute(
                parent::ATTR_DEFAULT_FETCH_MODE,
                parent::FETCH_ASSOC
            );
        }
        if (!isset($this->_config['driverOptions'][parent::ATTR_PERSISTENT])) {
            parent::setAttribute(parent::ATTR_PERSISTENT, false);
        }
    }

    /**
     * Magic method to create a table
     *
     * @param   string $key
     * @param   array|object $value
     * @return  void
     */
    public function __set($key, $value)
    {
        if (is_array($value) || is_object($value)) {
            $this->__unset($key);
            $tableName = $key;
            if (isset($this->_config['tableConfig']['prefix'])) {
                $tableName = $this->_config['tableConfig']['prefix'] . $tableName;
            }
            $fields = array();
            foreach ($value as $fieldName => $fieldType) {
                $fields[] = "$fieldName $fieldType";
            }
            $definition = implode(',', $fields);
            $sql = "CREATE TABLE $tableName ($definition);";
            $this->_statementLog[] = $sql;
            $count = parent::exec($sql);
            if ($count !== false) {
                $this->_tableExists[$tableName] = true;
            }
            return;
        }
        trigger_error(
            "Setting non-array or non-object property ($key) is not allowed",
            E_USER_NOTICE
        );
    }

    /**
     * Magic method to get a table object
     *
     * @param   string $key
     * @return  Flooer_Db_Table|null
     */
    public function __get($key)
    {
        if ($this->__isset($key)) {
            $tableName = $key;
            if (isset($this->_config['tableConfig']['prefix'])) {
                $tableName = $this->_config['tableConfig']['prefix'] . $tableName;
            }
            if (empty($this->_tableCache[$tableName])) {
                $this->_tableCache[$tableName] = new Flooer_Db_Table(
                    $this,
                    array('name' => $key)
                    + $this->_config['tableConfig']
                );
            }
            return $this->_tableCache[$tableName];
        }
        return null;
    }

    /**
     * Magic method to check a table
     *
     * @param   string $key
     * @return  bool
     */
    public function __isset($key)
    {
        $tableName = $key;
        if (isset($this->_config['tableConfig']['prefix'])) {
            $tableName = $this->_config['tableConfig']['prefix'] . $tableName;
        }
        if (isset($this->_tableExists[$tableName])) {
            return $this->_tableExists[$tableName];
        }
        $driver = parent::getAttribute(parent::ATTR_DRIVER_NAME);
        if ($driver == 'sqlite') {
            $sql = "SELECT 1"
                . " FROM sqlite_master"
                . " WHERE type = " . parent::quote('table')
                . " AND name = " . parent::quote($tableName)
                . " LIMIT 1;";
            $this->_statementLog[] = $sql;
            $statement = parent::prepare($sql);
            $bool = $statement->execute();
            $row = $statement->fetch(parent::FETCH_NUM);
            $statement->closeCursor();
            if ($bool && $row) {
                if ($row[0]) {
                    $this->_tableExists[$tableName] = true;
                    return true;
                }
                $this->_tableExists[$tableName] = false;
            }
        }
        else {
            $sql = "SELECT COUNT(*) FROM $tableName;";
            if ($driver == 'mysql' || $driver == 'pgsql') {
                $sql = "SELECT 1 FROM $tableName LIMIT 1;";
            }
            else if ($driver == 'sqlsrv') {
                $sql = "SELECT TOP 1 1 FROM $tableName;";
            }
            $this->_statementLog[] = $sql;
            $statement = parent::prepare($sql);
            $bool = $statement->execute();
            $statement->closeCursor();
            if ($bool) {
                $this->_tableExists[$tableName] = true;
                return true;
            }
            $this->_tableExists[$tableName] = false;
        }
        return false;
    }

    /**
     * Magic method to drop a table
     *
     * @param   string $key
     * @return  void
     */
    public function __unset($key)
    {
        if ($this->__isset($key)) {
            $tableName = $key;
            if (isset($this->_config['tableConfig']['prefix'])) {
                $tableName = $this->_config['tableConfig']['prefix'] . $tableName;
            }
            $sql = "DROP TABLE $tableName;";
            $this->_statementLog[] = $sql;
            $count = parent::exec($sql);
            if ($count !== false) {
                $this->_tableExists[$tableName] = false;
                unset($this->_tableCache[$tableName]);
            }
        }
    }

    /**
     * Exception handler
     *
     * @param   PDOException $exception
     * @return  void
     */
    public static function exceptionHandler($exception)
    {
        $code = $exception->getCode();
        $message = $exception->getMessage();
        $file = $exception->getFile();
        $line = $exception->getLine();
        echo __CLASS__ . ": [$code]: $message; $file($line)\n";
    }

    /**
     * Set a configuration options for a table class
     *
     * @param   array $config
     * @return  void
     */
    public function setTableConfig(array $config)
    {
        $this->_config['tableConfig'] = $config;
    }

    /**
     * Get a configuration options for a table class
     *
     * @return  array
     */
    public function getTableConfig()
    {
        return $this->_config['tableConfig'];
    }

    /**
     * Add a SQL statement log
     *
     * @return  void
     */
    public function addStatementLog($sql)
    {
        $this->_statementLog[] = $sql;
    }

    /**
     * Get a SQL statement log
     *
     * @return  array
     */
    public function getStatementLog()
    {
        return $this->_statementLog;
    }

}
