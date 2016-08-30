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

class table_favorites extends BaseModel
{

    public function __construct(&$db)
    {
        parent::__construct($db, $db->getTableConfig());
        $this->setName('favorites');
        $this->setPrimaryInsert(true);
    }

    public function __set($key, $value)
    {
        $value = $this->_convertArrayToObject($value);
        unset($value->id);
        parent::__set($key, $value);
    }

    public function getFavorites($clientId = null, $userId = null, $ownerId = null, $collectionId = null, $fileId = null, $ids = null, $perpage = 20, $page = 1)
    {
        $statementOption = '';
        $where = array();
        $values = array();
        $offset = 0;

        if ($clientId) {
            $where[] = 'client_id = :client_id';
            $values[':client_id'] = $clientId;
        }
        if ($userId) {
            $where[] = 'user_id = :user_id';
            $values[':user_id'] = $userId;
        }
        if ($ownerId) {
            $where[] = 'owner_id = :owner_id';
            $values[':owner_id'] = $ownerId;
        }
        if ($collectionId) {
            $where[] = 'collection_id = :collection_id';
            $values[':collection_id'] = $collectionId;
        }
        if ($fileId) {
            $where[] = 'file_id = :file_id';
            $values[':file_id'] = $fileId;
        }
        if ($ids) {
            $_ids = array();
            foreach (explode(',', $ids) as $id) {
                $id = trim($id);
                if ($id) {
                    $_ids[] = $this->getDb()->quote($id);
                }
            }
            if ($_ids) {
                $where[] = 'id IN (' . implode(',', $_ids) . ')';
            }
        }

        if ($where) {
            $statementOption = 'WHERE ' . implode(' AND ', $where);
        }

        if ($page > 1) {
            $offset = ($page - 1) * $perpage;
        }

        $favorites = $this->fetchRowset(
            $statementOption
            . " ORDER BY id ASC LIMIT $perpage OFFSET $offset",
            $values
        );

        if (!$favorites) {
            return null;
        }

        $pagination = Flooer_Utility_Pagination::paginate(
            $this->count($statementOption, $values),
            $perpage,
            $page
        );

        return array(
            'favorites' => $favorites,
            'pagination' => $pagination
        );
    }

    public function getFavoriteOwners($clientId, $userId)
    {
        return $this->fetchRowset(
            'WHERE client_id = :client_id'
            . ' AND user_id = :user_id'
            . ' AND owner_id IS NOT NULL'
            . ' AND collection_id IS NULL'
            . ' AND file_id IS NULL',
            array(
                ':client_id' => $clientId,
                ':user_id' => $userId
            )
        );
    }

    public function getFavoriteOwner($clientId, $userId, $ownerId)
    {
        return $this->fetchRow(
            'WHERE client_id = :client_id'
            . ' AND user_id = :user_id'
            . ' AND owner_id = :owner_id'
            . ' AND collection_id IS NULL'
            . ' AND file_id IS NULL'
            . ' LIMIT 1',
            array(
                ':client_id' => $clientId,
                ':user_id' => $userId,
                ':owner_id' => $ownerId
            )
        );
    }

    public function getFavoriteCollections($clientId, $userId)
    {
        return $this->fetchRowset(
            'WHERE client_id = :client_id'
            . ' AND user_id = :user_id'
            . ' AND collection_id IS NOT NULL'
            . ' AND file_id IS NULL',
            array(
                ':client_id' => $clientId,
                ':user_id' => $userId
            )
        );
    }

    public function getFavoriteCollection($clientId, $userId, $collectionId)
    {
        return $this->fetchRow(
            'WHERE client_id = :client_id'
            . ' AND user_id = :user_id'
            . ' AND collection_id = :collection_id'
            . ' AND file_id IS NULL'
            . ' LIMIT 1',
            array(
                ':client_id' => $clientId,
                ':user_id' => $userId,
                ':collection_id' => $collectionId
            )
        );
    }

    public function getFavoriteFiles($clientId, $userId)
    {
        return $this->fetchRowset(
            'WHERE client_id = :client_id'
            . ' AND user_id = :user_id'
            . ' AND file_id IS NOT NULL',
            array(
                ':client_id' => $clientId,
                ':user_id' => $userId
            )
        );
    }

    public function getFavoriteFile($clientId, $userId, $fileId)
    {
        return $this->fetchRow(
            'WHERE client_id = :client_id'
            . ' AND user_id = :user_id'
            . ' AND file_id = :file_id'
            . ' LIMIT 1',
            array(
                ':client_id' => $clientId,
                ':user_id' => $userId,
                ':file_id' => $fileId
            )
        );
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
