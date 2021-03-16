<?php
/**
 * Created by PhpStorm.
 * User: pgf
 * Date: 2019-03-06
 * Time: 12:14
 */

namespace QApi\Model;



use Exception;
use QApi\Data;
use QApi\Exception\SqlErrorException;

/**
 * 分表模型 自动分表处理
 * Class Model
 * @package Model
 */
abstract class Partition extends \QApi\Model
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
     * 设置分表
     * @param $partition_value
     */
    private function set_table($partition_value): void
    {
        $num = ((int)$partition_value % $this->partition_num) + 1;
        $table = $this->db->table . '_' . $num;
        $this->db->_set($this->db->config ['table_pre'] . $table, 'table');
    }

    /**
     * @return self
     * @throws Exception
     */
    public function where(): self
    {
        if (func_get_arg(0) === $this->partition_field) {
            if (func_num_args() !== 2) {
                throw new SqlErrorException('效验分表字段，where必须传入两个参数！');
            }
            if (func_num_args() === 3) {
                throw new SqlErrorException('效验分表字段，where只能传入两个参数！');
            }

            $this->set_table(func_get_arg(1));
        }
        call_user_func_array([$this->db, 'where'], func_get_args());
        return $this;
    }

    /**
     * 添加数据
     * @param array|Data $insert
     * @return bool|int
     * @throws SqlErrorException
     */
    public function insert(array|Data $insert): bool|int
    {
        if (!isset($insert[$this->partition_field])) {
            throw new SqlErrorException('必须传入 [ ' . $this->partition_field . ' ] 字段！', 0);
        }

        $this->set_table($insert[$this->partition_field]);
        return parent::insert($insert);
    }

    /**
     * 保存数据或者更新数据
     * 如果设置主键字段 $primary_key 将会判断此字段是否存在，如果存在则会为更新数据
     *
     * @param array|Data $data
     * @param String $primary_key
     *
     * @return  Bool|int
     */
    final public function save(array|Data $data, $primary_key = ''):bool|int
    {
        if (!is_array($data)) {
            return FALSE;
        }
        if ($primary_key && isset($data[$primary_key]) && $data[$primary_key]) {
            $primary_value = $data[$primary_key];
            unset($data[$primary_key]);
            return $this->where($primary_key, $primary_value)->update($data);
        }

        return $this->insert($data);
    }

}