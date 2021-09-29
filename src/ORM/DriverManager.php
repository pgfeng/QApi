<?php


namespace QApi\ORM;


use Doctrine\DBAL\Query\QueryBuilder;
use QApi\Config;

class DriverManager
{

    /**
     * @var QueryBuilder[]
     */
    public static array $DBC = [];

    /**
     * @param $configName
     * @return QueryBuilder
     * @throws \ErrorException
     */
    public static function connect($configName): QueryBuilder
    {
        $config = Config::database($configName);
        return self::$DBC[$configName] ?? (self::$DBC[$configName] = (new ($config->connectorClass)())?->getConnector
            ($config)->createQueryBuilder());
    }
}