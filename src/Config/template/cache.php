<?php


use QApi\Config\Cache\FileSystem;

return [
    'default' => new FileSystem(\QApi\App::$runtimeDir . DIRECTORY_SEPARATOR . 'cache'),
];