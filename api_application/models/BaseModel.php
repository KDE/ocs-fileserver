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

class BaseModel extends Flooer_Db_Table
{

    /**
     * Generates a ID number for primary key
     *
     * This method generating a unique ID number based on unix time.
     * And the maximum value of the ID has affected by database
     * column data type.
     *
     * Case of INT Signed:
     * Max value = 2147483647 (19 Jan 2038 03:14:07 UTC)
     * Case of INT Unsigned:
     * Max value = 4294967295 (07 Feb 2106 06:28:15 UTC)
     * Case of BIGINT Signed:
     * Max value = 9223372036854775807 (After 3000 billion years)
     */
    public function generateId()
    {
        $id = time() + mt_rand(1, 1000);
        while (isset($this->$id)) {
            $id = time() + mt_rand(1, 1000);
        }
        return $id;
    }
    
    public function generateNewId()
    {
        $result = $this->_db->query("SELECT UUID_SHORT();");
        $res = $result->fetchAll();
        return $res[0]['UUID_SHORT()'];
    }

    protected function _getTimestamp($time = null)
    {
        if ($time === null) {
            $time = time();
        }
        return date('Y-m-d H:i:s', $time);
    }

    protected function _getIp()
    {
        return isset($_SERVER['[HTTP_X_FORWARDED_FOR']) ? explode(',',$_SERVER['[HTTP_X_FORWARDED_FOR'])[0] : $_SERVER['REMOTE_ADDR'];
    }

    protected function _getReferer()
    {
        $referer = null;
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $referer = $_SERVER['HTTP_REFERER'];
        }
        return $referer;
    }

    protected function _convertArrayToObject($values)
    {
        if (is_array($values)) {
            $values = (object) $values;
        }
        if (is_object($values)) {
            foreach ($values as &$value) {
                $value = $this->_convertArrayToObject($value);
            }
        }
        return $values;
    }

    protected function _convertObjectToArray($values)
    {
        if (is_object($values)) {
            $values = (array) $values;
        }
        if (is_array($values)) {
            foreach ($values as &$value) {
                $value = $this->_convertObjectToArray($value);
            }
        }
        return $values;
    }

    protected function _convertFavoriteIdsToStatement(array $favoriteIds, array $columns)
    {
        $whereOr = array();

        if (!empty($favoriteIds['ownerIds'])
            && !empty($columns['ownerId'])
        ) {
            $ownerIds = array();
            foreach ($favoriteIds['ownerIds'] as $id) {
                $ownerIds[] = $this->getDb()->quote($id);
            }
            $whereOr[] = "{$columns['ownerId']} IN ("
                . implode(',', $ownerIds)
                . ")";
        }

        if (!empty($favoriteIds['collectionIds'])
            && !empty($columns['collectionId'])
        ) {
            $collectionIds = array();
            foreach ($favoriteIds['collectionIds'] as $id) {
                $collectionIds[] = $this->getDb()->quote($id);
            }
            $whereOr[] = "{$columns['collectionId']} IN ("
                . implode(',', $collectionIds)
                . ")";
        }

        if (!empty($favoriteIds['fileIds'])
            && !empty($columns['fileId'])
        ) {
            $fileIds = array();
            foreach ($favoriteIds['fileIds'] as $id) {
                $fileIds[] = $this->getDb()->quote($id);
            }
            $whereOr[] = "{$columns['fileId']} IN ("
                . implode(',', $fileIds)
                . ")";
        }

        if ($whereOr) {
            return '(' . implode(' OR ', $whereOr) . ')';
        }
        return '';
    }

}
