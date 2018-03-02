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

class table_profiles extends BaseModel
{

    public function __construct(&$db)
    {
        parent::__construct($db, $db->getTableConfig());
        $this->setName('profiles');
        $this->setPrimaryInsert(true);
    }

    public function __set($key, $value)
    {
        $value = $this->_convertArrayToObject($value);
        unset($value->id);
        parent::__set($key, $value);
    }

    public function getProfiles($status = 'active', $clientId = null, $ownerId = null, $search = null, $ids = null, array $favoriteIds = null, $sort = 'name', $perpage = 20, $page = 1)
    {
        $statementOption = '';
        $where = array();
        $values = array();
        $order = 'name ASC';
        $offset = 0;

        if ($status != 'all') {
            $active = 1;
            if ($status == 'inactive') {
                $active = 0;
            }
            $where[] = 'active = :active';
            $values[':active'] = $active;
        }
        if ($clientId) {
            $where[] = 'client_id = :client_id';
            $values[':client_id'] = $clientId;
        }
        if ($ownerId) {
            $where[] = 'owner_id = :owner_id';
            $values[':owner_id'] = $ownerId;
        }
        if ($search) {
            $isSearchable = false;
            foreach (explode(' ', $search) as $keyword) {
                if ($keyword && strlen($keyword) > 2) {
                    $keyword = $this->getDb()->quote("%$keyword%");
                    $where[] = "(name LIKE $keyword"
                        . " OR description LIKE $keyword)";
                    $isSearchable = true;
                }
            }
            if (!$isSearchable) {
                return null;
            }
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
        if (!empty($favoriteIds['ownerIds'])) {
            $where[] = $this->_convertFavoriteIdsToStatement(
                $favoriteIds,
                array('ownerId' => 'owner_id')
            );
        }

        if ($where) {
            $statementOption = 'WHERE ' . implode(' AND ', $where);
        }

        if ($sort == 'newest') {
            $order = 'id DESC';
        }

        if ($page > 1) {
            $offset = ($page - 1) * $perpage;
        }

        $profiles = $this->fetchRowset(
            $statementOption
            . " ORDER BY $order LIMIT $perpage OFFSET $offset",
            $values
        );

        if (!$profiles) {
            return null;
        }

        $pagination = Flooer_Utility_Pagination::paginate(
            $this->count($statementOption, $values),
            $perpage,
            $page
        );

        return array(
            'profiles' => $profiles,
            'pagination' => $pagination
        );
    }

    public function getProfile($id)
    {
        return $this->fetchRow(
            'WHERE id = :id'
            . ' LIMIT 1',
            array(':id' => $id)
        );
    }

    public function getProfileByClientIdAndOwnerId($clientId, $ownerId)
    {
        return $this->fetchRow(
            'WHERE client_id = :client_id'
            . ' AND owner_id = :owner_id'
            . ' LIMIT 1',
            array(
                ':client_id' => $clientId,
                ':owner_id' => $ownerId
            )
        );
    }

}
