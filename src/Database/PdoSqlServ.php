<?php

namespace QApi\Database;

use QApi\Config\Database\PdoSqlServDatabase;
use QApi\Data;
use QApi\Database\DBase;

/**
 * Class PdoDriver
 */
class PdoSqlServ extends DBase
{

    private \PDO $db;
    private string $configName = 'default';

    /**
     * @param PdoSqlServDatabase $database
     * @return bool
     */
    public function _connect(mixed $database): bool
    {
        $this->db = new \pdo('dblib:dbname=' . $database->dbName . ';host=' . $database->host . ';port=' .
            $database->port . ';charset=UTF-8;', $database->user, $database->password, [
            \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_ORACLE_NULLS => \PDO::NULL_NATURAL,
            \PDO::ATTR_STRINGIFY_FETCHES => false,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return TRUE;

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

    /**
     * 解析出完整的SQL命令
     * 返回解析好的SQL命令或者返回false
     *
     * @return string|bool or false
     */

    public function compile(): string|bool
    {
        $this->section['table'] = $this->get_table();
        if ($this->section['handle'] === 'insert') {
            $this->sql .= 'INSERT' . ' INTO ' . $this->section['table'] . ' ' . $this->section['insert'];
        } else {
            if ($this->section['handle'] === 'select') {
                if ($this->section['limit']) {
                    $limit = explode(',', $this->section['limit']);
                    if (count($limit) === 1) {
                        $sql = "{$this->section['handle']} TOP {$limit[0]} {$this->section['select']} from {$this->section['table']}";
                    } else {
                        $offset = (int)$limit[0];
                        $end = $offset + (int)$limit[1];
                        return $this->sql .= "{$this->section['handle']} {$this->section['select']} FROM ({$this->section['handle']} {$this->section['select']}, ROW_NUMBER() " . " OVER (" . ($this->section['orderBy'] ? "order by {$this->section['orderBy']}" : '') . ") as row FROM {$this->section['table']} " . ($this->section['join'] ? " " . $this->section['join'] : '') . ($this->section['where'] ? " where {$this->section['where']}" : '') . ($this->section['group'] ? " group by {$this->section['group']}" : '') . ") a WHERE row > {$offset} and row <= {$end}";
                    }
                } else {
                    $sql = "{$this->section['handle']} {$this->section['select']} from {$this->section['table']}";
                }

            } elseif ($this->section['handle'] === 'update') {
                $sql = "{$this->section['handle']} {$this->section['table']} set {$this->section['update']}";
            } elseif ($this->section['handle'] === 'delete') {
                $sql = "{$this->section['handle']} from {$this->section['table']}";
            }
            if (!empty($sql)) {
                $sql .= ($this->section['join'] ? " " . $this->section['join'] : '') . ($this->section['where'] ? " where {$this->section['where']}" : '') . ($this->section['group'] ? " group by {$this->section['group']}" : '') . ($this->section['orderBy'] ? " order by {$this->section['orderBy']}" : '');
                return $this->sql .= $sql;
            }

            return FALSE;
        }
        return FALSE;
    }

    /**
     * 有多少条数据
     *
     * @param $field
     *
     * @return int    获取到的数量
     */
    final public function Count($field = '*'): int
    {
        $field = $this->_Field($field);
        $count = $this->select('count(' . $field . ')')->query()[0];
        return $count ? (int)$count['computed'] : 0;
    }

    public function lastInsertId(): int|null
    {

        $query = $this->query('select @@IDENTITY;');

        return $query[0]['select @@IDENTITY;'];
    }
}