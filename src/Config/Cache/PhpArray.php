<?php


namespace QApi\Config\Cache;


use QApi\Cache\PhpArrayAdapter;
use QApi\Cache\RedisAdapter;
use QApi\Config\Abstracts\Cache;

/**
 * PhpArray Cache Config
 * Class Redis
 * @package QApi\Config\Cache
 */
class PhpArray extends Cache
{
    public string $driver = PhpArrayAdapter::class;
    public string $name = 'PhpArray';

    /**
     * PhpArray constructor.
     */
    public function __construct()
    {
    }
}