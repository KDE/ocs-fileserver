<?php

/**
 * Flooer Framework
 *
 * LICENSE: BSD License (2 Clause)
 *
 * @category    Flooer
 * @package     Flooer_Exception
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 * @copyright   Akira Ohgaki
 * @license     https://opensource.org/licenses/BSD-2-Clause  BSD License (2 Clause)
 * @link        https://github.com/akiraohgaki/flooer
 */

/**
 * Usage
 *
 * try {
 *     throw new Flooer_Exception('Message');
 * }
 * catch (Flooer_Exception $exception) {
 *     echo $exception->getMessage();
 * }
 */

/**
 * Exception class
 *
 * @category    Flooer
 * @package     Flooer_Exception
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 */
class Flooer_Exception extends Exception
{

    /**
     * Previous exception object
     *
     * @var     Flooer_Exception|Exception
     */
    protected $_previousException = null;

    /**
     * Constructor
     *
     * @param   string $message
     * @param   int $code
     * @param   string $file
     * @param   int $line
     * @param   Flooer_Exception|Exception $previous
     * @return  void
     */
    public function __construct($message = null, $code = 0, $file = null, $line = null, Exception $previous = null)
    {
        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            parent::__construct($message, $code, $previous);
        }
        else {
            parent::__construct($message, $code);
        }
        if ($file !== null) {
            $this->file = $file;
        }
        if ($line !== null) {
            $this->line = $line;
        }
        if ($previous !== null) {
            $this->_previousException = $previous;
        }
    }

    /**
     * Magic method to get a string representation
     *
     * @return  string
     */
    public function __toString()
    {
        return __CLASS__ . " [{$this->code}]: {$this->message}; {$this->file}({$this->line})\n";
    }

    /**
     * Exception handler
     *
     * @param   Flooer_Exception|Exception $exception
     * @return  void
     */
    public static function exceptionHandler($exception)
    {
        $displayErrors = ini_get("display_errors");
        $displayErrors = strtolower($displayErrors);

        $logErrors = boolval(ini_get("log_errors"));

        if ($logErrors) {
            $errorMsg = __CLASS__ . " [{$exception->getCode()}]: {$exception->getMessage()} in {$exception->getFile()}({$exception->getLine()})\n{$exception->getTraceAsString()}\n";
            error_log($errorMsg);
        }

        if (error_reporting() === 0 || empty($displayErrors) || $displayErrors === "off") {
            return;
        }

        echo $exception;
    }

    /**
     * Set the exception handler
     *
     * @return  callback|null
     */
    public static function setExceptionHandler()
    {
        return set_exception_handler(array(__CLASS__, 'exceptionHandler'));
    }

    /**
     * Restore the exception handler
     *
     * @return  bool Always true
     */
    public static function restoreExceptionHandler()
    {
        return restore_exception_handler();
    }

    /**
     * Error handler
     *
     * @param   int $errno
     * @param   string $errstr
     * @param   string $errfile
     * @param   int $errline
     * @return  void
     * @throws  Flooer_Exception
     */
    public static function errorHandler($errno, $errstr, $errfile = null, $errline = null)
    {
        throw new self($errstr, self::convertErrorCode($errno), $errfile, $errline);
    }

    /**
     * Set the error handler
     *
     * @return  mixed
     */
    public static function setErrorHandler()
    {
        return set_error_handler(array(__CLASS__, 'errorHandler'), error_reporting());
    }

    /**
     * Restore the error handler
     *
     * @return  bool Always true
     */
    public static function restoreErrorHandler()
    {
        return restore_error_handler();
    }

    /**
     * Convert an error code to a log code
     *
     * @param   int $errno E_* code
     * @return  int LOG_* code
     */
    public static function convertErrorCode($errno)
    {
        $types = array(
            E_ERROR => LOG_EMERG,
            E_WARNING => LOG_WARNING,
            E_PARSE => LOG_ERR,
            E_NOTICE => LOG_NOTICE,
            E_CORE_ERROR => LOG_EMERG,
            E_CORE_WARNING => LOG_WARNING,
            E_COMPILE_ERROR => LOG_EMERG,
            E_COMPILE_WARNING => LOG_WARNING,
            E_USER_ERROR => LOG_ERR,
            E_USER_WARNING => LOG_WARNING,
            E_USER_NOTICE => LOG_NOTICE,
            E_STRICT => LOG_NOTICE,
            E_RECOVERABLE_ERROR => LOG_ERR
        );
        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            $types += array(
                E_DEPRECATED => LOG_WARNING,
                E_USER_DEPRECATED => LOG_WARNING
            );
        }
        if (isset($types[$errno])) {
            return $types[$errno];
        }
        return LOG_NOTICE;
    }

    /**
     * Get a previous exception object
     *
     * @return  Flooer_Exception|Exception
     */
    public function getPreviousException()
    {
        return $this->_previousException;
    }

}
