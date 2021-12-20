<?php

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use QApi\App;

$defaultHandle = new StreamHandler(PROJECT_PATH . DIRECTORY_SEPARATOR . App::$runtimeDir . DIRECTORY_SEPARATOR . 'CliLog' .
    DIRECTORY_SEPARATOR
    . date('Y-m-d')
    . DIRECTORY_SEPARATOR . date('H') . '.log',
    \Monolog\Logger::API,
    true, null, true);
$formatter = new LineFormatter("%datetime% %channel%.%level_name% > %message%\n", '[Y-m-d H:i:s]');
$defaultHandle->setFormatter($formatter);
return [
    'ColumnDir' => 'Model/_Column',
    'BaseColumnNameSpace' => 'Model\_Column',
    'ServerRunDir' => PROJECT_PATH.'public',
    'CommandHandlers' => [

    ],
    'logHandler' => [
        $defaultHandle
    ]
];