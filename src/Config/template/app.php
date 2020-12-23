<?php

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use QApi\App;
use QApi\Config\Application;
use QApi\Enumeration\RunMode;

$defaultHandle = new StreamHandler(PROJECT_PATH . DIRECTORY_SEPARATOR . App::$runtimeDir . DIRECTORY_SEPARATOR . 'Log' .
    DIRECTORY_SEPARATOR
    . date('Y-m-d')
    . DIRECTORY_SEPARATOR . date('H') . '.log',
    \Monolog\Logger::API,
    true, null, true);
$formatter = new LineFormatter("%datetime% %channel%.%level_name% > %message%\n", '[Y-m-d H:i:s]');
$defaultHandle->setFormatter($formatter);
return [
    '*' => new Application(appDir: 'App', runMode: RunMode::DEVELOPMENT, defaultVersionName: 'V1.0.0', logHandler: [
        $defaultHandle,
        //        new \Monolog\Handler\NullHandler(),
    ]),
];