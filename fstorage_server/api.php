<?php
require("lib.inc.php");

function my_error_handler($errno, $errstr, $errfile, $errline) {
    $code = $errno;
    $text = $errstr . ". File $errfile [$errline]";
    header("Content-type:application/json");
    die(json_encode(__error($code,$text), JSON_NUMERIC_CHECK));
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
register_shutdown_function('fatalErrorShutdownHandler');
error_reporting(0);

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


