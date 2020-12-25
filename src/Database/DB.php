<?php


namespace QApi\Database;


use QApi\Model;

/**
 * Class DB
 * @package QApi\Database
 */
class DB
{
    public static $DBC = false;

    /**
     * @param        $table_name
     * @param string $config_name
     * @return Model
     */
    public static function table($table_name, $config_name = 'default'): Model
    {
        return new Model($table_name, $config_name);
    }

}