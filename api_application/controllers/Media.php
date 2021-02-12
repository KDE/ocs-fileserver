<?php /** @noinspection PhpUndefinedFieldInspection */

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

class Media extends BaseController
{

    public function getGenres()
    {
        $clientId = null;
        $ownerId = null;
        $collectionId = null;
        $collectionCategory = null;
        $collectionTags = null; // Comma-separated list
        $collectionContentId = null;
        $fileId = null;
        $fileTypes = null; // Comma-separated list
        $fileCategory = null;
        $fileTags = null; // Comma-separated list
        $fileOcsCompatibility = 'all';
        $fileContentId = null;
        $artistId = null;
        $albumId = null;
        $genre = null;
        $search = null; // 3 or more strings
        $favoriteIds = array();
        $sort = 'name';
        $perpage = $this->appConfig->general['perpage'];
        $page = 1;

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
        if (!empty($this->request->file_id)) {
            $fileId = $this->request->file_id;
        }
        if (!empty($this->request->file_types)) {
            $fileTypes = $this->request->file_types;
        }
        if (isset($this->request->file_category)) {
            $fileCategory = $this->request->file_category;
        }
        if (isset($this->request->file_tags)) {
            $fileTags = $this->request->file_tags;
        }
        if (!empty($this->request->file_ocs_compatibility)) {
            $fileOcsCompatibility = $this->request->file_ocs_compatibility;
        }
        if (isset($this->request->file_content_id)) {
            $fileContentId = $this->request->file_content_id;
        }
        if (!empty($this->request->artist_id)) {
            $artistId = $this->request->artist_id;
        }
        if (!empty($this->request->album_id)) {
            $albumId = $this->request->album_id;
        }
        if (!empty($this->request->genre)) {
            $genre = $this->request->genre;
        }
        if (!empty($this->request->search)) {
            $search = $this->request->search;
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

        $genres = $this->models->media->getGenres(
            $clientId,
            $ownerId,
            $collectionId,
            $collectionCategory,
            $collectionTags,
            $collectionContentId,
            $fileId,
            $fileTypes,
            $fileCategory,
            $fileTags,
            $fileOcsCompatibility,
            $fileContentId,
            $artistId,
            $albumId,
            $genre,
            $search,
            $favoriteIds,
            $sort,
            $perpage,
            $page
        );

        if (!$genres) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }

        $this->_setResponseContent('success', $genres);
    }

    public function getOwners()
    {
        $clientId = null;
        $ownerId = null;
        $collectionId = null;
        $collectionCategory = null;
        $collectionTags = null; // Comma-separated list
        $collectionContentId = null;
        $fileId = null;
        $fileTypes = null; // Comma-separated list
        $fileCategory = null;
        $fileTags = null; // Comma-separated list
        $fileOcsCompatibility = 'all';
        $fileContentId = null;
        $artistId = null;
        $albumId = null;
        $genre = null;
        $search = null; // 3 or more strings
        $favoriteIds = array();
        $sort = 'name';
        $perpage = $this->appConfig->general['perpage'];
        $page = 1;

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
        if (!empty($this->request->file_id)) {
            $fileId = $this->request->file_id;
        }
        if (!empty($this->request->file_types)) {
            $fileTypes = $this->request->file_types;
        }
        if (isset($this->request->file_category)) {
            $fileCategory = $this->request->file_category;
        }
        if (isset($this->request->file_tags)) {
            $fileTags = $this->request->file_tags;
        }
        if (!empty($this->request->file_ocs_compatibility)) {
            $fileOcsCompatibility = $this->request->file_ocs_compatibility;
        }
        if (isset($this->request->file_content_id)) {
            $fileContentId = $this->request->file_content_id;
        }
        if (!empty($this->request->artist_id)) {
            $artistId = $this->request->artist_id;
        }
        if (!empty($this->request->album_id)) {
            $albumId = $this->request->album_id;
        }
        if (!empty($this->request->genre)) {
            $genre = $this->request->genre;
        }
        if (!empty($this->request->search)) {
            $search = $this->request->search;
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

        $owners = $this->models->media->getOwners(
            $clientId,
            $ownerId,
            $collectionId,
            $collectionCategory,
            $collectionTags,
            $collectionContentId,
            $fileId,
            $fileTypes,
            $fileCategory,
            $fileTags,
            $fileOcsCompatibility,
            $fileContentId,
            $artistId,
            $albumId,
            $genre,
            $search,
            $favoriteIds,
            $sort,
            $perpage,
            $page
        );

        if (!$owners) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }

        $this->_setResponseContent('success', $owners);
    }

