<?php

namespace QApi\Database;

use ArrayAccess;
use Closure;
use Exception;
use QApi\Config;
use QApi\Config\Abstracts\Database;
use QApi\Config\Database\MysqliDatabase;
use QApi\Config\Database\PdoMysqlDatabase;
use QApi\Config\Database\PdoSqlServDatabase;
use QApi\Data;
use QApi\Enumeration\CliColor;
use QApi\Exception\SqlErrorException;
use QApi\Logger;


/**
 * 本类所有表名字段名为了符合大部分数据库的SQL规范字段未加转义符号
 * 构造数据库时请注意所使用的数据库的保留字
 */
abstract class DBase
{
    /**
     * @var string
     */
    public string $table = '';
    /**
     * @var Database|MysqliDatabase|PdoMysqlDatabase|PdoSqlServDatabase
     */
    public mixed $config = null;

    /**
     * @var string[] $section
     */
    public array $section = [
        'handle' => 'select',
        'select' => '*',
        'insert' => '',
        'set' => '',
        'where' => '',
        'join' => '',
        'group' => '',
        'orderBy' => '',
        'limit' => '',
        'lock' => null,
    ];
    /**
     * @var string $sql
     */
    public string $sql = '';

    public ?string $lastSql = null;

    final public function lastSql(): string
    {
        return $this->lastSql;
    }

    /**
     * @return string|null
     */
    final public function version(): string|null
    {
        $version = $this->query('SELECT VERSION()');
        return $version ? $version[0]['VERSION()'] : NULL;
    }


    /**
     * 获取分页内容
     *
     * @param int $number
     * @param int $page
     * @return array|Data
     */
    final public function paginate($number = 10, $page = 1): Data|array
    {
        $page = $page > 0 ? $page : 1;
        $min = ((int)$page - 1) * $number;

        return $this->limit($min, (int)$number)->query();
    }

    /**
     * @param bool $share_mode
     * @return $this
     */
    final public function lock(bool $share_mode = false): self
    {
        $this->section['lock'] = $share_mode;
        return $this;
    }

    /**
     * 获取完整字段
     * @param $field
     * @return string|array
     */
    final public function _Field($field): string|array
    {
        if (is_string($field)) {
            if (str_contains($field, '.')) {
                $field = $this->config->tablePrefix . $field;
            }
        } else if (is_array($field)) {
            foreach ($field as &$item) {
                $item = $this->_Field($item);
            }
        }
        return $field;
    }


    /**
     * 获取最大值
     * @param $field
     * @return int
     */
    final public function max($field): mixed
    {
        $field = $this->_Field($field);
        $fetch = $this->getOne('MAX(' . $field . ')');
        if (!$fetch) {
            return 0;
        }
        return $fetch['MAX(' . $field . ')'];
    }

    /**
     * 获取最小值
     * @param $field
     * @return mixed
     */
    final public function min($field): mixed
    {
        $field = $this->_Field($field);
        $fetch = $this->getOne('MIN(' . $field . ')');
        if (!$fetch) {
            return 0;
        }
        return $fetch['MIN(' . $field . ')'];
    }

    /**
     * @param $field
     *
     * @return int    获取到的数量
     */
    public function Count($field = '*'): int
    {
        $field = $this->_Field($field);
        $fetch = $this->getOne('COUNT(' . $field . ')');
        if (!$fetch) {
            return 0;
        }
        $array = explode('.', $field);
        return $fetch['COUNT(' . $field . ')'];
    }

    /**
     * @param $field
     * @return int|float
     */
    final public function sum($field): int|float
    {
        $field = $this->_Field($field);
        $fetch = $this->getOne('SUM(' . $field . ')');
        if (!$fetch) {
            return 0;
        }
        return $fetch['SUM(' . $field . ')'];
    }

    /**
     * @param $field
     * @return int|float
     */
    final public function avg($field): int|float
    {
        $field = $this->_Field($field);
        $fetch = $this->getOne('AVG(' . $field . ')');
        if (!$fetch) {
            return 0;
        }
        $array = explode('.', $field);
        return $fetch[end($array)];
    }

