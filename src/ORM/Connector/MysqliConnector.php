<?php


namespace QApi\ORM\Connector;


use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Mysqli\Driver;
use Doctrine\DBAL\DriverManager;
use QApi\Config\Database\MysqliDatabase;
use QApi\Config\Database\PdoMysqlDatabase;
use QApi\Config\Database\PdoSqliteDatabase;
use QApi\Logger;
use QApi\ORM\SQLLogger;

class MysqliConnector extends \QApi\ORM\Connector\Connection implements ConnectorInterface
{

    /**
     * @param MysqliDatabase $config
     * @return Connection
     * @throws \Doctrine\DBAL\Exception
     */
    public function getConnector(mixed $config): Connection
    {
        $this->config = $config;
        return DriverManager::getConnection([
            'dbname' => $config->dbName,
            'user' => $config->user,
            'host' => $config->host,
            'password' => $config->password,
            'port' => $config->port,
            'driverClass' => Driver::class,
            'charset' => $config->charset,
            'persistent' => true,
        ], $this->getConfiguration(), $this->getEventManager());
    }
}