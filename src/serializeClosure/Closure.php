<?php

namespace QApi\serializeClosure;

use ReflectionFunction;


class Closure
{

    public ?Closure $closure;

    private string $closureCode;

    public function __construct(\closure $closure)
    {
        $ref = new ReflectionFunction($closure);
        $params = $ref->getParameters();
        $paramData = [];
        foreach ($params as $param) {
            $paramData[] = '$' . $param->getName();
        }
        $this->closureCode = preg_replace('/function[\s]?\((.+)\)(.+){/', "function(" . implode(', ', $paramData) . "){", closureToStr($closure), 1);
    }


    public function getClosure(): \closure
    {
        ClosureStream::register();
        return include(ClosureStream::STREAM_PROTO . '://' . $this->closureCode);
    }
}