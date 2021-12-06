<?php

namespace QApi;

use ArrayObject;
use Iterator;
use JetBrains\PhpStorm\Pure;
use JsonSerializable as JsonSerializableAlias;

class Data extends ArrayObject implements JsonSerializableAlias
{
    /**
     * @param $data
     */
    public function __construct(protected array &$data)
    {
        parent::__construct($data);
    }

    /**
     * 将数据转为primary_key为键名并返回
     *
     * @param $primary_key
     *
     * @return Data
     */
    public function transPrimaryIndex($primary_key): Data
    {
        $newData = [];
        $data = $this->toArray();
        if ($data) {
            foreach ($data as $item) {
                $newData[$item[$primary_key]] = $item;
            }
        }
        return new Data($newData);
    }

    /**
     * 返回某列
     * @param string $column_key
     * @param string|null $index_key
     * @return array
     */
    #[Pure] public function column(string $column_key, string|null $index_key = null): array
    {
        return array_column($this->data, $column_key, $index_key);
    }

    /**
     * 合并
     * @param array $data
     * @return array
     */
    #[Pure] public function merge(array $data): array
    {
        return array_merge($this->data, $data);
    }

    /**
     * 在结尾插入元素
     * @param mixed $data
     * @return void
     */
    public function push(mixed $data): void
    {
        $this->data[] = $data;
    }

    /**
     * 在开头插入元素
     * @param mixed $data
     * @return void
     */
    public function unshift(mixed $data): void
    {
        array_unshift($this->data, $data);
    }

    /**
     * 弹出第一个元素
     * @return mixed
     */
    public function shift(): mixed
    {
        return array_shift($this->data);
    }

    /**
     * 弹出最后一个元素
     * @return mixed
     */
    public function pop(): mixed
    {
        return array_pop($this->data);
    }

    /**
     * 获取数据
     * @param $key
     * @param null $default_value
     * @return null|string|array
     */
    public function get($key = false, $default_value = null): null|string|array
    {
        if (!$key) {
            return $this->data;
        }
        return $this->data[$key] ?? $default_value;
    }


    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function set($key, $value): mixed
    {
        return $this->data[$key] = $value;
    }

    /**
     * @param $key
     * @return void
     */
    public function remove($key): void
    {
        unset($this->data[$key]);
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->data;
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function offsetExists($key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    public function offsetGet($key): mixed
    {
        return $this->data[$key] ?? NULL;
    }

    public function offsetSet($key, $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * @param mixed $key
     * @return void
     */
    public function offsetUnset(mixed $key): void
    {
        unset($this->data[$key]);
    }

    /**
     * @param $name
     * @return array|string
     */
    public function __get(string $name): string|array
    {
        return $this->data[$name];
    }

    /**
     * @param $key
     * @param $value
     */
    public function __set(string $key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

    /**
     * @param $name
     */
    public function __unset($name)
    {
        unset($this->data[$name]);
    }

    /**
     * @return mixed
     */
    #[Pure] public function count(): int
    {
        return count($this->data);
    }


    /**
     * @return Data|null
     */
    public function current(): ?Data
    {
        $data = current($this->data);
        if (!empty($data)) {
            return new Data($data);
        }

        return NULL;
    }

    /**
     * @return string
     */
    #[Pure] public function key(): string
    {
        return key($this->data);
    }

    /**
     * @return mixed
     */
    public function next(): mixed
    {
        return next($this->data);
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return $this->current() !== NULL;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * @return string
     * @throws \JsonException
     */
    public function toJson(): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR | JSON_ERROR_NONE | JSON_OBJECT_AS_ARRAY | JSON_UNESCAPED_UNICODE);
    }


    /**
     * @return ArrayObject
     */
    public function getIterator(): ArrayObject
    {
        return new ArrayObject($this->data);
    }

}