<?php
$_SERVER['PATH_INFO'] = $_GET['_router'] = preg_replace('/\?(.*)/', '', $_SERVER['REQUEST_URI']);
if (is_file($_SERVER["DOCUMENT_ROOT"] . $_SERVER["SCRIPT_NAME"])) {
    return false;
}
$_SERVER["SCRIPT_FILENAME"] = '/index.php';
$_SERVER["SCRIPT_NAME"] = '/index.php';
$_SERVER["PHP_SELF"] = '/index.php';
include $_SERVER['DOCUMENT_ROOT'] . "/index.php";