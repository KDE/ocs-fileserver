<?php

/**
 * Flooer Framework
 *
 * LICENSE: BSD License (2 Clause)
 *
 * @category    Flooer
 * @package     Flooer_Utility
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 * @copyright   Akira Ohgaki
 * @license     https://opensource.org/licenses/BSD-2-Clause  BSD License (2 Clause)
 * @link        https://github.com/akiraohgaki/flooer
 */

/**
 * Usage
 *
 * $bool = Flooer_Utility_Validation::isUri($uri);
 */

/**
 * Validator class
 *
 * @category    Flooer
 * @package     Flooer_Utility
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 */
class Flooer_Utility_Validation
{

    /**
     * Check for an IP address
     *
     * @param   string $ip
     * @return  bool
     */
    public static function isIp($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }
        return false;
    }

    /**
     * Check for a URI address
     *
     * @param   string $uri
     * @return  bool
     */
    public static function isUri($uri)
    {
        //if (filter_var($uri, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED)) {
        //    return true;
        //}
        if (preg_match("/^(https?|ftps?|davs?|file)(:\/\/[\w\.\~\-\/\?\&\+\=\:\;\@\%\,]+)$/" , $uri)) {
            return true;
        }
        return false;
    }

    /**
     * Check for an email address
     *
     * @param   string $email
     * @return  bool
     */
    public static function isEmail($email)
    {
        //if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        //    return true;
        //}
        if (preg_match("/^([\.\+\w\_\-]+@[\.\w\-]+\.[a-zA-Z]+)$/", $email)) {
            return true;
        }
        return false;
    }

    /**
     * Check for alphanumeric characters
     *
     * @param   string $alnum
     * @return  bool
     */
    public static function isAlnum($alnum)
    {
        if (ctype_alnum((string) $alnum)) {
            return true;
        }
        return false;
    }

    /**
     * Check for alphabetic characters
     *
     * @param   string $alpha
     * @return  bool
     */
    public static function isAlpha($alpha)
    {
        if (ctype_aplha((string) $alpha)) {
            return true;
        }
        return false;
    }

    /**
     * Check for numeric characters
     *
     * @param   string $digit
     * @return  bool
     */
    public static function isDigit($digit)
    {
        if (ctype_digit((string) $digit)) {
            return true;
        }
        return false;
    }

    /**
     * Check for the length of a string
     *
     * @param   string $string
     * @param   int $mini
     * @param   int $max
     * @return  bool
     */
    public static function isLength($string, $mini = 1, $max = 255)
    {
        $length = strlen($string);
        if ($length >= $mini && $length <= $max) {
            return true;
        }
        return false;
    }

}
