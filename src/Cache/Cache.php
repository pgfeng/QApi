<?php


namespace QApi\Cache;


use ErrorException;
use QApi\App;
use QApi\Config;
use QApi\Enumeration\RunMode;
use QApi\Exception\CacheErrorException;

class Cache
{

    /**
     * @param string $configName
     * @return CacheInterface
     * @throws CacheErrorException|ErrorException
     */
    public static function initialization(string $configName = 'default'): CacheInterface
    {

        if (!is_cli()) {
            $runMode = Config::app()->getRunMode();
        } else if (defined('DEV_MODE') && DEV_MODE === true) {
            $runMode = RunMode::DEVELOPMENT;
        } else {
            $runMode = RunMode::PRODUCTION;
        }
        $cache = Config::cache($configName);
        if ($cache === null) {
            throw new CacheErrorException('Cache [' . $configName . '] is Undefined!',
                0, 1, PROJECT_PATH . App::$configDir . DIRECTORY_SEPARATOR . $runMode
                . DIRECTORY_SEPARATOR . 'cache.php');
        }

        return new $cache->driver($cache);
    }
}