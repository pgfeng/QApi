<?php


namespace QApi;


class Model
{
    public function __construct(protected string $bean)
    {

    }

    /**
     * @return array
     */
    public function query(): array
    {
        return [new $this->bean(1, '哈哈', 'ss')];
    }
}