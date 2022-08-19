<?php

namespace QApi\Model\Traits;

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


    /**
     * 重写保存
     * @param array|Data $data
     * @param string|null $primary_key
     * @return int
     * @throws \Exception
     */
    public function saveAuthorize(Data|array $data, ?string $primary_key = null, array $types = []): int
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
        Logger::error($this->password_salt_field);
        return $this->save($data, $primary_key); // TODO: Change the autogenerated stub
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
     * 根据用户账号信息获取Token
     * @param Data $account
     * @param string $account_field
     * @return array|boolean
     */
    public function getAccountToken(Data $account, string $account_field): bool|string
    {
        if (!$account) {
            return false;
        }
        return base64_encode($account[$account_field] . ' || ' . md5($account[$this->password_field]));
    }

    /**
     * 验证token是否正确,正确返回账户信息,否则返回false
     * @param string $token
     * @return Data|bool|null
     */
    public function checkToken(string $token): Data|bool|null
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