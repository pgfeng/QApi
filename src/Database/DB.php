<?php


namespace QApi\Database;


use QApi\Model;

/**
 * Class DB
 * @package QApi\Database
 */
class DB
{
    /**
     * @var PdoSqlServ[]|PdoMysql[]|PdoSqlite[]|Mysqli[]
     */
    public static array $DBC = [];
    public static array $DBC_CON_TIME = [];

    /**
     * @param        $table_name
     * @param string $config_name
     * @return Model
     */
    public static function table($table_name, $config_name = 'default'): Model
    {
        return new Model($table_name, $config_name);
    }

    public static function clearDBC($wait_timeout = 3600): void
    {
        foreach (self::$DBC_CON_TIME as $configName => $time) {
            if (time() - $time > $wait_timeout - 300) {
                self::remove($configName);
            }
        }
    }

    public static function remove(string $configName): void
    {
        if (isset(self::$DBC[$configName])) {
            unset(self::$DBC[$configName]);
        }
        if (isset(self::$DBC_CON_TIME[$configName])) {
            unset(self::$DBC_CON_TIME[$configName]);
        }
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