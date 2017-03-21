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

require_once 'models/table_profiles.php';
require_once 'models/table_collections.php';
require_once 'models/table_collections_downloaded.php';
require_once 'models/table_files.php';
require_once 'models/table_files_downloaded.php';
require_once 'models/table_favorites.php';
require_once 'models/table_media.php';
require_once 'models/table_media_artists.php';
require_once 'models/table_media_albums.php';
require_once 'models/table_media_played.php';

class ModelContainer
{

    protected $_db = null;

    protected $_config = array(
        'createTables' => true
    );

    public $profiles = null;

    public $collections = null;

    public $collections_downloaded = null;

    public $files = null;

    public $files_downloaded = null;

    public $favorites = null;

    public $media = null;

    public $media_artists = null;

    public $media_albums = null;

    public $media_played = null;

    public function __construct(Flooer_Db &$db, array $config = null)
    {
        $this->_db =& $db;
        if ($config) {
            $this->_config = $config + $this->_config;
        }

        if ($this->_config['createTables']) {
            $this->createTables();
        }

        $this->profiles = new table_profiles($this->_db);
        $this->collections = new table_collections($this->_db);
        $this->collections_downloaded = new table_collections_downloaded($this->_db);
        $this->files = new table_files($this->_db);
        $this->files_downloaded = new table_files_downloaded($this->_db);
        $this->favorites = new table_favorites($this->_db);
        $this->media = new table_media($this->_db);
        $this->media_artists = new table_media_artists($this->_db);
        $this->media_albums = new table_media_albums($this->_db);
        $this->media_played = new table_media_played($this->_db);
    }

