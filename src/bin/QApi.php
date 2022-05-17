#!/usr/bin/env php
<?php

use QApi\Command;
use QApi\Enumeration\RunMode;
if (PROJECT_PATH){

}
define('PROJECT_PATH', getcwd() . DIRECTORY_SEPARATOR);
require 'vendor/autoload.php';
if (!defined('RUN_MODE')) {
    define('RUN_MODE', RunMode::DEVELOPMENT);
}
$Command = new Command();
$Command->execute();