    /**
     * @param $field
     * @return int
     */
    final public function length($field): int
    {
        $field = $this->_Field($field);
        $fetch = $this->getOne('LENGTH(' . $field . ')');
        if (!$fetch) {
            return 0;
        }
        $array = explode('.', $field);
        return $fetch[end($array)];
    }

    /**
     * 获取一条数据
     *
     * @param array|string $field
     *
     * @return null|Data
     */
    final public function getOne($field = '*'): null|Data
    {
        $this->select($field);
        $this->limit(1);
        $fetch = $this->query();
        if (count($fetch) < 1) {
            return null;
        }
        return $fetch[0];
    }

    /**
     * 写法兼容
     * @param string $field
     * @return Data|null
     */
    public function find($field = '*'): Data|null
    {
        return $this->getOne($field);
    }

    /**
     * 写法兼容
     * @param bool $sql
     * @return array|Data
     */
    public function findAll($sql = false): array|Data
    {
        return $this->query($sql);
    }

    /**
     * 设置字段值
     *
     * @param $field_name
     * @param $field_value
     *
     * @return boolean
     */
    final public function setField($field_name, $field_value): bool
    {
        $field_name = $this->_Field($field_name);
        return $this->update([
            $field_name => $field_value,
        ]);
    }

    /**
     * 获取一个字段值
     *
     * @param $field_name
     *
     * @return mixed
     */
    final public function getField($field_name): mixed
    {
        $field_name = $this->_Field($field_name);
        $this->select($field_name);
        $this->limit(0, 1);
        $fetch = $this->getOne();
        if (!$fetch) {
            return null;
        }
        $array = explode('.', $field_name);
        return $fetch[end($array)];
    }


    /**
     * @param string $field_name
     * @return self
     */
    final public function distinct(string $field_name): self
    {
        $this->select('DISTINCT ' . $this->_Field($field_name));
        return $this;
    }

    /**
     * 设置查询
     * 参数为一个时设置查询字段
     * 当为多个时可看成
     * SELECT($table,$where,$orderBy,$limit,$column);
     *
     * @param array|string $select
     *
     * @return DBase|Data
     */
    final public function select(array|string $select = '*'): self|Data
    {
        $this->section['handle'] = 'select';
        $arg_num = func_num_args();
        $arg_num = $arg_num > 5 ? 5 : $arg_num;
        if ($arg_num > 1) {
            $arg_list = func_get_args();
            for ($i = 0; $i < $arg_num; $i++) {
                switch ($i) {
                    case 0:
                        $this->setTable($arg_list[$i]);
                        break;
                    case 1:
                        $this->where($arg_list[$i]);
                        break;
                    case 2:
                        $this->orderBy($arg_list[$i]);
                        break;
                    case 3:
                        $this->limit($arg_list[$i]);
                        break;
                    case 4:
                        $this->select($arg_list[$i]);
                        break;
                }
            }

            return $this->query();        //多参数将自懂执行query，返回数组；
        }
        if (is_array($select)) {
            $allField = '';
            foreach ($select as $field) {
                if ($allField === '') {
                    $allField = $this->_Field($field);
                } else {
                    $allField .= ',' . $this->_Field($field);
                }
            }
            $this->_set($allField, 'select');
        } else {
            $this->_set($select, 'select');
        }

        return $this;
    }

    /**
     * 设置表名
     *
     * @param     $table
     * @param int $forget
     *
     * @return $this
     */
    final public function setTable($table, $forget = 1): self
    {
        if ($forget === 0) {
            $this->table = $this->config->tablePrefix . $table;
        }
        $this->_set($this->config->tablePrefix . $table, 'table');
        $this->compile();

        return $this;
    }

    /**
     * 设置section
     *
     * @param $data
     * @param $type
     */
    final public function _set($data, $type)
    {
        if (is_array($data)) {
            $this->section[$type] = implode(',', $data);
        } else {
            $this->section[$type] = $data;
        }
    }

    /**
     * @param $field
     * @param $Between
     * @return self
     * @throws Exception
     */
    final public function between($field, array $Between): self
    {
        $field = $this->_Field($field);
        if (count($Between) !== 2) {
            throw new SqlErrorException('Too few params to function Between($field, $Between), Must two params;');
        }
        $Between = $this->addslashes($Between);
        $pBetween = $Between[0] . ' AND ' . $Between[1];
        return $this->where("{$field} BETWEEN {$pBetween}");
    }

