<?php

namespace QApi\Model\Enums;

class ValidateEnum
{
    /**
     * 必填
     */
    const REQUIRE = 'require';

    /**
     * 邮箱
     */
    const EMAIL = 'email';

    /**
     * URL
     */
    const URL = 'url';

    /**
     * 日期
     */
    const DATE = 'date';

    /**
     * 价格
     */
    const CURRENCY = 'currency';

    /**
     * 正整数
     */
    const NUMBER = 'number';

    /**
     * 邮编
     */
    const ZIP = 'zip';

    /**
     * 整数 包含0和负数
     */
    const INTEGER = 'integer';

    /**
     * 浮点数
     */
    const DOUBLE = 'double';

    /**
     * 英文
     */
    const ENGLISH = 'english';

    /**
     * 中文
     */
    const CHINESE = 'chinese';

    /**
     * QQ号
     */
    const QQ = 'qq';

    /**
     * 手机号
     */
    const MOBILE = 'mobile';

    /**
     * 微信号
     */
    const WECHAT = 'weChat';

    /**
     * 用户名
     */
    const USERNAME = 'username';

    /**
     * 密码
     */
    const PASSWORD = 'password';

    /**
     * 日期
     */
    const Date = 'Date';

    /**
     * 时间 年月日时分秒
     */
    const DATETIME = 'DateTime';

    /**
     * 昵称
     */
    const NICKNAME = 'nickerName';

    /**
     * 版本号
     */
    const VERSION_NUMBER = 'versionNumber';

    /**
     * 身份证号
     */
    const IdCARD = 'idCard';
}