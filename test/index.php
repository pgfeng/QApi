<?php

use QApi\App;
use QApi\Config;

require "../vendor/autoload.php";

try {
    App::run(
        getVersionFunction: static function (string $defaultVersion): string {
        return $_GET['_ver'] ?? $defaultVersion;
    },
    );
} catch (ErrorException $e) {
}