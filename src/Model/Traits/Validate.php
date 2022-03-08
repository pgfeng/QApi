<?php

namespace QApi\Model\Traits;

use QApi\Logger;
use QApi\Model\filesModel;

/**
 * 字段验证
 */
trait Validate
{
    protected array $Column = [];
    /**
     * 验证规则
     *
     * @var array
     */
    public array $validate = [
        #不能为空
        'require' => [
            'rule' => '/.+/',
            'msg' => '%ColumnName%不准为空',
        ],
        #邮箱
        'email' => [
            'rule' => '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
            'msg' => '%ColumnName%不是正确的邮箱地址',
        ],
        #网址
        'url' => [
            'rule' => '/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/',
            'msg' => '您输入的%ColumnName%不正确的网址',
        ],
        #日期格式 2016-06-01
        'date' => [
            'rule' => '^(?:(?!0000)[0-9]{4}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-8])|(?:0[13-9]|1[0-2])-(?:29|30)|(?:0[13578]|1[02])-31)|(?:[0-9]{2}(?:0[48]|[2468][048]|[13579][26])|(?:0[48]|[2468][048]|[13579][26])00)-02-29)$',
            'msg' => '您输入的%ColumnName%格式不正确',
        ],
        #价格
        'currency' => [
            'rule' => '/^\d+(\.\d+)?$/',
            'msg' => '请输入正确格式的%ColumnName%',
        ],
        #数字
        'number' => [
            'rule' => '/^\d+$/',
            'msg' => '请输入正确的%ColumnName%,只能输入数字',
        ],
        #邮编
        'zip' => [
            'rule' => '/^\d{6}$/',
            'msg' => '请输入正确的%ColumnName%',
        ],
        #整数
        'integer' => [
            'rule' => '/^[-\+]?\d+$/',
            'msg' => '%ColumnName%只能是正整数或者负整数',
        ],
        #浮点数
        'double' => [
            'rule' => '/^[-\+]?\d+(\.\d+)?$/',
            'msg' => '%ColumnName%只能是小数',
        ],
        #英语单词
        'english' => [
            'rule' => '/^[A-Za-z]+$/',
            'msg' => '%ColumnName%只能是英语单词',
        ],
        #验证汉字
        'chinese' => [
            'rule' => '/^[\u4e00-\u9fa5]+$/',
            'msg' => '%ColumnName%只能是汉字',
        ],
        #QQ号码
        'qq' => [
            'rule' => '/^[1-9]\d{4,10}$/',
            'msg' => '请输入正确的%ColumnName%',
        ],
        #验证手机号码
        'mobile' => [
            'rule' => '/^1[3456789][0-9]{9}$/',
            'msg' => '请输入正确的%ColumnName%',
        ],
        # 微信号
        'weChat' => [
            'rule' => '/^[a-zA-Z\d_]{6,}$/',
            'msg' => '请输入正确的%ColumnName%',
        ],
        #用户名 常用正则
        'username' => [
            'rule' => '/^[a-zA-Z0-9_]{4,16}$/',
            'msg' => '%ColumnName%只允许输入英文数字下划线4-16位字符',
        ],
        #密码  常用正则   不能包含空白符 并且在4-16
        'password' => [
            'rule' => '/^[^\s]{4,16}$/',
            'msg' => '%ColumnName%不能包含空格，并且4-16个字符',
        ],
        #日期正则 年月日
        'Date' => [
            '/^(?:(?!0000)[0-9]{4}([-/.]?)(?:(?:0?[1-9]|1[0-2])\1(?:0?[1-9]|1[0-9]|2[0-8])|(?:0?[13-9]|1[0-2])\1(?:29|30)|(?:0?[13578]|1[02])\1(?:31))|(?:[0-9]{2}(?:0[48]|[2468][048]|[13579][26])|(?:0[48]|[2468][048]|[13579][26])00)([-/.]?)0?2\2(?:29))$/',
            '%ColumnName%不是正确的日期格式',
        ],
        #时间正则 年月日时分秒
        'DateTime' => [
            '/^(?:(?!0000)[0-9]{4}([-/.]?)(?:(?:0?[1-9]|1[0-2])\1(?:0?[1-9]|1[0-9]|2[0-8])|(?:0?[13-9]|1[0-2])\1(?:29|30)|(?:0?[13578]|1[02])\1(?:31))|(?:[0-9]{2}(?:0[48]|[2468][048]|[13579][26])|(?:0[48]|[2468][048]|[13579][26])00)([-/.]?)0?2\2(?:29))\s+([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/',
            '%ColumnName%不是正确的时间格式',
        ],
        #昵称 常用正则 中文字母数字下划线
        'nickerName' => [
            'rule' => '/^[\x80-\xff_a-zA-Z0-9]{1,20}$/',
            'msg' => '%ColumnName%只允许中英文下划线1-20个字符',
        ],
        #身份证
        'idCard' => [
            'rule' => '/^(\d{15}$|^\d{18}$|^\d{17}(\d|X|x))$/',
            'msg' => '请输入正确的%ColumnName%',
        ],
    ];


