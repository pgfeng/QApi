<?php

use QApi\App;
use QApi\Config;

require dirname(__DIR__) . "/vendor/autoload.php";


echo App::run(getVersionFunction: static function (string $defaultVersion): string {
    return $_GET['_ver'] ?? $defaultVersion;
});