<?php

namespace QApi;

use ArrayObject;
use JsonSerializable as JsonSerializableAlias;
use QApi\Exception\UserErrorException;

class Data extends ArrayObject implements JsonSerializableAlias
{
    private Model|ORM\Model|null|string $model = null;

    private array $modifyKeys = [];

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

    public function offsetSet(mixed $key, mixed $value): void
    {
        if (!in_array($key, $this->modifyKeys)) {
            $this->modifyKeys[] = $key;
        }
        parent::offsetSet($key, $value);
    }

    /**
     * @param string $keyField
     * @param string $parentField
     * @param string $childField
     * @param mixed $parent
     * @return array
     */
    public function &toTree(string $keyField, string $parentField, string $childField = 'children', mixed $parent =
    false, array|false             &$copyData = false, $treeLevel = 1):
    array
    {
        if ($copyData === false) {
            $copyData = $this->toArray();
        }
        $data = [];
        foreach ($copyData as $index => $item) {
            if ($parent === false || $item[$parentField] == $parent) {
                if ((string)$item[$keyField] === (string)$item[$parentField]) {
                    $item['tree_level'] = $treeLevel;
                    $data[] = $item;
                    array_splice($copyData, $index, 1);
                    continue;
                }
                $item['tree_level'] = $treeLevel;
                $item[$childField] = $this->toTree($keyField, $parentField, $childField, $item[$keyField], $copyData, $treeLevel + 1);
                if (!$item[$childField]) {
                    unset($item[$childField]);
                }
                if ($parent === false) {
                    if (!$item[$parentField]) {
                        $data[] = $item;
                    }
                } else {
                    $data[] = $item;
                }
            }
        }
        return $data;
    }

    /**
     * @param mixed $key
     * @return mixed
     */
    public function offsetGet(mixed $key): mixed
    {
        return parent::offsetGet($key);
    }

    /**
     * 返回某列
     * @param string $column_key
     * @param string|null $index_key
     * @return array
     */
    public function column(string $column_key, string|null $index_key = null): array
    {
        return array_column($this->getArrayCopy(), $column_key, $index_key);
    }

    /**
     * 合并
     * @param array $data
     * @return array
     */
    public function merge(array ...$data): array
    {
        $array = $this->getArrayCopy();
        foreach ($data as $item) {
            $array = array_merge($array, $item);
        }
        return $array;
    }

    /**
     * 在结尾插入元素
     * @param mixed $data
     * @return void
     */
    public function push(mixed ...$data): void
    {
        $array = $this->getArrayCopy();
        array_push($array, ...$data);
        $this->exchangeArray($array);
    }

    /**
     * 在开头插入元素
     * @param mixed $data
     * @return Data
     */
    public function unshift(mixed ...$data): Data
    {
        $array = $this->getArrayCopy();
        array_unshift($array, ...$data);
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
        if ($key === null || $key === false) {
            return $this->getArrayCopy();
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
    public function remove(...$key): void
    {
        foreach ($key as $k) {
            unset($this[$k]);
        }
    }

    /**
     * @param iterable $keys
     * @return void
     */
    public function batchRemove(iterable $keys): void
    {
        foreach ($keys as $key) {
            unset($this[$key]);
        }
    }

    /**
     * @param $key
     * @return bool
     */
    public function has(...$key): bool
    {
        foreach ($key as $k) {
            if (!isset($this[$k])) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);
        if (is_string($this->model)) {
            $this->model = new $this->model;
        }
    }

    /**
     * @return array
     */
    public function __serialize(): array
    {
        if ($this->model) {
            $this->model = get_class($this->model);
        }
        return parent::__serialize();
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

    /**
     * @return Model|ORM\Model
     */
    public function getModel(): ORM\Model|Model
    {
        return $this->model;
    }

    /**
     * @param Model|ORM\Model $model
     */
    public function setModel(ORM\Model|Model &$model): void
    {
        $this->model = $model;
    }

    /**
     * @param Data|array $data
     * @return int
     */
    public function save(Data|array $data = [], ?string $primary_key = null, array $types = []): int
    {
        if (!$this->model) {
            throw new UserErrorException('The model object needs to be set,Data->setModel().');
        }
        if (!$primary_key) {
            if (!$this->model->primary_key) {
                throw new UserErrorException('The model object has no primary key set.');
            }
            $primary_key = $this->model->primary_key;
        }
        if (!count($data)) {
            if (!count($this->modifyKeys)) {
                return 1;
            }
            foreach ($this->modifyKeys as $key) {
                $data[$key] = $this[$key];
            }
        }
        if ($this->has($primary_key)) {
            $data[$primary_key] = $this->get($primary_key);
        }
        return $this->model->save($data, $primary_key, $types);
    }
}