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

class table_collections extends BaseModel
{

    protected $_columns = '';

    protected $_join = '';

    public function __construct(&$db)
    {
        parent::__construct($db, $db->getTableConfig());
        $this->setName('collections');
        $this->setPrimaryInsert(true);

        $prefix = $this->getPrefix();

        $this->_columns = "{$prefix}collections.id AS id,"
            . "{$prefix}collections.active AS active,"
            . "{$prefix}collections.client_id AS client_id,"
            . "{$prefix}collections.owner_id AS owner_id,"
            . "{$prefix}profiles.id AS profile_id,"
            . "{$prefix}profiles.name AS profile_name,"
            . "{$prefix}collections.name AS name,"
            . "{$prefix}collections.files AS files,"
            . "{$prefix}collections.size AS size,"
            . "{$prefix}collections.title AS title,"
            . "{$prefix}collections.description AS description,"
            . "{$prefix}collections.category AS category,"
            . "{$prefix}collections.tags AS tags,"
            . "{$prefix}collections.version AS version,"
            . "{$prefix}collections.content_id AS content_id,"
            . "{$prefix}collections.content_page AS content_page,"
            . "{$prefix}collections.downloaded_timestamp AS downloaded_timestamp,"
            . "{$prefix}collections.downloaded_count AS downloaded_count,"
            . "{$prefix}collections.downloaded_count AS downloaded_timeperiod_count,"
            . "{$prefix}collections.created_timestamp AS created_timestamp,"
            . "{$prefix}collections.updated_timestamp AS updated_timestamp";

        $this->_join = "LEFT OUTER JOIN {$prefix}profiles"
            . " ON {$prefix}profiles.client_id = {$prefix}collections.client_id"
            . " AND {$prefix}profiles.owner_id = {$prefix}collections.owner_id";
    }

    public function __set($key, $value)
    {
        $value = $this->_convertArrayToObject($value);
        unset(
            $value->id,
            $value->created_timestamp,
            $value->created_ip,
            $value->updated_timestamp,
            $value->updated_ip
        );
        $value->updated_timestamp = $this->_getTimestamp();
        $value->updated_ip = $this->_getIp();
        if (!isset($this->$key)) {
            $value->downloaded_timestamp = $value->updated_timestamp;
            $value->downloaded_ip = $value->updated_ip;
            $value->downloaded_count = 0;
            $value->created_timestamp = $value->updated_timestamp;
            $value->created_ip = $value->updated_ip;
        }
        parent::__set($key, $value);
    }

