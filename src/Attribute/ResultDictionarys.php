<?php
/** @noinspection ALL */

namespace QApi\Attribute;


use \Attribute;

/**
 * Class ResultDictionaryFromTable
 * @package QApi\Attribute
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)] class
ResultDictionarys
{

    /**
     * ResultDictionarys constructor.
     * @param array $fields
     */
    public function __construct(public array $fields)
    {
    }

    public function toArray(): array
    {
        return $this->fields;
    }
}