    /**
     * @param $field
     * @param $Between
     *
     * @return DBase
     * @throws SqlErrorException
     */
    final public function notBetween($field, $Between): self
    {
        $field = $this->_Field($field);
        if (count($Between) !== 2) {
            throw new SqlErrorException('Too few params to function notBetween($field, $Between), Must two params;');
        }
        $Between = $this->addslashes($Between);
        $pBetween = $Between[0] . ' AND ' . $Between[1];
        return $this->where("{$field} NOT BETWEEN {$pBetween}");
    }

    /**
     * @param $field
     * @param array $in
     *
     * @return DBase
     * @throws Exception
     */
    final public function in($field, array $in): self
    {
        $field = $this->_Field($field);
        if (is_array($in)) {
            $pin = '\'';
            $pin .= implode('\',\'', $in);
            $pin .= '\'';
        } else {
            $pin = $in;
        }

        return $this->where("{$field} IN ({$pin})");
    }

    /**
     * @param $field
     * @param array $in
     *
     * @return DBase
     * @throws Exception
     */
    final public function notIn($field, array $in): self
    {
        $field = $this->_Field($field);
        if (is_array($in)) {
            $pin = '\'';
            $pin .= implode('\',\'', $in);
            $pin .= '\'';
        } else {
            $pin = $in;
        }

        return $this->where("{$field} NOT IN ({$pin})");
    }

    /**
     * 搜索查询
     * @param $field
     * @param $value
     * @return DBase
     */
    final public function like($field, $value)
    {
        return $this->where($field, 'like', $value);
    }

    /**
     * @param $field
     * @param $value
     * @return DBase
     */
    final public function leftLike($field, $value)
    {
        return $this->where($field, 'like', '%' . $value);
    }

    /**
     * @param $field
     * @param $value
     * @return DBase
     */
    final public function rightLike($field, $value)
    {
        return $this->where($field, 'like', $value . '%');
    }

    /**
     * @param $field
     * @param $value
     * @return DBase
     */
    final public function bothLike($field, $value)
    {
        return $this->where($field, 'like', '%' . $value . '%');
    }

