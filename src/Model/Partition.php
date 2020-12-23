<?php
/**
 * Created by PhpStorm.
 * User: pgf
 * Date: 2019-03-06
 * Time: 12:14
 */

namespace GFPHP\Model;


use GFPHP\Exception;

/**
 * 分表模型 自动分表处理
 * Class Model
 * @package Model
 */
abstract class Partition extends \GFPHP\Model
{
    /**
     * @var string 分表字段
     */
    protected $partition_field = 'uid';
    /**
     * @var int 分表数量
     */
    protected $partition_num = 10;

    public function __construct($model = FALSE, $configName = 'default')
    {
        parent::__construct($model, $configName);
    }

    /**
     * 设置分表
     * @param $partition_value
     */
    private function set_table($partition_value)
    {
        $num = ((int)$partition_value % $this->partition_num) + 1;
        $table = $this->db->table . '_' . $num;
        $this->db->_set($this->db->config ['table_pre'] . $table, 'table');
    }

    /**
     * @return \GFPHP\Model
     * @throws Exception
     */
    public function where()
    {
        if (func_get_arg(0) === $this->partition_field) {
            if (func_num_args() !== 2) {
                throw new Exception('效验分表字段，where必须传入两个参数！');
            }
            if (func_num_args() === 3) {
                throw new Exception('效验分表字段，where只能传入两个参数！');
            }

            $this->set_table(func_get_arg(1));
        }
        call_user_func_array([$this->db, 'where'], func_get_args());
        return $this;
    }

    /**
     * 添加数据
     * @param array $insert
     * @return bool|int
     * @throws Exception
     */
    public function insert(array $insert)
    {
        if (!isset($insert[$this->partition_field])) {
            throw new Exception('必须传入 [ ' . $this->partition_field . ' ] 字段！', 0);
        }

        $this->set_table($insert[$this->partition_field]);
        return parent::insert($insert);
    }

    /**
     * 保存数据或者更新数据
     * 如果设置主键字段 $primary_key 将会判断此字段是否存在，如果存在则会为更新数据
     *
     * @param array $data
     * @param String $primary_key
     *
     * @return  Bool|int
     * @throws Exception
     */
    final public function save($data, $primary_key = '')
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