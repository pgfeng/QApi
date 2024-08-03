<?php
$_SERVER['PATH_INFO'] = $_GET['_router'] = preg_replace('/\?(.*)/', '', $_SERVER['REQUEST_URI']);
if (file_exists($_SERVER["DOCUMENT_ROOT"] . $_SERVER["SCRIPT_NAME"]) && mime_content_type($_SERVER["DOCUMENT_ROOT"] .$_SERVER["SCRIPT_NAME"])!='text/x-php') {
    header("Access-Control-Allow-Origin: *");
    echo file_get_contents($_SERVER["DOCUMENT_ROOT"] . $_SERVER["SCRIPT_NAME"]);
} else {
    $_SERVER["SCRIPT_FILENAME"] = '/index.php';
    $_SERVER["SCRIPT_NAME"] = '/index.php';
    $_SERVER["PHP_SELF"] = '/index.php';
    include $_SERVER['DOCUMENT_ROOT'] . "/index.php";
}