    /**
     * 设置条件
     * 当参数是两个，第一个为字段名，第二个为值
     * 当参数为三个,第一个为字段名,第二个为逻辑字符,第三个为值
     * 如果为数组，则是多个条件如array('条件一','条件二'.......);
     *
     * @param $where
     *
     * @return DBase
     * @throws Exception
     */
    final public function where($where)
    {
        $hasOr = $hasAnd = 0;
        $fieldOr = [];
        $fieldAnd = [];
        if (func_num_args() > 1) {
            $field = func_get_arg(0);
            if (is_string($field)) {

                $fieldAnd = explode('&', $field);
                $hasAnd = count($fieldAnd) > 1;
                $fieldOr = explode('|', $field);
                $hasOr = count($fieldOr) > 1;
                if ($hasAnd && $hasOr) {
                    throw new \RuntimeException('Where 字段目前不能同时包含&和|');
                }
            }
            if (func_num_args() === 2) {
                $value = func_get_arg(1);
                if ($hasOr) {
                    $wheres = [];
                    $value = $this->addslashes($value);
                    foreach ($fieldOr as $f) {
                        $f = $this->_Field($f);
                        if (is_array($value)) {
                            $value = implode(' or ' . $f . '=', $value);
                        }
                        $wheres[] = '' . $f . '=' . $value;
                    }
                    $where = implode(' or ', $wheres);
                    unset($wheres);
                } elseif ($hasAnd) {
                    $wheres = [];
                    $value = $this->addslashes($value);
                    foreach ($fieldAnd as $f) {
                        $f = $this->_Field($f);
                        if (is_array($value)) {
                            $value = implode(' or ' . $f . '=', $value);
                        }
                        $wheres[] = '' . $f . '=' . $value;
                    }
                    $where = implode(' and ', $wheres);
                    unset($wheres);
                } else {
                    $field = $this->_Field($field);
                    $value = func_get_arg(1);
                    if (is_array($field)) {
                        if (is_array($value)) {
                            throw new \RuntimeException('Where不允许field和value同时为Array类型。');
                        }
                        $where = '';
                        $value = $this->addslashes($value);
                        foreach ($field as $f) {
                            $where_str = $f . ' = ' . $value;
                            if ($where === '') {
                                $where = $where_str;
                            } else {
                                $where .= ' or ' . $where_str;
                            }
                        }
                    } else {
                        if (is_array($value)) {
                            $value = implode(' or ' . $field . '=', $this->addslashes($value));
                        } else {
                            $value = $this->addslashes($value);
                        }
                        $where = '' . $field . '=' . $value;
                    }
                }
            } elseif (func_num_args() === 3) {
                if ($hasOr) {
                    $wheres = [];
                    foreach ($fieldOr as $f) {
                        $f = $this->_Field($f);
                        $wheres[] = '' . $f . ' ' . func_get_arg(1) . ' ' . $this->addslashes(func_get_arg(2));
                    }
                    $where = implode(' or ', $wheres);
                    unset($wheres);
                } elseif ($hasAnd) {
                    $wheres = [];
                    foreach ($fieldAnd as $f) {
                        $f = $this->_Field($f);
                        $wheres[] = '' . $f . ' ' . func_get_arg(1) . ' ' . $this->addslashes(func_get_arg(2));
                    }
                    $where = implode(' and ', $wheres);
                    unset($wheres);
                } else {
                    $field = $this->_Field($field);
                    $value = func_get_arg(2);
                    if (is_array($field)) {
                        if (is_array($value)) {
                            throw new \RuntimeException('Where不允许field和value同时为Array类型。');
                        }
                        $where = '';
                        $value = $this->addslashes($value);
                        foreach ($field as $f) {
                            $where_str = $f . ' ' . func_get_arg(1) . ' ' . $value;
                            if ($where === '') {
                                $where = $where_str;
                            } else {
                                $where .= ' or ' . $where_str;
                            }
                        }
                    } else {
                        if (is_array($value)) {
                            $value = implode(' or ' . $field . ' ' . func_get_arg(1), $this->addslashes($value));
                        } else {
                            $value = $this->addslashes($value);
                        }
                        $where = '' . $field . ' ' . func_get_arg(1) . ' ' . $value;
                    }
                }
            }
        }
        if (is_array($where)) {
            $where = implode(' and ', $where);
        }
        if (isset($this->section['where']) && !empty($this->section['where'])) {
            $this->section['where'] .= ' and ' . '(' . $where . ')';
        } else {
            $this->section['where'] = '(' . $where . ')';
        }

        return $this;
    }

    /**
     * @param string|array $field
     * @param string|null $by
     * @return $this
     */
    final public function orderBy(string|array $field, string|null $by = null): static
    {
        $func_num = func_num_args();
        if ($func_num === 2) {
            $fields = func_get_arg(0);
            $order = func_get_arg(1);
            $orderByStr = '';
            if (is_array($fields)) {
                foreach ($fields as $item) {
                    if ($orderByStr) {
                        $orderByStr .= ',';
                    }
                    $orderByStr .= $this->_Field($item) . ' ' . $order;
                }
                $this->section['orderBy'] = $orderByStr;
            } else {
                $this->section['orderBy'] = $this->_Field($fields) . ' ' . $order;
            }
        } else if (is_array($field)) {
            $order = '';
            foreach ($field as $f) {
                if ($order === '') {
                    $order = $this->_Field($f);
                } else {
                    $order .= ',' . $this->_Field($f);
                }
            }
            $this->section['orderBy'] = $order;
        } else {
            $this->section['orderBy'] = $field;
        }

        return $this;
    }

    /**
     * @return $this
     */
    final public function limit()
    {
        $arg_num = func_num_args();
        $arg_list = func_get_args();
        if ($arg_num === 1) {
            $this->section['limit'] = $arg_list[0];
        }
        if ($arg_num === 2) {
            $this->section['limit'] = (int)$arg_list[0] . ',' . (int)$arg_list[1];
        }

        return $this;
    }

