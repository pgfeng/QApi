<?php

namespace QApi\Cache;

use DateInterval;

class ApcuAdapter implements CacheInterface
{

    public function get(string $key, mixed $default = null): mixed
    {
        // TODO: Implement get() method.
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        // TODO: Implement set() method.
    }

    public function delete(string $key): bool
    {
        // TODO: Implement delete() method.
    }

    public function clear(): bool
    {
        // TODO: Implement clear() method.
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        // TODO: Implement getMultiple() method.
    }

    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        // TODO: Implement setMultiple() method.
    }

    public function deleteMultiple(iterable $keys): bool
    {
        // TODO: Implement deleteMultiple() method.
    }

    public function has($key): bool
    {
        // TODO: Implement has() method.
    }
}