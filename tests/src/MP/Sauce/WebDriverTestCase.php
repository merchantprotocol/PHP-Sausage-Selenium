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
    protected $testConfig;
    protected $adminUrl = '';

    public static $browsers = [
        [
            'browserName' => 'chrome',
            'desiredCapabilities' => [
                'version' => '45.0',
                'platform' => 'OS X 10.10',
            ],
        ],
    ];

    /**
     * WebDriverTestCase constructor.
     *
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = NULL, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->testConfig = new MP\Config();

        $this->base_url = $this->getTestConfig()->getValue('base_url');
        $this->adminUrl = $this->getTestConfig()->getValue('adminhtml_link');
    }

    /**
     * Returns config object
     *
     * @return MP\Config
     */
    public function getTestConfig()
    {
        return $this->testConfig;
    }
}