<?php

namespace QApi\Database;


use QApi\Config\Abstracts\Database;
use QApi\Config\Database\PdoMysqlDatabase;
use QApi\Data;
use QApi\Database\DBase;
use QApi\Logger;

/**
 * Class PdoDriver
 */
class PdoMysql extends DBase
{

    private \PDO $db;
    private string $configName = 'default';

    /**
     * @param PdoMysqlDatabase $database
     * @return bool
     */
    public function _connect(mixed $database): bool
    {
        $this->db = new \pdo('mysql:dbname=' . $database->dbName . ';host=' . $database->host . ';port=' .
            $database->port . ';', $database->user, $database->password, [
            \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_ORACLE_NULLS => \PDO::NULL_NATURAL,
            \PDO::ATTR_STRINGIFY_FETCHES => false,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $this->exec('set names ' . $database->charset);
        return TRUE;
    }

    /**
     * 获取最后自增ID
     *
     * @return int|null
     */
    final public function lastInsertId(): int|null
    {
        $query = $this->query('SELECT LAST_INSERT_ID()');

        return $query[0]['LAST_INSERT_ID()'];
    }

    /**
     * 返回错误信息
     *
     * @return string
     */
    public function getError(): string
    {
        return implode(' | ', $this->db->errorInfo());
    }

    /**
     * 数据库驱动必须创建下列方法
     * 并且必须返回正确的值
     *
     * @param $sql
     *
     * @return array|Data
     */
    public function _query($sql): array|Data
    {
        $query = $this->db->query($sql);

        if ($query) {
            $result = [];
            $data = $query->fetchAll(\PDO::FETCH_ASSOC);   //只获取键值
            foreach ($data as &$item) {
                $result[] = new Data($item);
            }
            return $result;
        }
        unset($query);

        return [];

    }

    /**
     * @param $string
     *
     * @return string
     */
    public function real_escape_string($string): string
    {
        return $this->db->quote($string);
    }

    /**
     * @param $sql
     *
     * @return false|int
     */
    public function _exec($sql): false|int
    {
        return $this->db->exec($sql);
    }

    /**
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->db->beginTransaction();
    }

    /**
     * @return bool
     */
    public function commit(): bool
    {
        return $this->db->commit();
    }

    /**
     * @return bool
     */
    public function rollBack(): bool
    {
        return $this->db->rollBack();
    }

    /**
     *
     */
    public function close(): void
    {

    }
}