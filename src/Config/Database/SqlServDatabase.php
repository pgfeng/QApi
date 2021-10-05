<?php


namespace QApi\Config\Database;


use QApi\Config\Abstracts\Database;
use QApi\Enumeration\DatabaseDriver;
use QApi\ORM\Connector\SqlSrvConnector;

class SqlServDatabase extends Database
{
    public string $name = 'sqlsrv';
    public string $driver = DatabaseDriver::PDO_SQLSERV;
    public string $connectorClass = SqlSrvConnector::class;

    /**
     * PdoSqlServDatabase constructor.
     * @param string $host
     * @param int $port
     * @param string $dbName
     * @param string $user
     * @param string $password
     * @param string $tablePrefix
     */
    public function __construct(public string $host, public int $port, public string $dbName, public string $user,
                                public string $password, public string $tablePrefix = 'p_')
    {
    }
}