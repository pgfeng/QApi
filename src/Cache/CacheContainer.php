<?php

namespace QApi\Cache;

use DateInterval;
use QApi\Logger;


class CacheContainer implements CacheInterface
{

    public function __construct(protected CacheInterface $cacheAdapter, protected string $configName, protected bool $disableLog = false)
    {

    }

    public function get(string $key, mixed $default = null): mixed
    {
        $result = $this->cacheAdapter->get($key, $default);
        if (!$this->disableLog) {
            Logger::cache($this->configName . '.GET' . ' => ' . $key);
        }
        return $result;
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $result = $this->cacheAdapter->set($key, $value, $ttl);
        if (!$this->disableLog) {
            Logger::cache($this->configName . '.SET => ' . $key);
        }
        return $result;
    }

    public function delete(string $key): bool
    {
        $result = $this->cacheAdapter->delete($key);
        if (!$this->disableLog) {
            Logger::cache($this->configName . '.DELETE => ' . $key);
        }
        return $result;
    }

    public function clear(): bool
    {
        $result = $this->cacheAdapter->clear();
        if (!$this->disableLog) {
            Logger::cache($this->configName . '.CLEAR');
        }
        return $result;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = $this->cacheAdapter->getMultiple($keys, $default);
        if (!$this->disableLog) {
            Logger::cache($this->configName . '.GET_MULTIPLE => ' . json_encode($keys, JSON_UNESCAPED_UNICODE));
        }
        return $result;
    }

    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        $result = $this->cacheAdapter->setMultiple($values, $ttl);

        if (!$this->disableLog) {
            Logger::cache($this->configName . '.SET_MULTIPLE => ' . json_encode(array_keys($values), JSON_UNESCAPED_UNICODE));
        }
        return $result;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $result = $this->cacheAdapter->deleteMultiple($keys);
        if (!$this->disableLog) {
            Logger::cache($this->configName . '.DELETE_MULTIPLE => ' . json_encode($keys, JSON_UNESCAPED_UNICODE));
        }
        return $result;
    }

    public function has($key): bool
    {
        $result = $this->cacheAdapter->has($key);
        if (!$this->disableLog) {
            Logger::cache($this->configName . '.HAS => ' . $key);
        }
        return $result;
    }
}