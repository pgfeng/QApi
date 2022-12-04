<?php


namespace QApi\Config\Abstracts;


use QApi\Cache\CacheContainer;
use QApi\Cache\CacheInterface;
use QApi\Cache\FileSystemAdapter;
use QApi\Config\Cache\FileSystem;

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
     * @return $this
     */
    public function setCacheAdapter(?CacheInterface $cacheAdapter): self
    {
        $this->cacheAdapter = new CacheContainer($cacheAdapter,'Database');
        return $this;
    }

    /**
     * @param $databaseName
     * @return $this
     * @throws \QApi\Exception\CacheErrorException
     */
    public function defaultCacheAdapter($databaseName): self
    {
        return $this->setCacheAdapter(new FileSystemAdapter(new FileSystem(PROJECT_PATH . \QApi\App::$runtimeDir . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . $databaseName)));
    }

}