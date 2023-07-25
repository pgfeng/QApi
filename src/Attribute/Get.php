<?php

namespace QApi\Attribute;

use Attribute;
use JetBrains\PhpStorm\Pure;
use QApi\Route\Methods;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE | Attribute::TARGET_FUNCTION)] class Get extends Route
{
    #[Pure] public function __construct(string $path = '', array $paramPattern = [], array|string|null $middleware = null, ?string $summary = null, ?string $description = null, array|string|null $tag = '', bool $checkParams = false)
    {
        parent::__construct($path, Methods::GET, $paramPattern, $middleware, $summary, $description, $tag, $checkParams);
    }
}