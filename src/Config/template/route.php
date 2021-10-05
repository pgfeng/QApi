<?php

return [

    /**
     * 路由缓存开关 路由表条数过大时建议开启
     */
    'cache' => false,

    /**
     * 路由缓存驱动
     */
    'cacheDriver' => new \QApi\Config\Cache\FileSystem(directory: PROJECT_PATH . 'runtime' . DIRECTORY_SEPARATOR . 'route'),

    /**
     * 缓存闭包函数开关
     * 注意：函数内部代码也会一并缓存 建议正式环境开启
     */
    'cacheClosure' => false,

    /**
     * 缓存时间
     * @var DateInterval|int|null
     */
    'cacheTTL' => null,

    /**
     * 默认控制器
     */
    'defaultController' => 'Index',

    /**
     * 默认行为
     */
    'defaultAction' => 'index',


];