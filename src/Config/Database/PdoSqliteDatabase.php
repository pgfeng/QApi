<?php


namespace QApi\Config\Database;


use QApi\Config\Abstracts\Database;
use QApi\Enumeration\DatabaseDriver;

class PdoSqliteDatabase extends Database
{
    public string $name = 'sqlite';
    public string $driver = DatabaseDriver::PDO_SQLITE;

    /**
     * PdoSqliteDatabase constructor.
     * @param string $filename
     * @param string $tablePrefix
     * @param string $charset
     */
    public function __construct(
        public string $filename = ':memory:', public string $tablePrefix = 'p_', public string $charset = 'utf8mb4'
    )
    {
    }
}