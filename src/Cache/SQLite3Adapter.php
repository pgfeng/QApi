<?php


namespace QApi\Cache;


use DateInterval;
use DateTime;
use QApi\Config\Cache\SQLite;
use SQLite3;
use SQLite3Result;

/**
 * Class SQLite3Cache
 * @package QApi\Cache
 */
class SQLite3Adapter implements CacheInterface
{
    /**
     * @var SQLite3
     */
    private SQLite3 $sqlite;

    /**
     * @var string
     */
    private string $table;

    /**
     * The ID field will store the cache key.
     */
    public const ID_FIELD = 'k';

    /**
     * The data field will store the serialized PHP value.
     */
    public const DATA_FIELD = 'd';

    /**
     * The expiration field will store a date value indicating when the
     * cache entry should expire.
     */
    public const EXPIRATION_FIELD = 'e';

    /**
     * SQLite3Cache constructor.
     * @param SQLite $config
     */
    public function __construct(SQLite $config)
    {
        $this->sqlite = new SQLite3($config->dbFilename);
        $this->table = $config->table;
        $this->sqlite->exec(
            sprintf(
                'CREATE TABLE IF NOT EXISTS %s(%s TEXT PRIMARY KEY NOT NULL, %s BLOB, %s INTEGER)',
                $this->table,
                static::ID_FIELD,
                static::DATA_FIELD,
                static::EXPIRATION_FIELD
            )
        );
    }

    /**
     * @return array
     */
    private function getFields(): array
    {
        return [static::ID_FIELD, static::DATA_FIELD, static::EXPIRATION_FIELD];
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $item = $this->findById($key);

        if (!$item) {
            return false;
        }

        return unserialize($item[self::DATA_FIELD], [
            'allowed_classes' => true,
        ]);
    }

    /**
     * @param $id
     * @param bool $includeData
     * @return array|null
     */
    private function findById($id, bool $includeData = true): ?array
    {
        [$idField] = $fields = $this->getFields();

        if (!$includeData) {
            $key = array_search(static::DATA_FIELD, $fields);
            unset($fields[$key]);
        }

        $statement = $this->sqlite->prepare(sprintf(
            'SELECT %s FROM %s WHERE %s = :id LIMIT 1',
            implode(',', $fields),
            $this->table,
            $idField
        ));
        $statement->bindValue(':id', $id, SQLITE3_TEXT);
        $item = $statement->execute()->fetchArray(SQLITE3_ASSOC);
        if ($item === false) {
            return null;
        }
        if ($this->isExpired($item)) {
            $this->delete($id);

            return null;
        }

        return $item;
    }

    /**
     * @param array $item
     * @return bool
     */
    private function isExpired(array $item): bool
    {
        return isset($item[static::EXPIRATION_FIELD]) &&
            $item[self::EXPIRATION_FIELD] !== null &&
            $item[self::EXPIRATION_FIELD] < time();
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param DateInterval|int|null $ttl
     * @return bool
     */
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $statement = $this->sqlite->prepare(sprintf(
            'INSERT OR REPLACE INTO %s (%s) VALUES (:id, :data, :expire)',
            $this->table,
            implode(',', $this->getFields())
        ));
        $statement->bindValue(':id', $key);
        $statement->bindValue(':data', serialize($value), SQLITE3_BLOB);
        if (is_int($ttl)) {
            $lifeTime = time() + $ttl;
        } else if ($ttl === null) {
            $lifeTime = 0;
        } else if ($ttl instanceof \DateInterval) {
            $lifeTime = (new DateTime())->add($ttl)->getTimestamp();
        }
        $statement->bindValue(':expire', $lifeTime > 0 ? $lifeTime : null);

        return $statement->execute() instanceof SQLite3Result;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        [$idField] = $this->getFields();

        $statement = $this->sqlite->prepare(sprintf(
            'DELETE FROM %s WHERE %s = :id',
            $this->table,
            $idField
        ));

        $statement->bindValue(':id', $key);

        return $statement->execute() instanceof SQLite3Result;
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        return $this->sqlite->exec(sprintf('DELETE FROM %s', $this->table));
    }

    /**
     * @param iterable $keys
     * @param mixed|null $default
     * @return iterable
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $this->get($key, $default);
        }
        return $data;
    }

    /**
     * @param iterable $values
     * @param DateInterval|int|null $ttl
     * @return bool
     */
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param iterable $keys
     * @return bool
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key): bool
    {
        return $this->findById($key, false) !== null;
    }
}