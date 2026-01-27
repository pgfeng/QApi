<?php


namespace QApi\Config\Cache;


use QApi\Cache\RedisNativeAdapter;
use QApi\Config\Abstracts\Cache;

/**
 * Redis Cache Config
 * Class Redis
 * @package QApi\Config\Cache
 */
class RedisNative extends Cache
{
    public string $driver = RedisNativeAdapter::class;
    public string $name = 'RedisNative';

    /**
     * Redis constructor.
     * @param string $host
     * @param int $port
     * @param string $scheme
     * @param string $username
     * @param string $password
     * @param int $database
     * @param string $prefix
     */
    public function __construct(
        public string $host = '127.0.0.1',
        public int $port = 6379,
        public string $scheme = 'tcp',
        public string $username = '',
        public string $password = '',
        public int $database = 0,
        public string $prefix = 'cacheDefaultSpace:',
    )
    {
    }
}