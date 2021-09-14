<?php
/** @noinspection ALL */

namespace QApi\Attribute;


use \Attribute;

/**
 * Class ResultDictionaryFromTable
 * @package QApi\Attribute
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)] class
ResultDictionary
{
    /**
     * Result constructor.
     * @param string[] $class
     */
    public function __construct(public string $name, public string $comment, public string $type = 'string',
                                public string $tag
                                = 'other')
    {
    }

    public function toArray(): array
    {
        return [
            [
                'name' => $this->name,
                'comment' => $this->comment,
                'type' => $this->type,
                'tag' => $this->tag,
            ]
        ];
    }
}