<?php


namespace QApi\Cache;


use DateInterval;
use QApi\Config\Cache\Chain;

class ChainAdapter implements CacheInterface
{

    /**
     * @var CacheInterface[]
     */
    private array $adapters = [];

    /**
     * ChainAdapter constructor.
     * @param Chain $config
     */
    public function __construct(Chain $config)
    {
        foreach ($config->adapters as $adapter) {
            $this->adapters[] = new $adapter->driver($adapter);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $default;
        foreach ($this->adapters as $adapter) {
            $value = $adapter->get($key, $default);
            if ($value !== $default) {
                return $value;
            }
        }
        return $value;
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $status = true;
        foreach ($this->adapters as $adapter) {
            if (!$adapter->set($key, $value, $ttl)) {
                if ($status === true) {
                    $status = false;
                }
            }
        }
        return $status;
    }

    public function delete(string $key): bool
    {
        $status = true;
        foreach ($this->adapters as $adapter) {
            if (!$adapter->delete($key)) {
                if ($status === true) {
                    $status = false;
                }
            }
        }
        return $status;
    }

    public function clear(): bool
    {
        $status = true;
        foreach ($this->adapters as $adapter) {
            if (!$adapter->clear()) {
                if ($status === true) {
                    $status = false;
                }
            }
        }
        return $status;
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
            $data[$key] = $default;
        }
        $keysNumber = count($keys);
        foreach ($this->adapters as $adapter) {
            $value = $adapter->getMultiple($keys, $default);
            if ($keysNumber === count($value) && in_array($default, $value, true)) {
                return $value;
            }
        }
        return $data;
    }

    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        $status = true;
        foreach ($this->adapters as $adapter) {
            if (!$adapter->setMultiple($values, $ttl)) {
                if ($status === true) {
                    $status = false;
                }
            }
        }
        return $status;
    }

    public function deleteMultiple(iterable $keys): bool
    {

        $status = true;
        foreach ($this->adapters as $adapter) {
            if (!$adapter->deleteMultiple($keys)) {
                if ($status === true) {
                    $status = false;
                }
            }
        }
        return $status;
    }

    public function has($key): bool
    {
        $status = false;
        foreach ($this->adapters as $adapter) {
            if ($adapter->has($key)) {
                return true;
            }
        }
        return $status;
    }
}