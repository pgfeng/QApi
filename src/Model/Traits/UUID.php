<?php


namespace QApi\Model\Traits;

use QApi\Data;
use QApi\Model;
use Symfony\Component\Uid\Ulid;

/**
 * UUID
 * Trait UUID
 * @mixin Model|\QApi\ORM\Model
 * @package QApi\Model\Traits
 */
trait UUID
{
    protected ?string $uuidPrefix = null;
    protected ?string $lastInsertUUID = null;

    /**
     * @param Data|array $data
     * @param string|null $primary_key
     * @return int
     * @throws \Exception
     */
    public function save(Data|array $data, ?string $primary_key = null, array $types = []): int
    {
        if (!$primary_key) {
            $primary_key = $this->primary_key;
        }
        if (!isset($data[$primary_key]) || !$data[$primary_key]) {
            $uuid = Ulid::generate();
            $data[$primary_key] = $this->lastInsertUUID = $uuid;
            return $this->insert($data);
        }
        return self::model()->where($primary_key, $data[$primary_key])->update($data);
    }
}