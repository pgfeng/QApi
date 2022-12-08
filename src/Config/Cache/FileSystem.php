<?php


namespace QApi\Config\Cache;


use QApi\Cache\FileSystemAdapter;
use QApi\Config\Abstracts\Cache;

class FileSystem extends Cache
{
    public string $driver = FileSystemAdapter::class;
    public string $name = 'FileSystem';

    public function __construct(public string $directory, public string $extension = '.cache', public int $umask = 0002, public bool $hashFileName = true)
    {
    }
}