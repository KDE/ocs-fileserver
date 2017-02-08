<?php

/**
 * Flooer Framework
 *
 * LICENSE: BSD License (2 Clause)
 *
 * @category    Flooer
 * @package     Flooer_Log
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 * @copyright   Akira Ohgaki
 * @license     https://opensource.org/licenses/BSD-2-Clause  BSD License (2 Clause)
 * @link        https://github.com/akiraohgaki/flooer
 */

/**
 * Usage
 *
 * $log = new Flooer_Log();
 * $log->setFile('./errors.log');
 * $log->setMail('email@address');
 * $log->log('Message', LOG_ERR);
 */

/**
 * Error logger class
 *
 * @category    Flooer
 * @package     Flooer_Log
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 */
class Flooer_Log
{

    /**
     * Configuration options
     *
     * @var     array
     */
    protected $_config = array(
        'format' => '%timestamp% %priorityName% (%priority%): %message%',
        'file' => null,
        'fileMaxLength' => 1024,
        'mail' => null,
        'mailAdditionalHeaders' => null,
        'mailFilter' => LOG_ERR
    );

    /**
     * Priorities
     *
     * @var     array
     */
    protected $_priorities = array(
        LOG_EMERG => 'EMERG',
        LOG_ALERT => 'ALERT',
        LOG_CRIT => 'CRIT',
        LOG_ERR => 'ERR',
        LOG_WARNING => 'WARN',
        LOG_NOTICE => 'NOTICE',
        LOG_INFO => 'INFO',
        LOG_DEBUG => 'DEBUG'
    );

    /**
     * Error log
     *
     * @var     array
     */
    protected $_errorLog = array();

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
     * Write a log and/or send an email notification
     *
     * @param   string $message
     * @param   int $priority Available values: 0-7 (LOG_EMERG-LOG_DEBUG)
     * @return  bool
     */
    public function log($message, $priority = null)
    {
        if ($priority === null) {
            $priority = LOG_NOTICE;
        }
        if (isset($this->_priorities[$priority])) {
            $log = $this->_config['format'];
            $log = str_replace('%timestamp%', date('c'), $log);
            $log = str_replace('%priorityName%', $this->_priorities[$priority], $log);
            $log = str_replace('%priority%', $priority, $log);
            $log = str_replace('%message%', $message, $log);
            $log .= "\n";
            $this->_errorLog[] = $log;
            $bool = false;
            if ($this->_config['file']
                && is_dir(dirname($this->_config['file']))
            ) {
                ini_set('log_errors', 1);
                ini_set('log_errors_max_len', $this->_config['fileMaxLength']);
                //ini_set('error_log', $this->_config['file']);
                $bool = error_log($log, 3, $this->_config['file']);
            }
            if ($this->_config['mail']
                && $priority <= $this->_config['mailFilter']
            ) {
                $bool = mail(
                    $this->_config['mail'],
                    __CLASS__ . ' message',
                    wordwrap($log, 70),
                    $this->_config['mailAdditionalHeaders']
                );
            }
            return $bool;
        }
        return false;
    }

    /**
     * Set a log format
     *
     * @param   string $format
     * @return  void
     */
    public function setFormat($format)
    {
        $this->_config['format'] = $format;
    }

    /**
     * Get a log format
     *
     * @return  string
     */
    public function getFormat()
    {
        return $this->_config['format'];
    }

    /**
     * Set the path of a log file
     *
     * @param   string $path
     * @return  void
     */
    public function setFile($path)
    {
        $this->_config['file'] = $path;
    }

    /**
     * Get the path of a log file
     *
     * @return  string
     */
    public function getFile()
    {
        return $this->_config['file'];
    }

    /**
     * Set the max length of a log file
     *
     * @param   int $maxLength
     * @return  void
     */
    public function setFileMaxLength($maxLength)
    {
        $this->_config['fileMaxLength'] = $maxLength;
    }

    /**
     * Get the max length of a log file
     *
     * @return  int
     */
    public function getFileMaxLength()
    {
        return $this->_config['fileMaxLength'];
    }

    /**
     * Set an email address
     *
     * @param   string $mail
     * @return  void
     */
    public function setMail($mail)
    {
        $this->_config['mail'] = $mail;
    }

    /**
     * Get an email address
     *
     * @return  string
     */
    public function getMail()
    {
        return $this->_config['mail'];
    }

    /**
     * Set the additional headers of an email
     *
     * @param   string $additionalHeaders
     * @return  void
     */
    public function setMailAdditionalHeaders($additionalHeaders)
    {
        $this->_config['mailAdditionalHeaders'] = $additionalHeaders;
    }

    /**
     * Get the additional headers of an email
     *
     * @return  string
     */
    public function getMailAdditionalHeaders()
    {
        return $this->_config['mailAdditionalHeaders'];
    }

    /**
     * Set the level of an email notification
     *
     * @param   int $level Available values: 0-7 (LOG_EMERG-LOG_DEBUG)
     * @return  void
     */
    public function setMailFilter($level)
    {
        $this->_config['mailFilter'] = $level;
    }

    /**
     * Get the level of an email notification
     *
     * @return  int
     */
    public function getMailFilter()
    {
        return $this->_config['mailFilter'];
    }

    /**
     * Get an error log
     *
     * @return  array
     */
    public function getErrorLog()
    {
        return $this->_errorLog;
    }

}
