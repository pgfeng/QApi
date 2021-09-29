<?php


namespace QApi\Config\Database;


use QApi\Config\Abstracts\Database;
use QApi\Enumeration\DatabaseDriver;
use QApi\ORM\Connector\PdoMysqlConnector;

class PdoMysqlDatabase extends Database
{
    public string $name = 'pdo_mysql';
    public string $driver = DatabaseDriver::PDO_MYSQL;
    public string $connectorClass = PdoMysqlConnector::class;

    /**
     * PdoMysqlDatabase constructor.
     * @param string $host
     * @param int $port
     * @param string $dbName
     * @param string $user
     * @param string $password
     * @param string $tablePrefix
     * @param string $charset
     */
    public function __construct(public string $host, public int $port, public string $dbName, public string $user,
                                public string $password,public string $tablePrefix = 'p_',public string $charset='utf8mb4')
    {
    }

}