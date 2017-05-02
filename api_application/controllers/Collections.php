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

class Collections extends BaseController
{

    public function getIndex()
    {
        $clientId = null;
        $ownerId = null;
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

        if (!empty($this->request->client_id)) {
            $clientId = $this->request->client_id;
        }
        if (!empty($this->request->owner_id)) {
            $ownerId = $this->request->owner_id;
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

        $collections = $this->models->collections->getCollections(
            $clientId,
            $ownerId,
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

        if (!$collections) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }

        $this->_setResponseContent('success', $collections);
    }

    public function getCollection()
    {
        $id = null;

        if (!empty($this->request->id)) {
            $id = $this->request->id;
        }

        $collection = $this->models->collections->getCollection($id);

        if (!$collection) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }

        $this->_setResponseContent(
            'success',
            array('collection' => $collection)
        );
    }

    public function postCollection()
    {
        if (!$this->_isAllowedAccess()) {
            $this->response->setStatus(403);
            throw new Flooer_Exception('Forbidden', LOG_NOTICE);
        }

        $id = null; // Auto generated
        $clientId = null;
        $ownerId = null;
        $name = null; // Auto generated
        $files = 0;
        $size = 0;
        $title = null; // Name as default
        $description = null;
        $category = null;
        $tags = null; // Comma-separated list
        $version = null;
        $contentId = null;
        $contentPage = null;

        if (!empty($this->request->client_id)) {
            $clientId = $this->request->client_id;
        }
        if (!empty($this->request->owner_id)) {
            $ownerId = $this->request->owner_id;
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

        $errors = array();
        if (!$clientId) {
            $errors['client_id'] = 'Required';
        }
        if (!$ownerId) {
            $errors['owner_id'] = 'Required';
        }

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

        $id = $this->models->collections->generateId();
        $name = $id;
        if (!$title) {
            $title = $name;
        }
        if (!is_dir($this->appConfig->general['filesDir'] . '/' . $name)
            && !mkdir($this->appConfig->general['filesDir'] . '/' . $name)
        ) {
            $this->response->setStatus(500);
            throw new Flooer_Exception('Failed to create collection', LOG_ALERT);
        }

        $this->models->collections->$id = array(
            'client_id' => $clientId,
            'owner_id' => $ownerId,
            'name' => $name,
            'files' => $files,
            'size' => $size,
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'tags' => $tags,
            'version' => $version,
            'content_id' => $contentId,
            'content_page' => $contentPage
        );

        $collection = $this->models->collections->getCollection($id);

        $this->_setResponseContent(
            'success',
            array('collection' => $collection)
        );
    }

    public function putCollection()
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

        $collection = $this->models->collections->$id;

        if (!$collection) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }
        else if ($collection->client_id != $this->request->client_id) {
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

        $this->models->collections->$id = $updata;

        $collection = $this->models->collections->getCollection($id);

        $this->_setResponseContent(
            'success',
            array('collection' => $collection)
        );
    }

    public function deleteCollection()
    {
        if (!$this->_isAllowedAccess()) {
            $this->response->setStatus(403);
            throw new Flooer_Exception('Forbidden', LOG_NOTICE);
        }

        $id = null;

        if (!empty($this->request->id)) {
            $id = $this->request->id;
        }

        $collection = $this->models->collections->$id;

        if (!$collection) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }
        else if ($collection->client_id != $this->request->client_id) {
            $this->response->setStatus(403);
            throw new Flooer_Exception('Forbidden', LOG_NOTICE);
        }

        $torrent = $this->appConfig->general['torrentsDir'] . '/' . $collection->name . '.torrent';
        if (is_file($torrent)) {
            unlink($torrent);
        }
        if (is_file($torrent . '.added')) {
            unlink($torrent . '.added');
        }

        $thumbnail = $this->appConfig->general['thumbnailsDir'] . '/collection_' . $id . '.jpg';
        if (is_file($thumbnail)) {
            unlink($thumbnail);
        }

        exec('rm'
            . ' -r'
            . ' "' . $this->appConfig->general['filesDir'] . '/' . $collection->name . '"'
        );

        unset($this->models->collections->$id);
        //$this->models->collections_downloaded->deleteByCollectionId($id);
        $this->models->files->deleteByCollectionId($id);
        //$this->models->files_downloaded->deleteByCollectionId($id);
        $this->models->favorites->deleteByCollectionId($id);
        $this->models->media->deleteByCollectionId($id);
        $this->models->media_played->deleteByCollectionId($id);

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

        $collection = $this->models->collections->$id;

        if (!$collection) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }

        $torrent = $this->appConfig->general['torrentsDir'] . '/' . $collection->name . '.torrent';
        if (is_file($torrent . '.added')) {
            $torrent = $torrent . '.added';
        }
        else if (!is_file($torrent)) {
            $this->_generateTorrent(
                $this->appConfig->general['filesDir'] . '/' . $collection->name,
                $torrent
            );
        }

        $profile = $this->models->profiles->getProfile(
            $collection->client_id,
            $collection->owner_id
        );

        $profileName = $collection->owner_id;
        if ($profile) {
            $profileName = $profile->name;
        }

        $collectionTitle = $collection->name;
        if ($collection->title) {
            $collectionTitle = $collection->title;
        }

        $filename = str_replace(' ', '_', $profileName)
            . '_' . str_replace(' ', '_', $collectionTitle);

        if (!$headeronly && $collection->downloaded_ip != $this->server->REMOTE_ADDR) {
            $this->models->collections->updateDownloadedStatus($collection->id);

            $downloadedId = $this->models->collections_downloaded->generateId();
            $this->models->collections_downloaded->$downloadedId = array(
                'client_id' => $collection->client_id,
                'owner_id' => $collection->owner_id,
                'collection_id' => $collection->id,
                'user_id' => $userId
            );
        }

        $this->_sendFile(
            $torrent,
            $filename . '.torrent',
            'application/x-bittorrent',
            filesize($torrent),
            true,
            $headeronly
        );
    }

}
