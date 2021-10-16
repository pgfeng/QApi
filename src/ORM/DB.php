<?php


namespace QApi\ORM;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use ErrorException;
use QApi\Config;
use QApi\Config\Database\MysqliDatabase;
use QApi\Config\Database\PdoMysqlDatabase;
use QApi\Config\Database\PdoSqliteDatabase;
use QApi\Config\Database\SqlServDatabase;
use QApi\Data;

/**
 * Class DB
 * @package QApi\ORM
 * @method self setFirstResult(int $offset),
 * @method self setMaxResults(int $maxNumber),
 * @method ExpressionBuilder expr(),
 * @method int getType(),
 * @method Connection getConnection(),
 * @method int getState(),
 * @method self select($select = null)
 * @method self setParameter(int|string $key, mixed $value, int|string|Type|null $type = null)
 * @method self setParameters(array $params, array $type = [])
 * @method array getParameters(array $params, array $type = [])
 * @method array set($key, $value)
 * @method mixed getParameter(int|string $key)
 * @method array getParameterTypes()
 * @method int|string|Type|null getParameterType(int|string $key)
 * @method int getFirstResult()
 * @method int getMaxResults()
 * @method self distinct()
 * @method self join(string $fromAlias, string $join, string $alias, string $condition = null)
 * @method self innerJoin(string $fromAlias, string $join, string $alias, string $condition = null)
 * @method self leftJoin(string $fromAlias, string $join, string $alias, string $condition = null)
 * @method self rightJoin(string $fromAlias, string $join, string $alias, string $condition = null)
 * @method self groupBy(string|array $groupBy)
 * @method self addGroupBy(string|array $groupBy)
 * @method self having($having)
 * @method self andHaving($having)
 * @method mixed getQueryPart(string $queryPartName)
 * @method self orHaving($having)
 * @method self orderBy($sort, $order = null)
 * @method self addOrderBy($sort, $order = null)
 * @method string getSQL()
 */
class DB
{
    private string $table;
    protected QueryBuilder $queryBuilder;
    public MysqliDatabase|PdoMysqlDatabase|PdoSqliteDatabase|Config\Database\PdoSqlServDatabase|SqlServDatabase
        $config;
    private bool $hasWhere = false;

    /**
     * DB constructor.
     * @param string $table
     * @param string $configName
     */
    public function __construct(string $table, string $configName)
    {
        $this->config = Config::database($configName);
        $this->queryBuilder = DriverManager::connect($configName);
        $this->select('*');
        if ($table) {
            $this->table = $table;
            $this->from($table);
        }
    }


    /**
     * @param string $table
     * @param string|null $alias
     * @return DB
     */
    public function from(string $table, string $alias = null): self
    {
        $this->queryBuilder->from($this->config->tablePrefix . $table, $alias);
        return $this;
    }


    /**
     * @param ExpressionBuilder|string|array $predicates
     * @param mixed|null $op
     * @param mixed|null $value
     * @return $this
     */
    public function where(ExpressionBuilder|string|array $predicates, mixed $op = null, mixed $value = null): self
    {
        $arg_number = func_num_args();
        if ($arg_number === 1) {
            if ($this->hasWhere) {
                $this->queryBuilder->andWhere($predicates);
            } else {
                $this->queryBuilder->where($predicates);
            }
        } else if ($arg_number === 2) {
            $values = func_get_arg(1);
            if ($this->hasWhere) {
                if (is_array($values)) {
                    $this->queryBuilder
                        ->andWhere(
                            $this->expr()->in($predicates, $this->quote($values))
                        );
                } else {
                    $this->queryBuilder
                        ->andWhere(
                            $this->expr()->eq($predicates, $this->quote($values))
                        );
                }
            } else if (is_array($values)) {
                $this->queryBuilder
                    ->where(
                        $this->expr()->in($predicates, $this->quote($values))
                    );
            } else {
                $this->queryBuilder
                    ->where(
                        $this->expr()->eq($predicates, $this->quote($values))
                    );
            }
        } elseif ($arg_number === 3) {

            if ($this->hasWhere) {
                $this->queryBuilder
                    ->andWhere(
                        $this->expr()->comparison($predicates, $op, $this->quote($value))
                    );
            } else {
                $this->queryBuilder
                    ->where(
                        $this->expr()->comparison($predicates, $op, $this->quote($value))
                    );
            }
        }
        $this->hasWhere = true;
        return $this;
    }

    /**
     * @param ExpressionBuilder|string|array $predicates
     * @param mixed|null $op
     * @param mixed|null $value
     * @return $this
     */
    public function orWhere(ExpressionBuilder|string|array $predicates, mixed $op = null, mixed $value = null): self
    {
        $arg_number = func_num_args();
        if ($arg_number === 1) {
            $this->queryBuilder->orWhere($predicates);
        } else if ($arg_number === 2) {
            $values = func_get_arg(1);
            if (is_array($values)) {
                $this->queryBuilder
                    ->orWhere(
                        $this->expr()->in($predicates, $this->quote($values))
                    );
            } else {
                $this->queryBuilder
                    ->orWhere(
                        $this->expr()->eq($predicates, $this->quote($values))
                    );
            }
        } elseif ($arg_number === 3) {
            $this->queryBuilder
                ->orWhere(
                    $this->expr()->comparison($predicates, $op, $this->quote($value))
                );
        }
        return $this;
    }


