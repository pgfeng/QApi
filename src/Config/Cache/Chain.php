<?php


namespace QApi\Config\Cache;


use QApi\Cache\ChainAdapter;
use QApi\Cache\RedisAdapter;
use QApi\Config\Abstracts\Cache;

class Chain extends Cache
{
    public string $driver = ChainAdapter::class;
    public string $name = 'Chain';

    /**
     * Redis constructor.
     * @param Cache[] $adapters
     */
    public function __construct(
        public array $adapters = [],
    )
    {
    }
}