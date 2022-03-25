<?php

namespace QApi\Cache;

use DateInterval;
use JetBrains\PhpStorm\Pure;
use QApi\Config\Cache\SwooleTable;
use QApi\Exception\CacheErrorException;
use QApi\Logger;
use Swoole\Table;

class SwooleTableAdapter implements CacheInterface
{

    protected Table $table;

    /**
     * @param SwooleTable $swooleTableConfig
     * @throws CacheErrorException
     */
    public function __construct(protected SwooleTable $swooleTableConfig)
    {
        self::isSupported(true);
        $this->table = new Table($this->swooleTableConfig->rowsSize);
        $this->table->column('value', Table::TYPE_STRING, $this->swooleTableConfig->valueSize);
        $this->table->column('expires', Table::TYPE_INT, 11);
        $this->table->create();
    }

    /**
     * @throws CacheErrorException
     */
    public static function isSupported($throwError = false): bool
    {
        return !\class_exists('Swoole\\Table') ? throw new CacheErrorException('Swoole is not installed,Please run the installation command: `pecl install swoole`.') : true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $row = $this->table->get($key);
        if (!$row) {
            return $default;
        }
        return $row['expires'] === 0 || $row['expires'] >= time() ? unserialize($row['value'], [
            'allowed_classes' => true,
        ]) : $default;
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

        if ($ttl instanceof DateInterval) {
            return $ttl->days * 86400 + $ttl->h * 3600
                + $ttl->i * 60 + $ttl->s;
        }

        if (is_int($ttl)) {
            return $ttl;
        }
        return 0;
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $ttl = $this->getDateIntervalToSecond($ttl);
        try {
            $this->table->set($key, [
                'value' => serialize($value),
                'expires' => $ttl ? $ttl + time() : 0,
            ]);
        } catch (\Exception $e) {
            Logger::error($e->getMessage());
            return false;
        }
        return true;
    }

    public function delete(string $key): bool
    {
        try {

            $this->table->del($key);
        } catch (\Exception $e) {
            Logger::error($e->getMessage());
            return false;
        }
        return true;
    }

    public function clear(): bool
    {
        try {
            $this->table->destroy();
        } catch (\Exception $e) {
            Logger::error($e->getMessage());
            return false;
        }
        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $this->table->get($key, $default);
        }
        return $data;
    }

    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        $status = false;
        foreach ($values as $key => $value) {
            $status = $this->set($key, $value, $ttl);
        }
        return $status;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $status = false;
        foreach ($keys as $key) {
            $status = $this->delete($key);
        }
        return $status;
    }

    public function has($key): bool
    {
        return $this->table->exist($key);
    }
}