<?php

namespace QApi;

use ArrayIterator;
use ArrayObject;
use Iterator;
use JetBrains\PhpStorm\Internal\LanguageLevelTypeAware;
use JetBrains\PhpStorm\Pure;
use JsonSerializable as JsonSerializableAlias;

class Data extends ArrayObject implements JsonSerializableAlias
{


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
     * @param mixed $key
     * @return mixed
     */
    public function offsetGet(mixed $key): mixed
    {
        try {
            return parent::offsetGet($key);
        }catch (\Exception $e){
            return null;
        }
    }

    /**
     * 返回某列
     * @param string $column_key
     * @param string|null $index_key
     * @return array
     */
    public function column(string $column_key, string|null $index_key = null): array
    {
        $array = $this->getArrayCopy();
        foreach ($array as $key => $value) {
            if (in_array(ArrayObject::class, array_keys(class_parents($value)))) {
                $array[$key] = $value->getArrayCopy();
            } else if (!is_array($value)) {
                throw new \ErrorException('Wrong data format');
            }
        }
        return array_column($array, $column_key, $index_key);
    }

    /**
     * 合并
     * @param array $data
     * @return array
     */
    public function merge(array $data): array
    {
        return array_merge($this->getArrayCopy(), $data);
    }

    /**
     * 在结尾插入元素
     * @param mixed $data
     * @return void
     */
    public function push(mixed $data): void
    {
        $this[] = $data;
    }

    /**
     * 在开头插入元素
     * @param mixed $data
     * @return Data
     */
    public function unshift(mixed ...$data): Data
    {
        $array = $this->getArrayCopy();
        array_unshift($array, $data);
        return new Data($array);
    }

    /**
     * 弹出第一个元素
     * @return mixed
     */
    public function shift(): mixed
    {
        $array = $this->getArrayCopy();
        return array_shift($array);
    }

    /**
     * 弹出最后一个元素
     * @return mixed
     */
    public function pop(): mixed
    {
        $array = $this->getArrayCopy();
        return array_pop($array);
    }

    /**
     * 获取数据
     * @param string|bool|null $key
     * @param null $default_value
     * @return null|string|array
     */
    public function get(string|bool|null $key = false, $default_value = null): null|string|array
    {
        if (!$key) {
            return $this;
        }
        return $this[$key] ?? $default_value;
    }


    public function __get(string $name)
    {
        return $this[$name] ?? null;
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function set($key, $value): mixed
    {
        return $this[$key] = $value;
    }

    /**
     * @param $key
     * @return void
     */
    public function remove($key): void
    {
        if (isset($this[$key]))
            unset($this[$key]);
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key): bool
    {
        return isset($this[$key]);
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }


    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->getArrayCopy();
    }

    /**
     * @return string
     * @throws \JsonException
     */
    public function toJson(): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR | JSON_ERROR_NONE | JSON_OBJECT_AS_ARRAY | JSON_UNESCAPED_UNICODE);
    }


}