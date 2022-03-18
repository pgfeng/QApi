<?php

namespace QApi\ODM;

use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
use QApi\Config\Database\MongoDatabase;
use QApi\Data;

class Mongo
{
    public Manager $manager;

    public function __construct(protected MongoDatabase $config)
    {
        $this->manager = new Manager('mongodb://' . ($config->user ? $config->user . ':' . $config->password . '@' : '') .
            $config->host . ':' .
            $config->port . '/' .
            $config->dbName);
    }


    /**
     * @param string $collection
     * @param array $where
     * @return array|null
     */
    public function find(string $collection, array $where = []): array|null
    {
        $data = $this->manager->executeQuery($this->config->dbName . '.' . $collection, new Query($where, [
            'limit' => 1,
        ]))->toArray();
        if (count($data)) {
            return json_decode(json_encode($data[0]), true);
        } else {
            return null;
        }
    }

    public function query(string $collection, array $where = [], $options = []): array
    {
        $data = $this->manager->executeQuery($this->config->dbName . '.' . $collection, new Query($where, $options))
            ->toArray();
        foreach ($data as &$item) {
            $item = json_decode(json_encode($item), true);
        }
        return $data;
    }

    /**
     * @param string $collection
     * @param array|Data $data
     * @return int
     */
    public function insert(string $collection, array|Data $data): int
    {
        if ($data instanceof Data) {
            $data = $data->toArray();
        }
        $bulk = new BulkWrite();
        $bulk->insert($data);
        return $this->manager->executeBulkWrite($this->config->dbName . '.' . $collection, $bulk)->getInsertedCount();
    }

    /**
     * @param string $collection
     * @param array $where
     * @param array|Data $data
     * @return int
     */
    public function update(string $collection, array $where, array|Data $data): int
    {

        if ($data instanceof Data) {
            $data = $data->toArray();
        }
        $bulk = new BulkWrite();
        $bulk->update($where, $data);
        return $this->manager->executeBulkWrite($this->config->dbName . '.' . $collection, $bulk)->getUpsertedCount();
    }

    public function delete(string $collection, array $where = []): int
    {
        $bulk = new BulkWrite();
        $bulk->delete($where);
        return $this->manager->executeBulkWrite($this->config->dbName . '.' . $collection, $bulk)->getDeletedCount();
    }

}