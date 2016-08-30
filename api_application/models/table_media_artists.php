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

class table_media_artists extends BaseModel
{

    public function __construct(&$db)
    {
        parent::__construct($db, $db->getTableConfig());
        $this->setName('media_artists');
        $this->setPrimaryInsert(true);
    }

    public function __set($key, $value)
    {
        $value = $this->_convertArrayToObject($value);
        unset($value->id);
        parent::__set($key, $value);
    }

    public function getId($clientId, $name)
    {
        $artist = $this->fetchRow(
            'WHERE client_id = :client_id'
            . ' AND name = :name'
            . ' LIMIT 1',
            array(
                ':client_id' => $clientId,
                ':name' => $name
            )
        );

        if ($artist) {
            return $artist->id;
        }
        return null;
    }

}