    public function __construct()
    {
        $this->initialization();
        $this->addCheckRule('file', static function ($Column, &$value) {
            $filesModel = new filesModel();
            $allow_type = $Column['allow_type'] ?? [];
            $value = $filesModel->upload($value, $allow_type);
            $status = $value['status'] ?? FALSE;
            $path = $value['path'] ?? FALSE;
            $msg = $value['msg'] ?? '';
            if (isset($status)) {
                if ($status === 'false') {
                    return $Column['ColumnName'] . '上传出现错误：' . $msg;
                }
                $value = $path;

                return NULL;
            }
            $value = $path;
            return NULL;
        }, NULL);
    }

    /**
     * 验证字段是否合法,如果返回Null则视为合法，否则视为失败
     *
     * @param $data
     *
     * @return String|Null
     */
    final public function checkColumn(&$data): ?string
    {
        foreach ($data as $column => $value) {
            if (isset($this->Column[$column])) {
                if (is_callable($this->Column[$column]['rule'])) {
                    if ($res = $this->Column[$column]['rule']($this->Column[$column], $value)) {
                        if (isset($this->Column[$column]['msg']) && $this->Column[$column]['msg'] !== '') {
                            return str_replace('%ColumnName%', $this->Column[$column]['ColumnName'], $this->validate[$this->Column[$column]['rule']]['msg'] ?? '请输入正确的%ColumnName%');
                        }

                        return $res;
                    }
                } else
                    if (isset($this->validate[$this->Column[$column]['rule']])) {
                        if (is_callable($this->validate[$this->Column[$column]['rule']]['rule'])) {
                            if ($res = $this->validate[$this->Column[$column]['rule']]['rule']($this->Column[$column], $value)) {
                                if (isset($this->validate[$this->Column[$column]['rule']]['msg']) &&
                                    $this->validate[$this->Column[$column]['rule']]['msg'] !== '') {
                                    return str_replace('%ColumnName%', $this->Column[$column]['ColumnName'], $this->validate[$this->Column[$column]['rule']]['msg'] ?? '请输入正确的%ColumnName%');
                                }

                                return $res;
                            }
                        } else if (preg_match($this->validate[$this->Column[$column]['rule']]['rule'], $value) !== 1) {
                            return str_replace('%ColumnName%', $this->Column[$column]['ColumnName'], $this->validate[$this->Column[$column]['rule']]['msg'] ?? '请输入正确的%ColumnName%');
                        }
                    } else {
                        Logger::error('Model: rule [' . $this->Column[$column]['rule'] . '] is Undefined!');
                    }
            }
        }

        return NULL;

    }

    /**
     * @param $ruleName
     * @param $rule
     * @param $msg
     */
    final public function addCheckRule($ruleName, $rule, $msg): void
    {
        $this->validate[$ruleName] = [
            'rule' => $rule,
            'msg' => $msg,
        ];
    }
}