<?php


namespace QApi\Config;


use QApi\Config\Abstracts\Database;
use QApi\Enumeration\DatabaseDriver;

class PdoMysqlDatabase extends Database
{
    public string $name = 'mysql';
    public string $driver = DatabaseDriver::PDO_MYSQL;

    /**
     * PdoMysqlDatabase constructor.
     * @param string $host
     * @param int $port
     * @param string $dbName
     * @param string $user
     * @param string $password
     */
    public function __construct(public string $host, public int $port, public string $dbName, public string $user,
                                public string $password)
    {
    }
}