<?php

namespace QApi\Template\Lib;

class Parameter implements \Stringable
{
    private string $noMarkValue;
    public function __construct(private string $originalString,private string|int $key,private mixed $value,private string $type)
    {
        $this->noMarkValue = preg_replace('/[\'"]?(.*)[\'"]?/U',"\\1",$this->value);
    }


    public function __toString()
    {
        return $this->value;
    }

    /**
     * @return array|string|null
     */
    public function getNoMarkValue(): array|string|null
    {
        return $this->noMarkValue;
    }

    /**
     * @return string
     */
    public function getOriginalString(): string
    {
        return $this->originalString;
    }

    /**
     * @return int|string
     */
    public function getKey(): int|string
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function getType(): string
    {
        return $this->type;
    }
}