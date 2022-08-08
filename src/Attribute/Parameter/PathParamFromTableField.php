<?php

namespace QApi\Attribute\Parameter;

use Attribute;
use JetBrains\PhpStorm\ArrayShape;
use QApi\Attribute\Column\Field;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)] class PathParamFromTableField
{
    public string $name;

    public function __construct(
        public string  $tableColumnClass,
        public string  $field,
        public ?string $summary = null,
        public ?string $description = null,
        public ?string $type = null,
        public ?bool   $required = null,
        public mixed   $default = null,
    )
    {
        $ref = new \ReflectionClass($tableColumnClass);
        $constants = $ref->getReflectionConstants();
        foreach ($constants as $constant) {
            $attributes = $constant->getAttributes(Field::class);
            foreach ($attributes as $attribute) {
                $argument = $attribute->getArguments();
                if ($argument['name'] === $this->field) {
                    $this->name = $argument['name'];
                    $this->summary = $this->summary === null ? $argument['comment'] : $this->summary;
                    $this->description = $this->description === null ? $argument['comment'] : $this->description;
                    $this->type = $this->type === null ? $argument['type'] : $this->type;
                    $this->required = $this->required === null ? !($argument['allowNull'] === 'true') : $this->required;
                    $this->default = $this->default === null ? $argument['default'] : $this->default;
                }
            }
        }
    }

    #[ArrayShape(['name' => "string", 'summary' => "string", 'description' => "string", 'type' => "string",
        'required' => "bool", 'default' => "mixed"])] public function toArray(): array
    {
        return [
            'name' => $this->name,
            'summary' => $this->summary,
            'description' => $this->description,
            'type' => $this->type,
            'required' => $this->required,
            'default' => $this->default,
        ];
    }
}