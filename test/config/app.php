<?php

use QApi\Config\Application;
use QApi\Enumeration\RunMode;

return [
    'qapi.t.9icode.net' => new Application(appDir: 'App', runMode: RunMode::PRODUCTION,defaultVersionName: 'V1.0.0'),
    '192.168.0.1' => new Application(appDir: 'App', runMode: RunMode::DEVELOPMENT,defaultVersionName: 'V1.0.2'),
    '0.0.0.0' => new Application(appDir: 'App', runMode: RunMode::DEVELOPMENT,defaultVersionName: 'V1.0.0'),
];