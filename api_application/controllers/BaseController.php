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

/**
 * Class BaseController
 *
 * @property RedisCache redisCache
 * @property object appConfig
 * @property Flooer_Log log
 * @property Flooer_Http_Response response
 * @property ModelContainer models
 * @property OcsModel modelOcs
 * @property Flooer_Http_Request request
 *
 */
class BaseController extends Flooer_Controller
{

    protected function _sendFile($filepath, $filename, $type, $size, $attachment = false, $headeronly = false)
    {
        $rangeBegin = 0;
        $rangeEnd = $size - 1; // Content-Range: bytes 0-1023/1024
        $disposition = 'inline';
        if ($attachment) {
            $disposition = 'attachment';
        }

        $this->response->setHeader('Access-Control-Allow-Headers', 'Range');
        $this->response->setHeader(
            'Access-Control-Expose-Headers',
            'Accept-Ranges, Content-Length, Content-Range'
        );
        $this->response->setHeader('Accept-Ranges', 'bytes');
        $this->response->setHeader('Cache-Control', 'public, must-revalidate, max-age=0');
        $this->response->setHeader('Pragma', 'no-cache');
        $this->response->setHeader('Last-Modified', date('r', filemtime($filepath)));
        $this->response->setHeader('Content-Type', $type);
        $this->response->setHeader('Content-Length', $size);
        $this->response->setHeader(
            'Content-Disposition',
            $disposition . '; filename="' . $filename . '"'
        );

        if (isset($this->server->HTTP_RANGE)) {
            if (preg_match(
                '/bytes=\h*(\d+)-(\d*)[\D.*]?/i',
                $this->server->HTTP_RANGE,
                $matches
            )) {
                $rangeBegin = (int) $matches[1];
                if (!empty($matches[2])) {
                    $rangeEnd = (int) $matches[2];
                }
            }
            $this->response->setStatus(206);
            $this->response->setHeader(
                'Content-Range',
                'bytes ' . $rangeBegin . '-' . $rangeEnd . '/' . $size
            );
            $this->response->setHeader(
                'Content-Length',
                $rangeEnd - $rangeBegin + 1
            );
        }

        $this->response->send();

        if (!$headeronly) {
            if (ob_get_level()) {
                ob_end_flush();
            }
            $length = 1024 * 512; // Please do not specify an extremely large size
            $cur = $rangeBegin;
            $end = $rangeEnd + 1;
            $fp = fopen($filepath, 'rb');
            fseek($fp, $cur, 0);
            while (!feof($fp)
                && $cur < $end
                && connection_status() == 0
            ) {
                echo fread($fp, min($length, $end - $cur));
                $cur += $length;
                ob_flush();
                flush();
            }
            fclose($fp);
        }

        if (php_sapi_name() == 'fpm-fcgi') {
            fastcgi_finish_request();
        }

        exit;
    }

    protected function _setResponseContent($status, array $data = null)
    {
        $status = strtolower($status);
        if (!in_array($status, array('success', 'error', 'failure'))) {
            $status = 'unknown';
        }

        $content = array('status' => $status);
        if ($data) {
            $content += $data;
        }

        $format = $this->appConfig->general['format'];
        if (!empty($this->request->format)) {
            $format = $this->request->format;
        }

        if (!empty($this->request->ignore_status_code)) {
            $this->response->setStatus(200);
        }

        switch (strtolower($format)) {
            case 'json':
                $this->view->content = json_encode($content);
                if (!empty($this->request->callback)) {
                    $this->view->jsonpCallback = htmlspecialchars(
                        $this->request->callback,
                        ENT_QUOTES
                    );
                    $this->view->setFile('content.js');
                    break;
                }
                $this->view->setFile('content.json');
                break;
            case 'xml':
                // Continue to default
            default:
                $this->view->content = $this->_convertXmlDom($content, 'response')->saveXML();
                $this->view->setFile('content.xml');
                break;
        }
    }

    protected function _convertXmlDom($values, $tagName = 'data', DOMNode &$dom = null, DOMElement &$element = null)
    {
        if (!$dom) {
            $dom = new DomDocument(
                '1.0',
                $this->dispatch->getApplication()->getConfig('encoding')
            );
        }

        if (!$element) {
            $element = $dom->appendChild($dom->createElement($tagName));
        }

        if (is_array($values) || is_object($values)) {
            foreach ($values as $key => $value) {
                if (ctype_digit((string) $key)) {
                    $key = $element->tagName . '_' . $key;
                }
                $childElement = $element->appendChild($dom->createElement($key));
                $this->_convertXmlDom($value, $key, $dom, $childElement);
            }
        }
        else {
            $element->appendChild($dom->createTextNode($values));
        }

        return $dom;
    }

