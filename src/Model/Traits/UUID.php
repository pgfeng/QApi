<?php


namespace QApi\Model\Traits;

use QApi\Data;
use QApi\Model;

/**
 * UUID
 * Trait AutoSave
 * @mixin Model
 * @package QApi\Model\Traits
 */
trait UUID
{
    protected ?string $uuidPrefix = null;

    /**
     * @param Data|array $data
     * @param string $primary_key
     * @return bool|int
     * @throws \Exception
     */
    public function save(Data|array $data, string $primary_key = ''): bool|int
    {
        if (!$primary_key) {
            $primary_key = $this->primary_key;
        }
        if (!isset($data[$primary_key])) {
            $data[$primary_key] = buildID($this->uuidPrefix?:$this->db->get_table());
            return $this->insert($data);
        }
        return $this->db->save($data);
    }
}