<?php


namespace QApi\ORM;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use QApi\Config;

class DriverManager
{

    /**
     * @var Connection[]
     */
    public static array $DBC = [];

    /**
     * @param $configName
     * @return Connection
     * @throws \ErrorException
     */
    public static function connect($configName): Connection
    {
        $config = Config::database($configName);
        return self::$DBC[$configName] ?? (self::$DBC[$configName] = (new ($config->connectorClass)())?->getConnector
            ($config));
    }
}