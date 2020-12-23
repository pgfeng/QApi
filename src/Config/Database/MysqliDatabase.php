<?php


namespace QApi\Config;


use QApi\Config\Abstracts\Database;
use QApi\Enumeration\DatabaseDriver;

class MysqliDatabase extends Database
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
    public function __construct(public string $host, public int $port, public string $dbName, public string $user, public string $password)
    {
    }

    public function getDSN(): string
    {
        return 'mysql:dbname=' . $this->dbName . ';host=' . $this->host . ';port=' . $this->port . ';';
    }

}