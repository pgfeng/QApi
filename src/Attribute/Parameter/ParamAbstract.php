<?php


namespace QApi\Attribute\Parameter;

use JetBrains\PhpStorm\ArrayShape;

abstract class ParamAbstract
{
    /**
     * ParamAbstract constructor.
     * @param string $name
     * @param string $summary
     * @param string $description
     * @param string<string> $type
     * @param bool $required
     * @param mixed|null $default
     */
    public function __construct(
        public string $name,
        public string $summary = '',
        public string $description = '',
        public string $type = 'string',
        public bool $required = false,
        public mixed $default = null,
    )
    {
    }

    /**
     * @return array
     */
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