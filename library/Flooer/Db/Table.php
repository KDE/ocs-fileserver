<?php

/**
 * Flooer Framework
 *
 * LICENSE: BSD License (2 Clause)
 *
 * @category    Flooer
 * @package     Flooer_Db
 * @subpackage  Table
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 * @copyright   Akira Ohgaki
 * @license     https://opensource.org/licenses/BSD-2-Clause  BSD License (2 Clause)
 * @link        https://github.com/akiraohgaki/flooer
 */

require_once 'Flooer/Db/Table/Row.php';
require_once 'Flooer/Db/Table/Rowset.php';

/**
 * Usage
 *
 * $table = new Flooer_Db_Table($db);
 * $table->setName('TableName');
 * $table->setPrimary('PrimaryKey');
 * $row = $table->RecordID;
 */

/**
 * Table class of SQL database abstraction layer
 *
 * @category    Flooer
 * @package     Flooer_Db
 * @subpackage  Table
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 */
class Flooer_Db_Table
{

    /**
     * Database connection object
     *
     * @var     Flooer_Db
     */
    protected $_db = null;

    /**
     * Configuration options
     *
     * @var     array
     */
    protected $_config = array(
        'name' => null,
        'prefix' => null,
        'columns' => '*',
        'primary' => 'id',
        'primaryInsert' => false,
        'insertIgnore' => false
    );

    /**
     * Row exists
     *
     * @var     array
     */
    protected $_rowExists = array();

    /**
     * Row object cache
     *
     * @var     array
     */
    protected $_rowCache = array();

    /**
     * Constructor
     *
     * @param   Flooer_Db &$db
     * @param   array $config
     * @return  void
     */
    public function __construct(Flooer_Db &$db, array $config = null)
    {
        $this->_db =& $db;
        if ($config) {
            $this->_config = $config + $this->_config;
        }
        if (!$this->_config['name']) {
            $this->_config['name'] = get_class($this);
        }
    }

    /**
     * Magic method to update/insert a row
     *
     * @param   string $key
     * @param   array|object $value
     * @return  void
     */
    public function __set($key, $value)
    {
        if (is_array($value) || is_object($value)) {
            // Update a row
            if ($this->__isset($key)) {
                $this->_rowExists = array();
                $this->_rowCache = array();
                $columns = array();
                $statementValues = array();
                foreach ($value as $columnName => $columnValue) {
                    $columns[] = "$columnName = :$columnName";
                    $statementValues[":$columnName"] = $columnValue;
                }
                $columnsSet = implode(',', $columns);
                $whereValue = $this->_db->quote($key);
                $sql = "UPDATE {$this->_config['prefix']}{$this->_config['name']}"
                    . " SET $columnsSet"
                    . " WHERE {$this->_config['primary']} = $whereValue;";
                $this->_db->addStatementLog($sql);
                $statement = $this->_db->prepare($sql);
                $statement->execute($statementValues);
                $statement->closeCursor();
            }
            // Insert a new row
            else {
                $this->_rowExists = array();
                $this->_rowCache = array();
                $columnNames = array();
                $columnValues = array();
                $statementValues = array();
                if ($this->_config['primaryInsert']) {
                    $columnNames[] = $this->_config['primary'];
                    $columnValues[] = ":{$this->_config['primary']}";
                    $statementValues[":{$this->_config['primary']}"] = $key;
                }
                foreach ($value as $columnName => $columnValue) {
                    $columnNames[] = $columnName;
                    $columnValues[] = ":$columnName";
                    $statementValues[":$columnName"] = $columnValue;
                }
                $columnNamesSet = implode(',', $columnNames);
                $columnValuesSet = implode(',', $columnValues);
                $sql = "INSERT ";
                if($this->_config['insertIgnore']) {
                    $sql .= " IGNORE ";
                }
                $sql .= " INTO {$this->_config['prefix']}{$this->_config['name']}"
                    . " ($columnNamesSet)"
                    . " VALUES ($columnValuesSet);";
                $this->_db->addStatementLog($sql);
                $statement = $this->_db->prepare($sql);
                $statement->execute($statementValues);
                $statement->closeCursor();
            }
            return;
        }
        trigger_error(
            "Setting non-array or non-object property ($key) is not allowed",
            E_USER_NOTICE
        );
    }

