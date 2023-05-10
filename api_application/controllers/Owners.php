<?php

use Ocs\Storage\FilesystemAdapter;

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
class Owners extends BaseController
{

    /**
     * @throws Flooer_Exception
     */
    public function deleteOwner() {
        if (!$this->_isAllowedAccess()) {
            $this->response->setStatus(403);
            throw new Flooer_Exception('Forbidden', LOG_NOTICE);
        }

        if (empty($this->request->client_id) || empty($this->request->id)) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }

        $clientId = $this->request->client_id;
        $ownerId = $this->request->id;

        // Remove profile
        $profile = $this->models->profiles->getProfileByClientIdAndOwnerId($clientId, $ownerId);
        if ($profile && $profile->active) {
            $this->models->profiles->{$profile->id} = array('active' => 0);
        }

        // Remove collections and related data
        $collections = $this->models->collections->fetchRowset('WHERE active = :active AND client_id = :client_id AND owner_id = :owner_id',
                                                               array(
                                                                   ':active'    => 1,
                                                                   ':client_id' => $clientId,
                                                                   ':owner_id'  => $ownerId
                                                               ));
        if ($collections) {
            $this->log->log("Remove collections (client:$clientId; owner:$ownerId)", LOG_NOTICE);
            foreach ($collections as $collection) {
                $thumbnail = $this->appConfig->general['thumbnailsDir'] . '/collection_' . $collection->id . '.jpg';
                if (is_file($thumbnail)) {
                    unlink($thumbnail);
                }

                $fileSystemAdapter = new FilesystemAdapter($this->appConfig);
                //$fileSystemAdapter = new \Ocs\Storage\S3Adapter($this->appConfig);

                // move collection to trash dir
                $trashDir = $this->appConfig->general['filesDir'] . '/.trash';
                if (!$fileSystemAdapter->testAndCreate($trashDir)) {
                    $this->response->setStatus(500);
                    throw new Flooer_Exception('Failed to remove the collection', LOG_ALERT);
                }
                $pathCollection = $this->appConfig->general['filesDir'] . '/' . $collection->name;
                if (is_dir($pathCollection) && !rename($pathCollection,$trashDir . '/' . $collection->id . '-' . $collection->name))
                {
                    $this->response->setStatus(500);
                    throw new Flooer_Exception('Failed to remove the collection', LOG_ALERT);
                }

                $this->models->collections->{$collection->id} = array('active' => 0);
                //$this->models->collections_downloaded->deleteByCollectionId($collection->id);
                //$this->models->files->deleteByCollectionId($collection->id);
                //$this->models->files_downloaded->deleteByCollectionId($collection->id);
                $this->models->favorites->deleteByCollectionId($collection->id);
                $this->models->media->deleteByCollectionId($collection->id);
                $this->models->media_played->deleteByCollectionId($collection->id);
            }
        }

        // Remove user or owner from favorites
        $favorites = $this->models->favorites->fetchRowset('WHERE client_id = :client_id' . ' AND (user_id = :user_id OR owner_id = :owner_id)',
                                                           array(
                                                               ':client_id' => $clientId,
                                                               ':user_id'   => $ownerId,
                                                               ':owner_id'  => $ownerId
                                                           ));
        if ($favorites) {
            foreach ($favorites as $favorite) {
                unset($this->models->favorites->{$favorite->id});
            }
        }

        $this->_setResponseContent('success');
    }

}
