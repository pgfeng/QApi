<?php
/** @noinspection ALL */

namespace QApi\Attribute\Parameter;

use Attribute;
use JetBrains\PhpStorm\ArrayShape;
use QApi\Attribute\Column\Field;
use QApi\Attribute\Column\Table;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)] class PostParamFromTable
{
    /**
     * @var PostParam[]
     */
    public array $postParams = [];


    /**
     * PostParamFromTable constructor.
     * @param string $tableColumnClass
     * [
     * 'field'=>
     *      [
     *      'name' => 'name',
     *      'summary' => 'summary',
     *      'description' => 'description',
     *      'type' => 'int',
     *      'required' => false,
     *      'default' => '',
     *     ]
     * ]
     * @param array $replaceField
     * @param array<string> $ignoreField
     * @throws \ReflectionException
     */
    public function __construct(
        public string $tableColumnClass,
        public array $replaceField = [],
        public array $ignoreField = [],
    )
    {
        $ref = new \ReflectionClass($tableColumnClass);
        $classAttributes = $ref->getAttributes(Table::class);
        $constants = $ref->getReflectionConstants();
        foreach ($classAttributes as $classAttribute) {
            foreach ($constants as $constant) {
                $attributes = $constant->getAttributes(Field::class);
                foreach ($attributes as $attribute) {
                    $argument = $attribute->getArguments();
                    if (!in_array($argument['name'], $this->ignoreField)) {
                        $this->postParams[] = new PostParam(
                            name: $this->replaceField[$argument['name']]['name'] ?? $argument['name'],
                            summary: $this->replaceField[$argument['name']]['summary'] ?? $argument['comment'],
                            description: $this->replaceField[$argument['name']]['description'] ?? $argument['comment'],
                            type: $this->replaceField[$argument['name']]['type'] ?? (stripos('int', $argument['type']) ?
                                ParamsType::INT : ParamsType::STRING),
                            required: $this->replaceField[$argument['name']]['required'] ?? false,
                            default: $this->replaceField[$argument['name']]['default'] ?? '',
                        );
                    }
                }
            }
        }
    }
    /**
     * @return array
     */
    #[ArrayShape(['name' => "string", 'summary' => "string", 'description' => "string", 'type' => "string",
        'required' => "bool", 'default' => "mixed"])] public function toArray(): array
    {
        return $this->postParams;
    }
}