    /**
     * @param ExpressionBuilder|string|array $predicates
     * @param mixed|null $op
     * @param mixed|null $value
     * @return $this
     */
    public function andWhere(ExpressionBuilder|string|array $predicates, mixed $op = null, mixed $value = null):
    self
    {
        $arg_number = func_num_args();
        if ($arg_number === 1) {
            $this->queryBuilder->orWhere($predicates);
        } else if ($arg_number === 2) {
            $values = func_get_arg(1);
            if (is_array($values)) {
                $this->queryBuilder->andWhere($this->expr()->in($predicates, $this->quote
                ($values)));
            } else {
                $this->queryBuilder->andWhere($this->expr()->eq($predicates, $this->quote
                ($values)));
            }
        } elseif ($arg_number === 3) {
            $this->queryBuilder
                ->andWhere(
                    $this->expr()->comparison($predicates, $op, $this->quote($value))
                );
        }
        return $this;
    }

    /**
     * @param array|Data $data
     * @param array $types
     * @param string|null $table
     * @return int
     */
    public function insert(array|Data $data, array $types = [], ?string $table = null): int
    {
        if ($data instanceof Data) {
            $data = $data->toArray();
        }
        if (!$table) {
            $table = $this->table;
        }
        return $this->queryBuilder->getConnection()->insert($this->config->tablePrefix . $table, $data, $types);
    }

    /**
     * @param array|Data $data
     * @param array $where
     * @param array $types
     * @param string|null $table
     * @return int
     */
    public function update(array|Data $data, array $where, array $types = [], ?string $table = null): int
    {
        if ($data instanceof Data) {
            $data = $data->toArray();
        }
        if (!$table) {
            $table = $this->table;
        }
        return $this->queryBuilder->getConnection()->update($this->config->tablePrefix . $table, $data, $where,
            $types);
    }

    /**
     * @param $value
     * @param int $type
     * @return mixed
     */
    public function quote($value, $type = ParameterType::STRING): mixed
    {
        if (is_array($value)) {
            foreach ($value as &$v) {
                $v = $this->quote($v, $type);
            }
            return $value;
        }

        return $this->getConnection()->quote($value, $type);
    }

    /**
     * @param string $field
     * @param $value
     * @return $this
     */
    public function like(string $field, $value): self
    {
        $this->queryBuilder->where($this->expr()->like($field, $value));
        return $this;
    }

    /**
     * @param string $field
     * @param $value
     * @return $this
     */
    public function bothLike(string $field, $value): self
    {
        $this->queryBuilder->where($this->expr()->like($field, '%' . $this->quote($value) . '%'));
        return $this;
    }

    /**
     * @param string $field
     * @param $value
     * @return $this
     */
    public function notLike(string $field, $value): self
    {
        $this->queryBuilder->where($this->expr()->notLike($field, $value));
        return $this;
    }

    /**
     * @param int $offset
     * @param int $maxNumber
     * @return $this
     */
    public function limit(int $offset, int $maxNumber): self
    {
        $this->setFirstResult($offset);
        $this->setMaxResults($maxNumber);
        return $this;
    }

    /**
     * @param string|null $sql
     * @param array $params
     * @param array $types
     * @param QueryCacheProfile|null $qcp
     * @return array[]
     */
    public function query(string $sql = null, array $params = [], array $types = [], ?QueryCacheProfile $qcp =
    null): array
    {
        if ($sql) {
            $data = $this->queryBuilder->getConnection()->executeQuery($sql, $params, $types, $qcp)
                ->fetchAllAssociative();
        } else {
            $data = $this->queryBuilder->executeQuery()->fetchAllAssociative();
        }
        $this->hasWhere = false;
        return $data;
    }

    /**
     * @param string $field
     * @return int
     * @throws Exception
     */
    public function count(string $field = '*'): int
    {
        $this->hasWhere = false;
        return (int)$this->queryBuilder->select('COUNT(' . $field . ')')->executeQuery()->fetchOne();
    }

    /**
     * @return Data|null
     */
    public function find(): ?Data
    {
        $data = $this->queryBuilder->setMaxResults(1)->executeQuery()->fetchAllAssociative();
        $this->hasWhere = false;
        if (count($data)) {
            return new Data($data[0]);
        }
        return null;
    }

    /**
     * @param mixed $val
     * @param string $field
     * @return Data|null
     */
    public function findByKey(mixed $val, string $field): ?Data
    {
        return $this->where($field, $val)->find();
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
        $fetch = $this->find();
        if (!$fetch) {
            return null;
        }
        $array = explode('.', $field_name);
        return $fetch[end($array)];
    }

    /**
     * 设置字段值
     *
     * @param $field_name
     * @param $field_value
     *
     * @return int
     */
    final public function setField($field_name, $field_value): int
    {
        $field_name = $this->_Field($field_name);
        return $this->update([
            $field_name => $field_value,
        ], $this->queryBuilder->getQueryPart('where'));
    }

    /**
     * 不存在的方法将执行DB类中的方法
     *
     * @param $func
     * @param $val
     *
     * @return mixed
     */
    final public function __call($func, $val): mixed
    {
        /** @var array $val */
        if (method_exists($this->queryBuilder, $func)) {
            $res = call_user_func_array([$this->queryBuilder, $func], $val);
            if (is_object($res)) {
                if ($res instanceof QueryBuilder) {
                    $this->queryBuilder = $res;
                } else {
                    return $res;
                }

                return $this;
            }

            return $res;
        }

        $message = get_class($this) . '->' . $func . '(' . implode(', ', $val) . ') is Undefined!';
        throw  new \RuntimeException($message);
    }

    /**
     * @param int $number
     * @param int $page
     * @return Data|array
     */
    final public function paginate($number = 10, $page = 1): Data|array
    {
        $page = $page > 0 ? $page : 1;
        $min = ((int)$page - 1) * $number;
        return $this->limit($min, (int)$number)->query();
    }


    public function __clone(): void
    {
        $this->queryBuilder = clone $this->queryBuilder;
    }
}