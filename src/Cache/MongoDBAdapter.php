<?php


namespace QApi\Cache;


use DateInterval;
use DateTime;
use FilesystemIterator;
use Iterator;
use JetBrains\PhpStorm\Pure;
use QApi\Config\Cache\FileSystem;
use QApi\Config\Cache\Mongo;
use QApi\Exception\CacheErrorException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class MongoDBAdapter implements CacheInterface
{
    protected \QApi\ODM\Mongo $Mongo;

    private int $cleanUpTime = 0;
    private int $connectionTime = 0;

    public function __construct(protected Mongo $config)
    {
        $this->check();
    }

    public function checkConnect(int $time)
    {
        if ($time - $this->connectionTime > $this->config->config->wait_timeout) {
            print_r('链接');
            $this->Mongo = new \QApi\ODM\Mongo($this->config->config);
            $this->connectionTime = $time;
        }
    }

    /**
     * 定时清理
     * @param int $time
     */
    public function checkCleanUp(int $time)
    {
        if ($time - $this->cleanUpTime > $this->config->cleanUpTime) {
            print_r('清理');
            $this->Mongo->delete($this->config->table, [
                $this->config->lifetimeCol => [
                    '$gt' => 0,
                ],
                $this->config->expiresTimeCol => [
                    '$gt' => $time,
                ]
            ]);
            $this->cleanUpTime = $time;
        }
    }

    public function check(): void
    {
        $time = time();
        $this->checkConnect($time);
        $this->checkCleanUp($time);
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
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->check();
        $data = $this->Mongo->find($this->config->table, [
            '$or' => [
                [
                    $this->config->keyCol => $this->config->namespace . $key,
                    $this->config->expiresTimeCol => [
                        '$gte' => time(),
                    ],
                    $this->config->lifetimeCol => [
                        '$gt' => 0,
                    ]
                ],
                [
                    $this->config->keyCol => $this->config->namespace . $key,
                    $this->config->lifetimeCol => 0
                ],
            ]
        ]);
        if ($data) {
            return unserialize($data[$this->config->dataCol], [
                'allowed_classes' => true,
            ]);
        } else {
            return $default;
        }

    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->check();
        $time = time();
        $lifeTime = $this->getDateIntervalToSecond($ttl);
        $data = [
            $this->config->keyCol => $this->config->namespace . $key,
            $this->config->dataCol => serialize($value),
            $this->config->expiresTimeCol => $lifeTime > 0 ? $time + $lifeTime : 0,
            $this->config->timeCol => $time,
            $this->config->lifetimeCol => $lifeTime,
        ];
        if ($this->has($key)) {
            return $this->Mongo->update($this->config->table, [
                    $this->config->keyCol => $this->config->namespace . $key,
                ], $data) > 0;
        } else {
            return $this->Mongo->insert($this->config->table, $data) > 0;
        }
    }

    public function delete(string $key): bool
    {
        $this->check();
        return $this->Mongo->delete($this->config->table, [
                $this->config->keyCol => $this->config->namespace . $key,
            ]) > 0;
    }

    public function clear(): bool
    {
        $this->check();
        return $this->Mongo->delete($this->config->table, [
                $this->config->keyCol => [
                    '$regex' => '^' . $this->config->namespace . '.*',
                ]
            ]) > 0;
    }

    /**
     * @param array $data
     * @return array
     */
    public function fetchAllAssociativeIndexed(array $data): array
    {
        $tempData = [];
        foreach ($data as $item) {
            $tempData[substr($item[$this->config->keyCol], strlen($this->config->namespace))] =
                $item[$this->config->dataCol];
        }
        return $tempData;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $this->check();
        $tempKeys = $keys;
        foreach ($keys as &$key) {
            $key = $this->config->namespace . $key;
        }
        unset($key);
        $data = $this->Mongo->query($this->config->table, [
            '$or' => [
                [
                    $this->config->keyCol => [
                        '$in' => $keys,
                    ],
                    $this->config->expiresTimeCol => [
                        '$gte' => time(),
                    ],
                    $this->config->lifetimeCol => [
                        '$gt' => 0,
                    ]
                ],
                [
                    $this->config->keyCol => [
                        '$in' => $keys,
                    ],
                    $this->config->lifetimeCol => 0
                ],
            ]
        ]);
        $data = $this->fetchAllAssociativeIndexed($data);
        $tempData = [];
        foreach ($tempKeys as $key) {
            if (isset($data[$key])) {
                $tempData[$key] = unserialize($data[$key], [
                    'allowed_classes' => true,
                ]);
            } else {
                $tempData[$key] = $default;
            }
        }
        return $tempData;
    }

    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as &$key) {
            $key = $this->config->namespace . $key;
        }
        return $this->Mongo->delete($this->config->table, [
                $this->config->keyCol => [
                    '$in' => $keys,
                ]
            ]) > 0;
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key): bool
    {
        $this->check();
        if ($this->Mongo->find($this->config->table, [
            $this->config->keyCol => $this->config->namespace . $key
        ])) {
            return true;
        } else {
            return false;
        }
    }
}