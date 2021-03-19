<?php


use QApi\Config\Cache\FileSystem;

return [
    'default' => new FileSystem(PROJECT_PATH . \QApi\App::$runtimeDir . DIRECTORY_SEPARATOR . 'cache'),
];