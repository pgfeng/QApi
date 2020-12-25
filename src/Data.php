<?php

namespace QApi;

use ArrayObject;
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
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    /**
     * @param int $offset
     *
     * @return mixed|null
     */
    public function offsetGet($offset): mixed
    {
        return $this->data[$offset] ?? NULL;
    }

    public function offsetSet($offset, $value): mixed
    {
        return $this->data[$offset] = $value;
    }

    /**
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
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
     */
    public function toJson(): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR | JSON_ERROR_NONE | JSON_OBJECT_AS_ARRAY | JSON_UNESCAPED_UNICODE);
    }


    /**
     * @return ArrayObject|Data
     */
    public function getIterator(): Data|ArrayObject
    {
        return new ArrayObject($this->data);
    }

}