    public function createTables()
    {
        $idDifinition = 'INTEGER NOT NULL PRIMARY KEY';
        $timestampDifinition = 'CHAR(19)';
        switch ($this->_db->getAttribute(Flooer_Db::ATTR_DRIVER_NAME)) {
            case 'mysql':
                //$idDifinition = 'INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY';
                $timestampDifinition = 'DATETIME';
                break;
            case 'pgsql':
                //$idDifinition = 'SERIAL PRIMARY KEY';
                $timestampDifinition = 'TIMESTAMP';
                break;
            case 'sqlite':
                //$idDifinition = 'INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT';
                $timestampDifinition = 'CHAR(19)';
                break;
        }

        if (!isset($this->_db->profiles)) {
            $this->_db->profiles = array(
                'id' => $idDifinition,
                'client_id' => 'INTEGER NOT NULL',
                'owner_id' => 'VARCHAR(255) NOT NULL',
                'name' => 'VARCHAR(255) NOT NULL',
                'email' => 'VARCHAR(255)',
                'homepage' => 'VARCHAR(255)',
                'image' => 'VARCHAR(255)',
                'description' => 'TEXT'
            );
        }

        if (!isset($this->_db->collections)) {
            $this->_db->collections = array(
                'id' => $idDifinition,
                'client_id' => 'INTEGER NOT NULL',
                'owner_id' => 'VARCHAR(255) NOT NULL',
                'name' => 'VARCHAR(255) NOT NULL',
                'files' => 'INTEGER NOT NULL',
                'size' => 'BIGINT UNSIGNED NOT NULL',
                'title' => 'VARCHAR(200)',
                'description' => 'TEXT',
                'category' => 'VARCHAR(64)',
                'tags' => 'VARCHAR(255)',
                'version' => 'VARCHAR(64)',
                'content_id' => 'VARCHAR(255)',
                'content_page' => 'VARCHAR(255)',
                'downloaded_timestamp' => $timestampDifinition,
                'downloaded_ip' => 'VARCHAR(39)',
                'downloaded_count' => 'INTEGER',
                'created_timestamp' => $timestampDifinition,
                'created_ip' => 'VARCHAR(39)',
                'updated_timestamp' => $timestampDifinition,
                'updated_ip' => 'VARCHAR(39)'
            );
        }

        if (!isset($this->_db->collections_downloaded)) {
            $this->_db->collections_downloaded = array(
                'id' => $idDifinition,
                'client_id' => 'INTEGER NOT NULL',
                'owner_id' => 'VARCHAR(255) NOT NULL',
                'collection_id' => 'INTEGER NOT NULL',
                'user_id' => 'VARCHAR(255)',
                'downloaded_timestamp' => $timestampDifinition,
                'downloaded_ip' => 'VARCHAR(39)'
            );
        }

        if (!isset($this->_db->files)) {
            $this->_db->files = array(
                'id' => $idDifinition,
                'active' => 'INTEGER(1) NOT NULL DEFAULT 1',
                'client_id' => 'INTEGER NOT NULL',
                'owner_id' => 'VARCHAR(255) NOT NULL',
                'collection_id' => 'INTEGER NOT NULL',
                'name' => 'VARCHAR(255) NOT NULL',
                'type' => 'VARCHAR(255) NOT NULL',
                'size' => 'BIGINT UNSIGNED NOT NULL',
                'title' => 'VARCHAR(200)',
                'description' => 'TEXT',
                'category' => 'VARCHAR(64)',
                'tags' => 'VARCHAR(255)',
                'version' => 'VARCHAR(64)',
                'content_id' => 'VARCHAR(255)',
                'content_page' => 'VARCHAR(255)',
                'downloaded_timestamp' => $timestampDifinition,
                'downloaded_ip' => 'VARCHAR(39)',
                'downloaded_count' => 'INTEGER',
                'created_timestamp' => $timestampDifinition,
                'created_ip' => 'VARCHAR(39)',
                'updated_timestamp' => $timestampDifinition,
                'updated_ip' => 'VARCHAR(39)'
            );
        }

        if (!isset($this->_db->files_downloaded)) {
            $this->_db->files_downloaded = array(
                'id' => $idDifinition,
                'client_id' => 'INTEGER NOT NULL',
                'owner_id' => 'VARCHAR(255) NOT NULL',
                'collection_id' => 'INTEGER NOT NULL',
                'file_id' => 'INTEGER NOT NULL',
                'user_id' => 'VARCHAR(255)',
                'downloaded_timestamp' => $timestampDifinition,
                'downloaded_ip' => 'VARCHAR(39)'
            );
        }

        if (!isset($this->_db->favorites)) {
            $this->_db->favorites = array(
                'id' => $idDifinition,
                'client_id' => 'INTEGER NOT NULL',
                'user_id' => 'VARCHAR(255) NOT NULL',
                'owner_id' => 'VARCHAR(255) NOT NULL',
                'collection_id' => 'INTEGER',
                'file_id' => 'INTEGER'
            );
        }

        if (!isset($this->_db->media)) {
            $this->_db->media = array(
                'id' => $idDifinition,
                'client_id' => 'INTEGER NOT NULL',
                'owner_id' => 'VARCHAR(255) NOT NULL',
                'collection_id' => 'INTEGER NOT NULL',
                'file_id' => 'INTEGER NOT NULL',
                'artist_id' => 'INTEGER NOT NULL',
                'album_id' => 'INTEGER NOT NULL',
                'title' => 'VARCHAR(255)',
                'genre' => 'VARCHAR(64)',
                'track' => 'VARCHAR(5)',
                'creationdate' => 'INTEGER(4)',
                'bitrate' => 'INTEGER',
                'playtime_seconds' => 'INTEGER',
                'playtime_string' => 'VARCHAR(8)',
                'played_timestamp' => $timestampDifinition,
                'played_ip' => 'VARCHAR(39)',
                'played_count' => 'INTEGER'
            );
        }

        if (!isset($this->_db->media_artists)) {
            $this->_db->media_artists = array(
                'id' => $idDifinition,
                'client_id' => 'INTEGER NOT NULL',
                'name' => 'VARCHAR(255) NOT NULL'
            );
        }

        if (!isset($this->_db->media_albums)) {
            $this->_db->media_albums = array(
                'id' => $idDifinition,
                'client_id' => 'INTEGER NOT NULL',
                'name' => 'VARCHAR(255) NOT NULL'
            );
        }

        if (!isset($this->_db->media_played)) {
            $this->_db->media_played = array(
                'id' => $idDifinition,
                'client_id' => 'INTEGER NOT NULL',
                'owner_id' => 'VARCHAR(255) NOT NULL',
                'collection_id' => 'INTEGER NOT NULL',
                'file_id' => 'INTEGER NOT NULL',
                'media_id' => 'INTEGER NOT NULL',
                'user_id' => 'VARCHAR(255)',
                'played_timestamp' => $timestampDifinition,
                'played_ip' => 'VARCHAR(39)'
            );
        }
    }

    public function getDb()
    {
        return $this->_db;
    }

}
