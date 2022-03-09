<?php

namespace QApi\Validator\Constraints;

use QApi\Attribute\Route;
use QApi\Response;
use Test\Model\_Column_default\article;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class File
{
    public const KB_BYTES = 1000;
    public const MB_BYTES = 1000000;
    public const KIB_BYTES = 1024;
    public const MIB_BYTES = 1048576;
    public function __construct(
        public string $name,
        public int|null $min = null,
        public int|null $max = null,
        public bool     $allowEmptyString = false,
    )
    {
    }
}