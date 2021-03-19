<?php

namespace QApi\Cache;

use DateInterval;

interface CacheInterface
{
    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     * @throws
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * @param string $key
     * @param mixed $value
     * @param null|int|DateInterval $ttl
     * @return bool
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool;

    /**
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool;

    /**
     * @return bool
     */
    public function clear(): bool;

    /**
     * @param iterable $keys
     * @param mixed|null $default
     * @return mixed
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable;

    /**
     * @param iterable $values
     * @param int|DateInterval|null $ttl
     * @return bool
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool;

    /**
     * @param iterable $keys
     * @return bool
     */
    public function deleteMultiple(iterable $keys): bool;

    /**
     * @param $key
     * @return bool
     */
    public function has($key): bool;
}