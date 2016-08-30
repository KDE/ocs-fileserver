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

class table_media extends BaseModel
{

    protected $_columns = '';

    protected $_join = '';

    public function __construct(&$db)
    {
        parent::__construct($db, $db->getTableConfig());
        $this->setName('media');
        $this->setPrimaryInsert(true);

        $prefix = $this->getPrefix();

        $this->_columns = "{$prefix}media.id AS id,"
            . "{$prefix}media.client_id AS client_id,"
            . "{$prefix}media.owner_id AS owner_id,"
            . "{$prefix}profiles.id AS profile_id,"
            . "{$prefix}profiles.name AS profile_name,"
            . "{$prefix}media.collection_id AS collection_id,"
            . "{$prefix}collections.title AS collection_title,"
            . "{$prefix}collections.category AS collection_category,"
            . "{$prefix}collections.tags AS collection_tags,"
            . "{$prefix}collections.version AS collection_version,"
            . "{$prefix}collections.content_id AS collection_content_id,"
            . "{$prefix}collections.content_page AS collection_content_page,"
            . "{$prefix}media.file_id AS file_id,"
            . "{$prefix}files.name AS file_name,"
            . "{$prefix}files.type AS file_type,"
            . "{$prefix}files.size AS file_size,"
            . "{$prefix}files.title AS file_title,"
            . "{$prefix}files.category AS file_category,"
            . "{$prefix}files.tags AS file_tags,"
            . "{$prefix}files.version AS file_version,"
            . "{$prefix}files.content_id AS file_content_id,"
            . "{$prefix}files.content_page AS file_content_page,"
            . "{$prefix}media.artist_id AS artist_id,"
            . "{$prefix}media_artists.name AS artist_name,"
            . "{$prefix}media.album_id AS album_id,"
            . "{$prefix}media_albums.name AS album_name,"
            . "{$prefix}media.title AS title,"
            . "{$prefix}media.genre AS genre,"
            . "{$prefix}media.track AS track,"
            . "{$prefix}media.creationdate AS creationdate,"
            . "{$prefix}media.bitrate AS bitrate,"
            . "{$prefix}media.playtime_seconds AS playtime_seconds,"
            . "{$prefix}media.playtime_string AS playtime_string,"
            . "{$prefix}media.played_timestamp AS played_timestamp,"
            . "{$prefix}media.played_count AS played_count,"
            . "{$prefix}media.played_count AS played_timeperiod_count";

        $this->_join = "LEFT OUTER JOIN {$prefix}profiles"
            . " ON {$prefix}profiles.client_id = {$prefix}media.client_id"
            . " AND {$prefix}profiles.owner_id = {$prefix}media.owner_id"
            . " LEFT OUTER JOIN {$prefix}collections"
            . " ON {$prefix}collections.id = {$prefix}media.collection_id"
            . " LEFT OUTER JOIN {$prefix}files"
            . " ON {$prefix}files.id = {$prefix}media.file_id"
            . " LEFT OUTER JOIN {$prefix}media_artists"
            . " ON {$prefix}media_artists.id = {$prefix}media.artist_id"
            . " LEFT OUTER JOIN {$prefix}media_albums"
            . " ON {$prefix}media_albums.id = {$prefix}media.album_id";
    }

    public function __set($key, $value)
    {
        $value = $this->_convertArrayToObject($value);
        unset($value->id);
        if (!isset($this->$key)) {
            $value->played_timestamp = $this->_getTimestamp();
            $value->played_ip = $this->_getIp();
            $value->played_count = 0;
        }
        parent::__set($key, $value);
    }

