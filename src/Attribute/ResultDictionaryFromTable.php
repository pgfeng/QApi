<?php
/** @noinspection ALL */

namespace QApi\Attribute;


use \Attribute;
use QApi\Attribute\Column\Field;
use QApi\Attribute\Column\Table;

/**
 * Class ResultDictionaryFromTable
 * @package QApi\Attribute
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)] class
ResultDictionaryFromTable
{
    /**
     * Result constructor.
     * @param string[] $class
     */
    public function __construct(public array $tableColumnClassArray)
    {
    }

    public function toArray(): array
    {
        $data = [];
        foreach ($this->tableColumnClassArray as $item) {
            $ref = new \ReflectionClass($item);
            $classAttributes = $ref->getAttributes(Table::class);
            $constants = $ref->getReflectionConstants();
            foreach ($classAttributes as $classAttribute) {
                foreach ($constants as $constant) {
                    $attributes = $constant->getAttributes(Field::class);
                    foreach ($attributes as $attribute) {
                        $argument = $attribute->getArguments();
                        $data[] = [
                                'name' => $argument['name'],
                                'comment' => $argument['comment'],
                                'type' => $argument['type'],
                                'tag' => $classAttribute->getArguments()['name'],
                        ];
                    }
                }
            }
        }
        return $data;
    }
}