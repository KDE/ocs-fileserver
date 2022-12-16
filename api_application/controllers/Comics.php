<?php
/** @noinspection PhpUndefinedFieldInspection */
/** @noinspection PhpUnused */

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
class Comics extends BaseController
{
    /**
     * @return void
     * @throws Flooer_Exception
     */
    public function getExtractcomic()
    {
        $id = null;
        if (!empty($this->request->id)) {
            $id = $this->request->id;
        }

        if ($id) {
            $id = $this->models->files->getFileId($id);
        }

        $file = $this->models->files->$id;

        if (!$file) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }

        $this->log->log("Start Extract comic book (file: $file->id;)", LOG_NOTICE);


        $collectionId = $file->collection_id;

        if (!$collectionId) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Collection not found', LOG_NOTICE);
        }

        $testPath = $this->appConfig->general['comicsDir'] . '/' . $collectionId;

        $this->log->log("Create folders (collection: $testPath;)", LOG_NOTICE);

        if (!is_dir($this->appConfig->general['comicsDir'] . '/' . $collectionId) && !mkdir($this->appConfig->general['comicsDir'] . '/' . $collectionId)) {
            $this->response->setStatus(500);
            throw new Flooer_Exception('Failed to create collection folder', LOG_ALERT);
        }

        if (!is_dir($this->appConfig->general['comicsDir'] . '/' . $collectionId . '/' . $file->id) && !mkdir($this->appConfig->general['comicsDir'] . '/' . $collectionId . '/' . $file->id)) {
            $this->response->setStatus(500);
            throw new Flooer_Exception('Failed to create comic folder', LOG_ALERT);
        }

        $filePath = $this->appConfig->general['filesDir'] . '/' . $collectionId . '/';

        $comicPath = $this->appConfig->general['comicsDir'] . '/' . $collectionId . '/' . $file->id . '/';

        $this->log->log("Comic-Path: $comicPath;)", LOG_NOTICE);


        if ($this->endsWith($file->name, ".cbz")) {
            $zip = new ZipArchive();

            if ($zip->open($filePath . $file->name) === true) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    $fileinfo = pathinfo($filename);

                    $folderName = $fileinfo['dirname'];
                    if ($folderName == '.') {
                        $folderName = '';
                    }
                    $folderName = $this->normalizeString($folderName);

                    //$this->log->log("Comic-Page-Path: ".$filename, LOG_NOTICE);

                    if ($this->endsWith($zip->getNameIndex($i), '.jpg') || $this->endsWith($zip->getNameIndex($i), '.gif') || $this->endsWith($zip->getNameIndex($i), '.png') || $this->endsWith($zip->getNameIndex($i), '.webp')) {

                        copy("zip://" . $filePath . $file->name . "#" . $filename, $comicPath . $folderName . $fileinfo['basename']);
                    }

                }
                $zip->close();
            }
        } else {
            if ($this->endsWith($file->name, ".cbr")) {

                exec('rar e ' . $filePath . $file->name . ' ' . $comicPath);

            }
        }

        //normalize file names
        foreach (new DirectoryIterator($comicPath) as $fn) {

            $nameString = $fn->getFilename();
            if ($this->endsWith($nameString, '.jpg') || $this->endsWith($nameString, '.gif') || $this->endsWith($nameString, '.png') || $this->endsWith($nameString, '.webp')) {
                if ($nameString != $this->normalizeString($nameString)) {
                    $cmd = 'mv ' . '\'' . $comicPath . $nameString . '\'' . ' ' . '\'' . $comicPath . $this->normalizeString($nameString) . '\'';
                    //$this->log->log("Rename file: ".$cmd, LOG_NOTICE);

                    exec($cmd);

                    $nameString = $this->normalizeString($nameString);
                }

                //Convert webp to png
                if ($this->endsWith($nameString, '.webp')) {
                    $cmd = 'dwebp ' . '\'' . $comicPath . $nameString . '\'' . ' -o ' . '\'' . $comicPath . $nameString . '.png\'';
                    //$this->log->log("Rename file: ".$cmd, LOG_NOTICE);

                    exec($cmd);

                    unlink($comicPath . $nameString);

                }

            }

        }

        $this->log->log("Extract: Done", LOG_NOTICE);

        $this->_setResponseContent('success');
        exit;
    }

    /**
     * @param $haystack
     * @param $needle
     *
     * @return bool
     */
    private function endsWith($haystack, $needle): bool
    {
        return $needle === "" || substr(strtolower($haystack), -strlen($needle)) === strtolower($needle);
    }

    /**
     * @param string $str
     *
     * @return array|string|string[]
     */
    public static function normalizeString(string $str = '')
    {
        $str = strip_tags($str);
        $str = preg_replace('/[\r\n\t ]+/', ' ', $str);
        $str = preg_replace('/[\"\*\/\:\<\>\?\'\|]+/', ' ', $str);
        //$str = strtolower($str);
        $str = html_entity_decode($str, ENT_QUOTES, "utf-8");
        $str = htmlentities($str, ENT_QUOTES, "utf-8");
        $str = preg_replace("/(&)([a-z])([a-z]+;)/i", '$2', $str);
        $str = str_replace(' ', '-', $str);
        $str = rawurlencode($str);

        return str_replace('%', '-', $str);
    }

    /**
     * @return void
     * @throws Flooer_Exception
     */
    public function getExtractebook()
    {
        $id = null;
        if (!empty($this->request->id)) {
            $id = $this->request->id;
        }

        if ($id) {
            $id = $this->models->files->getFileId($id);
        }

        $file = $this->models->files->$id;

        if (!$file) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }

        $this->log->log("Start Extract ebook (file: $file->id;)", LOG_NOTICE);


        $collectionId = $file->collection_id;

        if (!$collectionId) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Collection not found', LOG_NOTICE);
        }

        $testPath = $this->appConfig->general['ebooksDir'] . '/' . $collectionId;

        $this->log->log("Create folders (collection: $testPath;)", LOG_NOTICE);

        if (!is_dir($this->appConfig->general['ebooksDir'] . '/' . $collectionId) && !mkdir($this->appConfig->general['ebooksDir'] . '/' . $collectionId, 0777)) {
            $this->response->setStatus(500);
            throw new Flooer_Exception('Failed to create collection folder', LOG_ALERT);
        }

        if (!is_dir($this->appConfig->general['ebooksDir'] . '/' . $collectionId . '/' . $file->id) && !mkdir($this->appConfig->general['ebooksDir'] . '/' . $collectionId . '/' . $file->id)) {
            $this->response->setStatus(500);
            throw new Flooer_Exception('Failed to create ebooks folder', LOG_ALERT);
        }

        $filePath = $this->appConfig->general['filesDir'] . '/' . $collectionId . '/';

        $ebookPath = $this->appConfig->general['ebooksDir'] . '/' . $collectionId . '/' . $file->id . '/';

        $this->log->log("Ebook-Path: $ebookPath;)", LOG_NOTICE);


        if ($this->endsWith($file->name, ".epub")) {
            $zip = new ZipArchive();

            if ($zip->open($filePath . $file->name) === true) {
                $zip->extractTo($ebookPath);
                $zip->close();
            }
        }

        $this->log->log("Extract: Done", LOG_NOTICE);

        $this->_setResponseContent('success');
        exit;
    }

    /**
     * @return void
     * @throws Flooer_Exception
     */
    public function getToc()
    {
        $id = null;
        if (!empty($this->request->id)) {
            $id = $this->request->id;
        }

        if ($id) {
            $id = $this->models->files->getFileId($id);
        }

        $file = $this->models->files->$id;

        if (!$file) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }

        $this->log->log("Start get comic book toc (file: $file->id;)", LOG_NOTICE);


        $collectionId = $file->collection_id;

        if (!$collectionId) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Collection not found', LOG_NOTICE);
        }

        //ebook or epub?
        if ($this->endsWith($file->name, '.epub')) {
            $ebook = new EPubReader();
            $comicPath = $this->appConfig->general['ebooksDir'] . '/' . $collectionId . '/' . $file->id;
            $ebook->init($comicPath);

            //$this->log->log("Ebook Object:" . print_r($ebook, true), LOG_NOTICE);

            $toc = $ebook->getTOC();

        } else {
            $comicPath = $this->appConfig->general['comicsDir'] . '/' . $collectionId . '/' . $file->id;

            $this->log->log("Comic-Path: $comicPath;)", LOG_NOTICE);

            $tocFile = $comicPath . '/toc.txt';

            if (file_exists($tocFile)) {
                //Retrieve the data from our text file.
                $fileContents = file_get_contents($tocFile);

                //Convert the JSON string back into an array.
                $toc = json_decode($fileContents, true);

                $this->log->log("Read from Toc-File: $tocFile", LOG_NOTICE);
            } else {
                $toc = array();

                foreach (new DirectoryIterator($comicPath) as $fn) {

                    $nameString = $fn->getFilename();
                    if ($this->endsWith($nameString, '.jpg') || $this->endsWith($nameString, '.gif') || $this->endsWith($nameString, '.png') || $this->endsWith($nameString, '.webp')) {
                        $toc[] = $nameString;
                    }
                }

                natcasesort($toc);
                $toc = array_values($toc);


                //Encode the array into a JSON string.
                $encodedString = json_encode($toc);

                //Save the JSON string to a text file.
                file_put_contents($tocFile, $encodedString);

                $this->log->log("Read from Folder", LOG_NOTICE);
            }


            $this->log->log("Done, found " . count($toc) . " pages", LOG_NOTICE);
        }


        $this->_setResponseContent('success', array('files' => $toc));
    }

    /**
     * @return void
     * @throws Flooer_Exception
     */
    public function getPage()
    {
        $id = null;
        if (!empty($this->request->id)) {
            $id = $this->request->id;
        }

        $filename = null;
        if (!empty($this->request->filename)) {
            $filename = $this->request->filename;
        }

        if ($id) {
            $id = $this->models->files->getFileId($id);
        }

        $file = $this->models->files->$id;

        if (!$file) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }

        $this->log->log("Start show book page (file: $file->id;)", LOG_NOTICE);


        $collectionId = $file->collection_id;

        if (!$collectionId) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Collection not found', LOG_NOTICE);
        }


        //ebook or epub?
        if ($this->endsWith($file->name, '.epub')) {
            $ebook = new EPubReader();
            $comicPath = $this->appConfig->general['ebooksDir'] . '/' . $collectionId . '/' . $file->id . '/';
            $ebook->init($comicPath);

            //$this->log->log("Ebook Object:" . print_r($ebook, true), LOG_NOTICE);
            $pagePath = $comicPath . $ebook->getOPFDir() . '/' . $filename;

            $page = fopen($pagePath, 'rb');

            if (!$page) {
                $this->log->log("Page not found:" . $pagePath, LOG_NOTICE);
            } else {

                //$serverUri = $this->appConfig->general['ebookUri'] . '/api/files/pageitem?id=' . $file->id . '&filename=' . $filename . '/';

                //replace href links with links to /api/files/pageitem?FILE_ID&filename=FILENAME
                /*$result = "";
                # read the contents in
                $file_contents = fgets($page, filesize($pagePath));

                while ($file_contents) {
                    # apply the translation
                    $result .= str_replace(" href='", " href='".$serverUri, $file_contents);
                }*/

                header('Content-type: text/html');
                header('Access-Control-Allow-Origin: *');
                fpassthru($page);
                //print_r($result);
            }

        } else {

            $comicPath = $this->appConfig->general['comicsDir'] . '/' . $collectionId . '/' . $file->id . '/';

            $this->log->log("Comic-Path: " . $comicPath . $filename, LOG_NOTICE);

            $page = fopen($comicPath . $filename, 'rb');

            if ($this->endsWith($filename, ".jpg")) {
                //file_put_contents($saveName,$zip->getFromIndex(0));
                //imagejpeg($zip->getStream($page), "cache/test.jpg", 75);
                header('Content-type: image/jpeg');
                fpassthru($page);
            } else {
                if ($this->endsWith($filename, ".png")) {
                    //imagepng(($zip->getStream($page), $saveName);
                    header('Content-type: image/png');
                    fpassthru($page);
                } else {
                    if ($this->endsWith($filename, ".gif")) {
                        //imagegif($zip->getStream($page), $saveName);
                        header('Content-type: image/gif');
                        fpassthru($page);
                    } else {
                        if ($this->endsWith($filename, ".webp")) {
                            //imagegif($zip->getStream($page), $saveName);
                            header('Content-type: image/webp');
                            fpassthru($page);
                        }
                    }
                }
            }
            $this->log->log("Done", LOG_NOTICE);

            $this->_setResponseContent('success');
        }
        exit;
    }

    /**
     * @return void
     * @throws Flooer_Exception
     */
    public function getPageitem()
    {
        $id = null;
        if (!empty($this->request->id)) {
            $id = $this->request->id;
        }

        $filename = null;
        if (!empty($this->request->filename)) {
            $filename = $this->request->filename;
        }

        if ($id) {
            $id = $this->models->files->getFileId($id);
        }

        $file = $this->models->files->$id;

        if (!$file) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Not found', LOG_NOTICE);
        }

        $this->log->log("Start show book page item (file: $file->id, filename: $filename)", LOG_NOTICE);


        $collectionId = $file->collection_id;

        if (!$collectionId) {
            $this->response->setStatus(404);
            throw new Flooer_Exception('Collection not found', LOG_NOTICE);
        }


        //ebook or epub?
        if ($this->endsWith($file->name, '.epub')) {
            $ebook = new EPubReader();
            $comicPath = $this->appConfig->general['ebooksDir'] . '/' . $collectionId . '/' . $file->id . '/';
            $ebook->init($comicPath);

            //$this->log->log("Ebook Object:" . print_r($ebook, true), LOG_NOTICE);
            $pagePath = $comicPath . $ebook->getOPFDir() . '/' . $filename;

            $page = fopen($pagePath, 'rb');

            if (!$page) {
                $this->log->log("Page Item not found:" . $pagePath, LOG_NOTICE);
            } else {
                header('Content-type: ' . mime_content_type($pagePath));
                fpassthru($page);
            }
        }

        exit;
    }

    /**
     * @param $baseDir
     * @param $dir
     * @param $fileNameList
     *
     * @return mixed|void
     */
    protected function listFolderFiles($baseDir, $dir, $fileNameList)
    {
        $ffs = scandir($baseDir . '/' . $dir);

        unset($ffs[array_search('.', $ffs, true)]);
        unset($ffs[array_search('..', $ffs, true)]);

        // prevent empty ordered elements
        if (count($ffs) < 1) {
            return;
        }

        foreach ($ffs as $ff) {
            $nameString = $ff;
            if ($this->endsWith($nameString, '.jpg') || $this->endsWith($nameString, '.gif') || $this->endsWith($nameString, '.png') || $this->endsWith($nameString, '.webp')) {
                $fileNameList[] = $dir . '/' . $ff;
            }


            if (is_dir($baseDir . '/' . $dir . '/' . $ff)) {
                $fileNameList = $this->listFolderFiles($baseDir, $dir . '/' . $ff, $fileNameList);
            }
        }

        return $fileNameList;
    }

}