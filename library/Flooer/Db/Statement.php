<?php

/**
 * Flooer Framework
 *
 * LICENSE: BSD License (2 Clause)
 *
 * @category    Flooer
 * @package     Flooer_Db
 * @subpackage  Statement
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 * @copyright   Akira Ohgaki
 * @license     https://opensource.org/licenses/BSD-2-Clause  BSD License (2 Clause)
 * @link        https://github.com/akiraohgaki/flooer
 */

/**
 * Usage
 *
 * $db->setAttribute(
 *     Flooer_Db::ATTR_STATEMENT_CLASS,
 *     array('Flooer_Db_Statement')
 * );
 * $statement = $db->prepare('SELECT * FROM TableName');
 * $statement->execute();
 * $rowset = $statement->fetchAll();
 */

/**
 * Statement class of SQL database abstraction layer
 *
 * @category    Flooer
 * @package     Flooer_Db
 * @subpackage  Statement
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 */
class Flooer_Db_Statement extends PDOStatement
{
}