    public function getGenres($clientId = null, $ownerId = null, $collectionId = null, $collectionCategory = null, $collectionTags = null, $collectionContentId = null, $fileId = null, $fileTypes = null, $fileCategory = null, $fileTags = null, $fileContentId = null, $artistId = null, $albumId = null, $genre = null, $search = null, array $favoriteIds = null, $sort = 'name', $perpage = 20, $page = 1)
    {
        $prefix = $this->getPrefix();
        $columns = $this->getColumns();

        $_columns = "DISTINCT {$prefix}media.client_id AS client_id,"
            . "{$prefix}media.genre AS genre";

        $_join = "LEFT OUTER JOIN {$prefix}collections"
            . " ON {$prefix}collections.id = {$prefix}media.collection_id"
            . " LEFT OUTER JOIN {$prefix}files"
            . " ON {$prefix}files.id = {$prefix}media.file_id";

        $statementOption = '';
        $where = array();
        $values = array();
        $order = "{$prefix}media.genre ASC";
        $offset = 0;

        if ($clientId) {
            $where[] = "{$prefix}media.client_id = :client_id";
            $values[':client_id'] = $clientId;
        }
        if ($ownerId) {
            $where[] = "{$prefix}media.owner_id = :owner_id";
            $values[':owner_id'] = $ownerId;
        }
        if ($collectionId) {
            $where[] = "{$prefix}media.collection_id = :collection_id";
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
        if ($fileId) {
            $where[] = "{$prefix}media.file_id = :file_id";
            $values[':file_id'] = $fileId;
        }
        if ($fileTypes) {
            $_fileTypes = array();
            foreach (explode(',', $fileTypes) as $fileType) {
                $fileType = trim($fileType);
                if ($fileType) {
                    $_fileTypes[] = $this->getDb()->quote($fileType);
                }
            }
            if ($_fileTypes) {
                $where[] = "{$prefix}files.type IN (" . implode(',', $_fileTypes) . ')';
            }
        }
        if ($fileCategory !== null && $fileCategory !== '') {
            $where[] = "{$prefix}files.category = :file_category";
            $values[':file_category'] = $fileCategory;
        }
        if ($fileTags !== null && $fileTags !== '') {
            foreach (explode(',', $fileTags) as $tag) {
                $tag = trim($tag);
                if ($tag) {
                    $where[] = "({$prefix}files.tags = " . $this->getDb()->quote($tag)
                        . " OR {$prefix}files.tags LIKE " . $this->getDb()->quote("$tag,%")
                        . " OR {$prefix}files.tags LIKE " . $this->getDb()->quote("%,$tag,%")
                        . " OR {$prefix}files.tags LIKE " . $this->getDb()->quote("%,$tag") . ')';
                }
            }
        }
        if ($fileContentId !== null && $fileContentId !== '') {
            $where[] = "{$prefix}files.content_id = :file_content_id";
            $values[':file_content_id'] = $fileContentId;
        }
        if ($artistId) {
            $where[] = "{$prefix}media.artist_id = :artist_id";
            $values[':artist_id'] = $artistId;
        }
        if ($albumId) {
            $where[] = "{$prefix}media.album_id = :album_id";
            $values[':album_id'] = $albumId;
        }
        if ($genre) {
            $where[] = "{$prefix}media.genre = :genre";
            $values[':genre'] = $genre;
        }
        if ($search) {
            $isSearchable = false;
            $_genre = array();
            foreach (explode(' ', $search) as $keyword) {
                if ($keyword && strlen($keyword) > 2) {
                    $keyword = $this->getDb()->quote("%$keyword%");
                    $_genre[] = "{$prefix}media.genre LIKE $keyword";
                    $isSearchable = true;
                }
            }
            if (!$isSearchable) {
                return null;
            }
            $where[] = '(' . implode(' OR ', $_genre) . ')';
        }
        if (!empty($favoriteIds['ownerIds'])
            || !empty($favoriteIds['collectionIds'])
            || !empty($favoriteIds['fileIds'])
        ) {
            $where[] = $this->_convertFavoriteIdsToStatement(
                $favoriteIds,
                array(
                    'ownerId' => "{$prefix}media.owner_id",
                    'collectionId' => "{$prefix}media.collection_id",
                    'fileId' => "{$prefix}media.file_id"
                )
            );
        }

        if ($where) {
            $statementOption = 'WHERE ' . implode(' AND ', $where)
                . " AND {$prefix}media.genre IS NOT NULL";
        }
        else {
            $statementOption = "WHERE {$prefix}media.genre IS NOT NULL";
        }

        if ($sort == 'newest') {
            $order = "{$prefix}media.id DESC";
        }

        if ($page > 1) {
            $offset = ($page - 1) * $perpage;
        }

        $this->setColumns($_columns);
        $genres = $this->fetchRowset(
            $_join . ' ' . $statementOption
            . " ORDER BY $order LIMIT $perpage OFFSET $offset",
            $values
        );
        $this->setColumns($columns);

        if (!$genres) {
            return null;
        }

        $this->setColumns($_columns);
        $pagination = Flooer_Utility_Pagination::paginate(
            count((array) $this->fetchRowset($_join . ' ' . $statementOption, $values)),
            $perpage,
            $page
        );
        $this->setColumns($columns);

        return array(
            'genres' => $genres,
            'pagination' => $pagination
        );
    }

    public function getOwners($clientId = null, $ownerId = null, $collectionId = null, $collectionCategory = null, $collectionTags = null, $collectionContentId = null, $fileId = null, $fileTypes = null, $fileCategory = null, $fileTags = null, $fileContentId = null, $artistId = null, $albumId = null, $genre = null, $search = null, array $favoriteIds = null, $sort = 'name', $perpage = 20, $page = 1)
    {
        $prefix = $this->getPrefix();
        $columns = $this->getColumns();

        $_columns = "DISTINCT {$prefix}media.client_id AS client_id,"
            . "{$prefix}media.owner_id AS owner_id,"
            . "{$prefix}profiles.id AS profile_id,"
            . "{$prefix}profiles.name AS profile_name";

        $_join = "LEFT OUTER JOIN {$prefix}profiles"
            . " ON {$prefix}profiles.client_id = {$prefix}media.client_id"
            . " AND {$prefix}profiles.owner_id = {$prefix}media.owner_id"
            . " LEFT OUTER JOIN {$prefix}collections"
            . " ON {$prefix}collections.id = {$prefix}media.collection_id"
            . " LEFT OUTER JOIN {$prefix}files"
            . " ON {$prefix}files.id = {$prefix}media.file_id";

        $statementOption = '';
        $where = array();
        $values = array();
        $order = "{$prefix}profiles.name ASC";
        $offset = 0;

        if ($clientId) {
            $where[] = "{$prefix}media.client_id = :client_id";
            $values[':client_id'] = $clientId;
        }
        if ($ownerId) {
            $where[] = "{$prefix}media.owner_id = :owner_id";
            $values[':owner_id'] = $ownerId;
        }
        if ($collectionId) {
            $where[] = "{$prefix}media.collection_id = :collection_id";
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
        if ($fileId) {
            $where[] = "{$prefix}media.file_id = :file_id";
            $values[':file_id'] = $fileId;
        }
        if ($fileTypes) {
            $_fileTypes = array();
            foreach (explode(',', $fileTypes) as $fileType) {
                $fileType = trim($fileType);
                if ($fileType) {
                    $_fileTypes[] = $this->getDb()->quote($fileType);
                }
            }
            if ($_fileTypes) {
                $where[] = "{$prefix}files.type IN (" . implode(',', $_fileTypes) . ')';
            }
        }
        if ($fileCategory !== null && $fileCategory !== '') {
            $where[] = "{$prefix}files.category = :file_category";
            $values[':file_category'] = $fileCategory;
        }
        if ($fileTags !== null && $fileTags !== '') {
            foreach (explode(',', $fileTags) as $tag) {
                $tag = trim($tag);
                if ($tag) {
                    $where[] = "({$prefix}files.tags = " . $this->getDb()->quote($tag)
                        . " OR {$prefix}files.tags LIKE " . $this->getDb()->quote("$tag,%")
                        . " OR {$prefix}files.tags LIKE " . $this->getDb()->quote("%,$tag,%")
                        . " OR {$prefix}files.tags LIKE " . $this->getDb()->quote("%,$tag") . ')';
                }
            }
        }
        if ($fileContentId !== null && $fileContentId !== '') {
            $where[] = "{$prefix}files.content_id = :file_content_id";
            $values[':file_content_id'] = $fileContentId;
        }
        if ($artistId) {
            $where[] = "{$prefix}media.artist_id = :artist_id";
            $values[':artist_id'] = $artistId;
        }
        if ($albumId) {
            $where[] = "{$prefix}media.album_id = :album_id";
            $values[':album_id'] = $albumId;
        }
        if ($genre) {
            $where[] = "{$prefix}media.genre = :genre";
            $values[':genre'] = $genre;
        }
        if ($search) {
            $isSearchable = false;
            $_profile = array();
            foreach (explode(' ', $search) as $keyword) {
                if ($keyword && strlen($keyword) > 2) {
                    $keyword = $this->getDb()->quote("%$keyword%");
                    $_profile[] = "{$prefix}profiles.name LIKE $keyword";
                    $isSearchable = true;
                }
            }
            if (!$isSearchable) {
                return null;
            }
            $where[] = '(' . implode(' OR ', $_profile) . ')';
        }
        if (!empty($favoriteIds['ownerIds'])
            || !empty($favoriteIds['collectionIds'])
            || !empty($favoriteIds['fileIds'])
        ) {
            $where[] = $this->_convertFavoriteIdsToStatement(
                $favoriteIds,
                array(
                    'ownerId' => "{$prefix}media.owner_id",
                    'collectionId' => "{$prefix}media.collection_id",
                    'fileId' => "{$prefix}media.file_id"
                )
            );
        }

        if ($where) {
            $statementOption = 'WHERE ' . implode(' AND ', $where);
        }

        if ($sort == 'newest') {
            $order = "{$prefix}media.owner_id DESC";
        }

        if ($page > 1) {
            $offset = ($page - 1) * $perpage;
        }

        $this->setColumns($_columns);
        $owners = $this->fetchRowset(
            $_join . ' ' . $statementOption
            . " ORDER BY $order LIMIT $perpage OFFSET $offset",
            $values
        );
        $this->setColumns($columns);

        if (!$owners) {
            return null;
        }

        $this->setColumns($_columns);
        $pagination = Flooer_Utility_Pagination::paginate(
            count((array) $this->fetchRowset($_join . ' ' . $statementOption, $values)),
            $perpage,
            $page
        );
        $this->setColumns($columns);

        return array(
            'owners' => $owners,
            'pagination' => $pagination
        );
    }

    public function getCollections($clientId = null, $ownerId = null, $collectionId = null, $collectionCategory = null, $collectionTags = null, $collectionContentId = null, $fileId = null, $fileTypes = null, $fileCategory = null, $fileTags = null, $fileContentId = null, $artistId = null, $albumId = null, $genre = null, $search = null, array $favoriteIds = null, $sort = 'name', $perpage = 20, $page = 1)
    {
        $prefix = $this->getPrefix();
        $columns = $this->getColumns();

        $_columns = "DISTINCT {$prefix}media.client_id AS client_id,"
            . "{$prefix}media.owner_id AS owner_id,"
            . "{$prefix}profiles.id AS profile_id,"
            . "{$prefix}profiles.name AS profile_name,"
            . "{$prefix}media.collection_id AS collection_id,"
            . "{$prefix}collections.title AS collection_title";

        $_join = "LEFT OUTER JOIN {$prefix}profiles"
            . " ON {$prefix}profiles.client_id = {$prefix}media.client_id"
            . " AND {$prefix}profiles.owner_id = {$prefix}media.owner_id"
            . " LEFT OUTER JOIN {$prefix}collections"
            . " ON {$prefix}collections.id = {$prefix}media.collection_id"
            . " LEFT OUTER JOIN {$prefix}files"
            . " ON {$prefix}files.id = {$prefix}media.file_id";

        $statementOption = '';
        $where = array();
        $values = array();
        $order = "{$prefix}collections.title ASC";
        $offset = 0;

        if ($clientId) {
            $where[] = "{$prefix}media.client_id = :client_id";
            $values[':client_id'] = $clientId;
        }
        if ($ownerId) {
            $where[] = "{$prefix}media.owner_id = :owner_id";
            $values[':owner_id'] = $ownerId;
        }
        if ($collectionId) {
            $where[] = "{$prefix}media.collection_id = :collection_id";
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
        if ($fileId) {
            $where[] = "{$prefix}media.file_id = :file_id";
            $values[':file_id'] = $fileId;
        }
        if ($fileTypes) {
            $_fileTypes = array();
            foreach (explode(',', $fileTypes) as $fileType) {
                $fileType = trim($fileType);
                if ($fileType) {
                    $_fileTypes[] = $this->getDb()->quote($fileType);
                }
            }
            if ($_fileTypes) {
                $where[] = "{$prefix}files.type IN (" . implode(',', $_fileTypes) . ')';
            }
        }
        if ($fileCategory !== null && $fileCategory !== '') {
            $where[] = "{$prefix}files.category = :file_category";
            $values[':file_category'] = $fileCategory;
        }
        if ($fileTags !== null && $fileTags !== '') {
            foreach (explode(',', $fileTags) as $tag) {
                $tag = trim($tag);
                if ($tag) {
                    $where[] = "({$prefix}files.tags = " . $this->getDb()->quote($tag)
                        . " OR {$prefix}files.tags LIKE " . $this->getDb()->quote("$tag,%")
                        . " OR {$prefix}files.tags LIKE " . $this->getDb()->quote("%,$tag,%")
                        . " OR {$prefix}files.tags LIKE " . $this->getDb()->quote("%,$tag") . ')';
                }
            }
        }
        if ($fileContentId !== null && $fileContentId !== '') {
            $where[] = "{$prefix}files.content_id = :file_content_id";
            $values[':file_content_id'] = $fileContentId;
        }
        if ($artistId) {
            $where[] = "{$prefix}media.artist_id = :artist_id";
            $values[':artist_id'] = $artistId;
        }
        if ($albumId) {
            $where[] = "{$prefix}media.album_id = :album_id";
            $values[':album_id'] = $albumId;
        }
        if ($genre) {
            $where[] = "{$prefix}media.genre = :genre";
            $values[':genre'] = $genre;
        }
        if ($search) {
            $isSearchable = false;
            $_collection = array();
            foreach (explode(' ', $search) as $keyword) {
                if ($keyword && strlen($keyword) > 2) {
                    $keyword = $this->getDb()->quote("%$keyword%");
                    $_collection[] = "{$prefix}collections.title LIKE $keyword";
                    $isSearchable = true;
                }
            }
            if (!$isSearchable) {
                return null;
            }
            $where[] = '(' . implode(' OR ', $_collection) . ')';
        }
        if (!empty($favoriteIds['ownerIds'])
            || !empty($favoriteIds['collectionIds'])
            || !empty($favoriteIds['fileIds'])
        ) {
            $where[] = $this->_convertFavoriteIdsToStatement(
                $favoriteIds,
                array(
                    'ownerId' => "{$prefix}media.owner_id",
                    'collectionId' => "{$prefix}media.collection_id",
                    'fileId' => "{$prefix}media.file_id"
                )
            );
        }

        if ($where) {
            $statementOption = 'WHERE ' . implode(' AND ', $where);
        }

        if ($sort == 'newest') {
            $order = "{$prefix}media.collection_id DESC";
        }

        if ($page > 1) {
            $offset = ($page - 1) * $perpage;
        }

        $this->setColumns($_columns);
        $collections = $this->fetchRowset(
            $_join . ' ' . $statementOption
            . " ORDER BY $order LIMIT $perpage OFFSET $offset",
            $values
        );
        $this->setColumns($columns);

        if (!$collections) {
            return null;
        }

        $this->setColumns($_columns);
        $pagination = Flooer_Utility_Pagination::paginate(
            count((array) $this->fetchRowset($_join . ' ' . $statementOption, $values)),
            $perpage,
            $page
        );
        $this->setColumns($columns);

        return array(
            'collections' => $collections,
            'pagination' => $pagination
        );
    }

    public function getIndex($clientId = null, $ownerId = null, $collectionId = null, $collectionCategory = null, $collectionTags = null, $collectionContentId = null, $fileId = null, $fileTypes = null, $fileCategory = null, $fileTags = null, $fileContentId = null, $artistId = null, $albumId = null, $genre = null, $search = null, $ids = null, array $favoriteIds = null, $playedTimeperiodBegin = null, $playedTimeperiodEnd = null, $sort = 'name', $perpage = 20, $page = 1)
    {
        $prefix = $this->getPrefix();
        $name = $this->getName();
        $columns = $this->getColumns();

        $statementOption = '';
        $where = array();
        $values = array();
        $order = "{$prefix}media.title ASC";
        $offset = 0;

        if ($clientId) {
            $where[] = "{$prefix}media.client_id = :client_id";
            $values[':client_id'] = $clientId;
        }
        if ($ownerId) {
            $where[] = "{$prefix}media.owner_id = :owner_id";
            $values[':owner_id'] = $ownerId;
        }
        if ($collectionId) {
            $where[] = "{$prefix}media.collection_id = :collection_id";
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
        if ($fileId) {
            $where[] = "{$prefix}media.file_id = :file_id";
            $values[':file_id'] = $fileId;
        }
        if ($fileTypes) {
            $_fileTypes = array();
            foreach (explode(',', $fileTypes) as $fileType) {
                $fileType = trim($fileType);
                if ($fileType) {
                    $_fileTypes[] = $this->getDb()->quote($fileType);
                }
            }
            if ($_fileTypes) {
                $where[] = "{$prefix}files.type IN (" . implode(',', $_fileTypes) . ')';
            }
        }
        if ($fileCategory !== null && $fileCategory !== '') {
            $where[] = "{$prefix}files.category = :file_category";
            $values[':file_category'] = $fileCategory;
        }
        if ($fileTags !== null && $fileTags !== '') {
            foreach (explode(',', $fileTags) as $tag) {
                $tag = trim($tag);
                if ($tag) {
                    $where[] = "({$prefix}files.tags = " . $this->getDb()->quote($tag)
                        . " OR {$prefix}files.tags LIKE " . $this->getDb()->quote("$tag,%")
                        . " OR {$prefix}files.tags LIKE " . $this->getDb()->quote("%,$tag,%")
                        . " OR {$prefix}files.tags LIKE " . $this->getDb()->quote("%,$tag") . ')';
                }
            }
        }
        if ($fileContentId !== null && $fileContentId !== '') {
            $where[] = "{$prefix}files.content_id = :file_content_id";
            $values[':file_content_id'] = $fileContentId;
        }
        if ($artistId) {
            $where[] = "{$prefix}media.artist_id = :artist_id";
            $values[':artist_id'] = $artistId;
        }
        if ($albumId) {
            $where[] = "{$prefix}media.album_id = :album_id";
            $values[':album_id'] = $albumId;
        }
        if ($genre) {
            $where[] = "{$prefix}media.genre = :genre";
            $values[':genre'] = $genre;
        }
        if ($search) {
            $isSearchable = false;
            foreach (explode(' ', $search) as $keyword) {
                if ($keyword && strlen($keyword) > 2) {
                    $keyword = $this->getDb()->quote("%$keyword%");
                    $where[] = "({$prefix}profiles.name LIKE $keyword"
                        . " OR {$prefix}collections.title LIKE $keyword"
                        . " OR {$prefix}files.name LIKE $keyword"
                        . " OR {$prefix}files.title LIKE $keyword"
                        . " OR {$prefix}media_artists.name LIKE $keyword"
                        . " OR {$prefix}media_albums.name LIKE $keyword"
                        . " OR {$prefix}media.title LIKE $keyword)";
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
                $where[] = "{$prefix}media.id IN (" . implode(',', $_ids) . ')';
            }
        }
        if (!empty($favoriteIds['ownerIds'])
            || !empty($favoriteIds['collectionIds'])
            || !empty($favoriteIds['fileIds'])
        ) {
            $where[] = $this->_convertFavoriteIdsToStatement(
                $favoriteIds,
                array(
                    'ownerId' => "{$prefix}media.owner_id",
                    'collectionId' => "{$prefix}media.collection_id",
                    'fileId' => "{$prefix}media.file_id"
                )
            );
        }

        if ($where) {
            $statementOption = 'WHERE ' . implode(' AND ', $where);
        }

        if ($sort == 'newest') {
            $order = "{$prefix}media.id DESC";
        }
        else if ($sort == 'track') {
            $order = "{$prefix}media.track ASC";
        }
        else if ($sort == 'recent') {
            $order = "{$prefix}media.played_timestamp DESC";
        }
        else if ($sort == 'frequent') {
            $order = "{$prefix}media.played_count DESC";
        }

        if ($page > 1) {
            $offset = ($page - 1) * $perpage;
        }

        $index = null;
        $pagination = null;

        if ($playedTimeperiodBegin || $playedTimeperiodEnd) {
            $_playedTimeperiodBegin = $this->_getTimestamp(0);
            if ($playedTimeperiodBegin) {
                $_playedTimeperiodBegin = $playedTimeperiodBegin;
            }
            $_playedTimeperiodBegin = $this->getDb()->quote($_playedTimeperiodBegin);

            $_playedTimeperiodEnd = $this->_getTimestamp();
            if ($playedTimeperiodEnd) {
                $_playedTimeperiodEnd = $playedTimeperiodEnd;
            }
            $_playedTimeperiodEnd = $this->getDb()->quote($_playedTimeperiodEnd);

            $_from = '('
                . " SELECT {$prefix}media_played.media_id AS media_id,"
                . " COUNT({$prefix}media_played.media_id) AS count"
                . " FROM {$prefix}media_played"
                . " WHERE {$prefix}media_played.played_timestamp"
                . " BETWEEN {$_playedTimeperiodBegin} AND {$_playedTimeperiodEnd}"
                . " GROUP BY {$prefix}media_played.media_id"
                . ') AS played_timeperiod';

            $_join = "LEFT OUTER JOIN {$prefix}media"
                . " ON {$prefix}media.id = played_timeperiod.media_id"
                . ' ' . $this->_join;

            $_columns = str_replace(
                "{$prefix}media.played_count AS played_timeperiod_count",
                'played_timeperiod.count AS played_timeperiod_count',
                $this->_columns
            );

            if ($sort == 'frequent') {
                $order = 'played_timeperiod.count DESC';
            }

            $this->setPrefix('');
            $this->setName($_from);
            $this->setColumns($_columns);

            $index = $this->fetchRowset(
                $_join . ' ' . $statementOption
                . " ORDER BY $order LIMIT $perpage OFFSET $offset",
                $values
            );

            $this->setPrefix($prefix);
            $this->setName($name);
            $this->setColumns($columns);

            if (!$index) {
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
            $index = $this->fetchRowset(
                $this->_join . ' ' . $statementOption
                . " ORDER BY $order LIMIT $perpage OFFSET $offset",
                $values
            );
            $this->setColumns($columns);

            if (!$index) {
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
            'index' => $index,
            'pagination' => $pagination
        );
    }

    public function getMedia($id)
    {
        $prefix = $this->getPrefix();
        $columns = $this->getColumns();

        $this->setColumns($this->_columns);
        $media = $this->fetchRow(
            $this->_join
            . " WHERE {$prefix}media.id = :id"
            . ' LIMIT 1',
            array(':id' => $id)
        );
        $this->setColumns($columns);

        if ($media) {
            return $media;
        }
        return null;
    }

    public function getAlbumId($clientId, $artistName, $albumName)
    {
        $prefix = $this->getPrefix();
        $columns = $this->getColumns();

        $_columns = "{$prefix}media.album_id AS album_id";

        $_join = "LEFT OUTER JOIN {$prefix}media_artists"
            . " ON {$prefix}media_artists.id = {$prefix}media.artist_id"
            . " LEFT OUTER JOIN {$prefix}media_albums"
            . " ON {$prefix}media_albums.id = {$prefix}media.album_id";

        $this->setColumns($_columns);
        $result = $this->fetchRow(
            $_join
            . " WHERE {$prefix}media.client_id = :client_id"
            . " AND {$prefix}media_artists.name = :artist_name"
            . " AND {$prefix}media_albums.name = :album_name"
            . " LIMIT 1",
            array(
                ':client_id' => $clientId,
                ':artist_name' => $artistName,
                ':album_name' => $albumName
            )
        );
        $this->setColumns($columns);

        if ($result) {
            return $result->album_id;
        }
        return null;
    }

    public function updatePlayedStatus($id)
    {
        if (isset($this->$id)) {
            parent::__set($id, array(
                'played_timestamp' => $this->_getTimestamp(),
                'played_ip' => $this->_getIp(),
                'played_count' => $this->$id->played_count + 1
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

    public function deleteByFileId($fileId)
    {
        $primary = $this->getPrimary();
        $this->setPrimary('file_id');
        unset($this->$fileId);
        $this->setPrimary($primary);
    }

}
