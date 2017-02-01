<?php

namespace MP\Sauce;

use MP;
use Sauce;

/**
 * Class WebDriverTestCase
 * @package MP\Sauce
 */
abstract class WebDriverTestCase extends Sauce\Sausage\WebDriverTestCase
{
    protected static $testConfig = null;

    protected $adminUrl = '';

    /**
     * WebDriverTestCase constructor.
     *
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->base_url = static::getTestConfig()->getValue('base_url');
        $this->adminUrl = static::getTestConfig()->getValue('adminhtml_link');
    }

    /**
     * Returns config object
     *
     * @return MP\Config
     */
    public static function getTestConfig()
    {
        if (is_null(static::$testConfig)){
            static::$testConfig = new MP\Config();
        }

        return static::$testConfig;
    }

    /**
     * Override method, add ability to set browsers from config
     *
     * @return array
     * @throws \Exception
     */
    public static function browsers()
    {
        if (empty(static::$browsers)) {
            static::$browsers = static::getTestConfig()->getValue('browsers');
        }

        return parent::browsers();
    }
}