<?php

namespace QApi\Config\Database;

use QApi\Enumeration\DatabaseDriver;
use QApi\ORM\Connector\MysqliConnector;

class MongoDatabase
{
    public string $name = 'MongoDB';
    public string $driver = DatabaseDriver::MONGODB;

    /**
     * PdoMysqlDatabase constructor.
     * @param string $host
     * @param int $port
     * @param string $dbName
     * @param string|null $user
     * @param string|null $password
     * @param string $tablePrefix
     * @param string $charset
     * @param int $wait_timeout
     */
    public function __construct(public string  $host, public string $dbName, public int $port = 27017, public ?string $user =
    null,
                                public ?string $password = null, public string $tablePrefix = 'p_', public string $charset = 'utf8mb4', public int $wait_timeout = 3600)
    {
    }
}