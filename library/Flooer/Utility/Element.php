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
 * $xhtmlSource = Flooer_Utility_Element::convert($textSource);
 */

/**
 * Element converter class that convert a plain text to a XHTML source
 *
 * @category    Flooer
 * @package     Flooer_Utility
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 */
class Flooer_Utility_Element
{

    /**
     * Batch converting
     *
     * @param   string $source
     * @return  string
     */
    public static function convert($source)
    {
        $source = self::convertNewline($source);
        $source = self::convertSpecialchar($source);
        $source = self::convertInlineElement($source);
        $source = self::convertBlockElement($source);
        return $source;
    }

    /**
     * Convert a newline
     *
     * @param   string $source
     * @return  string
     */
    public static function convertNewline($source)
    {
        // Make a newline into LF
        return str_replace(array("\r\n", "\r"), "\n", $source);
    }

    /**
     * Convert a special character
     *
     * @param   string $source
     * @return  string
     */
    public static function convertSpecialchar($source)
    {
        // Encode a conflicted characters
        $source = preg_replace(
            "/\"[^\"\n]+\"\<[^\>\n]+\>/e",
            "rawurlencode(stripslashes('$0'))",
            $source
        );
        $source = preg_replace(
            "/\"[^\"\n]+\"\{[^\}\n]+\}\<[^\>\n]+\>/e",
            "rawurlencode(stripslashes('$0'))",
            $source
        );
        $source = preg_replace(
            "/\"[^\"\n]+\"\{[^\}\n]+\}/e",
            "rawurlencode(stripslashes('$0'))",
            $source
        );
        $source = preg_replace(
            "/\"[^\"\n]+\"\([^\)\n]+\)/e",
            "rawurlencode(stripslashes('$0'))",
            $source
        );
        $source = str_replace("\n>", "\n" . rawurlencode('>'), $source);
        // Make a characters into a character-entity
        $source = str_replace("\t", '    ', $source); // tab to 4 spaces
        $source = str_replace('&', '&amp;', $source);
        $source = str_replace('"', '&quot;', $source);
        $source = str_replace("'", '&#039;', $source);
        $source = str_replace('<', '&lt;', $source);
        $source = str_replace('>', '&gt;', $source);
        $source = str_ireplace('(C)', '&copy;', $source);
        $source = str_ireplace('(R)', '&reg;', $source);
        $source = str_ireplace('(TM)', '&trade;', $source);
        // Decode a conflicted characters
        $source = rawurldecode($source);
        return $source;
    }

    /**
     * Convert an inline element
     *
     * @param   string $source
     * @return  string
     */
    public static function convertInlineElement($source)
    {
        // Anchor
        // "title"<uri>
        $source = preg_replace(
            "/\"([^\"\n]+)\"\<([^\>\n]+)\>/",
            "<a href=\"$2\" title=\"$1\">$1</a>",
            $source
        );
        // Image Anchor
        // "title"{image-uri}<uri>
        $source = preg_replace(
            "/\"([^\"\n]+)\"\{([^\}\n]+)\}\<([^\>\n]+)\>/",
            "<a href=\"$3\" title=\"$1\"><img src=\"$2\" alt=\"$1\" /></a>",
            $source
        );
        // Image
        // "title"{image-uri}
        $source = preg_replace(
            "/\"([^\"\n]+)\"\{([^\}\n]+)\}/",
            "<img src=\"$2\" alt=\"$1\" />",
            $source
        );
        // Abbreviation
        // "abbr"(Abbreviation)
        $source = preg_replace(
            "/\"([^\"\n]+)\"\(([^\)\n]+)\)/",
            "<abbr title=\"$2\">$1</abbr>",
            $source
        );
        // Strong Emphasis
        // ***strong***
        $source = preg_replace(
            "/\*\*\*([^\*\n]+)\*\*\*/",
            "<strong>$1</strong>",
            $source
        );
        // Emphasis
        // **em**
        $source = preg_replace(
            "/\*\*([^\*\n]+)\*\*/",
            "<em>$1</em>",
            $source
        );
        // Subscript
        // ~~sub~~
        $source = preg_replace(
            "/\~\~([^\~\n]+)\~\~/",
            "<sub>$1</sub>",
            $source
        );
        // Superscript
        // ^^sup^^
        $source = preg_replace(
            "/\^\^([^\^\n]+)\^\^/",
            "<sup>$1</sup>",
            $source
        );
        // Insert
        // ++ins++
        $source = preg_replace(
            "/\+\+([^\+\n]+)\+\+/",
            "<ins>$1</ins>",
            $source
        );
        // Delete
        // --del--
        $source = preg_replace(
            "/\-\-([^\-\n]+)\-\-/",
            "<del>$1</del>",
            $source
        );
        return $source;
    }

