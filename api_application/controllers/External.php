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

class External extends BaseController
{

    public function getIndex()
    {
        $this->response->setStatus(403);
        throw new Flooer_Exception('Forbidden', LOG_NOTICE);
    }

    public function postResource()
    {
        $resource = null;
        if (!empty($this->request->uri)) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->request->uri,
                CURLOPT_HEADER => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60
            ));
            $resource = curl_exec($curl);
            curl_close($curl);
        }

        if (!$resource) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }

        $decodedResource = null;
        if (!empty($this->request->type)
            && $this->request->type == 'json'
        ) {
            $decodedResource = json_decode($resource);
        }
        else {
            libxml_use_internal_errors(true);
            $decodedResource = simplexml_load_string($resource);
        }

        $this->_setResponseContent(
            'success',
            array('resource' => $decodedResource)
        );
    }

}
