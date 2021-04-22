<?php


namespace QApi\Config\Cache;


use QApi\Cache\SQLite3Cache;
use QApi\Config\Abstracts\Cache;

class SQLite extends Cache
{
    public string $driver = SQLite3Cache::class;
    public string $name = 'SQLite3';

    /**
     * SQLite3 constructor.
     * @param string $dbFilename
     * @param string $table
     */
    public function __construct(public string $dbFilename, public string $table)
    {
    }
}