    protected function _getDownloadSecret($clientId)
    {
        $clients = parse_ini_file('configs/clients.ini', true);
        if (isset($clients[$clientId])) {
            return $clients[$clientId]['downloadSecret'];
        }
        return '';
    }

    protected function _isAllowedAccess(): bool {
        $this->logWithRequestId(__METHOD__ . " - {$this->request->client_id}; {$this->request->secret}");
        if (!empty($this->request->client_id)
            && !empty($this->request->secret)
        ) {
            $clients = parse_ini_file('configs/clients.ini', true);
            $this->logWithRequestId(__METHOD__ . " - {$this->request->client_id}; " . print_r($clients[$this->request->client_id], true));
            if (isset($clients[$this->request->client_id])
                && $clients[$this->request->client_id]['secret'] === $this->request->secret
            ) {
                $this->logWithRequestId(__METHOD__ . " - return true");
                return true;
            }
        }
        $this->logWithRequestId(__METHOD__ . " - return false");
        return false;
    }

    protected function _isValidUri($uri): bool {
        return Flooer_Utility_Validation::isUri($uri);
    }

    protected function _isValidEmail($email): bool {
        return Flooer_Utility_Validation::isEmail($email);
    }

    protected function _isValidPerpageNumber($number): bool {
        if (Flooer_Utility_Validation::isDigit($number)
            && $number > 0
            && $number <= $this->appConfig->general['perpageMax']
        ) {
            return true;
        }
        return false;
    }

    protected function _isValidPageNumber($number): bool {
        if (Flooer_Utility_Validation::isDigit($number) && $number > 0) {
            return true;
        }
        return false;
    }

    protected function _getFavoriteIds($clientId, $userId): array {
        $ids = array();

        $favoriteOwners = $this->models->favorites->getFavoriteOwners(
            $clientId,
            $userId
        );
        if ($favoriteOwners) {
            $ids['ownerIds'] = array();
            foreach ($favoriteOwners as $favoriteOwner) {
                $ids['ownerIds'][] = $favoriteOwner->owner_id;
            }
        }

        $favoriteCollections = $this->models->favorites->getFavoriteCollections(
            $clientId,
            $userId
        );
        if ($favoriteCollections) {
            $ids['collectionIds'] = array();
            foreach ($favoriteCollections as $favoriteCollection) {
                $ids['collectionIds'][] = $favoriteCollection->collection_id;
            }
        }

        $favoriteFiles = $this->models->favorites->getFavoriteFiles(
            $clientId,
            $userId
        );
        if ($favoriteFiles) {
            $ids['fileIds'] = array();
            foreach ($favoriteFiles as $favoriteFile) {
                $ids['fileIds'][] = $favoriteFile->file_id;
            }
        }

        return $ids;
    }

    protected function _generateArchive($source, $archive)
    {
        exec('tar'
            . ' -czf "' . $archive . '"'
            . ' "' . $source . '"'
        );
    }

    protected function _generateZsync($source, $zsync, $uri)
    {
        exec('zsyncmake'
            . ' -u "' . $uri . '"'
            . ' -o "' . $zsync . '"'
            . ' "' . $source . '"'
        );
    }

    protected function _detectLinkInTags($tagsString): string {
        $link = '';
        $tags = explode(',', $tagsString);
        foreach ($tags as $tag) {
            $tag = trim($tag);
            if (strpos($tag, 'link##') === 0) {
                $link = urldecode(str_replace('link##', '', $tag));
                break;
            }
        }
        return $link;
    }

