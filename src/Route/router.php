<?php
$_SERVER['PATH_INFO'] = $_GET['_router'] = preg_replace('/\?(.*)/', '', $_SERVER['REQUEST_URI']);
if (file_exists($_SERVER["DOCUMENT_ROOT"] . $_SERVER["SCRIPT_NAME"]) && ($type = mime_content_type($_SERVER["DOCUMENT_ROOT"] .$_SERVER["SCRIPT_NAME"]))!='text/x-php') {
    header("Access-Control-Allow-Origin: *");
    if (pathinfo($_SERVER["DOCUMENT_ROOT"] . $_SERVER["SCRIPT_NAME"], PATHINFO_EXTENSION) == 'js') {
        $type = 'application/javascript';
    }elseif (pathinfo($_SERVER["DOCUMENT_ROOT"] . $_SERVER["SCRIPT_NAME"], PATHINFO_EXTENSION) == 'css') {
        $type = 'text/css';
    }
    header("Content-Type: $type");
    header("Expires: ".gmdate('D, d M Y H:i:s \G\M\T', time() + 25920000));
    header("Last-Modified: ".gmdate('D, d M Y H:i:s \G\M\T', filemtime($_SERVER["DOCUMENT_ROOT"] . $_SERVER["SCRIPT_NAME"])));
    if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == filemtime($_SERVER["DOCUMENT_ROOT"] . $_SERVER["SCRIPT_NAME"])) {
        header('HTTP/1.1 304 Not Modified');
        exit;
    }
    echo file_get_contents($_SERVER["DOCUMENT_ROOT"] . $_SERVER["SCRIPT_NAME"]);
} else {
    $_SERVER["SCRIPT_FILENAME"] = '/index.php';
    $_SERVER["SCRIPT_NAME"] = '/index.php';
    $_SERVER["PHP_SELF"] = '/index.php';
    include $_SERVER['DOCUMENT_ROOT'] . "/index.php";
}