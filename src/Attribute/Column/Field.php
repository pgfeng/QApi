<?php /** @noinspection ALL */

namespace QApi\Attribute\Column;


use Attribute;

#[Attribute(Attribute::TARGET_METHOD,Attribute::TARGET_CLASS_CONSTANT)] class Field
{

    /**
     * Field constructor.
     * @param string $name
     * @param string $comment
     * @param string $type
     */
    public function __construct(public string $name, public string $comment, public string $type)
    {

    }
}