<?php

namespace QApi\Config\Cache;

use QApi\Cache\ApcuAdapter;
use QApi\Cache\SwooleTableAdapter;
use QApi\Config\Abstracts\Cache;
use QApi\Exception\CacheErrorException;

class SwooleTable extends Cache
{
    public string $driver = SwooleTableAdapter::class;
    public string $name = 'SwooleTable';

    /**
     * 2Gb of memory is used by default
     * @param int $rowsSize
     * @param int $valueSize
     */
    public function __construct(public int $rowsSize = 1024 * 1024, public int $valueSize =
    1024 * 1024)
    {
    }

}