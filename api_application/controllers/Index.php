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

class Index extends BaseController
{

    public function getIndex()
    {
        if ($this->_isAllowedAccess()) {
            $this->_setResponseContent('success');
            return;
        }

        $this->response->setStatus(403);
        throw new Flooer_Exception('Forbidden', LOG_NOTICE);
    }

    public function getHealth() {
        $filesystem = new FilesystemAdapter($this->appConfig);
        if ($filesystem->isFile($this->appConfig->general['filesDir'] . '/empty')) {
            $this->_setResponseContent('success',['message' => "I'm alive."]);
            return;
        }
        $this->response->setStatus(500);
        throw new Flooer_Exception('Internal Server Error: Resource not accessible.', LOG_NOTICE);
    }

}
