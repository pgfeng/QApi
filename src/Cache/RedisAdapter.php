<?php


namespace QApi\Cache;


use DateInterval;
use DateTime;
use Predis\Client;
use QApi\Config\Cache\Redis;


/**
 * Class RedisCache
 * @package QApi\Cache
 */
class RedisAdapter implements CacheInterface
{
    protected Client $client;
    protected Redis $config;

    /**
     * RedisCache constructor.
     * @param Redis $config
     */
    public function __construct(Redis $config)
    {
        $this->config = $config;
        $this->client = new Client([
            'scheme' => $config->scheme,
            'host' => $config->host,
            'port' => $config->port,
            'username' => $config->username,
            'password' => $config->password,
            'database' => $config->database,
        ], [
            'prefix' => $config->prefix,
        ]);
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $data = $this->client->get($key);
        if ($data) {
            return unserialize($data, [
                'allowed_classes' => true,
            ]);
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
        if ($ttl === null) {
            $seconds = -1;
        } else if ($ttl instanceof \DateInterval) {
            $dateTime = new DateTime();
            $seconds = $dateTime->add($ttl)->getTimestamp() - $dateTime->getTimestamp();
        } else {
            $seconds = $ttl;
        }
        $this->client->set($key, serialize($value));
        if ($seconds > -1) {
            $this->client->expire($key, $seconds);
        }
        return true;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        return (bool)$this->client->del($key);
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        $keys = $this->client->keys('*');
        if (count($keys) > 0) {
            $keys = preg_replace('/' . preg_quote($this->config->prefix, null) . '(.*)' . '/', "$1", $keys, 1);
            return $this->deleteMultiple($keys);
        }
        return true;
    }

    /**
     * @param iterable $keys
     * @param mixed|null $default
     * @return iterable
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $data = $this->client->mget((array)$keys);
        foreach ($data as $k => &$v) {
            if ($v) {
                $v = unserialize($v, [
                    'allowed_classes' => true,
                ]);
            } else {
                $v = $default;
            }
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
        foreach ($values as $k => $v) {
            $this->set($k, $v,$ttl);
        }
        return true;
    }

    /**
     * @param iterable $keys
     * @return bool
     */
    public function deleteMultiple(iterable $keys): bool
    {
        return (bool)$this->client->del($keys);
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key): bool
    {
        return (bool)$this->client->exists($key);
    }
}