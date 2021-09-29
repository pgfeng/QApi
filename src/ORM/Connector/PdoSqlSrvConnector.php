<?php


namespace QApi\ORM\Connector;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\SQLSrv\Driver;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\SQLServer2012Platform;
use QApi\Config\Database\MysqliDatabase;
use QApi\Config\Database\PdoMysqlDatabase;
use QApi\Config\Database\PdoSqliteDatabase;
use QApi\Config\Database\PdoSqlServDatabase;
use QApi\Config\Database\SqlServDatabase;

class PdoSqlSrvConnector extends \QApi\ORM\Connector\Connection implements ConnectorInterface
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
            'driverClass' => Driver::class,
            'platform' => SQLServer2012Platform::class,
            'charset' => $config->charset,
        ], $this->getConfiguration(), $this->getEventManager());
    }
}