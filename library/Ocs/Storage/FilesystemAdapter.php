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

    public function fixFilename(string $name, string $collectionPath): string
    {
        $fullPath = $collectionPath . DIRECTORY_SEPARATOR . $name;

        if (is_file($fullPath)) {
            $timestamp = date('YmdHis');
            $pathInfo = pathinfo($name);

            $name = $pathInfo['filename'] . '-' . $timestamp;
            if (isset($pathInfo['extension'])) {
                $name .= '.' . $pathInfo['extension'];
            }
        }

        return $name;
    }

    public function prepareCollectionPath(string $collectionName, string $filePath): bool
    {
        return !is_dir($filePath . DIRECTORY_SEPARATOR . $collectionName) && mkdir($filePath . DIRECTORY_SEPARATOR . $collectionName);
    }

    /** Return true if dir exists and is writeable, otherwise it tries to create the path
     *
     * @param string $dir
     * @return bool
     */
    public function testAndCreate(string $dir): bool
    {
        if (is_dir($dir)) {
            if (!is_writable($dir)) {
                return chmod($dir, 0755);
            }

            return true;
        }

        return mkdir($dir, 0755, true);
    }

    public function moveFile($from, $to): bool
    {
        return is_file($from) && $this->testAndCreate(dirname($to)) && copy($from, $to) && unlink($from);
    }

    public function copyFile($from, $to): bool
    {
        return is_file($from) && $this->testAndCreate(dirname($to)) && copy($from, $to);
    }

    public function isFile($from): bool
    {
        return is_file($from);
    }

    public function isDir($from) {
        return is_dir($from);
    }

    public function renameDir($from, $to) {
        return is_dir($from) && is_dir(dirname($to)) && rename($from, $to);
    }

}