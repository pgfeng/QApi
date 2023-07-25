<?php

namespace QApi\Template\Interfaces;

use QApi\Data;

/**
 * FunctionInterface
 */
interface FunctionInterface
{
    /**
     * @return string
     */
    public function getFunctionName(): string;

    /**
     * @return array
     */
    public function getParameter(): array;

    /**
     * @return mixed
     */
    public function runFunction(): mixed;
}