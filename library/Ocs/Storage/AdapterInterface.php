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

interface AdapterInterface
{
    /**
     * @param string $from
     * @param string $to
     *
     * @return bool
     */
    public function moveUploadedFile(string $from, string $to): bool;

    public function fixFilename(string $name, string $collectionName): string;

    public function prepareCollectionPath(string $collectionName): bool;

    public function testAndCreate(string $dir): bool;

    public function moveFile($from, $to): bool;

    public function copyFile($from, $to): bool;

}