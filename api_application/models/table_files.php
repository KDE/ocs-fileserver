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

class table_files extends BaseModel
{

    protected $_columns = '';

    protected $_join = '';

    public function __construct(&$db)
    {
        parent::__construct($db, $db->getTableConfig());
        $this->setName('files');
        $this->setPrimaryInsert(true);

        $prefix = $this->getPrefix();

        $this->_columns = "{$prefix}files.id AS id,"
            . "{$prefix}files.active AS active,"
            . "{$prefix}files.client_id AS client_id,"
            . "{$prefix}files.owner_id AS owner_id,"
            . "{$prefix}profiles.id AS profile_id,"
            . "{$prefix}profiles.name AS profile_name,"
            . "{$prefix}files.collection_id AS collection_id,"
            . "{$prefix}collections.title AS collection_title,"
            . "{$prefix}collections.category AS collection_category,"
            . "{$prefix}collections.tags AS collection_tags,"
            . "{$prefix}collections.version AS collection_version,"
            . "{$prefix}collections.content_id AS collection_content_id,"
            . "{$prefix}collections.content_page AS collection_content_page,"
            . "{$prefix}files.name AS name,"
            . "{$prefix}files.type AS type,"
            . "{$prefix}files.size AS size,"
            . "{$prefix}files.title AS title,"
            . "{$prefix}files.description AS description,"
            . "{$prefix}files.category AS category,"
            . "{$prefix}files.tags AS tags,"
            . "{$prefix}files.version AS version,"
            . "{$prefix}files.content_id AS content_id,"
            . "{$prefix}files.content_page AS content_page,"
            . "{$prefix}files.downloaded_timestamp AS downloaded_timestamp,"
            . "{$prefix}files.downloaded_count AS downloaded_count,"
            . "{$prefix}files.downloaded_count AS downloaded_timeperiod_count,"
            . "{$prefix}files.created_timestamp AS created_timestamp,"
            . "{$prefix}files.updated_timestamp AS updated_timestamp";

        $this->_join = "LEFT OUTER JOIN {$prefix}profiles"
            . " ON {$prefix}profiles.client_id = {$prefix}files.client_id"
            . " AND {$prefix}profiles.owner_id = {$prefix}files.owner_id"
            . " LEFT OUTER JOIN {$prefix}collections"
            . " ON {$prefix}collections.id = {$prefix}files.collection_id";
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

    public function getFiles($status = 'active', $clientId = null, $ownerId = null, $collectionId = null, $collectionCategory = null, $collectionTags = null, $collectionContentId = null, $types = null, $category = null, $tags = null, $contentId = null, $search = null, $ids = null, array $favoriteIds = null, $downloadedTimeperiodBegin = null, $downloadedTimeperiodEnd = null, $sort = 'name', $perpage = 20, $page = 1)
    {
        $prefix = $this->getPrefix();
        $name = $this->getName();
        $columns = $this->getColumns();

        $statementOption = '';
        $where = array();
        $values = array();
        $order = "{$prefix}files.name ASC";
        $offset = 0;

        if ($status != 'all') {
            $active = 1;
            if ($status == 'inactive') {
                $active = 0;
            }
            $where[] = "{$prefix}files.active = :active";
            $values[':active'] = $active;
        }
        if ($clientId) {
            $where[] = "{$prefix}files.client_id = :client_id";
            $values[':client_id'] = $clientId;
        }
        if ($ownerId) {
            $where[] = "{$prefix}files.owner_id = :owner_id";
            $values[':owner_id'] = $ownerId;
        }
        if ($collectionId) {
            $where[] = "{$prefix}files.collection_id = :collection_id";
            $values[':collection_id'] = $collectionId;
        }
        if ($collectionCategory !== null && $collectionCategory !== '') {
            $where[] = "{$prefix}collections.category = :collection_category";
            $values[':collection_category'] = $collectionCategory;
        }
        if ($collectionTags !== null && $collectionTags !== '') {
            foreach (explode(',', $collectionTags) as $tag) {
                $tag = trim($tag);
                if ($tag) {
                    $where[] = "({$prefix}collections.tags = " . $this->getDb()->quote($tag)
                        . " OR {$prefix}collections.tags LIKE " . $this->getDb()->quote("$tag,%")
                        . " OR {$prefix}collections.tags LIKE " . $this->getDb()->quote("%,$tag,%")
                        . " OR {$prefix}collections.tags LIKE " . $this->getDb()->quote("%,$tag") . ')';
                }
            }
        }
        if ($collectionContentId !== null && $collectionContentId !== '') {
            $where[] = "{$prefix}collections.content_id = :collection_content_id";
            $values[':collection_content_id'] = $collectionContentId;
        }
        if ($types) {
            $_types = array();
            foreach (explode(',', $types) as $type) {
                $type = trim($type);
                if ($type) {
                    $_types[] = $this->getDb()->quote($type);
                }
            }
            if ($_types) {
                $where[] = "{$prefix}files.type IN (" . implode(',', $_types) . ')';
            }
        }
        if ($category !== null && $category !== '') {
            $where[] = "{$prefix}files.category = :category";
            $values[':category'] = $category;
        }
        if ($tags !== null && $tags !== '') {
            foreach (explode(',', $tags) as $tag) {
                $tag = trim($tag);
                if ($tag) {
                    $where[] = "({$prefix}files.tags = " . $this->getDb()->quote($tag)
                        . " OR {$prefix}files.tags LIKE " . $this->getDb()->quote("$tag,%")
                        . " OR {$prefix}files.tags LIKE " . $this->getDb()->quote("%,$tag,%")
                        . " OR {$prefix}files.tags LIKE " . $this->getDb()->quote("%,$tag") . ')';
                }
            }
        }
        if ($contentId !== null && $contentId !== '') {
            $where[] = "{$prefix}files.content_id = :content_id";
            $values[':content_id'] = $contentId;
        }
        if ($search) {
            $isSearchable = false;
            foreach (explode(' ', $search) as $keyword) {
                if ($keyword && strlen($keyword) > 2) {
                    $keyword = $this->getDb()->quote("%$keyword%");
                    $where[] = "({$prefix}files.name LIKE $keyword"
                        . " OR {$prefix}files.title LIKE $keyword"
                        . " OR {$prefix}files.description LIKE $keyword)";
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
                $where[] = "{$prefix}files.id IN (" . implode(',', $_ids) . ')';
            }
        }
        if (!empty($favoriteIds['ownerIds'])
            || !empty($favoriteIds['collectionIds'])
            || !empty($favoriteIds['fileIds'])
        ) {
            $where[] = $this->_convertFavoriteIdsToStatement(
                $favoriteIds,
                array(
                    'ownerId' => "{$prefix}files.owner_id",
                    'collectionId' => "{$prefix}files.collection_id",
                    'fileId' => "{$prefix}files.id"
                )
            );
        }

        if ($where) {
            $statementOption = 'WHERE ' . implode(' AND ', $where);
        }

        if ($sort == 'newest') {
            $order = "{$prefix}files.id DESC";
        }
        else if ($sort == 'recent') {
            $order = "{$prefix}files.downloaded_timestamp DESC";
        }
        else if ($sort == 'frequent') {
            $order = "{$prefix}files.downloaded_count DESC";
        }

        if ($page > 1) {
            $offset = ($page - 1) * $perpage;
        }

        $files = null;
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
                . " SELECT {$prefix}files_downloaded.file_id AS file_id,"
                . " COUNT({$prefix}files_downloaded.file_id) AS count"
                . " FROM {$prefix}files_downloaded"
                . " WHERE {$prefix}files_downloaded.downloaded_timestamp"
                . " BETWEEN {$_downloadedTimeperiodBegin} AND {$_downloadedTimeperiodEnd}"
                . " GROUP BY {$prefix}files_downloaded.file_id"
                . ') AS downloaded_timeperiod';

            $_join = "LEFT OUTER JOIN {$prefix}files"
                . " ON {$prefix}files.id = downloaded_timeperiod.file_id"
                . ' ' . $this->_join;

            $_columns = str_replace(
                "{$prefix}files.downloaded_count AS downloaded_timeperiod_count",
                'downloaded_timeperiod.count AS downloaded_timeperiod_count',
                $this->_columns
            );

            if ($sort == 'frequent') {
                $order = 'downloaded_timeperiod.count DESC';
            }

            $this->setPrefix('');
            $this->setName($_from);
            $this->setColumns($_columns);

            $files = $this->fetchRowset(
                $_join . ' ' . $statementOption
                . " ORDER BY $order LIMIT $perpage OFFSET $offset",
                $values
            );

            $this->setPrefix($prefix);
            $this->setName($name);
            $this->setColumns($columns);

            if (!$files) {
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
            $files = $this->fetchRowset(
                $this->_join . ' ' . $statementOption
                . " ORDER BY $order LIMIT $perpage OFFSET $offset",
                $values
            );
            $this->setColumns($columns);

            if (!$files) {
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
            'files' => $files,
            'pagination' => $pagination
        );
    }

    public function getFile($id)
    {
        $prefix = $this->getPrefix();
        $columns = $this->getColumns();

        $this->setColumns($this->_columns);
        $file = $this->fetchRow(
            $this->_join
            . " WHERE {$prefix}files.id = :id"
            . ' LIMIT 1',
            array(':id' => $id)
        );
        $this->setColumns($columns);

        if ($file) {
            return $file;
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

    public function deleteByCollectionId($collectionId)
    {
        $primary = $this->getPrimary();
        $this->setPrimary('collection_id');
        unset($this->$collectionId);
        $this->setPrimary($primary);
    }

}
