<?php
require("lib.inc.php");

//TODO check user password
if (!isset($_REQUEST['user']) || !isset($_REQUEST['pass'])) {
    header('HTTP/1.0 403 Forbidden');
    die("403 Forbidden");
}
if ($_REQUEST['user'] != FSTORAGE_ACCESS_USER || $_REQUEST['pass'] != FSTORAGE_ACCESS_PASS) {
    header('HTTP/1.0 403 Forbidden');
    die("403 Forbidden");
}

if (!isset($_REQUEST['l'])) {
    header('HTTP/1.0 400 Bad Request');
    die("400 Bad Request");
}
$location = trim($_REQUEST['l']);

if (!$location) {
    header('HTTP/1.0 400 Bad Request');
    die("400 Bad Request");
}

$api = new FStorage_API();
$obj = $api->fetchLocation($location);
if (!$obj) {
    header('HTTP/1.0 404 Not Found');
    die("404 Not Found");
}

$file = $api->getObjectFile($obj['bucket_name'], $location);

//TODO ranges header
//TODO cache
//TODO etag
//TODO expires
//TODO last modified
//TODO if modified since
//
$fp = fopen($file, 'rb');

header("Content-Type: " . $obj['content_type'] );
header("Content-Length: " . $obj['content_size'] );
fpassthru($fp);
exit;
