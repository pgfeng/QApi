<?php


namespace QApi\Attribute\Parameter;

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

}