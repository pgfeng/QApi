<?php

namespace QApi\Config\Cache;

use QApi\Cache\MongoDBAdapter;
use QApi\Config\Abstracts\Cache;
use QApi\Config\Database\MongoDatabase;

class Mongo extends Cache
{
    /**
     * @var string
     */
    public string $name = 'MongoDB';

    /**
     * @var string driver class name
     */
    public string $driver = MongoDBAdapter::class;

    public function __construct(public MongoDatabase $config,
                                public string        $table = 'cache_items',
                                public string        $keyCol = 'item_key',
                                public string        $dataCol = 'item_data',
                                public string        $lifetimeCol = 'item_lifetime',
                                public string        $expiresTimeCol = 'item_expires_time',
                                public string        $timeCol = 'item_time',
                                public string        $namespace = 'default-->',
                                public int           $cleanUpTime = 60 * 60)
    {
    }
}