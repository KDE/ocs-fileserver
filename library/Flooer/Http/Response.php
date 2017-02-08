<?php

/**
 * Flooer Framework
 *
 * LICENSE: BSD License (2 Clause)
 *
 * @category    Flooer
 * @package     Flooer_Http
 * @subpackage  Response
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 * @copyright   Akira Ohgaki
 * @license     https://opensource.org/licenses/BSD-2-Clause  BSD License (2 Clause)
 * @link        https://github.com/akiraohgaki/flooer
 */

/**
 * Usage
 *
 * $response = new Flooer_Http_Response();
 * $response->setStatus(201);
 * $response->setHeader('Content-Type', 'text/html');
 * $response->setBody($data);
 * $response->send();
 */

/**
 * HTTP response class
 *
 * @category    Flooer
 * @package     Flooer_Http
 * @subpackage  Response
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 */
class Flooer_Http_Response
{

    /**
     * Configuration options
     *
     * @var     array
     */
    protected $_config = array(
        'status' => null,
        'redirect' => null,
        'headers' => array(),
        'body' => null,
        'replace' => true
    );

    /**
     * Status codes
     *
     * @var     array
     * @link    http://www.iana.org/assignments/http-status-codes
     */
    protected $_statusCodes = array(
        100 => '100 Continue',
        101 => '101 Switching Protocols',
        102 => '102 Processing',
        // 103-199 Unassigned
        200 => '200 OK',
        201 => '201 Created',
        202 => '202 Accepted',
        203 => '203 Non-Authoritative Information',
        204 => '204 No Content',
        205 => '205 Reset Content',
        206 => '206 Partial Content',
        207 => '207 Multi-Status',
        208 => '208 Already Reported',
        // 209-225 Unassigned
        226 => '226 IM Used',
        // 227-299 Unassigned
        300 => '300 Multiple Choices',
        301 => '301 Moved Permanently',
        302 => '302 Found',
        303 => '303 See Other',
        304 => '304 Not Modified',
        305 => '305 Use Proxy',
        // 306 Unused
        307 => '307 Temporary Redirect',
        308 => '308 Permanent Redirect',
        // 309-399 Unassigned
        400 => '400 Bad Request',
        401 => '401 Unauthorized',
        402 => '402 Payment Required',
        403 => '403 Forbidden',
        404 => '404 Not Found',
        405 => '405 Method Not Allowed',
        406 => '406 Not Acceptable',
        407 => '407 Proxy Authentication Required',
        408 => '408 Request Timeout',
        409 => '409 Conflict',
        410 => '410 Gone',
        411 => '411 Length Required',
        412 => '412 Precondition Failed',
        413 => '413 Payload Too Large',
        414 => '414 URI Too Long',
        415 => '415 Unsupported Media Type',
        416 => '416 Range Not Satisfiable',
        417 => '417 Expectation Failed',
        // 418-420 Unassigned
        421 => '421 Misdirected Request',
        422 => '422 Unprocessable Entity',
        423 => '423 Locked',
        424 => '424 Failed Dependency',
        // 425 Unassigned
        426 => '426 Upgrade Required',
        // 427 Unassigned
        428 => '428 Precondition Required',
        429 => '429 Too Many Requests',
        // 430 Unassigned
        431 => '431 Request Header Fields Too Large',
        // 432-450 Unassigned
        451 => '451 Unavailable for Legal Reasons',
        // 452-499 Unassigned
        500 => '500 Internal Server Error',
        501 => '501 Not Implemented',
        502 => '502 Bad Gateway',
        503 => '503 Service Unavailable',
        504 => '504 Gateway Timeout',
        505 => '505 HTTP Version Not Supported',
        506 => '506 Variant Also Negotiates',
        507 => '507 Insufficient Storage',
        508 => '508 Loop Detected',
        // 509 Unassigned
        510 => '510 Not Extended',
        511 => '511 Network Authentication Required'
        // 512-599 Unassigned
    );

