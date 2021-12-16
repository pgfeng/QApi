<?php


namespace QApi\Config\Database;


use QApi\Config\Abstracts\Database;
use QApi\Enumeration\DatabaseDriver;
use QApi\ORM\Connector\MysqliConnector;

class MysqliDatabase extends Database
{
    public string $name = 'mysqli';
    public string $driver = DatabaseDriver::MYSQLI;
    public string $connectorClass = MysqliConnector::class;

    /**
     * PdoMysqlDatabase constructor.
     * @param string $host
     * @param int $port
     * @param string $dbName
     * @param string $user
     * @param string $password
     * @param string $tablePrefix
     * @param string $charset
     * @param int $wait_timeout
     */
    public function __construct(public string $host, public int $port, public string $dbName, public string $user,
                                public string $password, public string $tablePrefix = 'p_', public string $charset = 'utf8mb4', public int $wait_timeout = 3600)
    {
    }


}