<?php


namespace QApi\Cache;


use DateInterval;
use DateTime;

class PhpArrayCache implements CacheInterface
{
    private array $values = [];

    private array $expires = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->has($key)) {
            return $this->values[$key];
        }
        return $default;
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $lifeTime = 0;
        if (is_int($ttl)) {
            $lifeTime = time() + $ttl;
        } else if ($ttl === null) {
            $lifeTime = 0;
        } else if ($ttl instanceof \DateInterval) {
            $lifeTime = (new DateTime())->add($ttl)->getTimestamp();
        }
        $this->values[$key] = $value;
        $this->expires[$key] = $lifeTime;
        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->values[$key], $this->expires[$key]);
        return true;
    }

    public function clear(): bool
    {
        $this->values = [];
        $this->expires = [];
        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $this->get($key, $default);
        }
        return $data;
    }

    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                return false;
            }
        }
        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                return false;
            }
        }
        return true;
    }

    public function has($key): bool
    {
        if (isset($this->expires[$key])) {
            if ($this->expires[$key] > 0) {
                if ($this->expires[$key] > time()) {

                    return true;
                }
                unset($this->expires[$key], $this->values[$key]);
                return false;
            }
            return true;
        }

        return false;
    }
}