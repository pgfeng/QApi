#!/usr/bin/env php
<?php
require_once dirname(__DIR__,2).DIRECTORY_SEPARATOR.'vendor/autoload.php';

use QApi\Command;
use QApi\Enumeration\RunMode;

if (!defined('PROJECT_PATH')) {
    define('PROJECT_PATH', dirname(getcwd(), 3) . DIRECTORY_SEPARATOR);
}
if (!defined('RUN_MODE')) {
    define('RUN_MODE', RunMode::DEVELOPMENT);
}
$Command = new Command();
$Command->execute();