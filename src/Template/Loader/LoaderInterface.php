<?php

namespace QApi\Template\Loader;

use QApi\Template\Lib\Source;

interface LoaderInterface
{
    public function getSource(string $name): Source;

    public function isFresh(string $name,int $time):bool;

    public function exists(string $name): string|false;
}