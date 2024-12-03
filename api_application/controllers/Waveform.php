<?php
/**
 * file server - part of Opendesktop.org platform project <https://www.opendesktop.org>.
 *
 * Copyright (c) 2016 pling GmbH.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
class Waveform extends BaseController
{

    /**
     * @throws Flooer_Exception
     */
    public function getFile() {
        $id = null;

        if (!empty($this->request->id)) {
            $id = $this->request->id;
        }
        $cache = $this->request->cache ?? true;

        $file = $this->models->files->getFile($id);

        // check file pre-conditions
        if (!$file) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }
        if (!$file->active) {
            $this->log->log("File is inactive (file: $file->id)", LOG_NOTICE);
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }
        if (substr($file->type, 0, 5) !== "audio") {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not an audio file (' . $file->id . ')', LOG_NOTICE);
        }

        $collectionId = $file->collection_id;
        $collection = $this->models->collections->$collectionId;

        // check collection pre-conditions
        if (!$collection) {
            $this->log->log("Collection not found (file: $file->id)", LOG_NOTICE);
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }
        if (!$collection->active) {
            $this->log->log("Collection is inactive (file: $file->id)", LOG_NOTICE);
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }

        $collectionDir = $this->appConfig->general['filesDir'] . '/' . $collection->name;
        $filePath = $collectionDir . '/' . $file->name;
        $fileName = $file->name . '.json';
        $fileType = 'application/json';
        $fileJsonWaveform = $filePath . '.json';

        if ($cache and file_exists($fileJsonWaveform)) {
            if (time() - filemtime($fileJsonWaveform) < 86400) {
                $this->_sendFile($fileJsonWaveform, $fileName, $fileType, filesize($fileJsonWaveform), true, false);
            }
        }

        $this->_generateWaveForm($filePath, $fileJsonWaveform);
        if (file_exists($fileJsonWaveform)) {
            $this->_sendFile($fileJsonWaveform, $fileName, $fileType, filesize($fileJsonWaveform), true, false);
        }

        $this->log->log("Waveform file not generated (file: $file->id)", LOG_NOTICE);
        $this->response->setStatus(404);
        throw new Flooer_Exception('File Not found', LOG_NOTICE);
    }

}