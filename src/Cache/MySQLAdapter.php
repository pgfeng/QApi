<?php


namespace QApi\Cache;


use DateInterval;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\BlobType;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use JetBrains\PhpStorm\Pure;
use QApi\Config\Cache\MySQL;
use QApi\Logger;

class MySQLAdapter implements CacheInterface
{
    private Connection $connection;
    private string $tableName;
    private int $cleanUpTime = 0;
    private int $connectionTime = 0;

    /**
     * MySQLAdapter constructor.
     * @param MySQL $config
     * @throws Exception
     */
    public function __construct(private MySQL $config)
    {
        $this->tableName = $this->config->database->tablePrefix . $this->config->table;
        $this->connect();
        $this->inspectTable();
    }


    public function connect(): void
    {
        $this->connection = ((new $this->config->database->connectorClass)->getConnector
        ($this->config->database));
        $this->connectionTime = time();
    }

    public function check(): void
    {
        $time = time();
        $this->checkConnect($time);
        $this->checkCleanUp($time);
    }

    public function checkCleanUp(int $time): void
    {
        if ($time >= ($this->cleanUpTime + $this->config->cleanUpTime - 10)) {
            try {
                $this->connection->executeStatement('DELETE FROM ' . $this->tableName . ' WHERE ' .
                    $this->config->expiresTimeCol . ' > ? AND ' . $this->config->lifetimeCol . ' > 0', [
                    time(),
                ], [
                    ParameterType::INTEGER
                ]);
                $this->cleanUpTime = time();
            } catch (Exception $e) {
                Logger::error($e->getMessage());
            }
        }
    }

    public function checkConnect(int $time): void
    {
        if ($time >= ($this->connectionTime + $this->config->database->wait_timeout - 10)) {
            $this->connection->close();
            $this->connect();
        }
    }

