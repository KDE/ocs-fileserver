<?php

/**
 * ocs-fileserver
 *
 * Copyright 2016 by pling GmbH.
 *
 * This file is part of ocs-fileserver.
 *
 * ocs-fileserver is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * ocs-fileserver is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Foobar.  If not, see <http://www.gnu.org/licenses/>.
 **/

require_once 'models/table_ocs_downloads.php';

class OcsModel
{

    protected $_db = null;

    protected $_config = array(
        'createTables' => false
    );

    public $profiles = null;


    public function __construct(Flooer_Db &$db, array $config = null)
    {
        $this->_db =& $db;
        if ($config) {
            $this->_config = $config + $this->_config;
        }

        $this->ocs_downloads = new table_ocs_downloads($this->_db);
        $this->ocs_downloads->setOcsDbConfig($this->_config);
    }

    public function getDb()
    {
        return $this->_db;
    }

}
