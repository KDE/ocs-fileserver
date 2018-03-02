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

class Profiles extends BaseController
{

    public function getIndex()
    {
        $status = 'active';
        $clientId = null;
        $ownerId = null;
        $search = null; // 3 or more strings
        $ids = null; // Comma-separated list
        $favoriteIds = array();
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

        $profiles = $this->models->profiles->getProfiles(
            $status,
            $clientId,
            $ownerId,
            $search,
            $ids,
            $favoriteIds,
            $sort,
            $perpage,
            $page
        );

        if (!$profiles) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }

        $this->_setResponseContent('success', $profiles);
    }

    public function getProfile()
    {
        $id = null;

        if (!empty($this->request->id)) {
            $id = $this->request->id;
        }

        $profile = $this->models->profiles->getProfile($id);

        if (!$profile) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }

        $this->_setResponseContent(
            'success',
            array('profile' => $profile)
        );
    }

    public function postProfile()
    {
        // Update profile or add new one

        if (!$this->_isAllowedAccess()) {
            $this->response->setStatus(403);
            throw new Flooer_Exception('Forbidden', LOG_NOTICE);
        }

        $id = null; // Auto generated
        $active = 1;
        $clientId = null;
        $ownerId = null;
        $name = null;
        $email = null;
        $homepage = null;
        $image = null;
        $description = null;

        if (!empty($this->request->client_id)) {
            $clientId = $this->request->client_id;
        }
        if (!empty($this->request->owner_id)) {
            $ownerId = $this->request->owner_id;
        }
        if (!empty($this->request->name)) {
            $name = mb_substr(strip_tags($this->request->name), 0, 255);
        }
        if (!empty($this->request->email)) {
            $email = $this->request->email;
        }
        if (!empty($this->request->homepage)) {
            $homepage = $this->request->homepage;
        }
        if (!empty($this->request->image)) {
            $image = $this->request->image;
        }
        if (isset($this->request->description)) {
            $description = strip_tags($this->request->description);
        }

        $errors = array();
        if (!$clientId) {
            $errors['client_id'] = 'Required';
        }
        if (!$ownerId) {
            $errors['owner_id'] = 'Required';
        }
        if (!$name) {
            $errors['name'] = 'Required';
        }
        if ($email && !$this->_isValidEmail($email)) {
            $errors['email'] = 'Invalid';
        }
        if ($homepage && !$this->_isValidUri($homepage)) {
            $errors['homepage'] = 'Invalid';
        }
        if ($image && !$this->_isValidUri($image)) {
            $errors['image'] = 'Invalid';
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

        $profile = $this->models->profiles->getProfileByClientIdAndOwnerId($clientId, $ownerId);

        if ($profile) {
            if ($profile->active) {
                $id = $profile->id;
            }
            else {
                $this->response->setStatus(403);
                throw new Flooer_Exception('Forbidden', LOG_NOTICE);
            }
        }
        else {
            $id = $this->models->profiles->generateId();
        }

        $this->models->profiles->$id = array(
            'active' => $active,
            'client_id' => $clientId,
            'owner_id' => $ownerId,
            'name' => $name,
            'email' => $email,
            'homepage' => $homepage,
            'image' => $image,
            'description' => $description
        );

        $profile = $this->models->profiles->getProfile($id);

        $this->_setResponseContent(
            'success',
            array('profile' => $profile)
        );
    }

    public function putProfile()
    {
        if (!$this->_isAllowedAccess()) {
            $this->response->setStatus(403);
            throw new Flooer_Exception('Forbidden', LOG_NOTICE);
        }

        $id = null;
        $name = null;
        $email = null;
        $homepage = null;
        $image = null;
        $description = null;

        if (!empty($this->request->id)) {
            $id = $this->request->id;
        }
        if (!empty($this->request->name)) {
            $name = mb_substr(strip_tags($this->request->name), 0, 255);
        }
        if (!empty($this->request->email)) {
            $email = $this->request->email;
        }
        if (!empty($this->request->homepage)) {
            $homepage = $this->request->homepage;
        }
        if (!empty($this->request->image)) {
            $image = $this->request->image;
        }
        if (isset($this->request->description)) {
            $description = strip_tags($this->request->description);
        }

        $profile = $this->models->profiles->$id;

        if (!$profile) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }
        else if (!$profile->active || $profile->client_id != $this->request->client_id) {
            $this->response->setStatus(403);
            throw new Flooer_Exception('Forbidden', LOG_NOTICE);
        }

        $updata = array();
        if ($name !== null) {
            $updata['name'] = $name;
        }
        if ($email !== null && $this->_isValidEmail($email)) {
            $updata['email'] = $email;
        }
        if ($homepage !== null && $this->_isValidUri($homepage)) {
            $updata['homepage'] = $homepage;
        }
        if ($image !== null && $this->_isValidUri($image)) {
            $updata['image'] = $image;
        }
        if ($description !== null) {
            $updata['description'] = $description;
        }

        $this->models->profiles->$id = $updata;

        $profile = $this->models->profiles->getProfile($id);

        $this->_setResponseContent(
            'success',
            array('profile' => $profile)
        );
    }

    public function deleteProfile()
    {
        if (!$this->_isAllowedAccess()) {
            $this->response->setStatus(403);
            throw new Flooer_Exception('Forbidden', LOG_NOTICE);
        }

        $id = null;

        if (!empty($this->request->id)) {
            $id = $this->request->id;
        }

        $profile = $this->models->profiles->$id;

        if (!$profile) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }
        else if (!$profile->active || $profile->client_id != $this->request->client_id) {
            $this->response->setStatus(403);
            throw new Flooer_Exception('Forbidden', LOG_NOTICE);
        }

        //unset($this->models->profiles->$id);
        $this->models->profiles->$id = array('active' => 0);

        $this->_setResponseContent('success');
    }

}
