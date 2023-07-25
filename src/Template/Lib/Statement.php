<?php

namespace QApi\Template\Lib;

class Statement
{
    const RENDER_INSTRUCTION = '$';

    const CUSTOM_INSTRUCTION = '#';

    const ANNOTATION_INSTRUCTION = '//';

    const FUNCTION_INSTRUCTION = '~';


    /**
     * @var Params
     */
    public Params $params;

    /**
     * @param string $originalStatement
     * @param string $instruction
     * @param string $name
     * @param string $params
     */
    public function __construct(public int $lineno,public string $originalStatement, public string $instruction, public string $name, string $params)
    {
        $this->params = new Params($params);
    }
}