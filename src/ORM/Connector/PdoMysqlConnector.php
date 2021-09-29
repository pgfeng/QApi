<?php


namespace QApi\ORM\Connector;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\MySQL\Driver;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use QApi\Config\Database\MysqliDatabase;
use QApi\Config\Database\PdoMysqlDatabase;
use QApi\Config\Database\PdoSqliteDatabase;
use QApi\Config\Database\PdoSqlServDatabase;
use QApi\Config\Database\SqlServDatabase;

class PdoMysqlConnector extends \QApi\ORM\Connector\Connection implements ConnectorInterface
{

    /**
     * @param PdoMysqlDatabase $config
     * @return Connection
     * @throws Exception
     */
    public function getConnector(mixed $config): Connection
    {
        $this->config = $config;
        return DriverManager::getConnection([
            'dbname' => $config->dbName,
            'user' => $config->user,
            'password' => $config->password,
            'host' => $config->host,
            'port' => $config->port,
            'driverClass' => Driver::class,
            'charset' => $config->charset,
        ], $this->getConfiguration(), $this->getEventManager());
    }
}