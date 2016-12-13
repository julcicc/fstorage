<?php
require("lib.inc.php");

function _forbidden() {
    header('HTTP/1.0 403 Forbidden');
    die("403 Forbidden");
}

function _bad_request() {
    header('HTTP/1.0 400 Bad Request');
    die("400 Bad Request");
}

function _errr_range_request($start, $end, $size) {
    header('HTTP/1.1 416 Requested Range Not Satisfiable');
    header("Content-Range: bytes $this->start-$this->end/$this->size");
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

$lastModifiedSeconds = strtotime($obj['date_modified']);
$etag = md5($obj['fs_location']) . $obj['content_md5'];
$clientLastModifiedSeconds = strtotime(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? trim($_SERVER['HTTP_IF_MODIFIED_SINCE']) : 0);
$ifNoneMatch = isset($_SERVER['HTTP_IF_NONE_MATCH']) ?  stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) : 0;

if( ( ($clientLastModifiedSeconds) && ($lastModifiedSeconds <= $clientLastModifiedSeconds ) )
     || ($ifNoneMatch && $ifNoneMatch == $etag) ) {
       header('Not Modified',true,304);
       exit;
}

ob_get_clean();

//cache headers
header('Last-Modified: '. date('r', $lastModifiedSeconds));
header("ETag: $etag");
header("Content-Type: " . $obj['content_type'] );

$fp = fopen($file, 'rb');
//ranges header
$start = 0;
$size = intval($obj['content_size']);
$end = $size - 1;
header("Accept-Ranges: 0-".$end);
if (isset($_SERVER['HTTP_RANGE'])) {
    list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
    if (strpos($range, ',') !== false) {
        _errr_range_request($start, $end, $size);
    }

    if ($range == '-') {
        $c_start = $size - substr($range, 1);
    }else{
        $range = explode('-', $range);
        $c_start = $range[0];
        $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $c_end;
    }

    $c_end = ($c_end > $end) ? $end : $c_end;
    if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
        _errr_range_request($start, $end, $size);
    }

    $start = $c_start;
    $end = $c_end;
    $length = $end - $start + 1;
    fseek($fp, $start);
    header('HTTP/1.1 206 Partial Content');
    header("Content-Length: ".$length);
    header("Content-Range: bytes $start-$end/".$size);
}
else {
    header("Content-Length: " . $obj['content_size'] );
}


fpassthru($fp);
exit;
