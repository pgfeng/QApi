<?php


namespace QApi\Config;


use QApi\Config\Abstracts\Database;

class MysqlDatabase extends Database
{
    public string $name = 'mysql';
    public string $driver = 'PDO';

    public function __construct(public string $host, public int $port, public string $user, public string $password)
    {
    }
}