    public function getCollections()
    {
        $clientId = null;
        $ownerId = null;
        $collectionId = null;
        $collectionCategory = null;
        $collectionTags = null; // Comma-separated list
        $collectionContentId = null;
        $fileId = null;
        $fileTypes = null; // Comma-separated list
        $fileCategory = null;
        $fileTags = null; // Comma-separated list
        $fileOcsCompatibility = 'all';
        $fileContentId = null;
        $artistId = null;
        $albumId = null;
        $genre = null;
        $search = null; // 3 or more strings
        $favoriteIds = array();
        $sort = 'name';
        $perpage = $this->appConfig->general['perpage'];
        $page = 1;

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
        if (!empty($this->request->file_id)) {
            $fileId = $this->request->file_id;
        }
        if (!empty($this->request->file_types)) {
            $fileTypes = $this->request->file_types;
        }
        if (isset($this->request->file_category)) {
            $fileCategory = $this->request->file_category;
        }
        if (isset($this->request->file_tags)) {
            $fileTags = $this->request->file_tags;
        }
        if (!empty($this->request->file_ocs_compatibility)) {
            $fileOcsCompatibility = $this->request->file_ocs_compatibility;
        }
        if (isset($this->request->file_content_id)) {
            $fileContentId = $this->request->file_content_id;
        }
        if (!empty($this->request->artist_id)) {
            $artistId = $this->request->artist_id;
        }
        if (!empty($this->request->album_id)) {
            $albumId = $this->request->album_id;
        }
        if (!empty($this->request->genre)) {
            $genre = $this->request->genre;
        }
        if (!empty($this->request->search)) {
            $search = $this->request->search;
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

        $collections = $this->models->media->getCollections(
            $clientId,
            $ownerId,
            $collectionId,
            $collectionCategory,
            $collectionTags,
            $collectionContentId,
            $fileId,
            $fileTypes,
            $fileCategory,
            $fileTags,
            $fileOcsCompatibility,
            $fileContentId,
            $artistId,
            $albumId,
            $genre,
            $search,
            $favoriteIds,
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

    public function getIndex()
    {
        $clientId = null;
        $ownerId = null;
        $collectionId = null;
        $collectionCategory = null;
        $collectionTags = null; // Comma-separated list
        $collectionContentId = null;
        $fileId = null;
        $fileTypes = null; // Comma-separated list
        $fileCategory = null;
        $fileTags = null; // Comma-separated list
        $fileOcsCompatibility = 'all';
        $fileContentId = null;
        $artistId = null;
        $albumId = null;
        $genre = null;
        $search = null; // 3 or more strings
        $ids = null; // Comma-separated list
        $favoriteIds = array();
        $playedTimeperiodBegin = null; // Datetime format
        $playedTimeperiodEnd = null; // Datetime format
        $sort = 'name';
        $perpage = $this->appConfig->general['perpage'];
        $page = 1;

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
        if (!empty($this->request->file_id)) {
            $fileId = $this->request->file_id;
        }
        if (!empty($this->request->file_types)) {
            $fileTypes = $this->request->file_types;
        }
        if (isset($this->request->file_category)) {
            $fileCategory = $this->request->file_category;
        }
        if (isset($this->request->file_tags)) {
            $fileTags = $this->request->file_tags;
        }
        if (!empty($this->request->file_ocs_compatibility)) {
            $fileOcsCompatibility = $this->request->file_ocs_compatibility;
        }
        if (isset($this->request->file_content_id)) {
            $fileContentId = $this->request->file_content_id;
        }
        if (!empty($this->request->artist_id)) {
            $artistId = $this->request->artist_id;
        }
        if (!empty($this->request->album_id)) {
            $albumId = $this->request->album_id;
        }
        if (!empty($this->request->genre)) {
            $genre = $this->request->genre;
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
        if (!empty($this->request->played_timeperiod_begin)) {
            $playedTimeperiodBegin = $this->request->played_timeperiod_begin;
        }
        if (!empty($this->request->played_timeperiod_end)) {
            $playedTimeperiodEnd = $this->request->played_timeperiod_end;
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

        $index = $this->models->media->getIndex(
            $clientId,
            $ownerId,
            $collectionId,
            $collectionCategory,
            $collectionTags,
            $collectionContentId,
            $fileId,
            $fileTypes,
            $fileCategory,
            $fileTags,
            $fileOcsCompatibility,
            $fileContentId,
            $artistId,
            $albumId,
            $genre,
            $search,
            $ids,
            $favoriteIds,
            $playedTimeperiodBegin,
            $playedTimeperiodEnd,
            $sort,
            $perpage,
            $page
        );

        if (!$index) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }

        $this->_setResponseContent('success', $index);
    }

    public function getMedia()
    {
        $id = null;

        if (!empty($this->request->id)) {
            $id = $this->request->id;
        }

        $media = $this->models->media->getMedia($id);

        if (!$media) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }

        $this->_setResponseContent(
            'success',
            array('media' => $media)
        );
    }

    public function headStream()
    {
        $this->getStream(true);
    }

    public function getStream($headeronly = false)
    {
        $id = null;
        $userId = null;

        if (!empty($this->request->id)) {
            $id = $this->request->id;
        }
        // Disabled for now
        //if (!empty($this->request->u)) {
        //    $userId = $this->request->u;
        //}

        $media = $this->models->media->$id;

        if (!$media) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }

        $collection = $this->models->collections->{$media->collection_id};
        $file = $this->models->files->{$media->file_id};

        if (!$headeronly && $media->played_ip != $this->server->REMOTE_ADDR) {
            $this->models->media->updatePlayedStatus($media->id);

            $playedId = $this->models->media_played->generateId();
            $this->models->media_played->$playedId = array(
                'client_id' => $media->client_id,
                'owner_id' => $media->owner_id,
                'collection_id' => $media->collection_id,
                'file_id' => $media->file_id,
                'media_id' => $media->id,
                'user_id' => $userId
            );

        }

        $this->_sendFile(
            $this->appConfig->general['filesDir'] . '/' . $collection->name . '/' . $file->name,
            $file->name,
            $file->type,
            $file->size,
            false,
            $headeronly
        );
    }

    public function headCollectionthumbnail()
    {
        $this->getCollectionthumbnail(true);
    }

    public function getCollectionthumbnail($headeronly = false)
    {
        $filepath = $this->appConfig->general['thumbnailsDir'] . '/collection_default.jpg';
        if (isset($this->request->id)
            && is_file($this->appConfig->general['thumbnailsDir'] . '/collection_' . $this->request->id . '.jpg')
        ) {
            $filepath = $this->appConfig->general['thumbnailsDir'] . '/collection_' . $this->request->id . '.jpg';
        }

        $this->_sendFile(
            $filepath,
            basename($filepath),
            'image/jpeg',
            filesize($filepath),
            false,
            $headeronly
        );
    }

    public function postCollectionthumbnail()
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

        $errors = array();
        if (!isset($_FILES['file'])) {
            $errors['file'] = 'Required';
        }
        if (!empty($_FILES['file']['error'])) { // 0 = UPLOAD_ERR_OK
            $errors['file'] = $_FILES['file']['error'];
        }
        if (!empty($_FILES['file']['tmp_name'])) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $type = $finfo->file($_FILES['file']['tmp_name']);
            if (strpos($type, 'image/jpeg') === false
                && strpos($type, 'image/png') === false
            ) {
                $errors['file'] = 'Must upload JPEG or PNG image';
            }
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

        $image = imagecreatefromstring(
            file_get_contents($_FILES['file']['tmp_name'])
        );

        if ($image !== false) {
            imagejpeg($image, $this->appConfig->general['thumbnailsDir'] . '/collection_' . $id . '.jpg', 75);
            imagedestroy($image);
        }

        $this->_setResponseContent('success');
    }

    public function headAlbumthumbnail()
    {
        $this->getAlbumthumbnail(true);
    }

    public function getAlbumthumbnail($headeronly = false)
    {
        $filepath = $this->appConfig->general['thumbnailsDir'] . '/album_default.jpg';
        if (isset($this->request->id)
            && is_file($this->appConfig->general['thumbnailsDir'] . '/album_' . $this->request->id . '.jpg')
        ) {
            $filepath = $this->appConfig->general['thumbnailsDir'] . '/album_' . $this->request->id . '.jpg';
        }

        $this->_sendFile(
            $filepath,
            basename($filepath),
            'image/jpeg',
            filesize($filepath),
            false,
            $headeronly
        );
    }

    public function postAlbumthumbnail()
    {
        if (!$this->_isAllowedAccess()) {
            $this->response->setStatus(403);
            throw new Flooer_Exception('Forbidden', LOG_NOTICE);
        }

        $id = null;

        if (!empty($this->request->id)) {
            $id = $this->request->id;
        }

        $mediaAlbum = $this->models->media_albums->$id;

        if (!$mediaAlbum) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }
        else if ($mediaAlbum->client_id != $this->request->client_id) {
            $this->response->setStatus(403);
            throw new Flooer_Exception('Forbidden', LOG_NOTICE);
        }

        $errors = array();
        if (!isset($_FILES['file'])) {
            $errors['file'] = 'Required';
        }
        if (!empty($_FILES['file']['error'])) { // 0 = UPLOAD_ERR_OK
            $errors['file'] = $_FILES['file']['error'];
        }
        if (!empty($_FILES['file']['tmp_name'])) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $type = $finfo->file($_FILES['file']['tmp_name']);
            if (strpos($type, 'image/jpeg') === false
                && strpos($type, 'image/png') === false
            ) {
                $errors['file'] = 'Must upload JPEG or PNG image';
            }
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

        $image = imagecreatefromstring(
            file_get_contents($_FILES['file']['tmp_name'])
        );

        if ($image !== false) {
            imagejpeg($image, $this->appConfig->general['thumbnailsDir'] . '/album_' . $id . '.jpg', 75);
            imagedestroy($image);
        }

        $this->_setResponseContent('success');
    }

}