    /**
     * Magic method to get a row object
     *
     * @param   string $key
     * @return  Flooer_Db_Table_Row|null
     */
    public function __get($key)
    {
        if ($this->__isset($key)) {
            if (empty($this->_rowCache[$key])) {
                $whereValue = $this->_db->quote($key);
                $sql = "SELECT {$this->_config['columns']}"
                    . " FROM {$this->_config['prefix']}{$this->_config['name']}"
                    . " WHERE {$this->_config['primary']} = $whereValue;";
                $this->_db->addStatementLog($sql);
                $statement = $this->_db->prepare($sql);
                $statement->setFetchMode(
                    Flooer_Db::FETCH_CLASS,
                    'Flooer_Db_Table_Row'
                );
                $bool = $statement->execute();
                $row = $statement->fetch();
                $statement->closeCursor();
                $this->_rowCache[$key] = null;
                if ($bool && $row) {
                    $this->_rowCache[$key] = $row;
                }
            }
            return $this->_rowCache[$key];
        }
        return null;
    }

    /**
     * Magic method to check a row
     *
     * @param   string $key
     * @return  bool
     */
    public function __isset($key)
    {
        if (isset($this->_rowExists[$key])) {
            return $this->_rowExists[$key];
        }
        $driver = $this->_db->getAttribute(Flooer_Db::ATTR_DRIVER_NAME);
        $whereValue = $this->_db->quote($key);
        $sql = "SELECT COUNT(*)"
            . " FROM {$this->_config['prefix']}{$this->_config['name']}"
            . " WHERE {$this->_config['primary']} = $whereValue;";
        if ($driver == 'sqlite' || $driver == 'mysql' || $driver == 'pgsql') {
            $sql = "SELECT 1"
                . " FROM {$this->_config['prefix']}{$this->_config['name']}"
                . " WHERE {$this->_config['primary']} = $whereValue"
                . " LIMIT 1;";
        }
        else if ($driver == 'sqlsrv') {
            $sql = "SELECT TOP 1 1"
                . " FROM {$this->_config['prefix']}{$this->_config['name']}"
                . " WHERE {$this->_config['primary']} = $whereValue;";
        }
        $this->_db->addStatementLog($sql);
        $statement = $this->_db->prepare($sql);
        $bool = $statement->execute();
        $row = $statement->fetch(Flooer_Db::FETCH_NUM);
        $statement->closeCursor();
        if ($bool && $row) {
            if ($row[0]) {
                $this->_rowExists[$key] = true;
                return true;
            }
            $this->_rowExists[$key] = false;
        }
        return false;
    }

    /**
     * Magic method to delete a row
     *
     * @param   string $key
     * @return  void
     */
    public function __unset($key)
    {
        if ($this->__isset($key)) {
            $whereValue = $this->_db->quote($key);
            $sql = "DELETE FROM {$this->_config['prefix']}{$this->_config['name']}"
                . " WHERE {$this->_config['primary']} = $whereValue;";
            $this->_db->addStatementLog($sql);
            $statement = $this->_db->prepare($sql);
            $bool = $statement->execute();
            $statement->closeCursor();
            if ($bool) {
                $this->_rowExists[$key] = false;
                unset($this->_rowCache[$key]);
            }
        }
    }

    /**
     * Fetch one row
     *
     * @param   string $statementOption
     * @param   array $values
     * @return  Flooer_Db_Table_Row|null
     */
    public function fetchRow($statementOption = '', array $values = null)
    {
        $sql = "SELECT {$this->_config['columns']}"
            . " FROM {$this->_config['prefix']}{$this->_config['name']}"
            . " $statementOption;";
        $this->_db->addStatementLog($sql);
        $statement = $this->_db->prepare($sql);
        $statement->setFetchMode(
            Flooer_Db::FETCH_CLASS,
            'Flooer_Db_Table_Row'
        );
        $bool = $statement->execute($values);
        $row = $statement->fetch();
        $statement->closeCursor();
        if ($bool && $row) {
            return $row;
        }
        return null;
    }

