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
     * ResultDictionaryFromTable constructor.
     * @param string[]|string $tableColumnClass
     *[
     * 'field'=>
     *      [
     *      'name' => $this->replaceField[$argument['name']]['name'] ?? $argument['name'],
     *      'comment' => $this->replaceField[$argument['name']]['comment'] ?? $argument['comment'],
     *      'type' => $this->replaceField[$argument['name']]['type'] ?? $argument['type'],
     *      'tag' => $this->replaceField[$argument['name']]['tag'] ?? $classAttribute->getArguments()['name'],
     *     ]
     * ]
     * @param array $replaceField
     * @param string[] $ignoreField
     */
    public function __construct(
        public array|string $tableColumnClass,
        public array $replaceField = [],
        public array $ignoreField = []
    )
    {
        if (is_string($this->tableColumnClass)) {
            $this->tableColumnClass = [$this->tableColumnClass];
        }
    }

    public function toArray(): array
    {
        $data = [];
        foreach ($this->tableColumnClass as $item) {
            $ref = new \ReflectionClass($item);
            $classAttributes = $ref->getAttributes(Table::class);
            $constants = $ref->getReflectionConstants();
            foreach ($classAttributes as $classAttribute) {
                foreach ($constants as $constant) {
                    $attributes = $constant->getAttributes(Field::class);
                    foreach ($attributes as $attribute) {
                        $argument = $attribute->getArguments();
                        if (!in_array($argument['name'], $this->ignoreField)) {
                            $data[] = [
                                'name' => $this->replaceField[$argument['name']]['name'] ?? $argument['name'],
                                'comment' => $this->replaceField[$argument['name']]['comment'] ?? $argument['comment'],
                                'type' => $this->replaceField[$argument['name']]['type'] ?? $argument['type'],
                                'tag' => $this->replaceField[$argument['name']]['tag'] ?? $classAttribute->getArguments()['name'],
                            ];
                        }
                    }
                }
            }
        }
        return $data;
    }
}