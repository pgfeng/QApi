<?php /** @noinspection ALL */

namespace QApi\Attribute\Column;


use Attribute;

#[Attribute(Attribute::TARGET_CLASS)] class Table
{
    /**
     * Field constructor.
     * @param string $name
     * @param string $comment
     * @param string $type
     */
    public function __construct(public string $name, public string $comment)
    {

    }
}