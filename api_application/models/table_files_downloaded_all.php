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

class table_files_downloaded_all extends BaseModel
{

    public function __construct(&$db)
    {
        parent::__construct($db, $db->getTableConfig());
        $this->setName('files_downloaded_all');
        $this->setPrimaryInsert(true);
    }

    public function __set($key, $value)
    {
        $value = $this->_convertArrayToObject($value);
        unset($value->id);
        if(empty($value->referer)) {
            $value->referer = $this->_getReferer();
        }
        $value->downloaded_timestamp = $this->_getTimestamp();
        $value->downloaded_ip = $this->_getIp();
        parent::__set($key, $value);
    }

    public function deleteByCollectionId($collectionId)
    {
        $primary = $this->getPrimary();
        $this->setPrimary('collection_id');
        unset($this->$collectionId);
        $this->setPrimary($primary);
    }

    public function deleteByFileId($fileId)
    {
        $primary = $this->getPrimary();
        $this->setPrimary('file_id');
        unset($this->$fileId);
        $this->setPrimary($primary);
    }

}
