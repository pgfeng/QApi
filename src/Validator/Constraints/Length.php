<?php

namespace QApi\Validator\Constraints;

use QApi\Attribute\Route;
use QApi\Response;
use Test\Model\_Column_default\article;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Length
{
    public function __construct(
        public string $name,
        public int|null $min = null,
        public int|null $max = null,
        public bool     $allowEmptyString = false,
    )
    {
    }
}