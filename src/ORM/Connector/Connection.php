<?php


namespace QApi\ORM\Connector;


use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use JetBrains\PhpStorm\Pure;
use QApi\Config\Database\MysqliDatabase;
use QApi\Config\Database\PdoMysqlDatabase;
use QApi\Config\Database\PdoSqliteDatabase;
use QApi\Config\Database\PdoSqlServDatabase;
use QApi\Config\Database\PdoSqlServDBLIBDatabase;
use QApi\Config\Database\SqlServDatabase;
use QApi\ORM\SQLLogger;

abstract class Connection
{
    public MysqliDatabase|PdoMysqlDatabase|PdoSqliteDatabase|PdoSqlServDatabase|SqlServDatabase $config;

    /**
     * @return Configuration
     */
    public function getConfiguration(): Configuration
    {
        $Configuration = new Configuration();
        $Configuration->setSQLLogger(new SQLLogger());
        return $Configuration;
    }

    /**
     * @return EventManager
     */
    #[Pure] public function getEventManager(): EventManager
    {
        return new EventManager();
    }
}