    /**
     * @param $where
     *
     * @return $this
     */
    final public function orWhere($where)
    {
        if (func_num_args() > 1) {
            $field = func_get_arg(0);
            $fieldAnd = explode('&', $field);
            $hasAnd = count($fieldAnd) > 1;
            $fieldOr = explode('|', $field);
            $hasOr = count($fieldOr) > 1;
            if ($hasAnd && $hasOr) {
                //TODO 待解决 同时处理OR和AND
                new Exception('Where 字段目前不能同时包含&和|');
            }
            if (func_num_args() === 2) {
                $value = func_get_arg(1);
                if ($hasOr) {
                    $wheres = [];
                    foreach ($fieldOr as $f) {
                        $f = $this->_Field($f);
                        if (is_array($value))
                            $value = implode(' or ' . $f . '=', $this->addslashes($value));
                        else
                            $value = $this->addslashes($value);
                        $wheres[] = '' . $f . '=' . $value;
                    }
                    $where = implode(' or ', $wheres);
                    unset($wheres);
                } elseif ($hasAnd) {
                    $wheres = [];
                    foreach ($fieldAnd as $f) {
                        $f = $this->_Field($f);
                        if (is_array($value)) {
                            $value = implode(' or ' . $f . '=', $this->addslashes($value));
                        } else {
                            $value = $this->addslashes($value);
                        }
                        $wheres[] = '' . $f . '=' . $value;
                    }
                    $where = implode(' and ', $wheres);
                    unset($wheres);
                } else {
                    $field = $this->_Field($field);
                    if (is_array($value)) {
                        $value = implode(' or ' . $field . '=', $this->addslashes($value));
                    } else
                        $value = $this->addslashes($value);
                    $where = '' . $field . '=' . $value;
                }
            } elseif (func_num_args() === 3) {
                $value = func_get_arg(2);
                if ($hasOr) {
                    $wheres = [];
                    foreach ($fieldOr as $f) {
                        $f = $this->_Field($f);
                        $wheres[] = '' . $f . ' ' . func_get_arg(1) . ' ' . $this->addslashes(func_get_arg(2));
                    }
                    $where = implode(' or ', $wheres);
                    unset($wheres);
                } elseif ($hasAnd) {
                    $wheres = [];
                    foreach ($fieldAnd as $f) {
                        $f = $this->_Field($f);
                        $wheres[] = '' . $f . ' ' . func_get_arg(1) . ' ' . $this->addslashes(func_get_arg(2));
                    }
                    $where = implode(' and ', $wheres);
                    unset($wheres);
                } else {
                    $field = $this->_Field($field);
                    if (is_array($value)) {
                        $value = implode(' or ' . $field . ' ' . func_get_arg(1), $this->addslashes($value));
                    } else {
                        $value = $this->addslashes($value);
                    }
                    $where = '' . $field . ' ' . func_get_arg(1) . ' ' . $value;
                }
            }
        }
        if (is_array($where)) {
            $where = implode(' or ', $where);
        }
        if (isset($this->section['where']) && !empty($this->section['where'])) {
            $this->section['where'] .= ' or ' . '(' . $where . ')';
        } else {
            $this->section['where'] = '(' . $where . ')';
        }

        return $this;
    }

    /**
     * @param $from
     *
     * @return $this
     */
    final public function from($from)
    {
        $this->setTable($from);

        return $this;
    }


    /**
     * @param string $column
     * @param int|float $num
     * @return bool
     */
    final public function setInc(string $column, int|float $num = 1): bool
    {

        $this->section['handle'] = 'update';
        $this->_set($column . '=' . $column . '+' . $num, 'update');

        return $this->exec();
    }

    /**
     * @param string $column
     * @param int|float $num
     * @return bool
     */
    final public function setDnc(string $column, int|float $num = 1): bool
    {
        $this->section['handle'] = 'update';
        $this->_set($column . '=' . $column . '-' . $num, 'update');

        return $this->exec();
    }

