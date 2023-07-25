<?php

namespace QApi\Config\Cache;

use QApi\Cache\RemoteAdapter;
use QApi\Config\Abstracts\Cache;

/**
 * Class Remote
 * @package QApi\Config\Cache
 */
class Remote extends Cache
{
    public string $driver = RemoteAdapter::class;
    public string $name = 'Remote';

    /**
     * Remote constructor.
     * @param string $host
     * @param int $port
     * @param string $scheme
     * @param string $username
     * @param string $password
     * @param string $configName
     * @param string $path
     */
    public function __construct(
        public string $host,
        public int    $port = 80,
        public string $scheme = 'http',
        public string $username = '',
        public string $password = '',
        public string $configName = 'default',
        public string $path = '____QApiCache____')
    {
    }
}