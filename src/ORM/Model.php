<?php


namespace QApi\ORM;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use JetBrains\PhpStorm\Deprecated;
use QApi\Config;
use QApi\Config\Database\MysqliDatabase;
use QApi\Config\Database\PdoMysqlDatabase;
use QApi\Config\Database\PdoSqliteDatabase;
use QApi\Data;
use QApi\Database\DBase;
use QApi\Logger;


class Model extends DB
{

    /**
     * Model constructor.
     * @param string|null $table
     * @param string $configName
     */
    public function __construct(string $table = null, string $configName = 'default')
    {
        if ($table) {
            parent::__construct($table, $configName);
        } else {
            $tb_name = substr(get_class($this), 6);
            $class = substr($tb_name, (($start = strrpos($tb_name, '\\')) > 0 ? $start + 1 : 0));
            $num = strpos($class, 'Model');
            if ($num !== 0) {
                $table = substr($class, 0, $num);
            } else {
                $table = substr($table, 0, strpos($table, 'Model'));
            }
            $table = strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $table));
            parent::__construct($table, $configName);
        }
    }


    public static function model(): self
    {
        return new static();
    }

}