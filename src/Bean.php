<?php


namespace QApi;


class Bean
{

    public function __construct(
        public int $id,
        public string $name = '',
        public string $time = ''
    )
    {
    }
}