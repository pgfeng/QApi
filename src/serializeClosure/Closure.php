<?php

namespace QApi\serializeClosure;

use ReflectionFunction;


class Closure
{

    public ?Closure $closure;
    public ?array $use;
    private string $closureCode;

    public function __construct(\closure $closure)
    {
        $ref = new ReflectionFunction($closure);
        $path = $ref->getFileName();  // absolute path of php file
        $begn = $ref->getStartLine(); // have to `-1` for array index
        $endn = $ref->getEndLine();
        $dlim = "\n"; // or PHP_EOL
        $content = file_get_contents($path);
        $list = explode($dlim, $content);         // lines of php-file source
        $list = array_slice($list, ($begn - 1), ($endn - ($begn - 1))); // lines of closure definition
        $last = (count($list) - 1); // last line number
        $list[0] = ('function' . explode('function', $list[0])[1]);
        $list[$last] = (explode('}', $list[$last])[0] . '}');
        $code = implode($dlim, $list);
        preg_match_all('/use[\s+](.*);/iUs', $content, $matches);
        $this->closureCode = base64_encode(json_encode([
            'code' => $code,
            'use' => implode("\n", $matches[0]),
        ], JSON_UNESCAPED_UNICODE));
    }


    public function getClosure(): \closure
    {
        ClosureStream::register();
        return include(ClosureStream::STREAM_PROTO . '://' . $this->closureCode);
    }
}