<?php


namespace QApi\Config\Cache;


use QApi\Cache\FileSystemCache;
use QApi\Config\Abstracts\Cache;

class FileSystem extends Cache
{
    public string $driver = FileSystemCache::class;
    public string $name = 'FileSystem';

    public function __construct(public string $directory, public string $extension = '.cache', public int $umask = 0002)
    {
    }
}