<?php


namespace QApi\Cache;


use QApi\Config;
use QApi\Exception\CacheErrorException;

class Cache
{

    /**
     * @param string $configName
     * @return CacheInterface
     */
    public static function initialization(string $configName = 'default'): CacheInterface
    {
        $cache = Config::cache($configName);
        if ($cache === null) {
            throw new CacheErrorException('Cache [' . $configName . '] is Undefined!');
        }

        return new $cache->driver($cache);
    }
}