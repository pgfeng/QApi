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
    #[Pure] public function isGetMethod():bool
    {
        return $this->isMethod(MethodsEnum::METHOD_GET);
    }

    /**
     * 是否是Post请求
     * @return bool
     */
    #[Pure] public function isPostMethod():bool
    {
        return $this->isMethod(MethodsEnum::METHOD_POST);
    }

    /**
     * 是否是Put请求
     * @return bool
     */
    #[Pure] public function isPutMethod():bool
    {
        return $this->isMethod(MethodsEnum::METHOD_PUT);
    }

    /**
     * 是否是Delete请求
     * @return bool
     */
    #[Pure] public function isDeleteMethod():bool
    {
        return $this->isMethod(MethodsEnum::METHOD_DELETE);
    }

    /**
     * 验证请求类型
     * @param string $request_method
     * @return bool
     */
    #[Pure] public function isMethod(string $request_method):bool
    {
        return strtoupper($request_method) === $this->getMethod();
    }



}