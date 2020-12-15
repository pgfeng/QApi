<?php


namespace QApi\Http\Request;


use JetBrains\PhpStorm\Pure;
use QApi\Request;

trait Methods
{

    /**
     * 是否是Get请求
     * @return bool
     */
    #[Pure] public static function isGetMethod():bool
    {
        return self::isMethod(MethodsEnum::METHOD_GET);
    }

    /**
     * 是否是Post请求
     * @return bool
     */
    #[Pure] public static function isPostMethod():bool
    {
        return self::isMethod(MethodsEnum::METHOD_POST);
    }

    /**
     * 是否是Put请求
     * @return bool
     */
    #[Pure] public static function isPutMethod():bool
    {
        return self::isMethod(MethodsEnum::METHOD_PUT);
    }

    /**
     * 是否是Delete请求
     * @return bool
     */
    #[Pure] public static function isDeleteMethod():bool
    {
        return self::isMethod(MethodsEnum::METHOD_DELETE);
    }

    /**
     * 验证请求类型
     * @param string $request_method
     * @return bool
     */
    #[Pure] public static function isMethod(string $request_method):bool
    {
        return strtoupper($request_method) === Request::$method;
    }



}