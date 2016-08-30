<?php

/**
 * Flooer Framework
 *
 * LICENSE: BSD License (2 Clause)
 *
 * @category    Flooer
 * @package     Flooer_Http
 * @subpackage  Request
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 * @copyright   Akira Ohgaki
 * @license     http://www.freebsd.org/copyright/freebsd-license.html  BSD License (2 Clause)
 * @link        https://github.com/akiraohgaki/flooer
 */

/**
 * Usage
 *
 * $request = new Flooer_Http_Request();
 * $request->addUriMapRule(
 *     '^tags/(.+)/(.*)',
 *     'key=$1&params=$2',
 *     'params'
 * );
 * $request->addUriMapRule(
 *     array('^TAGS/(.+)/(.*)', '#', 'i'),
 *     'key=$1&params=$2',
 *     array('params', '/')
 * );
 * $request->mapUri();
 * $value = $request->key;
 */

/**
 * HTTP request class
 *
 * @category    Flooer
 * @package     Flooer_Http
 * @subpackage  Request
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 */
class Flooer_Http_Request
{

    /**
     * Configuration options
     *
     * @var     array
     */
    protected $_config = array(
        'importPredefinedVars' => true,
        'mapUri' => false,
        'uriMapRules' => array()
    );

    /**
     * Overloaded properties
     *
     * @var     array
     */
    protected $_properties = array();

    /**
     * Constructor
     *
     * @param   array $config
     * @return  void
     */
    public function __construct(array $config = null)
    {
        if ($config) {
            $this->_config = $config + $this->_config;
        }
        if ($this->_config['importPredefinedVars']) {
            $this->importPredefinedVars();
        }
        if ($this->_config['mapUri']) {
            $this->mapUri();
        }
    }

    /**
     * Magic method to set a property
     *
     * @param   string $key
     * @param   mixed $value
     * @return  void
     */
    public function __set($key, $value)
    {
        $this->_properties[$key] = $value;
    }

    /**
     * Magic method to get a property
     *
     * @param   string $key
     * @return  mixed
     */
    public function __get($key)
    {
        if (isset($this->_properties[$key])) {
            return $this->_properties[$key];
        }
        return null;
    }

    /**
     * Magic method to check a property
     *
     * @param   string $key
     * @return  bool
     */
    public function __isset($key)
    {
        return isset($this->_properties[$key]);
    }

    /**
     * Magic method to unset a property
     *
     * @param   string $key
     * @return  void
     */
    public function __unset($key)
    {
        unset($this->_properties[$key]);
    }

