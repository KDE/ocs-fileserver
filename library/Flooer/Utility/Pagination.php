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
 * $paginationArray = Flooer_Utility_Pagination::paginate(2000, 10, 3);
 */

/**
 * Paginator class
 *
 * @category    Flooer
 * @package     Flooer_Utility
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 */
class Flooer_Utility_Pagination
{

    /**
     * Paginating
     *
     * @param   int $totalItems
     * @param   int $itemsPerPage
     * @param   int $current
     * @param   int $range
     * @param   string $style Available values: all, jumping, elastic, sliding
     * @return  array
     */
    public static function paginate($totalItems, $itemsPerPage, $current = 1, $range = 10, $style = 'sliding')
    {
        $pagination = array();
        $pagination['totalItems'] = (int) $totalItems;
        $pagination['itemsPerPage'] = (int) $itemsPerPage;
        $pagination['first'] = 1;
        $pagination['last'] = (int) ceil($pagination['totalItems'] / $pagination['itemsPerPage']);
        $pagination['current'] = (int) $current;
        if ($pagination['current'] >= $pagination['first']
            && $pagination['current'] <= $pagination['last']
        ) {
            if ($pagination['current'] > $pagination['first']) {
                $pagination['previous'] = $pagination['current'] - 1;
            }
            if ($pagination['current'] < $pagination['last']) {
                $pagination['next'] = $pagination['current'] + 1;
            }
            switch (strtolower($style)) {
                case 'all':
                    // List of all pages
                    // 1 2 3 4 5 6 7 8 9 10 ...
                    $low = $pagination['first'];
                    $high = $pagination['last'];
                    break;
                case 'jumping':
                    // The end of a range is the beginning of the new range
                    // Before: 1 2 3 4 5 6 7 8 [9] 10
                    // After:  [10] 11 12 13 14 15 16 17 18 19 20
                    $low = (int) floor($pagination['current'] / $range) * $range;
                    $high = $low + $range;
                    if ($low < $pagination['first']) {
                        $low = $pagination['first'];
                        $high = ($pagination['first'] + $range) - 1;
                        if ($high > $pagination['last']) {
                            $high = $pagination['last'];
                        }
                    }
                    if ($high > $pagination['last']) {
                        $high = $pagination['last'];
                        $low = ($pagination['last'] - $range) + 1;
                        if ($low < $pagination['first']) {
                            $low = $pagination['first'];
                        }
                    }
                    break;
                case 'elastic':
                    // Elastic scrolling style
                    // Before: 1 2 3 4 5 6 7 8 [9] 10
                    // After:  1 2 3 4 5 6 7 8 9 [10] 11 12 13 14 15 16 17 18 19 20
                    $low = ($pagination['current'] - $range) + 1;
                    $high = $pagination['current'] + $range;
                    if ($low < $pagination['first']) {
                        $low = $pagination['first'];
                        $high = ($pagination['current'] + $range) - 1;
                        if ($high > $pagination['last']) {
                            $high = $pagination['last'];
                        }
                    }
                    if ($high > $pagination['last']) {
                        $high = $pagination['last'];
                        $low = ($pagination['current'] - $range) + 1;
                        if ($low < $pagination['first']) {
                            $low = $pagination['first'];
                        }
                    }
                    break;
                case 'sliding':
                    // Continue to default
                default:
                    // Sliding scrolling style
                    // Before: 1 2 3 4 [5] 6 7 8 9 10
                    // After:  2 3 4 5 [6] 7 8 9 10 11
                    $delta = (int) ceil($range / 2);
                    $low = ($pagination['current'] - $delta) + 1;
                    $high = $pagination['current'] + $delta;
                    if ($low < $pagination['first']) {
                        $low = $pagination['first'];
                        $high = ($pagination['first'] + $range) - 1;
                        if ($high > $pagination['last']) {
                            $high = $pagination['last'];
                        }
                    }
                    if ($high > $pagination['last']) {
                        $high = $pagination['last'];
                        $low = ($pagination['last'] - $range) + 1;
                        if ($low < $pagination['first']) {
                            $low = $pagination['first'];
                        }
                    }
                    break;
            }
            $pagination['pages'] = range($low, $high);
        }
        return $pagination;
    }

}
