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
     * GDPR delete identified by owner_id.
     * Endpoint: DELETE /api/owners/{id}?client_id=X
     *
     * @throws Flooer_Exception
     */
    public function deleteOwner(): void
    {
        if (!$this->_isAllowedAccess()) {
            $this->response->setStatus(403);
            throw new Flooer_Exception('Forbidden', LOG_NOTICE);
        }

        if (empty($this->request->client_id) || empty($this->request->id)) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }

        $this->_performGdprDelete((int)$this->request->client_id, $this->request->id);
        $this->_setResponseContent('success');
    }

    /**
     * GDPR delete identified by collection_id.
     * Looks up owner_id and client_id from the collection record, then delegates.
     * Endpoint: DELETE /api/owners/bycollection?client_id=X&id=x
     *
     * @throws Flooer_Exception
     */
    public function deleteBycollection(): void
    {
        if (!$this->_isAllowedAccess()) {
            $this->response->setStatus(403);
            throw new Flooer_Exception('Forbidden', LOG_NOTICE);
        }

        if (empty($this->request->client_id) || empty($this->request->id)) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }

        $collection = $this->models->collections->{$this->request->id};
        if (!$collection) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Collection not found', LOG_NOTICE);
        }

        $this->_performGdprDelete((int)$this->request->client_id, $collection->owner_id);
        $this->_setResponseContent('success');
    }

    /**
     * GDPR-compliant hard-delete of all data belonging to an owner (Art. 17 DSGVO).
     *
     * - Profile row is hard-deleted.
     * - All files and collections are hard-deleted from the database.
     * - Collection directories are permanently removed from disk.
     * - Favorites referencing this owner/user are hard-deleted.
     *
     * @throws Flooer_Exception
     */
    private function _performGdprDelete(int $clientId, string $ownerId): void
    {
        $this->logWithRequestId(__METHOD__ . " GDPR delete started (client:$clientId; owner:$ownerId)");

        // 1. Remove collections, their files, and files on disk
        $collections = $this->models->collections->fetchRowset(
            'WHERE client_id = :client_id AND owner_id = :owner_id',
            array(':client_id' => $clientId, ':owner_id' => $ownerId)
        );

        if ($collections) {
            foreach ($collections as $collection) {
                // Delete thumbnail
                $thumbnail = $this->appConfig->general['thumbnailsDir'] . '/collection_' . $collection->id . '.jpg';
                if (is_file($thumbnail)) {
                    unlink($thumbnail);
                }

                // Permanently delete collection directory from disk.
                // Both paths are checked unconditionally to clean up any orphaned
                // artifacts left by past s3fs inconsistencies.
                $pathsToDelete = [
                    $this->appConfig->general['filesDir'] . '/' . $collection->name,
                    $this->appConfig->general['filesDir'] . '/.trash/' . $collection->id . '-' . $collection->name,
                ];
                foreach ($pathsToDelete as $path) {
                    if (is_dir($path)) {
                        $this->_deleteDirectoryRecursive($path);
                    }
                }

                // Hard-delete file records and per-collection favorites/media
                $this->models->files->deleteByCollectionId($collection->id);
                $this->models->collections_downloaded->deleteByCollectionId($collection->id);
                $this->models->favorites->deleteByCollectionId($collection->id);
                $this->models->media->deleteByCollectionId($collection->id);
                $this->models->media_played->deleteByCollectionId($collection->id);

                // Hard-delete the collection record itself
                unset($this->models->collections->{$collection->id});
            }
            $this->logWithRequestId(__METHOD__ . " collections removed (client:$clientId; owner:$ownerId)");
        }

        // 2. Anonymize all download/play log entries that reference this owner.
        //    Rows are kept for statistical integrity; personal references are removed.
//        $this->models->files_downloaded->anonymizeByOwnerId($ownerId, $this->appConfig->gdpr['deleted_owner_id']);
//        $this->models->files_downloaded_all->anonymizeByOwnerId($ownerId, $this->appConfig->gdpr['deleted_owner_id']);
//        $this->models->files_downloaded_unique->anonymizeByOwnerId($ownerId, $this->appConfig->gdpr['deleted_owner_id']);
//        $this->models->collections_downloaded->anonymizeByOwnerId($ownerId, $this->appConfig->gdpr['deleted_owner_id']);
//        $this->models->media_played->anonymizeByOwnerId($ownerId, $this->appConfig->gdpr['deleted_owner_id']);

        // 3. Hard-delete the profile
        $profile = $this->models->profiles->getProfileByClientIdAndOwnerId($clientId, $ownerId);
        if ($profile) {
            unset($this->models->profiles->{$profile->id});
        }

        // 4. Hard-delete all favorites where this person appears as user or as owner
        $favorites = $this->models->favorites->fetchRowset(
            'WHERE client_id = :client_id AND (user_id = :user_id OR owner_id = :owner_id)',
            array(':client_id' => $clientId, ':user_id' => $ownerId, ':owner_id' => $ownerId)
        );
        if ($favorites) {
            foreach ($favorites as $favorite) {
                unset($this->models->favorites->{$favorite->id});
            }
        }

        $this->logWithRequestId(__METHOD__ . " GDPR delete completed (client:$clientId; owner:$ownerId)");
    }

    /**
     * Recursively deletes a directory and all its contents.
     *
     * Uses exec('rm -rf') instead of SPL iterators because on s3fs (FUSE) mounts
     * getRealPath() returns false, causing unlink/rmdir to silently do nothing.
     * exec() with escapeshellarg is safe here: $dir is built server-side from
     * appConfig paths and a database-stored collection name, never from raw user input.
     */
    private function _deleteDirectoryRecursive(string $dir): void
    {
        exec('rm -rf ' . escapeshellarg($dir), $output, $exitCode);
        if ($exitCode !== 0) {
            $this->log->log(__METHOD__ . " - rm -rf failed for '$dir' (exit $exitCode)", LOG_ALERT);
        }
    }

}
