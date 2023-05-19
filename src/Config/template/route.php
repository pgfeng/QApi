<?php

use QApi\Config\Cache\FileSystem;

return [

    /**
     * 路由缓存开关 正式环境建议开启
     */
    'cache' => false,

    /**
     * 路由缓存驱动
     */
    'cacheDriver' => new FileSystem(directory: PROJECT_PATH . 'runtime' . DIRECTORY_SEPARATOR . 'route'),

    /**
     * 缓存闭包函数开关
     */
    'cacheClosure' => false,

    /**
     * 缓存时间
     * DateInterval|int|null
     */
    'cacheTTL' => null,


    /**
     * 自动路由
     */
    'autoRoute' => true,

    /**
     * 默认控制器 开启自动路由时有效
     */
    'defaultController' => 'Index',

    /**
     * 默认行为 开启自动路由时有效
     */
    'defaultAction' => 'index',

    /**
     * 请求对象读取文档参数
     */
    'RequestReadDocumentParameters' => true,
];