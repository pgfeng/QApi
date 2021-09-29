<?php


namespace QApi\ORM\Connector;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\MySQL\Driver;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use QApi\Config\Database\PdoSqliteDatabase;
use QApi\ORM\Connector\ConnectorInterface;

class PdoSqliteConnector extends \QApi\ORM\Connector\Connection implements ConnectorInterface
{
    /**
     * @param PdoSqliteDatabase $config
     * @return Connection
     * @throws Exception
     */
    public function getConnector(mixed $config): Connection
    {
        $this->config = $config;
        return DriverManager::getConnection([
            'user' => $config->user,
            'password' => $config->password,
            'driverClass' => Driver::class,
            'charset' => $config->charset,
            'path' => $config->filename,
        ], $this->getConfiguration(), $this->getEventManager());
    }
}