    /**
     * Import a predefined variables
     *
     * @return  void
     */
    public function importPredefinedVars()
    {
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                if (!empty($_GET)) {
                    $this->_properties += $_GET;
                }
                break;
            case 'POST':
                if (!empty($_POST)) {
                    $this->_properties += $_POST;
                }
                break;
            case 'PUT':
                $contentType = $this->getHeader('Content-Type');
                if (!empty($contentType)
                    && strpos(strtolower($contentType), 'application/x-www-form-urlencoded') !== false
                ) {
                    parse_str($this->getRawBody(), $put);
                    if (!empty($put)) {
                        $this->_properties += $put;
                    }
                }
                break;
            case 'DELETE':
                // Continue to default
            default:
                if (!empty($_SERVER['QUERY_STRING'])) {
                    parse_str($_SERVER['QUERY_STRING'], $params);
                    if (!empty($params)) {
                        $this->_properties += $params;
                    }
                }
                break;
        }
    }

    /**
     * URI mapping
     *
     * @return  void
     */
    public function mapUri()
    {
        $uri = $this->getUri();
        if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0) {
            $uri = substr($uri, strlen($_SERVER['SCRIPT_NAME']));
        }
        else if (strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0) {
            $uri = substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
        }
        $uri = trim(preg_replace("/^([^\?]*).*/", "$1", $uri), '/');
        if ($uri !== '' && $this->_config['uriMapRules']) {
            foreach ($this->_config['uriMapRules'] as $rule) {
                $pattern = null;
                $patternDelimiter = '#';
                $patternModifiers = null;
                $replacement = null;
                $addition = null;
                $additionDelimiter = '/';
                if (!empty($rule[0])) {
                    if (is_array($rule[0])) {
                        if (!empty($rule[0][0])) {
                            $pattern = $rule[0][0];
                        }
                        if (!empty($rule[0][1])) {
                            $patternDelimiter = $rule[0][1];
                        }
                        if (!empty($rule[0][2])) {
                            $patternModifiers = $rule[0][2];
                        }
                    }
                    else {
                        $pattern = $rule[0];
                    }
                    $pattern = $patternDelimiter
                        . $pattern
                        . $patternDelimiter
                        . $patternModifiers;
                }
                if (!empty($rule[1])) {
                    $replacement = $rule[1];
                }
                if (!empty($rule[2])) {
                    if (is_array($rule[2])) {
                        if (!empty($rule[2][0])) {
                            $addition = $rule[2][0];
                        }
                        if (!empty($rule[2][1])) {
                            $additionDelimiter = $rule[2][1];
                        }
                    }
                    else {
                        $addition = $rule[2];
                    }
                }
                if ($pattern && $replacement && preg_match($pattern, $uri)) {
                    $query = preg_replace($pattern, $replacement, $uri);
                    parse_str($query, $params);
                    if ($addition && !empty($params[$addition])) {
                        $kvPairs = explode(
                            $additionDelimiter,
                            trim($params[$addition], $additionDelimiter)
                        );
                        for ($i = 0; $i < count($kvPairs); $i++) {
                            $n = $i + 1;
                            if (isset($kvPairs[$n])) {
                                $params[$kvPairs[$i]] = $kvPairs[$n];
                                $i = $n;
                            }
                            else {
                                $params[$kvPairs[$i]] = null;
                            }
                        }
                    }
                    $this->_properties = $params + $this->_properties;
                    break;
                }
            }
        }
    }

    /**
     * Set a URI mapping rules
     *
     * @param   array $uriMapRules
     * @return  void
     */
    public function setUriMapRules(array $uriMapRules)
    {
        $this->_config['uriMapRules'] = $uriMapRules;
    }

    /**
     * Get a URI mapping rules
     *
     * @return  array
     */
    public function getUriMapRules()
    {
        return $this->_config['uriMapRules'];
    }

    /**
     * Add a URI mapping rule
     *
     * @param   array|string $pattern Regex
     * @param   string $replacement Query string
     * @param   array|string $addition Needle to parse as a key-value pairs
     * @return  void
     */
    public function addUriMapRule($pattern, $replacement, $addition = null)
    {
        $this->_config['uriMapRules'][] = array($pattern, $replacement, $addition);
    }

    /**
     * Check for a secure connection
     *
     * @return  bool
     */
    public function isSecure()
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            return true;
        }
        return false;
    }

    /**
     * Check for a XML HTTP request
     *
     * @return  bool
     */
    public function isXmlHttpRequest()
    {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])  == 'xmlhttprequest'
        ) {
            return true;
        }
        return false;
    }

    /**
     * Get a request method
     *
     * @param   bool $override
     * @return  string
     */
    public function getMethod($override = false)
    {
        $method = $_SERVER['REQUEST_METHOD'];
        if ($override
            && !empty($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])
        ) {
            $method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
        }
        return $method;
    }

    /**
     * Get a request URI
     *
     * @return  string|null
     */
    public function getUri()
    {
        $uri = null;
        // IIS Mod-Rewrite module
        if (!empty($_SERVER['HTTP_X_ORIGINAL_URL'])) {
            $uri = $_SERVER['HTTP_X_ORIGINAL_URL'];
        }
        // IIS Isapi_Rewrite module
        else if (!empty($_SERVER['HTTP_X_REWRITE_URL'])) {
            $uri = $_SERVER['HTTP_X_REWRITE_URL'];
        }
        // Common
        else if (!empty($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
        }
        // IIS and PHP-CGI
        else if (!empty($_SERVER['PATH_INFO'])) {
            if ($_SERVER['PATH_INFO'] == $_SERVER['SCRIPT_NAME']) {
                $uri = $_SERVER['PATH_INFO'];
            }
            else {
                $uri = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
            }
            if (!empty($_SERVER['QUERY_STRING'])) {
                $uri .= '?' . $_SERVER['QUERY_STRING'];
            }
        }
        else if (!empty($_SERVER['ORIG_PATH_INFO'])) {
            if ($_SERVER['ORIG_PATH_INFO'] == $_SERVER['SCRIPT_NAME']) {
                $uri = $_SERVER['ORIG_PATH_INFO'];
            }
            else {
                $uri = $_SERVER['SCRIPT_NAME'] . $_SERVER['ORIG_PATH_INFO'];
            }
            if (!empty($_SERVER['QUERY_STRING'])) {
                $uri .= '?' . $_SERVER['QUERY_STRING'];
            }
        }
        return $uri;
    }

    /**
     * Get a request header
     *
     * @param   string $name
     * @return  string|null
     */
    public function getHeader($name)
    {
        $header = null;
        $key = 'HTTP_' . str_replace('-', '_', strtoupper($name));
        if (!empty($_SERVER[$key])) {
            $header = $_SERVER[$key];
        }
        else if (function_exists('apache_request_headers')) {
            $headers = array_change_key_case(apache_request_headers(), CASE_LOWER);
            $key = strtolower($name);
            if (!empty($headers[$key])) {
                $header = $headers[$key];
            }
        }
        return $header;
    }

    /**
     * Get an accept language
     *
     * @param   bool $subset
     * @return  string|null
     */
    public function getAcceptLanguage($subset = true)
    {
        $language = null;
        if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            preg_match(
                "/^([a-z]{2})(\-|\_)?([a-z]{2})?\,?/i",
                $_SERVER['HTTP_ACCEPT_LANGUAGE'],
                $matches
            );
            if ($subset && !empty($matches[1]) && !empty($matches[3])) {
                $language = strtolower($matches[1]) . '-' . strtoupper($matches[3]);
            }
            else if (!empty($matches[1])) {
                $language = strtolower($matches[1]);
            }
        }
        return $language;
    }

    /**
     * Get a raw body data
     *
     * @return  mixed
     */
    public function getRawBody()
    {
        return file_get_contents('php://input');
    }

}