    /**
     * 一个参数是设置修改内容
     * 多个参考下面参数使用
     * UPDATE($table, $set, $where, $limit)
     * @param $update
     * @return bool
     */
    final public function update($update): bool
    {
        $this->section['handle'] = 'update';
        $arg_num = func_num_args();
        $arg_num = $arg_num > 4 ? 4 : $arg_num;
        if ($arg_num > 1) {
            $arg_list = func_get_args();
            for ($i = 0; $i < $arg_num; $i++) {
                switch ($i) {
                    case 0:
                        $this->setTable($arg_list[$i]);
                        break;
                    case 1:
                        $this->_set($arg_list[$i], 'update');
                        break;
                    case 2:
                        $this->where($arg_list[$i]);
                        break;
                    case 3:
                        $this->limit($arg_list[$i]);
                        break;
                }
            }

            return $this->exec();
        }

        $this->section['handle'] = 'update';
        if (is_string($update)) {
            $this->_set($update, 'update');

            return $this->exec();
        }
        $keys = array_keys($update);
        if (in_array('0', $keys, true)) {
            $this->_set($update, 'update');
        } else {
            $values = array_values($update);

            $size = count($keys);
            $ud = NULL;
            for ($i = 0; $i < $size; $i++) {
                if ($i !== 0) {
                    $ud .= ',';
                }
                $ud .= $keys[$i] . ' = ' . (is_array($values[$i]) ? $this->addslashes(json_encode($values[$i], JSON_UNESCAPED_UNICODE)) : (is_object($values[$i]) ? $this->addslashes(serialize($values[$i])) : $this->addslashes($values[$i]))) . '';
            }
            $this->_set($ud, 'update');
        }

        return $this->exec();
    }


    /**
     * @param bool $table
     * @return string
     */
    final public function get_table($table = FALSE): string
    {
        if (!$table) {
            return (isset($this->section['table']) && !empty($this->section['table'])) ? $this->section['table'] :
                $this->config->tablePrefix . $this->table;
        }

        return $this->config->tablePrefix . $table;
    }

    /**
     * @param string|null $sql
     *
     * @return bool
     */
    final public function exec($sql = null): bool
    {
        if (!$sql) {
            $this->compile();
            $sql = $this->sql;
        }
        $this->parseTablePre($sql);
        $this->lastSql = $sql;
        $this->_reset();
        Logger::sql($sql);
        if ($this->_exec($sql) !== FALSE) {
            return true;
        }

        new Exception($this->getError());
        return false;
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
                $sql = "SELECT {$this->section['select']} from {$this->section['table']}";
            } elseif ($this->section['handle'] === 'update') {
                $sql = "UPDATE {$this->section['table']} set {$this->section['update']}";
            } elseif ($this->section['handle'] === 'delete') {
                $sql = "DELETE FROM {$this->section['table']}";
            }
            if (!empty($sql)) {
                $sql .= ($this->section['join'] ? " " . $this->section['join'] : '') . ($this->section['where'] ? " WHERE {$this->section['where']}" : '') . ($this->section['group'] ? " GROUP BY {$this->section['group']}" : '') . ($this->section['orderBy'] ? " ORDER BY {$this->section['orderBy']}" : '') . ($this->section['limit'] ? " LIMIT  {$this->section['limit']}" : '');
                if (($this->section['handle'] === 'select') && $this->section['lock'] !== null) {
                    if ($this->section['lock'] === true) {
                        $sql .= ' LOCK IN SHARE MODE';
                    } else {
                        $sql .= ' FOR UPDATE';
                    }
                }
                return $this->sql .= $sql;
            }

