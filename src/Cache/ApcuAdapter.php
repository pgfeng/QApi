<?php

namespace QApi\Cache;

use DateInterval;
use JetBrains\PhpStorm\Pure;
use QApi\Config\Cache\Apcu;
use QApi\Exception\CacheErrorException;

/**
 * ApcuAdapter
 */
class ApcuAdapter implements CacheInterface
{

    /**
     * @param Apcu $config
     * @throws CacheErrorException
     */
    public function __construct(
        public Apcu $config
    )
    {
        static::isSupported(true);
        ini_set('apc.use_request_time', 0);
    }

    /**
     * @throws CacheErrorException
     */
    public static function isSupported($throwError = false): bool
    {
        if (\function_exists('apcu_fetch')) {
            if (\PHP_SAPI === 'cli') {
                if (!filter_var(ini_get('apc.enable_cli'), \FILTER_VALIDATE_BOOLEAN)) {
                    if ($throwError) {
                        throw new CacheErrorException('APCu is not enabled on cli,Please check php.ini[apc.enable_cli]');
                    }
                    return false;
                }
            } else {
                if (!filter_var(ini_get('apc.enabled'), \FILTER_VALIDATE_BOOLEAN)) {
                    if ($throwError) {
                        throw new CacheErrorException('APCu is not enabled,Please check php.ini[apc.enabled]');
                    }
                    return false;
                }
            }
        } else {
            if ($throwError) {
                throw new CacheErrorException('APCu is not installed,Please run the installation command: `pecl install apcu`.');

            }
            return false;
        }
        return false;
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $data = apcu_fetch($this->config->namespace . $key, $success);
        if (!$success) {
            return $default;
        } else {
            return $data;
        }
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

    /**
     * @param string $key
     * @param mixed $value
     * @param DateInterval|int|null $ttl
     * @return bool
     */
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $lifeTime = $this->getDateIntervalToSecond($ttl);
        return apcu_store($this->config->namespace . $key, $value, $lifeTime);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        return apcu_delete($this->config->namespace . $key);
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        return apcu_delete(new \APCuIterator(sprintf('/^%s/', preg_quote($this->config->namespace, '/'))));
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
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    /**
     * @param iterable $keys
     * @return bool
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as &$key) {
            $key = $this->config->namespace . $key;
        }
        apcu_delete($keys);
        return true;
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key): bool
    {
        return apcu_exists($this->config->namespace . $key);
    }
}