    /**
     * Convert a block element
     *
     * @param   string $source
     * @return  string
     */
    public static function convertBlockElement($source)
    {
        $sourceLines = explode("\n", $source);
        $totalSourceLines = count($sourceLines);
        $isBlockContinueable = false;
        $isLineBreakable = false;
        for ($i = 0; $i < $totalSourceLines; $i++) {
            $sourceLines[$i] = rtrim($sourceLines[$i]);
            $n = $i + 1;
            if (!isset($sourceLines[$n])) {
                $sourceLines[$n] = '';
            }
            // Blank line
            // Blank line is removes.
            if ($sourceLines[$i] == '') {
                unset($sourceLines[$i]);
                continue;
            }
            // Commentation
            // // Comment line 1
            // // Comment line 2
            // // Comment line 3
            // Comment line is removes.
            else if (preg_match("/^\/\//", $sourceLines[$i])) {
                unset($sourceLines[$i]);
                continue;
            }
            // Division
            // ******
            // ++++++
            // ------
            // "*/+/-" is 4 characters or more required.
            else if (preg_match("/^(\*|\+|\-){4,}$/", $sourceLines[$i])) {
                $sourceLines[$i] = "</div>\n<div>";
                $isBlockContinueable = false;
                $isLineBreakable = false;
            }
            // Heading
            // = Heading 1 =
            // ====== Heading 6 ======
            // # Heading 1 #
            // ###### Heading 6 ######
            // Closing "=/#" is an option and not counted.
            else if (preg_match("/(^[\=\#]{1,6})[ \t]*(.*)/", $sourceLines[$i], $matches)) {
                $matches[1] = strlen($matches[1]);
                $matches[2] = preg_replace("/[ \t\=\#]*$/", '', $matches[2]);
                $sourceLines[$i] = "<h{$matches[1]}>{$matches[2]}</h{$matches[1]}>";
                $isBlockContinueable = false;
                $isLineBreakable = false;
            }
            // Address
            // @ Address line 1
            // @ Address line 2
            // @ Address line 3
            else if (preg_match("/^\@[ \t]*(.*)/", $sourceLines[$i], $matches)) {
                $sourceLines[$i] = $matches[1];
                if (!$isBlockContinueable) {
                    $sourceLines[$i] = "<address>\n{$sourceLines[$i]}";
                    $isBlockContinueable = true;
                    $isLineBreakable = true;
                }
                if (!preg_match("/^\@/", $sourceLines[$n])) {
                    $sourceLines[$i] .= "\n</address>";
                    $isBlockContinueable = false;
                    $isLineBreakable = false;
                }
            }
            // Preformatted Text
            // \sPreformatted Text line 1
            // \tPreformatted Text line 2
            // \s\tPreformatted Text line 3
            // "\s" is a space and "\t" is a tab.
            else if (preg_match("/^[ \t].*/", $sourceLines[$i])) {
                if (!$isBlockContinueable) {
                    $sourceLines[$i] = "<pre>\n{$sourceLines[$i]}";
                    $isBlockContinueable = true;
                    $isLineBreakable = false;
                }
                if (!preg_match("/^[ \t]/", $sourceLines[$n])) {
                    $sourceLines[$i] .= "\n</pre>";
                    $isBlockContinueable = false;
                    $isLineBreakable = false;
                }
            }
            // Block Quote
            // > Block Quote line 1
            // > Block Quote line 2
            // > Block Quote line 3
            else if (preg_match("/^\>[ \t]*(.*)/", $sourceLines[$i], $matches)) {
                $sourceLines[$i] = $matches[1];
                if (!$isBlockContinueable) {
                    $sourceLines[$i] = "<blockquote>\n{$sourceLines[$i]}";
                    $isBlockContinueable = true;
                    $isLineBreakable = true;
                }
                if (!preg_match("/^\>/", $sourceLines[$n])) {
                    $sourceLines[$i] .= "\n</blockquote>";
                    $isBlockContinueable = false;
                    $isLineBreakable = false;
                }
            }
            // Ordered List
            // 1. Ordered List 1
            // 2. Ordered List 2
            // 3. Ordered List 3
            else if (preg_match("/(^[1-9][0-9]*)\.[ \t]*(.*)/", $sourceLines[$i], $matches)) {
                $sourceLines[$i] = "<li>{$matches[2]}</li>";
                if (!$isBlockContinueable) {
                    $sourceLines[$i] = "<ol start=\"{$matches[1]}\">\n{$sourceLines[$i]}";
                    $isBlockContinueable = true;
                    $isLineBreakable = false;
                }
                if (!preg_match("/^[1-9][0-9]*\./", $sourceLines[$n])) {
                    $sourceLines[$i] .= "\n</ol>";
                    $isBlockContinueable = false;
                    $isLineBreakable = false;
                }
            }
            // Unordered List
            // * Unordered List 1
            // + Unordered List 2
            // - Unordered List 3
            else if (preg_match("/^[\*\+\-][ \t]*(.*)/", $sourceLines[$i], $matches)) {
                $sourceLines[$i] = "<li>{$matches[1]}</li>";
                if (!$isBlockContinueable) {
                    $sourceLines[$i] = "<ul>\n{$sourceLines[$i]}";
                    $isBlockContinueable = true;
                    $isLineBreakable = false;
                }
                if (!preg_match("/^[\*\+\-]+[^\*\+\-]+/", $sourceLines[$n])) {
                    $sourceLines[$i] .= "\n</ul>";
                    $isBlockContinueable = false;
                    $isLineBreakable = false;
                }
            }
            // Definition List
            // | dl1 | Definition List 1
            // | dl2 | Definition List 2
            // | dl3 | Definition List 3
            else if (preg_match("/^\|[ \t]*(.*)\|[ \t]*(.*)/", $sourceLines[$i], $matches)) {
                $matches[1] = rtrim($matches[1]);
                $sourceLines[$i] = "<dt>{$matches[1]}</dt>\n<dd>{$matches[2]}</dd>";
                if (!$isBlockContinueable) {
                    $sourceLines[$i] = "<dl>\n{$sourceLines[$i]}";
                    $isBlockContinueable = true;
                    $isLineBreakable = false;
                }
                if (!preg_match("/^\|.*\|/", $sourceLines[$n])) {
                    $sourceLines[$i] .= "\n</dl>";
                    $isBlockContinueable = false;
                    $isLineBreakable = false;
                }
            }
            // Paragraph
            // A line without markup is a paragraph and separate as a blank line.
            else {
                if (!$isBlockContinueable) {
                    $sourceLines[$i] = "<p>\n{$sourceLines[$i]}";
                    $isBlockContinueable = true;
                    $isLineBreakable = true;
                }
                if ($sourceLines[$n] == ''
                    || preg_match("/^([ \t\=\#\>\@\*\+\-]|[1-9][0-9]*\.|\|.*\|)/", $sourceLines[$n])
                ) {
                    $sourceLines[$i] .= "\n</p>";
                    $isBlockContinueable = false;
                    $isLineBreakable = false;
                }
            }
            // Line break
            if ($isLineBreakable) {
                $sourceLines[$i] .= '<br />';
            }
        }
        // Enclosed with a div tag
        $source = "<div>\n" . implode("\n", $sourceLines) . "\n</div>";
        return $source;
    }

}
