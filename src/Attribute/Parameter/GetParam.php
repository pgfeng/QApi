<?php
/** @noinspection ALL */

namespace QApi\Attribute\Parameter;


use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)] class GetParam extends ParamAbstract
{
}