            return false;
        }
        return false;
    }

    /**
     * 重置查询
     */
    final public function _reset(): void
    {
        $this->section = [
            'handle' => 'select',
            'select' => '*',
            'insert' => '',
            'table' => $this->get_table($this->table),
            'set' => '',
            'where' => '',
            'join' => '',
            'group' => '',
            'orderBy' => '',
            'limit' => '',
            'lock' => null,
        ];

        $this->sql = '';
    }

    /**
     * 一个参数时只设置插入内容
     * 多个参数参考下面函数
     * INSERT($table,$value)
     *
     * @param $insert
     *
     * @return bool|int
     */
    final public function insert($insert): bool|int
    {
        $this->section['handle'] = 'insert';
        $arg_num = func_num_args();

        if ($arg_num > 1) {
            $arg_list = func_get_args();
            $this->setTable($arg_list[0])->insert($arg_list[1]);

            return $this->exec();
        }
        //--强制开发者使用默认值,添加不可以设置空值,杜绝因为运营人员表单没输入而没有使用数据库默认值
        foreach ($insert as $key => $value) {
            if ($value === '') {
                unset($insert[$key]);
            }
        }
        foreach ($insert as $key => $value) {
            $insert[$key] = is_array($value) ? $this->addslashes(json_encode($value, JSON_UNESCAPED_UNICODE)) : (is_object($value) ? $this->addslashes(serialize($value)) : $this->addslashes($value));
        }
        $this->section['insert'] = is_array($insert) ? '(' . implode(',', array_keys($insert)) . ') VALUES (' . implode(',', array_values($insert)) . ')' : "VALUES('{$insert}')";

        return $this->exec();
    }

    /**
     * @param $table
     * @param $on1
     * @param $on2
     *
     * @return DBase
     */
    final public function leftJoin($table, $on1, $on2): DBase
    {
        return $this->join($table, $on1, $on2, 'LEFT');
    }

    /**
     * @param $table
     * @param $on1
     * @param $on2
     * @param $ori
     *
     * @return $this
     */
    final public function join($table, $on1, $on2, $ori): DBase
    {
        if ($this->section['join'] === '') {
            $this->section['join'] = $ori . ' JOIN ' . $this->config->tablePrefix . $table . " ON " .
                $this->config->tablePrefix . $on1 . '=' . $this->config->tablePrefix . $on2;
        } else {
            $this->section['join'] .= ' ' . $ori . ' JOIN ' . $this->config->tablePrefix . $table . " ON " .
                $this->config->tablePrefix . $on1 . '=' . $this->config->tablePrefix . $on2;
        }

        return $this;
    }

    /**
     * @param $table
     * @param $on1
     * @param $on2
     *
     * @return DBase
     */
    final public function rightJoin($table, $on1, $on2): DBase
    {
        return $this->join($table, $on1, $on2, 'RIGHT');
    }

    /**
     * @param $table
     * @param $on1
     * @param $on2
     *
     * @return DBase
     */
    final public function fullJoin($table, $on1, $on2): DBase
    {
        return $this->join($table, $on1, $on2, 'FULL');
    }

    /**
     * @param $table
     * @param $on1
     * @param $on2
     *
     * @return DBase
     */
    final public function innerJoin($table, $on1, $on2): DBase
    {
        return $this->join($table, $on1, $on2, 'INNER');
    }

    /**
     * @param bool $all
     *
     * @return DBase
     */
    final public function union($all = FALSE): DBase
    {
        $handle = $this->section['handle'];
        $sql = $this->compile();
        $this->_reset();
        $this->sql = $sql;
        $this->section['handle'] = $handle;
        if ($all) {
            $this->sql .= ' UNION ALL ';
        } else {
            $this->sql .= ' UNION ';
        }

        return $this;
    }

    /**
     * @param $field
     * @return DBase
     */
    final public function notNull($field): DBase
    {
        $field = $this->_Field($field);
        return $this->where($field . ' IS NOT NULL');
    }

    /**
     * @param $field
     * @return DBase
     */
    final public function isNull($field): DBase
    {
        $field = $this->_Field($field);
        return $this->where($field . ' IS NULL');
    }

    /**
     * 删除记录
     * 一个参数设置表名
     * 多个参数参考如下
     * DELETE($table, $where, $orderBy, $limit)
     *
     * @param bool $delete
     *
     * @return bool|int
     */
    final public function delete($delete = FALSE): bool|int
    {
        $this->section['handle'] = 'delete';
        $arg_num = func_num_args();
        $arg_list = func_get_args();
        $arg_num = $arg_num > 4 ? 4 : $arg_num;
        if ($arg_num > 1) {
            for ($i = 0; $i < $arg_num; $i++) {
                switch ($i) {
                    case 0:
                        $this->setTable($arg_list[0]);
                        break;
                    case 1:
                        $this->where($arg_list[1]);
                        break;
                    case 2:
                        $this->orderBy($arg_list[2]);
                        break;
                    case 3:
                        $this->limit($arg_list[3]);
                        break;

                }
            }

            return $this->exec();
        }
        if ($delete) {
            $this->setTable($delete);
        }

        return $this->exec();
    }

    /**
     * @param $group
     *
     * @return DBase
     */
    final public function group($group): DBase
    {
        $this->section['group'] = $this->_Field($group);
        return $this;
    }

    /**
     * 替换表前缀
     *
     * @param $sql
     *
     * @return string
     */
    private function parseTablePre(&$sql): string
    {
        return $sql = str_replace('_PREFIX_', $this->config->tablePrefix, $sql);
    }

    /**
     * 保存数据或者更新数据
     * 如果设置主键字段 $primary_key 将会判断此字段是否存在，如果存在则会为更新数据
     *
     * @param ArrayAccess|array $data
     * @param String $primary_key
     *
     * @return  Bool|int
     * @throws Exception
     */
    final public function save(ArrayAccess|array $data, $primary_key = ''): bool|int
    {
        if (($primary_key !== '') && isset($data[$primary_key]) && $data[$primary_key]) {
            $primary_value = $data[$primary_key];
            unset($data[$primary_key]);

            return $this->where($primary_key, $primary_value)->update($data);
        }

        return $this->insert($data);
    }

    /**
     * 查询SQL
     *
     * @param string|null $sql
     *
     * @return array | Data
     */
    final public function &query(string|null $sql = null): Data|array
    {
        if (!$sql) {
            $this->compile();
            $sql = $this->sql;
        }
        $this->_reset();
        $this->lastSql = $sql;
        $this->parseTablePre($sql);
        Logger::sql($sql);
        $data = $this->_query($sql);
        if ($data === FALSE) {
            new Exception($this->getError());
        }
        $data = $this->stripslashes($data);
        if ($data === NULL) {
            $data = [];
        }
        $result = new Data($data);
        return $result;
    }

    /**
     * 闭包执行事务，返回事务执行的状态
     * @param Closure $callback
     * @return bool
     */
    final public function transaction(Closure $callback): bool
    {
        try {
            $this->beginTransaction();
            if ($callback($this) !== false) {
                return $this->commit();
            }
            $this->rollBack();
            return false;
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $file = $e->getFile();
            $line = $e->getLine();
            $errorType = get_class($e);
            Logger::error("\x1b[" . CliColor::ERROR . ";1m " . $errorType . "：" . $msg . "\e[0m\n\t\t" . " in " . $file . ' on line ' .
                $line);
            $this->rollBack();
            return false;
        }
    }

    /**
     * 转义函数
     * 参数可以为多参数或数组，返回数组
     *
     * @param array|string|null $data
     *
     * @return string|array|null
     */
    public function addslashes(array|string|null $data): string|array|null
    {
        if (is_array($data)) {
            foreach ($data as $k => &$v) {
                $v = $this->addslashes($v);
            }
        } else {
            $data = $this->real_escape_string($data);
        }
        return $data;
    }

    /**
     * @param string|array $var
     *
     * @return string|array
     */
    public function stripslashes(array|string $var): array|string
    {
        if (!is_string($var)) {
            foreach ($var as $k => &$v) {
                $this->stripslashes($v);
            }
        } else {
            $var = stripslashes($var);
        }

        return $var;
    }

    /**
     * 链接数据库
     * @param string $configName
     * @return bool
     */
    public function connect(string $configName): bool
    {
        try {
            $this->config = Config::database($configName);
            return $this->_connect($this->config);
        } catch (\ErrorException $e) {
            return false;
        }

    }

    /**
     * 数据库驱动必须创建下列方法
     * 并且必须返回正确的值
     *
     * @param $sql
     *
     * @return array|Data
     */
    abstract public function _query($sql): array|Data;         //返回值是查询出的数组

    abstract public function getError(): string;            //返回上一个错误信息

    abstract public function real_escape_string($string): string; //特殊字符转义

    /**
     * 获取上次插入数据
     * @return int|null
     */
    abstract public function lastInsertId(): int|null;

    /**
     * @param $sql
     *
     * @return int|bool
     */
    abstract public function _exec($sql): int|bool;           //执行SQL

    abstract public function _connect(Database $database);            //返回处理后的语柄

    abstract public function beginTransaction(): bool;   //开启事务

    abstract public function commit(): bool;             //关闭事务

    abstract public function rollBack(): bool;           //回滚事务
}

//====================    END DB.class.php      ========================//
