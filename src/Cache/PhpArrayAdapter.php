<?php


namespace QApi\Cache;


use DateInterval;
use DateTime;

class PhpArrayAdapter implements CacheInterface
{
    private array $values = [];

    private array $expires = [];

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->has($key)) {
            return $this->values[$key];
        }
        return $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param DateInterval|int|null $ttl
     * @return bool
     */
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $lifeTime = 0;
        if (is_int($ttl)) {
            $lifeTime = time() + $ttl;
        } else if ($ttl instanceof \DateInterval) {
            $lifeTime = (new DateTime())->add($ttl)->getTimestamp();
        }
        $this->values[$key] = $value;
        $this->expires[$key] = $lifeTime;
        return true;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        unset($this->values[$key], $this->expires[$key]);
        return true;
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        $this->values = [];
        $this->expires = [];
        return true;
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