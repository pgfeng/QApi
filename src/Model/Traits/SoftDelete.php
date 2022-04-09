<?php


namespace QApi\Model\Traits;

use QApi\Model;
use QApi\Response;

/**
 * Trait SoftDelete
 * @mixin Model|\QApi\ORM\Model
 * @package QApi\Model\Traits
 */
trait SoftDelete
{
    /**
     * 删除字段
     * @var string
     */
    public string $softDeleteField = 'is_delete';

    /**
     * 已删除状态
     * @var int
     */
    public int $deleted = 1;

    /**
     * 未删除状态
     * @var int
     */
    public int $notDeleted = 0;

    /**
     * 删除数据 传入 false 为真删除
     * @param bool $softDelete
     * @return bool|int
     */
    public function softDelete(bool $softDelete = true): bool|int
    {
        if ($softDelete) {
            return $this->setField($this->softDeleteField, $this->deleted);
        } else {
            return $this->delete();
        }
    }

    public function deleteTrashed(): int
    {
        return $this->where($this->softDeleteField, $this->deleted)->delete();
    }

    /**
     * 只获取已删除数据
     * @return $this
     */
    public function queryTrashed(): self
    {
        return $this->where($this->softDeleteField, $this->deleted);
    }

    /**
     * 获取未删除数据
     * @return $this
     */
    public function queryNoTrashed(): self
    {
        return $this->where($this->softDeleteField, $this->notDeleted);
    }
}