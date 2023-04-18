<?php

namespace QApi\Validator\Constraints;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class NotBlank
{
    public function __construct(
        public int|null $min = null,
        public int|null $max = null,
        public bool     $allowEmpty = false,
    ){

    }

}