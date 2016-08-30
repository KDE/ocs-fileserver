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
 * @license     http://www.freebsd.org/copyright/freebsd-license.html  BSD License (2 Clause)
 * @link        https://github.com/akiraohgaki/flooer
 */

/**
 * Usage
 *
 * $convertedText = Flooer_Utility_Text::convert($text);
 */

/**
 * Text converter class
 *
 * @category    Flooer
 * @package     Flooer_Utility
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 */
class Flooer_Utility_Text
{

    /**
     * Batch converting
     *
     * @param   string $string
     * @param   string $newline
     * @return  string
     */
    public static function convert($string, $newline = "\n")
    {
        $string = self::convertNewline($string, $newline);
        $string = self::convertUri($string);
        $string = self::convertEmail($string);
        return $string;
    }

    /**
     * Convert a newline
     *
     * @param   string $string
     * @param   string $newline
     * @return  string
     */
    public static function convertNewline($string, $newline = "\n")
    {
        $string = str_replace(array("\r\n", "\r"), "\n", $string);
        if ($newline != "\n") {
            $string = str_replace("\n", $newline, $string);
        }
        return $string;
    }

    /**
     * Convert a URI address
     *
     * @param   string $string
     * @return  string
     */
    public static function convertUri($string)
    {
        return preg_replace(
            "/(https?|ftps?|davs?|file)(:\/\/[\w\.\~\-\/\?\&\+\=\:\;\@\%\,]+)/",
            "<a href=\"$0\">$0</a>",
            $string
        );
    }

    /**
     * Convert an email address
     *
     * @param   string $string
     * @return  string
     */
    public static function convertEmail($string)
    {
        return preg_replace(
            "/([\.\+\w\_\-]+@[\.\w\-]+\.[a-zA-Z]+)/",
            "<a href=\"mailto:$0\">$0</a>",
            $string
        );
    }

}
