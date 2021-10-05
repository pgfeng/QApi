<?php


namespace QApi\ORM\Connector;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\SQLSrv\Driver;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use QApi\Config\Database\MysqliDatabase;
use QApi\Config\Database\PdoMysqlDatabase;
use QApi\Config\Database\PdoSqliteDatabase;

class SqlSrvConnector extends \QApi\ORM\Connector\Connection implements ConnectorInterface
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
            'user' => $config->user,
            'password' => $config->password,
            'dbname' => $config->dbName,
            'host' => $config->host,
            'port' => $config->port,
            'driverClass' => Driver::class
        ], $this->getConfiguration(), $this->getEventManager());
    }
}