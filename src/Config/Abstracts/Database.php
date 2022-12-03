<?php


namespace QApi\Config\Abstracts;


use QApi\Cache\CacheInterface;

abstract class Database
{
    /**
     * @var string
     */
    public string $name;

    /**
     * @var string driver class name
     */
    public string $driver;

    /**
     * @var CacheInterface|null
     */
    public ?CacheInterface $cacheAdapter = null;

    /**
     * @param ?CacheInterface $cacheAdapter
     * @return Database
     */
    public function setCacheAdapter(?CacheInterface $cacheAdapter): static
    {
        $this->cacheAdapter = $cacheAdapter;
        return $this;
    }

}