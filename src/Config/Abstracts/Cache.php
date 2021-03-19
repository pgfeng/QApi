<?php


namespace QApi\Config\Abstracts;


abstract class Cache
{
    /**
     * @var string
     */
    public string $name;

    /**
     * @var string driver class name
     */
    public string $driver;
}