    public function getCollections($status = 'active', $clientId = null, $ownerId = null, $category = null, $tags = null, $contentId = null, $search = null, $ids = null, array $favoriteIds = null, $downloadedTimeperiodBegin = null, $downloadedTimeperiodEnd = null, $sort = 'name', $perpage = 20, $page = 1)
    {
        $prefix = $this->getPrefix();
        $name = $this->getName();
        $columns = $this->getColumns();

        $statementOption = '';
        $where = array();
        $values = array();
        $order = "{$prefix}collections.name ASC";
        $offset = 0;

        if ($status != 'all') {
            $active = 1;
            if ($status == 'inactive') {
                $active = 0;
            }
            $where[] = "{$prefix}collections.active = :active";
            $values[':active'] = $active;
        }
        if ($clientId) {
            $where[] = "{$prefix}collections.client_id = :client_id";
            $values[':client_id'] = $clientId;
        }
        if ($ownerId) {
            $where[] = "{$prefix}collections.owner_id = :owner_id";
            $values[':owner_id'] = $ownerId;
        }
        if ($category !== null && $category !== '') {
            $where[] = "{$prefix}collections.category = :category";
            $values[':category'] = $category;
        }
        if ($tags !== null && $tags !== '') {
            foreach (explode(',', $tags) as $tag) {
                $tag = trim($tag);
                if ($tag) {
                    $where[] = "({$prefix}collections.tags = " . $this->getDb()->quote($tag)
                        . " OR {$prefix}collections.tags LIKE " . $this->getDb()->quote("$tag,%")
                        . " OR {$prefix}collections.tags LIKE " . $this->getDb()->quote("%,$tag,%")
                        . " OR {$prefix}collections.tags LIKE " . $this->getDb()->quote("%,$tag") . ')';
                }
            }
        }
        if ($contentId !== null && $contentId !== '') {
            $where[] = "{$prefix}collections.content_id = :content_id";
            $values[':content_id'] = $contentId;
        }
        if ($search) {
            $isSearchable = false;
            foreach (explode(' ', $search) as $keyword) {
                if ($keyword && strlen($keyword) > 2) {
                    $keyword = $this->getDb()->quote("%$keyword%");
                    $where[] = "({$prefix}collections.name LIKE $keyword"
                        . " OR {$prefix}collections.title LIKE $keyword"
                        . " OR {$prefix}collections.description LIKE $keyword)";
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
                $where[] = "{$prefix}collections.id IN (" . implode(',', $_ids) . ')';
            }
        }
        if (!empty($favoriteIds['ownerIds'])
            || !empty($favoriteIds['collectionIds'])
        ) {
            $where[] = $this->_convertFavoriteIdsToStatement(
                $favoriteIds,
                array(
                    'ownerId' => "{$prefix}collections.owner_id",
                    'collectionId' => "{$prefix}collections.id"
                )
            );
        }

        if ($where) {
            $statementOption = 'WHERE ' . implode(' AND ', $where);
        }

        if ($sort == 'newest') {
            $order = "{$prefix}collections.id DESC";
        }
        else if ($sort == 'recent') {
            $order = "{$prefix}collections.downloaded_timestamp DESC";
        }
        else if ($sort == 'frequent') {
            $order = "{$prefix}collections.downloaded_count DESC";
        }

        if ($page > 1) {
            $offset = ($page - 1) * $perpage;
        }

        $collections = null;
        $pagination = null;

        if ($downloadedTimeperiodBegin || $downloadedTimeperiodEnd) {
            $_downloadedTimeperiodBegin = $this->_getTimestamp(0);
            if ($downloadedTimeperiodBegin) {
                $_downloadedTimeperiodBegin = $downloadedTimeperiodBegin;
            }
            $_downloadedTimeperiodBegin = $this->getDb()->quote($_downloadedTimeperiodBegin);

            $_downloadedTimeperiodEnd = $this->_getTimestamp();
            if ($downloadedTimeperiodEnd) {
                $_downloadedTimeperiodEnd = $downloadedTimeperiodEnd;
            }
            $_downloadedTimeperiodEnd = $this->getDb()->quote($_downloadedTimeperiodEnd);

            $_from = '('
                . " SELECT {$prefix}collections_downloaded.collection_id AS collection_id,"
                . " COUNT({$prefix}collections_downloaded.collection_id) AS count"
                . " FROM {$prefix}collections_downloaded"
                . " WHERE {$prefix}collections_downloaded.downloaded_timestamp"
                . " BETWEEN {$_downloadedTimeperiodBegin} AND {$_downloadedTimeperiodEnd}"
                . " GROUP BY {$prefix}collections_downloaded.collection_id"
                . ') AS downloaded_timeperiod';

            $_join = "LEFT OUTER JOIN {$prefix}collections"
                . " ON {$prefix}collections.id = downloaded_timeperiod.collection_id"
                . ' ' . $this->_join;

            $_columns = str_replace(
                "{$prefix}collections.downloaded_count AS downloaded_timeperiod_count",
                'downloaded_timeperiod.count AS downloaded_timeperiod_count',
                $this->_columns
            );

            if ($sort == 'frequent') {
                $order = 'downloaded_timeperiod.count DESC';
            }

            $this->setPrefix('');
            $this->setName($_from);
            $this->setColumns($_columns);

            $collections = $this->fetchRowset(
                $_join . ' ' . $statementOption
                . " ORDER BY $order LIMIT $perpage OFFSET $offset",
                $values
            );

            $this->setPrefix($prefix);
            $this->setName($name);
            $this->setColumns($columns);

            if (!$collections) {
                return null;
            }

            $this->setPrefix('');
            $this->setName($_from);
            $this->setColumns($_columns);

            $pagination = Flooer_Utility_Pagination::paginate(
                $this->count($_join . ' ' . $statementOption, $values),
                $perpage,
                $page
            );

            $this->setPrefix($prefix);
            $this->setName($name);
            $this->setColumns($columns);
        }
        else {
            $this->setColumns($this->_columns);
            $collections = $this->fetchRowset(
                $this->_join . ' ' . $statementOption
                . " ORDER BY $order LIMIT $perpage OFFSET $offset",
                $values
            );
            $this->setColumns($columns);

            if (!$collections) {
                return null;
            }

            $this->setColumns($this->_columns);
            $pagination = Flooer_Utility_Pagination::paginate(
                $this->count($this->_join . ' ' . $statementOption, $values),
                $perpage,
                $page
            );
            $this->setColumns($columns);
        }

        return array(
            'collections' => $collections,
            'pagination' => $pagination
        );
    }

    public function getCollection($id)
    {
        $prefix = $this->getPrefix();
        $columns = $this->getColumns();

        $this->setColumns($this->_columns);
        $collection = $this->fetchRow(
            $this->_join
            . " WHERE {$prefix}collections.id = :id"
            . ' LIMIT 1',
            array(':id' => $id)
        );
        $this->setColumns($columns);

        if ($collection) {
            return $collection;
        }
        return null;
    }

    public function updateDownloadedStatus($id)
    {
        if (isset($this->$id)) {
            parent::__set($id, array(
                'downloaded_timestamp' => $this->_getTimestamp(),
                'downloaded_ip' => $this->_getIp(),
                'downloaded_count' => $this->$id->downloaded_count + 1
            ));
        }
    }

}
