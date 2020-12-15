<?php

use QApi\Config\Application;
use QApi\Enumeration\RunMode;

return [
    '*' => new Application(appDir: 'App', runMode: RunMode::DEVELOPMENT,defaultVersionName: 'V1.0.0'),
    'qapi.t.9icode.net' => new Application(appDir: 'App', runMode: RunMode::PRODUCTION,defaultVersionName: 'V1.0.0'),
];