    /**
     * Content types
     *
     * @var     array
     */
    protected $_contentTypes = array(
        'xml' => 'application/xml',
        'rdf' => 'application/rdf+xml',
        'atom' => 'application/atom+xml',
        'rss' => 'application/rss+xml',
        'xhtml' => 'application/xhtml+xml',
        'json' => 'application/json',
        'php' => 'text/html',
        'phtml' => 'text/html',
        'html' => 'text/html',
        'txt' => 'text/plain',
        'csv' => 'text/csv',
        'tsv' => 'text/tab-separated-values',
        'vcf' => 'text/x-vcard',
        'ics' => 'text/calendar',
        'js' => 'text/javascript',
        'css' => 'text/css'
    );

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
    }

    /**
     * Send a response
     *
     * @return  void
     */
    public function send()
    {
        if ($this->_config['status']) {
            $this->sendStatus($this->_config['status']);
        }
        if ($this->_config['redirect']) {
            $this->redirect($this->_config['redirect']);
        }
        if ($this->_config['headers']) {
            foreach ($this->_config['headers'] as $name => $value) {
                $this->sendHeader($name, $value);
            }
        }
        if ($this->_config['body']) {
            echo $this->_config['body'];
        }
    }

    /**
     * Send a status code
     *
     * @param   int $code
     * @return  void
     */
    public function sendStatus($code)
    {
        if (isset($this->_statusCodes[$code])) {
            header(
                $_SERVER['SERVER_PROTOCOL'] . ' ' . $this->_statusCodes[$code],
                $this->_config['replace']
            );
            header(
                'Status: ' . $this->_statusCodes[$code],
                $this->_config['replace'],
                $code
            );
        }
    }

    /**
     * Redirection
     *
     * @param   string $location
     * @return  void
     */
    public function redirect($location)
    {
        header('Location: ' . $location, true);
        exit;
    }

    /**
     * Send a response header
     *
     * @param   string $name
     * @param   string $value
     * @return  void
     */
    public function sendHeader($name, $value)
    {
        header($name . ': ' . $value, $this->_config['replace']);
    }

    /**
     * Detect a content type
     *
     * @param   string $filename
     * @return  string|null
     */
    public function detectContentType($filename)
    {
        $type = null;
        if (preg_match("/.+\.([^\.]+)$/", strtolower($filename), $matches)
            && isset($this->_contentTypes[$matches[1]])
        ) {
            $type = $this->_contentTypes[$matches[1]];
            if (extension_loaded('mbstring') && mb_http_output() != 'pass') {
                $type .= '; charset=' . mb_http_output();
            }
        }
        return $type;
    }

    /**
     * Set a status code
     *
     * @param   int $code
     * @return  void
     */
    public function setStatus($code)
    {
        $this->_config['status'] = $code;
    }

    /**
     * Get a status code
     *
     * @return  int
     */
    public function getStatus()
    {
        return $this->_config['status'];
    }

    /**
     * Set a redirect location
     *
     * @param   string $location
     * @return  void
     */
    public function setRedirect($location)
    {
        $this->_config['redirect'] = $location;
    }

    /**
     * Get a redirect location
     *
     * @return  string
     */
    public function getRedirect()
    {
        return $this->_config['redirect'];
    }

    /**
     * Set a response headers
     *
     * @param   array $headers
     * @return  void
     */
    public function setHeaders(array $headers)
    {
        $this->_config['headers'] = $headers;
    }

    /**
     * Get a response headers
     *
     * @return  array
     */
    public function getHeaders()
    {
        return $this->_config['headers'];
    }

    /**
     * Set a response header
     *
     * @param   string $name
     * @param   string $value
     * @return  void
     */
    public function setHeader($name, $value)
    {
        $this->_config['headers'][$name] = $value;
    }

    /**
     * Get a response header
     *
     * @param   string $name
     * @return  string|null
     */
    public function getHeader($name)
    {
        if (isset($this->_config['headers'][$name])) {
            return $this->_config['headers'][$name];
        }
        return null;
    }

    /**
     * Set a response body data
     *
     * @param   mixed $data
     * @return  void
     */
    public function setBody($data)
    {
        $this->_config['body'] = $data;
    }

    /**
     * Get a response body data
     *
     * @return  mixed
     */
    public function getBody()
    {
        return $this->_config['body'];
    }

    /**
     * Set a header replace option
     *
     * @param   bool $replace
     * @return  void
     */
    public function setReplace($replace)
    {
        $this->_config['replace'] = $replace;
    }

    /**
     * Get a header replace option
     *
     * @return  bool
     */
    public function getReplace()
    {
        return $this->_config['replace'];
    }

}
