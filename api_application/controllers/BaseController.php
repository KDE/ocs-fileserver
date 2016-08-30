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

class BaseController extends Flooer_Controller
{

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

    protected function _isAllowedAccess()
    {
        if (!empty($this->request->client_id)
            && !empty($this->request->secret)
        ) {
            $clients = parse_ini_file('configs/clients.ini', true);
            if (isset($clients[$this->request->client_id])
                && $clients[$this->request->client_id]['secret'] === $this->request->secret
            ) {
                return true;
            }
        }
        return false;
    }

    protected function _isValidUri($uri)
    {
        return Flooer_Utility_Validation::isUri($uri);
    }

    protected function _isValidEmail($email)
    {
        return Flooer_Utility_Validation::isEmail($email);
    }

    protected function _isValidPerpageNumber($number)
    {
        if (Flooer_Utility_Validation::isDigit($number)
            && $number > 0
            && $number <= $this->appConfig->general['perpageMax']
        ) {
            return true;
        }
        return false;
    }

    protected function _isValidPageNumber($number)
    {
        if (Flooer_Utility_Validation::isDigit($number) && $number > 0) {
            return true;
        }
        return false;
    }

    protected function _getFavoriteIds($clientId, $userId)
    {
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

    protected function _generateTorrent($source, $torrent)
    {
        exec('ctorrent'
            . ' -t'
            . ' -u "' . $this->appConfig->general['announceUri'] . '"'
            . ' -s "' . $torrent . '"'
            . ' "' . $source . '"'
        );
    }

    protected function _sendFile($filepath, $filename, $type, $size, $attachment = false, $headeronly = false)
    {
        //$this->dispatch->setFilterOutput(false);
        //$this->dispatch->setSendResponse(false);

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
            $length = 1024 * 8;
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

        exit;
    }

}
