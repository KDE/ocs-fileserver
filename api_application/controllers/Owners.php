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
     * GDPR-compliant hard-delete of all data belonging to an owner (Art. 17 DSGVO).
     *
     * - Profile row is hard-deleted.
     * - All files and collections are hard-deleted from the database.
     * - Collection directories are permanently removed from disk.
     * - Download/play log tables retain aggregate rows but owner_id is replaced
     *   with the configured sentinel value and IP addresses are nullified so that
     *   no personal reference remains.
     * - Favorites referencing this owner/user are hard-deleted.
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

        $clientId = $this->request->client_id;
        $ownerId  = $this->request->id;
        $deletedOwnerPlaceholder = $this->appConfig->gdpr['deleted_owner_id'];

        $this->logWithRequestId(__METHOD__ . " GDPR delete started (client:$clientId; owner:$ownerId)");

        // 1. Remove collections, their files, and files on disk
        $collections = $this->models->collections->fetchRowset(
            'WHERE client_id = :client_id AND owner_id = :owner_id',
            array(':client_id' => $clientId, ':owner_id' => $ownerId)
        );

        if ($collections) {
            $fileSystemAdapter = new FilesystemAdapter($this->appConfig);
            foreach ($collections as $collection) {
                // Delete thumbnail
                $thumbnail = $this->appConfig->general['thumbnailsDir'] . '/collection_' . $collection->id . '.jpg';
                if (is_file($thumbnail)) {
                    unlink($thumbnail);
                }

                // Permanently delete collection directory from disk
                $collectionPath = $this->appConfig->general['filesDir'] . '/' . $collection->name;
                if (is_dir($collectionPath)) {
                    $this->_deleteDirectoryRecursive($collectionPath);
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
        $this->models->files_downloaded->anonymizeByOwnerId($ownerId, $deletedOwnerPlaceholder);
        $this->models->files_downloaded_all->anonymizeByOwnerId($ownerId, $deletedOwnerPlaceholder);
        $this->models->files_downloaded_unique->anonymizeByOwnerId($ownerId, $deletedOwnerPlaceholder);
        $this->models->collections_downloaded->anonymizeByOwnerId($ownerId, $deletedOwnerPlaceholder);
        $this->models->media_played->anonymizeByOwnerId($ownerId, $deletedOwnerPlaceholder);

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

        $this->_setResponseContent('success');
    }

    /**
     * Recursively deletes a directory and all its contents.
     * Uses SPL iterators to avoid shell injection risks.
     */
    private function _deleteDirectoryRecursive(string $dir): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
        }
        rmdir($dir);
    }

}
