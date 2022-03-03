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

namespace Ocs\Url;

use DateTime;
use Exception;

class UrlSigner
{

    /**
     * Sign a URL
     *
     * @param string $url
     * @param string $private_key
     * @param int    $expireMin
     *
     * @return string Signed URL
     * @throws Exception
     */
    public static function getSignedUrl(string $url, string $private_key, int $expireMin = 30): string
    {
        $join = parse_url($url, PHP_URL_QUERY) ? '&' : '?';
        $expiration = self::getExpirationTimestamp($expireMin);

        return $url . $join . 'signature=' . self::getUrlSignature($url, $private_key, $expiration) . '&expires=' . $expiration;
    }

    /**
     * @throws Exception
     */
    private static function getExpirationTimestamp($expiration): string
    {
        if (is_int($expiration)) {
            $expiration = (new DateTime())->modify((int)$expiration . ' minutes');
        }

        if (!$expiration instanceof DateTime) {
            throw new Exception('Expiration date must be an instance of DateTime or an integer');
        }

        if (!self::isFuture($expiration->getTimestamp())) {
            throw new Exception('Expiration date must be in the future');
        }

        return (string)$expiration->getTimestamp();
    }

    private static function isFuture($timestamp): bool
    {
        return ((int)$timestamp) >= (new DateTime())->getTimestamp();
    }

    /**
     * Get the signature for the given URL
     *
     * @param string $url
     * @param string $private_key
     * @param        $expiration
     *
     * @return string URL signature string
     */
    private static function getUrlSignature(string $url, string $private_key, $expiration): string
    {
        return md5($url . ':' . $expiration . ':' . $private_key);
    }

    /**
     * Check that the given URL is correctly signed
     *
     * @param string $url
     * @param string $private_key
     *
     * @return bool True if URL contains valid signature, false otherwise
     */
    public static function verifySignedUrl(string $url, string $private_key): bool
    {

        $param_expires = preg_quote('expires');
        if (!preg_match($regex1 = "/(:?&|\?)?{$param_expires}=([0-9]{10})/", $url, $matches)) {
            return false;
        }
        // Get the expires param
        $expiration = $matches[2];


        if (!self::isFuture($expiration)) {
            return false;
        }
        $param_name = preg_quote('signature');
        if (!preg_match($regex2 = "/(:?&|\?)?{$param_name}=([0-9a-f]{32})/", $url, $matches)) {
            return false;
        }
        // Get the signature param
        $passed_sig = $matches[2];

        // Strip signature from the given URL
        $url = preg_replace($regex1, '', $url);
        $url = preg_replace($regex2, '', $url);

        // Check that the given signature matches the correct one
        return self::getUrlSignature($url, $private_key, $expiration) === $passed_sig;
    }
}