    /**
     * @throws Exception
     */
    public function inspectTable(): void
    {
        $schema = $this->connection->createSchemaManager();
        if (!$schema->tablesExist($this->tableName)) {
            $table = new Table($this->tableName);
            $table->addColumn($this->config->keyCol, Types::STRING, ['length' => $this->config->maxKeyLength]);
            $table->addColumn($this->config->dataCol, Types::BLOB);
            $table->addColumn($this->config->lifetimeCol, Types::INTEGER, ['unsigned' => true]);
            $table->addColumn($this->config->expiresTimeCol, Types::INTEGER, ['unsigned' => true]);
            $table->addColumn($this->config->timeCol, Types::INTEGER, ['unsigned' => true]);
            $table->setPrimaryKey([$this->config->keyCol]);
            $schema->createTable($table);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->check();
        $data = $this->connection->fetchOne(
            'SELECT ' . $this->config->dataCol . ' FROM ' . $this->tableName . ' WHERE (' .
            $this->config->expiresTimeCol . ' >= ? OR ' . $this->config->lifetimeCol . ' = 0) AND ' .
            $this->config->keyCol . ' = ?'
            , [
            time(),
            $this->config->namespace . $key,
        ], [
            ParameterType::INTEGER,
            ParameterType::STRING,
        ]);
        if ($data === false) {
            return $default;
        }
        return unserialize($data, [
            'allowed_classes' => true,
        ]);
    }

    /**
     * @param DateInterval|int|null $ttl
     * @return int
     */
    #[Pure] public function getDateIntervalToSecond(DateInterval|int|null $ttl = null): int
    {
        if ($ttl === null) {
            return 0;
        }

        if ($ttl instanceof \DateInterval) {
            return $ttl->days * 86400 + $ttl->h * 3600
                + $ttl->i * 60 + $ttl->s;
        }

        if (is_int($ttl)) {
            return $ttl;
        }
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->check();
        $lifeTime = $this->getDateIntervalToSecond($ttl);
        $time = time();
        $value = serialize($value);
        if ($this->realHas($this->config->namespace . $key)) {
            try {
                return $this->connection->update($this->tableName, [
                        $this->config->dataCol => $value,
                        $this->config->expiresTimeCol => $time + $lifeTime,
                        $this->config->lifetimeCol => $lifeTime,
                        $this->config->timeCol => $time,
                    ], [
                        $this->config->keyCol => $this->config->namespace . $key,
                    ]) > 0;
            } catch (Exception $e) {
                Logger::error($e->getMessage());
                return false;
            }
        }

        try {
            return $this->connection->insert($this->tableName, [
                    $this->config->keyCol => $this->config->namespace . $key,
                    $this->config->dataCol => $value,
                    $this->config->expiresTimeCol => $time + $lifeTime,
                    $this->config->lifetimeCol => $lifeTime,
                    $this->config->timeCol => $time,
                ]) > 0;
        } catch (Exception $e) {
            Logger::error($e->getMessage());
            return false;
        }
    }

    public function delete(string $key): bool
    {
        $this->check();
        try {
            return $this->connection->executeStatement('DELETE FROM ' . $this->tableName . ' WHERE '
                    . $this->config->keyCol . ' = ?', [
                    $this->config->namespace . $key,
                ], [
                    ParameterType::STRING
                ]) > 0;
        } catch (Exception $e) {
            Logger::error($e->getMessage());
            return false;
        }
    }

    public function clear(): bool
    {
        $this->check();
        if ($this->config->namespace === '') {
            try {
                return $this->connection->executeStatement('TRUNCATE TABLE ' . $this->tableName) > 0;
            } catch (Exception $e) {
                Logger::error($e->getMessage());
                return false;
            }
        }
        try {
            return $this->connection->executeStatement('DELETE FROM ' . $this->tableName . ' WHERE '
                . $this->config->keyCol . ' LIKE ?', [
                $this->config->namespace . '%',
            ], [
                ParameterType::STRING
            ]);
        } catch (Exception $e) {
            Logger::error($e->getMessage());
            return false;
        }
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $this->check();
        $realKeys = [];
        foreach ($keys as $key) {
            $realKeys[] = $this->config->namespace . $key;
        }
        print_r($realKeys);
        try {
            $data = $this->connection->fetchAllAssociativeIndexed(
                'SELECT ' . $this->config->keyCol . ',' . $this->config->dataCol . ' FROM ' . $this->tableName . ' WHERE (' .
                $this->config->expiresTimeCol . ' >= ? OR ' . $this->config->lifetimeCol . ' = 0) AND ' .
                $this->config->keyCol . ' IN (?)'
                , [
                time(),
                $realKeys,
            ], [
                ParameterType::INTEGER,
                Connection::PARAM_STR_ARRAY,
            ]);
        } catch (Exception $e) {
            Logger::error($e->getMessage());
            return array_fill_keys((array)$keys, $default);
        }
        $result = [];
        foreach ($keys as $key) {
            $k = $this->config->namespace . $key;
            if (isset($data[$k])) {
                $result[$key] = unserialize($data[$k][$this->config->dataCol], [
                    'allowed_classes' => true,
                ]);
            } else {
                $result[$key] = $default;
            }
        }
        return $result;
    }

    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $this->check();
        $realKeys = [];
        foreach ($keys as $key) {
            $realKeys[] = $this->config->namespace . $key;
        }
        try {
            return $this->connection->executeStatement('DELETE FROM ' . $this->tableName . ' WHERE '
                    . $this->config->keyCol . ' IN (?)', [
                    $realKeys
                ], [
                    Connection::PARAM_STR_ARRAY
                ]) > 0;
        } catch (Exception $e) {
            Logger::error($e->getMessage());
            return false;
        }
    }

    public function realHas($key): bool
    {
        $this->check();
        try {
            $count = $this->connection->executeQuery('SELECT COUNT(' . $this->config->keyCol . ') FROM ' .
                $this->tableName . ' WHERE ' . $this->config->keyCol . ' = ?', [
                $this->config->namespace . $key,
            ], [
                ParameterType::INTEGER,
            ])->fetchOne();
            return $count > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    public function has($key): bool
    {
        $this->check();
        try {
            $count = $this->connection->executeQuery('SELECT COUNT(' . $this->config->keyCol . ') FROM ' .
                $this->tableName . ' WHERE '
                . $this->config->expiresTimeCol . ' > ? and ' . $this->config->keyCol . ' = ?', [
                time(),
                $this->config->namespace . $key,
            ], [
                ParameterType::INTEGER,
                ParameterType::STRING
            ])->fetchOne();
            return $count > 0;
        } catch (Exception $e) {
            return false;
        }
    }
}