<?php

/**
 * Flooer Framework
 *
 * LICENSE: BSD License (2 Clause)
 *
 * @category    Flooer
 * @package     Flooer_Gettext
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 * @copyright   Akira Ohgaki
 * @license     https://opensource.org/licenses/BSD-2-Clause  BSD License (2 Clause)
 * @link        https://github.com/akiraohgaki/flooer
 */

/**
 * Usage
 *
 * $gettext = new Flooer_Gettext();
 * $gettext->setBaseDir('./locales');
 * $gettext->setLocale('en_US');
 * $gettext->setup();
 */

/**
 * Gettext and locale information setting class
 *
 * @category    Flooer
 * @package     Flooer_Gettext
 * @author      Akira Ohgaki <akiraohgaki@gmail.com>
 */
class Flooer_Gettext
{

    /**
     * Configuration options
     *
     * @var     array
     */
    protected $_config = array(
        'baseDir' => null,
        'domain' => 'messages',
        'encoding' => 'UTF-8',
        'category' => LC_ALL,
        'locale' => 'C'
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
     * Setup for gettext
     *
     * @return  void
     */
    public function setup()
    {
        if ($this->_config['locale'] != 'C'
            && $this->_config['baseDir']
            && is_dir($this->_config['baseDir'])
        ) {
            if (!ini_get('safe_mode')) {
                @putenv('LANG=' . $this->_config['locale']);
            }
            setlocale($this->_config['category'], $this->_config['locale']);
            bindtextdomain($this->_config['domain'], $this->_config['baseDir']);
            bind_textdomain_codeset($this->_config['domain'], $this->_config['encoding']);
            textdomain($this->_config['domain']);
        }
    }

    /**
     * Set the path of a base directory
     *
     * @param   string $path
     * @return  void
     */
    public function setBaseDir($path)
    {
        $this->_config['baseDir'] = $path;
    }

    /**
     * Get the path of a base directory
     *
     * @return  string
     */
    public function getBaseDir()
    {
        return $this->_config['baseDir'];
    }

    /**
     * Set a DOMAIN message catalog
     *
     * @param   string $domain
     * @return  void
     */
    public function setDomain($domain)
    {
        $this->_config['domain'] = $domain;
    }

    /**
     * Get a DOMAIN message catalog
     *
     * @return  string
     */
    public function getDomain()
    {
        return $this->_config['domain'];
    }

    /**
     * Set a character encoding
     *
     * @param   string $encoding Codeset in a DOMAIN message catalog
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
     * Set a category for locale information
     *
     * @param   int $category Available values to setlocale()
     * @return  void
     */
    public function setCategory($category)
    {
        $this->_config['category'] = $category;
    }

    /**
     * Get a category for locale information
     *
     * @return  int
     */
    public function getCategory()
    {
        return $this->_config['category'];
    }

    /**
     * Set locale information
     *
     * @param   string $locale Available values to setlocale()
     * @return  void
     */
    public function setLocale($locale)
    {
        $this->_config['locale'] = $locale;
    }

    /**
     * Get locale information
     *
     * @return  string
     */
    public function getLocale()
    {
        return $this->_config['locale'];
    }

}
