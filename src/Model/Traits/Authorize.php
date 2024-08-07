<?php

namespace QApi\Model\Traits;

use JetBrains\PhpStorm\ArrayShape;
use QApi\Data;
use QApi\Logger;
use QApi\ORM\Model;

/**
 * @mixin Model
 * @package QApi\Model\Traits
 */
trait Authorize
{
    /**
     * @var bool | array|Data
     */
    protected Data|string|bool $account = false;

    /**
     * 账户名字段,用于登陆校验,可是多个字段
     * @var array
     */
    protected array $account_field = ['user_name'];

    /**
     * 账户密码,用于校验以及生产HASH密码
     * @var string
     */
    protected string $password_field = 'user_password';

    /**
     * 存放盐值的字段，不设置不使用
     */
    protected string|null $password_salt_field = null;

    /**
     * 盐值长度
     */
    protected int $password_salt_length = 16;

    protected function beforeSave(Data|array $data): Data|array
    {
        if (isset($data[$this->password_field])) {
            $salt = random($this->password_salt_length, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz$_=-0123456789');
            if ($this->password_salt_field) {
                $data[$this->password_field] = password_hash($data[$this->password_field] . $salt, PASSWORD_DEFAULT);
                $data[$this->password_salt_field] = $salt;
            } else {
                $data[$this->password_field] = password_hash($data[$this->password_field], PASSWORD_DEFAULT);
            }
        }
        return $data;
    }

    /**
     * @param $account_name
     * @return bool|Data
     */
    public function getAccount($account_name): null|Data
    {
        $field_counter = 0;
        foreach ($this->account_field as $field) {
            if ($field_counter === 0) {
                $this->where($field, $account_name);
            } else {
                $this->orWhere($field, $account_name);
            }
            $field_counter++;
        }
        return $this->find();
    }

    /**
     * 根据用户账号和密码登陆
     * @param string $account
     * @param string $password
     * @return array|boolean
     */
    public function getToken(string $account, string $password): bool|string
    {
        $account_data = $this->getAccount($account);
        if (!$account_data) {
            return false;
        }
        $hash_password = $account_data[$this->password_field];
        if ($this->password_salt_field) {
            $password .= $account_data[$this->password_salt_field];
        }
        if (password_verify($password, $hash_password)) {
            return base64_encode($account . ' || ' . md5($hash_password));
        }

        return false;
    }

    /**
     * @param string $account
     * @param string $password
     * @return array
     */
    #[ArrayShape(['status' => "bool", 'data' => "array", 'message' => "string", 'code' => "int"])]
    public function login(string $account, string $password): array
    {
        $account = $this->getAccount($account);
        if (!$account) {
            return ['status' => false, 'data' => [], 'message' => '账号不存在！', 'code' => -2];
        }
        $hash_password = $account[$this->password_field];
        if ($this->password_salt_field) {
            $password .= $account[$this->password_salt_field];
        }
        if (password_verify($password, $hash_password)) {
            return ['status' => true, 'data' => $account, 'message' => '登录成功！', 'code' => 0];
        } else {
            return ['status' => false, 'data' => [], 'message' => '密码错误！', 'code' => -1];
        }
    }

    /**
     * 根据用户账号信息获取Token
     * @param Data $account
     * @param string $account_field
     * @return array|boolean
     */
    public function getAccountToken(Data $account, string $account_field): bool|string
    {
        return base64_encode($account[$account_field] . ' || ' . md5($account[$this->password_field]));
    }

    /**
     * 验证token是否正确,正确返回账户信息,否则返回false
     * @param string $token
     * @return Data|bool|null
     */
    public function checkToken(string $token): Data|bool|null
    {
        return $this->getAccountByToken($token);
    }

    /**
     * @param string $token
     * @return Data|bool|null
     */
    public function getAccountByToken(string $token): Data|bool|null
    {
        if (!$token) {
            return false;
        }
        $token = explode(' || ', base64_decode($token));
        if (count($token) !== 2) {
            return false;
        }
        //-- 获取账户信息
        $account = $this->getAccount($token[0]);
        if (!$account) {
            return false;
        }
        $hash_password = md5($account[$this->password_field]);
        if ($hash_password === $token[1]) {
            return $account;
        }
        return false;
    }
}