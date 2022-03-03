<?php


namespace QApi\Config\Cache;


use QApi\Cache\RedisAdapter;
use QApi\Config\Abstracts\Cache;

/**
 * Redis Cache Config
 * Class Redis
 * @package QApi\Config\Cache
 */
class Redis extends Cache
{
    public string $driver = RedisAdapter::class;
    public string $name = 'Redis';

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