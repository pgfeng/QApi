<?php


namespace QApi\ORM;


use Doctrine\DBAL\Connection;
use QApi\Config;
use QApi\Config\Abstracts\Database;

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

    /**
     * @param $configName
     * @param Database $config
     * @return Connection
     * @throws \ErrorException
     */
    public static function addConnect($configName, Database $config): Connection
    {
        if (isset(self::$DBC[$configName])) {
            return self::$DBC[$configName];
        }
        return self::$DBC[$configName] = (new ($config->connectorClass)())?->getConnector($config);
    }
}