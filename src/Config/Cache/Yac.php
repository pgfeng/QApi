<?php

namespace QApi\Config\Cache;

use QApi\Cache\YacAdapter;
use QApi\Config\Abstracts\Cache;

class Yac extends Cache
{
    public string $driver = YacAdapter::class;
    public string $name = 'Apcu';

    /**
     * @param string $namespace
     */
    public function __construct(public string $namespace = 'default-->')
    {
    }
}