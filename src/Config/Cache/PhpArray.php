<?php


namespace QApi\Config\Cache;


use QApi\Cache\PhpArrayCache;
use QApi\Cache\RedisCache;
use QApi\Config\Abstracts\Cache;

/**
 * PhpArray Cache Config
 * Class Redis
 * @package QApi\Config\Cache
 */
class PhpArray extends Cache
{
    public string $driver = PhpArrayCache::class;
    public string $name = 'PhpArray';

    /**
     * PhpArray constructor.
     */
    public function __construct()
    {
    }
}