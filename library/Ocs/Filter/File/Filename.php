<?php
/**
 * Image media server - part of Opendesktop.org platform project <https://www.opendesktop.org>.
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

namespace Ocs\Filter\File;

use Laminas\Filter\AbstractFilter;

class Filename extends AbstractFilter
{
    protected $beautify;

    public function __construct($options = [])
    {
        $this->beautify = true;
        if (isset($options['beautify'])) {
            $this->beautify = $options['beautify'];
        }

    }

    /**
     * @inheritDoc
     */
    public function filter($value)
    {
        if (is_string($value)) {
            return $this->filter_filename($value);
        }

        if (false == isset($value['name'])) {
            return $value;
        }

        $value['name'] = $this->filter_filename($value['name']);

        return $value;
    }

    /**
     * @param string $filename
     *
     * @return string
     */
    private function filter_filename(string $filename): string
    {
        // try to prevent filesystem traversal attacks
        $filename = basename($filename);
        // sanitize filename
        $filename = preg_replace('~
        [<>:"/\\|?*]|            # file system reserved https://en.wikipedia.org/wiki/Filename#Reserved_characters_and_words
        [\x00-\x1F]|             # control characters http://msdn.microsoft.com/en-us/library/windows/desktop/aa365247%28v=vs.85%29.aspx
        [\x7F\xA0\xAD]|          # non-printing characters DEL, NO-BREAK SPACE, SOFT HYPHEN
        [#\[\]@!$&\'()+,;=]|     # URI reserved https://tools.ietf.org/html/rfc3986#section-2.2
        [{}^\~`]                 # URL unsafe characters https://www.ietf.org/rfc/rfc1738.txt
        ~x', '-', $filename);
        // avoids ".", ".." or ".hiddenFiles"
        $filename = ltrim($filename, '.-');
        // optional beautification
        if ($this->beautify) {
            $filename = $this->beautify_filename($filename);
        }
        // maximize filename length to 255 bytes http://serverfault.com/a/9548/44086
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $filename = mb_strcut(pathinfo($filename, PATHINFO_FILENAME), 0, 255 - ($ext ? strlen($ext) + 1 : 0), mb_detect_encoding($filename)) . ($ext ? '.' . $ext : '');

        return $filename;
    }

    /**
     * @param string $filename
     *
     * @return string
     */
    private function beautify_filename($filename): string
    {
        // reduce consecutive characters
        $filename = preg_replace(array(// "file   name.zip" becomes "file-name.zip"
                                       '/ +/',
                                       // "file___name.zip" becomes "file-name.zip"
                                       '/_+/',
                                       // "file---name.zip" becomes "file-name.zip"
                                       '/-+/'), '-', $filename);
        $filename = preg_replace(array(// "file--.--.-.--name.zip" becomes "file.name.zip"
                                       '/-*\.-*/',
                                       // "file...name..zip" becomes "file.name.zip"
                                       '/\.{2,}/'), '.', $filename);
        // lowercase for windows/unix interoperability http://support.microsoft.com/kb/100625
        //$filename = mb_strtolower($filename, mb_detect_encoding($filename));
        // ".file-name.-" becomes "file-name"
        $filename = trim($filename, '.-');

        return $filename;
    }

    /**
     * @return mixed
     */
    public function getBeautify()
    {
        return $this->beautify;
    }

    /**
     * @param mixed $beautify
     */
    public function setBeautify($beautify): void
    {
        $this->beautify = $beautify;
    }

}