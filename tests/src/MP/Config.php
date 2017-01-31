<?php

namespace MP;

/**
 * Configuration CLass
 *
 * Class Config
 * @package MP
 */
class Config
{
    public $params = [];

    /**
     * Config constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        $configDir  = realpath(__DIR__ . '/../../../etc');
        $configFile = $configDir . '/config.staging.php';
        $localFile  = $configDir . '/config.local.php';

        if (!file_exists($configFile)) {
            throw new \Exception('Config file not found');
        }

        $config = include $configFile;

        if (file_exists($localFile)) {
            $localConfig = include $localFile;
            $config = array_replace_recursive($config, $localConfig);
        }

        $this->params = $config;
    }

    /**
     * Returns specific value from config
     *
     * @param string $param
     * @return array|null
     * @throws \Exception
     */
    public function getValue($param)
    {
        if (!is_string($param)) {
            throw new \Exception('Parameter should be a string');
        }

        return isset($this->params[$param])
            ? $this->params[$param]
            : null;
    }
}