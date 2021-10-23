<?php


namespace QApi\ORM;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Types\Type;
use ErrorException;
use QApi\Config;
use QApi\Config\Database\MysqliDatabase;
use QApi\Config\Database\PdoMysqlDatabase;
use QApi\Config\Database\PdoSqliteDatabase;
use QApi\Config\Database\SqlServDatabase;
use QApi\Data;
use QApi\Database\DBase;
use QApi\Exception\SqlErrorException;
use QApi\Logger;

/**
 * Class DB
 * @package QApi\ORM
 * @method $this setFirstResult(int $offset),
 * @method $this setMaxResults(int $maxNumber),
 * @method ExpressionBuilder expr(),
 * @method int getType(),
 * @method Connection getConnection(),
 * @method int getState(),
 * @method $this select($select = null)
 * @method $this setParameter(int|string $key, mixed $value, int|string|Type|null $type = null)
 * @method $this setParameters(array $params, array $type = [])
 * @method array getParameters(array $params, array $type = [])
 * @method array set($key, $value)
 * @method mixed getParameter(int|string $key)
 * @method array getParameterTypes()
 * @method int|string|Type|null getParameterType(int|string $key)
 * @method int getFirstResult()
 * @method int getMaxResults()
 * @method $this distinct()
 * @method $this join(string $fromAlias, string $join, string $alias, string $condition = null)
 * @method $this innerJoin(string $fromAlias, string $join, string $alias, string $condition = null)
 * @method $this leftJoin(string $fromAlias, string $join, string $alias, string $condition = null)
 * @method $this rightJoin(string $fromAlias, string $join, string $alias, string $condition = null)
 * @method $this groupBy(string|array $groupBy)
 * @method $this addGroupBy(string|array $groupBy)
 * @method $this having($having)
 * @method $this andHaving($having)
 * @method mixed getQueryPart(string $queryPartName)
 * @method $this orHaving($having)
 * @method $this orderBy($sort, $order = null)
 * @method $this addOrderBy($sort, $order = null)
 * @method string getSQL()
 */
class DB
{
    private string $table;
    protected QueryBuilder $queryBuilder;
    public MysqliDatabase|PdoMysqlDatabase|PdoSqliteDatabase|Config\Database\PdoSqlServDatabase|SqlServDatabase
        $config;
    private bool $hasWhere = false;
    protected Connection $connection;

    /**
     * DB constructor.
     * @param string $table
     * @param string $configName
     */
    public function __construct(string $table, string $configName)
    {
        $this->config = Config::database($configName);
        $this->connection = DriverManager::connect($configName);
        $this->queryBuilder = $this->connection->createQueryBuilder();
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
    public function update(array|Data $data, array $types = [], ?string $table = null): int
    {
        if ($data instanceof Data) {
            $data = $data->toArray();
        }
        if (!$table) {
            $table = $this->table;
        }
        $update = $this->queryBuilder->update($this->config->tablePrefix . $table);
        foreach ($data as $key => $value) {
            if (isset($types[$key])) {
                $update->set($key, $value);
            } else {
                $update->set($key, $this->quote($value));
            }
        }
        $this->hasWhere = false;
        return $update->executeStatement();
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
     * @param mixed $change
     * @return int
     */
    final public function setInc(string $field, mixed $change = 1): int
    {
        $field = $this->_Field($field);
        return $this->update([
            $field => $field . ' + ' . $change,
        ], [$field => true]);
    }

    /**
     * @param string $field
     * @param int|float $change
     * @return bool
     */
    final public function setDec(string $field, mixed $change = 1): bool
    {
        $field = $this->_Field($field);
        return $this->update([
            $field => $field . ' - ' . $change,
        ], [$field => true]);
    }

    /**
     * @param $field
     * @param $Between
     * @return $this
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
     * @return $this
     */
    final public function notBetween($field, array $Between): self
    {
        $field = $this->_Field($field);
        if (count($Between) !== 2) {
            throw new SqlErrorException('Too few params to function Between($field, $Between), Must two params;');
        }
        $Between = $this->addslashes($Between);
        $pBetween = $Between[0] . ' AND ' . $Between[1];
        return $this->where("{$field} NOT BETWEEN {$pBetween}");
    }

    /**
     * @param string $field
     * @param $value
     * @return $this
     */
    public function leftLike(string $field, $value): self
    {
        $this->queryBuilder->where($this->expr()->like($field, '%' . $this->quote($value)));
        return $this;
    }

    /**
     * @param string $field
     * @param $value
     * @return $this
     */
    public function rightLike(string $field, $value): self
    {
        $this->queryBuilder->where($this->expr()->like($field, $this->quote($value) . '%'));
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
     */
    public function count(string $field = '*'): int
    {
        $this->hasWhere = false;
        return (int)$this->queryBuilder->select('COUNT(' . $field . ')')->executeQuery()->fetchOne();
    }

    /**
     * @param string $field
     * @return int
     */
    public function max(string $field): int
    {
        $this->hasWhere = false;
        return (int)$this->queryBuilder->select('MAX(' . $field . ')')->executeQuery()->fetchOne();
    }

    /**
     * @param string $field
     * @return int
     */
    public function min(string $field): int
    {
        $this->hasWhere = false;
        return (int)$this->queryBuilder->select('MIN(' . $field . ')')->executeQuery()->fetchOne();
    }

    /**
     * @param string $field
     * @return int
     */
    public function sum(string $field): int
    {
        $this->hasWhere = false;
        return (int)$this->queryBuilder->select('SUM(' . $field . ')')->executeQuery()->fetchOne();
    }

    /**
     * @param string $field
     * @return int
     */
    public function avg(string $field): int
    {
        $this->hasWhere = false;
        return (int)$this->queryBuilder->select('AVG(' . $field . ')')->executeQuery()->fetchOne();
    }

    /**
     * @param string $field
     * @return int
     */
    public function length(string $field): int
    {
        $this->hasWhere = false;
        return (int)$this->queryBuilder->select('LENGTH(' . $field . ')')->executeQuery()->fetchOne();
    }

    /**
     * @return Data|null
     */
    public function find(): ?Data
    {
        $this->hasWhere = false;
        $data = $this->queryBuilder->setMaxResults(1)->executeQuery()->fetchAllAssociative();
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
     * @param $field_name
     * @return mixed
     */
    final public function getField($field_name): mixed
    {
        $this->hasWhere = false;
        $field_name = $this->_Field($field_name);
        return $this->queryBuilder->select($field_name)->setMaxResults(1)->executeQuery()->fetchOne();
    }

    /**
     * 设置字段值
     *
     * @param $field_name
     * @param $field_value
     * @param bool $format
     * @return int
     */
    final public function setField($field_name, $field_value, $format = true): int
    {
        $field_name = $this->_Field($field_name);
        return $this->update([
            $field_name => $field_value,
        ], !$format ? [$field_name => true] : []);
    }

    /**
     * @param string|null $delete
     * @param string|null $alias
     * @return int
     */
    final public function delete(?string $delete = null, ?string $alias = null): int
    {
        return $this->queryBuilder->delete($delete, $alias)->executeStatement();
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