    /**
     * Fetch a rowset
     *
     * @param   string $statementOption
     * @param   array $values
     * @return  Flooer_Db_Table_Rowset|null
     */
    public function fetchRowset($statementOption = '', array $values = null)
    {
        $sql = "SELECT {$this->_config['columns']}"
            . " FROM {$this->_config['prefix']}{$this->_config['name']}"
            . " $statementOption;";
        $this->_db->addStatementLog($sql);
        $statement = $this->_db->prepare($sql);
        $statement->setFetchMode(
            Flooer_Db::FETCH_CLASS,
            'Flooer_Db_Table_Row'
        );
        $bool = $statement->execute($values);
        $rowsetArray = $statement->fetchAll();
        $statement->closeCursor();
        if ($bool && $rowsetArray) {
            $rowset = new Flooer_Db_Table_Rowset();
            foreach ($rowsetArray as $key => $value) {
                $rowset->$key = $value;
            }
            return $rowset;
        }
        return null;
    }

    /**
     * Count a record size
     *
     * @param   string $statementOption
     * @param   array $values
     * @return  int|bool
     */
    public function count($statementOption = '', array $values = null)
    {
        $sql = "SELECT COUNT(*)"
            . " FROM {$this->_config['prefix']}{$this->_config['name']}"
            . " $statementOption;";
        $this->_db->addStatementLog($sql);
        $statement = $this->_db->prepare($sql);
        $bool = $statement->execute($values);
        $row = $statement->fetch(Flooer_Db::FETCH_NUM);
        $statement->closeCursor();
        if ($bool && $row) {
            return $row[0];
        }
        return false;
    }

    /**
     * Get a database connection object
     *
     * @return  Flooer_Db
     */
    public function getDb()
    {
        return $this->_db;
    }

    /**
     * Set a table name
     *
     * @param   string $name
     * @return  void
     */
    public function setName($name)
    {
        $this->_config['name'] = $name;
        $this->_rowExists = array();
        $this->_rowCache = array();
    }

    /**
     * Get a table name
     *
     * @return  string
     */
    public function getName()
    {
        return $this->_config['name'];
    }

    /**
     * Set a table name prefix
     *
     * @param   string $prefix
     * @return  void
     */
    public function setPrefix($prefix)
    {
        $this->_config['prefix'] = $prefix;
        $this->_rowExists = array();
        $this->_rowCache = array();
    }

    /**
     * Get a table name prefix
     *
     * @return  string
     */
    public function getPrefix()
    {
        return $this->_config['prefix'];
    }

    /**
     * Set a table columns
     *
     * @param   string $columns
     * @return  void
     */
    public function setColumns($columns)
    {
        $this->_config['columns'] = $columns;
        $this->_rowCache = array();
    }

    /**
     * Get a table columns
     *
     * @return  string
     */
    public function getColumns()
    {
        return $this->_config['columns'];
    }

    /**
     * Set a primary key name
     *
     * @param   string $primary
     * @return  void
     */
    public function setPrimary($primary)
    {
        $this->_config['primary'] = $primary;
        $this->_rowExists = array();
        $this->_rowCache = array();
    }

    /**
     * Get a primary key name
     *
     * @return  string
     */
    public function getPrimary()
    {
        return $this->_config['primary'];
    }

    /**
     * Set an option for a primary key inserting
     *
     * @param   bool $bool
     * @return  void
     */
    public function setPrimaryInsert($bool)
    {
        $this->_config['primaryInsert'] = $bool;
    }

    /**
     * Get an option for a primary key inserting
     *
     * @return  bool
     */
    public function getPrimaryInsert()
    {
        return $this->_config['primaryInsert'];
    }
    
    /**
     * Set an option for a insert irgnore
     *
     * @param   bool $bool
     * @return  void
     */
    public function setInsertIgnore($bool)
    {
        $this->_config['insertIgnore'] = $bool;
    }

    /**
     * Get an option for a primary key inserting
     *
     * @return  bool
     */
    public function getInsertIgnore()
    {
        return $this->_config['insertIgnore'];
    }

}
