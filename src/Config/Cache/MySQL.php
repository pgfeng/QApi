<?php


namespace QApi\Config\Cache;


use QApi\Cache\MySQLAdapter;
use QApi\Config\Abstracts\Cache;
use QApi\Config\Database\MysqliDatabase;
use QApi\Config\Database\PdoMysqlDatabase;

class MySQL extends Cache
{
    public string $driver = MySQLAdapter::class;
    public string $name = 'MySQL';

    /**
     * MySQL constructor.
     * @param MysqliDatabase|PdoMysqlDatabase $database
     * @param int $maxKeyLength
     * @param string $table
     * @param string $keyCol
     * @param string $dataCol
     * @param string $lifetimeCol
     * @param string $expiresTimeCol
     * @param string $timeCol
     * @param string $namespace
     * @param int $cleanUpTime
     */
    public function __construct(
        public MysqliDatabase|PdoMysqlDatabase $database,
        public int $maxKeyLength = 255,
        public string $table = 'cache_items',
        public string $keyCol = 'item_key',
        public string $dataCol = 'item_data',
        public string $lifetimeCol = 'item_lifetime',
        public string $expiresTimeCol = 'item_expires_time',
        public string $timeCol = 'item_time',
        public string $namespace = 'default-->',
        public int $cleanUpTime = 60 * 60,
    )
    {

    }

}