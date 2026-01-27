<?php
declare(strict_types=1);

namespace QApi\Cache;



use DateInterval;
use DateTime;
use QApi\Config\Cache\RedisNative;

/**
 * Class RedisAdapter
 * @package QApi\Cache
 */
class RedisNativeAdapter implements CacheInterface
{
    /** @var Redis */
    protected \Redis $client;

    /**
     * RedisAdapter constructor.
     * @param RedisNative $config
     */
    public function __construct(RedisNative $config)
    {
        $this->config = $config;
        // 判断 Redis 扩展是否存在
        if (!extension_loaded('redis') && !class_exists(\Redis::class)) {
            throw new \RuntimeException('The Redis extension is not loaded.');
        }
        $this->client = new \Redis();

        // 连接
        $connected = $this->client->pconnect(
            $config->host,
            $config->port,
            $config->timeout ?? 0.0
        );

        if (!$connected) {
            throw new \RuntimeException(
                sprintf('Unable to connect to Redis @ %s:%s', $config->host, $config->port)
            );
        }

        // 认证
        if ($config->username !== '' || $config->password !== '') {
            if (!$this->client->auth([$config->username, $config->password])) {
                throw new \RuntimeException('Redis authentication failed');
            }
        }

        // 选库
        $this->client->select($config->database);

        // 前缀
        if ($config->prefix !== '') {
            $this->client->setOption(\Redis::OPT_PREFIX, $config->prefix);
        }
    }


    /* ------------------------------------------------------------------ */
    /* ---------------------------  基本 API  --------------------------- */
    /* ------------------------------------------------------------------ */

    public function get(string $key, mixed $default = null): mixed
    {
        $data = $this->client->get($key);
        if ($data === false) { // false == key not found
            return $default;
        }

        return unserialize($data, ['allowed_classes' => true]);
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        // 计算 TTL（秒）
        $seconds = $ttl === null ? 0 : $this->resolveTtl($ttl);

        // 序列化
        $payload = serialize($value);

        // 立即设置
        $ok = $seconds > 0
            ? $this->client->setex($key, $seconds, $payload)
            : $this->client->set($key, $payload);

        return (bool)$ok;
    }

    public function delete(string $key): bool
    {
        return (bool)$this->client->del($key);
    }

    public function has($key): bool
    {
        return (bool)$this->client->exists($key);
    }

    /* ------------------------------------------------------------------ */
    /* ---------------------------  批量 API  --------------------------- */
    /* ------------------------------------------------------------------ */

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $keysArr = (array)$keys;
        $data   = $this->client->mGet($keysArr);

        foreach ($data as $k => &$v) {
            if ($v === false) {
                $v = $default;
            } else {
                $v = unserialize($v, ['allowed_classes' => true]);
            }
        }

        return $data;
    }

    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        $seconds = $ttl === null ? 0 : $this->resolveTtl($ttl);

        $pipeline = $this->client->multi(\Redis::PIPELINE);
        foreach ($values as $k => $v) {
            $payload = serialize($v);
            $pipeline->set($k, $payload);
            if ($seconds > 0) {
                $pipeline->expire($k, $seconds);
            }
        }
        $results = $pipeline->exec();

        // 当 pipeline 有任一失败时返回 false
        foreach ($results as $res) {
            if ($res === false) {
                return false;
            }
        }
        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        return (bool)$this->client->del((array)$keys);
    }

    /* ------------------------------------------------------------------ */
    /* ---------------------------  其它 API  --------------------------- */
    /* ------------------------------------------------------------------ */

    public function clear(): bool
    {
        // 仅清除当前前缀下的 keys
        $keys = $this->client->keys('*');
        if (empty($keys)) {
            return true;
        }

        // 去掉内置前缀
        $prefix = $this->config->prefix ?: '';
        if ($prefix !== '') {
            // 在 mget 之类的 API 已经自动加前缀，删除时只需要传原 key
            $keys = array_map(fn($k) => substr($k, strlen($prefix)), $keys);
        }

        return $this->deleteMultiple($keys);
    }

    /* ------------------------------------------------------------------ */
    /* ---------------------------  工具方法  --------------------------- */
    /* ------------------------------------------------------------------ */

    /**
     * 解析 ttl 为秒
     *
     * @param DateInterval|int $ttl
     * @return int
     */
    protected function resolveTtl(DateInterval|int $ttl): int
    {
        return $ttl instanceof DateInterval
            ? (new DateTime())->add($ttl)->getTimestamp() - (new DateTime())->getTimestamp()
            : (int)$ttl;
    }
}