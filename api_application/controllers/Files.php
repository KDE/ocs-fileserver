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
        $originId = null;
        $status = 'active';
        $clientId = null;
        $ownerId = null;
        $collectionId = null;
        $collectionStatus = 'active';
        $collectionCategory = null;
        $collectionTags = null; // Comma-separated list
        $collectionContentId = null;
        $types = null;  // Comma-separated list
        $category = null;
        $tags = null; // Comma-separated list
        $ocsCompatibility = 'all';
        $contentId = null;
        $search = null; // 3 or more strings
        $ids = null; // Comma-separated list
        $favoriteIds = array();
        $downloadedTimeperiodBegin = null; // Datetime format
        $downloadedTimeperiodEnd = null; // Datetime format
        $sort = 'name';
        $perpage = $this->appConfig->general['perpage'];
        $page = 1;

        if (!empty($this->request->origin_id)) {
            $originId = $this->request->origin_id;
        }
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
        if (!empty($this->request->collection_status)) {
            $collectionStatus = $this->request->collection_status;
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
        if (!empty($this->request->ocs_compatibility)) {
            $ocsCompatibility = $this->request->ocs_compatibility;
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
            $originId,
            $status,
            $clientId,
            $ownerId,
            $collectionId,
            $collectionStatus,
            $collectionCategory,
            $collectionTags,
            $collectionContentId,
            $types,
            $category,
            $tags,
            $ocsCompatibility,
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
        $originId = null; // Auto generated
        $active = 1;
        $clientId = null;
        $ownerId = null;
        $collectionId = null;
        $name = null; // Auto generated
        $type = null; // Auto detect
        $size = null; // Auto detect
        $md5sum = null; // Auto detect
        $title = null; // Name as default
        $description = null;
        $category = null;
        $tags = null; // Comma-separated list
        $version = null;
        $ocsCompatible = 1;
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
            $tags = strip_tags($this->request->tags);
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
                $md5sum = md5_file($_FILES['file']['tmp_name']);
            }
            if (!empty($_FILES['file']['size'])) {
                $size = $_FILES['file']['size'];
            }
        }
        // for hive files importing (Deprecated) ----------
        else if (isset($this->request->local_file_path)) {
            if (!empty($this->request->local_file_path)) {
                $name = mb_substr(strip_tags(basename($this->request->local_file_path)), 0, 200);
                $externalUri = $this->_detectLinkInTags($tags);
                if ($name == 'empty' && !empty($externalUri)) {
                    $type = $this->_detectMimeTypeFromUri($externalUri);
                    $size = $this->_detectFilesizeFromUri($externalUri);
                }
                else {
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
        if (isset($this->request->ocs_compatible)) {
            if ($this->request->ocs_compatible == 1) {
                $ocsCompatible = 1;
            }
            else if ($this->request->ocs_compatible == 0) {
                $ocsCompatible = 0;
            }
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

        // Get ID3 tags in the file
        $id3Tags = $this->_getId3Tags($type, $_FILES['file']['tmp_name']);

        // Prepare to append the file to collection
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
            $collectionActive = 1;
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
                'active' => $collectionActive,
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
        $originId = $id;
        $name = $this->_fixFilename($name, $collectionName);
        if (!$title) {
            $title = $name;
        }

        // Save the uploaded file
        if (isset($_FILES['file'])) {
            try {
                move_uploaded_file(
                    $_FILES['file']['tmp_name'],
                    $this->appConfig->general['filesDir'] . '/' . $collectionName . '/' . $name
                );
            } catch (Exception $exc) {
                //try to change owner 
                try {
                    $this->log->log("Set new rights", LOG_NOTICE);

                    $output = shell_exec('/opt/php_root /opt/repair.sh  '.$collectionName);
                    // Log
                    $this->log->log("Set new rights Done: ".$output, LOG_NOTICE);
                    
                } catch (Exception $exc) {
                    echo $exc->getTraceAsString();
                }
                
                if (!move_uploaded_file(
                    $_FILES['file']['tmp_name'],
                    $this->appConfig->general['filesDir'] . '/' . $collectionName . '/' . $name
                )) {
                    $this->response->setStatus(500);
                    throw new Flooer_Exception('Failed to save the file', LOG_ALERT);
                }
            }
            /*
            if (!move_uploaded_file(
                $_FILES['file']['tmp_name'],
                $this->appConfig->general['filesDir'] . '/' . $collectionName . '/' . $name
            )) {
                $this->response->setStatus(500);
                throw new Flooer_Exception('Failed to save the file', LOG_ALERT);
            }*/
        }
        // for hive files importing (Deprecated) ----------
        else if (isset($this->request->local_file_path)) {
            try {
                copy(
                    $this->request->local_file_path,
                    $this->appConfig->general['filesDir'] . '/' . $collectionName . '/' . $name
                );
                
            } catch (Exception $exc) {
                //try to change owner 
                try {
                    $this->log->log("Set new rights", LOG_NOTICE);

                    $output = shell_exec('/opt/php_root /opt/repair.sh  '.$collectionName);
                    // Log
                    $this->log->log("Set new rights Done: ".$output, LOG_NOTICE);
                    
                } catch (Exception $exc) {
                    echo $exc->getTraceAsString();
                }
                
                if (!copy(
                    $this->request->local_file_path,
                    $this->appConfig->general['filesDir'] . '/' . $collectionName . '/' . $name
                )) {
                    $this->response->setStatus(500);
                    throw new Flooer_Exception('Failed to save the file', LOG_ALERT);
                }
            }
            /*
            if (!copy(
                $this->request->local_file_path,
                $this->appConfig->general['filesDir'] . '/' . $collectionName . '/' . $name
            )) {
                $this->response->setStatus(500);
                throw new Flooer_Exception('Failed to save the file', LOG_ALERT);
            }*/
        }
        // ------------------------------------------------

        // Add/Update the collection
        $this->models->collections->$collectionId = $collectionData;

        // Add the file
        $this->models->files->$id = array(
            'origin_id' => $originId,
            'active' => $active,
            'client_id' => $clientId,
            'owner_id' => $ownerId,
            'collection_id' => $collectionId,
            'name' => $name,
            'type' => $type,
            'size' => $size,
            'md5sum' => $md5sum,
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'tags' => $tags,
            'version' => $version,
            'ocs_compatible' => $ocsCompatible,
            'content_id' => $contentId,
            'content_page' => $contentPage,
            'downloaded_count' => $downloadedCount // for hive files importing (Deprecated)
        );

        // Add the media
        if ($id3Tags) {
            $this->_addMedia($id3Tags, $clientId, $ownerId, $collectionId, $id, $name);
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
        $ocsCompatible = null;
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
            $tags = strip_tags($this->request->tags);
        }
        if (isset($this->request->version)) {
            $version = mb_substr(strip_tags($this->request->version), 0, 64);
        }
        if (isset($this->request->ocs_compatible)) {
            if ($this->request->ocs_compatible == 1) {
                $ocsCompatible = 1;
            }
            else if ($this->request->ocs_compatible == 0) {
                $ocsCompatible = 0;
            }
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

        // If new file has uploaded,
        // remove old file and replace to the new file with new file id
        if (isset($_FILES['file'])) {
            $id = null;
            $originId = $file->origin_id;
            $active = 1;
            $clientId = $file->client_id;
            $ownerId = $file->owner_id;
            $collectionId = $file->collection_id;
            $name = null; // Auto generated
            $type = null; // Auto detect
            $size = null; // Auto detect
            $md5sum = null; // Auto detect

            $downloadedCount = 0; // for hive files importing (Deprecated)

            if (!empty($_FILES['file']['name'])) {
                $name = mb_substr(strip_tags(basename($_FILES['file']['name'])), 0, 200);
            }
            if (!empty($_FILES['file']['tmp_name'])) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $type = $finfo->file($_FILES['file']['tmp_name']);
                $md5sum = md5_file($_FILES['file']['tmp_name']);
                if (!$type) {
                    $type = 'application/octet-stream';
                }
            }
            if (!empty($_FILES['file']['size'])) {
                $size = $_FILES['file']['size'];
            }
            
            if ($description === null) {
                $description = $file->description;
            }
            if ($category === null) {
                $category = $file->category;
            }
            if ($tags === null) {
                $tags = $file->tags;
            }
            if ($version === null) {
                $version = $file->version;
            }
            if ($ocsCompatible === null) {
                $ocsCompatible = $file->ocs_compatible;
            }
            if ($contentId === null) {
                $contentId = $file->content_id;
            }
            if ($contentPage === null) {
                $contentPage = $file->content_page;
            }

            $errors = array();
            if (!empty($_FILES['file']['error'])) { // 0 = UPLOAD_ERR_OK
                $errors['file'] = $_FILES['file']['error'];
            }

            if ($errors) {
                $this->response->setStatus(400);
                $this->_setResponseContent(
                    'error',
                    array(
                        'message' => 'File upload error',
                        'errors' => $errors
                    )
                );
                return;
            }

            // Remove old file
            $this->_removeFile($file);

            // Get ID3 tags in the file
            $id3Tags = $this->_getId3Tags($type, $_FILES['file']['tmp_name']);

            // Prepare to append the file to collection
            $collection = $this->models->collections->$collectionId;
            $collectionName = $collection->name;
            $collectionData = array(
                'files' => $collection->files + 1,
                'size' => $collection->size + $size
            );

            $id = $this->models->files->generateId();
            $name = $this->_fixFilename($name, $collectionName);
            if (!$title) {
                $title = $name;
            }

            // Save the uploaded file
            if (!move_uploaded_file(
                $_FILES['file']['tmp_name'],
                $this->appConfig->general['filesDir'] . '/' . $collectionName . '/' . $name
            )) {
                $this->response->setStatus(500);
                throw new Flooer_Exception('Failed to save the file', LOG_ALERT);
            }

            // Add the file
            $this->models->files->$id = array(
                'origin_id' => $originId,
                'active' => $active,
                'client_id' => $clientId,
                'owner_id' => $ownerId,
                'collection_id' => $collectionId,
                'name' => $name,
                'type' => $type,
                'size' => $size,
                'md5sum' => $md5sum,
                'title' => $title,
                'description' => $description,
                'category' => $category,
                'tags' => $tags,
                'version' => $version,
                'ocs_compatible' => $ocsCompatible,
                'content_id' => $contentId,
                'content_page' => $contentPage,
                'downloaded_count' => $downloadedCount // for hive files importing (Deprecated)
            );

            // Update the collection
            $this->models->collections->$collectionId = $collectionData;

            // Add the media
            if ($id3Tags) {
                $this->_addMedia($id3Tags, $clientId, $ownerId, $collectionId, $id, $name);
            }
        }
        // Update only file information
        else {
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
            if ($ocsCompatible !== null) {
                $updata['ocs_compatible'] = $ocsCompatible;
            }
            if ($contentId !== null) {
                $updata['content_id'] = $contentId;
            }
            if ($contentPage !== null) {
                $updata['content_page'] = $contentPage;
            }

            $this->models->files->$id = $updata;
        }

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

        $this->_removeFile($file);

        $this->_setResponseContent('success');
    }

    public function headDownloadfile() // Deprecated
    {
        // This is alias for HEAD /files/download
        $this->headDownload();
    }

    public function getDownloadfile($headeronly = false) // Deprecated
    {
        // This is alias for GET /files/download
        $this->getDownload($headeronly);
    }

    public function headDownload()
    {
        $this->getDownload(true);
    }
    
    public function getDownloadtorrent($headeronly = false) {
        
        $id = null;
        $userId = null;
        $isFromOcsApi = false;
        $isTorrent = true;
        $as = 'self';
        
        $linkType = null;

        if (!empty($this->request->id)) {
            $id = $this->request->id;
        }
        if (!empty($this->request->lt)) {
            $linkType = $this->request->lt;
            if($linkType === 'torrent') {
                $isTorrent = true;
            }
        }

        if ($id) {
            $id = $this->models->files->getFileId($id);
        }

        $file = $this->models->files->$id;

        if (!$file) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }

        $collectionId = $file->collection_id;
        
        $torrent = $this->appConfig->general['torrentsDir'] . '/' . $collectionId . '_' . $file->name . '.torrent';
        $fileName = $collectionId . '_' . $file->name . '.torrent';
        if (is_file($torrent . '.added')) {
            $torrent = $torrent . '.added';
            $fileName = $fileName  . '.added';
        }
        
        if (!is_file($torrent)) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }
        
        /*
        else if (!is_file($torrent)) {
            
            $collection = $this->models->collections->$collectionId;

            $collectionDir = '';
            if ($collection->active) {
                $collectionDir = $this->appConfig->general['filesDir'] . '/' . $collection->name;
            }
            else {
                $collectionDir = $this->appConfig->general['filesDir'] . '/.trash/' . $collection->id . '-' . $collection->name;
            }

            $filePath = '';
            if ($file->active) {
                $filePath = $collectionDir . '/' . $file->name;
            }
            else {
                $filePath = $collectionDir . '/.trash/' . $file->id . '-' . $file->name;
            }
            
            $this->_generateTorrent(
                $filePath,
                $torrent
            );
        }
         * 
         */
        
        //Save downloads, but not for perview downloads
        if($isTorrent) {
            if($isFromOcsApi) {
                $data = array(
                        'client_id' => $file->client_id,
                        'owner_id' => $file->owner_id,
                        'collection_id' => $file->collection_id,
                        'file_id' => $file->id,
                        'user_id' => $userId,
                        //'anonymous_cookie' => $anonymousCookie,
                        'referer' => 'OCS-API',
                        'source'  => 'OCS-API'
                    );
            } else {
                $data = array(
                        'client_id' => $file->client_id,
                        'owner_id' => $file->owner_id,
                        'collection_id' => $file->collection_id,
                        'file_id' => $file->id,
                        'user_id' => $userId,
                        //'anonymous_cookie' => $anonymousCookie,
                        'source'  => 'OCS-Webserver',
                        'link_type' => $linkType,
                        'referer' => null
                    );
            }
            try {
                //$downloadedId = $this->models->files_downloaded_all->generateId();
                $downloadedId = $this->models->files_downloaded_all->generateNewId();
                $ref = 'OCS-API';
                $this->models->files_downloaded_all->$downloadedId = $data;
            } catch (Exception $exc) {
                //echo $exc->getTraceAsString();
                $this->log->log("ERROR saving Download Data to DB: $exc->getTraceAsString()", LOG_ERR);
            }
        }
        
        
        if ($isTorrent) {
            if ($isTorrent && !$headeronly) {
                $this->models->files->updateDownloadedStatus($file->id);

                try {
                    //$downloadedId = $this->models->files_downloaded->generateId();
                    $downloadedId = $this->models->files_downloaded->generateNewId();
                    $ref = null;
                    if ($isFromOcsApi) {
                      $ref = 'OCS-API';
                    }
                    $this->models->files_downloaded->$downloadedId = array(
                        'client_id' => $file->client_id,
                        'owner_id' => $file->owner_id,
                        'collection_id' => $file->collection_id,
                        'file_id' => $file->id,
                        'user_id' => $userId,
                        'referer' => $ref
                    );

                    //save unique dataset
                    $downloadedId = $this->models->files_downloaded_unique->generateNewId();
                    $ref = null;
                    if ($isFromOcsApi) {
                      $ref = 'OCS-API';
                    }
                    $this->models->files_downloaded_unique->$downloadedId = array(
                        'client_id' => $file->client_id,
                        'owner_id' => $file->owner_id,
                        'collection_id' => $file->collection_id,
                        'file_id' => $file->id,
                        'user_id' => $userId,
                        'referer' => $ref
                    );
                } catch (Exception $exc) {
                    //echo $exc->getTraceAsString();
                    $this->log->log("ERROR saving Download Data to DB: $exc->getMessage()", LOG_ERR);
                }
            }

            $this->_sendFile(
                $torrent,
                $fileName,
                'text/html',
                filesize($torrent),
                true,
                $headeronly
            );
        }
        else {
            // Link is not ok
            // Log
            $this->log->log("Start Download failed (file: $file->id; time-div: $div;  client: $file->client_id; salt: $salt; hash: $hash; hashGiven: $hashGiven)", LOG_NOTICE);
            // Redirect to opendesktop project page
            $this->response->redirect($this->appConfig->general['redirectTargetServer'] . '/co/' . $collectionId);
        }
        
    }
    
    public function getCreatetorrent() {
        
        $id = null;
        if (!empty($this->request->id)) {
            $id = $this->request->id;
        }

        if ($id) {
            $id = $this->models->files->getFileId($id);
        }

        $file = $this->models->files->$id;

        if (!$file) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }
        
        $this->log->log("Start Create torrent (file: $file->id;)", LOG_NOTICE);
            

        $collectionId = $file->collection_id;
        
        $torrent = $this->appConfig->general['torrentsDir'] . '/' . $collectionId . '_' . $file->name . '.torrent';
        $fileName = $collectionId . '_' . $file->name . '.torrent';
        if (is_file($torrent . '.added')) {
            $torrent = $torrent . '.added';
            $fileName = $fileName  . '.added';
        }
        
        
        if (!is_file($torrent)) {
            
            $this->log->log("Start Create New torrent", LOG_NOTICE);

            
            $collection = $this->models->collections->$collectionId;

            $collectionDir = '';
            if ($collection->active) {
                $collectionDir = $this->appConfig->general['filesDir'] . '/' . $collection->name;
            }
            else {
                $collectionDir = $this->appConfig->general['filesDir'] . '/.trash/' . $collection->id . '-' . $collection->name;
            }

            $filePath = '';
            if ($file->active) {
                $filePath = $collectionDir . '/' . $file->name;
            }
            else {
                $filePath = $collectionDir . '/.trash/' . $file->id . '-' . $file->name;
            }
            
            $this->_generateTorrent(
                $filePath,
                $torrent
            );
            
            $this->log->log("Update File", LOG_NOTICE);
            $this->models->files->updateHasTorrent($file->id);
            
            $this->log->log("Done Create Torrent: $torrent", LOG_NOTICE);
            
        }
        
        $this->response->setStatus(200);
        $this->response->setHeader('Access-Control-Allow-Headers', 'User-Agent');
        $this->response->send();
        exit;
    }

    public function optionsDownloadTorrent()
    {
        $this->response->setStatus(200);
        $this->response->setHeader('Access-Control-Allow-Headers', 'User-Agent');
        $this->response->send();
        exit;
    }

    public function getDownload($headeronly = false)
    {
        $this->log->log(print_r($_SERVER, true));
        $this->log->log(print_r($_REQUEST, true));

        $id = null;
        $as = null;
        $userId = null;
        $hashGiven = null;
        $timestamp = null;
        $isFromOcsApi = false;
        $isFilepreview = false;
        
        $linkType = null;

        $anonymousCookie = null;

        if (!empty($this->request->id)) {
            $id = $this->request->id;
        }
        if (!empty($this->request->as)) {
            $as = $this->request->as;
        }
        if (!empty($this->request->u)) {
            $userId = $this->request->u;
        }
        if (!empty($this->request->c)) {
            $anonymousCookie = $this->request->c;
        }        
        if (!empty($this->request->s)) {
            $hashGiven = $this->request->s;
        }
        if (!empty($this->request->t)) {
            $timestamp = $this->request->t;
        }
        if (!empty($this->request->o)) {
            $isFromOcsApi = ($this->request->o == 1);
        }
        if (!empty($this->request->lt)) {
            $linkType = $this->request->lt;
            if($linkType === 'filepreview') {
                $isFilepreview = true;
            }
        }

        if ($id && $as) {
            $id = $this->models->files->getFileId($id, $as);
        }

        $file = $this->models->files->$id;

        if (!$file) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }

        $collectionId = $file->collection_id;

        // Check link if it is expired or old style
        $salt = $this->_getDownloadSecret($file->client_id);
        //20181009 ronald: change hash from MD5 to SHA512
        //$hash = md5($salt . $collectionId . $timestamp);
        $hash = hash('sha512',$salt . $collectionId . $timestamp);
        
        
        $now = time();
        $div = ($timestamp - $now);

        // Log
        $this->log->log("Start Download (client: $file->client_id; salt: $salt; hash: $hash; hashGiven: $hashGiven)", LOG_NOTICE);
        
        //Save downloads, but not for perview downloads
        if(!$isFilepreview) {
            if($isFromOcsApi) {
                $data = array(
                        'client_id' => $file->client_id,
                        'owner_id' => $file->owner_id,
                        'collection_id' => $file->collection_id,
                        'file_id' => $file->id,
                        'user_id' => $userId,
                        //'anonymous_cookie' => $anonymousCookie,
                        'referer' => 'OCS-API',
                        'source'  => 'OCS-API'
                    );
            } else {
                $data = array(
                        'client_id' => $file->client_id,
                        'owner_id' => $file->owner_id,
                        'collection_id' => $file->collection_id,
                        'file_id' => $file->id,
                        'user_id' => $userId,
                        //'anonymous_cookie' => $anonymousCookie,
                        'source'  => 'OCS-Webserver',
                        'link_type' => $linkType,
                        'referer' => null
                    );
            }
            try {
                //$downloadedId = $this->models->files_downloaded_all->generateId();
                $downloadedId = $this->models->files_downloaded_all->generateNewId();
                $ref = 'OCS-API';
                $this->models->files_downloaded_all->$downloadedId = $data;
            } catch (Exception $exc) {
                //echo $exc->getTraceAsString();
                $this->log->log("ERROR saving Download Data to DB: $exc->getTraceAsString()", LOG_ERR);
            }
        }
        

        if ($as || $isFromOcsApi || $isFilepreview || ($hashGiven == $hash && $div > 0)) {
            // Link is ok, go on
            $collection = $this->models->collections->$collectionId;

            $collectionDir = '';
            if ($collection->active) {
                $collectionDir = $this->appConfig->general['filesDir'] . '/' . $collection->name;
            }
            else {
                $collectionDir = $this->appConfig->general['filesDir'] . '/.trash/' . $collection->id . '-' . $collection->name;
            }

            $filePath = '';
            if ($file->active) {
                $filePath = $collectionDir . '/' . $file->name;
            }
            else {
                $filePath = $collectionDir . '/.trash/' . $file->id . '-' . $file->name;
            }

            $fileName = $file->name;
            $fileType = $file->type;
            $fileSize = $file->size;
            
            // If request URI ended with .zsync, make a response data as zsync data
            if (strtolower(substr($this->request->getUri(), -6)) == '.zsync') {
                // But don't make zsync for external URI
                if (!empty($this->_detectLinkInTags($file->tags))) {
                    $this->response->setStatus(404);
                    throw new Flooer_Exception('Not found', LOG_NOTICE);
                }

                $zsyncPath = $this->appConfig->general['zsyncDir'] . '/' . $file->id . '.zsync';
                if (!is_file($zsyncPath)) {
                    $this->_generateZsync($filePath, $zsyncPath, $fileName);
                }

                $filePath = $zsyncPath;
                $fileName .= '.zsync';
                $fileType = 'application/x-zsync';
                $fileSize = filesize($zsyncPath);
            }
            else {
                if (!$isFilepreview && !$headeronly) {
                    $this->models->files->updateDownloadedStatus($file->id);

                    try {
                        //$downloadedId = $this->models->files_downloaded->generateId();
                        $downloadedId = $this->models->files_downloaded->generateNewId();
                        $ref = null;
                        if ($isFromOcsApi) {
                          $ref = 'OCS-API';
                        }
                        $this->models->files_downloaded->$downloadedId = array(
                            'client_id' => $file->client_id,
                            'owner_id' => $file->owner_id,
                            'collection_id' => $file->collection_id,
                            'file_id' => $file->id,
                            'user_id' => $userId,
                            'referer' => $ref
                        );
                        
                        //save unique dataset
                        $downloadedId = $this->models->files_downloaded_unique->generateNewId();
                        $ref = null;
                        if ($isFromOcsApi) {
                          $ref = 'OCS-API';
                        }
                        $this->models->files_downloaded_unique->$downloadedId = array(
                            'client_id' => $file->client_id,
                            'owner_id' => $file->owner_id,
                            'collection_id' => $file->collection_id,
                            'file_id' => $file->id,
                            'user_id' => $userId,
                            'referer' => $ref
                        );
                    } catch (Exception $exc) {
                        //echo $exc->getTraceAsString();
                        $this->log->log("ERROR saving Download Data to DB: $exc->getMessage()", LOG_ERR);
                    }
                }

                // If external URI has set, redirect to it
                $externalUri = $this->_detectLinkInTags($file->tags);
                if (!empty($externalUri)) {
                    $this->response->redirect($externalUri);
                }
            }

            $this->_sendFile(
                $filePath,
                $fileName,
                $fileType,
                $fileSize,
                true,
                $headeronly
            );
        }
        else {
            // Link is not ok
            // Log
            $this->log->log("Start Download failed (file: $file->id; time-div: $div;  client: $file->client_id; salt: $salt; hash: $hash; hashGiven: $hashGiven)", LOG_NOTICE);
            // Redirect to opendesktop project page
            $this->response->redirect($this->appConfig->general['redirectTargetServer'] . '/co/' . $collectionId);
        }
    }

    public function optionsDownload()
    {
        $this->response->setStatus(200);
        $this->response->setHeader('Access-Control-Allow-Headers', 'Range, User-Agent');
        $this->response->send();
        exit;
    }

    private function _fixFilename($name, $collectionName)
    {
        if (is_file($this->appConfig->general['filesDir'] . '/' . $collectionName . '/' . $name)) {
            $fix = date('YmdHis');
            if (preg_match("/^([^.]+)(\..+)/", $name, $matches)) {
                $name = $matches[1] . '-' . $fix . $matches[2];
            }
            else {
                $name = $name . '-' . $fix;
            }
        }
        return $name;
    }

    private function _removeFile(Flooer_Db_Table_Row &$file)
    {
        // Please be care the remove process in Collections::deleteCollection()

        $id = $file->id;

        $collectionId = $file->collection_id;
        $collection = $this->models->collections->$collectionId;

        $trashDir = $this->appConfig->general['filesDir'] . '/' . $collection->name . '/.trash';
        if (!is_dir($trashDir) && !mkdir($trashDir)) {
            $this->response->setStatus(500);
            throw new Flooer_Exception('Failed to remove the file', LOG_ALERT);
        }
        if (is_file($this->appConfig->general['filesDir'] . '/' . $collection->name . '/' . $file->name)
            && !rename(
                $this->appConfig->general['filesDir'] . '/' . $collection->name . '/' . $file->name,
                $trashDir . '/' . $id . '-' . $file->name
            )
        ) {
            $this->response->setStatus(500);
            throw new Flooer_Exception('Failed to remove the file', LOG_ALERT);
        }

        $this->models->files->$id = array('active' => 0);
        //$this->models->files_downloaded->deleteByFileId($id);
        $this->models->favorites->deleteByFileId($id);
        $this->models->media->deleteByFileId($id);
        $this->models->media_played->deleteByFileId($id);

        $this->models->collections->$collectionId = array(
            'files' => $collection->files - 1,
            'size' => $collection->size - $file->size
        );
    }

    private function _getId3Tags($filetype, $filepath)
    {
        // NOTE: getid3 may not work for a files in a network storage.
        $id3Tags = null;
        if (strpos($filetype, 'audio/') !== false
            || strpos($filetype, 'video/') !== false
            || strpos($filetype, 'application/ogg') !== false
        ) {
            require_once 'getid3/getid3.php';
            $getID3 = new getID3();
            $id3Tags = $getID3->analyze($filepath);
            getid3_lib::CopyTagsToComments($id3Tags);
        }
        return $id3Tags;
    }

    private function _addMedia(array $id3Tags, $clientId, $ownerId, $collectionId, $fileId, $defaultTitle)
    {
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

        // Add the media
        $mediaData = array(
            'client_id' => $clientId,
            'owner_id' => $ownerId,
            'collection_id' => $collectionId,
            'file_id' => $fileId,
            'artist_id' => $artistId,
            'album_id' => $albumId,
            'title' => $defaultTitle,
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

}
