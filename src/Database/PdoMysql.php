<?php

namespace GFPHP\Database;

use GFPHP\Config, GFPHP\DBase;
use GFPHP\DataObject;

/**
 * Class PdoDriver
 */
class PdoMysql extends DBase
{
    /**
     * @var \PDO
     */
    private $db;
    private $configName = 'default';

    /**
     * @param $configName
     *
     * @return bool
     */
    public function _connect($configName)
    {
        $config = Config::database($configName);
        $this->configName = $configName;
        try {
            $this->db = new \pdo('mysql:dbname=' . $config['name'] . ';host=' . $config['host'] . ';port=' . $config['port'] . ';', $config['user'], $config['pass'], [
                \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_ORACLE_NULLS => \PDO::NULL_NATURAL,
                \PDO::ATTR_STRINGIFY_FETCHES => false,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $this->exec('set names ' . $config['charset']);
            return TRUE;
        } catch (\PDOException $e) {
            new \Exception('连接数据库失败：' . $e->getMessage(), 0);
        }
    }

    /**
     * 返回错误信息
     *
     * @return string
     */
    public function getError()
    {
        return implode(' | ', $this->db->errorInfo());
    }

    /**
     * 数据库驱动必须创建下列方法
     * 并且必须返回正确的值
     *
     * @param $sql
     *
     * @return array
     */
    public function _query($sql)
    {
        $query = $this->db->query($sql);
        if (!$query) {
            return [];
        }
        $result = $query->fetchAll(\PDO::FETCH_ASSOC);   //只获取键值
        foreach ($result as &$item) {
            $item = new DataObject($item, TRUE, $this->table, $this->configName);
        }
        unset($query);

        return $result;

    }

    /**
     * @param $string
     *
     * @return string
     */
    public function real_escape_string($string)
    {
        return $this->db->quote($string);
    }

    /**
     * @param $sql
     *
     * @return mixed
     */
    public function _exec($sql)
    {
        return $this->db->exec($sql);
    }

    /**
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->db->beginTransaction();
    }

    /**
     * @return bool
     */
    public function commit()
    {
        return $this->db->commit();
    }

    /**
     * @return bool
     */
    public function rollBack()
    {
        return $this->db->rollBack();
    }

    /**
     *
     */
    public function close()
    {

    }
}