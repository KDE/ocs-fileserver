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

class Files extends BaseController
{

    public function getIndex()
    {
        $status = 'active';
        $clientId = null;
        $ownerId = null;
        $collectionId = null;
        $collectionCategory = null;
        $collectionTags = null; // Comma-separated list
        $collectionContentId = null;
        $types = null;  // Comma-separated list
        $category = null;
        $tags = null; // Comma-separated list
        $contentId = null;
        $search = null; // 3 or more strings
        $ids = null; // Comma-separated list
        $favoriteIds = array();
        $downloadedTimeperiodBegin = null; // Datetime format
        $downloadedTimeperiodEnd = null; // Datetime format
        $sort = 'name';
        $perpage = $this->appConfig->general['perpage'];
        $page = 1;

        if (!empty($this->request->status)) {
            $status = $this->request->status;
        }
        if (!empty($this->request->client_id)) {
            $clientId = $this->request->client_id;
        }
        if (!empty($this->request->owner_id)) {
            $ownerId = $this->request->owner_id;
        }
        if (!empty($this->request->collection_id)) {
            $collectionId = $this->request->collection_id;
        }
        if (isset($this->request->collection_category)) {
            $collectionCategory = $this->request->collection_category;
        }
        if (isset($this->request->collection_tags)) {
            $collectionTags = $this->request->collection_tags;
        }
        if (isset($this->request->collection_content_id)) {
            $collectionContentId = $this->request->collection_content_id;
        }
        if (!empty($this->request->types)) {
            $types = $this->request->types;
        }
        if (isset($this->request->category)) {
            $category = $this->request->category;
        }
        if (isset($this->request->tags)) {
            $tags = $this->request->tags;
        }
        if (isset($this->request->content_id)) {
            $contentId = $this->request->content_id;
        }
        if (!empty($this->request->search)) {
            $search = $this->request->search;
        }
        if (!empty($this->request->ids)) {
            $ids = $this->request->ids;
        }
        if (!empty($this->request->client_id)
            && !empty($this->request->favoritesby)
        ) {
            $favoriteIds = $this->_getFavoriteIds(
                $this->request->client_id,
                $this->request->favoritesby
            );
            if (!$favoriteIds) {
                $this->response->setStatus(404);
                throw new Flooer_Exception('Not found', LOG_NOTICE);
            }
        }
        if (!empty($this->request->downloaded_timeperiod_begin)) {
            $downloadedTimeperiodBegin = $this->request->downloaded_timeperiod_begin;
        }
        if (!empty($this->request->downloaded_timeperiod_end)) {
            $downloadedTimeperiodEnd = $this->request->downloaded_timeperiod_end;
        }
        if (!empty($this->request->sort)) {
            $sort = $this->request->sort;
        }
        if (!empty($this->request->perpage)
            && $this->_isValidPerpageNumber($this->request->perpage)
        ) {
            $perpage = $this->request->perpage;
        }
        if (!empty($this->request->page)
            && $this->_isValidPageNumber($this->request->page)
        ) {
            $page = $this->request->page;
        }

        $files = $this->models->files->getFiles(
            $status,
            $clientId,
            $ownerId,
            $collectionId,
            $collectionCategory,
            $collectionTags,
            $collectionContentId,
            $types,
            $category,
            $tags,
            $contentId,
            $search,
            $ids,
            $favoriteIds,
            $downloadedTimeperiodBegin,
            $downloadedTimeperiodEnd,
            $sort,
            $perpage,
            $page
        );

        if (!$files) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }

        $this->_setResponseContent('success', $files);
    }

    public function getFile()
    {
        $id = null;

        if (!empty($this->request->id)) {
            $id = $this->request->id;
        }

        $file = $this->models->files->getFile($id);

        if (!$file) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }

        $this->_setResponseContent(
            'success',
            array('file' => $file)
        );
    }

    public function postFile()
    {
        if (!$this->_isAllowedAccess()) {
            $this->response->setStatus(403);
            throw new Flooer_Exception('Forbidden', LOG_NOTICE);
        }

        $id = null; // Auto generated
        $active = 1;
        $clientId = null;
        $ownerId = null;
        $collectionId = null;
        $name = null; // Auto generated
        $type = null; // Auto detect
        $size = null; // Auto detect
        $title = null; // Name as default
        $description = null;
        $category = null;
        $tags = null; // Comma-separated list
        $version = null;
        $contentId = null;
        $contentPage = null;

        $downloadedCount = 0; // for hive files importing (Deprecated)

        if (!empty($this->request->client_id)) {
            $clientId = $this->request->client_id;
        }
        if (!empty($this->request->owner_id)) {
            $ownerId = $this->request->owner_id;
        }
        if (!empty($this->request->collection_id)) {
            $collectionId = $this->request->collection_id;
        }
        if (isset($this->request->tags)) {
            $tags = mb_substr(strip_tags($this->request->tags), 0, 255);
        }
        if (isset($_FILES['file'])) {
            if (!empty($_FILES['file']['name'])) {
                $name = mb_substr(strip_tags(basename($_FILES['file']['name'])), 0, 200);
            }
            if (!empty($_FILES['file']['tmp_name'])) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $type = $finfo->file($_FILES['file']['tmp_name']);
                if (!$type) {
                    $type = 'application/octet-stream';
                }
            }
            if (!empty($_FILES['file']['size'])) {
                $size = $_FILES['file']['size'];
            }
        }
        // for hive files importing (Deprecated) ----------
        else if (isset($this->request->local_file_path)) {
            if (!empty($this->request->local_file_path)) {
                $name = mb_substr(strip_tags(basename($this->request->local_file_path)), 0, 200);

                #if this is a external link?
                if($name == 'empty' && str_word_count($tags, 0, 'link##')>0) {
                    $type = null;
                    $size = null;
                    $link = null;
                    $tagArray = explode(",", $tags);
                    foreach ($tagArray as $tag) {
                        $tag = trim($tag);
                        if (strpos($tag, 'link##') === 0) {
                            $link = urldecode(str_replace('link##', '', $tag));
                            $size = $this->_remoteFilesize($link);
                            $type = $this->_mimeContentType($link);
                        }
                    }
                } else {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $type = $finfo->file($this->request->local_file_path);
                    if (!$type) {
                        $type = 'application/octet-stream';
                    }
                    $size = filesize($this->request->local_file_path);
                }
            }
            if (!empty($this->request->local_file_name)) {
                $name = mb_substr(strip_tags(basename($this->request->local_file_name)), 0, 200);
            }
        }
        // ------------------------------------------------
        if (!empty($this->request->title)) {
            $title = mb_substr(strip_tags($this->request->title), 0, 200);
        }
        if (isset($this->request->description)) {
            $description = strip_tags($this->request->description);
        }
        if (isset($this->request->category)) {
            $category = mb_substr(strip_tags($this->request->category), 0, 64);
        }
        if (isset($this->request->version)) {
            $version = mb_substr(strip_tags($this->request->version), 0, 64);
        }
        if (isset($this->request->content_id)) {
            $contentId = $this->request->content_id;
        }
        if (!empty($this->request->content_page)) {
            $contentPage = $this->request->content_page;
        }
        // for hive files importing (Deprecated) ----------
        if (!empty($this->request->downloaded_count)) {
            $downloadedCount = intval($this->request->downloaded_count);
        }
        // ------------------------------------------------

        $errors = array();
        if (!$clientId) {
            $errors['client_id'] = 'Required';
        }
        if (!$ownerId) {
            $errors['owner_id'] = 'Required';
        }
        /*
        if (!isset($_FILES['file'])) {
            $errors['file'] = 'Required';
        }
        else if (!empty($_FILES['file']['error'])) { // 0 = UPLOAD_ERR_OK
            $errors['file'] = $_FILES['file']['error'];
        }
        */
        // for hive files importing (Deprecated) ----------
        if (!isset($_FILES['file']) && !isset($this->request->local_file_path)) {
            $errors['file'] = 'Required';
        }
        if (isset($_FILES['file']) && !empty($_FILES['file']['error'])) { // 0 = UPLOAD_ERR_OK
            $errors['file'] = $_FILES['file']['error'];
        }
        // ------------------------------------------------

        if ($errors) {
            $this->response->setStatus(400);
            $this->_setResponseContent(
                'error',
                array(
                    'message' => 'Validation error',
                    'errors' => $errors
                )
            );
            return;
        }

        // Get ID3 tags
        // NOTE: getid3 may not work for a files in a network storage.
        $id3Tags = null;
        if (strpos($type, 'audio/') !== false
            || strpos($type, 'video/') !== false
            || strpos($type, 'application/ogg') !== false
        ) {
            require_once 'getid3/getid3.php';
            $getID3 = new getID3();
            $id3Tags = $getID3->analyze($_FILES['file']['tmp_name']);
            getid3_lib::CopyTagsToComments($id3Tags);
        }

        $collectionName = null;
        $collectionData = array();
        if ($collectionId) {
            // Get specified collection
            $collection = $this->models->collections->$collectionId;
            if (!$collection
                || $collection->client_id != $clientId
                || $collection->owner_id != $ownerId
            ) {
                $this->response->setStatus(403);
                throw new Flooer_Exception('Forbidden', LOG_NOTICE);
            }
            $collectionName = $collection->name;
            $collectionData = array(
                'files' => $collection->files + 1,
                'size' => $collection->size + $size
            );
        }
        else {
            // Prepare new collection
            $collectionId = $this->models->collections->generateId();
            $collectionName = $collectionId;
            $collectionTitle = $collectionName;
            $collectionDescription = null;
            $collectionCategory = null;
            $collectionTags = null;
            $collectionVersion = null;
            $collectionContentId = null;
            $collectionContentPage = null;
            if (!is_dir($this->appConfig->general['filesDir'] . '/' . $collectionName)
                && !mkdir($this->appConfig->general['filesDir'] . '/' . $collectionName)
            ) {
                $this->response->setStatus(500);
                throw new Flooer_Exception('Failed to create collection', LOG_ALERT);
            }
            $collectionData = array(
                'client_id' => $clientId,
                'owner_id' => $ownerId,
                'name' => $collectionName,
                'files' => 1,
                'size' => $size,
                'title' => $collectionTitle,
                'description' => $collectionDescription,
                'category' => $collectionCategory,
                'tags' => $collectionTags,
                'version' => $collectionVersion,
                'content_id' => $collectionContentId,
                'content_page' => $collectionContentPage
            );
        }

        $id = $this->models->files->generateId();
        if (is_file($this->appConfig->general['filesDir'] . '/' . $collectionName . '/' . $name)) {
            if (preg_match("/(.+)(\.[^.]+)$/", $name, $matches)) {
                //$name = $matches[1] . '-' . $id . $matches[2];
                $name = $id . '-' . $matches[1] . $matches[2];
            }
            else {
                $name = $id . '-' . $name;
            }
        }
        if (!$title) {
            $title = $name;
        }

        if (isset($_FILES['file'])) {
            if (!move_uploaded_file(
                $_FILES['file']['tmp_name'],
                $this->appConfig->general['filesDir'] . '/' . $collectionName . '/' . $name
            )) {
                $this->response->setStatus(500);
                throw new Flooer_Exception('Failed to save the file', LOG_ALERT);
            }
        }
        // for hive files importing (Deprecated) ----------
        else if (isset($this->request->local_file_path)) {
            if (!copy(
                $this->request->local_file_path,
                $this->appConfig->general['filesDir'] . '/' . $collectionName . '/' . $name
            )) {
                $this->response->setStatus(500);
                throw new Flooer_Exception('Failed to save the file', LOG_ALERT);
            }
        }
        // ------------------------------------------------

        $this->models->collections->$collectionId = $collectionData;

        $this->models->files->$id = array(
            'active' => $active,
            'client_id' => $clientId,
            'owner_id' => $ownerId,
            'collection_id' => $collectionId,
            'name' => $name,
            'type' => $type,
            'size' => $size,
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'tags' => $tags,
            'version' => $version,
            'content_id' => $contentId,
            'content_page' => $contentPage,
            'downloaded_count' => $downloadedCount // for hive files importing (Deprecated)
        );

        // Delete old torrent file
        $torrent = $this->appConfig->general['torrentsDir'] . '/' . $collectionName . '.torrent';
        if (is_file($torrent)) {
            unlink($torrent);
        }
        if (is_file($torrent . '.added')) {
            unlink($torrent . '.added');
        }

        // Save the media information
        if ($id3Tags) {
            // Get artist id or add new one
            $artistName = 'Unknown';
            if (isset($id3Tags['comments']['artist'][0])
                && $id3Tags['comments']['artist'][0] != ''
            ) {
                $artistName = mb_substr(strip_tags($id3Tags['comments']['artist'][0]), 0, 255);
            }
            $artistId = $this->models->media_artists->getId($clientId, $artistName);
            if (!$artistId) {
                $artistId = $this->models->media_artists->generateId();
                $this->models->media_artists->$artistId = array(
                    'client_id' => $clientId,
                    'name' => $artistName
                );
            }

            // Get album id or add new one
            $albumName = 'Unknown';
            if (isset($id3Tags['comments']['album'][0])
                && $id3Tags['comments']['album'][0] != ''
            ) {
                $albumName = mb_substr(strip_tags($id3Tags['comments']['album'][0]), 0, 255);
            }
            $albumId = $this->models->media->getAlbumId($clientId, $artistName, $albumName);
            if (!$albumId) {
                $albumId = $this->models->media_albums->generateId();
                $this->models->media_albums->$albumId = array(
                    'client_id' => $clientId,
                    'name' => $albumName
                );
            }

            // Set the media information
            $mediaData = array(
                'client_id' => $clientId,
                'owner_id' => $ownerId,
                'collection_id' => $collectionId,
                'file_id' => $id,
                'artist_id' => $artistId,
                'album_id' => $albumId,
                'title' => $name,
                'genre' => null,
                'track' => null,
                'creationdate' => null,
                'bitrate' => 0,
                'playtime_seconds' => 0,
                'playtime_string' => 0
            );
            if (isset($id3Tags['comments']['title'][0])
                && $id3Tags['comments']['title'][0] != ''
            ) {
                $mediaData['title'] = mb_substr(strip_tags($id3Tags['comments']['title'][0]), 0, 255);
            }
            if (!empty($id3Tags['comments']['genre'][0])) {
                $mediaData['genre'] = mb_substr(strip_tags($id3Tags['comments']['genre'][0]), 0, 64);
            }
            if (!empty($id3Tags['comments']['track_number'][0])) {
                $mediaData['track'] = mb_substr(strip_tags($id3Tags['comments']['track_number'][0]), 0, 5);
            }
            if (!empty($id3Tags['comments']['creationdate'][0])) {
                $mediaData['creationdate'] = mb_substr(strip_tags($id3Tags['comments']['creationdate'][0]), 0, 4);
            }
            if (!empty($id3Tags['bitrate'])) {
                $mediaData['bitrate'] = mb_substr(strip_tags($id3Tags['bitrate']), 0, 11);
            }
            if (!empty($id3Tags['playtime_seconds'])) {
                $mediaData['playtime_seconds'] = mb_substr(strip_tags($id3Tags['playtime_seconds']), 0, 11);
            }
            if (!empty($id3Tags['playtime_string'])) {
                $mediaData['playtime_string'] = mb_substr(strip_tags($id3Tags['playtime_string']), 0, 8);
            }

            $mediaId = $this->models->media->generateId();
            $this->models->media->$mediaId = $mediaData;

            // Save the album cover
            if (!empty($id3Tags['comments']['picture'][0]['data'])) {
                $image = imagecreatefromstring($id3Tags['comments']['picture'][0]['data']);
                if ($image !== false) {
                    imagejpeg($image, $this->appConfig->general['thumbnailsDir'] . '/album_' . $albumId . '.jpg', 75);
                    imagedestroy($image);
                }
            }
        }

        $file = $this->models->files->getFile($id);

        $this->_setResponseContent(
            'success',
            array('file' => $file)
        );
    }

    public function putFile()
    {
        if (!$this->_isAllowedAccess()) {
            $this->response->setStatus(403);
            throw new Flooer_Exception('Forbidden', LOG_NOTICE);
        }

        $id = null;
        $title = null;
        $description = null;
        $category = null;
        $tags = null; // Comma-separated list
        $version = null;
        $contentId = null;
        $contentPage = null;

        if (!empty($this->request->id)) {
            $id = $this->request->id;
        }
        if (!empty($this->request->title)) {
            $title = mb_substr(strip_tags($this->request->title), 0, 200);
        }
        if (isset($this->request->description)) {
            $description = strip_tags($this->request->description);
        }
        if (isset($this->request->category)) {
            $category = mb_substr(strip_tags($this->request->category), 0, 64);
        }
        if (isset($this->request->tags)) {
            $tags = mb_substr(strip_tags($this->request->tags), 0, 255);
        }
        if (isset($this->request->version)) {
            $version = mb_substr(strip_tags($this->request->version), 0, 64);
        }
        if (isset($this->request->content_id)) {
            $contentId = $this->request->content_id;
        }
        if (!empty($this->request->content_page)) {
            $contentPage = $this->request->content_page;
        }

        $file = $this->models->files->$id;

        if (!$file) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }
        else if (!$file->active || $file->client_id != $this->request->client_id) {
            $this->response->setStatus(403);
            throw new Flooer_Exception('Forbidden', LOG_NOTICE);
        }

        $updata = array();
        if ($title !== null) {
            $updata['title'] = $title;
        }
        if ($description !== null) {
            $updata['description'] = $description;
        }
        if ($category !== null) {
            $updata['category'] = $category;
        }
        if ($tags !== null) {
            $updata['tags'] = $tags;
        }
        if ($version !== null) {
            $updata['version'] = $version;
        }
        if ($contentId !== null) {
            $updata['content_id'] = $contentId;
        }
        if ($contentPage !== null) {
            $updata['content_page'] = $contentPage;
        }

        $this->models->files->$id = $updata;

        $file = $this->models->files->getFile($id);

        $this->_setResponseContent(
            'success',
            array('file' => $file)
        );
    }

    public function deleteFile()
    {
        if (!$this->_isAllowedAccess()) {
            $this->response->setStatus(403);
            throw new Flooer_Exception('Forbidden', LOG_NOTICE);
        }

        $id = null;

        if (!empty($this->request->id)) {
            $id = $this->request->id;
        }

        $file = $this->models->files->$id;

        if (!$file) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }
        else if (!$file->active || $file->client_id != $this->request->client_id) {
            $this->response->setStatus(403);
            throw new Flooer_Exception('Forbidden', LOG_NOTICE);
        }

        $collectionId = $file->collection_id;
        $collection = $this->models->collections->$collectionId;

        //unlink($this->appConfig->general['filesDir'] . '/' . $collection->name . '/' . $file->name);
        //unset($this->models->files->$id);

        if (!is_dir($this->appConfig->general['filesDir'] . '/' . $collection->name . '/.trash')
            && !mkdir($this->appConfig->general['filesDir'] . '/' . $collection->name . '/.trash')
        ) {
            $this->response->setStatus(500);
            throw new Flooer_Exception('Failed to remove the file', LOG_ALERT);
        }

        if (!rename(
            $this->appConfig->general['filesDir'] . '/' . $collection->name . '/' . $file->name,
            $this->appConfig->general['filesDir'] . '/' . $collection->name . '/.trash/' . $id . '-' . $file->name
        )) {
            $this->response->setStatus(500);
            throw new Flooer_Exception('Failed to remove the file', LOG_ALERT);
        }

        $this->models->files->$id = array('active' => 0);
        //ronald 20170502 We need this information, so no delete in table files_downloaded
        //$this->models->files_downloaded->deleteByFileId($id);
        $this->models->favorites->deleteByFileId($id);
        $this->models->media->deleteByFileId($id);
        $this->models->media_played->deleteByFileId($id);

        $this->models->collections->$collectionId = array(
            'files' => $collection->files - 1,
            'size' => $collection->size - $file->size
        );

        // Delete old torrent file
        $torrent = $this->appConfig->general['torrentsDir'] . '/' . $collection->name . '.torrent';
        if (is_file($torrent)) {
            unlink($torrent);
        }
        if (is_file($torrent . '.added')) {
            unlink($torrent . '.added');
        }

        $this->_setResponseContent('success');
    }

    public function headDownload()
    {
        $this->getDownload(true);
    }

    public function getDownload($headeronly = false)
    {
        $id = null;
        $userId = null;

        if (!empty($this->request->id)) {
            $id = $this->request->id;
        }
        // Disabled for now
        //if (!empty($this->request->user_id)) {
        //    $userId = $this->request->user_id;
        //}

        $file = $this->models->files->$id;

        if (!$file) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }
        else if (!$file->active) {
            $this->response->setStatus(403);
            throw new Flooer_Exception('Forbidden', LOG_NOTICE);
        }

        $collectionId = $file->collection_id;
        $collection = $this->models->collections->$collectionId;

        if (!$headeronly && $file->downloaded_ip != $this->server->REMOTE_ADDR) {
            $this->models->files->updateDownloadedStatus($file->id);

            $downloadedId = $this->models->files_downloaded->generateId();
            $this->models->files_downloaded->$downloadedId = array(
                'client_id' => $file->client_id,
                'owner_id' => $file->owner_id,
                'collection_id' => $file->collection_id,
                'file_id' => $file->id,
                'user_id' => $userId
            );
        }

        // If external URI has set, redirect to it
        $externalUri = '';
        $tags = explode(',', $file->tags);
        foreach ($tags as $tag) {
            $tag = trim($tag);
            if (strpos($tag, 'link##') === 0) {
                $externalUri = urldecode(str_replace('link##', '', $tag));
                break;
            }
        }
        if ($externalUri) {
            $this->response->redirect($externalUri);
        }

        $this->_sendFile(
            $this->appConfig->general['filesDir'] . '/' . $collection->name . '/' . $file->name,
            $file->name,
            $file->type,
            $file->size,
            true,
            $headeronly
        );
    }

    private function _remoteFilesize($url)
    {
        static $regex = '/^Content-Length: *+\K\d++$/im';
        if (!$fp = @fopen($url, 'rb')) {
            return false;
        }
        if (
            isset($http_response_header) &&
            preg_match($regex, implode("\n", $http_response_header), $matches)
        ) {
            return (int)$matches[0];
        }
        return strlen(stream_get_contents($fp));
    }

    private function _mimeContentType($url)
    {
        $mime_types = array(
          'txt'  => 'text/plain',
          'htm'  => 'text/html',
          'html' => 'text/html',
          'php'  => 'text/html',
          'css'  => 'text/css',
          'js'   => 'application/javascript',
          'json' => 'application/json',
          'xml'  => 'application/xml',
          'swf'  => 'application/x-shockwave-flash',
          'flv'  => 'video/x-flv',
          // images
          'png'  => 'image/png',
          'jpe'  => 'image/jpeg',
          'jpeg' => 'image/jpeg',
          'jpg'  => 'image/jpeg',
          'gif'  => 'image/gif',
          'bmp'  => 'image/bmp',
          'ico'  => 'image/vnd.microsoft.icon',
          'tiff' => 'image/tiff',
          'tif'  => 'image/tiff',
          'svg'  => 'image/svg+xml',
          'svgz' => 'image/svg+xml',
          // archives
          'zip'  => 'application/zip',
          'rar'  => 'application/x-rar-compressed',
          'exe'  => 'application/x-msdownload',
          'msi'  => 'application/x-msdownload',
          'cab'  => 'application/vnd.ms-cab-compressed',
          // audio/video
          'mp3'  => 'audio/mpeg',
          'qt'   => 'video/quicktime',
          'mov'  => 'video/quicktime',
          // adobe
          'pdf'  => 'application/pdf',
          'psd'  => 'image/vnd.adobe.photoshop',
          'ai'   => 'application/postscript',
          'eps'  => 'application/postscript',
          'ps'   => 'application/postscript',
          // ms office
          'doc'  => 'application/msword',
          'rtf'  => 'application/rtf',
          'xls'  => 'application/vnd.ms-excel',
          'ppt'  => 'application/vnd.ms-powerpoint',
          // open office
          'odt'  => 'application/vnd.oasis.opendocument.text',
          'ods'  => 'application/vnd.oasis.opendocument.spreadsheet',
        );

        $filename_parts = explode('.', $url);
        $ext = strtolower(array_pop($filename_parts));
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        } else {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_NOBODY, 1);
            curl_exec($ch);
            return curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        }
    }
}
