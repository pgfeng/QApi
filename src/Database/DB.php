<?php


namespace QApi\Database;


use QApi\Model;

/**
 * Class DB
 * @package QApi\Database
 */
class DB
{
    public static array $DBC = [];

    /**
     * @param        $table_name
     * @param string $config_name
     * @return Model
     */
    public static function table($table_name, $config_name = 'default'): Model
    {
        return new Model($table_name, $config_name);
    }

    /**
     * @param $db
     * @param string $config_name
     * @return Model
     */
    public static function db($db, $config_name = 'default'): Model
    {
        $model = new Model(false, $config_name);
        $model->query('use ' . $db);
        return $model;
    }
}