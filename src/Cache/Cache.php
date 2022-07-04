<?php


namespace QApi\Cache;


use ErrorException;
use QApi\App;
use QApi\Config;
use QApi\Config\Cache\FileSystem;
use QApi\Enumeration\RunMode;
use QApi\Exception\CacheErrorException;

class Cache
{
    private static array $adapters = [];

    /**
     * @param string $configName
     * @return CacheInterface
     * @throws CacheErrorException|ErrorException
     */
    public static function initialization(string $configName = 'default'): CacheInterface
    {
        if (isset(self::$adapters[$configName])) {
            return self::$adapters[$configName];
        }
        if ($configName === '__document') {
            return self::$adapters[$configName] = new FileSystemAdapter(new FileSystem(PROJECT_PATH . '.document'));
        }
        if (!is_cli()) {
            $runMode = Config::app()->getRunMode();
        } else if (defined('RUN_MODE')) {
            $runMode = RUN_MODE;
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
        return (self::$adapters[$configName] = new $cache->driver($cache));
    }

    /**
     * @param $configName
     * @param CacheInterface $cacheAdapter
     * @return CacheInterface
     */
    public static function add($configName, CacheInterface $cacheAdapter): CacheInterface
    {
        return self::$adapters[$configName] = $cacheAdapter;
    }

    /**
     * @param $configName
     * @return void
     */
    public static function remove($configName): void
    {
        unset(self::$adapters[$configName]);
    }
}