    protected function _detectMimeTypeFromUri($uri)
    {
        $mimeTypes = array(
          'txt'  => 'text/plain',
          'htm'  => 'text/html',
          'html' => 'text/html',
          'php'  => 'text/html',
          'css'  => 'text/css',
          'js'   => 'application/javascript',
          'json' => 'application/json',
          'xml'  => 'application/xml',
          'swf'  => 'application/x-shockwave-flash',
          // images
          'png'  => 'image/png',
          'jpe'  => 'image/jpeg',
          'jpeg' => 'image/jpeg',
          'jpg'  => 'image/jpeg',
          'gif'  => 'image/gif',
          'bmp'  => 'image/bmp',
          'ico'  => 'image/vnd.microsoft.icon',
          'tiff' => 'image/tiff',
          'tif'  => 'image/tiff',
          'svg'  => 'image/svg+xml',
          'svgz' => 'image/svg+xml',
          // archives
          'tar'  => 'application/x-tar',
          'tgz'  => 'application/tar+gzip',
          'gz'   => 'application/x-gzip',
          'bz2'   => 'application/x-bzip2',
          'xz'   => 'application/x-xz',
          'zip'  => 'application/zip',
          '7z'   => 'application/x-7z-compressed',
          'rar'  => 'application/x-rar-compressed',
          'exe'  => 'application/x-msdownload',
          'msi'  => 'application/x-msdownload',
          'cab'  => 'application/vnd.ms-cab-compressed',
          // audio/video
          'aac'  => 'audio/aac',
          'm4a'  => 'audio/mp4',
          'mp3'  => 'audio/mpeg',
          'qt'   => 'video/quicktime',
          'mov'  => 'video/quicktime',
          'mp4'  => 'video/mp4',
          'm4v'  => 'video/mp4',
          'ogv'  => 'video/ogg',
          'flv'  => 'video/x-flv',
          // adobe
          'pdf'  => 'application/pdf',
          'psd'  => 'image/vnd.adobe.photoshop',
          'ai'   => 'application/postscript',
          'eps'  => 'application/postscript',
          'ps'   => 'application/postscript',
          // ms office
          'doc'  => 'application/msword',
          'rtf'  => 'application/rtf',
          'xls'  => 'application/vnd.ms-excel',
          'ppt'  => 'application/vnd.ms-powerpoint',
          // open office
          'odt'  => 'application/vnd.oasis.opendocument.text',
          'ods'  => 'application/vnd.oasis.opendocument.spreadsheet'
        );

        $uriParts = explode('.', $uri);
        $ext = strtolower(array_pop($uriParts));

        if (array_key_exists($ext, $mimeTypes)) {
            return $mimeTypes[$ext];
        }
        else {
            $ch = curl_init($uri);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_NOBODY, 1);
            curl_exec($ch);
            return curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        }
    }

    protected function _detectFilesizeFromUri($uri)
    {
        static $regex = '/^Content-Length: *+\K\d++$/im';
        if (!$fp = @fopen($uri, 'rb')) {
            return false;
        }
        if (isset($http_response_header)
            && preg_match($regex, implode("\n", $http_response_header), $matches)
        ) {
            return (int)$matches[0] > 0 ? (int)$matches[0] : 1;
        }
        return strlen(stream_get_contents($fp));
    }

    protected function getRemoteFileInfo($url): array {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_NOBODY, TRUE);
        $data = curl_exec($ch);
        $fileSize = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        $fileType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $httpResponseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'fileExists' => (int)$httpResponseCode == 200,
            'fileSize'   => (int)$fileSize,
            'fileType'   => $fileType,
        ];
    }

    protected function getHost(): string {
        $host = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null);
        $host = isset($host) ? $host : $_SERVER['SERVER_NAME'];

        return mb_strimwidth($host,0,255);
    }

    protected function getScheme(): string {
        $scheme = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? $_SERVER['HTTP_X_FORWARDED_PROTO'] : null;
        $scheme = isset($scheme) ? $scheme : (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : null);
        $scheme = isset($scheme) ? $scheme : (isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : null);
        $scheme = isset($scheme) ? $scheme : (isset($_SERVER['SERVER_PORT']) AND $_SERVER['SERVER_PORT'] == '443' ? 'https' : 'http');

        return mb_strimwidth($scheme,0,5);
    }

    protected function getRemoteIpAddress(): string {
        $ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']) : $_SERVER['REMOTE_ADDR'];

        if (is_array($ip)) {
            return mb_strimwidth($ip[0],0,39);
        }

        if (false === filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return '0.0.0.0';
        }
        return mb_strimwidth($ip,0,39);
    }

    protected function getRequestId(): string {
        $request_id = $_SERVER['UNIQUE_ID'] ?? ($_SERVER['HTTP_X_REQUEST_ID'] ?? null);

        return mb_strimwidth($request_id, 0, 25);
    }

    protected function logWithRequestId($message, $priority = null): bool {
        return $this->log->log($message . "; request id: {$this->getRequestId()}", $priority);
    }

    protected function _generateWaveForm($src, $target): int {
        $output = [];
        $code = 0;
        $command = "/bin/bash -c 'audiowaveform -i $src -o $target -z 256 -b 16'";
        $this->log->log(__METHOD__ . " :: system($command)");
        $output = system($command, $code);
        $this->log->log(__METHOD__ . ' :: ' . $output . '(' . $code . ')');

        return $code;
    }
}
