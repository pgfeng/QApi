<?php


namespace QApi\ORM;

use Closure;
use DateInterval;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\InvalidLockMode;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Types\Type;
use ErrorException;
use QApi\Attribute\Column\Field;
use QApi\Attribute\Utils;
use QApi\Config;
use QApi\Config\Abstracts\Database;
use QApi\Config\Database\MysqliDatabase;
use QApi\Config\Database\PdoMysqlDatabase;
use QApi\Config\Database\PdoSqliteDatabase;
use QApi\Config\Database\SqlServDatabase;
use QApi\Data;
use QApi\Enumeration\CliColor;
use QApi\Exception\SqlErrorException;
use QApi\Logger;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

/**
 * Class DB
 * @package QApi\ORM
 * @method $this setFirstResult(int $offset),
 * @method $this setMaxResults(int $maxNumber),
 * @method ExpressionBuilder expr(),
 * @method int getType(),
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
 * @method $this resetQueryPart(string|array $queryPart)
 * @method $this resetQueryParts($queryPartNames = null)
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
    protected ?QueryBuilder $queryBuilder = null;
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
     * @var int|null
     */
    protected ?int $lockMode = null;

    /**
     * @var callable(Data):void|null $bindRecordCallback
     */
    private ?Closure $bindRecordCallback = null;

    /**
     * @var callable(Data):void|null $bindCollectionCallback
     */
    private ?Closure $bindCollectionCallback = null;

    /**
     * @var callable(Data):void|null $recordCallback
     */
    private ?Closure $recordCallback = null;

    /**
     * @var callable(Data):void|null $collectionCallback
     */
    private ?Closure $collectionCallback = null;

    /**
     * @var bool|null
     */
    protected ?bool $cacheSwitch = false;

    /**
     * @var string
     */
    protected string $cacheSpaceKey = '';

    /**
     * @var int|DateInterval|null
     */
    protected null|int|DateInterval $cacheTtl = null;

    public static array $dbColumns = [];

    private bool $isOr = false;

    /**
     * @return string|null
     */
    public function getAliasName(): ?string
    {
        return $this->aliasName;
    }

    /**
     * @param string $columnClassName
     * @param array|string $rejectFiled
     * @param string|null $aliasName
     * @return $this
     * @throws ReflectionException
     */
    public function reject(string $columnClassName, array|string $rejectFiled, null|string $aliasName = null): self
    {
        if (is_string($rejectFiled)) {
            $rejectFiled = [$rejectFiled];
        }
        $columns = array_keys(Utils::tableColumn($columnClassName, $rejectFiled));
        if ($aliasName) {
            foreach ($columns as $k => $column) {
                $columns[$k] = $aliasName . '.' . $column;
            }
        }
        return $this->select(...$columns);
    }

    /**
     * Set a one-time callback function to process each element in the collection.
     * @param callable(Data $data):void $callback
     * @return $this
     */
    public function record(callable $callback): self
    {
        $this->recordCallback = $callback;
        return $this;
    }

    /**
     * Set the callback function to be executed before returning the records.
     * @param callable $callback
     * @return $this
     */
    public function collection(callable $callback): self
    {
        $this->collectionCallback = $callback;
        return $this;
    }

    /**
     * DB constructor.
     * @param string $table
     * @param string $configName
     * @throws ErrorException
     */
    public function __construct(string $table, private string $configName)
    {
        $this->config = Config::database($configName);
        $this->setConnect($configName, $this->config);
        $this->select('*');
        if (!isset(self::$dbColumns[$configName][$table])) {
            try {
                if (class_exists(Config::command('BaseColumnNameSpace') . '_' . $configName . '\\' . $table)) {
                    $ref = new ReflectionClass(Config::command('BaseColumnNameSpace') . '_' . $configName . '\\' . $table);
                    $constants = $ref->getReflectionConstants();
                    $columns = [];
                    foreach ($constants as $constant) {
                        $field = $constant->getAttributes(Field::class);
                        if (isset($field[0])) {
                            $arguments = $field[0]->getArguments();
                            $columns[$arguments['name']] = $arguments;
                        }
                    }
                    self::$dbColumns[$configName][$table] = $columns;
                }
            } catch (ReflectionException $e) {
                Logger::error($e->getMessage());
            }
        }
        if ($table) {
            $this->table = $table;
            $this->from($table);
        }
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function setTable(string $table): self
    {
        $this->from($table);
        $this->table = $table;
        return $this;
    }

    /**
     * @param $configName
     * @param Database $config
     * @return mixed
     * @throws ErrorException
     */
    public function setConnect($configName, Database $config): self
    {
        $this->config = $config;
        $this->connection = DriverManager::addConnect($configName, $config);
        $oldParts = [];
        if ($this->queryBuilder) {
            $oldParts = $this->queryBuilder->getQueryParts();
        }
        $this->queryBuilder = $this->connection->createQueryBuilder();
        foreach ($oldParts as $key => $value) {
            $this->queryBuilder->add($key, $value);
        }
        $this->configName = $configName;
        $this->schemaManager = $this->connection->createSchemaManager();
        return $this;
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @param int $lockMode
     * @return $this
     * @throws Exception
     * @throws InvalidLockMode
     */
    public function lock(int $lockMode = LockMode::PESSIMISTIC_WRITE): self
    {
        $this->lockMode = $lockMode;
        $this->from($this->connection->getDatabasePlatform()->appendLockHint($this->table, $lockMode), $this->aliasName);
        return $this;
    }

    /**
     * @throws Exception
     */
    public
    function getSchemaManager(): AbstractSchemaManager
    {
        if ($this->schemaManager) {
            return $this->schemaManager;
        } else {
            return $this->schemaManager = $this->connection->createSchemaManager();
        }
    }

    /**
     * @param bool $tablePrefix
     * @return string
     */
    public
    function getTableName(bool $tablePrefix = true): string
    {
        if ($tablePrefix) {
            return $this->table ? $this->config->tablePrefix . $this->table : '';
        }
        return $this->table;
    }

    /**
     * @param string $formAlias
     * @return $this
     */
    public
    function alias(string $formAlias): self
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
    public
    function join(string $join, string $condition, ?string $joinAlias = null, ?string $fromAlias = null): self
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
    public
    function innerJoin(string $join, string $condition, ?string $joinAlias = null, ?string $fromAlias = null): self
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
    public
    function leftJoin(string $join, string $condition, ?string $joinAlias = null, ?string $fromAlias = null): self
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
    public
    function rightJoin(string $join, string $condition, ?string $joinAlias = null, ?string $fromAlias = null):
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
    public
    function from(string $table, string $alias = null): self
    {
        $this->table = $table;
        $this->aliasName = $alias;
        $this->queryBuilder->add('from', [[
            'table' => $this->config->tablePrefix . $table,
            'alias' => $alias,
        ]]);
        return $this;
    }

    /**
     * @param string $table
     * @param string|null $alias
     * @return DB
     */
    public
    function addFrom(string $table, string $alias = null): self
    {
        $this->table = $table;
        $this->aliasName = $alias;
        $this->queryBuilder->add('from', [
            'table' => $this->config->tablePrefix . $table,
            'alias' => $alias,
        ], true);
        return $this;
    }

    /**
     * @throws Exception
     */
    final public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * @throws Exception
     */
    final public function commit(): bool
    {
        return $this->connection->commit();
    }

    /**
     * @throws Exception
     */
    final public function rollBack(): bool
    {
        return $this->connection->rollBack();
    }

    /**
     * 闭包执行事务，返回事务执行的状态
     * @param Closure $callback
     * @return bool
     * @throws Exception
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
     * @param string $field
     * @param array $values
     * @return $this
     */
    public function in(string $field, array $values): self
    {

        $expr = $this->expr()->in($field, $this->quote($values));
        if ($this->hasWhere) {
            $this->queryBuilder
                ->andWhere(
                    $expr
                );
        } else {
            $this->queryBuilder
                ->where(
                    $expr
                );
            $this->hasWhere = true;
        }
        return $this;
    }


    /**
     * @param string $field
     * @param array $values
     * @return $this
     */
    public function notIn(string $field, array $values): self
    {
        $expr = $this->expr()->notIn($field, $this->quote($values));
        if ($this->hasWhere) {
            $this->queryBuilder
                ->andWhere(
                    $expr
                );
        } else {
            $this->queryBuilder
                ->where(
                    $expr
                );
            $this->hasWhere = true;
        }
        return $this;
    }


    /**
     * @param string | ExpressionBuilder $expression
     * @param ExpressionBuilder | string ...$expressions
     * @return $this
     */
    public function or(ExpressionBuilder|string $expression, ...$expressions): self
    {
        $this->queryBuilder->orWhere($expression, ...$expressions);
        return $this;
    }

    /**
     * @param string | ExpressionBuilder $expression
     * @param ExpressionBuilder | string ...$expressions
     * @return $this
     */
    public function and(ExpressionBuilder|string $expression, ...$expressions): self
    {
        $this->queryBuilder->andWhere($expression, ...$expressions);
        return $this;
    }

    /**
     * @param ExpressionBuilder|string|array|Closure $predicates
     * @param mixed|null $op
     * @param mixed|null $value
     * @return $this
     */
    public function where(ExpressionBuilder|string|array|Closure $predicates, mixed $op = null, mixed $value = null): self
    {
        $arg_number = func_num_args();
        if ($arg_number === 1) {
            if ($predicates instanceof Closure) {
                $predicates($this);
            } else if (is_array($predicates)) {
                foreach ($predicates as $key => $value) {
                    if ($value instanceof ExpressionBuilder) {
                        $this->where($value);
                    } else if ($value instanceof Closure) {
                        $this->where($value);
                    } else {
                        $this->where($key, $value);
                    }
                }
            } else {
                if ($this->hasWhere) {
                    if ($this->isOr) {
                        $this->queryBuilder->orWhere($predicates);
                    } else {
                        $this->queryBuilder->andWhere($predicates);
                    }
                } else {
                    $this->queryBuilder->where($predicates);
                }
            }
        } else if ($arg_number === 2) {
            $values = func_get_arg(1);
            if (is_array($predicates)) {
                $wheres = [];
                foreach ($predicates as $key => $value) {
                    if (is_null($value)) {
                        $wheres[] = $this->parseField($value) . ' IS NULL';
                    } else {
                        $wheres[] = $this->parseField($value) . ' = ' . $this->quote($values);
                    }
                }
                $this->or(...$wheres);
            } else {
                if (is_array($values)) {
                    $this->where($predicates, 'in', $values);
                } else if (is_null($values)) {
                    $this->where($predicates, '=', null);
                } else {
                    $this->where($predicates, '=', $values);
                }
            }
        } elseif ($arg_number === 3) {
            $predicates = $this->parseField($predicates);
            $expr = $this->expr();
            $op = strtoupper($op);
            $tmpOp = str_replace(' ', '-', $op);
            if ($tmpOp === 'IN') {
                $where = $expr->in($predicates, $this->quote($value));
            } else if ($tmpOp === 'NOT-IN') {
                $where = $expr->notIn($predicates, $this->quote($value));
            } else if ($tmpOp === 'LIKE') {
                $where = $expr->like($predicates, $this->quote($value));
            } else if ($tmpOp === 'NOT-LIKE') {
                $where = $expr->like($predicates, $this->quote($value));
            } else if ($tmpOp === 'BETWEEN') {
                if (is_array($value)) {
                    $where = $expr->comparison($predicates, $op, $this->quote($value[0]) . ' AND ' . $this->quote($value[1]));
                } else {
                    $where = $expr->comparison($predicates, $op, $value);
                }
            } else if ($tmpOp === 'NOT-BETWEEN') {
                if (is_array($value)) {
                    $where = $expr->comparison($predicates, 'NOT BETWEEN', $this->quote($value[0]) . ' AND ' . $this->quote($value[1]));
                } else {
                    $where = $expr->comparison($predicates, 'NOT BETWEEN', $value);
                }
            } else if (is_null($value)) {
                if ($op === '=') {
                    $where = $expr->isNull($predicates);
                } else {
                    $where = $expr->isNotNull($predicates);
                }
            } else {
                $where = $expr->comparison($predicates, $op, $this->quote($value));
            }
            if ($this->hasWhere) {
                if ($this->isOr) {
                    $this->queryBuilder->orWhere($where);
                } else {
                    $this->queryBuilder->andWhere($where);
                }
            } else {
                $this->queryBuilder
                    ->where($where);
            }
        }
        $this->hasWhere = true;
        return $this;
    }

    /**
     * @param string $field
     * @return $this
     */
    public function isNull(string $field): self
    {
        return $this->where($field, null);
    }

    /**
     * @param string $field
     * @return $this
     */
    public function isNotNull(string $field): self
    {
        return $this->where($field, '<>', null);
    }

    /**
     * @param ExpressionBuilder|string|array $predicates
     * @param mixed|null $op
     * @param mixed|null $value
     * @return $this
     */
    public function orWhere(ExpressionBuilder|string|array $predicates, mixed $op = null, mixed $value = null): self
    {
        $this->isOr = true;
        $this->where(...func_get_args());
        $this->isOr = false;
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
        return $this->where(...func_get_args());
    }

    /**
     * @param array|Data $data
     * @param array $types
     * @param string|null $table
     * @return int
     * @throws Exception
     */
    public function insert(array|Data $data, array $types = [], ?string $table = null): int
    {
        if ($data instanceof Data) {
            $data = $data->toArray();
        }
        foreach ($data as &$item) {
            if (is_array($item)) {
                $item = json_encode($item, JSON_UNESCAPED_UNICODE);
            }
        }
        if (!$table) {
            $table = $this->table;
        }
        foreach ($data as $key => $value) {
            $data[$key] = $this->quote($value, $types[$key] ?? ParameterType::STRING);
        }
        return $this->queryBuilder
            ->insert($this->config->tablePrefix . $table)
            ->values($data)
            ->executeStatement();
    }

    /**
     * @param array|Data $data
     * @param array $types
     * @param string|null $table
     * @return int
     * @throws Exception
     */
    public function batchInsert(array|Data $data, array $types = [], ?string $table = null): int
    {
        if ($data instanceof Data) {
            $data = $data->toArray();
        }
        if (!$table) {
            $table = $this->table;
        }
        $valueDql = '';
        foreach ($data as $value) {
            $valueDql .= '(';
            foreach ($value as $k => $v) {
                $valueDql .= $this->quote($v, $types[$k] ?? ParameterType::STRING) . ',';
            }
            $valueDql = rtrim($valueDql, ',') . '),';
        }
        return $this->connection->executeStatement(
            'INSERT INTO ' . $this->config->tablePrefix . $table . '(' . implode(',', array_keys($data[0])) . ') VALUES ' . rtrim($valueDql, ',')
        );
    }

    /**
     * @param array|Data $data
     * @param array $types
     * @param string|null $table
     * @return int
     * @throws SqlErrorException
     */
    public function update(array|Data $data, array $types = [], ?string $table = null): int
    {
        if ($data instanceof Data) {
            $data = $data->toArray();
        }
        foreach ($data as &$item) {
            if (is_array($item)) {
                $item = json_encode($item, JSON_UNESCAPED_UNICODE);
            }
        }
        if (!$table) {
            $table = $this->table;
        }
        $update = $this->queryBuilder->update($this->config->tablePrefix . $table);
        foreach ($data as $key => $value) {
            if (isset($types[$key])) {
                if ($types[$key] === true) {
                    $update->set($key, $value);
                } else {
                    $update->set($key, $this->quote($value, $types[$key]));
                }
            } else {
                $update->set($key, $this->quote($value));
            }
        }
        $this->hasWhere = false;
        try {
            return $update->executeStatement();
        } catch (\Exception $e) {
            $traces = $e->getTrace();
            $realTrance = null;
            foreach ($traces as $trace) {
                if (isset($trace['class']) && in_array($trace['class'], [
                        'QApi\\ORM\\Model', 'QApi\\ORM\\DB'
                    ])) {
                    $realTrance = $trace;
                }
            }
            if ($realTrance) {
                throw new SqlErrorException($e->getMessage(), (int)$e->getCode(), 0, $realTrance['file'], $realTrance['line'], $e);
            } else {
                throw new SqlErrorException($e->getMessage(), (int)$e->getCode(), 0, $e['file'], $e['line'], $e);
            }
        }
    }


    /**
     * @param $value
     * @param int|bool $type
     * @return mixed
     */
    public function quote($value, int|bool $type = ParameterType::STRING): mixed
    {
        if (is_array($value)) {
            foreach ($value as &$v) {
                if ($type !== true) {
                    $v = $this->quote($v, $type);
                }
            }
            return $value;
        } elseif ($type === true) {
            return $value;
        } else if ($value === null) {
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
     * @throws SqlErrorException
     */
    final public function setInc(string $field, mixed $change = 1): int
    {
        $field = $this->parseField($field);
        return $this->update([
            $field => $field . ' + ' . $change,
        ], [$field => true]);
    }

    /**
     * @param string $field
     * @param int|float $change
     * @return bool
     * @throws SqlErrorException
     */
    final public function setDec(string $field, mixed $change = 1): bool
    {
        $field = $this->parseField($field);
        return $this->update([
            $field => $field . ' - ' . $change,
        ], [$field => true]);
    }

    /**
     * @param $field
     * @param array $Between
     * @param int|string|Type|null $type
     * @return $this
     * @throws SqlErrorException
     */
    final public function between($field, array $Between, Type|int|string|null $type = ParameterType::STRING): self
    {
        $field = $this->parseField($field);
        if (count($Between) !== 2) {
            throw new SqlErrorException('Too few params to function Between($field, $Between), Must two params;');
        }
        $Between = $this->quote($Between, $type);
        $pBetween = $Between[0] . ' AND ' . $Between[1];
        return $this->where($field, 'BETWEEN', $pBetween);
    }

    /**
     * @param $field
     * @param array $Between
     * @param int|string|Type|null $type
     * @return $this
     * @throws SqlErrorException
     */
    final public function notBetween($field, array $Between, Type|int|string|null $type = ParameterType::STRING): self
    {
        $field = $this->parseField($field);
        if (count($Between) !== 2) {
            throw new SqlErrorException('Too few params to function Between($field, $Between), Must two params;');
        }
        $Between = $this->quote($Between, $type);
        $pBetween = $Between[0] . ' AND ' . $Between[1];
        return $this->where("$field NOT BETWEEN $pBetween");
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
        $this->queryBuilder->where($this->expr()->notLike($field, $this->quote($value)));
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

    private function setCache($sql, $data): void
    {
        if ($this->config->cacheAdapter && $this->cacheSwitch) {
            $this->config->cacheAdapter->set(($this->cacheSpaceKey ? '[' . $this->cacheSpaceKey . ']' : '') . $sql, $data, $this->cacheTtl);
        }
        // resetCache
        $this->cacheSwitch = false;
        $this->cacheTtl = null;
        $this->cacheSpaceKey = '';
    }

    /**
     * @param string|null $sql
     * @param array $params
     * @param array $types
     * @return Data|bool
     * @throws SqlErrorException
     */
    public function query(string $sql = null, array $params = [], array $types = []): Data|bool
    {
        try {
            if ($sql) {
                $data = null;
                if ($this->config->cacheAdapter && $this->cacheSwitch) {
                    $data = $this->config->cacheAdapter->get($sql);
                }
                if ($data === null) {
                    $data = $this->connection->executeQuery($sql, $params, $types)
                        ->fetchAllAssociative();
                    $this->setCache($sql, $data);
                }
            } else {
                $sql = $this->queryBuilder->getSQL();
                if ($this->lockMode !== null) {
                    $sql .= match ($this->lockMode) {
                        LockMode::PESSIMISTIC_READ => ' ' . $this->connection->getDatabasePlatform()->getReadLockSQL(),
                        LockMode::PESSIMISTIC_WRITE => ' ' . $this->connection->getDatabasePlatform()->getWriteLockSQL(),
                        default => '',
                    };
                    $this->lockMode = null;
                }
                $data = null;
                if ($this->config->cacheAdapter && $this->cacheSwitch) {
                    $data = $this->config->cacheAdapter->get($sql);
                }
                if ($data === null) {
                    $data = $this->connection->executeQuery($sql, $this->queryBuilder->getParameters(),
                        $this->queryBuilder->getParameterTypes())
                        ->fetchAllAssociative();
                    $this->setCache($sql, $data);
                }
            }
        } catch (\Exception $e) {
            $traces = $e->getTrace();
            $realTrance = null;
            foreach ($traces as $trace) {
                if (isset($trace['class']) && in_array($trace['class'], [
                        'QApi\\ORM\\Model', 'QApi\\ORM\\DB'
                    ])) {
                    $realTrance = $trace;
                }
            }
            if ($realTrance) {
                throw new SqlErrorException($e->getMessage(), (int)$e->getCode(), 0, $realTrance['file'], $realTrance['line'], $e);
            } else {
                throw new SqlErrorException($e->getMessage(), (int)$e->getCode(), 0, $e['file'], $e['line'], $e);
            }
        }
        if ($this instanceof Model) {
            $setModel = true;
        } else {
            $setModel = false;
        }
        foreach ($data as $key => $item) {
            if (isset(self::$dbColumns[$this->configName][$this->table])) {
                foreach ($item as $k => $v) {
                    $field = explode('.', $k);
                    $field = $field[count($field) - 1];
                    if (!is_null($v) && isset(self::$dbColumns[$this->configName][$this->table][$field])) {
                        $type = self::$dbColumns[$this->configName][$this->table][$field]['type'];
                        if (stripos($type, 'INT') === 0) {
                            $item[$k] = (int)$v;
                        } else if (stripos($type, 'TINYINT') === 0) {
                            $item[$k] = (int)$v;
                        } else if (stripos($type, 'DECIMAL') > -1 || stripos($type, 'FLOAT') > -1) {
                            if ($v > PHP_FLOAT_MAX || $v < PHP_FLOAT_MIN) {
                                $item[$k] = (string)$v;
                            } else {
                                $item[$k] = (float)$v;
                            }
                        } else if (stripos($type, 'JSON') > -1) {
                            try {
                                $item[$k] = json_decode($v, true, JSON_UNESCAPED_UNICODE);
                            } catch (\Exception $e) {
                                $item[$k] = null;
                            }
                        } else {
                            $item[$k] = (string)$v;
                        }
                    }
                }
            }
            $dataObject = new Data($item);
            if ($setModel) {
                $dataObject->setModel($this);
            }
            $data[$key] = $dataObject;
        }
        $this->hasWhere = false;
        if (!is_array($data)) {
            $data = [];
        }
        $data = new Data($data);
        if ($this->bindCollectionCallback instanceof Closure) {
            call_user_func($this->bindCollectionCallback, $data);
        }
        if ($this->collectionCallback instanceof Closure) {
            call_user_func($this->collectionCallback, $data);
            $this->collectionCallback = null;
        }
        if ($this->bindRecordCallback instanceof Closure) {
            $data->each($this->bindRecordCallback);
        }
        if ($this->recordCallback instanceof Closure) {
            $data->each($this->recordCallback);
            $this->recordCallback = null;
        }
        $this->cacheSwitch = false;
        return $data;
    }

    /**
     * @param string $field
     * @return int
     * @throws SqlErrorException
     */
    public function count(string $field = '*'): int
    {
        $this->hasWhere = false;
        try {
            return (int)$this->select('COUNT(' . $field . ')')->fetchOne();
        } catch (\Exception $e) {
            $traces = $e->getTrace();
            $realTrance = null;
            Logger::error($traces);
            foreach ($traces as $trace) {
                if (isset($trace['class']) && in_array($trace['class'], [
                        'QApi\\ORM\\Model', 'QApi\\ORM\\DB'
                    ])) {
                    $realTrance = $trace;
                }
            }
            if ($realTrance) {
                throw new SqlErrorException($e->getMessage(), (int)$e->getCode(), 0, $realTrance['file'], $realTrance['line'], $e);
            } else {
                throw new SqlErrorException($e->getMessage(), (int)$e->getCode(), 0, $e['file'], $e['line'], $e);
            }
        }
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function fetchOne(): mixed
    {
        $sql = $this->queryBuilder->getSQL();
        $data = null;
        if ($this->config->cacheAdapter && $this->cacheSwitch) {
            $data = $this->config->cacheAdapter->get($sql);
        }
        if ($data === null) {
            $data = $this->connection->executeQuery($sql)->fetchOne();
            $this->setCache($sql, $data);
        }
        return $data;
    }

    /**
     * @param string $field
     * @return false|mixed
     * @throws SqlErrorException
     */
    public function max(string $field): mixed
    {
        $this->hasWhere = false;
        try {
            return $this->select('MAX(' . $field . ')')->fetchOne();
        } catch (\Exception $e) {
            $traces = $e->getTrace();
            $realTrance = null;
            foreach ($traces as $trace) {
                if (isset($trace['class']) && in_array($trace['class'], [
                        'QApi\\ORM\\Model', 'QApi\\ORM\\DB'
                    ])) {
                    $realTrance = $trace;
                }
            }
            if ($realTrance) {
                throw new SqlErrorException($e->getMessage(), (int)$e->getCode(), 0, $realTrance['file'], $realTrance['line'], $e);
            } else {
                throw new SqlErrorException($e->getMessage(), (int)$e->getCode(), 0, $e['file'], $e['line'], $e);
            }
        }
    }

    /**
     * @param string $field
     * @return false|mixed
     * @throws SqlErrorException
     */
    public function min(string $field): mixed
    {
        $this->hasWhere = false;
        try {
            return $this->select('MIN(' . $field . ')')->fetchOne();
        } catch (\Exception $e) {
            $traces = $e->getTrace();
            $realTrance = null;
            foreach ($traces as $trace) {
                if (isset($trace['class']) && in_array($trace['class'], [
                        'QApi\\ORM\\Model', 'QApi\\ORM\\DB'
                    ])) {
                    $realTrance = $trace;
                }
            }
            if ($realTrance) {
                throw new SqlErrorException($e->getMessage(), (int)$e->getCode(), 0, $realTrance['file'], $realTrance['line'], $e);
            } else {
                throw new SqlErrorException($e->getMessage(), (int)$e->getCode(), 0, $e['file'], $e['line'], $e);
            }
        }
    }


    /**
     * @param string $field
     * @return false|mixed
     * @throws SqlErrorException
     */
    public function sum(string $field): mixed
    {
        $this->hasWhere = false;
        try {
            return $this->select('SUM(' . $field . ')')->fetchOne();
        } catch (\Exception $e) {
            $traces = $e->getTrace();
            $realTrance = null;
            foreach ($traces as $trace) {
                if (isset($trace['class']) && in_array($trace['class'], [
                        'QApi\\ORM\\Model', 'QApi\\ORM\\DB'
                    ])) {
                    $realTrance = $trace;
                }
            }
            if ($realTrance) {
                throw new SqlErrorException($e->getMessage(), (int)$e->getCode(), 0, $realTrance['file'], $realTrance['line'], $e);
            } else {
                throw new SqlErrorException($e->getMessage(), (int)$e->getCode(), 0, $e['file'], $e['line'], $e);
            }
        }
    }


    /**
     * @param string $field
     * @return false|mixed
     * @throws SqlErrorException
     */
    public function avg(string $field): mixed
    {
        $this->hasWhere = false;
        try {
            return $this->select('AVG(' . $field . ')')->fetchOne();
        } catch (\Exception $e) {
            $traces = $e->getTrace();
            $realTrance = null;
            foreach ($traces as $trace) {
                if (isset($trace['class']) && in_array($trace['class'], [
                        'QApi\\ORM\\Model', 'QApi\\ORM\\DB'
                    ])) {
                    $realTrance = $trace;
                }
            }
            if ($realTrance) {
                throw new SqlErrorException($e->getMessage(), (int)$e->getCode(), 0, $realTrance['file'], $realTrance['line'], $e);
            } else {
                throw new SqlErrorException($e->getMessage(), (int)$e->getCode(), 0, $e['file'], $e['line'], $e);
            }
        }
    }

    /**
     * @param string $field
     * @return int
     * @throws SqlErrorException
     */
    public function length(string $field): int
    {
        $this->hasWhere = false;
        try {
            return (int)$this->select('LENGTH(' . $field . ')')->fetchOne();
        } catch (\Exception $e) {
            $traces = $e->getTrace();
            $realTrance = null;
            foreach ($traces as $trace) {
                if (isset($trace['class']) && in_array($trace['class'], [
                        'QApi\\ORM\\Model', 'QApi\\ORM\\DB'
                    ])) {
                    $realTrance = $trace;
                }
            }
            if ($realTrance) {
                throw new SqlErrorException($e->getMessage(), (int)$e->getCode(), 0, $realTrance['file'], $realTrance['line'], $e);
            } else {
                throw new SqlErrorException($e->getMessage(), (int)$e->getCode(), 0, $e['file'], $e['line'], $e);
            }
        }
    }

    /**
     * @return Data|null
     * @throws SqlErrorException
     */
    public function find(): ?Data
    {
        $this->hasWhere = false;

        $data = $this->limit(0, 1)->query();
        if (count($data)) {
            return $data[0];
        }
        return null;
    }

    /**
     * @param mixed $val
     * @param string $field
     * @return Data|null
     * @throws SqlErrorException
     */
    public function findByKey(mixed $val, string $field): ?Data
    {
        return $this->where($field, $val)->find();
    }

    /**
     * @param $field
     * @return string|array
     */
    final public function parseField($field): string|array
    {
        if (is_string($field)) {
            if (!$this->getAliasName()) {
                if (str_contains($field, '.')) {
                    $field = $this->config->tablePrefix . $field;
                }
            }
        } else if (is_array($field)) {
            foreach ($field as &$item) {
                $item = $this->parseField($item);
            }
        }
        return $field;
    }

    /**
     * @param $field_name
     * @return mixed
     * @throws SqlErrorException
     * @deprecated please use value() instead
     */
    final public function getField($field_name): mixed
    {
        return $this->value($field_name);
    }


    /**
     * @param $fieldName
     * @return mixed
     * @throws SqlErrorException
     */
    final public function value($fieldName): mixed
    {
        $this->hasWhere = false;
        $data = $this->select($fieldName)->paginate(1);
        if (count($data)) {
            return $data[0][$fieldName];
        } else {
            return null;
        }
    }

    /**
     * @param callable(Data):void $callback
     * @return $this
     */
    public function bindRecord(callable $callback): self
    {
        $this->bindRecordCallback = $callback;
        return $this;
    }

    /**
     * @param callable(Data):void $callback
     * @return self
     */
    public function bindCollection(callable $callback): self
    {
        $this->bindCollectionCallback = $callback;
        return $this;
    }

    /**
     * @param $field_name
     * @param $field_value
     * @param bool $format
     * @return int
     * @throws SqlErrorException
     * @deprecated please use setValue() instead
     */
    final public function setField($field_name, $field_value, bool $format = true): int
    {
        return $this->setValue($field_name, $field_value, $format);
    }

    /**
     * @param $field_name
     * @param $field_value
     * @param bool $format
     * @return int
     * @throws SqlErrorException
     */
    final public function setValue($field_name, $field_value, bool $format = true): int
    {

        $field_name = $this->parseField($field_name);
        return $this->update([
            $field_name => $field_value,
        ], !$format ? [$field_name => true] : []);
    }

    /**
     * @param string|null $delete
     * @param string|null $alias
     * @return int
     * @throws SqlErrorException
     */
    final public function delete(?string $delete = null, ?string $alias = null): int
    {
        try {
            return $this->queryBuilder->delete($delete ?: $this->getTableName(), $alias)->executeStatement();
        } catch (\Exception $e) {
            $traces = $e->getTrace();
            $realTrance = null;
            foreach ($traces as $trace) {
                if (isset($trace['class']) && in_array($trace['class'], [
                        'QApi\\ORM\\Model', 'QApi\\ORM\\DB'
                    ])) {
                    $realTrance = $trace;
                }
            }
            if ($realTrance) {
                throw new SqlErrorException($e->getMessage(), (int)$e->getCode(), 0, $realTrance['file'], $realTrance['line'], $e);
            } else {
                throw new SqlErrorException($e->getMessage(), (int)$e->getCode(), 0, $e['file'], $e['line'], $e);
            }
        }
    }

    /**
     * 不存在的方法将执行DB类中的方法
     *
     * @param $func
     * @param $val
     * @return mixed|DB
     */
    final public function __call($func, $val)
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
        throw  new RuntimeException($message);
    }

    /**
     * @return string|int
     * @throws Exception
     */
    final function lastInsertId(): int|string
    {
        return $this->connection->lastInsertId();
    }

    /**
     * @param int|DateInterval|null $ttl
     * @param string $spaceKey
     * @return $this
     */
    final public function cache(null|int|DateInterval $ttl = null, string $spaceKey = ''): self
    {
        $this->cacheSwitch = true;
        $this->cacheTtl = $ttl;
        $this->cacheSpaceKey = $spaceKey;
        return $this;
    }

    /**
     * @param int $size
     * @param int $page
     * @return Data|array
     * @throws SqlErrorException
     */
    public function paginate(int $size = 10, int $page = 1): Data|array
    {
        $page = $page > 0 ? $page : 1;
        $min = ($page - 1) * $size;
        return $this->limit($min, $size)->query();
    }


    public function __clone(): void
    {
        $this->queryBuilder = clone $this->queryBuilder;
    }
}