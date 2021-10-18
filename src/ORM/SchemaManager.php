<?php


namespace QApi\ORM;


use Doctrine\DBAL\Schema\AbstractSchemaManager;
use QApi\Config;

class SchemaManager
{
    /**
     * @var AbstractSchemaManager[]
     */
    public static array $schemaManager = [];

    /**
     * @param string $configName
     */
    public static function create(string $configName = 'default'): AbstractSchemaManager
    {
        $config = Config::database($configName);
        return self::$schemaManager[$configName] ?? (self::$schemaManager[$configName] = (new
            ($config->connectorClass)())?->getConnector
            ($config)?->createSchemaManager());
    }
}