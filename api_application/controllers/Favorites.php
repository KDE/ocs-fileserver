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

class Favorites extends BaseController
{

    public function getIndex()
    {
        $clientId = null;
        $userId = null;
        $ownerId = null;
        $collectionId = null;
        $fileId = null;
        $ids = null; // Comma-separated list
        $perpage = $this->appConfig->general['perpage'];
        $page = 1;

        if (!empty($this->request->client_id)) {
            $clientId = $this->request->client_id;
        }
        if (!empty($this->request->user_id)) {
            $userId = $this->request->user_id;
        }
        if (!empty($this->request->owner_id)) {
            $ownerId = $this->request->owner_id;
        }
        if (!empty($this->request->collection_id)) {
            $collectionId = $this->request->collection_id;
        }
        if (!empty($this->request->file_id)) {
            $fileId = $this->request->file_id;
        }
        if (!empty($this->request->ids)) {
            $ids = $this->request->ids;
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

        $favorites = $this->models->favorites->getFavorites(
            $clientId,
            $userId,
            $ownerId,
            $collectionId,
            $fileId,
            $ids,
            $perpage,
            $page
        );

        if (!$favorites) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }

        $this->_setResponseContent('success', $favorites);
    }

    public function getFavorite()
    {
        $id = null;

        if (!empty($this->request->id)) {
            $id = $this->request->id;
        }

        $favorite = $this->models->favorites->$id;

        if (!$favorite) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }

        $this->_setResponseContent(
            'success',
            array('favorite' => $favorite)
        );
    }

    public function postFavorite()
    {
        // Get favorite information or add new one

        if (!$this->_isAllowedAccess()) {
            $this->response->setStatus(403);
            throw new Flooer_Exception('Forbidden', LOG_NOTICE);
        }

        $id = null; // Auto generated
        $clientId = null;
        $userId = null;
        $ownerId = null; // Auto detect
        $collectionId = null; // Auto detect
        $fileId = null;

        if (!empty($this->request->client_id)) {
            $clientId = $this->request->client_id;
        }
        if (!empty($this->request->user_id)) {
            $userId = $this->request->user_id;
        }
        if (!empty($this->request->owner_id)) {
            $ownerId = $this->request->owner_id;
        }
        if (!empty($this->request->collection_id)) {
            $collectionId = $this->request->collection_id;
        }
        if (!empty($this->request->file_id)) {
            $fileId = $this->request->file_id;
        }

        $errors = array();
        if (!$clientId) {
            $errors['client_id'] = 'Required';
        }
        if (!$userId) {
            $errors['user_id'] = 'Required';
        }
        if (!$ownerId && !$collectionId && !$fileId) {
            $errors['owner_id'] = 'Missing';
            $errors['collection_id'] = 'Missing';
            $errors['file_id'] = 'Missing';
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

        $favorite = null;
        if ($fileId) {
            $favorite = $this->models->favorites->getFavoriteFile(
                $clientId,
                $userId,
                $fileId
            );
        }
        else if ($collectionId) {
            $favorite = $this->models->favorites->getFavoriteCollection(
                $clientId,
                $userId,
                $collectionId
            );
        }
        else if ($ownerId) {
            $favorite = $this->models->favorites->getFavoriteOwner(
                $clientId,
                $userId,
                $ownerId
            );
        }

        if (!$favorite) {
            $_clientId = null;
            if ($fileId) {
                $file = $this->models->files->$fileId;
                if (!$file) {
                    $this->response->setStatus(404);
                    throw new Flooer_Exception('Not found', LOG_NOTICE);
                }
                else if (!$file->active) {
                    $this->response->setStatus(403);
                    throw new Flooer_Exception('Forbidden', LOG_NOTICE);
                }
                $_clientId = $file->client_id;
                $ownerId = $file->owner_id;
                $collectionId = $file->collection_id;
            }
            else if ($collectionId) {
                $collection = $this->models->collections->$collectionId;
                if (!$collection) {
                    $this->response->setStatus(404);
                    throw new Flooer_Exception('Not found', LOG_NOTICE);
                }
                $_clientId = $collection->client_id;
                $ownerId = $collection->owner_id;
            }
            else if ($ownerId) {
                $_clientId = $clientId;
            }

            if ($_clientId != $clientId) {
                $this->response->setStatus(403);
                throw new Flooer_Exception('Forbidden', LOG_NOTICE);
            }

            $id = $this->models->favorites->generateId();
            $this->models->favorites->$id = array(
                'client_id' => $clientId,
                'user_id' => $userId,
                'owner_id' => $ownerId,
                'collection_id' => $collectionId,
                'file_id' => $fileId
            );

            $favorite = $this->models->favorites->$id;
        }

        $this->_setResponseContent(
            'success',
            array('favorite' => $favorite)
        );
    }

    public function deleteFavorite()
    {
        if (!$this->_isAllowedAccess()) {
            $this->response->setStatus(403);
            throw new Flooer_Exception('Forbidden', LOG_NOTICE);
        }

        $id = null;

        if (!empty($this->request->id)) {
            $id = $this->request->id;
        }

        $favorite = $this->models->favorites->$id;

        if (!$favorite) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }
        else if ($favorite->client_id != $this->request->client_id) {
            $this->response->setStatus(403);
            throw new Flooer_Exception('Forbidden', LOG_NOTICE);
        }

        unset($this->models->favorites->$id);

        $this->_setResponseContent('success');
    }

}
