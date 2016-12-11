<?php
require("lib.inc.php");

function my_error_handler($errno, $errstr, $errfile, $errline) {
    $code = $errno;
    $text = $errstr . ". File $errfile [$errline]";
    header("Content-type:application/json");
    die(json_encode(__error($code,$text), JSON_NUMERIC_CHECK));
}

function my_exception_handler($e) {
    my_error_handler(strtoupper(get_class($e)), $e->getMessage(), "", 0);
}

function fatalErrorShutdownHandler()
{
  $last_error = error_get_last();
  if ($last_error['type'] === E_ERROR) {
    // fatal error
    my_error_handler(E_ERROR, $last_error['message'], $last_error['file'], $last_error['line']);
  }
}

function _json_output($value) {
	header("Content-type:application/json");
	echo json_encode($value, JSON_NUMERIC_CHECK);
	exit;
}

set_error_handler("my_error_handler", E_ALL);
set_exception_handler("my_exception_handler");
register_shutdown_function('fatalErrorShutdownHandler');
error_reporting(0);

if (intval($_SERVER['CONTENT_LENGTH'])>0 && count($_POST)==0) {
    _json_output(__error("ERROR_POST_SIZE",sprintf("Post size (%d) exceeds current server settings (%s)"
        , intval($_SERVER['CONTENT_LENGTH']), ini_get("post_max_size") )));
}

if (isset($_FILES['file']) && $_FILES['file']['error'] != UPLOAD_ERR_OK) {
    $txt = $code = "";
    switch($_FILES['file']['error']) {
        case UPLOAD_ERR_INI_SIZE:
            $code = "UPLOAD_ERR_INI_SIZE";
            $txt = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
            break;
        case UPLOAD_ERR_FORM_SIZE:
            $code = "UPLOAD_ERR_FORM_SIZE";
            $txt = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
            break;
        case UPLOAD_ERR_PARTIAL:
            $code = "UPLOAD_ERR_PARTIAL";
            $txt = "The uploaded file was only partially uploaded";
            break;
        case UPLOAD_ERR_NO_FILE:
            $code = "UPLOAD_ERR_NO_FILE";
            $txt = "No file was uploaded";
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $code = "UPLOAD_ERR_NO_TMP_DIR";
            $txt = "Missing a temporary folder";
            break;
        case UPLOAD_ERR_CANT_WRITE:
            $code = "UPLOAD_ERR_CANT_WRITE";
            $txt = "Failed to write file to disk";
            break;
        case UPLOAD_ERR_EXTENSION:
            $code = "UPLOAD_ERR_EXTENSION";
            $txt = "A PHP extension stopped the file upload";
            break;
        default:
            $code = "UPLOAD_GENERIC_ERR";
            $txt = "Unkown error while uploading";
            break;
    }
    _json_output(__error("ERROR_FILE_UPLOAD",$txt));
}

//TODO check user password
if (!isset($_REQUEST['user']) || !isset($_REQUEST['pass'])) {
    _json_output(__error("INVALID_CREDENTIALS","Invalid credentials"));
}
if ($_REQUEST['user'] != FSTORAGE_ACCESS_USER || $_REQUEST['pass'] != FSTORAGE_ACCESS_PASS) {
    _json_output(__error("INVALID_CREDENTIALS","Invalid credentials"));
}

if (!isset($_REQUEST['method'])) {
    _json_output(__error("MISSING_METHOD","No method supplied"));
}
$method = $_REQUEST['method'];

$api = new FStorage_API();
$result = null;
if(method_exists($api, $method)) {
	$arguments = array();
	$reflectionmethod = new ReflectionMethod($api, $method);
	foreach($reflectionmethod->getParameters() as $arg){
		if(isset($_REQUEST[$arg->name])){
			$arguments[$arg->name] = $_REQUEST[$arg->name];
		} else if($arg->isDefaultValueAvailable()){
			$arguments[$arg->name] = $arg->getDefaultValue();
		} else {
			_json_output(__error("MISSING_MANDATORY_ARGUMENT","Argument missing for {$arg->name}"));
		}
	}
	$result = call_user_func_array(array($api, $method), $arguments);
	_json_output($result);
}
else {
	_json_output(__error("INVALID_METHOD",""));
}


