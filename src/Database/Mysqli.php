<?php

namespace QApi\Database;


use QApi\Config\Database\MysqliDatabase;
use QApi\Data;
use QApi\Database\DBase;

/**
 * Class mysqliDriver
 */
class Mysqli extends DBase
{
    /**
     * @var \mysqli
     */
    public \mysqli $mysqli;

    /**
     * @param MysqliDatabase $database
     *
     * @return bool
     */
    public function _connect(mixed $database): bool
    {
        //=====使用长连接
        $mysqli = new \mysqli($database->host, $database->user, $database->password, $database->dbName, $database->port);
        if ($mysqli->connect_error) {
            new \ErrorException('连接数据库失败：' . $mysqli->connect_error);
            return false;
        }

        $this->mysqli = $mysqli;
        $this->mysqli->set_charset($database->charset);
        return TRUE;
    }

    /**
     * @param $string
     *
     * @return string
     */
    public function real_escape_string($string): string
    {
        $string = mysqli_real_escape_string($this->mysqli, $string);
        if (is_numeric($string)) {
            return $string;
        }

        return '\'' . $string . '\'';
    }

    /**
     * 返回错误信息
     * @return string
     */
    public function getError(): string
    {
        return $this->mysqli->error;
    }

    /**
     * @param $sql
     * @return array | Data
     */
    public function &_query($sql): Data|array
    {
        $query = $this->mysqli->query($sql);
        $result = [];
        if ($query) {
            while ($row = $query->fetch_assoc()) {
                $data = $row;
                $result[] = new Data($data);
                unset($data);
            }
            unset($query);
            return $result;
        }
        return $result;
    }

    /**
     * @return bool
     */
    public function close(): bool
    {
        return mysqli_close($this->mysqli);
    }

    /**
     * @param $sql
     * @return bool
     */
    public function _exec($sql): bool
    {
        return $this->mysqli->query($sql);
    }

    /**
     * @return bool
     */
    public function rollBack(): bool
    {
        return $this->mysqli->rollback();
    }

    /**
     * @return bool
     */
    public function commit(): bool
    {
        return $this->mysqli->commit();
    }

    /**
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->mysqli->autocommit(FALSE);
    }
}