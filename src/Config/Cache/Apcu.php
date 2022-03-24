<?php

namespace QApi\Config\Cache;

use QApi\Cache\ApcuAdapter;
use QApi\Config\Abstracts\Cache;
use QApi\Exception\CacheErrorException;

class Apcu extends Cache
{
    public string $driver = ApcuAdapter::class;
    public string $name = 'Apcu';

    /**
     * @param string $namespace
     */
    public function __construct(public string $namespace = 'default-->')
    {
    }

}