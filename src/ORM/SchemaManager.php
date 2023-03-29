<?php


namespace QApi\ORM;


use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use ErrorException;

class SchemaManager
{
    /**
     * @var AbstractSchemaManager[]
     */
    public static array $schemaManager = [];

    /**
     * @param string $configName
     * @throws Exception|ErrorException
     */
    public static function create(string $configName = 'default'): AbstractSchemaManager
    {
        return self::$schemaManager[$configName] ?? (self::$schemaManager[$configName] = DriverManager::connect($configName)
                ?->createSchemaManager());
    }
}