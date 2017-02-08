<?php

/**
 * Flooer Framework
 *
 * LICENSE: BSD License (2 Clause)
 *
 * @category    Flooer
 * @package     Flooer_Filter
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 * @copyright   Akira Ohgaki
 * @license     https://opensource.org/licenses/BSD-2-Clause  BSD License (2 Clause)
 * @link        https://github.com/akiraohgaki/flooer
 */

/**
 * Usage
 *
 * $filter = new Flooer_Filter();
 * $filter->setEncoding('UTF-8');
 * $filter->setNewline('LF');
 * $filter->filter($input);
 */

/**
 * String filter class
 *
 * @category    Flooer
 * @package     Flooer_Filter
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 */
class Flooer_Filter
{

    /**
     * Configuration options
     *
     * @var     array
     */
    protected $_config = array(
        'convertEncoding' => true,
        'convertNewline' => true,
        'stripNull' => true,
        'stripSlashes' => true,
        'trimWhitespace' => true,
        'encoding' => 'UTF-8',
        'newline' => 'LF'
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
     * Filtering
     *
     * @param   mixed &$data
     * @return  void
     */
    public function filter(&$data)
    {
        // Filter an array/object recursively
        if (is_array($data) || is_object($data)) {
            if ($this->_config['convertEncoding']) {
                array_walk_recursive($data, array($this, 'convertEncoding'));
            }
            if ($this->_config['convertNewline']) {
                array_walk_recursive($data, array($this, 'convertNewline'));
            }
            if ($this->_config['stripNull']) {
                array_walk_recursive($data, array($this, 'stripNull'));
            }
            if ($this->_config['stripSlashes']) {
                array_walk_recursive($data, array($this, 'stripSlashes'));
            }
            if ($this->_config['trimWhitespace']) {
                array_walk_recursive($data, array($this, 'trimWhitespace'));
            }
        }
        // Filter a string
        else {
            if ($this->_config['convertEncoding']) {
                $this->convertEncoding($data);
            }
            if ($this->_config['convertNewline']) {
                $this->convertNewline($data);
            }
            if ($this->_config['stripNull']) {
                $this->stripNull($data);
            }
            if ($this->_config['stripSlashes']) {
                $this->stripSlashes($data);
            }
            if ($this->_config['trimWhitespace']) {
                $this->trimWhitespace($data);
            }
        }
    }

    /**
     * Convert character encoding
     *
     * @param   string &$value
     * @param   mixed $key
     * @return  void
     */
    public function convertEncoding(&$value, $key = null)
    {
        $value = mb_convert_encoding($value, $this->_config['encoding'], mb_detect_order());
    }

    /**
     * Convert a newline
     *
     * @param   string &$value
     * @param   mixed $key
     * @return  void
     */
    public function convertNewline(&$value, $key = null)
    {
        switch (strtoupper($this->_config['newline'])) {
            case 'LF':
                $value = str_replace(array("\r\n", "\r"), "\n", $value);
                break;
            case 'CRLF':
                $value = str_replace(array("\r\n", "\r"), "\n", $value);
                $value = str_replace("\n", "\r\n", $value);
                break;
            case 'CR':
                $value = str_replace(array("\r\n", "\n"), "\r", $value);
                break;
            default:
                break;
        }
    }

    /**
     * Strip a null characters
     *
     * @param   string &$value
     * @param   mixed $key
     * @return  void
     */
    public function stripNull(&$value, $key = null)
    {
        $value = str_replace("\0", '', $value);
    }

    /**
     * Un-quote a quoted string
     *
     * @param   string &$value
     * @param   mixed $key
     * @return  void
     */
    public function stripSlashes(&$value, $key = null)
    {
        $value = stripslashes($value);
    }

    /**
     * Trim whitespace
     *
     * @param   string &$value
     * @param   mixed $key
     * @return  void
     */
    public function trimWhitespace(&$value, $key = null)
    {
        $value = trim($value);
    }

    /**
     * Set an executable flag for convertEncoding()
     *
     * @param   bool $bool
     * @return  void
     */
    public function setConvertEncoding($bool)
    {
        $this->_config['convertEncoding'] = $bool;
    }

    /**
     * Get an executable flag for convertEncoding()
     *
     * @return  bool
     */
    public function getConvertEncoding()
    {
        return $this->_config['convertEncoding'];
    }

    /**
     * Set an executable flag for convertNewline()
     *
     * @param   bool $bool
     * @return  void
     */
    public function setConvertNewline($bool)
    {
        $this->_config['convertNewline'] = $bool;
    }

    /**
     * Get an executable flag for convertNewline()
     *
     * @return  bool
     */
    public function getConvertNewline()
    {
        return $this->_config['convertNewline'];
    }

    /**
     * Set an executable flag for stripNull()
     *
     * @param   bool $bool
     * @return  void
     */
    public function setStripNull($bool)
    {
        $this->_config['stripNull'] = $bool;
    }

    /**
     * Get an executable flag for stripNull()
     *
     * @return  bool
     */
    public function getStripNull()
    {
        return $this->_config['stripNull'];
    }

    /**
     * Set an executable flag for stripSlashes()
     *
     * @param   bool $bool
     * @return  void
     */
    public function setStripSlashes($bool)
    {
        $this->_config['stripSlashes'] = $bool;
    }

    /**
     * Get an executable flag for stripSlashes()
     *
     * @return  bool
     */
    public function getStripSlashes()
    {
        return $this->_config['stripSlashes'];
    }

    /**
     * Set an executable flag for trimWhitespace()
     *
     * @param   bool $bool
     * @return  void
     */
    public function setTrimWhitespace($bool)
    {
        $this->_config['trimWhitespace'] = $bool;
    }

    /**
     * Get an executable flag for trimWhitespace()
     *
     * @return  bool
     */
    public function getTrimWhitespace()
    {
        return $this->_config['trimWhitespace'];
    }

    /**
     * Set a character encoding
     *
     * @param   string $encoding Available values to mb_convert_encoding()
     * @return  void
     */
    public function setEncoding($encoding)
    {
        $this->_config['encoding'] = $encoding;
    }

    /**
     * Get a character encoding
     *
     * @return  string
     */
    public function getEncoding()
    {
        return $this->_config['encoding'];
    }

    /**
     * Set a newline
     *
     * @param   string $newline Available values: LF, CRLF, CR
     * @return  void
     */
    public function setNewline($newline)
    {
        $this->_config['newline'] = $newline;
    }

    /**
     * Get a newline
     *
     * @return  string
     */
    public function getNewline()
    {
        return $this->_config['newline'];
    }

}
