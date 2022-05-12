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

namespace Ocs\Storage;

class FilesystemAdapter implements AdapterInterface
{

    private object $appConfig;

    public function __construct(object $appConfig)
    {
        $this->appConfig = $appConfig;
    }

    /**
     * @inheritDoc
     */
    public function moveUploadedFile(string $from, string $to): bool
    {
        return move_uploaded_file($from, $to);
    }

    public function fixFilename(string $name, string $collectionName): string
    {
        if (is_file($this->appConfig->general['filesDir'] . '/' . $collectionName . '/' . $name)) {
            $fix = date('YmdHis');
            if (preg_match("/^([^.]+)(\..+)/", $name, $matches)) {
                $name = $matches[1] . '-' . $fix . $matches[2];
            } else {
                $name = $name . '-' . $fix;
            }
        }

        return $name;
    }

    public function prepareCollectionPath(string $collectionName): bool
    {
        return !is_dir($this->appConfig->general['filesDir'] . '/' . $collectionName) && mkdir($this->appConfig->general['filesDir'] . '/' . $collectionName);
    }

    public function testAndCreate(string $dir): bool
    {
        if (is_dir($dir)) {
            return true;
        }

        return mkdir($dir, null, true);
    }

    public function moveFile($from, $to): bool
    {
        return is_file($from) && copy($from, $to) && unlink($from);
    }
}