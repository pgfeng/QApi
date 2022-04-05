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
     * 默认的主键名
     * @var string
     */
    public string $primary_key = 'id';

    /**
     * Model constructor.
     * @param string|null $table
     * @param string $configName
     */
    public function __construct(string $table = null, string $configName = 'default')
    {
        $this->initialization($table, $configName);
    }

    /**
     * @param string|null $table
     * @param string $configName
     */
    protected function initialization(string $table = null, string $configName = 'default')
    {

        if (!$table) {
            $tb_name = substr(get_class($this), 6);
            $class = substr($tb_name, (($start = strrpos($tb_name, '\\')) > 0 ? $start + 1 : 0));
            $num = strpos($class, 'Model');
            if ($num !== 0) {
                $table = substr($class, 0, $num);
            } else {
                $table = substr($table, 0, strpos($table, 'Model'));
            }
            $table = strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $table));
        }
        parent::__construct($table, $configName);
    }

    /**
     * @param Data|array $data
     * @param string|null $primary_key
     * @return int
     */
    public function save(Data|array $data, ?string $primary_key = null): int
    {
        if (!$primary_key) {
            $primary_key = $this->primary_key;
        }
        if (isset($data[$primary_key])) {
            $primary_value = $data[$primary_key];
            unset($data[$primary_key]);
            return self::model()->where($primary_key, $primary_value)->update($data);
        }

        return self::model()->insert($data);
    }

    public static function model(): static
    {
        return new static();
    }

    /**
     * 写法兼容
     * @param      $primary_value
     * @param bool $primary_key
     * @return Data|null
     */
    public function findByPk($primary_value, string|false|null $primary_key = false): Data|null
    {
        if (!$primary_key) {
            $primary_key = $this->primary_key;
        }
        return $this->findByKey($primary_value, $primary_key);
    }
}