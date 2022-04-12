<?php


namespace QApi\ORM;

use Closure;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ServerException;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Types\Type;
use QApi\Config;
use QApi\Config\Database\MysqliDatabase;
use QApi\Config\Database\PdoMysqlDatabase;
use QApi\Config\Database\PdoSqliteDatabase;
use QApi\Config\Database\SqlServDatabase;
use QApi\Data;
use QApi\Enumeration\CliColor;
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
 * @method $this select(string ...$selects = null)
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
 * @method $this groupBy(string|array $groupBy)
 * @method $this addGroupBy(string|array $groupBy)
 * @method int executeStatement($sql, array $params = [], array $types = [])
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
     * @var AbstractSchemaManager|null
     */
    private ?AbstractSchemaManager $schemaManager = null;
    /**
     * @var string|null
     */
    protected ?string $aliasName = null;
    /**
     * @var int
     */
    protected ?int $lockMode = null;

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
     * @param int $lockMode One of the Doctrine\DBAL\LockMode::* constants
     */
    public function lock(int $lockMode = LockMode::NONE): self
    {
        $this->lockMode = $lockMode;
        $this->from($this->connection->getDatabasePlatform()->appendLockHint($this->table, $lockMode), $this->aliasName);
        return $this;
    }

    public function getSchemaManager(): AbstractSchemaManager
    {
        if ($this->schemaManager) {
            return $this->schemaManager;
        } else {
            return $this->schemaManager = $this->connection->createSchemaManager();
        }
    }

    public function getTableName(): string
    {
        return $this->table ? $this->config->tablePrefix . $this->table : '';
    }

    /**
     * @param string $formAlias
     * @return $this
     */
    public function alias(string $formAlias): self
    {
        $this->from($this->table, $formAlias);
        return $this;
    }

    /**
     * @param string $join
     * @param string $condition
     * @param string|null $joinAlias
     * @param string|null $fromAlias
     * @return $this
     */
    public function join(string $join, string $condition, ?string $joinAlias = null, ?string $fromAlias = null): self
    {
        $join = $this->config->tablePrefix . $join;
        if ($fromAlias) {
            $this->alias($fromAlias);
        } else {
            if ($this->aliasName) {
                $fromAlias = $this->aliasName;
            } else {
                $fromAlias = $this->getTableName();
            }
        }
        if (!$joinAlias) {
            $joinAlias = $join;
        }
        $this->queryBuilder->join($fromAlias, $join, $joinAlias, $condition);
        return $this;
    }


    /**
     * @param string $join
     * @param string $condition
     * @param string|null $joinAlias
     * @param string|null $fromAlias
     * @return $this
     */
    public function innerJoin(string $join, string $condition, ?string $joinAlias = null, ?string $fromAlias = null): self
    {
        $join = $this->config->tablePrefix . $join;
        if ($fromAlias) {
            $this->alias($fromAlias);
        } else {
            if ($this->aliasName) {
                $fromAlias = $this->aliasName;
            } else {
                $fromAlias = $this->getTableName();
            }
        }
        if (!$joinAlias) {
            $joinAlias = $join;
        }
        $this->queryBuilder->join($fromAlias, $join, $joinAlias, $condition);
        return $this;
    }

    /**
     * @param string $join
     * @param string $condition
     * @param string|null $joinAlias
     * @param string|null $fromAlias
     * @return $this
     */
    public function leftJoin(string $join, string $condition, ?string $joinAlias = null, ?string $fromAlias = null): self
    {
        $join = $this->config->tablePrefix . $join;
        if ($fromAlias) {
            $this->alias($fromAlias);
        } else {
            if ($this->aliasName) {
                $fromAlias = $this->aliasName;
            } else {
                $fromAlias = $this->getTableName();
            }
        }
        if (!$joinAlias) {
            $joinAlias = $join;
        }
        $this->queryBuilder->leftJoin($fromAlias, $join, $joinAlias, $condition);
        return $this;
    }

    /**
     * @param string $join
     * @param string $condition
     * @param string|null $joinAlias
     * @param string|null $fromAlias
     * @return $this
     */
    public function rightJoin(string $join, string $condition, ?string $joinAlias = null, ?string $fromAlias = null):
    self
    {
        $join = $this->config->tablePrefix . $join;
        if ($fromAlias) {
            $this->alias($fromAlias);
        } else {
            if ($this->aliasName) {
                $fromAlias = $this->aliasName;
            } else {
                $fromAlias = $this->getTableName();
            }
        }
        if (!$joinAlias) {
            $joinAlias = $join;
        }
        $this->queryBuilder->rightJoin($fromAlias, $join, $joinAlias, $condition);
        return $this;
    }

    /**
     * @param string $table
     * @param string|null $alias
     * @return DB
     */
    public function from(string $table, string $alias = null): self
    {
        $this->table = $table;
        $this->aliasName = $alias;
        $this->queryBuilder->add('from', [[
            'table' => $this->config->tablePrefix . $table,
            'alias' => $alias,
        ]], false);
        return $this;
    }

    /**
     * @param string $table
     * @param string|null $alias
     * @return DB
     */
    public function addFrom(string $table, string $alias = null): self
    {
        $this->table = $table;
        $this->aliasName = $alias;
        $this->queryBuilder->add('from', [
            'table' => $this->config->tablePrefix . $table,
            'alias' => $alias,
        ], true);
        return $this;
    }

    final public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    final public function commit(): bool
    {
        return $this->connection->commit();
    }

    final public function rollBack(): bool
    {
        return $this->connection->rollBack();
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
                $update->set($key, $this->quote($value, $types[$key]));
            } else {
                $update->set($key, $this->quote($value));
            }
        }
        $this->hasWhere = false;
        try {
            return $update->executeStatement();
        } catch (ServerException $e) {
            $exception = $e->getTrace()[5];
            throw new SqlErrorException($e->getMessage(), $e->getCode(), 0, $exception['file'], $exception['line'], $e);
        }
    }


    /**
     * @param $value
     * @param int $type
     * @return mixed
     */
    public function quote($value, int $type = ParameterType::STRING): mixed
    {
        if (is_array($value)) {
            foreach ($value as &$v) {
                $v = $this->quote($v, $type);
            }
            return $value;
        }
        if (is_null($value)) {
            return 'null';
        } else {
            return $this->getConnection()->quote($value, $type);
        }
    }

    /**
     * @param string|array $field
     * @param $value
     * @return $this
     */
    public function like(string|array $field, $value): self
    {
        if (is_array($field)) {
            $expr = [];
            foreach ($field as $f) {
                $expr[] = $this->expr()->like($f, $this->quote($value));
            }
            $where = $this->queryBuilder->expr()->or(...$expr);
        } else {
            $where = $this->expr()->like($field, $this->quote($value));
        }
        $this->where($where);
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
     * @param array $Between
     * @param int|string|Type|null $type
     * @return $this
     */
    final public function between($field, array $Between, Type|int|string|null $type = ParameterType::STRING): self
    {
        $field = $this->_Field($field);
        if (count($Between) !== 2) {
            throw new SqlErrorException('Too few params to function Between($field, $Between), Must two params;');
        }
        $Between = $this->quote($Between, $type);
        $pBetween = $Between[0] . ' AND ' . $Between[1];
        return $this->where("{$field} BETWEEN {$pBetween}");
    }

    /**
     * @param $field
     * @param array $Between
     * @param int|string|Type|null $type
     * @return $this
     */
    final public function notBetween($field, array $Between, Type|int|string|null $type = ParameterType::STRING): self
    {
        $field = $this->_Field($field);
        if (count($Between) !== 2) {
            throw new SqlErrorException('Too few params to function Between($field, $Between), Must two params;');
        }
        $Between = $this->quote($Between, $type);
        $pBetween = $Between[0] . ' AND ' . $Between[1];
        return $this->where("{$field} NOT BETWEEN {$pBetween}");
    }

    /**
     * @param string|array $field
     * @param $value
     * @return $this
     */
    public function leftLike(string|array $field, $value): self
    {
        return $this->like($field, '%' . $value);
    }

    /**
     * @param string|array $field
     * @param $value
     * @return $this
     */
    public function rightLike(string|array $field, $value): self
    {
        return $this->like($field, $value . '%');
    }

    /**
     * @param string|array $field
     * @param $value
     * @return $this
     */
    public function bothLike(string|array $field, $value): self
    {
        return $this->like($field, '%' . $value . '%');
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
     * @return Data|bool
     */
    public function query(string $sql = null, array $params = [], array $types = [], ?QueryCacheProfile $qcp =
    null): Data|bool
    {
        try {

            if ($sql) {
                $data = $this->queryBuilder->getConnection()->executeQuery($sql, $params, $types, $qcp)
                    ->fetchAllAssociative();
            } else {
                $sql = $this->queryBuilder->getSQL();
                if ($this->lockMode !== null) {
                    $sql .= ' ' . $this->connection->getDatabasePlatform()->getWriteLockSQL();
                    $this->lockMode = null;
                }
                $data = $this->connection->executeQuery($sql, $this->queryBuilder->getParameters(),
                    $this->queryBuilder->getParameterTypes(), $qcp)
                    ->fetchAllAssociative();
            }
        } catch (ServerException $e) {
            $exception = $e->getTrace()[4];
            throw new SqlErrorException($e->getMessage(), $e->getCode(), 0, $exception['file'], $exception['line'], $e);
        }
        foreach ($data as $key => $item) {
            $data[$key] = new Data($item);
        }
        $this->hasWhere = false;
        return new Data($data);
    }

    /**
     * @param string $field
     * @return int
     */
    public function count(string $field = '*'): int
    {
        $this->hasWhere = false;
        try {
            return (int)$this->queryBuilder->select('COUNT(' . $field . ')')->executeQuery()->fetchOne();
        } catch (ServerException $e) {
            $exception = $e->getTrace()[4];
            throw new SqlErrorException($e->getMessage(), $e->getCode(), 0, $exception['file'], $exception['line'], $e);
        }
    }

    /**
     * @param string $field
     * @return false|mixed
     */
    public function max(string $field)
    {
        $this->hasWhere = false;
        try {
            return $this->queryBuilder->select('MAX(' . $field . ')')->executeQuery()->fetchOne();
        } catch (ServerException $e) {
            $exception = $e->getTrace()[4];
            throw new SqlErrorException($e->getMessage(), $e->getCode(), 0, $exception['file'], $exception['line'], $e);
        }
    }

    /**
     * @param string $field
     * @return false|mixed
     */
    public function min(string $field)
    {
        $this->hasWhere = false;
        try {
            return $this->queryBuilder->select('MIN(' . $field . ')')->executeQuery()->fetchOne();
        } catch (ServerException $e) {
            $exception = $e->getTrace()[4];
            throw new SqlErrorException($e->getMessage(), $e->getCode(), 0, $exception['file'], $exception['line'], $e);
        }
    }


    /**
     * @param string $field
     * @return false|mixed
     */
    public function sum(string $field)
    {
        $this->hasWhere = false;
        try {
            return $this->queryBuilder->select('SUM(' . $field . ')')->executeQuery()->fetchOne();
        } catch (ServerException $e) {
            $exception = $e->getTrace()[4];
            throw new SqlErrorException($e->getMessage(), $e->getCode(), 0, $exception['file'], $exception['line'], $e);
        }
    }


    /**
     * @param string $field
     * @return false|mixed
     */
    public function avg(string $field)
    {
        $this->hasWhere = false;
        try {
            return $this->queryBuilder->select('AVG(' . $field . ')')->executeQuery()->fetchOne();
        } catch (ServerException $e) {
            $exception = $e->getTrace()[4];
            throw new SqlErrorException($e->getMessage(), $e->getCode(), 0, $exception['file'], $exception['line'], $e);
        }
    }

    /**
     * @param string $field
     * @return int
     */
    public function length(string $field): int
    {
        $this->hasWhere = false;
        try {
            return (int)$this->queryBuilder->select('LENGTH(' . $field . ')')->executeQuery()->fetchOne();
        } catch (ServerException $e) {
            $exception = $e->getTrace()[4];
            throw new SqlErrorException($e->getMessage(), $e->getCode(), 0, $exception['file'], $exception['line'], $e);
        }
    }

    /**
     * @return Data|null
     */
    public function find(): ?Data
    {
        $this->hasWhere = false;

        $data = $this->limit(0, 1)->query();
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
        try {
            return $this->queryBuilder->select($field_name)->setMaxResults(1)->executeQuery()->fetchOne();
        } catch (ServerException $e) {
            $exception = $e->getTrace()[4];
            throw new SqlErrorException($e->getMessage(), $e->getCode(), 0, $exception['file'], $exception['line'], $e);
        }
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
        try {
            return $this->queryBuilder->delete($delete ?: $this->getTableName(), $alias)->executeStatement();
        } catch (ServerException $e) {
            $exception = $e->getTrace()[5];
            throw new SqlErrorException($e->getMessage(), $e->getCode(), 0, $exception['file'], $exception['line'], $e);
        }
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
    final public function paginate(int $number = 10, int $page = 1): Data|array
    {
        $page = $page > 0 ? $page : 1;
        $min = ($page - 1) * $number;
        return $this->limit($min, $number)->query();
    }


    public function __clone(): void
    {
        $this->queryBuilder = clone $this->queryBuilder;
    }
}