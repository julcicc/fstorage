<?php
require("lib.inc.php");

function _forbidden() {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

function _bad_request() {
    header('HTTP/1.0 400 Bad Request');
    exit;
}

function _not_found() {
    header('HTTP/1.1 404 Not Found');
    exit;
}

function _not_modified() {
    header('Not Modified',true,304);
    exit;
}

function _precondition_failed() {
    header('HTTP/1.1 412 Precondition Failed');
    exit;
}

function _err_range_request($start, $end, $size) {
    header('HTTP/1.1 416 Requested Range Not Satisfiable');
    if ($start>0) header("Content-Range: bytes $start-$end/$size");
    exit;
}

if (!isset($_REQUEST['user']) || !isset($_REQUEST['pass'])) {
    _forbidden();
}
if ($_REQUEST['user'] != FSTORAGE_ACCESS_USER || $_REQUEST['pass'] != FSTORAGE_ACCESS_PASS) {
    _forbidden();
}

if (!isset($_REQUEST['l'])) {
    _bad_request();
}
$location = trim($_REQUEST['l']);

if (!$location) {
    _bad_request();
}

$api = new FStorage_API();
$obj = $api->fetchLocation($location);
if (!$obj) {
    _not_found();
}

$file = $api->getObjectFile($obj['bucket_name'], $location);

$lastModifiedSeconds = strtotime($obj['date_modified']);
$etag = md5($obj['fs_location']) . $obj['content_md5'];
$requestHeaders = apache_request_headers();

if (isset($requestHeaders['If-None-Match']) && $requestHeaders['If-None-Match']==$etag) {
     _not_modified();
}

if (isset($requestHeaders['If-Match']) && $requestHeaders['If-Match']!=$etag
    && isset($requestHeaders['Range'])) {
     _err_range_request(0,0,0);
}

if (isset($requestHeaders['If-Modified-Since']) &&
    ($lastModifiedSeconds < strtotime( trim($requestHeaders['If-Modified-Since']) ))) {
     _not_modified();
}

/*
if (isset($requestHeaders['If-Unmodified-Since']) &&
    ($lastModifiedSeconds >= strtotime( trim($requestHeaders['If-Unmodified-Since'])))
     && isset($requestHeaders['Range'])) {
    _precondition_failed();
}
*/

ob_get_clean();
$resourceFp = null;

//AVOID PROCESSING HEAD METHOD we detect this by leaving the resource file 
//pointer to null
if (!(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD']=='HEAD')) { 
    $resourceFp = @fopen($file, 'rb');
    if (!$resourceFp) {
        _not_found();
    }
}

//cache headers
header('X-Fstorage-Info: ok');
header('Last-Modified: '. gmdate('r', $lastModifiedSeconds));
header("ETag: $etag");
header("Content-Type: " . $obj['content_type'] );
//ranges header
$start = 0;
$size = intval($obj['content_size']);
$end = $size - 1;
header("Accept-Ranges: bytes");

if (isset($_SERVER['HTTP_RANGE'])) {
    list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
    if (strpos($range, ',') !== false) {
        _err_range_request($start, $end, $size);
    }

    if ($range == '-') {
        $c_start = $size - substr($range, 1);
    }else{
        $range = explode('-', $range);
        $c_start = $range[0];
        $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $end;
    }

    $c_end = ($c_end > $end) ? $end : $c_end;
    if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
        _err_range_request($start, $end, $size);
    }

    $start = $c_start;
    $end = $c_end;
    $length = $end - $start + 1;
    if($resourceFp) fseek($resourceFp, $start);
    header('HTTP/1.1 206 Partial Content');
    header("Content-Length: ".$length);
    header("Content-Range: bytes $start-$end/".$size);
}
else {
    header("Content-Length: " . $obj['content_size'] );
}

//do not process full if HEAD method Only
if($resourceFp) {
    fpassthru($resourceFp);
}
exit;
