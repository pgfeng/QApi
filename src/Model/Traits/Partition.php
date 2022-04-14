<?php

namespace QApi\Model\Traits;


use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use JetBrains\PhpStorm\Pure;
use QApi\Data;
use QApi\Exception\SqlErrorException;
use QApi\ORM\DB;
use QApi\ORM\Model;

/**
 * 分表辅助
 * Trait AutoSave
 * @mixin Model
 * @package QApi\Model\Traits
 */
trait Partition
{
    /**
     * @var string 分表字段
     */
    protected string $partition_field = 'uid';

    /**
     * @var int 分表数量
     */
    protected int $partition_num = 10;

    /**
     * 分隔符
     * @var string
     */
    protected string $separationString = '_';


    /**
     * 根据分区字段表值获取所在表
     * @param $partition_value
     * @return string
     */
    final public function getTable($partition_value): string
    {
        return $this->getTableName(false) . $this->separationString . (((int)$partition_value % $this->partition_num) + 1);
    }

    /**
     * @param int $partition_value
     * @return $this
     */
    public function setPartitionValue(int $partition_value): self
    {
        $this->from($this->getTable($partition_value));
        return $this;
    }
}