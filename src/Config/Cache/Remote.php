<?php

namespace QApi\Config\Cache;

use QApi\Cache\RemoteAdapter;
use QApi\Config\Abstracts\Cache;

class Remote extends Cache
{
    public string $driver = RemoteAdapter::class;
    public string $name = 'Remote';

    public function __construct(
        public string $host,
        public int    $port = 80,
        public string $scheme = 'http',
        public string $username = '',
        public string $password = '',
        public string $path = '')
    {
    }
}