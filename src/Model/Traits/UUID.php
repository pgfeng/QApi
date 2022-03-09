<?php


namespace QApi\Model\Traits;

use QApi\Data;
use QApi\Model;

/**
 * UUID
 * Trait UUID
 * @mixin Model
 * @package QApi\Model\Traits
 */
trait UUID
{
    protected ?string $uuidPrefix = null;

    /**
     * @param Data|array $data
     * @param string|null $primary_key
     * @return int
     * @throws \Exception
     */
    public function save(Data|array $data, ?string $primary_key = null): int
    {
        if (!$primary_key) {
            $primary_key = $this->primary_key;
        }
        if (!isset($data[$primary_key]) || !$data[$primary_key]) {
            $data[$primary_key] = buildID($this->uuidPrefix ?: str_replace('_', '-', $this->getTableName()) . '-');
            return $this->insert($data);
        }
        return self::model()->where($primary_key, $data[$primary_key])->update($data);
    }
}