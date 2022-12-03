<?php


namespace QApi\Config\Abstracts;


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
     * @return Database
     */
    public function setCacheAdapter(?CacheInterface $cacheAdapter): static
    {
        $this->cacheAdapter = $cacheAdapter;
        return $this;
    }

    /**
     * @param $databaseName
     * @return void
     * @throws \QApi\Exception\CacheErrorException
     */
    public function openDefaultCacheAdapter($databaseName)
    {
        $this->cacheAdapter = new FileSystemAdapter(new FileSystem(PROJECT_PATH . \QApi\App::$runtimeDir . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'amaze'));
    }

}